<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class EmpleadoController extends Controller
{
    private string $empleadosUrl = 'http://192.168.2.7:8080/api/nomina/empaque';

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:1000', 'required_without:qr'],
            'qr' => ['nullable', 'string', 'max:1000', 'required_without:code'],
        ]);

        $input = $data['code'] ?? $data['qr'] ?? '';
        $candidates = $this->extractCandidates($input);

        foreach ($candidates as $candidate) {
            $empleado = Empleado::query()
                ->where('codigo', $candidate)
                ->first();

            if ($empleado) {
                return response()->json([
                    'message' => 'Empleado encontrado.',
                    'source' => 'local',
                    'matched_by' => $candidate,
                    'employee' => $this->empleadoPayload($empleado),
                ]);
            }
        }

        $external = $this->findExternalEmpleado($candidates);

        if ($external) {
            $empleado = $this->storeExternalEmpleado($external);

            return response()->json([
                'message' => 'Empleado encontrado desde la API externa.',
                'source' => 'external',
                'matched_by' => $empleado->codigo,
                'employee' => $this->empleadoPayload($empleado),
            ]);
        }

        return response()->json([
            'message' => 'No se encontró un empleado con ese código o QR.',
            'candidates' => $candidates,
        ], 404);
    }

    private function extractCandidates(string $input): array
    {
        $input = trim($input);
        $candidates = [$input];

        $decodedJson = json_decode($input, true);

        if (is_array($decodedJson)) {
            foreach (['codigo', 'code', 'empleado', 'codigo_empleado', 'employee_code', 'id'] as $key) {
                $value = Arr::get($decodedJson, $key);

                if (is_scalar($value)) {
                    $candidates[] = (string) $value;
                }
            }
        }

        $urlParts = parse_url($input);

        if (is_array($urlParts)) {
            if (! empty($urlParts['query'])) {
                parse_str($urlParts['query'], $query);

                foreach (['codigo', 'code', 'empleado', 'codigo_empleado', 'employee_code', 'id'] as $key) {
                    if (isset($query[$key]) && is_scalar($query[$key])) {
                        $candidates[] = (string) $query[$key];
                    }
                }
            }

            if (! empty($urlParts['path'])) {
                $candidates[] = basename($urlParts['path']);
            }
        }

        return collect($candidates)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function findExternalEmpleado(array $candidates): ?array
    {
        try {
            $response = Http::timeout(12)
                ->connectTimeout(5)
                ->get(env('API_EMPLEADOS_URL', $this->empleadosUrl));

            if (! $response->successful()) {
                return null;
            }

            $items = $response->json('data') ?? $response->json();

            if (! is_array($items)) {
                return null;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $codigo = trim((string) ($item['codigo'] ?? ''));

                if ($codigo !== '' && in_array($codigo, $candidates, true)) {
                    return $item;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function storeExternalEmpleado(array $item): Empleado
    {
        $fechaBaja = $this->dateTime($item['fecha_baja'] ?? null);

        return Empleado::updateOrCreate(
            ['codigo' => trim((string) $item['codigo'])],
            [
                'nombre' => trim((string) ($item['nombre'] ?? 'Sin nombre')),
                'fecha_ingreso' => $this->dateTime($item['fecha_ingreso'] ?? null),
                'cargo' => $item['cargo'] ?? null,
                'fecha_baja' => $fechaBaja,
                'area' => $item['area'] ?? null,
                'activo' => is_null($fechaBaja),
            ]
        );
    }

    private function empleadoPayload(Empleado $empleado): array
    {
        return [
            'id' => $empleado->id,
            'codigo' => $empleado->codigo,
            'nombre' => $empleado->nombre,
            'cargo' => $empleado->cargo,
            'area' => $empleado->area,
            'activo' => (bool) $empleado->activo,
        ];
    }

    private function dateTime($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
