<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Empleado;
use App\Models\Producto;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VinetaRegistroUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_the_activity_and_recalculates_its_catalog_values(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $actividadAnterior = Actividad::create([
            'api_id_actividad' => 101,
            'codigo_actividad' => 'ACT-101',
            'nombre' => 'Anillado sencillo',
        ]);
        $actividadNueva = Actividad::create([
            'api_id_actividad' => 202,
            'codigo_actividad' => 'ACT-202',
            'nombre' => '2 Anillos, Celofan',
        ]);
        $producto = Producto::create([
            'api_id_producto' => 3001,
            'codigo_producto' => 'PROD-3001',
            'item' => 'ITEM-3001',
            'nombre' => 'Producto prueba',
        ]);
        DB::table('actividad_producto')->insert([
            'producto_id' => $producto->id,
            'actividad_id' => $actividadNueva->id,
            'tipo_empaque_id' => null,
            'precio_mo' => 0.125,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $empleado = Empleado::create([
            'codigo' => 'EMP-001',
            'nombre' => 'Empleado prueba',
            'cargo' => 'Anillador',
            'area' => 'Empaque',
            'activo' => true,
        ]);
        $vineta = Vineta::create([
            'api_id' => 4001,
            'codigo_producto' => $producto->codigo_producto,
            'item' => $producto->item,
            'nombre' => $producto->nombre,
            'cantidad_puros' => 20,
            'impreso' => true,
        ]);
        $registro = VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'producto_id' => $producto->id,
            'actividad_id' => $actividadAnterior->id,
            'empleado_id' => $empleado->id,
            'vineta_api_id' => $vineta->api_id,
            'producto_codigo' => $producto->codigo_producto,
            'producto_item' => $producto->item,
            'producto_nombre' => $producto->nombre,
            'actividad_api_id' => $actividadAnterior->api_id_actividad,
            'actividad_codigo' => $actividadAnterior->codigo_actividad,
            'actividad_nombre' => $actividadAnterior->nombre,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
            'precio_mo' => 0.05,
            'fecha_registro' => '2026-08-19',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-08-19 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
            'raw_payload' => ['modo_registro' => 'por_tarea'],
        ]);

        $response = $this->patchJson("/api/vineta-registros/{$registro->id}", [
            'fecha_registro' => '2026-08-19',
            'hora_registro' => '09:15',
            'cantidad_puros' => 20,
            'empleado_codigo' => $empleado->codigo,
            'modo_registro' => 'por_tarea',
            'minutos_trabajados' => 45,
            'actividad_id' => $actividadNueva->id,
            'api_id_actividad' => 999,
            'codigo_actividad' => $actividadNueva->codigo_actividad,
            'actividad_nombre' => $actividadNueva->nombre,
            'actividad_tipo_empaque' => 'Caja',
            'cantidad_actividades' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('registro.actividad.id', $actividadNueva->id)
            ->assertJsonPath('registro.actividad.codigo_actividad', 'ACT-202')
            ->assertJsonPath('registro.actividad.nombre', '2 Anillos, Celofan')
            ->assertJsonPath('registro.cantidad_actividades', 3)
            ->assertJsonPath('registro.total_actividades', 60)
            ->assertJsonPath('registro.actividad.precio_mo', 0.125);

        $this->assertDatabaseHas('vineta_registros', [
            'id' => $registro->id,
            'actividad_id' => $actividadNueva->id,
            'actividad_api_id' => 202,
            'actividad_codigo' => 'ACT-202',
            'actividad_nombre' => '2 Anillos, Celofan',
            'actividad_tipo_empaque' => 'Caja',
            'cantidad_actividades' => 3,
        ]);

        $this->assertSame('0.1250', $registro->refresh()->precio_mo);

        $this->patchJson("/api/vineta-registros/{$registro->id}", [
            'fecha_registro' => '2026-08-19',
            'hora_registro' => '09:30',
            'cantidad_puros' => 20,
            'empleado_codigo' => $empleado->codigo,
            'modo_registro' => 'por_tarea',
            'minutos_trabajados' => 45,
            'actividad_id' => null,
            'actividad_nombre' => null,
        ])->assertOk();

        $this->assertSame($actividadNueva->id, $registro->refresh()->actividad_id);
    }

    public function test_store_allows_llenado_and_anillado_in_either_order_and_returns_unblocked_process_payloads(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = $this->createProcessEmpleado();
        $llenadoPrimero = $this->createProcessVineta(5001);
        $anilladoPrimero = $this->createProcessVineta(5002);

        $this->postJson("/api/vinetas/{$llenadoPrimero->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Llenado de cajas',
            '2026-08-20',
            '08:00'
        ))->assertCreated()
            ->assertJsonPath('proceso.puede_llenar', true)
            ->assertJsonPath('proceso.mensaje_bloqueo_llenado', null)
            ->assertJsonPath('proceso.pasos.1.completado', false)
            ->assertJsonPath('proceso.pasos.2.completado', true);

        $this->postJson('/api/vinetas/scan', ['qr' => (string) $llenadoPrimero->api_id])
            ->assertOk()
            ->assertJsonPath('vineta.proceso.puede_llenar', true)
            ->assertJsonPath('vineta.proceso.mensaje_bloqueo_llenado', null)
            ->assertJsonPath('vineta.proceso.pasos.1.completado', false)
            ->assertJsonPath('vineta.proceso.pasos.2.completado', true);

        $this->postJson("/api/vinetas/{$llenadoPrimero->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Anillado sencillo',
            '2026-08-21',
            '08:00'
        ))->assertCreated();

        $this->postJson("/api/vinetas/{$anilladoPrimero->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Celofan y sello',
            '2026-08-20',
            '09:00'
        ))->assertCreated();

        $this->postJson("/api/vinetas/{$anilladoPrimero->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Llenado de cajas',
            '2026-08-21',
            '09:00'
        ))->assertCreated();

        $this->assertDatabaseCount('vineta_registros', 4);
    }

    public function test_update_allows_llenado_without_anillado_and_allows_changing_anillado_after_llenado(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = $this->createProcessEmpleado();
        $vineta = $this->createProcessVineta(5003);
        $registro = $this->createProcessRegistro(
            $vineta,
            $empleado,
            'Control de calidad',
            '2026-08-20',
            '08:00:00'
        );

        $this->patchJson("/api/vineta-registros/{$registro->id}", $this->updateProcessPayload(
            $empleado,
            'Llenado de cajas',
            '2026-08-20',
            '08:00'
        ))->assertOk()
            ->assertJsonPath('registro.actividad.nombre', 'Llenado de cajas');

        $anillado = $this->createProcessRegistro(
            $vineta,
            $empleado,
            'Anillado sencillo',
            '2026-08-21',
            '09:00:00'
        );

        $this->patchJson("/api/vineta-registros/{$anillado->id}", $this->updateProcessPayload(
            $empleado,
            'Empaque final',
            '2026-08-21',
            '09:00'
        ))->assertOk()
            ->assertJsonPath('registro.actividad.nombre', 'Empaque final');

        $this->assertDatabaseHas('vineta_registros', [
            'id' => $registro->id,
            'actividad_nombre' => 'Llenado de cajas',
        ]);
        $this->assertDatabaseHas('vineta_registros', [
            'id' => $anillado->id,
            'actividad_nombre' => 'Empaque final',
        ]);
    }

    public function test_store_and_update_still_reject_another_activity_in_a_completed_process_group(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = $this->createProcessEmpleado();
        $vineta = $this->createProcessVineta(5004);

        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Anillado sencillo',
            '2026-08-20',
            '08:00'
        ))->assertCreated();

        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Celofan y sello',
            '2026-08-21',
            '08:00'
        ))->assertUnprocessable()
            ->assertJsonValidationErrors('actividad_nombre')
            ->assertJsonPath('errors.actividad_nombre.0', 'Esta viñeta ya tiene anillado registrado.');

        $registro = $this->createProcessRegistro(
            $vineta,
            $empleado,
            'Control de calidad',
            '2026-08-22',
            '08:00:00'
        );

        $this->patchJson("/api/vineta-registros/{$registro->id}", $this->updateProcessPayload(
            $empleado,
            'Celofanado especial',
            '2026-08-22',
            '08:00'
        ))->assertUnprocessable()
            ->assertJsonValidationErrors('actividad_nombre')
            ->assertJsonPath('errors.actividad_nombre.0', 'Esta viñeta ya tiene anillado registrado.');


        $this->assertSame('Control de calidad', $registro->refresh()->actividad_nombre);
        $this->assertDatabaseCount('vineta_registros', 2);
    }

    public function test_store_and_update_still_reject_an_exact_activity_duplicate_on_the_same_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = $this->createProcessEmpleado();
        $vineta = $this->createProcessVineta(5005);

        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Control de calidad',
            '2026-08-20',
            '08:00'
        ))->assertCreated();

        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Control de calidad',
            '2026-08-20',
            '09:00'
        ))->assertConflict()
            ->assertJsonPath('message', 'Ya existe un registro activo para esta viñeta, actividad y fecha.');

        $registro = $this->createProcessRegistro(
            $vineta,
            $empleado,
            'Empaque final',
            '2026-08-20',
            '10:00:00'
        );

        $this->patchJson("/api/vineta-registros/{$registro->id}", $this->updateProcessPayload(
            $empleado,
            'Control de calidad',
            '2026-08-20',
            '10:00'
        ))->assertConflict()
            ->assertJsonPath('message', 'Ya existe otro registro activo para esta viñeta, actividad y fecha.');

        $this->assertSame('Empaque final', $registro->refresh()->actividad_nombre);
        $this->assertDatabaseCount('vineta_registros', 2);
    }

    public function test_store_allows_multiple_distinct_llenado_activities_on_same_vineta_and_rejects_exact_activity_duplicate(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = $this->createProcessEmpleado();
        $vineta = $this->createProcessVineta(5006);

        // 1. First filling activity: Sellado de bolsas
        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Sellado de bolsas',
            '2026-08-24',
            '08:00'
        ))->assertCreated()
            ->assertJsonPath('proceso.pasos.2.completado', true)
            ->assertJsonCount(1, 'proceso.pasos.2.actividades');

        // 2. Second distinct filling activity on same vineta: Displays
        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Displays',
            '2026-08-24',
            '09:00'
        ))->assertCreated()
            ->assertJsonPath('proceso.pasos.2.completado', true)
            ->assertJsonCount(2, 'proceso.pasos.2.actividades');

        // 3. Third distinct filling activity on same vineta: Llenado de cajas
        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Llenado de cajas',
            '2026-08-24',
            '10:00'
        ))->assertCreated()
            ->assertJsonPath('proceso.pasos.2.completado', true)
            ->assertJsonCount(3, 'proceso.pasos.2.actividades');

        // 4. Repeating an already registered activity on this vineta on same date should return 409
        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Sellado de bolsas',
            '2026-08-24',
            '11:00'
        ))->assertConflict()
            ->assertJsonPath('message', 'Ya existe un registro activo para esta viñeta, actividad y fecha.');

        // 5. Repeating an already registered activity on this vineta on a different date should return 422
        $this->postJson("/api/vinetas/{$vineta->id}/registros", $this->storeProcessPayload(
            $empleado,
            'Sellado de bolsas',
            '2026-08-25',
            '08:00'
        ))->assertUnprocessable()
            ->assertJsonValidationErrors('actividad_nombre')
            ->assertJsonPath('errors.actividad_nombre.0', 'Esta viñeta ya tiene la actividad Sellado de bolsas registrada.');

        $this->assertDatabaseCount('vineta_registros', 3);

    }

    private function createProcessEmpleado(): Empleado

    {
        return Empleado::create([
            'codigo' => 'EMP-PROCESO',
            'nombre' => 'Empleado proceso',
            'cargo' => 'Operador',
            'area' => 'Empaque',
            'activo' => true,
        ]);
    }

    private function createProcessVineta(int $apiId): Vineta
    {
        return Vineta::create([
            'api_id' => $apiId,
            'cantidad_puros' => 20,
            'impreso' => true,
        ]);
    }

    private function storeProcessPayload(
        Empleado $empleado,
        string $actividad,
        string $fecha,
        string $hora
    ): array {
        return [
            'empleado_id' => $empleado->id,
            'actividad_nombre' => $actividad,
            'cantidad_puros' => 20,
            'fecha_registro' => $fecha,
            'hora_registro' => $hora,
        ];
    }

    private function updateProcessPayload(
        Empleado $empleado,
        string $actividad,
        string $fecha,
        string $hora
    ): array {
        return [
            'empleado_codigo' => $empleado->codigo,
            'actividad_nombre' => $actividad,
            'cantidad_puros' => 20,
            'fecha_registro' => $fecha,
            'hora_registro' => $hora,
        ];
    }

    private function createProcessRegistro(
        Vineta $vineta,
        Empleado $empleado,
        string $actividad,
        string $fecha,
        string $hora
    ): VinetaRegistro {
        return VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'empleado_id' => $empleado->id,
            'vineta_api_id' => $vineta->api_id,
            'actividad_nombre' => $actividad,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
            'fecha_registro' => $fecha,
            'hora_registro' => $hora,
            'registrado_en' => $fecha.' '.$hora,
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
            'raw_payload' => ['modo_registro' => 'por_tarea'],
        ]);
    }

    public function test_it_allows_scanning_and_registering_for_future_dates_one_week_ahead(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $empleado = Empleado::create([
            'codigo' => 'EMP-FUTURO',
            'nombre' => 'Empleado Adelanto',
            'cargo' => 'Anillador',
            'area' => 'Empaque',
            'activo' => true,
        ]);

        $vineta = Vineta::create([
            'api_id' => 9001,
            'item' => 'ITEM-9001',
            'nombre' => 'Producto Futuro',
            'cantidad_puros' => 25,
            'orden' => 'ORD-9001',
        ]);

        $fechaFutura = now('America/Tegucigalpa')->addDays(7)->format('Y-m-d');

        $response = $this->postJson("/api/vinetas/{$vineta->id}/registros", [
            'empleado_codigo' => $empleado->codigo,
            'actividad_nombre' => '2 Anillo, Celofan, Cello',
            'cantidad_puros' => 25,
            'fecha_registro' => $fechaFutura,
            'hora_registro' => '10:00',
            'modo_registro' => 'por_tarea',
        ]);

        $response->assertCreated()
            ->assertJsonPath('registro.fecha_registro', $fechaFutura)
            ->assertJsonPath('registro.empleado.codigo', $empleado->codigo);

        $this->assertDatabaseHas('vineta_registros', [
            'vineta_id' => $vineta->id,
            'empleado_codigo' => $empleado->codigo,
            'fecha_registro' => $fechaFutura.' 00:00:00',
        ]);

        $resumenResponse = $this->getJson("/api/empleados/{$empleado->id}/resumen-diario?fecha={$fechaFutura}");
        $resumenResponse->assertOk()
            ->assertJsonPath('resumen_diario.fecha', $fechaFutura)
            ->assertJsonPath('resumen_diario.total_actividades', 100);
    }
}
