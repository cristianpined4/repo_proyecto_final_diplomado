<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallesVenta extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_venta',
    ];

    public function producto()
    {
        return $this->belongsTo('App\Models\Productos', 'producto_id', 'id');
    }

    public function venta()
    {
        return $this->belongsTo('App\Models\Ventas', 'venta_id', 'id');
    }
}
