@php
    $catalogosActivo = request()->routeIs('catalogos.*');
@endphp

<aside
    x-init="
        const savedSidebar = localStorage.getItem('sidebarOpen');

        if (savedSidebar !== null) {
            sidebarOpen = savedSidebar === 'true';
        }

        $watch('sidebarOpen', value => {
            localStorage.setItem('sidebarOpen', value);
        });
    "
    :class="sidebarOpen ? 'w-72' : 'w-20'"
    class="app-sidebar hidden md:block shrink-0 transition-[width] duration-300 ease-in-out bg-[#3b2818] text-white">
    <div class="app-sidebar-header h-20 flex items-center border-b border-white/10"
     :class="sidebarOpen ? 'justify-between px-4' : 'justify-center px-0'">
    <div x-show="sidebarOpen"
     x-transition.opacity.duration.150ms
     class="flex items-center gap-3">
            <div class="w-12 h-12  flex items-center justify-center shadow-sm">
                <img src="{{ asset('img/plasencia-logo.png') }}"
                     class="h-12 w-auto object-contain"
                     alt="Logo">
            </div>

            <div>
                <p class="text-sl font-bold leading-tight">Sistema</p>
                <p class="text-sm text-white/60 leading-tight">Empaque</p>
            </div>
        </div>
<button @click="sidebarOpen = !sidebarOpen; localStorage.setItem('sidebarOpen', sidebarOpen)"
        class="app-sidebar-toggle w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/10 text-white transition duration-200"
        :class="sidebarOpen ? '' : 'translate-x-[4px]'">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <nav class="py-5 pl-4 pr-0 space-y-2">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
           {{ request()->routeIs('dashboard') && !request('tab') ? 'sidebar-active' : 'sidebar-link' }}">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
            </svg>

            <span x-show="sidebarOpen" x-transition.opacity.duration.150ms>Dashboard</span>
        </a>

        {{-- Usuarios --}}
        @can('usuarios.ver')
            <a href="{{ route('usuarios.index') }}"
               class="w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
               {{ request()->routeIs('usuarios.*') ? 'sidebar-active' : 'sidebar-link' }}">

                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17 20h5v-2a4 4 0 0 0-4-4h-1M9 20H4v-2a4 4 0 0 1 4-4h1m8-5a4 4 0 1 0-8 0 4 4 0 0 0 8 0zm-8 0a4 4 0 1 0-8 0 4 4 0 0 0 8 0z" />
                </svg>

                <span x-show="sidebarOpen" x-transition.opacity.duration.150ms>Usuarios</span>
            </a>
        @endcan

        {{-- Roles --}}
        @can('roles.ver')
            <a href="{{ route('roles.index') }}"
               class="w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
               {{ request()->routeIs('roles.*') ? 'sidebar-active' : 'sidebar-link' }}">

                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6 21v-2a6 6 0 0 1 12 0v2M19 8l1.5 1.5L23 7" />
                </svg>

                <span x-show="sidebarOpen" x-transition.opacity.duration.150ms>Roles</span>
            </a>
        @endcan

        {{-- Permisos --}}
        @can('roles.ver')
            <a href="{{ route('permisos.index') }}"
               class="w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
               {{ request()->routeIs('permisos.*') ? 'sidebar-active' : 'sidebar-link' }}">

                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 2l7 4v6c0 5-3.5 9-7 10-3.5-1-7-5-7-10V6l7-4z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12l2 2 4-4" />
                </svg>

                <span x-show="sidebarOpen" x-transition.opacity.duration.150ms>Permisos</span>
            </a>
        @endcan

        {{-- Catálogos --}}
        @can('catalogos.ver')
   <button type="button"
        @click="
            if (!sidebarOpen) {
                sidebarOpen = true;
                catalogos = true;
            } else {
                catalogos = !catalogos;
            }
        "
        class="w-full flex items-center justify-between px-4 py-3 rounded-l-2xl transition
        {{ $catalogosActivo ? 'sidebar-active' : 'sidebar-link' }}">

                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 6v12" />
                    </svg>

                    <span x-show="sidebarOpen" x-transition.opacity.duration.150ms>Catálogos</span>
                </div>

                <svg x-show="sidebarOpen"
                     :class="catalogos ? 'rotate-180' : ''"
                     class="w-4 h-4 transition-transform"
                     xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="catalogos && sidebarOpen"
     x-transition.opacity.duration.200ms
     class="ml-8 mr-4 mt-2 space-y-1 text-sm"
     style="display: none;">

                @can('productos.ver')
                    <a href="{{ route('catalogos.productos.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.productos.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Productos
                    </a>
                @endcan

                @can('marcas.ver')
                    <a href="{{ route('catalogos.marcas.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.marcas.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Marcas
                    </a>
                @endcan

                @can('vitolas.ver')
                    <a href="{{ route('catalogos.vitolas.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.vitolas.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Vitolas
                    </a>
                @endcan

                @can('capas.ver')
                    <a href="{{ route('catalogos.capas.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.capas.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Capas
                    </a>
                @endcan

                @can('actividades.ver')
                    <a href="{{ route('catalogos.actividades.index') }}"
                       class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.actividades.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                        Actividades
                    </a>
                @endcan

                <a href="{{ route('catalogos.empresas.index') }}"
                   class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.empresas.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                    Empresas
                </a>

                <a href="{{ route('catalogos.presentaciones.index') }}"
                   class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.presentaciones.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                    Presentaciones
                </a>

                <a href="{{ route('catalogos.tipo-empaques.index') }}"
                   class="block px-4 py-2 rounded-xl transition {{ request()->routeIs('catalogos.tipo-empaques.*') ? 'sidebar-sub-active' : 'sidebar-sub-link' }}">
                    Tipos de empaque
                </a>
            </div>
        @endcan

        {{-- Mi perfil --}}
        <a href="{{ route('profile.edit') }}"
           class="w-full flex items-center gap-3 px-4 py-3 rounded-l-2xl transition
           {{ request()->routeIs('profile.edit') ? 'sidebar-active' : 'sidebar-link' }}">

            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 min-w-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 21a8 8 0 0 1 16 0" />
            </svg>

            <span x-show="sidebarOpen" x-transition.opacity.duration.150ms>Mi perfil</span>
        </a>

    </nav>
</aside>