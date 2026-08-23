<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ActividadController;
use App\Models\Actividad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActividadSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_scope_returns_unique_catalog_activities_without_product_associations(): void
    {
        Actividad::create([
            'api_id_actividad' => 101,
            'codigo_actividad' => 'ACT-101',
            'nombre' => 'Actividad general uno',
        ]);
        Actividad::create([
            'api_id_actividad' => 102,
            'codigo_actividad' => 'ACT-102',
            'nombre' => 'Actividad general dos',
        ]);

        $request = Request::create('/api/actividades/search', 'GET', [
            'scope' => 'general',
            'limit' => 80,
        ]);
        $response = app(ActividadController::class)->search($request);
        $activities = $response->getData(true)['activities'];

        $this->assertCount(2, $activities);
        $this->assertSame('Actividad general dos', $activities[0]['nombre']);
        $this->assertNull($activities[0]['producto_id']);
        $this->assertSame('Actividad general uno', $activities[1]['nombre']);
    }
}
