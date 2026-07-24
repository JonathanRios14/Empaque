<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\EmpleadoHoraOrdinaria;
use App\Models\VinetaRegistro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EmpleadoHoraOrdinariaController extends Controller
{
    private int $metaDiariaMinutos = 570;

    public function index(Request $request, Empleado $empleado): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $fecha = $data['fecha'];
        $tablaDisponible = Schema::hasTable('empleado_horas_ordinarias');
        $ordinarias = collect();

        if ($tablaDisponible) {
            $ordinarias = EmpleadoHoraOrdinaria::query()
                ->where('empleado_codigo', $empleado->codigo)
                ->whereDate('fecha', $fecha)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
        }

        $cajones = collect();

        if (Schema::hasTable('vineta_registros') && Schema::hasColumn('vineta_registros', 'minutos_trabajados')) {
            $cajones = VinetaRegistro::query()
                ->where('empleado_codigo', $empleado->codigo)
                ->whereDate('fecha_registro', $fecha)
                ->where('estado', VinetaRegistro::ESTADO_ACTIVO)
                ->orderBy('fecha_registro')
                ->orderBy('hora_registro')
                ->orderBy('id')
                ->get();
        }

        $minutosCajones = (int) $cajones->sum(fn (VinetaRegistro $registro) => (int) ($registro->minutos_trabajados ?? 0));
        $minutosOrdinarios = (int) $ordinarias->sum('minutos');

        return response()->json([
            'message' => 'Horas ordinarias encontradas.',
            'tabla_disponible' => $tablaDisponible,
            'empleado' => $this->empleadoPayload($empleado),
            'fecha' => $fecha,
            'resumen' => $this->resumenPayload($minutosCajones, $minutosOrdinarios),
            'cajones' => $cajones->map(fn (VinetaRegistro $registro) => $this->cajonPayload($registro))->values(),
            'horas_ordinarias' => $ordinarias->map(fn (EmpleadoHoraOrdinaria $hora) => $this->horaPayload($hora))->values(),
        ]);
    }

    public function store(Request $request, Empleado $empleado): JsonResponse
    {
        if (! Schema::hasTable('empleado_horas_ordinarias')) {
            return response()->json([
                'message' => 'La tabla de horas ordinarias no existe. Ejecuta la migracion pendiente.',
            ], 409);
        }

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'minutos' => ['required', 'integer', 'min:1', 'max:570'],
            'observacion' => ['required', 'string', 'max:1000'],
        ]);

        $hora = EmpleadoHoraOrdinaria::create([
            'empleado_id' => $empleado->id,
            'registrado_por_user_id' => $request->user()?->id,
            'empleado_codigo' => $empleado->codigo,
            'empleado_nombre' => $empleado->nombre,
            'fecha' => $data['fecha'],
            'minutos' => (int) $data['minutos'],
            'observacion' => $data['observacion'],
            'registrado_por_nombre' => $request->user()?->name,
        ]);

        return response()->json([
            'message' => 'Hora ordinaria agregada correctamente.',
            'hora_ordinaria' => $this->horaPayload($hora),
        ], 201);
    }

    public function update(Request $request, Empleado $empleado, EmpleadoHoraOrdinaria $horaOrdinaria): JsonResponse
    {
        if ($horaOrdinaria->empleado_codigo !== $empleado->codigo) {
            abort(404);
        }

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'minutos' => ['required', 'integer', 'min:1', 'max:570'],
            'observacion' => ['required', 'string', 'max:1000'],
        ]);

        $horaOrdinaria->update([
            'fecha' => $data['fecha'],
            'minutos' => (int) $data['minutos'],
            'observacion' => $data['observacion'],
        ]);

        return response()->json([
            'message' => 'Hora ordinaria actualizada correctamente.',
            'hora_ordinaria' => $this->horaPayload($horaOrdinaria->refresh()),
        ]);
    }

    public function destroy(Empleado $empleado, EmpleadoHoraOrdinaria $horaOrdinaria): JsonResponse
    {
        if ($horaOrdinaria->empleado_codigo !== $empleado->codigo) {
            abort(404);
        }

        $horaOrdinaria->delete();

        return response()->json([
            'message' => 'Hora ordinaria eliminada correctamente.',
        ]);
    }

    private function resumenPayload(int $minutosCajones, int $minutosOrdinarios): array
    {
        $total = $minutosCajones + $minutosOrdinarios;
        $faltante = max($this->metaDiariaMinutos - $total, 0);

        return [
            'meta_minutos' => $this->metaDiariaMinutos,
            'meta_texto' => VinetaRegistro::minutosATiempoTexto($this->metaDiariaMinutos),
            'minutos_cajones' => $minutosCajones,
            'tiempo_cajones_texto' => VinetaRegistro::minutosATiempoTexto($minutosCajones),
            'minutos_ordinarios' => $minutosOrdinarios,
            'tiempo_ordinario_texto' => VinetaRegistro::minutosATiempoTexto($minutosOrdinarios),
            'total_minutos' => $total,
            'total_texto' => VinetaRegistro::minutosATiempoTexto($total),
            'faltante_minutos' => $faltante,
            'faltante_texto' => VinetaRegistro::minutosATiempoTexto($faltante),
            'completado' => $total >= $this->metaDiariaMinutos,
            'porcentaje' => $this->metaDiariaMinutos > 0
                ? min(round(($total / $this->metaDiariaMinutos) * 100, 1), 100)
                : 0,
        ];
    }

    private function cajonPayload(VinetaRegistro $registro): array
    {
        $porHora = $registro->esPorHoraOrdinario();

        return [
            'id' => $registro->id,
            'vineta' => $registro->vineta_api_id ? 'ID ' . $registro->vineta_api_id : $registro->codigo_vineta,
            'actividad' => $registro->actividad_nombre,
            'producto' => $registro->producto_nombre,
            'cantidad_puros' => $registro->cantidad_puros,
            'modo_registro' => $registro->modoRegistro(),
            'por_hora' => $porHora,
            'minutos' => $porHora ? null : (int) ($registro->minutos_trabajados ?? 0),
            'tiempo_texto' => $registro->tiempoTrabajadoReporteTexto(),
            'registrado_en_texto' => $registro->fechaHoraRegistroTexto(),
        ];
    }

    private function horaPayload(EmpleadoHoraOrdinaria $hora): array
    {
        return [
            'id' => $hora->id,
            'fecha' => $hora->fecha?->format('Y-m-d'),
            'minutos' => $hora->minutos,
            'tiempo_texto' => VinetaRegistro::minutosATiempoTexto((int) $hora->minutos),
            'observacion' => $hora->observacion,
            'registrado_por' => $hora->registrado_por_nombre,
            'created_at_texto' => $hora->created_at?->timezone('America/Tegucigalpa')->format('d/m/Y h:i A'),
        ];
    }

    private function empleadoPayload(Empleado $empleado): array
    {
        return [
            'id' => $empleado->id,
            'codigo' => $empleado->codigo,
            'nombre' => $empleado->nombre,
            'cargo' => $empleado->cargo,
            'area' => $empleado->area,
            'activo' => (bool) $empleado->activo,
        ];
    }
}
