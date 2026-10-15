<?php

namespace App\Helpers;

use App\Models\Productos as Producto;
use App\Models\DetallesCompra as DetalleCompra;
use App\Models\MovimientosInventario as MovimientoInventario;
use App\Models\Kardex;
use Illuminate\Support\Facades\DB;

class InventoryValuator
{
    /**
     * Obtener el método de valuación activo desde la configuración.
     */
    public static function getValuationMethod(): string
    {
        return DB::table('settings')
            ->where('key', 'inventory_valuation_method')
            ->value('value') ?? 'promedio';
    }

    /**
     * Reducir stock de un producto aplicando el método de valuación correspondiente.
     * 
     * @return float El costo total acumulado del movimiento
     */
    public static function reduceStock(Producto $producto, int $cantidad, int $sucursalId, string $razon): float
    {
        $method = self::getValuationMethod();
        $stockAnterior = $producto->stock;
        $costoAnterior = $producto->costo_promedio;
        $nuevoStock = $stockAnterior - $cantidad;

        if ($nuevoStock < 0) {
            throw new \Exception("Stock insuficiente para el producto: {$producto->nombre}");
        }

        $costoTotalMovimiento = 0.0;

        if ($method === 'fifo' || $method === 'lifo') {
            // Obtener compras con stock disponible
            $query = DetalleCompra::where('producto_id', $producto->id)
                ->where('cantidad_restante', '>', 0)
                ->whereHas('compra', function ($q) use ($sucursalId) {
                    $q->where('sucursal_id', $sucursalId)->where('status', 'pagado');
                });

            if ($method === 'fifo') {
                $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'); // FIFO: Primero en entrar
            } else {
                $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'); // LIFO: Último en entrar
            }

            $lotes = $query->get();
            $cantidadRestantePorConsumir = $cantidad;

            foreach ($lotes as $lote) {
                if ($cantidadRestantePorConsumir <= 0) {
                    break;
                }

                $consumo = min($lote->cantidad_restante, $cantidadRestantePorConsumir);
                $lote->cantidad_restante -= $consumo;
                $lote->save();

                $costoTotalMovimiento += $consumo * $lote->precio_compra;
                $cantidadRestantePorConsumir -= $consumo;
            }

            // Si aún queda cantidad por consumir (porque el stock físico no coincide con los lotes en DB)
            if ($cantidadRestantePorConsumir > 0) {
                // Consumir del costo promedio actual como fallback
                $costoTotalMovimiento += $cantidadRestantePorConsumir * $costoAnterior;
            }

            $costoUnitarioMedio = $cantidad > 0 ? ($costoTotalMovimiento / $cantidad) : 0.0;
        } else {
            // Costo Promedio Ponderado
            $costoTotalMovimiento = $cantidad * $costoAnterior;
            $costoUnitarioMedio = $costoAnterior;
        }

        // Actualizar stock del producto
        $producto->stock = $nuevoStock;
        // El costo promedio no cambia en una salida bajo ningún método, se mantiene
        $producto->save();

        // Actualizar stock por sucursal
        self::updateSucursalStock($producto->id, $sucursalId, -$cantidad);

        // Registrar movimiento
        MovimientoInventario::create([
            'producto_id' => $producto->id,
            'tipo' => 'salida',
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitarioMedio,
            'razon' => $razon,
            'sucursal_id' => $sucursalId,
            'user_id' => auth()->id(),
        ]);

        // Registrar Kardex
        Kardex::create([
            'producto_id' => $producto->id,
            'cantidad_antes' => $stockAnterior,
            'costo_antes' => $costoAnterior,
            'cantidad_movimiento' => -$cantidad,
            'costo_movimiento' => $costoUnitarioMedio,
            'cantidad_despues' => $nuevoStock,
            'costo_despues' => $producto->costo_promedio,
            'sucursal_id' => $sucursalId,
            'user_id' => auth()->id(),
        ]);

        return $costoTotalMovimiento;
    }

    /**
     * Restaurar stock de un producto por devolución o cancelación.
     */
    public static function restoreStock(Producto $producto, int $cantidad, int $sucursalId, string $razon): void
    {
        $method = self::getValuationMethod();
        $stockAnterior = $producto->stock;
        $costoAnterior = $producto->costo_promedio;
        $nuevoStock = $stockAnterior + $cantidad;

        if ($method === 'fifo' || $method === 'lifo') {
            // En devoluciones, devolvemos el stock al lote más reciente que compramos o al primero con espacio consumido
            $lotes = DetalleCompra::where('producto_id', $producto->id)
                ->whereHas('compra', function ($q) use ($sucursalId) {
                    $q->where('sucursal_id', $sucursalId)->where('status', 'pagado');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $cantidadRestantePorDevolver = $cantidad;

            foreach ($lotes as $lote) {
                if ($cantidadRestantePorDevolver <= 0) {
                    break;
                }

                // Podemos devolver hasta el límite de la cantidad original comprada en ese lote
                $espacioDisponible = $lote->cantidad - $lote->cantidad_restante;
                if ($espacioDisponible > 0) {
                    $devolucion = min($espacioDisponible, $cantidadRestantePorDevolver);
                    $lote->cantidad_restante += $devolucion;
                    $lote->save();
                    $cantidadRestantePorDevolver -= $devolucion;
                }
            }

            // Si todavía queda por devolver (por ejemplo, porque excede las compras registradas), se crea un lote ficticio o se ignora
        }

        // Actualizar stock del producto
        $producto->stock = $nuevoStock;
        $producto->save();

        // Actualizar stock por sucursal
        self::updateSucursalStock($producto->id, $sucursalId, $cantidad);

        // Registrar movimiento
        MovimientoInventario::create([
            'producto_id' => $producto->id,
            'tipo' => 'entrada',
            'cantidad' => $cantidad,
            'costo_unitario' => $costoAnterior, // Usar el costo que tenía
            'razon' => $razon,
            'sucursal_id' => $sucursalId,
            'user_id' => auth()->id(),
        ]);

        // Registrar Kardex
        Kardex::create([
            'producto_id' => $producto->id,
            'cantidad_antes' => $stockAnterior,
            'costo_antes' => $costoAnterior,
            'cantidad_movimiento' => $cantidad,
            'costo_movimiento' => $costoAnterior,
            'cantidad_despues' => $nuevoStock,
            'costo_despues' => $producto->costo_promedio,
            'sucursal_id' => $sucursalId,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Registrar una compra y calcular el costo promedio si aplica.
     */
    public static function registerPurchase(Producto $producto, int $cantidad, float $precioCompra, int $sucursalId, string $razon, int $compraId): void
    {
        $stockAnterior = $producto->stock;
        $costoAnterior = $producto->costo_promedio;
        $nuevoStock = $stockAnterior + $cantidad;

        // Calcular costo promedio ponderado (se calcula como indicador global para el producto independientemente del método)
        if ($nuevoStock > 0) {
            $nuevoCostoPromedio = (($stockAnterior * $costoAnterior) + ($cantidad * $precioCompra)) / $nuevoStock;
        } else {
            $nuevoCostoPromedio = $precioCompra;
        }

        // Actualizar producto
        $producto->stock = $nuevoStock;
        $producto->costo_promedio = $nuevoCostoPromedio;
        $producto->save();

        // Actualizar stock por sucursal
        self::updateSucursalStock($producto->id, $sucursalId, $cantidad);

        // Registrar movimiento
        MovimientoInventario::create([
            'producto_id' => $producto->id,
            'tipo' => 'entrada',
            'cantidad' => $cantidad,
            'costo_unitario' => $precioCompra,
            'razon' => $razon,
            'sucursal_id' => $sucursalId,
            'user_id' => auth()->id(),
        ]);

        // Registrar Kardex
        Kardex::create([
            'producto_id' => $producto->id,
            'cantidad_antes' => $stockAnterior,
            'costo_antes' => $costoAnterior,
            'cantidad_movimiento' => $cantidad,
            'costo_movimiento' => $precioCompra,
            'cantidad_despues' => $nuevoStock,
            'costo_despues' => $nuevoCostoPromedio,
            'sucursal_id' => $sucursalId,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Actualizar stock por sucursal.
     */
    private static function updateSucursalStock(int $productoId, int $sucursalId, int $cantidadCambio): void
    {
        $prodSuc = DB::table('producto_sucursal')
            ->where('producto_id', $productoId)
            ->where('sucursal_id', $sucursalId)
            ->first();

        if ($prodSuc) {
            $nuevoStockSuc = max(0, $prodSuc->stock_actual + $cantidadCambio);
            DB::table('producto_sucursal')
                ->where('id', $prodSuc->id)
                ->update([
                    'stock_actual' => $nuevoStockSuc,
                    'updated_at' => now()
                ]);
        } else {
            $nuevoStockSuc = max(0, $cantidadCambio);
            DB::table('producto_sucursal')->insert([
                'producto_id' => $productoId,
                'sucursal_id' => $sucursalId,
                'stock_actual' => $nuevoStockSuc,
                'stock_minimo' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
