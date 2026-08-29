<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vinetas_por_orden', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_qr')->nullable()->unique()->index();
            $table->unsignedBigInteger('api_id')->nullable()->index();
            $table->string('id_pendiente_empaque')->nullable()->index();
            $table->string('id_detalle_programacion')->nullable()->index();
            $table->date('fecha')->nullable()->index();
            $table->unsignedInteger('cantidad_puros')->nullable();
            $table->string('estado', 50)->nullable()->index();
            $table->timestamp('api_created_at')->nullable();
            $table->timestamp('api_updated_at')->nullable();
            $table->string('item')->nullable()->index();
            $table->string('orden_del_sistema')->nullable()->index();
            $table->string('mes')->nullable();
            $table->string('orden')->nullable()->index();
            $table->string('marca')->nullable()->index();
            $table->string('nombre')->nullable()->index();
            $table->string('capa')->nullable();
            $table->string('vitola')->nullable();
            $table->string('tipo_empaque')->nullable();
            $table->string('codigo_producto')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vinetas_por_orden');
    }
};
