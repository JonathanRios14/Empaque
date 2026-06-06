<?php

return [

    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'max' => [
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'unique' => 'El valor del campo :attribute ya está registrado.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'exists' => 'El valor seleccionado en :attribute no es válido.',
    'array' => 'El campo :attribute debe ser una lista.',
    'not_in' => 'El valor seleccionado en :attribute no es permitido.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'current_password' => 'contraseña actual',
        'role' => 'rol',
        'photo' => 'foto de perfil',
        'permissions' => 'permisos',
    ],

];