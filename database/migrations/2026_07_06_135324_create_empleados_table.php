<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
    $table->id();

    $table->string('codigo')->unique();
    $table->string('nombre');
    $table->dateTime('fecha_ingreso')->nullable();
    $table->string('cargo')->nullable();
    $table->dateTime('fecha_baja')->nullable();
    $table->string('area')->nullable();

    $table->boolean('activo')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
