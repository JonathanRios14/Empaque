<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800">

<div x-data="{ sidebarOpen: true, catalogos: false, seguridad: false, produccion: false }" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">
       
    @include('layouts.topbar', [
    'title' => 'Editar usuario',
    'description' => 'Actualiza la información del usuario y su rol dentro del sistema.',
    ])


        <section class="p-6">
            <div class="max-w-5xl mx-auto">

                <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-[#e5d8c7] bg-[#fbf8f3]">
                        <h2 class="text-lg font-bold text-[#3b2818]">
                            Información del usuario
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Puedes cambiar nombre, correo, rol o contraseña.
                        </p>
                    </div>

                    <form method="POST"
                          action="{{ route('usuarios.update', $user) }}"
                          class="p-6 space-y-5"
                          x-data="{ showPassword: false, showConfirmPassword: false }">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-[#3b2818] mb-2">
                                    Nombre
                                </label>

                                <input type="text"
                                       name="name"
                                       value="{{ old('name', $user->name) }}"
                                       required
                                       class="w-full rounded-xl border-gray-300 text-sm focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                                @error('name')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[#3b2818] mb-2">
                                    Correo electrónico
                                </label>

                                <input type="email"
                                       name="email"
                                       value="{{ old('email', $user->email) }}"
                                       required
                                       class="w-full rounded-xl border-gray-300 text-sm focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                                @error('email')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-[#3b2818] mb-2">
                                Rol del usuario
                            </label>

                            <select name="role"
                                    required
                                    class="w-full rounded-xl border-gray-300 text-sm focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">
                                <option value="">Seleccione un rol</option>

                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}"
                                        @selected(old('role', $user->roles->first()?->name) === $role->name)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('role')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-xl bg-[#f9f5ee] border border-[#e5d8c7] p-4">
                            <p class="text-sm font-semibold text-[#3b2818]">
                                Cambiar contraseña
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Deja estos campos vacíos si no deseas cambiar la contraseña.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-[#3b2818] mb-2">
                                    Nueva contraseña
                                </label>

                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'"
                                           name="password"
                                           placeholder="Opcional"
                                           class="w-full rounded-xl border-gray-300 text-sm pr-20 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                                    <button type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#5b3a1e] font-semibold">
                                        <span x-text="showPassword ? 'Ocultar' : 'Ver'"></span>
                                    </button>
                                </div>

                                @error('password')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[#3b2818] mb-2">
                                    Confirmar contraseña
                                </label>

                                <div class="relative">
                                    <input :type="showConfirmPassword ? 'text' : 'password'"
                                           name="password_confirmation"
                                           placeholder="Opcional"
                                           class="w-full rounded-xl border-gray-300 text-sm pr-20 focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                                    <button type="button"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-[#5b3a1e] font-semibold">
                                        <span x-text="showConfirmPassword ? 'Ocultar' : 'Ver'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#eee3d5]">
                            <a href="{{ route('usuarios.index') }}"
                               class="px-5 py-2.5 rounded-xl bg-gray-100 text-gray-700 text-sm hover:bg-gray-200 transition">
                                Cancelar
                            </a>

                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </section>
    </main>
</div>
@include('layouts.flash')

</body>
</html>