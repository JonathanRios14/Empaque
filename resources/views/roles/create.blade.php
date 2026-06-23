<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Rol | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('layouts.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800 transition-colors duration-300">

<div x-data="{
    sidebarOpen: localStorage.getItem('sidebarOpen') === null ? true : localStorage.getItem('sidebarOpen') === 'true',
    catalogos: false
}" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">

        @include('layouts.topbar', [
            'title' => 'Nuevo rol',
            'description' => 'Crea un rol y asigna los permisos correspondientes.',
        ])

        <section class="p-4 lg:p-6">
            <div class="w-full max-w-[1600px] mx-auto">

                <form method="POST" action="{{ route('roles.store') }}">
                    @csrf

                    <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm p-6 mb-6">
                        <h2 class="theme-title text-lg font-bold text-[#3b2818]">
                            Información del rol
                        </h2>

                        <p class="theme-text text-sm text-gray-500 mt-1">
                            Define el nombre del rol y selecciona sus permisos.
                        </p>

                        <div class="mt-5 max-w-md">
                            <label class="theme-title block text-sm font-semibold text-[#3b2818] mb-2">
                                Nombre del rol
                            </label>

                            <input type="text"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   placeholder="Ej. Encargado de empaque"
                                   class="theme-input w-full rounded-xl border-gray-300 text-sm focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                            @error('name')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="theme-card theme-shadow bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                        <div class="theme-soft px-6 py-5 border-b border-[#e5d8c7] theme-border bg-[#fbf8f3]">
                            <h2 class="theme-title text-lg font-bold text-[#3b2818]">
                                Permisos del rol
                            </h2>

                            <p class="theme-text text-sm text-gray-500 mt-1">
                                Marca los permisos que tendrá este rol.
                            </p>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                @foreach ($grupos as $nombreGrupo => $permisosGrupo)
                                    @if ($permisosGrupo->count())
                                        <div class="theme-soft rounded-2xl bg-[#f9f5ee] border border-[#e5d8c7] theme-border overflow-hidden">
                                            <div class="theme-card px-5 py-4 bg-white border-b border-[#e5d8c7] theme-border flex items-center justify-between">
                                                <h3 class="theme-title font-bold text-[#3b2818]">
                                                    {{ $nombreGrupo }}
                                                </h3>

                                                <span class="theme-badge px-3 py-1 rounded-full bg-[#f3efe7] text-[#5b3a1e] text-xs font-semibold border border-[#e5d8c7]">
                                                    {{ $permisosGrupo->count() }}
                                                </span>
                                            </div>

                                            <div class="p-5 space-y-3">
                                                @foreach ($permisosGrupo as $permission)
                                                    <label class="theme-card flex items-center justify-between rounded-xl bg-white border border-[#eee3d5] theme-border px-4 py-3 cursor-pointer hover:bg-[#f3efe7] transition">
                                                        <div>
                                                            <p class="theme-title text-sm font-medium text-gray-700">
                                                                {{ $permission->name }}
                                                            </p>

                                                            <p class="theme-text text-xs text-gray-400">
                                                                ID {{ $permission->id }}
                                                            </p>
                                                        </div>

                                                        <input type="checkbox"
                                                               name="permissions[]"
                                                               value="{{ $permission->name }}"
                                                               @checked(is_array(old('permissions')) && in_array($permission->name, old('permissions')))
                                                               class="rounded border-gray-300 text-[#5b3a1e] focus:ring-[#5b3a1e]">
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="theme-soft sticky bottom-0 mt-6 bg-[#f5f2ec]/90 backdrop-blur border-t border-[#e5d8c7] theme-border py-4">
                        <div class="max-w-[1600px] mx-auto flex justify-end gap-3">
                            <a href="{{ route('roles.index') }}"
                               class="theme-button-secondary px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm border border-gray-200 hover:bg-gray-200 transition">
                                Cancelar
                            </a>

                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                Guardar rol
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </section>
    </main>
</div>

@include('layouts.flash')

</body>
</html>