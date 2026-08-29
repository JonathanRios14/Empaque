<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';

    protected $fillable = [
        'api_id_actividad',
        'codigo_actividad',
        'nombre',
        'precio_mo',
    ];


    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'actividad_producto')
            ->withPivot('tipo_empaque_id', 'precio_mo', 'activo', 'origen', 'ultimo_escaneo_en')
            ->withTimestamps();
    }


    public function vinetaRegistros()
    {
        return $this->hasMany(VinetaRegistro::class);
    }
}
