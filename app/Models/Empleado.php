<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'fecha_ingreso',
        'cargo',
        'fecha_baja',
        'area',
        'activo',
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'fecha_baja' => 'datetime',
        'activo' => 'boolean',
    ];

    public function vinetaRegistros()
    {
        return $this->hasMany(VinetaRegistro::class);
    }
}
