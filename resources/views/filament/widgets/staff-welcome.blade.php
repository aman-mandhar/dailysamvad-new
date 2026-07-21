<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500">{{ auth()->user()->name }} · {{ str($role)->replace('-', ' ')->title() }}</p>
                <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $heading }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $description }}</p>
            </div>
            @if ($createUrl)
                <x-filament::button tag="a" :href="$createUrl">Create Post</x-filament::button>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
