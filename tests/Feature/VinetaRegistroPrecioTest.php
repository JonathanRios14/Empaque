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

class VinetaRegistroPrecioTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_activity_catalog_price_without_a_product_pivot(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $actividad = $this->createActividad();
        $producto = $this->createProducto(1729, 'P-01946', '151945');
        $productoReferencia = $this->createProducto(1730, 'P-REF', 'REF-001');
        $this->attachPrecio($productoReferencia, $actividad, 0.0384248);
        $empleado = $this->createEmpleado();
        $vineta = Vineta::create([
            'api_id' => 15293,
            'codigo_producto' => $producto->codigo_producto,
            'item' => $producto->item,
            'nombre' => $producto->nombre,
            'cantidad_puros' => 144,
            'impreso' => true,
        ]);

        $response = $this->postJson("/api/vinetas/{$vineta->id}/registros", [
            'producto_id' => $producto->id,
            'actividad_id' => $actividad->id,
            'empleado_id' => $empleado->id,
            'cantidad_puros' => 144,
            'modo_registro' => 'por_tarea',
            'precio_mo' => 0,
            'fecha_registro' => '2026-08-18',
            'hora_registro' => '09:31',
        ]);

        $response->assertCreated()
            ->assertJsonPath('registro.actividad.codigo_actividad', '141')
            ->assertJsonPath('registro.actividad.precio_mo', 0.0384248);

        $registro = VinetaRegistro::query()->sole();

        $this->assertSame('0.0384', $registro->precio_mo);
        $this->assertSame(0.0384248, $registro->precioMoEfectivo());
    }

    public function test_it_uses_the_lowest_positive_catalog_price_when_an_activity_has_multiple_prices(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $actividad = $this->createActividad();
        $producto = $this->createProducto(1729, 'P-01946', '151945');
        $this->attachPrecio($this->createProducto(1730, 'P-REF-1', 'REF-001'), $actividad, 0.0384248);
        $this->attachPrecio($this->createProducto(1731, 'P-REF-2', 'REF-002'), $actividad, 0.0410000);
        $empleado = $this->createEmpleado();
        $vineta = Vineta::create([
            'api_id' => 15293,
            'codigo_producto' => $producto->codigo_producto,
            'item' => $producto->item,
            'nombre' => $producto->nombre,
            'cantidad_puros' => 144,
            'impreso' => true,
        ]);

        $response = $this->postJson("/api/vinetas/{$vineta->id}/registros", [
            'producto_id' => $producto->id,
            'actividad_id' => $actividad->id,
            'empleado_id' => $empleado->id,
            'cantidad_puros' => 144,
            'modo_registro' => 'por_tarea',
            'precio_mo' => 0,
            'fecha_registro' => '2026-08-18',
            'hora_registro' => '09:31',
        ]);

        $response->assertCreated()
            ->assertJsonPath('registro.actividad.precio_mo', 0.0384248);

        $registro = VinetaRegistro::query()->sole();

        $this->assertSame('0.0384', $registro->precio_mo);
        $this->assertSame(0.0384248, $registro->precioMoEfectivo());
    }

    public function test_the_registered_vinetas_table_uses_the_activity_price_for_historical_nulls(): void
    {
        $user = User::factory()->create();
        $actividad = $this->createActividad();
        $producto = $this->createProducto(1729, 'P-01946', '151945');
        $this->attachPrecio($this->createProducto(1730, 'P-REF', 'REF-001'), $actividad, 0.0384248);
        $empleado = $this->createEmpleado();
        $vineta = Vineta::create([
            'api_id' => 15293,
            'codigo_producto' => $producto->codigo_producto,
            'item' => $producto->item,
            'nombre' => $producto->nombre,
            'cantidad_puros' => 144,
            'impreso' => true,
        ]);
        VinetaRegistro::create([
            'vineta_id' => $vineta->id,
            'vineta_api_id' => $vineta->api_id,
            'producto_id' => $producto->id,
            'producto_codigo' => $producto->codigo_producto,
            'producto_item' => $producto->item,
            'producto_nombre' => $producto->nombre,
            'actividad_id' => $actividad->id,
            'actividad_codigo' => $actividad->codigo_actividad,
            'actividad_nombre' => $actividad->nombre,
            'empleado_id' => $empleado->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'cantidad_puros' => 144,
            'cantidad_cajones' => 1,
            'precio_mo' => null,
            'fecha_registro' => '2026-08-18',
            'hora_registro' => '09:31:00',
            'registrado_en' => '2026-08-18 09:31:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ]);

        $this->actingAs($user)
            ->get('/vinetas-registradas')
            ->assertOk()
            ->assertSee('0.0384248')
            ->assertSee('5.53');
    }

    private function createActividad(): Actividad
    {
        return Actividad::create([
            'api_id_actividad' => 54,
            'codigo_actividad' => '141',
            'nombre' => 'Rezagado Family',
        ]);
    }

    private function createProducto(int $apiId, string $codigo, string $item): Producto
    {
        return Producto::create([
            'api_id_producto' => $apiId,
            'codigo_producto' => $codigo,
            'item' => $item,
            'nombre' => 'Robusto',
        ]);
    }

    private function createEmpleado(): Empleado
    {
        return Empleado::create([
            'codigo' => '1829',
            'nombre' => 'GONZALEZ DIGNA MIREDIA',
            'cargo' => 'Rezago',
            'area' => 'Empaque',
            'activo' => true,
        ]);
    }

    private function attachPrecio(Producto $producto, Actividad $actividad, float $precio): void
    {
        DB::table('actividad_producto')->insert([
            'producto_id' => $producto->id,
            'actividad_id' => $actividad->id,
            'tipo_empaque_id' => null,
            'precio_mo' => $precio,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
