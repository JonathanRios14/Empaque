@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'theme-input border-gray-300 rounded-md shadow-sm']) !!}>
