<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'api_id_producto',
        'item',
        'codigo_producto',
        'codigo_caja',
        'codigo_precio',
        'nombre',
        'descripcion',
        'precio',
        'cantidad_bulto',
        'anillo',
        'cello',
        'upc',
        'sampler',
        'caja_local',
        'empresa_id',
        'marca_id',
        'vitola_id',
        'capa_id',
        'presentacion_id',
        'tipo_empaque_id',
    ];

    protected $casts = [
        'precio' => 'decimal:10',
        'cantidad_bulto' => 'integer',
        'anillo' => 'boolean',
        'cello' => 'boolean',
        'upc' => 'boolean',
        'sampler' => 'boolean',
        'caja_local' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function vitola()
    {
        return $this->belongsTo(Vitola::class);
    }

    public function capa()
    {
        return $this->belongsTo(Capa::class);
    }

    public function presentacion()
    {
        return $this->belongsTo(Presentacion::class);
    }

    public function tipoEmpaque()
    {
        return $this->belongsTo(TipoEmpaque::class);
    }

    public function actividades()
    {
        return $this->belongsToMany(Actividad::class, 'actividad_producto')
            ->withPivot('tipo_empaque_id', 'precio_mo')
            ->withTimestamps();
    }
}