<?php

namespace App\Services;

use App\Models\Productos as Producto;
use App\Helpers\InventoryValuator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InventoryService
{
  /**
   * Actualizar stock de un producto (ajuste manual).
   */
  public function ajustarStock(int $productoId, int $nuevoStock, string $razon = 'Ajuste manual'): array
  {
    $producto = Producto::findOrFail($productoId);

    DB::beginTransaction();

    try {
      $cantidadMovimiento = $nuevoStock - $producto->stock;

      if ($cantidadMovimiento > 0) {
        // Simular compra para entrada
        InventoryValuator::registerPurchase(
          $producto,
          $cantidadMovimiento,
          $producto->costo_promedio,
          app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1,
          $razon,
          0
        );
      } else if ($cantidadMovimiento < 0) {
        // Simular venta para salida
        InventoryValuator::reduceStock(
          $producto,
          abs($cantidadMovimiento),
          app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1,
          $razon
        );
      }

      $producto->stock = $nuevoStock;
      $producto->save();

      Cache::forget('dashboard_metrics');
      Cache::forget('dashboard_metrics_v3');

      DB::commit();

      return [
        'status' => true,
        'message' => 'Stock ajustado correctamente',
        'producto' => $producto
      ];

    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  /**
   * Obtener kardex con eager loading.
   */
  public function getKardex(array $filters = []): mixed
  {
    $start = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
    $end = $filters['end_date'] ?? now()->endOfDay()->toDateTimeString();
    $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : null;

    $query = \App\Models\Kardex::with(['producto', 'producto.categoria'])
      ->whereBetween('created_at', [$start, $end]);

    if ($sucursalId) {
      $query->where('sucursal_id', $sucursalId);
    }

    return $query->orderBy('created_at', 'desc')->paginate(50);
  }
}