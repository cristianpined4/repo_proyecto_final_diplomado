<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Unidad;

class ProductosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Genera 10,000 productos del catálogo de Centro de Plástico El Limeño
     */
    public function run(): void
    {
        $this->command->info('>>> [4/7] Generando 10,000 Productos con inventario y sucursales...');

        $total = 10000;
        $batchSize = 1000;

        // Cargar mapas de relaciones de catálogos
        $categorias = DB::table('categorias')->pluck('id')->toArray();
        $subcategoriasPorCat = [];
        foreach ($categorias as $catId) {
            $subs = DB::table('subcategorias')->where('categoria_id', $catId)->pluck('id')->toArray();
            $subcategoriasPorCat[$catId] = !empty($subs) ? $subs : [null];
        }

        $marcas = DB::table('marcas')->pluck('id')->toArray();
        $modelosPorMarca = [];
        foreach ($marcas as $mId) {
            $mods = DB::table('modelos')->where('marca_id', $mId)->pluck('id')->toArray();
            $modelosPorMarca[$mId] = !empty($mods) ? $mods : [null];
        }

        $unidades = DB::table('unidads')->pluck('id')->toArray();
        if (empty($unidades)) $unidades = [1];

        $sucursales = DB::table('sucursals')->pluck('id')->toArray();
        if (empty($sucursales)) $sucursales = [1];

        // Plantillas de productos por familia para Centro de Plástico El Limeño
        $plantillasProductos = [
            // Bolsas y Empaques (cat 1)
            [
                'cat_index' => 0,
                'items' => [
                    ['Bolsa Gabacha Blanca 8x12', 0.85, 2],
                    ['Bolsa Gabacha Blanca 10x14', 1.15, 2],
                    ['Bolsa Gabacha Blanca 12x18', 1.65, 2],
                    ['Bolsa Gabacha Blanca 14x20', 2.10, 2],
                    ['Bolsa Gabacha Blanca 16x24', 2.80, 2],
                    ['Bolsa Gabacha Blanca 18x28', 3.50, 2],
                    ['Bolsa Gabacha Negra Jumbo 24x36', 4.50, 2],
                    ['Bolsa Gabacha Rayada Color 12x18', 1.80, 2],
                    ['Bolsa para Basura Mediana 20x30 Paq x25', 1.90, 2],
                    ['Bolsa para Basura Grande 30x40 Paq x10', 2.25, 2],
                    ['Bolsa para Basura Jumbo 40x50 Paq x10', 3.20, 2],
                    ['Bolsa Ziploc Hermética 1/4 lb Paq x100', 1.40, 2],
                    ['Bolsa Ziploc Hermética 1/2 lb Paq x100', 1.85, 2],
                    ['Bolsa Ziploc Hermética 1 lb Paq x100', 2.50, 2],
                    ['Bolsa Ziploc Hermética 2 lb Paq x100', 3.75, 2],
                    ['Bolsa en Rollo Transparente 10x14 Rollo x500', 4.20, 8],
                    ['Película Strech Film Embalaje Industrial 18 pulg', 12.50, 8],
                    ['Rollo Plástico Negro Ensilaje Calibre 600', 45.00, 8],
                ]
            ],
            // Desechables y Fiestas (cat 2)
            [
                'cat_index' => 1,
                'items' => [
                    ['Vaso Plástico Desechable 5.5oz Paq x50', 0.65, 2],
                    ['Vaso Plástico Desechable 7oz Paq x50', 0.85, 2],
                    ['Vaso Plástico Desechable 10oz Paq x50', 1.10, 2],
                    ['Vaso Plástico Desechable 12oz Paq x50', 1.35, 2],
                    ['Vaso Plástico Desechable 16oz Paq x50', 1.80, 2],
                    ['Vaso Térmico Duroport 8oz Cja x1000', 18.00, 7],
                    ['Vaso Térmico Duroport 12oz Cja x1000', 22.50, 7],
                    ['Plato Térmico #1 Hondo Fardo x500', 14.50, 3],
                    ['Plato Térmico #2 Tendido Fardo x500', 16.00, 3],
                    ['Plato Térmico #3 Pastelero Fardo x500', 12.00, 3],
                    ['Tenedor Plástico Blanco Fuerte Paq x100', 1.25, 2],
                    ['Cuchara Plástica Sopera Blanca Paq x100', 1.25, 2],
                    ['Cuchillo Plástico Desechable Paq x100', 1.30, 2],
                    ['Pajilla con Envoltura Papel Caja x500', 2.80, 7],
                    ['Pajilla Gruesa para Granizados Paq x100', 1.10, 2],
                    ['Contenedor Térmico Clamshell Hamburguesero Fdo x200', 9.50, 3],
                    ['Contenedor Térmico 3 Divisiones Fdo x200', 13.50, 3],
                    ['Contenedor Térmico 1 División Fdo x200', 12.00, 3],
                ]
            ],
            // Contenedores y Almacenamiento (cat 3)
            [
                'cat_index' => 2,
                'items' => [
                    ['Paila Plástica No. 10 Reforzada', 1.50, 1],
                    ['Paila Plástica No. 14 Gran Capacidad', 2.25, 1],
                    ['Paila Plástica No. 18 Industrial', 3.50, 1],
                    ['Huacal Plástico No. 12 Multiusos', 1.75, 1],
                    ['Huacal Plástico No. 16 Colores Surtidos', 2.80, 1],
                    ['Bote Plástico con Tapa Hermética 2 Litros', 2.40, 1],
                    ['Bote Plástico con Tapa Hermética 4 Litros', 3.80, 1],
                    ['Jarra Plástica Graduada 2 Litros con Tapa', 2.20, 1],
                    ['Picchel Plástico Familiar 3 Litros', 3.10, 1],
                    ['Set de 3 Tuppers Herméticos Cuadrados', 4.50, 2],
                    ['Set de 5 Fiambreras Multiusos Redondas', 6.20, 2],
                    ['Caja Organizadora Plástica con Tapa 30L', 8.50, 1],
                    ['Caja Organizadora Plástica Transparente 60L', 14.00, 1],
                    ['Gavetero Plástico Organizador 4 Gavetas', 28.00, 1],
                    ['Gavetero Plástico Infantil 5 Gavetas', 34.00, 1],
                ]
            ],
            // Hogar y Limpieza (cat 4)
            [
                'cat_index' => 3,
                'items' => [
                    ['Escoba Plástica Tipo Cepillo con Mango', 2.20, 1],
                    ['Escoba Plástica Abanico para Exteriores', 2.60, 1],
                    ['Basurero Plástico con Tapa Vaivén 12L', 4.20, 1],
                    ['Basurero Plástico con Pedal 20L Rimax', 9.50, 1],
                    ['Basurero Plástico con Ruedas y Pedal 50L', 22.00, 1],
                    ['Cubeta Plástica 10L con Asa Metálica', 2.80, 1],
                    ['Cubeta con Exprimidor Industrial 20L', 18.50, 1],
                    ['Cesta Plástica para Ropa Calada Redonda', 5.50, 1],
                    ['Cesta Plástica para Ropa Rectangular con Tapa', 8.20, 1],
                    ['Ganchos Plásticos para Ropa Docena x12', 1.75, 5],
                    ['Pinzas Plásticas para Ropa Paq x24', 1.20, 2],
                ]
            ],
            // Mobiliario Plástico (cat 5)
            [
                'cat_index' => 4,
                'items' => [
                    ['Silla Plástica sin Brazos Cafetería Rimax Blanca', 8.50, 1],
                    ['Silla Plástica sin Brazos Cafetería Roja', 8.50, 1],
                    ['Silla Plástica con Brazos Reclinable Rimax', 12.80, 1],
                    ['Silla Plástica Infantil Colores Surtidos', 4.50, 1],
                    ['Mesa Plástica Cuadrada 80x80cm Rimax Blanca', 24.00, 1],
                    ['Mesa Plástica Redonda 90cm Rimax Café', 28.50, 1],
                    ['Banco Plástico Alto para Barra', 6.80, 1],
                    ['Banco Plástico Bajo Multiusos', 3.20, 1],
                ]
            ],
            // Línea Industrial y Agrícola (cat 6)
            [
                'cat_index' => 5,
                'items' => [
                    ['Bidón Plástico 55 Galones con Tapa Rosca y Grifo', 38.00, 1],
                    ['Bidón Plástico 30 Galones Dos Bocas', 26.00, 1],
                    ['Bidón Plástico 15 Galones Asa Resistente', 16.50, 1],
                    ['Tanque Plástico de Polietileno 450 Litros', 75.00, 1],
                    ['Malla Sombra Agrícola 50% Rollo 4x100m', 68.00, 8],
                    ['Malla Sombra Agrícola 80% Rollo 4x100m', 85.00, 8],
                    ['Caja Plástica Cosechera Calada Agrícola', 6.50, 1],
                    ['Gaveta Plástica Industrial Apilable', 4.80, 1],
                ]
            ],
            // Recipientes Térmicos y Bebidas (cat 7)
            [
                'cat_index' => 6,
                'items' => [
                    ['Termo para Agua Plástico 1/2 Galón Rimax', 4.80, 1],
                    ['Termo para Agua Plástico 1 Galón con Pico', 7.50, 1],
                    ['Termo Térmico Familiar 2 Galones con Grifo', 13.50, 1],
                    ['Hielera Plástica Térmica 16 Litros Rimax', 19.50, 1],
                    ['Hielera Plástica Térmica 32 Litros con Asa', 29.00, 1],
                    ['Pachón Plástico Escolar 500ml con Tapa', 1.50, 1],
                    ['Dispensador de Bebidas Plástico 5 Galones con Grifo', 16.00, 1],
                ]
            ],
            // Juguetería y Varios (cat 8)
            [
                'cat_index' => 7,
                'items' => [
                    ['Pelotas Plásticas Surtidas Malla x50', 5.50, 2],
                    ['Balde de Playa Plástico con Accesorios', 2.80, 1],
                    ['Maceta Plástica Redonda No. 12 Terracota', 1.60, 1],
                    ['Maceta Plástica Jardinera Colgante No. 16', 2.90, 1],
                    ['Set de Moldes y Accesorios Plásticos Fiesta', 3.50, 2],
                ]
            ]
        ];

        // Aplanar catálogo de productos base con sus categorías
        $catalogoBase = [];
        foreach ($plantillasProductos as $grupo) {
            $catId = $categorias[$grupo['cat_index'] % count($categorias)];
            $subsDisponibles = $subcategoriasPorCat[$catId];

            foreach ($grupo['items'] as $item) {
                $catalogoBase[] = [
                    'nombre' => $item[0],
                    'costo' => $item[1],
                    'unidad_id' => $item[2],
                    'categoria_id' => $catId,
                    'subcategorias' => $subsDisponibles,
                ];
            }
        }

        // Variaciones para expandir a 10,000 productos únicos y descriptivos
        $variaciones = [
            'Estándar', 'Reforzado', 'Económico', 'Industrial', 'Pesado', 'Especial',
            'Super Resistente', 'Línea Hogar', 'Línea Comercial', 'Grado Alimenticio',
            'Calibre Especial', 'Edición Exportación', 'Premium', 'Uso Rudo', 'Multicolor'
        ];

        // Rango de fechas: 2016-01-01 hasta 2026-10-11
        $startTs = strtotime('2016-01-01 08:00:00');
        $endTs = strtotime('2026-10-11 18:00:00');
        $tsInterval = ($endTs - $startTs) / $total;

        $productosRecords = [];
        $productoIdActual = 1;

        for ($i = 1; $i <= $total; $i++) {
            $baseIndex = ($i - 1) % count($catalogoBase);
            $base = $catalogoBase[$baseIndex];

            $marcaId = $marcas[($i) % count($marcas)];
            $modelosDisponibles = $modelosPorMarca[$marcaId];
            $modeloId = $modelosDisponibles[($i) % count($modelosDisponibles)];

            $subId = $base['subcategorias'][($i) % count($base['subcategorias'])];
            $variacion = $variaciones[((int)(($i - 1) / count($catalogoBase))) % count($variaciones)];
            $secuencia = (int)(($i - 1) / (count($catalogoBase) * count($variaciones))) + 1;

            $nombreProducto = $secuencia > 1
                ? "{$base['nombre']} {$variacion} - Ref #{$secuencia}"
                : "{$base['nombre']} {$variacion}";

            // Costo con ligera variación realista por referencia
            $factorCosto = 1.0 + (($i % 15) * 0.03);
            $costoPromedio = round($base['costo'] * $factorCosto, 2);
            // Margen del 30% a 35%
            $precioVenta = round($costoPromedio * 1.32, 2);

            $sku = sprintf('PLAS-%06d', $i);
            $codigoInterno = sprintf('COD-%05d', $i);
            if ($i <= 40) {
                // Productos con bajo stock (< 15) para alimentar el dashboard bajo método FIFO
                $stock = 3 + ($i % 11); // 3 a 13 unidades
            } elseif ($i <= 55) {
                // Productos agotados (stock = 0) para filtros de disponibilidad
                $stock = 0;
            } else {
                // Stock regular operativo
                $stock = 25 + ($i * 7 % 380);
            }

            // Fechas cronológicas 2016-2026
            $itemTs = (int)($startTs + ($i * $tsInterval) + rand(-3600, 3600));
            if ($itemTs > $endTs) $itemTs = $endTs;
            $createdAt = date('Y-m-d H:i:s', $itemTs);

            $productosRecords[] = [
                'id' => $i,
                'nombre' => $nombreProducto,
                'sku' => $sku,
                'codigo_interno' => $codigoInterno,
                'stock' => $stock,
                'costo_promedio' => $costoPromedio,
                'precio_venta' => $precioVenta,
                'categoria_id' => $base['categoria_id'],
                'subcategoria_id' => $subId,
                'marca_id' => $marcaId,
                'modelo_id' => $modeloId,
                'unidad_id' => $base['unidad_id'],
                'status' => ($i % 40 !== 0), // 97.5% activos, 2.5% inactivos
                'tipo' => 'no perecedero',
                'aplica_iva' => true,
                'fecha_vencimiento' => null,
                'imagenes' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($productosRecords) >= $batchSize) {
                DB::table('productos')->insert($productosRecords);

                // Insertar registros correspondientes en producto_sucursal cuadrando la suma exacta
                $sucursalRecords = [];
                foreach ($productosRecords as $p) {
                    $st = $p['stock'];
                    $s1 = (int)ceil($st / 3);
                    $s2 = (int)floor($st / 3);
                    $s3 = $st - $s1 - $s2;
                    $cantidadesPorSucursal = [$s1, $s2, $s3];

                    foreach ($sucursales as $sIdx => $sId) {
                        $sucursalRecords[] = [
                            'producto_id' => $p['id'],
                            'sucursal_id' => $sId,
                            'stock_actual' => $cantidadesPorSucursal[$sIdx % 3] ?? 0,
                            'stock_minimo' => 10,
                            'stock_maximo' => 500,
                            'created_at' => $p['created_at'],
                            'updated_at' => $p['updated_at'],
                        ];
                    }
                }
                DB::table('producto_sucursal')->insert($sucursalRecords);

                $productoIdActual += count($productosRecords);
                $productosRecords = [];
                $this->command->getOutput()->write("\rProductos insertados: " . ($productoIdActual - 1) . " / {$total}");
            }
        }

        if (!empty($productosRecords)) {
            DB::table('productos')->insert($productosRecords);
            $sucursalRecords = [];
            foreach ($productosRecords as $p) {
                $st = $p['stock'];
                $s1 = (int)ceil($st / 3);
                $s2 = (int)floor($st / 3);
                $s3 = $st - $s1 - $s2;
                $cantidadesPorSucursal = [$s1, $s2, $s3];

                foreach ($sucursales as $sIdx => $sId) {
                    $sucursalRecords[] = [
                        'producto_id' => $p['id'],
                        'sucursal_id' => $sId,
                        'stock_actual' => $cantidadesPorSucursal[$sIdx % 3] ?? 0,
                        'stock_minimo' => 10,
                        'stock_maximo' => 500,
                        'created_at' => $p['created_at'],
                        'updated_at' => $p['updated_at'],
                    ];
                }
            }
            DB::table('producto_sucursal')->insert($sucursalRecords);
        }

        // Sincronizar secuencia en PostgreSQL
        DB::statement("SELECT setval('productos_id_seq', (SELECT MAX(id) FROM productos))");
        DB::statement("SELECT setval('producto_sucursal_id_seq', (SELECT MAX(id) FROM producto_sucursal))");

        $this->command->info("\nProductos completados exitosamente: {$total} productos con stock y sucursales asignadas.");
    }
}
