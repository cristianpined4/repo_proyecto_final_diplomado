<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ventas extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_venta',
        'subtotal',
        'monto_iva',
        'porcentaje_iva_aplicado',
        'cliente_id',
        'user_id',
        'sucursal_id',
        'status',
        'serie',
        'correlativo',
        'metodo_pago',
        'monto_recibido',
        'vuelto',
    ];

    protected $casts = [
        'total_venta' => 'float',
        'subtotal' => 'float',
        'monto_iva' => 'float',
        'monto_recibido' => 'float',
        'vuelto' => 'float',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function cliente()
    {
        return $this->belongsTo('App\Models\Clientes', 'cliente_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    public function detallesVenta()
    {
        return $this->hasMany('App\Models\DetallesVenta', 'venta_id', 'id');
    }
}
