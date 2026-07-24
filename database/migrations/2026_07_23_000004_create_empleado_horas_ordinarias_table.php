<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empleado_horas_ordinarias')) {
            return;
        }

        Schema::create('empleado_horas_ordinarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('registrado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('empleado_codigo')->index();
            $table->string('empleado_nombre')->index();
            $table->date('fecha')->index();
            $table->unsignedSmallInteger('minutos');
            $table->text('observacion');
            $table->string('registrado_por_nombre')->nullable();
            $table->timestamps();

            $table->index(['empleado_codigo', 'fecha'], 'empleado_horas_ord_empleado_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_horas_ordinarias');
    }
};
