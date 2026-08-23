<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmpleadoHorasResumenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_employees_with_daily_vinetas_in_each_process_group(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $rezago = $this->createEmpleado('100', 'Maria', 'Rezaga Puros');
        $anillado = $this->createEmpleado('200', 'Ana', 'Anilladora');
        $llenado = $this->createEmpleado('300', 'Luis', 'Llenado de Cajas y Paquetes');
        $sinTrabajo = $this->createEmpleado('400', 'Sin trabajo', 'Rezaga Puros');

        $this->createRegistro(1001, $rezago, '2 Rezagados', 20, 2, 120);
        $this->createRegistro(1002, $anillado, 'Sellado y Anillado', 30, 1, 90);
        $this->createRegistro(1003, $llenado, 'Llenado de cajas', 40, 3, 60);
        $this->createRegistro(1004, $sinTrabajo, 'Rezagado', 50, 1, 30, '2026-08-12');
        $this->createRegistro(
            1005,
            $sinTrabajo,
            'Rezagado',
            50,
            1,
            30,
            '2026-08-13',
            VinetaRegistro::ESTADO_ANULADO
        );
        EmpleadoHoraOrdinaria::create([
            'empleado_id' => $rezago->id,
            'empleado_codigo' => $rezago->codigo,
            'empleado_nombre' => $rezago->nombre,
            'fecha' => '2026-08-13',
            'minutos' => 60,
            'observacion' => 'Apoyo de inventario',
        ]);

        $response = $this->getJson('/api/empleados/horas-ordinarias/resumen?fecha=2026-08-13');

        $response->assertOk()
            ->assertJsonPath('grupos.rezago', 1)
            ->assertJsonPath('grupos.anillado', 1)
            ->assertJsonPath('grupos.llenado', 1)
            ->assertJsonCount(3, 'empleados')
            ->assertJsonPath('empleados.0.grupo', 'rezago')
            ->assertJsonPath('empleados.0.empleado.id', $rezago->id)
            ->assertJsonPath('empleados.0.resumen.total_vinetas', 1)
            ->assertJsonPath('empleados.0.resumen.total_puros', 20)
            ->assertJsonPath('empleados.0.resumen.total_actividades', 40)
            ->assertJsonPath('empleados.0.resumen.minutos_vinetas', 120)
            ->assertJsonPath('empleados.0.resumen.minutos_ordinarios', 60)
            ->assertJsonPath('empleados.0.resumen.total_minutos', 180)
            ->assertJsonPath('empleados.1.grupo', 'anillado')
            ->assertJsonPath('empleados.1.empleado.id', $anillado->id)
            ->assertJsonPath('empleados.1.resumen.total_puros', 30)
            ->assertJsonPath('empleados.2.grupo', 'llenado')
            ->assertJsonPath('empleados.2.empleado.id', $llenado->id)
            ->assertJsonPath('empleados.2.resumen.total_actividades', 120)
            ->assertJsonMissing(['codigo' => $sinTrabajo->codigo]);
    }

    public function test_it_groups_all_task_records_by_the_employees_position(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = $this->createEmpleado('100', 'Maria', 'Rezaga Puros');
        $this->createRegistro(1001, $empleado, 'Rezagado', 20, 2, 120);
        $this->createRegistro(1002, $empleado, 'Anillado', 30, 1, 90);
        EmpleadoHoraOrdinaria::create([
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'fecha' => '2026-08-13',
            'minutos' => 30,
            'observacion' => 'Apoyo de inventario',
        ]);

        $response = $this->getJson(
            "/api/empleados/{$empleado->id}/horas-ordinarias?fecha=2026-08-13&grupo=rezago"
        );

        $response->assertOk()
            ->assertJsonPath('grupo', 'rezago')
            ->assertJsonPath('resumen.total_vinetas', 2)
            ->assertJsonPath('resumen.total_puros', 50)
            ->assertJsonPath('resumen.total_actividades', 70)
            ->assertJsonPath('resumen.minutos_vinetas', 210)
            ->assertJsonPath('resumen.minutos_ordinarios', 30)
            ->assertJsonPath('resumen.total_minutos', 240)
            ->assertJsonCount(2, 'cajones')
            ->assertJsonPath('cajones.0.vineta', 'ID 1001')
            ->assertJsonPath('cajones.0.grupo', 'rezago')
            ->assertJsonPath('cajones.0.cantidad_puros', 20)
            ->assertJsonPath('cajones.0.cantidad_actividades', 2)
            ->assertJsonPath('cajones.0.total_actividades', 40)
            ->assertJsonPath('cajones.0.minutos', 120);
    }

    public function test_it_distributes_edits_and_removes_the_employees_group_workday(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = $this->createEmpleado('100', 'Maria', 'Rezaga Puros');
        $rezago = $this->createRegistro(1001, $empleado, 'Rezagado', 20, 2, 120);
        $anillado = $this->createRegistro(1002, $empleado, 'Anillado', 30, 1, 90);

        $this->postJson("/api/empleados/{$empleado->id}/jornada-laboral", [
            'fecha' => '2026-08-13',
            'grupo' => 'rezago',
            'minutos' => 300,
        ])->assertOk()
            ->assertJsonPath('registros_actualizados', 2)
            ->assertJsonPath('minutos_distribuidos', 300);

        $this->assertSame(150, $rezago->refresh()->minutos_trabajados);
        $this->assertSame(150, $anillado->refresh()->minutos_trabajados);

        $this->getJson("/api/empleados/{$empleado->id}/horas-ordinarias?fecha=2026-08-13&grupo=rezago")
            ->assertOk()
            ->assertJsonPath('jornada_laboral.registros_actualizados', 2)
            ->assertJsonPath('jornada_laboral.minutos_distribuidos', 300)
            ->assertJsonPath('jornada_laboral.tiempo_distribuido_texto', '5 h');

        $this->deleteJson("/api/empleados/{$empleado->id}/jornada-laboral?fecha=2026-08-13&grupo=rezago")
            ->assertOk()
            ->assertJsonPath('registros_actualizados', 2);

        $this->assertNull($rezago->refresh()->minutos_trabajados);
        $this->assertNull($anillado->refresh()->minutos_trabajados);
    }

    public function test_it_excludes_hourly_records_from_the_ordinary_hours_groups(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = $this->createEmpleado('700', 'Empleado por hora', 'Rezaga Puros');
        $this->createRegistro(1010, $empleado, 'Anillado', 20, 1, 0, attributes: [
            'raw_payload' => ['modo_registro' => 'por_hora'],
        ]);

        $this->getJson('/api/empleados/horas-ordinarias/resumen?fecha=2026-08-13')
            ->assertOk()
            ->assertJsonPath('grupos.rezago', 0)
            ->assertJsonPath('grupos.anillado', 0)
            ->assertJsonPath('grupos.llenado', 0)
            ->assertJsonCount(0, 'empleados');
    }

    public function test_it_classifies_special_activity_names_in_group_scopes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $llenado = $this->createEmpleado('500', 'Llenado especial', 'Auxiliar');
        $anillado = $this->createEmpleado('600', 'Anillado especial', 'Auxiliar');

        $this->createRegistro(1006, $llenado, 'Hecha de Paquete TUBO/25', 20, 2, 60);
        $this->createRegistro(1007, $llenado, 'Sampler COTSCO 10 Puros', 10, 10, 60);
        $this->createRegistro(1008, $anillado, 'Esponja', 20, 1, 60);
        $this->createRegistro(1009, $anillado, 'Lamina', 20, 1, 60);

        $this->getJson('/api/empleados/horas-ordinarias/resumen?fecha=2026-08-13')
            ->assertOk()
            ->assertJsonPath('grupos.llenado', 1)
            ->assertJsonPath('grupos.anillado', 1);

        $this->getJson("/api/empleados/{$llenado->id}/horas-ordinarias?fecha=2026-08-13&grupo=llenado")
            ->assertOk()
            ->assertJsonPath('grupo', 'llenado')
            ->assertJsonPath('resumen.total_vinetas', 2)
            ->assertJsonPath('resumen.total_actividades', 140);

        $this->getJson("/api/empleados/{$anillado->id}/horas-ordinarias?fecha=2026-08-13&grupo=anillado")
            ->assertOk()
            ->assertJsonPath('grupo', 'anillado')
            ->assertJsonPath('resumen.total_vinetas', 2)
            ->assertJsonPath('resumen.total_actividades', 40);
    }

    private function createEmpleado(string $codigo, string $nombre, string $cargo, bool $activo = true): Empleado
    {
        return Empleado::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cargo' => $cargo,
            'area' => 'Empaque a Tarea Permanente',
            'activo' => $activo,
        ]);
    }

    private function createRegistro(
        int $apiId,
        Empleado $empleado,
        string $actividad,
        int $puros,
        int $cantidadActividades,
        int $minutos,
        string $fecha = '2026-08-13',
        string $estado = VinetaRegistro::ESTADO_ACTIVO,
        array $attributes = []
    ): VinetaRegistro {
        $vineta = Vineta::create(['api_id' => $apiId, 'impreso' => true]);

        return VinetaRegistro::create(array_merge([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.$apiId,
            'vineta_api_id' => $apiId,
            'actividad_nombre' => $actividad,
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'cantidad_puros' => $puros,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => $cantidadActividades,
            'minutos_trabajados' => $minutos,
            'fecha_registro' => $fecha,
            'hora_registro' => '08:00:00',
            'registrado_en' => $fecha.' 08:00:00',
            'estado' => $estado,
        ], $attributes));
    }
}
