<?php

namespace App\Services;

use App\Models\Productos as Producto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductoService
{
  /**
   * Obtener lista de productos con filtros y eager loading.
   */
  public function getProductos(array $filters = []): mixed
  {
    $limit = $filters['limit'] ?? 15;
    $search = $filters['search'] ?? '';
    $sortBy = $filters['sortBy'] ?? 'id';
    $descending = ($filters['descending'] ?? 'false') === 'true' ? 'desc' : 'asc';

    $allowedSorts = ['id', 'nombre', 'sku', 'stock', 'costo_promedio', 'precio_venta', 'created_at'];
    if (!in_array($sortBy, $allowedSorts)) {
      $sortBy = 'id';
    }

    $query = Producto::with(['categoria', 'subcategoria', 'marca', 'modelo', 'unidad']);

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('nombre', 'LIKE', "%{$search}%")
          ->orWhere('sku', 'LIKE', "%{$search}%")
          ->orWhere('codigo_interno', 'LIKE', "%{$search}%");
      });
    }

    $query->orderBy($sortBy, $descending);

    if ($limit > 0) {
      return $query->paginate($limit);
    }

    return $query->get();
  }

  /**
   * Crear un nuevo producto.
   */
  public function crearProducto(array $data): Producto
  {
    DB::beginTransaction();

    try {
      $producto = Producto::create($data);

      DB::commit();

      Cache::forget('productos_catalogos');

      return $producto;

    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  /**
   * Actualizar un producto.
   */
  public function actualizarProducto(int $id, array $data): Producto
  {
    $producto = Producto::findOrFail($id);

    DB::beginTransaction();

    try {
      $producto->update($data);

      DB::commit();

      Cache::forget('productos_catalogos');

      return $producto;

    } catch (\Throwable $th) {
      DB::rollBack();
      throw $th;
    }
  }

  /**
   * Subir imagen a producto.
   */
  public function uploadImage(int $id, $imageFile): array
  {
    $producto = Producto::findOrFail($id);

    $path = $imageFile->store('productos', 'public');

    $imagenes = json_decode($producto->imagenes, true) ?? [];
    $imagenes[] = $path;

    $producto->imagenes = json_encode($imagenes);
    $producto->save();

    return [
      'success' => true,
      'message' => 'Imagen subida correctamente',
      'image_path' => $path,
      'imagenes' => $imagenes
    ];
  }

  /**
   * Eliminar imagen de producto.
   */
  public function deleteImage(int $id, string $imagePath): array
  {
    $producto = Producto::findOrFail($id);

    $imagenes = json_decode($producto->imagenes, true) ?? [];

    if (($key = array_search($imagePath, $imagenes)) !== false) {
      if (Storage::disk('public')->exists($imagePath)) {
        Storage::disk('public')->delete($imagePath);
      }

      unset($imagenes[$key]);
      $imagenes = array_values($imagenes);

      $producto->imagenes = json_encode($imagenes);
      $producto->save();

      return [
        'success' => true,
        'message' => 'Imagen eliminada correctamente',
        'imagenes' => $imagenes
      ];
    }

    throw new \Exception('La imagen no existe en este producto');
  }
}