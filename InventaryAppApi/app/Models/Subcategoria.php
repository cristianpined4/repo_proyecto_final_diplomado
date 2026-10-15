<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'categoria_id', 'nombre', 'estado'
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return $this->nombre;
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function productos()
    {
        return $this->hasMany(Productos::class, 'subcategoria_id');
    }
}
