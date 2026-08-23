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
