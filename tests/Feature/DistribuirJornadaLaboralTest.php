<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DistribuirJornadaLaboralTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_distribuye_jornada_globalmente(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $emp1 = $this->createEmpleado('201', 'Carlos Sanchez');
        $emp2 = $this->createEmpleado('202', 'Lucia Flores');

        $reg1 = $this->createRegistro(3001, $emp1, 'Rezagado', 25, '2026-08-25');
        $reg2 = $this->createRegistro(3002, $emp2, 'Llenado de cajas', 35, '2026-08-25');

        $response = $this->postJson('/api/empleados/jornada-laboral/global', [
            'fecha' => '2026-08-25',
            'minutos' => 570,
        ]);

        $response->assertOk()
            ->assertJsonPath('empleados_actualizados', 2)
            ->assertJsonPath('registros_actualizados', 2)
            ->assertJsonPath('minutos_distribuidos', 570);

        $this->assertSame(570, $reg1->refresh()->minutos_trabajados);
        $this->assertSame(570, $reg2->refresh()->minutos_trabajados);

        // Eliminar global API
        $this->deleteJson('/api/empleados/jornada-laboral/global', [
            'fecha' => '2026-08-25',
        ])->assertOk()
            ->assertJsonPath('registros_actualizados', 2);

        $this->assertNull($reg1->refresh()->minutos_trabajados);
        $this->assertNull($reg2->refresh()->minutos_trabajados);
    }

    public function test_api_distribuye_jornada_individualmente(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $emp1 = $this->createEmpleado('201', 'Carlos Sanchez');
        $emp2 = $this->createEmpleado('202', 'Lucia Flores');

        $reg1 = $this->createRegistro(3001, $emp1, 'Rezagado', 25, '2026-08-25');
        $reg2 = $this->createRegistro(3002, $emp1, 'Rezagado', 35, '2026-08-25');
        $reg3 = $this->createRegistro(3003, $emp2, 'Llenado de cajas', 35, '2026-08-25');

        $response = $this->postJson("/api/empleados/{$emp1->id}/jornada-laboral", [
            'fecha' => '2026-08-25',
            'minutos' => 570,
        ]);

        $response->assertOk()
            ->assertJsonPath('registros_actualizados', 2)
            ->assertJsonPath('minutos_distribuidos', 570);

        $this->assertSame(285, $reg1->refresh()->minutos_trabajados);
        $this->assertSame(285, $reg2->refresh()->minutos_trabajados);
        $this->assertNull($reg3->refresh()->minutos_trabajados);

        // Eliminar individual API
        $this->deleteJson("/api/empleados/{$emp1->id}/jornada-laboral", [
            'fecha' => '2026-08-25',
        ])->assertOk()
            ->assertJsonPath('registros_actualizados', 2);

        $this->assertNull($reg1->refresh()->minutos_trabajados);
        $this->assertNull($reg2->refresh()->minutos_trabajados);
    }

    private function createEmpleado(string $codigo, string $nombre): Empleado
    {
        return Empleado::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cargo' => 'Operario',
            'area' => 'Empaque a Tarea Permanente',
            'activo' => true,
        ]);
    }

    private function createRegistro(
        int $apiId,
        Empleado $empleado,
        string $actividad,
        int $puros,
        string $fecha = '2026-08-25',
        ?int $minutos = null
    ): VinetaRegistro {
        $vineta = Vineta::create(['api_id' => $apiId, 'impreso' => true]);

        return VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.$apiId,
            'vineta_api_id' => $apiId,
            'actividad_nombre' => $actividad,
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'cantidad_puros' => $puros,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => $minutos,
            'fecha_registro' => $fecha,
            'hora_registro' => '08:00:00',
            'registrado_en' => $fecha.' 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);
    }
}
