<x-filament-panels::page>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        @php($statistics = $latest['statistics'] ?? [])
        @foreach (['imported', 'updated', 'skipped', 'failed'] as $statistic)
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ str($statistic)->headline() }}</p>
                <p class="mt-2 text-3xl font-semibold">{{ number_format($statistics[$statistic] ?? 0) }}</p>
            </x-filament::section>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Latest import">
            @if ($latest)
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="font-medium">Completed</dt><dd>{{ $latest['completed_at'] ?? 'Unknown' }}</dd></div>
                    <div><dt class="font-medium">Mode</dt><dd>{{ $latest['mode'] ?? 'Unknown' }}</dd></div>
                    <div><dt class="font-medium">Importers</dt><dd>{{ implode(', ', $latest['importers'] ?? []) }}</dd></div>
                    <div><dt class="font-medium">Resume run</dt><dd>{{ ($latest['resume'] ?? false) ? 'Yes' : 'No' }}</dd></div>
                    <div><dt class="font-medium">Dry run</dt><dd>{{ ($latest['dry_run'] ?? false) ? 'Yes' : 'No' }}</dd></div>
                </dl>
            @else
                <p>No import history has been recorded.</p>
            @endif
        </x-filament::section>

        <x-filament::section heading="Latest verification">
            @if ($verification)
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    @foreach (($verification['summary'] ?? []) as $label => $value)
                        <div><dt class="font-medium">{{ str($label)->headline() }}</dt><dd>{{ number_format($value) }}</dd></div>
                    @endforeach
                </dl>
            @else
                <p>Run <code>php artisan import:verify</code> to generate verification results.</p>
            @endif
        </x-filament::section>
    </div>

    <x-filament::section heading="Recent failures and warnings">
        @forelse ($events as $event)
            <div class="border-b border-gray-200 py-3 text-sm last:border-0 dark:border-gray-700">
                <span class="font-medium uppercase">{{ $event['level'] }}</span>
                <span class="ml-2">{{ $event['message'] }}</span>
                <span class="block text-gray-500">{{ $event['recorded_at'] }}</span>
            </div>
        @empty
            <p>No recent import failures.</p>
        @endforelse
    </x-filament::section>

    <x-filament::section heading="Recent runs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr><th class="py-2">Completed</th><th>Importers</th><th>Mode</th><th>Resume</th><th>Result</th></tr></thead>
                <tbody>
                    @forelse ($history as $run)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-2">{{ $run['completed_at'] ?? 'Unknown' }}</td>
                            <td>{{ implode(', ', $run['importers'] ?? []) }}</td>
                            <td>{{ $run['mode'] ?? 'Unknown' }}</td>
                            <td>{{ ($run['resume'] ?? false) ? 'Yes' : 'No' }}</td>
                            <td>{{ ($run['statistics']['failed'] ?? 0) > 0 ? 'Failed' : 'Completed' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3">No import runs recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
