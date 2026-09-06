<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmpleadoSeguimientoRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_employee_ranking_by_production_group_and_cargo(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $anilladora = Empleado::create([
            'codigo' => 'EMP-ANILLO',
            'nombre' => 'Ana Anilladora',
            'cargo' => 'Anilladora y Celofanadora',
            'area' => 'Empaque',
            'activo' => true,
        ]);

        $rezagadora = Empleado::create([
            'codigo' => 'EMP-REZAGO',
            'nombre' => 'Rosa Rezagadora',
            'cargo' => 'Rezagadora de puros',
            'area' => 'Empaque',
            'activo' => true,
        ]);

        $excepcion8219 = Empleado::create([
            'codigo' => '8219',
            'nombre' => 'Empleado Especial 8219',
            'cargo' => 'Operario General',
            'area' => 'Empaque',
            'activo' => true,
        ]);

        $llenadora = Empleado::create([
            'codigo' => 'EMP-LLENADO',
            'nombre' => 'Laura Llenadora',
            'cargo' => 'Llenado de Cajas y Paquetes',
            'area' => 'Empaque',
            'activo' => true,
        ]);

        $limpiadora = Empleado::create([
            'codigo' => 'EMP-LIMPIEZA',
            'nombre' => 'Luisa Limpiadora',
            'cargo' => 'Limpia Puros',
            'area' => 'Empaque',
            'activo' => true,
        ]);

        $vineta = Vineta::create([
            'api_id' => 1100,
            'item' => 'ITEM-1100',
            'nombre' => 'Puro Clasico',
            'cantidad_puros' => 20,
        ]);

        $fecha = '2026-09-02';

        foreach ([$anilladora, $rezagadora, $excepcion8219, $llenadora, $limpiadora] as $emp) {
            VinetaRegistro::create([
                'vineta_id' => $vineta->id,
                'empleado_id' => $emp->id,
                'empleado_codigo' => $emp->codigo,
                'empleado_nombre' => $emp->nombre,
                'actividad_nombre' => 'Anillado y Celofan',
                'cantidad_puros' => 20,
                'cantidad_actividades' => 20,
                'fecha_registro' => $fecha,
                'hora_registro' => '09:00',
                'registrado_en' => "$fecha 09:00:00",
                'estado' => VinetaRegistro::ESTADO_ACTIVO,
            ]);
        }

        // 1. ANILLADO: solo Ana Anilladora
        $resAnillado = $this->getJson("/api/empleados/seguimiento?scope=anillado&period=month&date={$fecha}");
        $resAnillado->assertOk();
        $anilladoCodes = collect($resAnillado->json('employee_summaries'))->pluck('codigo')->all();
        $this->assertEquals(['EMP-ANILLO'], $anilladoCodes);
        $this->assertEquals('Anilladora y Celofanadora', $resAnillado->json('employee_summaries.0.cargo'));

        // 2. REZAGO: solo Rosa Rezagadora y 8219
        $resRezago = $this->getJson("/api/empleados/seguimiento?scope=rezago&period=month&date={$fecha}");
        $resRezago->assertOk();
        $rezagoCodes = collect($resRezago->json('employee_summaries'))->pluck('codigo')->all();
        $this->assertContains('EMP-REZAGO', $rezagoCodes);
        $this->assertContains('8219', $rezagoCodes);
        $this->assertNotContains('EMP-ANILLO', $rezagoCodes);
        $this->assertNotContains('EMP-LLENADO', $rezagoCodes);
        $this->assertNotContains('EMP-LIMPIEZA', $rezagoCodes);

        // 3. LLENADO: solo Laura Llenadora
        $resLlenado = $this->getJson("/api/empleados/seguimiento?scope=llenado&period=month&date={$fecha}");
        $resLlenado->assertOk();
        $llenadoCodes = collect($resLlenado->json('employee_summaries'))->pluck('codigo')->all();
        $this->assertEquals(['EMP-LLENADO'], $llenadoCodes);
    }
}
