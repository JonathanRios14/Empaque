<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_producto', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();

            $table->foreignId('actividad_id')
                ->constrained('actividades')
                ->cascadeOnDelete();

            $table->foreignId('tipo_empaque_id')
                ->nullable()
                ->constrained('tipo_empaques')
                ->nullOnDelete();

            $table->decimal('precio_mo', 18, 7)->default(0);

            $table->timestamps();

            $table->unique([
                'producto_id',
                'actividad_id',
                'tipo_empaque_id',
                'precio_mo'
            ], 'actividad_producto_unique');

            $table->index('producto_id');
            $table->index('actividad_id');
            $table->index('tipo_empaque_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_producto');
    }
};