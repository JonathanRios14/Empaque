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
        'imagen_caja',
        'imagen_anillado',
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
        'sync_hash',
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

    public function vinetaRegistros()
    {
        return $this->hasMany(VinetaRegistro::class);
    }

    public function imagenCajaUrl(): ?string
    {
        return $this->firstImageUrl($this->imagenesCaja());
    }

    public function imagenAnilladoUrl(): ?string
    {
        return $this->firstImageUrl($this->imagenesAnillado());
    }

    public function imagenesCaja(): array
    {
        return $this->imagePaths($this->imagen_caja);
    }

    public function imagenesAnillado(): array
    {
        return $this->imagePaths($this->imagen_anillado);
    }

    public function imagenesCajaUrls(): array
    {
        return $this->imageUrls($this->imagenesCaja());
    }

    public function imagenesAnilladoUrls(): array
    {
        return $this->imageUrls($this->imagenesAnillado());
    }

    public function imagenEmpaqueUrl(): ?string
    {
        return $this->imagenCajaUrl();
    }

    public function imagenesEmpaqueUrls(): array
    {
        return $this->imagenesCajaUrls();
    }

    public function imagenPrincipalUrl(): ?string
    {
        return $this->imagenEmpaqueUrl() ?? $this->imagenAnilladoUrl();
    }

    public function imagenesProductoUrls(): array
    {
        return array_values(array_unique(array_merge(
            $this->imagenesEmpaqueUrls(),
            $this->imagenesAnilladoUrls()
        )));
    }

    private function firstImageUrl(array $paths): ?string
    {
        foreach ($paths as $path) {
            $url = $this->externalImageUrl($path);

            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    private function imageUrls(array $paths): array
    {
        return collect($paths)
            ->map(fn (string $path) => $this->externalImageUrl($path))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function imagePaths($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return collect($value)
                ->flatten()
                ->filter(fn ($path) => is_scalar($path))
                ->map(fn ($path) => trim((string) $path))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->imagePaths($decoded);
        }

        return [$value];
    }

    private function externalImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if (str_starts_with($path, 'imagenes/')) {
            $path = 'storage/' . $path;
        }

        return rtrim(config('services.product_images.base_url'), '/') . '/' . ltrim($path, '/');
    }
}
