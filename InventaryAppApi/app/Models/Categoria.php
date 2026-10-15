<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    // Nombre de la tabla asociada
    protected $table = 'categorias';

    // Campos que se pueden asignar de forma masiva
    protected $fillable = [
        'name',
        'status',
    ];

    public function productos()
    {
        return $this->hasMany('App\Models\Productos', 'categoria_id', 'id');
    }
}
