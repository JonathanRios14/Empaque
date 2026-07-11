<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vineta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class VinetaController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr' => ['required', 'string', 'max:1000'],
        ]);

        $candidates = $this->extractCandidates($data['qr']);

        foreach ($candidates as $candidate) {
            $vineta = $this->findVineta($candidate);

            if ($vineta) {
                return response()->json([
                    'message' => 'Viñeta encontrada.',
                    'matched_by' => $candidate,
                    'vineta' => $this->vinetaPayload($vineta),
                ]);
            }
        }

        return response()->json([
            'message' => 'No se encontró una viñeta con el QR escaneado.',
            'qr' => $data['qr'],
        ], 404);
    }

    private function extractCandidates(string $qr): array
    {
        $qr = trim($qr);
        $candidates = [$qr];

        $decodedJson = json_decode($qr, true);

        if (is_array($decodedJson)) {
            foreach (['api_id', 'id', 'vineta_id', 'codigo_producto', 'item', 'orden', 'orden_del_sistema', 'id_pendiente_empaque'] as $key) {
                $value = Arr::get($decodedJson, $key);

                if (is_scalar($value)) {
                    $candidates[] = (string) $value;
                }
            }
        }

        $urlParts = parse_url($qr);

        if (is_array($urlParts)) {
            if (! empty($urlParts['query'])) {
                parse_str($urlParts['query'], $query);

                foreach (['api_id', 'id', 'vineta_id', 'codigo_producto', 'item', 'orden', 'orden_del_sistema', 'id_pendiente_empaque'] as $key) {
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

    private function findVineta(string $candidate): ?Vineta
    {
        return Vineta::query()
            ->where(function ($query) use ($candidate) {
                if (ctype_digit($candidate)) {
                    $query->where('api_id', (int) $candidate);
                }

                $query->orWhere('id_pendiente_empaque', $candidate)
                    ->orWhere('id_detalle_programacion', $candidate)
                    ->orWhere('codigo_producto', $candidate)
                    ->orWhere('item', $candidate)
                    ->orWhere('orden', $candidate)
                    ->orWhere('orden_del_sistema', $candidate);
            })
            ->first();
    }

    private function vinetaPayload(Vineta $vineta): array
    {
        return [
            'id' => $vineta->id,
            'api_id' => $vineta->api_id,
            'id_pendiente_empaque' => $vineta->id_pendiente_empaque,
            'id_detalle_programacion' => $vineta->id_detalle_programacion,
            'fecha' => $vineta->fecha?->format('Y-m-d'),
            'marca' => $vineta->marca,
            'nombre' => $vineta->nombre,
            'capa' => $vineta->capa,
            'vitola' => $vineta->vitola,
            'tipo_empaque' => $vineta->tipo_empaque,
            'codigo_producto' => $vineta->codigo_producto,
            'item' => $vineta->item,
            'orden_del_sistema' => $vineta->orden_del_sistema,
            'mes' => $vineta->mes,
            'orden' => $vineta->orden,
            'cantidad_puros' => $vineta->cantidad_puros,
            'estado' => $vineta->estado,
            'impreso' => (bool) $vineta->impreso,
            'api_created_at' => $vineta->api_created_at?->toISOString(),
            'api_updated_at' => $vineta->api_updated_at?->toISOString(),
            'updated_at' => $vineta->updated_at?->toISOString(),
        ];
    }
}
