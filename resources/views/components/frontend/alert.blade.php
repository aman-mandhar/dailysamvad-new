@props(['type' => 'info', 'message' => null])

@php
    $styles = [
        'success' => 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100',
        'error' => 'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100',
        'warning' => 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100',
        'info' => 'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100',
    ];
    $alertType = array_key_exists($type, $styles) ? $type : 'info';
@endphp

<div {{ $attributes->class(['rounded-lg border px-4 py-3 text-sm', $styles[$alertType]]) }} role="{{ $alertType === 'error' ? 'alert' : 'status' }}">
    {{ $message ?? $slot }}
</div>
