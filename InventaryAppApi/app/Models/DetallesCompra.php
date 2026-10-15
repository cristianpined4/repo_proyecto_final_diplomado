<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallesCompra extends Model
{
    use HasFactory;

    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantidad',
        'cantidad_restante',
        'precio_compra',
    ];

    public function producto()
    {
        return $this->belongsTo('App\Models\Productos', 'producto_id', 'id');
    }

    public function compra()
    {
        return $this->belongsTo('App\Models\Compras', 'compra_id', 'id');
    }
}
