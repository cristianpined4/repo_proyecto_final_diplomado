<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'correo',
        'dui',
        'nit',
        'nrc',
        'telefono',
        'direccion',
        'status',
        'user_id',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ventas()
    {
        return $this->hasMany('App\Models\Ventas', 'cliente_id', 'id');
    }
}
