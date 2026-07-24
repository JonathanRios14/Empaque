<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'imagen_caja')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->text('imagen_caja')->nullable()->after('descripcion');
            });
        }

        if (! Schema::hasColumn('productos', 'imagen_anillado')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->text('imagen_anillado')->nullable()->after('imagen_caja');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'imagen_anillado')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('imagen_anillado');
            });
        }

        if (Schema::hasColumn('productos', 'imagen_caja')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('imagen_caja');
            });
        }
    }
};
