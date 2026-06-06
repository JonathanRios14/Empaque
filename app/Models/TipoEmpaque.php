<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEmpaque extends Model
{
    protected $table = 'tipo_empaques';

    protected $fillable = [
        'nombre',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }

    public function actividades()
    {
        return $this->belongsToMany(Actividad::class, 'actividad_producto')
            ->withPivot('producto_id', 'precio_mo')
            ->withTimestamps();
    }
}