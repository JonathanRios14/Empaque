<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vineta_registros') || Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            return;
        }

        Schema::table('vineta_registros', function (Blueprint $table) {
            $table->unsignedSmallInteger('minutos_trabajados')->nullable()->after('cantidad_actividades');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vineta_registros') || ! Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            return;
        }

        Schema::table('vineta_registros', function (Blueprint $table) {
            $table->dropColumn('minutos_trabajados');
        });
    }
};
