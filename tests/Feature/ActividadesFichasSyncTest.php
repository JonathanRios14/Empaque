<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Producto;
use App\Models\TipoEmpaque;
use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use App\Models\Empleado;
use App\Services\ActividadApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ActividadesFichasSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_actividades_sync_imports_fichas_actividades_safely(): void
    {
        Http::fake([
            'http://192.168.2.7:8080/api/fichas/empaque/actividades*' => Http::response([
                'data' => [
                    [
                        'codigo_actividad' => '196',
                        'nombre_actividad' => 'Llenado de Bolsas 3 Puros (Kretek)',
                        'precio_mo' => 0.0897917,
                    ],
                    [
                        'codigo_actividad' => '135',
                        'nombre_actividad' => '2 Anillo, Celofan',
                        'precio_mo' => 0.2698809,
                    ],
                ],
            ], 200),
        ]);

        $service = app(ActividadApiService::class);
        $resultado = $service->sincronizar();

        $this->assertTrue($resultado['ok']);
        $this->assertDatabaseHas('actividades', [
            'codigo_actividad' => '196',
            'nombre' => 'Llenado de Bolsas 3 Puros (Kretek)',
        ]);
        $this->assertDatabaseHas('actividades', [
            'codigo_actividad' => '135',
            'nombre' => '2 Anillo, Celofan',
        ]);
    }

    public function test_toggle_actividad_producto_switches_active_state(): void
    {
        $user = User::factory()->create();
        $producto = Producto::create([
            'api_id_producto' => 1001,
            'nombre' => 'Test Cigar',
            'item' => 'ITEM-01',
            'codigo_producto' => 'P-01',
        ]);
        $actividad = Actividad::create([
            'api_id_actividad' => 101,
            'codigo_actividad' => '101',
            'nombre' => 'Actividad Test',
        ]);

        DB::table('actividad_producto')->insert([
            'producto_id' => $producto->id,
            'actividad_id' => $actividad->id,
            'precio_mo' => 0.05,
            'activo' => true,
            'origen' => 'api',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/catalogos/productos/{$producto->id}/actividades/{$actividad->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('activo', false);

        $this->assertDatabaseHas('actividad_producto', [
            'producto_id' => $producto->id,
            'actividad_id' => $actividad->id,
            'activo' => false,
        ]);

        // Toggle back to active
        $response2 = $this->actingAs($user)->postJson("/catalogos/productos/{$producto->id}/actividades/{$actividad->id}/toggle");

        $response2->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('activo', true);

        $this->assertDatabaseHas('actividad_producto', [
            'producto_id' => $producto->id,
            'actividad_id' => $actividad->id,
            'activo' => true,
        ]);
    }

    public function test_scanned_vineta_auto_associates_activity_and_orders_by_recent_scan(): void
    {
        $producto = Producto::create([
            'api_id_producto' => 2002,
            'nombre' => 'Producto Scanned Test',
            'item' => 'ITEM-SCAN',
            'codigo_producto' => 'P-SCAN',
        ]);

        $actividad1 = Actividad::create([
            'api_id_actividad' => 201,
            'codigo_actividad' => '201',
            'nombre' => 'Actividad Antigua',
        ]);

        $actividad2 = Actividad::create([
            'api_id_actividad' => 202,
            'codigo_actividad' => '202',
            'nombre' => 'Actividad Reciente',
        ]);

        $tipoEmpaque = TipoEmpaque::create(['nombre' => 'MAZOS 1/5']);

        // Insert actividad1 (older)
        DB::table('actividad_producto')->insert([
            'producto_id' => $producto->id,
            'actividad_id' => $actividad1->id,
            'tipo_empaque_id' => $tipoEmpaque->id,
            'precio_mo' => 0.10,
            'activo' => true,
            'origen' => 'api',
            'ultimo_escaneo_en' => now()->subDays(5),
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Associate actividad2 as recently scanned
        app(ActividadApiService::class)->asociarActividadDeEscaneo(
            $producto,
            $actividad2,
            $tipoEmpaque->id,
            0.15,
            now()
        );

        $vineta = Vineta::create([
            'api_id' => 9999,
            'item' => 'ITEM-SCAN',
            'codigo_producto' => 'P-SCAN',
            'tipo_empaque' => 'MAZOS 1/5',
            'fecha' => now()->toDateString(),
            'cantidad' => 500,
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/vinetas/{$vineta->id}/actividades");

        $response->assertOk();
        $activities = $response->json('activities');

        $this->assertCount(2, $activities);
        $this->assertSame('Actividad Reciente', $activities[0]['nombre']);
        $this->assertSame('Actividad Antigua', $activities[1]['nombre']);
    }
}
