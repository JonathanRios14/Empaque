<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario | Sistema de Empaque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f5f2ec] text-gray-800">

<div x-data="{ sidebarOpen: true, catalogos: false, seguridad: false, produccion: false }" class="flex min-h-screen">

    @include('layouts.sidebar')

    <main class="flex-1">
 
    @include('layouts.topbar', [
    'title' => 'Crear usuario',
    'description' => 'Registra un usuario y asigna su rol dentro del sistema.'
])

        <section class="p-6">
    <div class="max-w-5xl mx-auto">

                <div class="bg-white rounded-2xl border border-[#e5d8c7] shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-[#e5d8c7] bg-[#fbf8f3]">
                        <h2 class="text-lg font-bold text-[#3b2818]">
                            Información del usuario
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Completa los datos necesarios para crear la cuenta.
                        </p>
                    </div>

                    <form method="POST"
                          action="{{ route('usuarios.store') }}"
                          class="p-6 space-y-5"
                          x-data="{ showPassword: false, showConfirmPassword: false }">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-[#3b2818] mb-2">
                                    Nombre
                                </label>

                                <input type="text"
                                       name="name"
                                       value="{{ old('name') }}"
                                       required
                                       placeholder="Ej. Juan Pérez"
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
                                       value="{{ old('email') }}"
                                       required
                                       placeholder="usuario@empresa.com"
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
                                    <option value="{{ $role->name }}" @selected(old('role') === $role->name)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('role')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-[#3b2818] mb-2">
                                    Contraseña
                                </label>

                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'"
                                           name="password"
                                           required
                                           placeholder="Mínimo 8 caracteres"
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
                                           required
                                           placeholder="Repite la contraseña"
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
                                Guardar usuario
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