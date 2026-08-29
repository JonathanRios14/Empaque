<?php

namespace App\Console\Commands;

use App\Services\ActividadApiService;
use Illuminate\Console\Command;

class SyncActividadesFichasCommand extends Command
{
    protected $signature = 'catalogos:sync-actividades';

    protected $description = 'Sincroniza las actividades de empaque desde la API de fichas y vincula los registros de viñetas';

    public function handle(ActividadApiService $actividadApiService): int
    {
        $this->info('Iniciando sincronización de actividades...');

        $resultado = $actividadApiService->sincronizar();

        if (! $resultado['ok']) {
            $this->error($resultado['mensaje']);
            return Command::FAILURE;
        }

        $this->info($resultado['mensaje']);
        $this->table(
            ['Total', 'Nuevos', 'Actualizados', 'Sin cambios', 'Vinculados desde escaneos'],
            [[
                $resultado['total'],
                $resultado['nuevos'],
                $resultado['actualizados'],
                $resultado['sin_cambios'],
                $resultado['vinculados_escaneos'] ?? 0,
            ]]
        );

        return Command::SUCCESS;
    }
}
