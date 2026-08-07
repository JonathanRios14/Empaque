<?php

namespace App\Http\Controllers;

use App\Models\VinetaRegistro;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private string $timezone = 'America/Tegucigalpa';

    public function index()
    {
        $today = Carbon::now($this->timezone)->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $yearStart = $today->copy()->startOfYear();

        $hasRegistros = Schema::hasTable('vineta_registros');
        $hasHorasOrdinarias = Schema::hasTable('empleado_horas_ordinarias');
        $hasCantidadActividades = $hasRegistros && Schema::hasColumn('vineta_registros', 'cantidad_actividades');
        $hasMinutosTrabajados = $hasRegistros && Schema::hasColumn('vineta_registros', 'minutos_trabajados');

        $context = compact('hasRegistros', 'hasHorasOrdinarias', 'hasCantidadActividades', 'hasMinutosTrabajados');

        $produccionHoy = $this->productionSummary($today, $today, $context);
        $produccionMes = $this->productionSummary($monthStart, $today, $context);
        $produccionAnio = $this->productionSummary($yearStart, $today, $context);
        $produccionTotal = $this->productionSummary(null, null, $context);

        $tendenciaDiaria = $this->dailyTrend($monthStart, $today, $context);
        $tendenciaMensual = $this->monthlyTrend($today, $context);
        $rankingEmpleados = $this->topEmployees($monthStart, $today, $context);
        $rankingActividades = $this->topActivities($monthStart, $today, $context);
        $distribucionProcesos = $this->processBreakdown($monthStart, $today, $context);
        $ultimosRegistros = $this->latestProductionRecords($context);

        return view('dashboard', compact(
            'today',
            'produccionHoy',
            'produccionMes',
            'produccionAnio',
            'produccionTotal',
            'tendenciaDiaria',
            'tendenciaMensual',
            'rankingEmpleados',
            'rankingActividades',
            'distribucionProcesos',
            'ultimosRegistros'
        ));
    }

    private function productionSummary(?Carbon $from, ?Carbon $to, array $context): array
    {
        $row = (object) [
            'registros' => 0,
            'puros' => 0,
            'cajones' => 0,
            'actividades' => 0,
            'minutos_cajones' => 0,
            'monto' => 0,
            'empleados' => 0,
        ];

        if ($context['hasRegistros']) {
            $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
            $minutesExpression = $context['hasMinutosTrabajados']
                ? 'COALESCE(SUM(minutos_trabajados), 0)'
                : '0';

            $row = $this->activeRecordsQuery($from, $to)
                ->selectRaw('COUNT(*) as registros')
                ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
                ->selectRaw('COALESCE(SUM(cantidad_cajones), 0) as cajones')
                ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
                ->selectRaw("$minutesExpression as minutos_cajones")
                ->selectRaw('COALESCE(SUM(cantidad_puros * COALESCE(precio_mo, 0)), 0) as monto')
                ->selectRaw('COUNT(DISTINCT empleado_codigo) as empleados')
                ->first();
        }

        $minutosOrdinarios = $this->ordinaryMinutes($from, $to, $context['hasHorasOrdinarias']);
        $minutos = (int) $row->minutos_cajones + $minutosOrdinarios;
        $horas = $minutos > 0 ? round($minutos / 60, 1) : 0;

        return [
            'registros' => (int) $row->registros,
            'puros' => (int) $row->puros,
            'cajones' => (int) $row->cajones,
            'actividades' => (int) $row->actividades,
            'minutos' => $minutos,
            'minutos_cajones' => (int) $row->minutos_cajones,
            'minutos_ordinarios' => $minutosOrdinarios,
            'tiempo' => VinetaRegistro::minutosATiempoTexto($minutos),
            'horas' => $horas,
            'monto' => (float) $row->monto,
            'empleados' => (int) $row->empleados,
            'actividades_por_hora' => $horas > 0 ? round(((int) $row->actividades) / $horas, 1) : 0,
        ];
    }

    private function dailyTrend(Carbon $from, Carbon $to, array $context): array
    {
        $labels = [];
        $keys = [];

        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            $key = $date->format('Y-m-d');
            $keys[] = $key;
            $labels[] = $date->format('d/m');
        }

        $rows = collect();

        if ($context['hasRegistros']) {
            $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
            $minutesExpression = $context['hasMinutosTrabajados']
                ? 'COALESCE(SUM(minutos_trabajados), 0)'
                : '0';

            $rows = $this->activeRecordsQuery($from, $to)
                ->selectRaw('fecha_registro as fecha')
                ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
                ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
                ->selectRaw("$minutesExpression as minutos")
                ->groupBy('fecha_registro')
                ->get()
                ->keyBy(fn ($row) => (string) $row->fecha);
        }

        $ordinaryMinutes = $this->ordinaryMinutesByDate($from, $to, $context['hasHorasOrdinarias']);

        return [
            'labels' => $labels,
            'actividades' => array_map(fn ($key) => (int) ($rows[$key]->actividades ?? 0), $keys),
            'puros' => array_map(fn ($key) => (int) ($rows[$key]->puros ?? 0), $keys),
            'horas' => array_map(function ($key) use ($rows, $ordinaryMinutes) {
                $minutes = (int) ($rows[$key]->minutos ?? 0) + (int) ($ordinaryMinutes[$key] ?? 0);

                return round($minutes / 60, 1);
            }, $keys),
        ];
    }

    private function monthlyTrend(Carbon $today, array $context): array
    {
        $yearStart = $today->copy()->startOfYear();
        $labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $months = range(1, 12);
        $rows = collect();

        if ($context['hasRegistros']) {
            $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
            $minutesExpression = $context['hasMinutosTrabajados']
                ? 'COALESCE(SUM(minutos_trabajados), 0)'
                : '0';

            $rows = $this->activeRecordsQuery($yearStart, $today)
                ->selectRaw('MONTH(fecha_registro) as mes')
                ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
                ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
                ->selectRaw("$minutesExpression as minutos")
                ->groupByRaw('MONTH(fecha_registro)')
                ->get()
                ->keyBy(fn ($row) => (int) $row->mes);
        }

        $ordinaryMinutes = $this->ordinaryMinutesByMonth($yearStart, $today, $context['hasHorasOrdinarias']);

        return [
            'labels' => $labels,
            'actividades' => array_map(fn ($month) => (int) ($rows[$month]->actividades ?? 0), $months),
            'puros' => array_map(fn ($month) => (int) ($rows[$month]->puros ?? 0), $months),
            'horas' => array_map(function ($month) use ($rows, $ordinaryMinutes) {
                $minutes = (int) ($rows[$month]->minutos ?? 0) + (int) ($ordinaryMinutes[$month] ?? 0);

                return round($minutes / 60, 1);
            }, $months),
        ];
    }

    private function topEmployees(Carbon $from, Carbon $to, array $context): array
    {
        if (! $context['hasRegistros']) {
            return [];
        }

        $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
        $minutesExpression = $context['hasMinutosTrabajados']
            ? 'COALESCE(SUM(minutos_trabajados), 0)'
            : '0';

        $rows = $this->activeRecordsQuery($from, $to)
            ->selectRaw("COALESCE(NULLIF(empleado_codigo, ''), 'N/A') as codigo")
            ->selectRaw("COALESCE(NULLIF(empleado_nombre, ''), 'Empleado') as nombre")
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
            ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
            ->selectRaw("$minutesExpression as minutos")
            ->groupBy('empleado_codigo', 'empleado_nombre')
            ->orderByDesc('actividades')
            ->limit(8)
            ->get();

        return $rows->map(fn ($row) => [
            'codigo' => $row->codigo,
            'nombre' => $row->nombre,
            'registros' => (int) $row->registros,
            'puros' => (int) $row->puros,
            'actividades' => (int) $row->actividades,
            'tiempo' => VinetaRegistro::minutosATiempoTexto((int) $row->minutos),
        ])->all();
    }

    private function topActivities(Carbon $from, Carbon $to, array $context): array
    {
        if (! $context['hasRegistros']) {
            return [];
        }

        $activityExpression = $this->activityExpression($context['hasCantidadActividades']);

        return $this->activeRecordsQuery($from, $to)
            ->selectRaw("COALESCE(NULLIF(actividad_nombre, ''), 'Actividad') as nombre")
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('COALESCE(SUM(cantidad_puros), 0) as puros')
            ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
            ->groupBy('actividad_nombre')
            ->orderByDesc('actividades')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'nombre' => $row->nombre,
                'registros' => (int) $row->registros,
                'puros' => (int) $row->puros,
                'actividades' => (int) $row->actividades,
            ])
            ->all();
    }

    private function processBreakdown(Carbon $from, Carbon $to, array $context): array
    {
        if (! $context['hasRegistros']) {
            return [
                'labels' => [],
                'data' => [],
                'rows' => [],
            ];
        }

        $activityExpression = $this->activityExpression($context['hasCantidadActividades']);
        $case = $this->processCaseExpression();

        $rows = $this->activeRecordsQuery($from, $to)
            ->selectRaw("$case as grupo")
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw("COALESCE(SUM($activityExpression), 0) as actividades")
            ->groupByRaw($case)
            ->orderByDesc('actividades')
            ->get()
            ->map(fn ($row) => [
                'grupo' => $row->grupo,
                'registros' => (int) $row->registros,
                'actividades' => (int) $row->actividades,
            ])
            ->all();

        return [
            'labels' => array_column($rows, 'grupo'),
            'data' => array_column($rows, 'actividades'),
            'rows' => $rows,
        ];
    }

    private function latestProductionRecords(array $context): array
    {
        if (! $context['hasRegistros']) {
            return [];
        }

        return DB::table('vineta_registros')
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->orderByDesc('fecha_registro')
            ->orderByDesc('hora_registro')
            ->orderByDesc('id')
            ->limit(8)
            ->get([
                'vineta_api_id',
                'codigo_vineta',
                'fecha_registro',
                'hora_registro',
                'empleado_nombre',
                'actividad_nombre',
                'marca',
                'cantidad_puros',
            ])
            ->map(fn ($row) => [
                'vineta' => $row->vineta_api_id ? '#' . $row->vineta_api_id : ($row->codigo_vineta ?: 'N/A'),
                'fecha' => Carbon::parse($row->fecha_registro)->format('d/m/Y'),
                'hora' => $row->hora_registro ? Carbon::parse($row->hora_registro)->format('h:i A') : 'N/A',
                'empleado' => $row->empleado_nombre ?: 'Empleado',
                'actividad' => $row->actividad_nombre ?: 'Actividad',
                'marca' => $row->marca ?: 'Sin marca',
                'puros' => (int) $row->cantidad_puros,
            ])
            ->all();
    }

    private function activeRecordsQuery(?Carbon $from = null, ?Carbon $to = null)
    {
        return DB::table('vineta_registros')
            ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
            ->when($from, fn ($query) => $query->whereDate('fecha_registro', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->whereDate('fecha_registro', '<=', $to->toDateString()));
    }

    private function ordinaryMinutes(?Carbon $from, ?Carbon $to, bool $hasHorasOrdinarias): int
    {
        if (! $hasHorasOrdinarias) {
            return 0;
        }

        return (int) DB::table('empleado_horas_ordinarias')
            ->when($from, fn ($query) => $query->whereDate('fecha', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->whereDate('fecha', '<=', $to->toDateString()))
            ->sum('minutos');
    }

    private function ordinaryMinutesByDate(Carbon $from, Carbon $to, bool $hasHorasOrdinarias): array
    {
        if (! $hasHorasOrdinarias) {
            return [];
        }

        return DB::table('empleado_horas_ordinarias')
            ->whereDate('fecha', '>=', $from->toDateString())
            ->whereDate('fecha', '<=', $to->toDateString())
            ->selectRaw('fecha, COALESCE(SUM(minutos), 0) as minutos')
            ->groupBy('fecha')
            ->pluck('minutos', 'fecha')
            ->map(fn ($minutes) => (int) $minutes)
            ->all();
    }

    private function ordinaryMinutesByMonth(Carbon $from, Carbon $to, bool $hasHorasOrdinarias): array
    {
        if (! $hasHorasOrdinarias) {
            return [];
        }

        return DB::table('empleado_horas_ordinarias')
            ->whereDate('fecha', '>=', $from->toDateString())
            ->whereDate('fecha', '<=', $to->toDateString())
            ->selectRaw('MONTH(fecha) as mes, COALESCE(SUM(minutos), 0) as minutos')
            ->groupByRaw('MONTH(fecha)')
            ->pluck('minutos', 'mes')
            ->map(fn ($minutes) => (int) $minutes)
            ->all();
    }

    private function activityExpression(bool $hasCantidadActividades): string
    {
        if (! $hasCantidadActividades) {
            return 'cantidad_puros';
        }

        return 'cantidad_puros * CASE WHEN cantidad_actividades IS NULL OR cantidad_actividades < 1 THEN 1 ELSE cantidad_actividades END';
    }

    private function processCaseExpression(): string
    {
        $text = "LOWER(CONCAT(COALESCE(actividad_nombre, ''), ' ', COALESCE(actividad_tipo_empaque, ''), ' ', COALESCE(actividad_codigo, '')))";

        return "CASE
            WHEN $text LIKE '%rezag%' OR $text LIKE '%rezad%' OR $text LIKE '%resag%' THEN 'Rezago'
            WHEN $text LIKE '%llenad%' THEN 'Llenado'
            WHEN $text LIKE '%anill%' OR $text LIKE '%anil%' OR $text LIKE '%celof%' OR $text LIKE '%sello%' OR $text LIKE '%sell%' THEN 'Anillado'
            ELSE 'Otros'
        END";
    }
}
