<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compras extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_compra',
        'subtotal',
        'monto_iva',
        'porcentaje_iva_aplicado',
        'user_id',
        'sucursal_id',
        'proveedor_id',
        'status',
        'numero_factura',
    ];

    protected $casts = [
        'total_compra' => 'float',
        'subtotal' => 'float',
        'monto_iva' => 'float',
        'porcentaje_iva_aplicado' => 'float',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function detallesCompra()
    {
        return $this->hasMany('App\Models\DetallesCompra', 'compra_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
