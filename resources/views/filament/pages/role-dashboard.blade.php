<x-filament-panels::page>
    <div class="space-y-5 sm:space-y-6" aria-labelledby="dashboard-heading">
        <x-filament::section>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-600 dark:text-primary-400">Daily Samvad · Workspace</p>
                    <h1 id="dashboard-heading" class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">{{ $heading }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $description }}</p>
                </div>
                <div wire:loading aria-live="polite" class="hidden items-center gap-2 text-sm text-gray-500 sm:flex">
                    <x-filament::loading-indicator class="h-4 w-4" /> Updating…
                </div>
            </div>
        </x-filament::section>

        <section aria-labelledby="metrics-heading">
            <h2 id="metrics-heading" class="sr-only">Workspace metrics</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
                @forelse ($metrics as $label => $value)
                    <x-filament::section class="transition-shadow hover:shadow-md">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ str($label)->replace('_', ' ')->title() }}</p>
                        <p class="mt-2 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">{{ is_numeric($value) ? number_format($value) : $value }}</p>
                    </x-filament::section>
                @empty
                    <x-filament::section class="sm:col-span-2 xl:col-span-4"><p class="text-sm text-gray-500">No dashboard data is available yet.</p></x-filament::section>
                @endforelse
            </div>
        </section>

        @if ($actions !== [])
            <x-filament::section heading="Quick actions">
                <div class="flex flex-wrap gap-2" aria-label="Quick actions">
                    @foreach ($actions as $action)
                        <x-filament::button tag="a" :href="$action['url']" size="sm">{{ $action['label'] }}</x-filament::button>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        <x-filament::section heading="Recent workflow activity">
            <div wire:loading class="space-y-3" aria-hidden="true">
                @for ($i = 0; $i < 3; $i++) <div class="h-4 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div> @endfor
            </div>
            <ol wire:loading.remove class="divide-y divide-gray-200 dark:divide-gray-700" aria-label="Recent workflow activity">
                @forelse ($activity as $item)
                    <li class="flex flex-col gap-1 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ str($item['event'])->replace('_', ' ')->title() }} <span class="font-normal text-gray-500">· Post #{{ $item['post_id'] }}</span></span>
                        <time class="text-xs text-gray-500" datetime="{{ $item['created_at'] }}">{{ $item['created_at'] }}</time>
                    </li>
                @empty
                    <li class="py-3 text-sm text-gray-500">No workflow activity is available in your authorized scope.</li>
                @endforelse
            </ol>
        </x-filament::section>
    </div>
</x-filament-panels::page>
