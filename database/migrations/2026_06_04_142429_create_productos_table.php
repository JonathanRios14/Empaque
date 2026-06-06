<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            // ID que viene desde la API
            $table->unsignedBigInteger('api_id_producto')->unique();

            // Códigos del producto
            $table->string('item')->nullable()->index();
            $table->string('codigo_producto')->nullable()->index();
            $table->string('codigo_caja')->nullable()->index();
            $table->string('codigo_precio')->nullable()->index();

            // Información principal
            $table->string('nombre')->nullable()->index();
            $table->text('descripcion')->nullable();

            // Valores
            $table->decimal('precio', 18, 10)->default(0);
            $table->integer('cantidad_bulto')->default(0);

            // Indicadores
            $table->boolean('anillo')->default(false);
            $table->boolean('cello')->default(false);
            $table->boolean('upc')->default(false);
            $table->boolean('sampler')->default(false);
            $table->boolean('caja_local')->default(false);

            // Relaciones normalizadas
            $table->foreignId('empresa_id')
                ->nullable()
                ->constrained('empresas')
                ->nullOnDelete();

            $table->foreignId('marca_id')
                ->nullable()
                ->constrained('marcas')
                ->nullOnDelete();

            $table->foreignId('vitola_id')
                ->nullable()
                ->constrained('vitolas')
                ->nullOnDelete();

            $table->foreignId('capa_id')
                ->nullable()
                ->constrained('capas')
                ->nullOnDelete();

            $table->foreignId('presentacion_id')
                ->nullable()
                ->constrained('presentaciones')
                ->nullOnDelete();

            $table->foreignId('tipo_empaque_id')
                ->nullable()
                ->constrained('tipo_empaques')
                ->nullOnDelete();

            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('empresa_id');
            $table->index('marca_id');
            $table->index('vitola_id');
            $table->index('capa_id');
            $table->index('presentacion_id');
            $table->index('tipo_empaque_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};