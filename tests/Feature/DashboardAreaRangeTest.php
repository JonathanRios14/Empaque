<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardAreaRangeTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(): User
    {
        $user = User::factory()->create();
        Permission::findOrCreate('dashboard.ver', 'web');
        $user->givePermissionTo('dashboard.ver');

        return $user;
    }

    public function test_dashboard_retorna_resumen_mensual_por_defecto(): void
    {
        $this->withoutExceptionHandling();
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Resumen por área');
        $response->assertSee('areaRangeFrom');
        $response->assertSee('areaRangeTo');
    }

    public function test_dashboard_endpoint_ajax_resumen_rango_mes_por_defecto(): void
    {
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'resumen_rango' => 1,
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'ok',
            'is_custom_range',
            'label',
            'fecha_desde',
            'fecha_hasta',
            'total_actividades',
            'areas' => [
                'rezago' => ['key', 'label', 'actividades', 'actividades_formatted', 'empleados', 'registros', 'puros', 'share'],
                'anillado',
                'llenado',
            ],
        ]);
        $this->assertFalse($response->json('is_custom_range'));
    }

    public function test_dashboard_endpoint_ajax_resumen_con_rango_personalizado(): void
    {
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'resumen_rango' => 1,
            'fecha_desde' => '2026-08-01',
            'fecha_hasta' => '2026-08-15',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('is_custom_range'));
        $this->assertEquals('01/08/2026 - 15/08/2026', $response->json('label'));
        $this->assertEquals('2026-08-01', $response->json('fecha_desde'));
        $this->assertEquals('2026-08-15', $response->json('fecha_hasta'));
    }

    public function test_dashboard_endpoint_ajax_ranking_mes(): void
    {
        $user = $this->createAuthorizedUser();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'ranking_mes' => 1,
            'mes' => '2026-08',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'ok',
            'mes',
            'label',
            'ranking' => [
                'rezago' => ['key', 'label', 'color', 'rows'],
                'anillado' => ['key', 'label', 'color', 'rows'],
                'llenado' => ['key', 'label', 'color', 'rows'],
            ],
        ]);
        $this->assertTrue($response->json('ok'));
        $this->assertEquals('2026-08', $response->json('mes'));
        $this->assertEquals('Agosto 2026', $response->json('label'));
    }
}
