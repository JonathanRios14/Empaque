<section>
    <header>
        <h2 class="theme-title text-lg font-bold text-[#3b2818]">
            Información del perfil
        </h2>

        <p class="theme-text mt-1 text-sm text-gray-600">
            Actualiza tu nombre, correo electrónico y foto de perfil.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post"
          action="{{ route('profile.update') }}"
          class="mt-6 space-y-6"
          enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="name" class="theme-title block text-sm font-semibold text-[#3b2818] mb-2">
                    Nombre
                </label>

                <input id="name"
                       name="name"
                       type="text"
                       value="{{ old('name', $user->name) }}"
                       required
                       autofocus
                       autocomplete="name"
                       class="theme-input w-full rounded-xl border-gray-300 text-sm focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <label for="email" class="theme-title block text-sm font-semibold text-[#3b2818] mb-2">
                    Correo electrónico
                </label>

                <input id="email"
                       name="email"
                       type="email"
                       value="{{ old('email', $user->email) }}"
                       required
                       autocomplete="username"
                       class="theme-input w-full rounded-xl border-gray-300 text-sm focus:border-[#5b3a1e] focus:ring-[#5b3a1e]">

                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        <div>
            <label for="photo" class="theme-title block text-sm font-semibold text-[#3b2818] mb-2">
                Foto de perfil
            </label>

            <label for="photo"
                   class="theme-soft flex items-center justify-between gap-4 w-full rounded-2xl border border-dashed border-[#d8c6a3] bg-[#fbf8f3] px-5 py-4 cursor-pointer hover:bg-[#f3efe7] transition">

                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-[#5b3a1e] text-white flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="w-5 h-5"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M12 12V4m0 0l-4 4m4-4l4 4" />
                        </svg>
                    </div>

                    <div>
                        <p class="theme-title text-sm font-semibold text-[#3b2818]">
                            Seleccionar nueva foto
                        </p>

                        <p id="photoFileName" class="theme-text text-xs text-gray-500 mt-1">
                            JPG, PNG o WEBP · máximo 2 MB
                        </p>
                    </div>
                </div>

                <span class="theme-button-secondary hidden md:inline-flex px-4 py-2 rounded-xl bg-white border border-[#e5d8c7] text-[#5b3a1e] text-xs font-semibold">
                    Buscar archivo
                </span>
            </label>

            <input id="photo"
                   name="photo"
                   type="file"
                   accept="image/*"
                   class="hidden">

            <x-input-error class="mt-2" :messages="$errors->get('photo')" />
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-xl bg-yellow-50 border border-yellow-200 p-4">
                <p class="text-sm text-yellow-800">
                    Tu correo electrónico aún no está verificado.

                    <button form="send-verification"
                            class="underline text-sm font-semibold text-yellow-900 hover:text-yellow-700">
                        Reenviar correo de verificación
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-sm text-green-600 font-medium">
                        Se envió un nuevo enlace de verificación a tu correo.
                    </p>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-end gap-4 pt-4 border-t border-[#eee3d5] theme-border">
            <button type="submit"
                    class="gooey-action px-5 py-2.5 rounded-xl bg-[#5b3a1e] text-white text-sm font-semibold hover:bg-[#3b2818] transition">
                Guardar cambios
            </button>

       @if (session('status') === 'profile-updated')
<script>
document.addEventListener('DOMContentLoaded', () => {
    mostrarToast('success', 'Información actualizada correctamente.');
});
</script>
@endif
        </div>
    </form>
</section>
