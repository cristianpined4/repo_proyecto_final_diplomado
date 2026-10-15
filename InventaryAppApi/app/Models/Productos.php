<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productos extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'sku',
        'stock',
        'costo_promedio',
        'precio_venta',
        'categoria_id',
        'subcategoria_id',
        'marca_id',
        'modelo_id',
        'unidad_id',
        'tipo',
        'fecha_vencimiento',
        'status',
        'codigo_interno',
        'aplica_iva',
        'imagenes',
    ];

    protected $casts = [
        'status' => 'boolean',
        'aplica_iva' => 'boolean',
        'costo_promedio' => 'float',
        'precio_venta' => 'float',
    ];

    // NOTA: Se eliminó $appends['categoria'] que causaba N+1 queries.
    // Usar ->with('categoria') en los controllers en su lugar.

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(\App\Models\Subcategoria::class, 'subcategoria_id');
    }

    public function marca()
    {
        return $this->belongsTo(\App\Models\Marca::class, 'marca_id');
    }

    public function modelo()
    {
        return $this->belongsTo(\App\Models\Modelo::class, 'modelo_id');
    }

    public function unidad()
    {
        return $this->belongsTo(\App\Models\Unidad::class, 'unidad_id');
    }

    public function movimientosInventario()
    {
        return $this->hasMany(\App\Models\MovimientosInventario::class, 'producto_id');
    }

    public function kardex()
    {
        return $this->hasMany(\App\Models\Kardex::class, 'producto_id');
    }

    public function detallesVenta()
    {
        return $this->hasMany(\App\Models\DetallesVenta::class, 'producto_id');
    }

    public function detallesCompra()
    {
        return $this->hasMany(\App\Models\DetallesCompra::class, 'producto_id');
    }
}
