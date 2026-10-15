<?php
namespace App\Http\Controllers;

use App\Models\Productos as Producto;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProductosController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $search = $request->input('search', '');
        $sortBy = $request->input('sortBy', 'nombre');
        $descending = $request->input('descending', 'false') === 'true' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'nombre', 'sku', 'stock', 'costo_promedio', 'precio_venta', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'nombre';
        }

        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1;
        $method = \App\Helpers\InventoryValuator::getValuationMethod();

        $query = Producto::select('productos.*')
            ->selectSub(function ($q) use ($sucursalId) {
                $q->selectRaw('COALESCE(stock_actual, 0)')
                    ->from('producto_sucursal')
                    ->whereColumn('producto_id', 'productos.id')
                    ->where('sucursal_id', $sucursalId)
                    ->limit(1);
            }, 'stock');

        if ($method === 'fifo') {
            $query->selectRaw("COALESCE((SELECT dc.precio_compra FROM detalles_compras dc JOIN compras c ON c.id = dc.compra_id WHERE dc.producto_id = productos.id AND dc.cantidad_restante > 0 AND c.status = 'pagado' AND c.sucursal_id = " . (int)$sucursalId . " ORDER BY dc.created_at ASC, dc.id ASC LIMIT 1), productos.costo_promedio) as costo_actual");
        } elseif ($method === 'lifo') {
            $query->selectRaw("COALESCE((SELECT dc.precio_compra FROM detalles_compras dc JOIN compras c ON c.id = dc.compra_id WHERE dc.producto_id = productos.id AND dc.cantidad_restante > 0 AND c.status = 'pagado' AND c.sucursal_id = " . (int)$sucursalId . " ORDER BY dc.created_at DESC, dc.id DESC LIMIT 1), productos.costo_promedio) as costo_actual");
        } else {
            $query->selectRaw('productos.costo_promedio as costo_actual');
        }

        $query->with(['categoria', 'subcategoria', 'marca', 'modelo', 'unidad']);

        if ($request->filled('categoria_id')) {
            $query->where('productos.categoria_id', $request->input('categoria_id'));
        }

        if ($request->filled('marca_id')) {
            $query->where('productos.marca_id', $request->input('marca_id'));
        }

        if ($request->filled('status')) {
            $statusVal = ($request->input('status') === '1' || $request->input('status') === 'true' || $request->input('status') === 1) ? 1 : 0;
            $query->where('productos.status', $statusVal);
        }

        if ($request->filled('stock_status')) {
            $stockStatus = $request->input('stock_status');
            if ($stockStatus === 'con_stock') {
                $query->whereRaw('COALESCE((SELECT stock_actual FROM producto_sucursal WHERE producto_id = productos.id AND sucursal_id = ? LIMIT 1), 0) > 0', [(int)$sucursalId]);
            } elseif ($stockStatus === 'sin_stock') {
                $query->whereRaw('COALESCE((SELECT stock_actual FROM producto_sucursal WHERE producto_id = productos.id AND sucursal_id = ? LIMIT 1), 0) = 0', [(int)$sucursalId]);
            }
        }

        if ($request->filled('tipo')) {
            $query->where('productos.tipo', $request->input('tipo'));
        }

        if ($request->filled('precio_min')) {
            $query->where('productos.precio_venta', '>=', (float)$request->input('precio_min'));
        }

        if ($request->filled('precio_max')) {
            $query->where('productos.precio_venta', '<=', (float)$request->input('precio_max'));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                    ->orWhere('sku', 'ILIKE', "%{$search}%")
                    ->orWhere('codigo_interno', 'ILIKE', "%{$search}%");
            });
        }

        $query->orderBy($sortBy, $descending);

        if ($limit > 0) {
            $productos = $query->paginate($limit);
        } else {
            $productos = $query->get();
        }

        return response()->json([
            'success' => true,
            'metodo_valuacion' => $method,
            'data' => $productos,
        ], 200);
    }

    /**
     * Catálogos para el modal de crear/editar producto.
     * Endpoint separado para que no se carguen en cada paginación.
     */
    public function catalogos()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('productos_catalogos', 600, function () {
            return [
                'categorias' => Categoria::where('status', 1)->get(['id', 'name']),
                'subcategorias' => \App\Models\Subcategoria::where('estado', 1)->get(['id', 'nombre', 'categoria_id']),
                'marcas' => \App\Models\Marca::where('estado', 1)->get(['id', 'nombre']),
                'modelos' => \App\Models\Modelo::where('estado', 1)->get(['id', 'nombre', 'marca_id']),
                'unidades' => \App\Models\Unidad::where('estado', 1)->get(['id', 'nombre', 'abreviatura']),
            ];
        });

        return response()->json(['success' => true, 'catalogos' => $data], 200);
    }

    // Almacenar un nuevo producto en la base de datos
    public function store(Request $request)
    {
        $this->authorize('create', Producto::class);

        $rules = [
            'nombre' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:productos',
            'codigo_interno' => 'nullable|string|max:100',
            'costo_promedio' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategoria_id' => 'nullable|exists:subcategorias,id',
            'marca_id' => 'nullable|exists:marcas,id',
            'modelo_id' => 'nullable|exists:modelos,id',
            'unidad_id' => 'nullable|exists:unidads,id',
            'tipo' => 'required|in:no perecedero,perecedero',
            'fecha_vencimiento' => 'nullable|date',
            'aplica_iva' => 'required|boolean',
            'status' => 'required|boolean',
            'stock' => 'nullable|integer|min:0',
        ];

        $messages = [
            'required' => 'El campo :attribute es obligatorio',
            'unique' => 'El campo :attribute ya existe',
            'exists' => 'La selección en :attribute es inválida',
        ];

        $request->validate($rules, $messages);

        try {
            DB::beginTransaction();
            $stockInicial = (int)$request->input('stock', 0);
            $costoPromedio = (float)$request->input('costo_promedio', 0.0);
            $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1;

            $producto = Producto::create([
                'nombre' => $request->nombre,
                'sku' => $request->sku,
                'codigo_interno' => $request->codigo_interno,
                'costo_promedio' => $costoPromedio,
                'precio_venta' => $request->precio_venta,
                'categoria_id' => $request->categoria_id,
                'subcategoria_id' => $request->subcategoria_id,
                'marca_id' => $request->marca_id,
                'modelo_id' => $request->modelo_id,
                'unidad_id' => $request->unidad_id,
                'tipo' => $request->tipo,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'aplica_iva' => $request->boolean('aplica_iva'),
                'status' => $request->boolean('status'),
                'stock' => 0, // initially 0 globally
            ]);

            if ($stockInicial > 0) {
                \App\Helpers\InventoryValuator::registerPurchase(
                    $producto,
                    $stockInicial,
                    $costoPromedio,
                    $sucursalId,
                    'Stock inicial al registrar producto',
                    0
                );
            } else {
                DB::table('producto_sucursal')->insert([
                    'producto_id' => $producto->id,
                    'sucursal_id' => $sucursalId,
                    'stock_actual' => 0,
                    'stock_minimo' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto creado con éxito',
                'data' => $producto
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    // Actualizar un producto existente en la base de datos
    public function update(Request $request, $id)
    {
        $producto = Producto::findOrFail($id);
        $this->authorize('update', $producto);

        $rules = [
            'nombre' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:productos,sku,' . $id,
            'codigo_interno' => 'nullable|string|max:100',
            'costo_promedio' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'subcategoria_id' => 'nullable|exists:subcategorias,id',
            'marca_id' => 'nullable|exists:marcas,id',
            'modelo_id' => 'nullable|exists:modelos,id',
            'unidad_id' => 'nullable|exists:unidads,id',
            'tipo' => 'required|in:no perecedero,perecedero',
            'fecha_vencimiento' => 'nullable|date',
            'aplica_iva' => 'required|boolean',
            'status' => 'required|boolean',
        ];

        $messages = [
            'required' => 'El campo :attribute es obligatorio',
            'unique' => 'El campo :attribute ya existe',
            'exists' => 'La selección en :attribute es inválida',
        ];

        $request->validate($rules, $messages);

        try {
            DB::beginTransaction();
            $updateData = $request->only([
                'nombre',
                'sku',
                'codigo_interno',
                'costo_promedio',
                'precio_venta',
                'categoria_id',
                'subcategoria_id',
                'marca_id',
                'modelo_id',
                'unidad_id',
                'tipo',
                'fecha_vencimiento',
            ]);
            $updateData['aplica_iva'] = $request->boolean('aplica_iva');
            $updateData['status'] = $request->boolean('status');

            $producto->update($updateData);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado con éxito',
                'data' => $producto
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    // Eliminar un producto de la base de datos
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $this->authorize('delete', $producto);

        try {
            DB::beginTransaction();

            // Borrar imágenes físicas si las hay
            if ($producto->imagenes) {
                $imgs = json_decode($producto->imagenes, true) ?? [];
                foreach ($imgs as $img) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($img);
                }
            }

            $producto->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado con éxito'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el producto porque está siendo utilizado (o ha ocurrido un error): ' . $th->getMessage()
            ], 500);
        }
    }

    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120'
        ]);

        $producto = Producto::findOrFail($id);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('productos', 'public');

            $imagenes = json_decode($producto->imagenes, true) ?? [];
            $imagenes[] = $path;

            $producto->imagenes = json_encode($imagenes);
            $producto->save();

            // Eliminar carpeta storage/app/private si fue creada por el sistema
            $privateDir = storage_path('app/private');
            if (\Illuminate\Support\Facades\File::isDirectory($privateDir)) {
                \Illuminate\Support\Facades\File::deleteDirectory($privateDir);
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'image_path' => $path,
                'imagenes' => $imagenes
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'No se recibió ninguna imagen'], 400);
    }

    public function deleteImage(Request $request, $id)
    {
        $request->validate([
            'image_path' => 'required|string'
        ]);

        $producto = Producto::findOrFail($id);
        $imagePath = $request->image_path;

        $imagenes = json_decode($producto->imagenes, true) ?? [];

        if (($key = array_search($imagePath, $imagenes)) !== false) {
            // Delete from disk
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
            }

            // Remove from array
            unset($imagenes[$key]);

            // Reindex array so it stays a JSON array and not an object in DB
            $imagenes = array_values($imagenes);

            $producto->imagenes = json_encode($imagenes);
            $producto->save();

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
                'imagenes' => $imagenes
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'La imagen no existe en este producto'], 404);
    }

    /**
     * Búsqueda asíncrona optimizada de productos para Ventas, Compras y Kardex con 100,000+ registros.
     */
    public function search(Request $request)
    {
        $term = trim($request->input('q', $request->input('search', '')));
        $limit = min(50, max(1, (int)$request->input('limit', 25)));
        $sucursalId = app()->bound('active_sucursal_id') ? app('active_sucursal_id') : 1;

        $query = Producto::select([
            'productos.id',
            'productos.nombre',
            'productos.sku',
            'productos.codigo_interno',
            'productos.precio_venta',
            'productos.costo_promedio',
            'productos.aplica_iva',
            'productos.status'
        ])
        ->selectSub(function ($q) use ($sucursalId) {
            $q->selectRaw('COALESCE(stock_actual, 0)')
                ->from('producto_sucursal')
                ->whereColumn('producto_id', 'productos.id')
                ->where('sucursal_id', $sucursalId)
                ->limit(1);
        }, 'stock')
        ->where('productos.status', true);

        if (!empty($term)) {
            $query->where(function ($q) use ($term) {
                $q->where('productos.nombre', 'ILIKE', "%{$term}%")
                    ->orWhere('productos.sku', 'ILIKE', "%{$term}%")
                    ->orWhere('productos.codigo_interno', 'ILIKE', "%{$term}%");
            });
        }

        $productos = $query->orderBy('productos.nombre', 'asc')->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $productos
        ], 200);
    }
}