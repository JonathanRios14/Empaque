<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = [
        'nombre',
    ];

    public function marcas()
    {
        return $this->hasMany(Marca::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}