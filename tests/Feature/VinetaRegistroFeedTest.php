<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VinetaRegistroFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_a_valid_start_date_and_returns_json_without_authentication(): void
    {
        $this->get('/api/vinetas-registradas')
            ->assertUnprocessable()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonValidationErrors('fecha');

        $this->get('/api/vinetas-registradas?fecha=10-08-2026')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fecha');

        $this->get('/api/vinetas-registradas?fecha=2026-08-10&todo=2')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('todo');

        $this->get('/api/vinetas-registradas?fecha=2026-08-10&grupo=otro')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grupo');
    }

    public function test_it_returns_active_registration_rows_from_the_start_date_in_order(): void
    {
        $vinetaUno = Vineta::create(['api_id' => 1001, 'impreso' => true]);
        $vinetaDos = Vineta::create(['api_id' => 1002, 'impreso' => true]);

        $this->createRegistro($vinetaUno, [
            'producto_codigo' => 'PROD-01',
            'fecha_registro' => '2026-08-09',
            'registrado_en' => '2026-08-09 08:00:00',
        ]);
        $this->createRegistro($vinetaUno, [
            'actividad_codigo' => 'ACT-REZ',
            'producto_item' => 'ITEM-01',
            'producto_codigo' => 'PROD-01',
            'orden_del_sistema' => 'OS-100',
            'orden' => 'OC-100',
            'cantidad_puros' => 100,
            'minutos_trabajados' => 11,
            'fecha_registro' => '2026-08-10',
            'hora_registro' => '09:00:00',
            'registrado_en' => '2026-08-10 09:00:00',
        ]);
        $this->createRegistro($vinetaUno, [
            'actividad_nombre' => 'Anillado',
            'producto_item' => 'ITEM-01',
            'producto_codigo' => 'PROD-01',
            'orden_del_sistema' => 'OS-100',
            'orden' => 'OC-100',
            'cantidad_puros' => 100,
            'minutos_trabajados' => null,
            'fecha_registro' => '2026-08-10',
            'hora_registro' => '10:00:00',
            'registrado_en' => '2026-08-10 10:00:00',
        ]);
        $this->createRegistro($vinetaDos, [
            'producto_item' => 'ITEM-02',
            'producto_codigo' => 'PROD-02',
            'orden_del_sistema' => 'OS-200',
            'orden' => 'OC-200',
            'cantidad_puros' => 40,
            'minutos_trabajados' => 60,
            'fecha_registro' => '2026-08-11',
            'registrado_en' => '2026-08-11 08:00:00',
        ]);
        $this->createRegistro($vinetaDos, [
            'producto_codigo' => 'PROD-02',
            'fecha_registro' => '2026-08-12',
            'registrado_en' => '2026-08-12 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ANULADO,
        ]);

        $response = $this->getJson('/api/vinetas-registradas?fecha=2026-08-10');

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
        $response->assertOk()
            ->assertJsonPath('fecha_desde', '2026-08-10')
            ->assertJsonPath('todo', 1)
            ->assertJsonPath('grupo', null)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(3, 'registros')
            ->assertJsonPath('registros.0.id_vineta', 1001)
            ->assertJsonPath('registros.0.item', 'ITEM-01')
            ->assertJsonPath('registros.0.codigo_producto', 'PROD-01')
            ->assertJsonPath('registros.0.orden_del_sistema', 'OS-100')
            ->assertJsonPath('registros.0.orden_del_cliente', 'OC-100')
            ->assertJsonPath('registros.0.codigo_actividad', 'ACT-REZ')
            ->assertJsonPath('registros.0.actividad', 'Rezagado')
            ->assertJsonPath('registros.0.grupo', 'rezago')
            ->assertJsonPath('registros.0.empleado_codigo', 'EMP-001')
            ->assertJsonPath('registros.0.empleado_nombre', 'Empleado Uno')
            ->assertJsonPath('registros.0.cantidad_puros', 100)
            ->assertJsonPath('registros.0.minutos_por_vineta', 0.18)
            ->assertJsonPath('registros.0.fecha_ingreso', '2026-08-10')
            ->assertJsonPath('registros.1.actividad', 'Anillado')
            ->assertJsonPath('registros.1.minutos_por_vineta', null)
            ->assertJsonPath('registros.2.id_vineta', 1002)
            ->assertJsonMissingPath('registros.0.id_registro')
            ->assertJsonMissingPath('registros.0.actividad_id')
            ->assertJsonMissingPath('registros.0.actividad_codigo');

        $this->getJson('/api/vinetas-registradas?fecha=2026-08-10&todo=0')
            ->assertOk()
            ->assertJsonPath('todo', 0)
            ->assertJsonPath('total', 2)
            ->assertJsonCount(2, 'registros')
            ->assertJsonPath('registros.0.fecha_ingreso', '2026-08-10')
            ->assertJsonPath('registros.1.fecha_ingreso', '2026-08-10');

        $this->getJson('/api/vinetas-registradas?fecha=2026-08-10&todo=1')
            ->assertOk()
            ->assertJsonPath('todo', 1)
            ->assertJsonPath('total', 3)
            ->assertJsonCount(3, 'registros');
    }

    public function test_it_filters_registration_rows_by_activity_group(): void
    {
        $vineta = Vineta::create(['api_id' => 2001, 'impreso' => true]);
        $this->createRegistro($vineta, ['actividad_nombre' => 'Rezagado Family']);
        $this->createRegistro($vineta, ['actividad_nombre' => 'Celofanado y sello']);
        $this->createRegistro($vineta, ['actividad_nombre' => 'Llenado de cajas']);
        $this->createRegistro($vineta, ['actividad_nombre' => 'Control de calidad']);

        foreach ([
            'rezago' => 'Rezagado Family',
            'anillado' => 'Celofanado y sello',
            'llenado' => 'Llenado de cajas',
        ] as $grupo => $actividad) {
            $this->getJson("/api/vinetas-registradas?fecha=2026-08-10&todo=0&grupo={$grupo}")
                ->assertOk()
                ->assertJsonPath('todo', 0)
                ->assertJsonPath('grupo', $grupo)
                ->assertJsonPath('total', 1)
                ->assertJsonCount(1, 'registros')
                ->assertJsonPath('registros.0.actividad', $actividad);
        }
    }

    public function test_it_classifies_special_activities_by_group(): void
    {
        $llenadoActivities = [
            'Hecha de Paquete TUBO/5',
            'Hecha de Paquete TUBO/25',
            'Petaca 4 Puros',
            'Sampler COTSCO 10 Puros',
            'Sampler de 5',
        ];
        $anilladoActivities = [
            'Esponja',
            'Lamina',
            'Pegado de sello en celofan',
        ];

        foreach ([...$llenadoActivities, ...$anilladoActivities] as $index => $actividad) {
            $vineta = Vineta::create(['api_id' => 2002 + $index, 'impreso' => true]);
            $this->createRegistro($vineta, ['actividad_nombre' => $actividad]);
        }

        $this->getJson('/api/vinetas-registradas?fecha=2026-08-10&todo=0&grupo=llenado')
            ->assertOk()
            ->assertJsonPath('grupo', 'llenado')
            ->assertJsonPath('total', count($llenadoActivities))
            ->assertJsonPath('registros.0.actividad', 'Hecha de Paquete TUBO/5')
            ->assertJsonPath('registros.4.actividad', 'Sampler de 5');

        $this->getJson('/api/vinetas-registradas?fecha=2026-08-10&todo=0&grupo=anillado')
            ->assertOk()
            ->assertJsonPath('grupo', 'anillado')
            ->assertJsonPath('total', count($anilladoActivities))
            ->assertJsonPath('registros.0.actividad', 'Esponja')
            ->assertJsonPath('registros.2.actividad', 'Pegado de sello en celofan');
    }

    public function test_daily_records_payload_includes_special_activity_groups_for_the_flutter_app(): void
    {
        Sanctum::actingAs(User::factory()->create());

        foreach ([
            15346 => 'Petaca 4 Puros',
            15347 => 'Sampler COTSCO 10 Puros',
            15348 => 'Hecha de Paquete TUBO/25',
            15349 => 'Esponja',
        ] as $apiId => $actividad) {
            $vineta = Vineta::create(['api_id' => $apiId, 'impreso' => true]);
            $this->createRegistro($vineta, ['actividad_nombre' => $actividad]);
        }

        $this->getJson('/api/vineta-registros?fecha=2026-08-10')
            ->assertOk()
            ->assertJsonPath('registros.0.vineta_api_id', 15346)
            ->assertJsonPath('registros.0.actividad.nombre', 'Petaca 4 Puros')
            ->assertJsonPath('registros.0.grupo_actividad', 'llenado')
            ->assertJsonPath('registros.1.grupo_actividad', 'llenado')
            ->assertJsonPath('registros.2.grupo_actividad', 'llenado')
            ->assertJsonPath('registros.3.grupo_actividad', 'anillado');
    }

    public function test_daily_records_separates_hourly_work_and_groups_tasks_by_employee_position(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = Empleado::create([
            'codigo' => 'EMP-100',
            'nombre' => 'Maria Rezago',
            'cargo' => 'Rezaga Puros',
            'activo' => true,
        ]);
        $taskVineta = Vineta::create(['api_id' => 16001, 'impreso' => true]);
        $hourlyVineta = Vineta::create(['api_id' => 16002, 'impreso' => true]);
        $this->createRegistro($taskVineta, [
            'actividad_nombre' => 'Anillado',
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'raw_payload' => ['modo_registro' => 'por_tarea'],
        ]);
        $this->createRegistro($hourlyVineta, [
            'actividad_nombre' => 'Llenado de cajas',
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'raw_payload' => ['modo_registro' => 'por_hora'],
        ]);

        $this->getJson('/api/vineta-registros?fecha=2026-08-10')
            ->assertOk()
            ->assertJsonPath('registros.0.grupo_actividad', 'anillado')
            ->assertJsonPath('registros.0.grupo_empleado', 'rezago')
            ->assertJsonPath('registros.0.empleado.cargo', 'Rezaga Puros')
            ->assertJsonPath('registros.1.grupo_empleado', 'por_hora')
            ->assertJsonPath('registros.1.por_hora', true);
    }

    private function createRegistro(Vineta $vineta, array $attributes = []): VinetaRegistro
    {
        return VinetaRegistro::create(array_merge([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.$vineta->api_id,
            'vineta_api_id' => $vineta->api_id,
            'producto_item' => 'ITEM',
            'producto_codigo' => 'PROD-01',
            'orden_del_sistema' => 'OS-001',
            'orden' => 'OC-001',
            'actividad_nombre' => 'Rezagado',
            'empleado_codigo' => 'EMP-001',
            'empleado_nombre' => 'Empleado Uno',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'fecha_registro' => '2026-08-10',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-08-10 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ], $attributes));
    }
}
