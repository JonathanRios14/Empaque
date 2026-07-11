<?php

namespace App\Services;

use App\Models\Vineta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class VinetaApiService
{
    private string $url = 'http://192.168.2.7:8080/api/empaque/vinetas';

    public function sincronizar(): array
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        $response = Http::timeout(120)
            ->connectTimeout(15)
            ->get($this->url);

        if ($response->failed()) {
            return [
                'ok' => false,
                'mensaje' => 'No se pudo conectar con la API de viñetas. Estado: ' . $response->status(),
                'total' => 0,
                'omitidos' => 0,
            ];
        }

        $json = $response->json();
        $data = $json['data'] ?? $json;

        if (! is_array($data)) {
            return [
                'ok' => false,
                'mensaje' => 'La respuesta de la API de viñetas no tiene un formato válido.',
                'total' => 0,
                'omitidos' => 0,
            ];
        }

        $procesados = 0;
        $omitidos = 0;
        $vinetas=[];

        foreach ($data as $item) {
            if (! is_array($item) || empty($item['id'])) {
                $omitidos++;
                continue;
            }

           $vinetas[]= [ 'api_id' => (int) $item['id'],
                'id_pendiente_empaque' => $this->nullableString($item['id_pendiente_empaque'] ?? null),
                'id_detalle_programacion' => $this->nullableString($item['id_detalle_programacion'] ?? $item['id_detalle_programaciom'] ?? null),
                'fecha' => $this->date($item['fecha'] ?? null),
                'cantidad_puros' => $this->integer($item['cantidad_puros'] ?? null),
                'estado' => $this->nullableString($item['estado'] ?? null),
                'impreso' => $this->boolean($item['impreso'] ?? null),
                'api_created_at' => $this->dateTime($item['created_at'] ?? null),
                'api_updated_at' => $this->dateTime($item['updated_at'] ?? null),
                'item' => $this->nullableString($item['item'] ?? null),
                'orden_del_sistema' => $this->nullableString($item['orden_del_sistema'] ?? $item['orden_del_sitema'] ?? null),
                'mes' => $this->nullableString($item['mes'] ?? null),
                'orden' => $this->nullableString($item['orden'] ?? null),
                'marca' => $this->nullableString($item['marca'] ?? null),
                'nombre' => $this->nullableString($item['nombre'] ?? null),
                'capa' => $this->nullableString($item['capa'] ?? null),
                'vitola' => $this->nullableString($item['vitola'] ?? null),
                'tipo_empaque' => $this->nullableString($item['tipo_empaque'] ?? null),
                'codigo_producto' => $this->nullableString($item['codigo_producto'] ?? null),
                'raw_payload' => json_encode($item)
            ];
          
            $procesados++;
           

        }

    

        $lotes = array_chunk($vinetas, 1000);
            foreach ($lotes as $lote) {
               Vineta::insertOrIgnore($lote);
            }
            

        return [
            'ok' => true,
            'mensaje' =>  'Viñetas guardadas correctamente',
            'total' => $procesados,
            'omitidos' => $omitidos,
        ];
    }


    

    private function nullableString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function integer($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function boolean($value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'y', 'yes', 's', 'si'], true);
    }

    private function date($value): ?string
    {
        return $this->parseDate($value, 'Y-m-d');
    }

    private function dateTime($value): ?string
    {
        return $this->parseDate($value, 'Y-m-d H:i:s');
    }

    private function parseDate($value, string $format): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return null;
        }
    }
}
