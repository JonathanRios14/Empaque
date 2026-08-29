<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividad_producto', function (Blueprint $table) {
            if (! Schema::hasColumn('actividad_producto', 'activo')) {
                $table->boolean('activo')->default(true)->after('precio_mo')->index();
            }

            if (! Schema::hasColumn('actividad_producto', 'origen')) {
                $table->string('origen', 30)->default('api')->after('activo');
            }

            if (! Schema::hasColumn('actividad_producto', 'ultimo_escaneo_en')) {
                $table->timestamp('ultimo_escaneo_en')->nullable()->after('origen')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('actividad_producto', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('actividad_producto', 'ultimo_escaneo_en')) {
                $columnsToDrop[] = 'ultimo_escaneo_en';
            }

            if (Schema::hasColumn('actividad_producto', 'origen')) {
                $columnsToDrop[] = 'origen';
            }

            if (Schema::hasColumn('actividad_producto', 'activo')) {
                $columnsToDrop[] = 'activo';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
