@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-blue-200 focus:border-blue-500 focus:ring-blue-500']) }}>
