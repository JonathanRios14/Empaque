<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalEmpaqueImageService
{
    private const CONNECTION = 'external_empaque';

    private ?array $imagenesPorCodigoTipo = null;

    public function imagenesParaProducto(array $item): array
    {
        $imagenes = [
            'imagen_caja' => [],
            'imagen_anillado' => [],
        ];

        $tipoEmpaque = $this->normalizeType($item['tipo_empaque'] ?? null);

        foreach ($this->codigosProducto($item) as $codigo) {
            $externas = $this->imagenesPorCodigoTipo()[$this->indexKey($codigo, $tipoEmpaque)] ?? null;

            if ($externas === null) {
                continue;
            }

            $imagenes['imagen_caja'] = array_merge($imagenes['imagen_caja'], $externas['imagen_caja']);
            $imagenes['imagen_anillado'] = array_merge($imagenes['imagen_anillado'], $externas['imagen_anillado']);
        }

        return [
            'imagen_caja' => $this->unique($imagenes['imagen_caja']),
            'imagen_anillado' => $this->unique($imagenes['imagen_anillado']),
        ];
    }

    private function imagenesPorCodigoTipo(): array
    {
        if ($this->imagenesPorCodigoTipo !== null) {
            return $this->imagenesPorCodigoTipo;
        }

        if (! $this->configured()) {
            return $this->imagenesPorCodigoTipo = [];
        }

        try {
            $rows = DB::connection(self::CONNECTION)->select(
                'CALL GetDetallePedido(?, ?, ?, ?, ?, ?, ?, ?)',
                [null, null, null, null, null, null, null, null]
            );
        } catch (Throwable $exception) {
            Log::warning('No se pudieron cargar imagenes desde la BD externa de empaque.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->imagenesPorCodigoTipo = [];
        }

        $index = [];

        foreach ($rows as $row) {
            $imagenes = [
                'imagen_caja' => $this->imagenesArray($row->imagen_caja ?? null),
                'imagen_anillado' => $this->imagenesArray($row->imagen_anillado ?? null),
            ];

            if ($imagenes['imagen_caja'] === [] && $imagenes['imagen_anillado'] === []) {
                continue;
            }

            $tipoEmpaque = $this->normalizeType($row->tipo_empaque ?? null);

            foreach ($this->codigosRow($row) as $codigo) {
                $key = $this->indexKey($codigo, $tipoEmpaque);

                $index[$key] ??= [
                    'imagen_caja' => [],
                    'imagen_anillado' => [],
                ];

                $index[$key]['imagen_caja'] = array_merge($index[$key]['imagen_caja'], $imagenes['imagen_caja']);
                $index[$key]['imagen_anillado'] = array_merge($index[$key]['imagen_anillado'], $imagenes['imagen_anillado']);
            }
        }

        foreach ($index as $codigo => $imagenes) {
            $index[$codigo] = [
                'imagen_caja' => $this->unique($imagenes['imagen_caja']),
                'imagen_anillado' => $this->unique($imagenes['imagen_anillado']),
            ];
        }

        return $this->imagenesPorCodigoTipo = $index;
    }

    private function configured(): bool
    {
        $config = config('database.connections.' . self::CONNECTION, []);

        return filled($config['host'] ?? null)
            && filled($config['database'] ?? null)
            && filled($config['username'] ?? null);
    }

    private function codigosProducto(array $item): array
    {
        return $this->normalizeCodes([
            $item['codigo_producto'] ?? null,
        ]);
    }

    private function codigosRow(object $row): array
    {
        return $this->normalizeCodes([
            $row->codigo_puro ?? null,
        ]);
    }

    private function normalizeCodes(array $codes): array
    {
        return collect($codes)
            ->filter(fn ($code) => is_scalar($code))
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeType($type): ?string
    {
        if (! is_scalar($type)) {
            return null;
        }

        $type = preg_replace('/\s+/', ' ', strtoupper(trim((string) $type)));

        return $type !== '' ? $type : null;
    }

    private function indexKey(string $codigo, ?string $tipoEmpaque): string
    {
        return $tipoEmpaque === null ? $codigo : $codigo . '|' . $tipoEmpaque;
    }

    private function imagenesArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return collect($value)
                ->flatten()
                ->filter(fn ($imagen) => is_scalar($imagen))
                ->map(fn ($imagen) => trim((string) $imagen))
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
            return $this->imagenesArray($decoded);
        }

        return [$value];
    }

    private function unique(array $values): array
    {
        return collect($values)
            ->filter(fn ($value) => is_scalar($value))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
