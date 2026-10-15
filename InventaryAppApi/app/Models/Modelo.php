<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{
    use HasFactory;

    protected $fillable = ['marca_id', 'nombre', 'estado'];

    public function marca()
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }
}
