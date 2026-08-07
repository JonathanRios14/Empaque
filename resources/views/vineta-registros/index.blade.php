<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas registradas | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $sortLink = function ($campo) {
        $ordenActual = request('orden', 'fecha_registro');
        $direccionActual = request('direccion', 'desc');

        $nuevaDireccion = ($ordenActual === $campo && $direccionActual === 'asc') ? 'desc' : 'asc';

        return route('vineta-registros.index', array_merge(request()->query(), [
            'orden' => $campo,
            'direccion' => $nuevaDireccion,
            'page' => null,
        ]));
    };

    $sortIcon = function ($campo) {
        $ordenActual = request('orden', 'fecha_registro');
        $direccionActual = request('direccion', 'desc');

        if ($ordenActual !== $campo) {
            return '↕';
        }

        return $direccionActual === 'asc' ? '↑' : '↓';
    };

    $hasHorasOrdinarias = $hasHorasOrdinarias ?? false;
    $emptyColspan = ($hasMinutosTrabajados ?? false) ? 14 : 13;
    $editDefaultSubtitle = ($hasMinutosTrabajados ?? false)
        ? 'Actualiza fecha, hora, cantidad, tiempo y empleado.'
        : 'Actualiza fecha, hora, cantidad y empleado.';
@endphp

<body class="vinetas-page min-h-screen theme-bg antialiased">
    <div
        x-data="{
            sidebarOpen: false,
            catalogos: false,
            seguridad: false,
            produccion: true
        }"
        class="min-h-screen flex theme-bg">

        @include('layouts.sidebar')

        <div class="flex-1 min-w-0 flex flex-col">
            @include('layouts.topbar')

            <main class="flex-1 min-w-0">
                <section class="app-content-compact">
                    <div class="w-full max-w-none space-y-3">

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="section-title-icon vinetas-header-icon w-9 h-9 rounded-xl flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 0 1 2-2Z" />
                                            </svg>
                                        </div>

                                        <div class="min-w-0">
                                            <h1 class="theme-title text-lg sm:text-xl font-bold leading-tight">
                                                Viñetas registradas
                                            </h1>

                                            <p class="theme-text text-xs sm:text-sm mt-0.5 truncate">
                                                Registros guardados desde el móvil y horas ordinarias para seguimiento y planilla.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 min-w-full xl:min-w-[34rem]">
                                    <div class="theme-badge rounded-2xl border px-3 py-2">
                                        <p class="theme-text text-[11px] font-semibold">Registros</p>
                                        <p class="theme-title text-lg font-black">{{ number_format($totales['registros']) }}</p>
                                    </div>

                                    <div class="theme-badge rounded-2xl border px-3 py-2">
                                        <p class="theme-text text-[11px] font-semibold">Puros</p>
                                        <p class="theme-title text-lg font-black">{{ number_format($totales['puros']) }}</p>
                                    </div>

                                    <div class="theme-badge rounded-2xl border px-3 py-2">
                                        <p class="theme-text text-[11px] font-semibold">Cajones</p>
                                        <p class="theme-title text-lg font-black">{{ number_format($totales['cajones']) }}</p>
                                    </div>

                                    <div class="theme-badge rounded-2xl border px-3 py-2">
                                        <p class="theme-text text-[11px] font-semibold">Total act.</p>
                                        <p class="theme-title text-lg font-black">{{ number_format($totales['actividades']) }}</p>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                            <form method="GET"
                                  action="{{ route('vineta-registros.index') }}"
                                  class="vineta-registros-filter-form grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-7 gap-2 items-end">

                                <div class="xl:col-span-2">
                                    <label class="theme-text block text-xs font-semibold mb-1">Buscar</label>
                                    <input type="text"
                                           name="buscar"
                                           value="{{ request('buscar') }}"
                                           placeholder="Viñeta, producto, empleado, observación..."
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Empleado</label>
                                    <input type="text"
                                           name="empleado"
                                           value="{{ request('empleado') }}"
                                           placeholder="Código o nombre"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Grupo actividad</label>
                                    <select name="actividad_grupo"
                                            class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                        <option value="" @selected($actividadGrupo === '')>Todos</option>
                                        <option value="anillado" @selected($actividadGrupo === 'anillado')>Anillado</option>
                                        <option value="rezago" @selected($actividadGrupo === 'rezago')>Rezago</option>
                                        <option value="llenado" @selected($actividadGrupo === 'llenado')>Llenado</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Desde</label>
                                    <input type="date"
                                           name="fecha_desde"
                                           value="{{ request('fecha_desde') }}"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Hasta</label>
                                    <input type="date"
                                           name="fecha_hasta"
                                           value="{{ request('fecha_hasta') }}"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Estado</label>
                                    <select name="estado"
                                            class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                        <option value="activo" @selected($estado === 'activo')>Activos</option>
                                        <option value="anulado" @selected($estado === 'anulado')>Anulados</option>
                                        <option value="todos" @selected($estado === 'todos')>Todos</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-2 xl:col-span-7 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 pt-1">
                                    <a href="{{ route('vineta-registros.index') }}"
                                       class="theme-button-secondary inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white text-[#0b1220] text-sm font-bold border theme-border hover:bg-[#f1f5f9] transition">
                                        Limpiar
                                    </a>

                                    <a href="{{ route('vineta-registros.export', request()->except('page')) }}"
                                       class="theme-button-secondary inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white text-[#5b3a1e] text-sm font-black border theme-border hover:bg-[#f3efe7] transition">
                                        Exportar Excel
                                    </a>

                                    <a href="{{ route('vineta-registros.reporte-semanal', request()->except('page')) }}"
                                       class="theme-button-secondary inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-white text-[#0f766e] text-sm font-black border theme-border hover:bg-[#ecfdf5] transition">
                                        Reporte semanal
                                    </a>

                                    @if ($hasHorasOrdinarias)
                                        <button type="button"
                                                id="createHoraOrdinariaOpen"
                                                class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-[#0f172a] text-white text-sm font-black hover:bg-[#1e293b] transition">
                                            Agregar hora ordinaria
                                        </button>
                                    @endif

                                    <button type="submit"
                                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-[#0f172a] text-white text-sm font-black hover:bg-[#1e293b] transition">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible">
                            <div id="vinetaRegistrosTableInner" class="productos-table-inner relative">
                                <div class="productos-table-scroll catalogo-table-scroll vinetas-table-scroll">
                                    <table class="vinetas-table w-full text-sm">
                                        <thead class="theme-table-head productos-sticky-head bg-[#eff6ff] text-[#0f172a]">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                                                <a href="{{ $sortLink('fecha_registro') }}"
                                                   class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                    Fecha
                                                    <span class="text-xs">{{ $sortIcon('fecha_registro') }}</span>
                                                </a>
                                            </th>

                                            <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                                                <a href="{{ $sortLink('vineta_api_id') }}"
                                                   class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                    Viñeta
                                                    <span class="text-xs">{{ $sortIcon('vineta_api_id') }}</span>
                                                </a>
                                            </th>

                                            <th class="px-4 py-3 text-left font-bold min-w-[240px]">
                                                Producto
                                            </th>

                                            <th class="px-4 py-3 text-left font-bold min-w-[180px]">
                                                <a href="{{ $sortLink('actividad_nombre') }}"
                                                   class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                    Actividad
                                                    <span class="text-xs">{{ $sortIcon('actividad_nombre') }}</span>
                                                </a>
                                            </th>

                                            <th class="px-4 py-3 text-left font-bold min-w-[190px]">
                                                <a href="{{ $sortLink('empleado_nombre') }}"
                                                   class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                    Empleado
                                                    <span class="text-xs">{{ $sortIcon('empleado_nombre') }}</span>
                                                </a>
                                            </th>

                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                                <a href="{{ $sortLink('cantidad_puros') }}"
                                                   class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                    Puros
                                                    <span class="text-xs">{{ $sortIcon('cantidad_puros') }}</span>
                                                </a>
                                            </th>

                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                                <a href="{{ $sortLink('cantidad_cajones') }}"
                                                   class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                    Cajones
                                                    <span class="text-xs">{{ $sortIcon('cantidad_cajones') }}</span>
                                                </a>
                                            </th>

                                            @if ($hasMinutosTrabajados)
                                                <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                                    <a href="{{ $sortLink('minutos_trabajados') }}"
                                                       class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                        Tiempo
                                                        <span class="text-xs">{{ $sortIcon('minutos_trabajados') }}</span>
                                                    </a>
                                                </th>
                                            @endif

                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                                <a href="{{ $sortLink('cantidad_actividades') }}"
                                                   class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                    Cant. act.
                                                    <span class="text-xs">{{ $sortIcon('cantidad_actividades') }}</span>
                                                </a>
                                            </th>

                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                                Total act.
                                            </th>

                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                                <a href="{{ $sortLink('precio_mo') }}"
                                                   class="inline-flex items-center gap-2 hover:text-[#2563eb]">
                                                    Precio
                                                    <span class="text-xs">{{ $sortIcon('precio_mo') }}</span>
                                                </a>
                                            </th>

                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                                Total
                                            </th>

                                            <th class="px-4 py-3 text-left font-bold whitespace-nowrap">
                                                Estado
                                            </th>

                                            <th class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                                Acciones
                                            </th>
                                        </tr>
                                        </thead>

                                        <tbody class="divide-y theme-divide">
                                        @forelse ($registros as $registro)
                                            @php
                                                $isHoraOrdinaria = ($registro->reporte_tipo ?? 'vineta') === 'hora_ordinaria';
                                                $isRegistroPorHora = ! $isHoraOrdinaria && method_exists($registro, 'esPorHoraOrdinario') && $registro->esPorHoraOrdinario();
                                                $horaOrdinariaMinutos = $isHoraOrdinaria ? (int) ($registro->minutos ?? 0) : 0;
                                                $horaOrdinariaHoras = intdiv($horaOrdinariaMinutos, 60);
                                                $horaOrdinariaResto = $horaOrdinariaMinutos % 60;
                                            @endphp
                                            <tr class="vinetas-table-row transition {{ ($isHoraOrdinaria || $isRegistroPorHora) ? 'vinetas-row-no-hover' : 'theme-row' }}">
                                                <td class="px-4 py-3 whitespace-nowrap theme-text">
                                                    {{ $registro->fechaHoraRegistroTexto() }}
                                                </td>

                                                 <td class="px-4 py-3 whitespace-nowrap">
                                                     @if ($isHoraOrdinaria)
                                                         <span class="theme-badge inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border">
                                                             Ordinaria
                                                         </span>
                                                      @else
                                                        <span class="theme-badge vinetas-id-badge inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border">
                                                            ID {{ $registro->vineta_api_id ?? $registro->vineta_id }}
                                                        </span>
                                                        @if ($isRegistroPorHora)
                                                            <span class="theme-badge mt-1 inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-black border">
                                                                Por hora
                                                            </span>
                                                        @endif
                                                      @endif
                                                </td>

                                                <td class="px-4 py-3 min-w-[240px]">
                                                     <p class="theme-title font-semibold leading-tight">
                                                         {{ $registro->producto_nombre ?? 'Sin producto' }}
                                                     </p>

                                                     @if ($isHoraOrdinaria)
                                                         <p class="theme-text text-xs mt-0.5">
                                                             {{ $registro->observacion }}
                                                         </p>
                                                     @else
                                                         <p class="theme-text text-xs mt-0.5">
                                                             Código: {{ $registro->producto_codigo ?? 'N/A' }}
                                                         </p>

                                                         <p class="theme-text text-[11px] opacity-80">
                                                             Item: {{ $registro->producto_item ?? 'N/A' }}
                                                         </p>
                                                     @endif
                                                </td>

                                                <td class="px-4 py-3 min-w-[180px]">
                                                    <p class="theme-title font-semibold leading-tight">
                                                        {{ $registro->actividad_nombre }}
                                                    </p>

                                                      <p class="theme-text text-xs mt-0.5">
                                                          @if ($isHoraOrdinaria)
                                                              Registro manual
                                                          @elseif ($isRegistroPorHora)
                                                              Por hora ordinario
                                                          @else
                                                              {{ $registro->actividad_codigo ?? $registro->actividad_tipo_empaque ?? 'N/A' }}
                                                          @endif
                                                      </p>
                                                </td>

                                                <td class="px-4 py-3 min-w-[190px]">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-xl bg-[#0f172a] text-white flex items-center justify-center text-xs font-bold shrink-0">
                                                            {{ mb_substr($registro->empleado_nombre, 0, 1) }}
                                                        </div>

                                                        <div class="min-w-0">
                                                            <p class="theme-title font-semibold leading-tight truncate">
                                                                {{ $registro->empleado_nombre }}
                                                            </p>

                                                            <p class="theme-text text-xs mt-0.5">
                                                                {{ $registro->empleado_codigo }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 text-right theme-title font-black whitespace-nowrap">
                                                    {{ number_format($registro->cantidad_puros) }}
                                                </td>

                                                <td class="px-4 py-3 text-right theme-title font-black whitespace-nowrap">
                                                    {{ number_format($registro->cantidad_cajones) }}
                                                </td>

                                                @if ($hasMinutosTrabajados)
                                                    <td class="px-4 py-3 text-right theme-title font-black whitespace-nowrap">
                                                        {{ method_exists($registro, 'tiempoTrabajadoReporteTexto') ? $registro->tiempoTrabajadoReporteTexto() : $registro->tiempoTrabajadoTexto() }}
                                                    </td>
                                                @endif

                                                <td class="px-4 py-3 text-right theme-title font-black whitespace-nowrap">
                                                    {{ number_format($registro->cantidadActividadesValor()) }}
                                                </td>

                                                <td class="px-4 py-3 text-right theme-title font-black whitespace-nowrap">
                                                    {{ number_format($registro->total_actividades) }}
                                                </td>

                                                <td class="px-4 py-3 text-right theme-title font-semibold whitespace-nowrap">
                                                    {{ number_format((float) ($registro->precio_mo ?? 0), 4) }}
                                                </td>

                                                <td class="px-4 py-3 text-right theme-title font-black whitespace-nowrap">
                                                    {{ number_format($registro->total_mo, 2) }}
                                                </td>

                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <span class="theme-badge vinetas-status-badge inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border">
                                                        {{ ucfirst($registro->estado) }}
                                                    </span>
                                                </td>

                                                <td class="px-3 py-3 text-right whitespace-nowrap">
                                                    @if ($isHoraOrdinaria)
                                                        <div class="vineta-actions inline-flex items-center justify-end overflow-hidden rounded-xl border theme-border">
                                                            <button type="button"
                                                                    title="Editar hora ordinaria"
                                                                    aria-label="Editar hora ordinaria"
                                                                    class="hora-ordinaria-edit vineta-action-btn vineta-action-btn-secondary"
                                                                    data-action="{{ route('vineta-registros.horas-ordinarias.update', $registro) }}"
                                                                    data-fecha="{{ optional($registro->fecha)->format('Y-m-d') }}"
                                                                    data-horas="{{ $horaOrdinariaHoras }}"
                                                                    data-minutos="{{ $horaOrdinariaResto }}"
                                                                    data-empleado-codigo="{{ $registro->empleado_codigo }}"
                                                                    data-empleado-nombre="{{ $registro->empleado_nombre }}"
                                                                    data-observacion="{{ $registro->observacion }}">
                                                                Editar
                                                            </button>

                                                            <form method="POST"
                                                                  action="{{ route('vineta-registros.horas-ordinarias.destroy', $registro) }}"
                                                                  onsubmit="return confirm('Eliminar esta hora ordinaria?');"
                                                                  class="inline-flex">
                                                                @csrf
                                                                @method('DELETE')

                                                                <button type="submit"
                                                                        title="Eliminar hora ordinaria"
                                                                        aria-label="Eliminar hora ordinaria"
                                                                        class="vineta-action-btn vineta-action-btn-danger">
                                                                    Eliminar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @else
                                                        <div class="vineta-actions inline-flex items-center justify-end overflow-hidden rounded-xl border theme-border">
                                                            <button type="button"
                                                                    title="Ver seguimiento"
                                                                    aria-label="Ver seguimiento"
                                                                    class="vineta-registro-seguimiento vineta-action-btn vineta-action-btn-primary"
                                                                    data-vineta-id="{{ $registro->vineta_id }}">
                                                                Seguimiento
                                                            </button>

                                                            <button type="button"
                                                                    title="Editar registro"
                                                                    aria-label="Editar registro"
                                                                    class="vineta-registro-edit vineta-action-btn vineta-action-btn-secondary"
                                                                    data-action="{{ route('vineta-registros.update', $registro) }}"
                                                                    data-fecha="{{ optional($registro->fecha_registro)->format('Y-m-d') }}"
                                                                    data-hora="{{ substr((string) $registro->hora_registro, 0, 5) }}"
                                                                    data-cantidad="{{ $registro->cantidad_puros }}"
                                                                    data-minutos="{{ $registro->minutos_trabajados ?? '' }}"
                                                                    data-por-hora="{{ $isRegistroPorHora ? '1' : '0' }}"
                                                                    data-empleado-codigo="{{ $registro->empleado_codigo }}"
                                                                    data-empleado-nombre="{{ $registro->empleado_nombre }}"
                                                                    data-vineta="ID {{ $registro->vineta_api_id ?? $registro->vineta_id }}"
                                                                    data-actividad="{{ $registro->actividad_nombre }}">
                                                                Editar
                                                            </button>

                                                            <form method="POST"
                                                                  action="{{ route('vineta-registros.destroy', $registro) }}"
                                                                  onsubmit="return confirm('Eliminar este registro de prueba?');"
                                                                  class="inline-flex">
                                                                @csrf
                                                                @method('DELETE')

                                                                <button type="submit"
                                                                        title="Eliminar registro"
                                                                        aria-label="Eliminar registro"
                                                                        class="vineta-action-btn vineta-action-btn-danger">
                                                                    Eliminar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $emptyColspan }}" class="px-4 py-12 text-center">
                                                    <p class="theme-title font-bold">
                                                        No hay viñetas registradas
                                                    </p>

                                                    <p class="theme-text text-sm mt-1">
                                                        Guarda registros desde el móvil o cambia los filtros de búsqueda.
                                                    </p>
                                                </td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="theme-soft px-4 py-3 border-t border-[#e5d8c7] theme-border bg-[#fbf8f3] flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                        <p class="theme-text text-sm text-gray-500">
                                            Mostrando
                                            <span class="theme-title font-semibold text-[#3b2818]">{{ $registros->firstItem() ?? 0 }}</span>
                                            a
                                            <span class="theme-title font-semibold text-[#3b2818]">{{ $registros->lastItem() ?? 0 }}</span>
                                            de
                                            <span class="theme-title font-semibold text-[#3b2818]">{{ $registros->total() }}</span>
                                            registro(s)
                                        </p>

                                        <form method="GET"
                                              action="{{ route('vineta-registros.index') }}"
                                              class="per-page-control">
                                            @foreach(request()->except('per_page', 'page') as $key => $value)
                                                @if(is_array($value))
                                                    @foreach($value as $item)
                                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach

                                            <label class="per-page-label">
                                                Mostrar:
                                            </label>

                                            <select name="per_page"
                                                    onchange="this.form.requestSubmit()"
                                                    class="per-page-select">
                                                <option value="10" @selected(request('per_page') == 10)>10</option>
                                                <option value="25" @selected(request('per_page', 25) == 25)>25</option>
                                                <option value="50" @selected(request('per_page') == 50)>50</option>
                                                <option value="100" @selected(request('per_page') == 100)>100</option>
                                            </select>
                                        </form>
                                    </div>

                                    <div class="pagination-cafe">
                                        {{ $registros->onEachSide(1)->links('pagination.cafe') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="seguimientoVinetaModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card flex max-h-[94vh] w-[92vw] max-w-6xl flex-col overflow-hidden rounded-3xl border theme-border bg-white shadow-2xl">
            <div class="border-b theme-border px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="theme-text text-xs font-black uppercase tracking-wide">
                            Seguimiento del cajón
                        </p>

                        <h2 id="seguimientoVinetaTitle" class="theme-title mt-1 text-xl font-black">
                            Viñeta
                        </h2>

                        <div id="seguimientoVinetaSubtitle" class="vineta-modal-info mt-3">
                            Actividades realizadas a esta viñeta.
                        </div>
                    </div>

                    <button type="button"
                            id="seguimientoVinetaClose"
                            class="theme-button-secondary inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]">
                        ×
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div class="theme-badge rounded-2xl border px-3 py-2">
                        <p class="theme-text text-[11px] font-semibold">Movimientos</p>
                        <p id="seguimientoVinetaMovimientos" class="theme-title text-lg font-black">0</p>
                    </div>

                    <div class="theme-badge rounded-2xl border px-3 py-2">
                        <p class="theme-text text-[11px] font-semibold">Activos</p>
                        <p id="seguimientoVinetaActivos" class="theme-title text-lg font-black">0</p>
                    </div>

                    <div class="theme-badge rounded-2xl border px-3 py-2">
                        <p class="theme-text text-[11px] font-semibold">Puros cajón</p>
                        <p id="seguimientoVinetaPuros" class="theme-title text-lg font-black">0</p>
                    </div>

                </div>
            </div>

            <div class="overflow-y-auto px-5 py-5">
                <div id="seguimientoVinetaTimeline" class="space-y-0"></div>
            </div>
        </div>
    </div>

    <div id="editVinetaRegistroModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-xl rounded-3xl border theme-border bg-white shadow-2xl overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                <div>
                    <p class="theme-text text-xs font-black uppercase tracking-wide">
                        Editar registro
                    </p>

                    <h2 id="editRegistroTitle" class="theme-title mt-1 text-xl font-black">
                        Viñeta registrada
                    </h2>

                     <p id="editRegistroSubtitle" class="theme-text mt-1 text-sm">
                         Actualiza fecha, hora, cantidad{{ $hasMinutosTrabajados ? ', tiempo' : '' }} y empleado.
                     </p>
                </div>

                <button type="button"
                        id="editVinetaRegistroClose"
                        class="theme-button-secondary inline-flex h-10 w-10 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]">
                    ×
                </button>
            </div>

            <form id="editVinetaRegistroForm" method="POST" action="" class="px-5 py-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="edit_fecha_registro" class="theme-text mb-1 block text-xs font-bold">
                            Fecha
                        </label>

                        <input id="edit_fecha_registro"
                               type="date"
                               name="fecha_registro"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_hora_registro" class="theme-text mb-1 block text-xs font-bold">
                            Hora
                        </label>

                        <input id="edit_hora_registro"
                               type="time"
                               name="hora_registro"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_cantidad_puros" class="theme-text mb-1 block text-xs font-bold">
                            Cantidad puros
                        </label>

                        <input id="edit_cantidad_puros"
                               type="number"
                               name="cantidad_puros"
                               min="1"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    @if ($hasMinutosTrabajados)
                        <div id="edit_minutos_trabajados_group">
                            <label for="edit_minutos_trabajados" class="theme-text mb-1 block text-xs font-bold">
                                Minutos trabajados
                            </label>

                            <input id="edit_minutos_trabajados"
                                   type="number"
                                   name="minutos_trabajados"
                                   min="1"
                                   max="570"
                                   required
                                   class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">

                            <p class="theme-text mt-1 text-[11px] font-semibold">
                                Meta diaria: 570 min (9 h 30 min).
                            </p>
                        </div>
                    @endif

                    <div>
                        <label for="edit_empleado_codigo" class="theme-text mb-1 block text-xs font-bold">
                            Código empleado
                        </label>

                        <input id="edit_empleado_codigo"
                               type="text"
                               name="empleado_codigo"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                </div>

                <div class="theme-soft mt-4 rounded-2xl border theme-border px-4 py-3">
                    <p class="theme-text text-xs font-bold">
                        Empleado seleccionado
                    </p>

                    <p id="editEmpleadoNombre" class="theme-title mt-1 font-black">
                        N/A
                    </p>

                    <p id="editEmpleadoEstado" class="theme-text mt-1 text-xs font-semibold">
                        Ingresa un código para validar el empleado.
                    </p>
                </div>

                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button"
                            id="editVinetaRegistroCancel"
                            class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#0f172a] px-5 py-3 text-sm font-black text-white transition hover:bg-[#1e293b]">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($hasHorasOrdinarias)
        <div id="createHoraOrdinariaModal"
             class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
            <div class="theme-card w-full max-w-xl rounded-3xl border theme-border bg-white shadow-2xl overflow-hidden">
                <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                    <div>
                        <p class="theme-text text-xs font-black uppercase tracking-wide">
                            Hora ordinaria
                        </p>

                        <h2 class="theme-title mt-1 text-xl font-black">
                            Agregar hora ordinaria
                        </h2>

                        <p class="theme-text mt-1 text-sm">
                            Registra tiempo manual por empleado para seguimiento y reportes.
                        </p>
                    </div>

                    <button type="button"
                            id="createHoraOrdinariaClose"
                            class="theme-button-secondary inline-flex h-10 w-10 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]">
                        ×
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('vineta-registros.horas-ordinarias.store') }}"
                      class="px-5 py-5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="create_hora_ord_empleado_codigo" class="theme-text mb-1 block text-xs font-bold">
                                Código empleado
                            </label>

                            <input id="create_hora_ord_empleado_codigo"
                                   type="text"
                                   name="empleado_codigo"
                                   value="{{ old('empleado_codigo') }}"
                                   placeholder="Código"
                                   required
                                   class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                        </div>

                        <div>
                            <label for="create_hora_ord_fecha" class="theme-text mb-1 block text-xs font-bold">
                                Fecha
                            </label>

                            <input id="create_hora_ord_fecha"
                                   type="date"
                                   name="fecha"
                                   value="{{ old('fecha', now('America/Tegucigalpa')->toDateString()) }}"
                                   required
                                   class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                        </div>

                        <div>
                            <label for="create_hora_ord_horas" class="theme-text mb-1 block text-xs font-bold">
                                Horas
                            </label>

                            <input id="create_hora_ord_horas"
                                   type="number"
                                   name="horas"
                                   value="{{ old('horas') }}"
                                   min="0"
                                   max="9"
                                   placeholder="0"
                                   class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                        </div>

                        <div>
                            <label for="create_hora_ord_minutos" class="theme-text mb-1 block text-xs font-bold">
                                Minutos
                            </label>

                            <input id="create_hora_ord_minutos"
                                   type="number"
                                   name="minutos"
                                   value="{{ old('minutos') }}"
                                   min="0"
                                   max="59"
                                   placeholder="0"
                                   class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="create_hora_ord_observacion" class="theme-text mb-1 block text-xs font-bold">
                                Observación
                            </label>

                            <textarea id="create_hora_ord_observacion"
                                      name="observacion"
                                      rows="4"
                                      placeholder="Motivo o detalle"
                                      required
                                      class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">{{ old('observacion') }}</textarea>
                        </div>
                    </div>

                    <p class="theme-text mt-3 text-[11px] font-semibold">
                        Tiempo máximo por registro: 570 min (9 h 30 min).
                    </p>

                    <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <button type="button"
                                id="createHoraOrdinariaCancel"
                                class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">
                            Cancelar
                        </button>

                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-[#0f172a] px-5 py-3 text-sm font-black text-white transition hover:bg-[#1e293b]">
                            Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div id="editHoraOrdinariaModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-xl rounded-3xl border theme-border bg-white shadow-2xl overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                <div>
                    <p class="theme-text text-xs font-black uppercase tracking-wide">
                        Editar hora ordinaria
                    </p>

                    <h2 id="editHoraOrdinariaTitle" class="theme-title mt-1 text-xl font-black">
                        Registro manual
                    </h2>

                    <p class="theme-text mt-1 text-sm">
                        Actualiza empleado, fecha, tiempo y observación.
                    </p>
                </div>

                <button type="button"
                        id="editHoraOrdinariaClose"
                        class="theme-button-secondary inline-flex h-10 w-10 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]">
                    ×
                </button>
            </div>

            <form id="editHoraOrdinariaForm" method="POST" action="" class="px-5 py-5">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="edit_hora_ord_empleado_codigo" class="theme-text mb-1 block text-xs font-bold">
                            Código empleado
                        </label>

                        <input id="edit_hora_ord_empleado_codigo"
                               type="text"
                               name="empleado_codigo"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_hora_ord_fecha" class="theme-text mb-1 block text-xs font-bold">
                            Fecha
                        </label>

                        <input id="edit_hora_ord_fecha"
                               type="date"
                               name="fecha"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_hora_ord_horas" class="theme-text mb-1 block text-xs font-bold">
                            Horas
                        </label>

                        <input id="edit_hora_ord_horas"
                               type="number"
                               name="horas"
                               min="0"
                               max="9"
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div>
                        <label for="edit_hora_ord_minutos" class="theme-text mb-1 block text-xs font-bold">
                            Minutos
                        </label>

                        <input id="edit_hora_ord_minutos"
                               type="number"
                               name="minutos"
                               min="0"
                               max="59"
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="edit_hora_ord_observacion" class="theme-text mb-1 block text-xs font-bold">
                            Observación
                        </label>

                        <textarea id="edit_hora_ord_observacion"
                                  name="observacion"
                                  rows="4"
                                  required
                                  class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20"></textarea>
                    </div>
                </div>

                <p class="theme-text mt-3 text-[11px] font-semibold">
                    Tiempo máximo por registro: 570 min (9 h 30 min).
                </p>

                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button"
                            id="editHoraOrdinariaCancel"
                            class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">
                        Cancelar
                    </button>

                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#0f172a] px-5 py-3 text-sm font-black text-white transition hover:bg-[#1e293b]">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('layouts.flash')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('seguimientoVinetaModal');
            const title = document.getElementById('seguimientoVinetaTitle');
            const subtitle = document.getElementById('seguimientoVinetaSubtitle');
            const movimientos = document.getElementById('seguimientoVinetaMovimientos');
            const activos = document.getElementById('seguimientoVinetaActivos');
            const puros = document.getElementById('seguimientoVinetaPuros');
            const timelineContainer = document.getElementById('seguimientoVinetaTimeline');
            const timelines = @json($seguimientoTimelineMap);
            const summaries = @json($seguimientoResumenMap);
            const numberFormat = new Intl.NumberFormat('es-HN');

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const renderTimeline = (items) => {
                if (!items.length) {
                    timelineContainer.innerHTML = `
                        <div class="theme-soft rounded-2xl border theme-border px-4 py-8 text-center">
                            <p class="theme-title font-black">Sin movimientos</p>
                            <p class="theme-text mt-1 text-sm">Todavía no hay actividades registradas para esta viñeta.</p>
                        </div>
                    `;
                    return;
                }

                timelineContainer.innerHTML = `<div class="vineta-delivery-scroll"><div class="vineta-delivery-track" style="--vineta-steps: ${items.length}">${items.map((item, index) => {
                    const isLast = index === items.length - 1;
                    const isAnulado = item.estado === 'anulado';
                    const stateClass = isAnulado ? 'is-anulado' : (isLast ? 'is-current' : 'is-complete');
                    const statusText = isAnulado ? 'Anulado' : (isLast ? 'Ultimo movimiento' : 'Completado');
                    const paso = index + 1;

                    return `
                        <div class="vineta-delivery-step ${stateClass} ${isLast ? 'is-last' : ''}">
                            <div class="vineta-delivery-rail">
                                <span class="vineta-delivery-dot">${isAnulado ? '!' : paso}</span>
                                ${isLast ? '' : '<span class="vineta-delivery-line" aria-hidden="true"></span>'}
                            </div>

                            <div class="vineta-delivery-card">
                                <div class="vineta-delivery-card-head">
                                    <div class="min-w-0">
                                        <div class="vineta-delivery-kicker">
                                            <span>Paso ${paso}</span>
                                            <span class="vineta-delivery-status ${stateClass}">
                                                ${statusText}
                                            </span>
                                        </div>

                                        <p class="vineta-delivery-title">
                                            ${escapeHtml(item.actividad || 'Actividad sin nombre')}
                                        </p>

                                        <p class="vineta-delivery-date">
                                            ${escapeHtml(item.fecha || 'N/A')}
                                        </p>
                                    </div>

                                    <div class="vineta-delivery-worker">
                                        <span class="vineta-delivery-worker-avatar">
                                            ${escapeHtml(String(item.empleado || '?').slice(0, 1).toUpperCase())}
                                        </span>

                                        <div class="min-w-0">
                                            <p>${escapeHtml(item.empleado || 'N/A')}</p>
                                            <span>${escapeHtml(item.empleado_codigo || 'N/A')}</span>
                                        </div>
                                    </div>
                                </div>

                                ${item.motivo_anulacion ? `<p class="vineta-timeline-alert mt-3 rounded-xl border px-3 py-2 text-xs font-semibold">Anulado: ${escapeHtml(item.motivo_anulacion)}</p>` : ''}

                            </div>
                        </div>
                    `;
                }).join('')}</div></div>`;
            };

            document.querySelectorAll('.vineta-registro-seguimiento').forEach((button) => {
                button.addEventListener('click', () => {
                    const vinetaId = String(button.dataset.vinetaId || '');
                    const items = timelines[vinetaId] || [];
                    const summary = summaries[vinetaId] || {};

                    title.textContent = summary.vineta || 'Viñeta';
                    subtitle.innerHTML = [
                        ['Producto', summary.producto || 'Sin producto'],
                        ['Código', summary.producto_codigo || 'N/A'],
                        ['Item', summary.producto_item || 'N/A'],
                        ['Marca', summary.marca || 'N/A'],
                        ['Orden', summary.orden || 'N/A'],
                        ['Fecha viñeta', summary.vineta_fecha || 'N/A'],
                    ].map(([label, value]) => `
                        <span class="vineta-modal-info-chip">
                            <strong>${escapeHtml(label)}</strong>
                            <span>${escapeHtml(value)}</span>
                        </span>
                    `).join('');
                    movimientos.textContent = numberFormat.format(summary.movimientos || items.length || 0);
                    activos.textContent = numberFormat.format(summary.activos || 0);
                    puros.textContent = numberFormat.format(summary.puros || 0);
                    renderTimeline(items);

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            document.getElementById('seguimientoVinetaClose').addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('editVinetaRegistroModal');
            const form = document.getElementById('editVinetaRegistroForm');
            const title = document.getElementById('editRegistroTitle');
            const subtitle = document.getElementById('editRegistroSubtitle');
            const empleadoNombre = document.getElementById('editEmpleadoNombre');
            const empleadoEstado = document.getElementById('editEmpleadoEstado');
            const empleadoLookupUrl = @json(route('vineta-registros.empleado'));
            const editDefaultSubtitle = @json($editDefaultSubtitle);
            let empleadoLookupTimer = null;
            let empleadoLookupController = null;
            const fields = {
                 fecha: document.getElementById('edit_fecha_registro'),
                 hora: document.getElementById('edit_hora_registro'),
                 cantidad: document.getElementById('edit_cantidad_puros'),
                  minutos: document.getElementById('edit_minutos_trabajados'),
                  minutosGroup: document.getElementById('edit_minutos_trabajados_group'),
                 empleado: document.getElementById('edit_empleado_codigo'),
             };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const setEmpleadoStatus = (nombre, estado, type = 'neutral') => {
                empleadoNombre.textContent = nombre;
                empleadoEstado.textContent = estado;
                empleadoEstado.classList.remove('text-emerald-600', 'text-rose-600', 'text-amber-600');

                if (type === 'success') {
                    empleadoEstado.classList.add('text-emerald-600');
                }

                if (type === 'error') {
                    empleadoEstado.classList.add('text-rose-600');
                }

                if (type === 'warning') {
                    empleadoEstado.classList.add('text-amber-600');
                }
            };

            const lookupEmpleado = () => {
                const codigo = fields.empleado.value.trim();

                window.clearTimeout(empleadoLookupTimer);

                if (empleadoLookupController) {
                    empleadoLookupController.abort();
                }

                if (!codigo) {
                    setEmpleadoStatus('N/A', 'Ingresa un código para validar el empleado.');
                    return;
                }

                setEmpleadoStatus('Buscando...', 'Consultando empleado...', 'warning');

                empleadoLookupTimer = window.setTimeout(async () => {
                    empleadoLookupController = new AbortController();

                    try {
                        const url = new URL(empleadoLookupUrl, window.location.origin);
                        url.searchParams.set('codigo', codigo);

                        const response = await fetch(url, {
                            headers: {'Accept': 'application/json'},
                            signal: empleadoLookupController.signal,
                        });

                        const data = await response.json();

                        if (!response.ok || !data.employee) {
                            setEmpleadoStatus('No encontrado', 'No se encontró un empleado con ese código.', 'error');
                            return;
                        }

                        if (!data.employee.activo) {
                            setEmpleadoStatus(data.employee.nombre, 'Empleado inactivo.', 'error');
                            return;
                        }

                        setEmpleadoStatus(data.employee.nombre, `Código ${data.employee.codigo} validado.`, 'success');
                    } catch (error) {
                        if (error.name === 'AbortError') {
                            return;
                        }

                        setEmpleadoStatus('No validado', 'No se pudo consultar el empleado.', 'error');
                    }
                }, 280);
            };

            document.querySelectorAll('.vineta-registro-edit').forEach((button) => {
                button.addEventListener('click', () => {
                    const isPorHora = button.dataset.porHora === '1';

                    form.action = button.dataset.action;
                      fields.fecha.value = button.dataset.fecha || '';
                      fields.hora.value = button.dataset.hora || '';
                      fields.cantidad.value = button.dataset.cantidad || '';
                      if (fields.minutos) {
                          fields.minutos.value = isPorHora ? '' : (button.dataset.minutos || '');
                          fields.minutos.disabled = isPorHora;
                          fields.minutos.required = ! isPorHora;
                      }

                      if (fields.minutosGroup) {
                          fields.minutosGroup.classList.toggle('hidden', isPorHora);
                      }
                     fields.empleado.value = button.dataset.empleadoCodigo || '';
                    setEmpleadoStatus(
                        button.dataset.empleadoNombre || 'N/A',
                        button.dataset.empleadoCodigo ? `Código ${button.dataset.empleadoCodigo} validado.` : 'Ingresa un código para validar el empleado.',
                        button.dataset.empleadoCodigo ? 'success' : 'neutral'
                    );
                    title.textContent = button.dataset.vineta || 'Viñeta registrada';
                    subtitle.textContent = button.dataset.actividad || editDefaultSubtitle;

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            fields.empleado.addEventListener('input', lookupEmpleado);

            document.getElementById('editVinetaRegistroClose').addEventListener('click', closeModal);
            document.getElementById('editVinetaRegistroCancel').addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            @if ($errors->any())
                appSwal({
                    icon: 'error',
                    title: 'No se pudo guardar',
                    html: @json($errors->all()).join('<br>'),
                });
            @endif
        });
    </script>

    @if ($hasHorasOrdinarias)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('createHoraOrdinariaModal');
                const openButton = document.getElementById('createHoraOrdinariaOpen');

                if (!modal || !openButton) {
                    return;
                }

                const openModal = () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                };

                const closeModal = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                };

                openButton.addEventListener('click', openModal);
                document.getElementById('createHoraOrdinariaClose').addEventListener('click', closeModal);
                document.getElementById('createHoraOrdinariaCancel').addEventListener('click', closeModal);

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeModal();
                    }
                });
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('editHoraOrdinariaModal');
            const form = document.getElementById('editHoraOrdinariaForm');
            const title = document.getElementById('editHoraOrdinariaTitle');
            const fields = {
                empleado: document.getElementById('edit_hora_ord_empleado_codigo'),
                fecha: document.getElementById('edit_hora_ord_fecha'),
                horas: document.getElementById('edit_hora_ord_horas'),
                minutos: document.getElementById('edit_hora_ord_minutos'),
                observacion: document.getElementById('edit_hora_ord_observacion'),
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.querySelectorAll('.hora-ordinaria-edit').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.action || '';
                    fields.empleado.value = button.dataset.empleadoCodigo || '';
                    fields.fecha.value = button.dataset.fecha || '';
                    fields.horas.value = button.dataset.horas || '';
                    fields.minutos.value = button.dataset.minutos || '';
                    fields.observacion.value = button.dataset.observacion || '';
                    title.textContent = button.dataset.empleadoNombre || 'Registro manual';

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            document.getElementById('editHoraOrdinariaClose').addEventListener('click', closeModal);
            document.getElementById('editHoraOrdinariaCancel').addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>
