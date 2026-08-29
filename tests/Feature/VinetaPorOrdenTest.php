<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VinetaPorOrden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VinetaPorOrdenTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_puede_ver_vista_vinetas_por_orden(): void
    {
        $user = User::factory()->create();

        VinetaPorOrden::create([
            'codigo_qr' => 'o1',
            'orden' => 'ORD-1001',
            'orden_del_sistema' => 'OS-501',
            'item' => 'ITM-99',
            'marca' => 'Flor de Copan',
            'nombre' => 'Corona',
            'vitola' => '5x50',
            'capa' => 'Habano',
            'tipo_empaque' => 'Caja 20',
            'codigo_producto' => 'P-1001',
            'cantidad_puros' => 200,
            'estado' => 'Activo',
            'fecha' => '2026-08-25',
        ]);

        $response = $this->actingAs($user)->get(route('vinetas-por-orden.index'));

        $response->assertOk();
        $response->assertSee('Viñetas por orden');
        $response->assertSee('Flor de Copan');
        $response->assertSee('ORD-1001');
        $response->assertSee('o1');
    }

    public function test_filtros_de_vinetas_por_orden(): void
    {
        $user = User::factory()->create();

        VinetaPorOrden::create([
            'codigo_qr' => 'o1',
            'marca' => 'Marca A',
            'orden' => 'ORD-A',
            'fecha' => '2026-08-25',
        ]);

        VinetaPorOrden::create([
            'codigo_qr' => 'o2',
            'marca' => 'Marca B',
            'orden' => 'ORD-B',
            'fecha' => '2026-08-25',
        ]);

        $response = $this->actingAs($user)->get(route('vinetas-por-orden.index', [
            'marca' => 'Marca A',
        ]));

        $response->assertOk();
        $response->assertSee('Marca A');
        $response->assertDontSee('Marca B');
    }

    public function test_peticion_ajax_retorna_solo_la_tabla_parcial(): void
    {
        $user = User::factory()->create();

        VinetaPorOrden::create([
            'codigo_qr' => 'o3',
            'marca' => 'Marca C',
            'fecha' => '2026-08-25',
        ]);

        $response = $this->actingAs($user)->get(route('vinetas-por-orden.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('vinetasPorOrdenTableInner');
        $response->assertDontSee('<!DOCTYPE html>');
    }
}
