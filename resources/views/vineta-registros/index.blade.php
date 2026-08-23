<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viñetas registradas | Sistema de Empaque</title>

    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $hasHorasOrdinarias = $hasHorasOrdinarias ?? false;
    $today = now('America/Tegucigalpa');
    $defaultExportStart = request('fecha_desde', $today->copy()->startOfWeek()->toDateString());
    $defaultExportEnd = request('fecha_hasta', $today->toDateString());
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

                        <div id="vinetaRegistrosSummaryContainer">
                            @include('vineta-registros.partials.resumen')
                        </div>

                        <div class="theme-card bg-white rounded-2xl border theme-border theme-shadow p-3 sm:p-4">
                            <form method="GET"
                                  action="{{ route('vineta-registros.index') }}"
                                  class="vineta-registros-filter-form vineta-registros-ajax-filter-form grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-9 gap-2 items-end">

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">ID viñeta</label>
                                    <input type="text"
                                           name="id_vineta"
                                           value="{{ request('id_vineta') }}"
                                           placeholder="ID o código"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Item</label>
                                    <input type="text"
                                           name="item"
                                           value="{{ request('item') }}"
                                           placeholder="Item"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Orden del sistema</label>
                                    <input type="text"
                                           name="orden_del_sistema"
                                           value="{{ request('orden_del_sistema') }}"
                                           placeholder="Orden sistema"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Orden del cliente</label>
                                    <input type="text"
                                           name="orden_cliente"
                                           value="{{ request('orden_cliente') }}"
                                           placeholder="Orden cliente"
                                           class="w-full rounded-xl border theme-border bg-white px-3 py-2 text-sm theme-title focus:ring-2 focus:ring-[#2563eb]/20 focus:border-[#2563eb] outline-none transition">
                                </div>

                                <div>
                                    <label class="theme-text block text-xs font-semibold mb-1">Código producto</label>
                                    <input type="text"
                                           name="codigo_producto"
                                           value="{{ request('codigo_producto') }}"
                                           placeholder="Código producto"
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

                                <div class="sm:col-span-2 xl:col-span-9 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 pt-1">
                                    <a href="{{ route('vineta-registros.index') }}"
                                       class="vineta-registros-ajax-clear gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-2.5 rounded-xl bg-white text-[#0b1220] text-sm font-bold border theme-border hover:bg-[#f1f5f9] transition">
                                        Limpiar
                                    </a>

                                    <button type="button"
                                            id="vinetaRegistrosExportOpen"
                                            class="gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-2.5 rounded-xl bg-white text-[#5b3a1e] text-sm font-black border theme-border hover:bg-[#f3efe7] transition">
                                        Exportar Excel
                                    </button>

                                    <button type="button"
                                            id="vinetaRegistrosWeeklyReportOpen"
                                            class="gooey-action theme-button-secondary inline-flex items-center justify-center px-3 py-2.5 rounded-xl bg-white text-[#0f766e] text-sm font-black border theme-border hover:bg-[#ecfdf5] transition">
                                        Reporte semanal
                                    </button>

                                    <button type="submit"
                                            class="gooey-action inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-[#0f172a] text-white text-sm font-black hover:bg-[#1e293b] transition">
                                        Filtrar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="productos-card theme-card bg-white rounded-2xl border theme-border theme-shadow overflow-visible">
                            <div id="vinetaRegistrosTableContainer">
                                @include('vineta-registros.partials.tabla')
                            </div>
                        </div>

                    </div>
                </section>
            </main>
        </div>
    </div>

    <div id="vinetaRegistrosExportModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-lg overflow-hidden rounded-3xl border theme-border bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                <div>
                    <p class="theme-text text-xs font-black uppercase tracking-wide">Exportar Excel</p>
                    <h2 class="theme-title mt-1 text-xl font-black">Selecciona el periodo</h2>
                    <p class="theme-text mt-1 text-sm">La primera hoja mostrará subtotales por empleado y producto; el detalle original se conservará en otra hoja.</p>
                </div>
                <button type="button"
                        data-vineta-registros-modal-close="vinetaRegistrosExportModal"
                        class="theme-button-secondary inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]"
                        aria-label="Cerrar exportación">×</button>
            </div>

            <form method="GET" action="{{ route('vineta-registros.export') }}" class="px-5 py-5">
                @foreach(request()->except(['page', 'fecha_desde', 'fecha_hasta']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="export_fecha_desde" class="theme-text mb-1 block text-xs font-bold">Desde</label>
                        <input id="export_fecha_desde"
                               type="date"
                               name="fecha_desde"
                               value="{{ $defaultExportStart }}"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                    <div>
                        <label for="export_fecha_hasta" class="theme-text mb-1 block text-xs font-bold">Hasta</label>
                        <input id="export_fecha_hasta"
                               type="date"
                               name="fecha_hasta"
                               value="{{ $defaultExportEnd }}"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button"
                            data-vineta-registros-modal-close="vinetaRegistrosExportModal"
                            class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">Cancelar</button>
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#5b3a1e] px-5 py-3 text-sm font-black text-white transition hover:bg-[#3b2818]">Descargar Excel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="vinetaRegistrosWeeklyReportModal"
         class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/55 px-4 py-6 backdrop-blur-sm">
        <div class="theme-card w-full max-w-lg overflow-hidden rounded-3xl border theme-border bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b theme-border px-5 py-4">
                <div>
                    <p class="theme-text text-xs font-black uppercase tracking-wide">Reporte semanal</p>
                    <h2 class="theme-title mt-1 text-xl font-black">Selecciona el periodo</h2>
                    <p class="theme-text mt-1 text-sm">El reporte incluirá únicamente los registros dentro de estas fechas.</p>
                </div>
                <button type="button"
                        data-vineta-registros-modal-close="vinetaRegistrosWeeklyReportModal"
                        class="theme-button-secondary inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border theme-border text-xl font-black transition hover:bg-[#f3efe7]"
                        aria-label="Cerrar reporte semanal">×</button>
            </div>

            <form method="GET" action="{{ route('vineta-registros.reporte-semanal') }}" class="px-5 py-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="weekly_fecha_desde" class="theme-text mb-1 block text-xs font-bold">Desde</label>
                        <input id="weekly_fecha_desde"
                               type="date"
                               name="fecha_desde"
                               value="{{ $defaultExportStart }}"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                    <div>
                        <label for="weekly_fecha_hasta" class="theme-text mb-1 block text-xs font-bold">Hasta</label>
                        <input id="weekly_fecha_hasta"
                               type="date"
                               name="fecha_hasta"
                               value="{{ $defaultExportEnd }}"
                               required
                               class="w-full rounded-2xl border theme-border bg-white px-4 py-3 text-sm theme-title outline-none transition focus:border-[#2563eb] focus:ring-2 focus:ring-[#2563eb]/20">
                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button"
                            data-vineta-registros-modal-close="vinetaRegistrosWeeklyReportModal"
                            class="theme-button-secondary inline-flex items-center justify-center rounded-2xl border theme-border bg-white px-4 py-3 text-sm font-bold transition hover:bg-[#f3efe7]">Cancelar</button>
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#5b3a1e] px-5 py-3 text-sm font-black text-white transition hover:bg-[#3b2818]">Generar reporte</button>
                </div>
            </form>
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
            const modals = {
                export: document.getElementById('vinetaRegistrosExportModal'),
                weekly: document.getElementById('vinetaRegistrosWeeklyReportModal'),
            };

            const openModal = (modal) => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };

            const closeModal = (modal) => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };

            document.getElementById('vinetaRegistrosExportOpen')?.addEventListener('click', () => openModal(modals.export));
            document.getElementById('vinetaRegistrosWeeklyReportOpen')?.addEventListener('click', () => openModal(modals.weekly));

            document.querySelectorAll('[data-vineta-registros-modal-close]').forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal(document.getElementById(button.dataset.vinetaRegistrosModalClose));
                });
            });

            Object.values(modals).forEach((modal) => {
                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    Object.values(modals).forEach(closeModal);
                }
            });
        });
    </script>

    <div id="vinetaRegistrosTableLoader"
         class="productos-table-loader hidden"
         role="status"
         aria-live="polite">
        <div class="productos-table-loader-card theme-card theme-shadow">
            <div class="productos-table-loader-icon"><span></span></div>
            <div class="text-left">
                <p class="theme-title text-sm font-bold leading-tight">Actualizando tabla</p>
                <p class="theme-text text-xs leading-tight mt-0.5">Cargando registros...</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getTableContainer = () => document.getElementById('vinetaRegistrosTableContainer');
            const getSummaryContainer = () => document.getElementById('vinetaRegistrosSummaryContainer');
            const getFilterForm = () => document.querySelector('.vineta-registros-ajax-filter-form');
            const getTableScroll = () => document.querySelector('#vinetaRegistrosTableContainer .vineta-registros-table-scroll');
            const getTopbarBottom = () => document.querySelector('.app-topbar')?.getBoundingClientRect().bottom || 0;
            let stickyHeaderClone = null;
            let floatingScroll = null;
            let boundFloatingScroll = null;
            let syncingFloatingScroll = false;
            let windowEventsBound = false;

            const removeStickyHeaderClone = () => {
                stickyHeaderClone?.remove();
                stickyHeaderClone = null;
            };

            const syncStickyHeaderClone = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');

                if (!scroll || !table || !header || !stickyHeaderClone) {
                    return;
                }

                const tableRect = scroll.getBoundingClientRect();
                const headerRect = header.getBoundingClientRect();
                const topbarBottom = getTopbarBottom();
                const shouldShow = headerRect.top < topbarBottom
                    && tableRect.bottom > topbarBottom + headerRect.height;

                stickyHeaderClone.classList.toggle('is-visible', shouldShow);

                if (!shouldShow) {
                    return;
                }

                stickyHeaderClone.style.top = `${topbarBottom}px`;
                stickyHeaderClone.style.left = `${tableRect.left}px`;
                stickyHeaderClone.style.width = `${tableRect.width}px`;

                const cloneTable = stickyHeaderClone.querySelector('table');

                if (cloneTable) {
                    cloneTable.style.width = `${table.scrollWidth}px`;
                    cloneTable.style.transform = `translateX(${-scroll.scrollLeft}px)`;
                }
            };

            const syncFloatingScrollFromTable = () => {
                const scroll = getTableScroll();

                if (!scroll || !floatingScroll || syncingFloatingScroll) {
                    return;
                }

                syncingFloatingScroll = true;
                floatingScroll.scrollLeft = scroll.scrollLeft;
                syncingFloatingScroll = false;
            };

            const updateFloatingScroll = () => {
                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                floatingScroll = document.getElementById('vinetaRegistrosFloatingScroll');

                if (!scroll || !table || !floatingScroll) {
                    return;
                }

                const rect = scroll.getBoundingClientRect();
                const hasHorizontalOverflow = table.scrollWidth > Math.ceil(rect.width);
                const isTableVisible = rect.top < window.innerHeight - 80 && rect.bottom > getTopbarBottom();

                floatingScroll.classList.toggle('is-visible', hasHorizontalOverflow && isTableVisible);

                if (!hasHorizontalOverflow || !isTableVisible) {
                    return;
                }

                const inner = floatingScroll.querySelector('.vinetas-floating-scrollbar-inner');

                if (inner) {
                    inner.style.width = `${table.scrollWidth}px`;
                }

                syncFloatingScrollFromTable();
            };

            const initTableFeatures = () => {
                removeStickyHeaderClone();

                const scroll = getTableScroll();
                const table = scroll?.querySelector('.vinetas-table');
                const header = table?.querySelector('thead');
                floatingScroll = document.getElementById('vinetaRegistrosFloatingScroll');

                if (!scroll || !table || !header || !floatingScroll) {
                    return;
                }

                stickyHeaderClone = document.createElement('div');
                stickyHeaderClone.className = 'vinetas-sticky-header-clone';
                stickyHeaderClone.innerHTML = `
                    <div class="vinetas-sticky-header-inner">
                        <table class="w-full text-sm">${header.outerHTML}</table>
                    </div>
                `;

                const originalHeaders = [...header.querySelectorAll('th')];
                const cloneHeaders = [...stickyHeaderClone.querySelectorAll('th')];

                cloneHeaders.forEach((th, index) => {
                    const width = originalHeaders[index]?.getBoundingClientRect().width;

                    if (width) {
                        th.style.width = `${width}px`;
                        th.style.minWidth = `${width}px`;
                    }
                });

                document.body.appendChild(stickyHeaderClone);

                scroll.addEventListener('scroll', () => {
                    syncStickyHeaderClone();
                    syncFloatingScrollFromTable();
                }, { passive: true });

                if (boundFloatingScroll !== floatingScroll) {
                    floatingScroll.addEventListener('scroll', () => {
                        const currentScroll = getTableScroll();

                        if (!currentScroll || syncingFloatingScroll) {
                            return;
                        }

                        syncingFloatingScroll = true;
                        currentScroll.scrollLeft = floatingScroll.scrollLeft;
                        syncStickyHeaderClone();
                        syncingFloatingScroll = false;
                    }, { passive: true });
                    boundFloatingScroll = floatingScroll;
                }

                if (!windowEventsBound) {
                    window.addEventListener('scroll', () => {
                        syncStickyHeaderClone();
                        updateFloatingScroll();
                    }, { passive: true });
                    window.addEventListener('resize', () => requestAnimationFrame(initTableFeatures));
                    windowEventsBound = true;
                }

                requestAnimationFrame(() => {
                    syncStickyHeaderClone();
                    updateFloatingScroll();
                });
            };

            const showTableLoader = () => {
                const loader = document.getElementById('vinetaRegistrosTableLoader');
                const header = document.querySelector('#vinetaRegistrosTableContainer .productos-sticky-head');

                if (loader && header) {
                    const rect = header.getBoundingClientRect();
                    loader.style.top = `${Math.max(rect.bottom + 12, getTopbarBottom() + 12)}px`;
                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }

                getTableContainer()?.querySelector('#vinetaRegistrosTableInner')?.classList.add('productos-table-loading');
                getSummaryContainer()?.classList.add('productos-table-loading');
            };

            const hideTableLoader = () => {
                getTableContainer()?.querySelector('#vinetaRegistrosTableInner')?.classList.remove('productos-table-loading');
                getSummaryContainer()?.classList.remove('productos-table-loading');

                window.setTimeout(() => {
                    const loader = document.getElementById('vinetaRegistrosTableLoader');
                    loader?.classList.add('hidden');
                    loader?.classList.remove('flex');
                }, 160);
            };

            const loadTable = async (url, preserveHorizontalScroll = false) => {
                const container = getTableContainer();
                const summaryContainer = getSummaryContainer();

                if (!container || !summaryContainer) {
                    return;
                }

                const scrollLeft = preserveHorizontalScroll ? (getTableScroll()?.scrollLeft || 0) : 0;
                showTableLoader();

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo actualizar la tabla de viñetas registradas');
                    }

                    const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const summaryTemplate = responseDocument.getElementById('vinetaRegistrosSummaryResponse');
                    const tableTemplate = responseDocument.getElementById('vinetaRegistrosTableResponse');

                    if (!summaryTemplate || !tableTemplate) {
                        throw new Error('La respuesta no contiene el resumen y la tabla de viñetas registradas');
                    }

                    summaryContainer.innerHTML = summaryTemplate.innerHTML;
                    container.innerHTML = tableTemplate.innerHTML;
                    document.dispatchEvent(new CustomEvent('vineta-registros-table-updated'));
                    initTableFeatures();

                    if (preserveHorizontalScroll) {
                        requestAnimationFrame(() => {
                            const scroll = getTableScroll();

                            if (scroll) {
                                scroll.scrollLeft = Math.min(scrollLeft, Math.max(scroll.scrollWidth - scroll.clientWidth, 0));
                                syncStickyHeaderClone();
                                updateFloatingScroll();
                            }
                        });
                    }

                    window.history.pushState({}, '', url);
                } catch (error) {
                    console.error(error);
                    window.location.href = url;
                } finally {
                    hideTableLoader();
                }
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a');

                if (!link) {
                    return;
                }

                const isSortLink = link.classList.contains('vineta-registros-ajax-table-link');
                const isPaginationLink = link.closest('#vinetaRegistrosTableContainer .vineta-registros-ajax-pagination');
                const isClearLink = link.classList.contains('vineta-registros-ajax-clear');

                if (!isSortLink && !isPaginationLink && !isClearLink) {
                    return;
                }

                event.preventDefault();

                if (isClearLink) {
                    getFilterForm()?.querySelectorAll('input, select').forEach((field) => {
                        field.value = '';
                    });
                }

                loadTable(link.href, isSortLink);
            });

            document.addEventListener('submit', (event) => {
                const filterForm = event.target.closest('.vineta-registros-ajax-filter-form');
                const perPageForm = event.target.closest('.vineta-registros-ajax-per-page-form');

                if (!filterForm && !perPageForm) {
                    return;
                }

                event.preventDefault();

                const form = filterForm || perPageForm;
                const params = new URLSearchParams(new FormData(form));
                params.delete('page');
                loadTable(`${form.action}?${params.toString()}`);
            });

            window.addEventListener('popstate', () => loadTable(window.location.href));
            initTableFeatures();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('seguimientoVinetaModal');
            const title = document.getElementById('seguimientoVinetaTitle');
            const subtitle = document.getElementById('seguimientoVinetaSubtitle');
            const movimientos = document.getElementById('seguimientoVinetaMovimientos');
            const activos = document.getElementById('seguimientoVinetaActivos');
            const puros = document.getElementById('seguimientoVinetaPuros');
            const timelineContainer = document.getElementById('seguimientoVinetaTimeline');
            let timelines = {};
            let summaries = {};
            const numberFormat = new Intl.NumberFormat('es-HN');
            const seguimientoUrlTemplate = @json(route('vineta-registros.seguimiento', ['vineta' => '__VINETA_ID__']));

            const refreshSeguimientoData = () => {
                const data = document.getElementById('vinetaRegistrosSeguimientoData');

                if (!data) {
                    return;
                }

                try {
                    const parsed = JSON.parse(data.textContent);
                    timelines = parsed.timelines || {};
                    summaries = parsed.summaries || {};
                } catch (error) {
                    console.error('No se pudo actualizar el seguimiento de viñetas.', error);
                }
            };

            refreshSeguimientoData();
            document.addEventListener('vineta-registros-table-updated', refreshSeguimientoData);

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

            const renderSeguimiento = (items, summary) => {
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
            };

            const loadSeguimiento = async (vinetaId) => {
                const response = await fetch(
                    seguimientoUrlTemplate.replace('__VINETA_ID__', encodeURIComponent(vinetaId)),
                    {headers: {'Accept': 'application/json'}}
                );

                if (!response.ok) {
                    throw new Error('No se pudo cargar el seguimiento de la viñeta.');
                }

                const data = await response.json();
                timelines[vinetaId] = data.timeline || [];
                summaries[vinetaId] = data.summary || {};

                return {
                    items: timelines[vinetaId],
                    summary: summaries[vinetaId],
                };
            };

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('.vineta-registro-seguimiento');

                if (!button) {
                    return;
                }

                const vinetaId = String(button.dataset.vinetaId || '');
                let items = timelines[vinetaId] || [];
                let summary = summaries[vinetaId] || {};
                const hasPreloadedData = Object.prototype.hasOwnProperty.call(timelines, vinetaId)
                    && Object.prototype.hasOwnProperty.call(summaries, vinetaId);

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                if (!hasPreloadedData) {
                    title.textContent = 'Cargando seguimiento...';
                    subtitle.textContent = 'Consultando los movimientos de la viñeta.';
                    movimientos.textContent = '...';
                    activos.textContent = '...';
                    puros.textContent = '...';
                    timelineContainer.innerHTML = `
                        <div class="theme-soft rounded-2xl border theme-border px-4 py-8 text-center">
                            <p class="theme-title font-black">Cargando movimientos...</p>
                        </div>
                    `;

                    try {
                        ({items, summary} = await loadSeguimiento(vinetaId));
                    } catch (error) {
                        console.error(error);
                        title.textContent = 'No se pudo cargar el seguimiento';
                        subtitle.textContent = 'Intenta nuevamente.';
                        movimientos.textContent = '0';
                        activos.textContent = '0';
                        puros.textContent = '0';
                        timelineContainer.innerHTML = `
                            <div class="theme-soft rounded-2xl border theme-border px-4 py-8 text-center">
                                <p class="theme-title font-black">Error al cargar los movimientos</p>
                            </div>
                        `;
                        return;
                    }
                }

                renderSeguimiento(items, summary);
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

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.vineta-registro-edit');

                if (!button) {
                    return;
                }

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

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.hora-ordinaria-edit');

                if (!button) {
                    return;
                }

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
