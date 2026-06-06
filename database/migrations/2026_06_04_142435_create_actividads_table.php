<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();

            // ID que viene desde la API
            $table->unsignedBigInteger('api_id_actividad')->unique();

            $table->string('codigo_actividad')->nullable()->index();
            $table->string('nombre')->index();

            $table->timestamps();

            $table->index('api_id_actividad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};