<?php

use Database\Seeders\VinetasPorOrdenSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningUnitTests() && Schema::hasTable('vinetas_por_orden')) {
            $seeder = new VinetasPorOrdenSeeder();
            $seeder->run();
        }
    }

    public function down(): void
    {
        // Safe: Do not delete user data on rollback
    }
};
