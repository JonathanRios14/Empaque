<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vineta;
use App\Models\VinetaRegistro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class VinetaRegistroExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_product_subtotals_and_employee_totals_in_the_first_sheet(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $index) {
            $vineta = Vineta::create(['api_id' => 9000 + $index, 'impreso' => true]);
            $this->createRegistro($vineta, [
                'cantidad_puros' => 260,
                'cantidad_actividades' => 4,
                'minutos_trabajados' => 82,
            ]);
        }

        $otraVineta = Vineta::create(['api_id' => 9010, 'impreso' => true]);
        $this->createRegistro($otraVineta, [
            'producto_codigo' => 'P-99999',
            'actividad_nombre' => 'Rezagado',
            'cantidad_puros' => 250,
            'cantidad_actividades' => 2,
            'minutos_trabajados' => 61,
        ]);

        $vinetaOtroEmpleado = Vineta::create(['api_id' => 9020, 'impreso' => true]);
        $this->createRegistro($vinetaOtroEmpleado, [
            'empleado_codigo' => '7000',
            'empleado_nombre' => 'ZUNIGA EMPLEADO DOS',
            'cantidad_puros' => 100,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 30,
        ]);

        $response = $this->actingAs($user)->get(route('vineta-registros.export', [
            'fecha_desde' => '2026-08-18',
            'fecha_hasta' => '2026-08-18',
        ]));

        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path) === true);

        try {
            $workbook = (string) $zip->getFromName('xl/workbook.xml');
            $rows = $this->xlsxRows((string) $zip->getFromName('xl/worksheets/sheet1.xml'));

            $this->assertStringContainsString('name="Resumen agrupado"', $workbook);
            $this->assertStringContainsString('name="Detalle"', $workbook);

            $subtotal = collect($rows)->first(fn (array $row) => ($row[3] ?? null) === 'Subtotal producto'
                && ($row[1] ?? null) === 'AGUILAR VALLADARES NALLELY MARIBEL'
                && ($row[5] ?? null) === 'P-03202');

            $this->assertNotNull($subtotal);
            $this->assertSame('3', $subtotal[4]);
            $this->assertSame('VINTAGE 2003', $subtotal[6]);
            $this->assertSame('10104751', $subtotal[7]);
            $this->assertSame('3556', $subtotal[8]);
            $this->assertSame('HON-4295', $subtotal[9]);
            $this->assertSame('2 Anillo, Celofan, Cello (3)', $subtotal[10]);
            $this->assertSame('780', $subtotal[11]);
            $this->assertSame('3120', $subtotal[12]);
            $this->assertSame('246', $subtotal[13]);
            $this->assertSame('4 h 6 min', $subtotal[14]);

            $totalEmpleado = collect($rows)->first(fn (array $row) => ($row[3] ?? null) === 'Total empleado'
                && ($row[1] ?? null) === 'AGUILAR VALLADARES NALLELY MARIBEL');

            $this->assertNotNull($totalEmpleado);
            $this->assertSame('4', $totalEmpleado[4]);
            $this->assertSame('1030', $totalEmpleado[11]);
            $this->assertSame('3620', $totalEmpleado[12]);
            $this->assertSame('307', $totalEmpleado[13]);
            $this->assertSame('5 h 7 min', $totalEmpleado[14]);
            $this->assertSame(2, collect($rows)->where(3, 'Total empleado')->count());
        } finally {
            $zip->close();
            @unlink($path);
        }
    }

    private function createRegistro(Vineta $vineta, array $attributes = []): VinetaRegistro
    {
        return VinetaRegistro::create(array_merge([
            'vineta_id' => $vineta->id,
            'codigo_vineta' => 'VIN-'.$vineta->api_id,
            'vineta_api_id' => $vineta->api_id,
            'producto_codigo' => 'P-03202',
            'producto_item' => '10104751',
            'producto_nombre' => 'Producto prueba',
            'marca' => 'VINTAGE 2003',
            'orden_del_sistema' => '3556',
            'orden' => 'HON-4295',
            'actividad_nombre' => '2 Anillo, Celofan, Cello',
            'empleado_codigo' => '6087',
            'empleado_nombre' => 'AGUILAR VALLADARES NALLELY MARIBEL',
            'cantidad_puros' => 20,
            'cantidad_cajones' => 1,
            'cantidad_actividades' => 1,
            'minutos_trabajados' => 10,
            'fecha_registro' => '2026-08-18',
            'hora_registro' => '08:00:00',
            'registrado_en' => '2026-08-18 08:00:00',
            'estado' => VinetaRegistro::ESTADO_ACTIVO,
        ], $attributes));
    }

    private function xlsxRows(string $xml): array
    {
        preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s', $xml, $rowMatches);

        return collect($rowMatches[1] ?? [])
            ->map(function (string $rowXml) {
                preg_match_all('/<c\b[^>]*>(.*?)<\/c>/s', $rowXml, $cellMatches);

                return collect($cellMatches[1] ?? [])
                    ->map(function (string $cellXml) {
                        if (preg_match('/<t>(.*?)<\/t>/s', $cellXml, $textMatch)) {
                            return html_entity_decode($textMatch[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
                        }

                        return preg_match('/<v>(.*?)<\/v>/s', $cellXml, $valueMatch)
                            ? $valueMatch[1]
                            : '';
                    })
                    ->all();
            })
            ->all();
    }
}
