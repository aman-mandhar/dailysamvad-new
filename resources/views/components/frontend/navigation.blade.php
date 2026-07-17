@props(['mobile' => false])

@php
    $items = ['Home', 'Latest', 'Punjab', 'India', 'World'];
@endphp

<nav {{ $attributes->class([$mobile ? 'block' : 'hidden lg:block']) }} aria-label="{{ $mobile ? 'Mobile navigation' : 'Main navigation' }}">
    <ul class="{{ $mobile ? 'flex flex-col gap-1' : 'flex items-center gap-1' }}">
        @foreach ($items as $item)
            <li>
                <span class="block rounded-md px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                    {{ $item }}
                </span>
            </li>
        @endforeach
    </ul>
</nav>
