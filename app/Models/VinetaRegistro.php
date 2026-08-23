<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VinetaRegistro extends Model
{
    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_ANULADO = 'anulado';

    protected $fillable = [
        'vineta_id',
        'producto_id',
        'actividad_id',
        'empleado_id',
        'registrado_por_user_id',
        'codigo_vineta',
        'vineta_api_id',
        'id_pendiente_empaque',
        'id_detalle_programacion',
        'vineta_fecha',
        'producto_codigo',
        'producto_item',
        'producto_nombre',
        'marca',
        'capa',
        'vitola',
        'tipo_empaque',
        'orden',
        'orden_del_sistema',
        'actividad_api_id',
        'actividad_codigo',
        'actividad_nombre',
        'actividad_tipo_empaque',
        'precio_mo',
        'empleado_codigo',
        'empleado_nombre',
        'cantidad_puros',
        'cantidad_cajones',
        'cantidad_actividades',
        'minutos_trabajados',
        'fecha_registro',
        'hora_registro',
        'registrado_en',
        'registrado_por_nombre',
        'estado',
        'observacion',
        'anulado_por_user_id',
        'anulado_en',
        'motivo_anulacion',
        'raw_payload',
    ];

    protected $casts = [
        'vineta_api_id' => 'integer',
        'vineta_fecha' => 'date',
        'actividad_api_id' => 'integer',
        'precio_mo' => 'decimal:4',
        'cantidad_puros' => 'integer',
        'cantidad_cajones' => 'integer',
        'cantidad_actividades' => 'integer',
        'minutos_trabajados' => 'integer',
        'fecha_registro' => 'date',
        'registrado_en' => 'datetime',
        'anulado_en' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function getTotalMoAttribute(): float
    {
        return (float) $this->cantidad_puros * (float) ($this->precioMoEfectivo() ?? 0);
    }

    public function productoNombreReporte(): string
    {
        return $this->textoReportePreferido(
            $this->vineta?->nombre,
            $this->producto_nombre,
            'Sin producto'
        );
    }

    public function tipoEmpaqueReporte(): string
    {
        return $this->textoReportePreferido(
            $this->vineta?->tipo_empaque,
            $this->tipo_empaque,
            'N/A'
        );
    }

    private function textoReportePreferido(?string $principal, ?string $secundario, string $fallback): string
    {
        foreach ([$principal, $secundario] as $value) {
            $text = trim((string) $value);

            if ($text !== '' && ! in_array(strtolower($text), ['ninguna', 'ninguno', 'n/a', 'na', 'null'], true)) {
                return $text;
            }
        }

        return $fallback;
    }

    public function precioMoEfectivo(): ?float
    {
        if (array_key_exists('precio_actividad_catalogo', $this->attributes)) {
            $precioCatalogo = $this->attributes['precio_actividad_catalogo'];

            return $precioCatalogo !== null && (float) $precioCatalogo > 0
                ? (float) $precioCatalogo
                : ($this->precio_mo === null ? null : (float) $this->precio_mo);
        }

        return self::precioMoActividadCatalogo($this->actividad_id)
            ?? ($this->precio_mo === null ? null : (float) $this->precio_mo);
    }

    public static function precioMoActividadCatalogo(?int $actividadId): ?float
    {
        if (! $actividadId) {
            return null;
        }

        $precio = DB::table('actividad_producto')
            ->where('actividad_id', $actividadId)
            ->whereNotNull('precio_mo')
            ->where('precio_mo', '>', 0)
            ->min('precio_mo');

        return $precio === null ? null : (float) $precio;
    }

    public function getTotalActividadesAttribute(): int
    {
        return (int) $this->cantidad_puros * $this->cantidadActividadesValor();
    }

    public function cantidadActividadesValor(): int
    {
        if (array_key_exists('cantidad_actividades', $this->attributes) && $this->attributes['cantidad_actividades'] !== null) {
            $cantidad = (int) $this->attributes['cantidad_actividades'];

            if ($cantidad > 0) {
                return $cantidad;
            }
        }

        return self::cantidadActividadesDesdeNombre($this->actividad_nombre);
    }

    public function modoRegistro(): string
    {
        $payload = $this->raw_payload;

        if (
            is_array($payload)
            && in_array(($payload['modo_registro'] ?? null), ['por_tarea', 'por_hora'], true)
        ) {
            return $payload['modo_registro'];
        }

        if (strtolower((string) $this->actividad_nombre) === 'control por hora') {
            return 'por_hora';
        }

        return 'por_tarea';
    }

    public function esPorHoraOrdinario(): bool
    {
        return $this->modoRegistro() === 'por_hora';
    }

    public function tiempoTrabajadoReporteTexto(): string
    {
        return $this->esPorHoraOrdinario()
            ? 'Por hora ordinario'
            : $this->tiempoTrabajadoTexto();
    }

    public function tiempoTrabajadoTexto(): string
    {
        return self::minutosATiempoTexto((int) ($this->minutos_trabajados ?? 0));
    }

    public static function minutosATiempoTexto(int $minutos): string
    {
        $minutos = max($minutos, 0);
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        if ($horas === 0) {
            return $resto.' min';
        }

        if ($resto === 0) {
            return $horas.' h';
        }

        return $horas.' h '.$resto.' min';
    }

    public static function cantidadActividadesDesdeNombre(?string $nombre): int
    {
        $nombre = trim((string) $nombre);

        if ($nombre === '') {
            return 1;
        }

        $partes = preg_split('/\s*,\s*/', $nombre) ?: [];
        $total = 0;

        foreach ($partes as $parte) {
            $parte = trim($parte);

            if ($parte === '') {
                continue;
            }

            if (preg_match('/^(\d+)\s+/', $parte, $matches)) {
                $total += max((int) $matches[1], 1);

                continue;
            }

            $total++;
        }

        return max($total, 1);
    }

    public function fechaHoraRegistroTexto(): string
    {
        if (! $this->fecha_registro || ! $this->hora_registro) {
            return 'N/A';
        }

        $hora = (string) $this->hora_registro;

        if (substr_count($hora, ':') === 1) {
            $hora .= ':00';
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $this->fecha_registro->format('Y-m-d').' '.$hora,
                'America/Tegucigalpa'
            )->format('d/m/Y h:i A');
        } catch (\Throwable) {
            return $this->fecha_registro->format('d/m/Y').' '.$this->hora_registro;
        }
    }

    public function vineta()
    {
        return $this->belongsTo(Vineta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por_user_id');
    }
}
