<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            if (! Schema::hasColumn('actividades', 'precio_mo')) {
                $table->decimal('precio_mo', 18, 7)->default(0)->after('nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            if (Schema::hasColumn('actividades', 'precio_mo')) {
                $table->dropColumn('precio_mo');
            }
        });
    }
};
