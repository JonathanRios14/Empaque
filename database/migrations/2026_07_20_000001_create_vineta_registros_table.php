<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vineta_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vineta_id')->constrained('vinetas')->restrictOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->foreignId('actividad_id')->nullable()->constrained('actividades')->nullOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('registrado_por_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('codigo_vineta')->nullable()->index();
            $table->unsignedBigInteger('vineta_api_id')->nullable()->index();
            $table->string('id_pendiente_empaque')->nullable()->index();
            $table->string('id_detalle_programacion')->nullable()->index();
            $table->date('vineta_fecha')->nullable()->index();
            $table->string('producto_codigo')->nullable()->index();
            $table->string('producto_item')->nullable()->index();
            $table->string('producto_nombre')->nullable()->index();
            $table->string('marca')->nullable()->index();
            $table->string('capa')->nullable();
            $table->string('vitola')->nullable();
            $table->string('tipo_empaque')->nullable()->index();
            $table->string('orden')->nullable()->index();
            $table->string('orden_del_sistema')->nullable()->index();

            $table->unsignedBigInteger('actividad_api_id')->nullable()->index();
            $table->string('actividad_codigo')->nullable()->index();
            $table->string('actividad_nombre')->index();
            $table->string('actividad_tipo_empaque')->nullable()->index();
            $table->decimal('precio_mo', 12, 4)->nullable();

            $table->string('empleado_codigo')->index();
            $table->string('empleado_nombre')->index();
            $table->unsignedInteger('cantidad_puros');
            $table->unsignedSmallInteger('cantidad_cajones')->default(1);
            $table->date('fecha_registro')->index();
            $table->time('hora_registro');
            $table->timestamp('registrado_en')->index();
            $table->string('registrado_por_nombre')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->text('observacion')->nullable();

            $table->foreignId('anulado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulado_en')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['vineta_id', 'fecha_registro']);
            $table->index(['vineta_id', 'actividad_id', 'fecha_registro', 'estado'], 'vineta_registros_actividad_dia_idx');
            $table->index(['empleado_codigo', 'fecha_registro', 'estado'], 'vineta_registros_empleado_dia_idx');
            $table->index(['actividad_nombre', 'fecha_registro', 'estado'], 'vineta_registros_actividad_nombre_dia_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vineta_registros');
    }
};
