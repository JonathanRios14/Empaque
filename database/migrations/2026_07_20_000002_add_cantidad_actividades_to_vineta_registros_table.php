<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vineta_registros') || Schema::hasColumn('vineta_registros', 'cantidad_actividades')) {
            return;
        }

        Schema::table('vineta_registros', function (Blueprint $table) {
            $table->unsignedSmallInteger('cantidad_actividades')->default(1)->after('cantidad_cajones');
        });

        DB::table('vineta_registros')
            ->select('id', 'actividad_nombre')
            ->orderBy('id')
            ->chunkById(200, function ($registros) {
                foreach ($registros as $registro) {
                    DB::table('vineta_registros')
                        ->where('id', $registro->id)
                        ->update([
                            'cantidad_actividades' => $this->cantidadActividadesDesdeNombre($registro->actividad_nombre),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vineta_registros') || ! Schema::hasColumn('vineta_registros', 'cantidad_actividades')) {
            return;
        }

        Schema::table('vineta_registros', function (Blueprint $table) {
            $table->dropColumn('cantidad_actividades');
        });
    }

    private function cantidadActividadesDesdeNombre(?string $nombre): int
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
};
