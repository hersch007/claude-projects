<x-filament-panels::page>

    <div class="space-y-6">

        <x-filament::section heading="Upload Lumos Customer Payment File">
            <p class="text-sm text-gray-500 mb-4">
                Upload the Excel (.xlsx) or CSV file exported from the Lumos payment system.
                The importer will automatically create customers, domains, and payment records.
            </p>

            <form wire:submit.prevent="import">
                {{ $this->form }}

                <div class="mt-4">
                    <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray">
                        Import File
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section heading="Recent Imports">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2 pr-4">File</th>
                            <th class="py-2 pr-4">Imported</th>
                            <th class="py-2 pr-4">Skipped</th>
                            <th class="py-2 pr-4">Date</th>
                            <th class="py-2">Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->getLogs() as $log)
                        <tr class="border-b">
                            <td class="py-2 pr-4 font-medium">{{ $log->filename }}</td>
                            <td class="py-2 pr-4 text-green-600">{{ $log->rows_imported }}</td>
                            <td class="py-2 pr-4 text-yellow-600">{{ $log->rows_skipped }}</td>
                            <td class="py-2 pr-4 text-gray-500">{{ $log->created_at->format('M j, Y g:ia') }}</td>
                            <td class="py-2 text-red-500 text-xs">{{ $log->errors ? 'Yes' : '—' }}</td>
                        </tr>
                        @endforeach

                        @if($this->getLogs()->isEmpty())
                            <tr><td colspan="5" class="py-4 text-gray-400 text-center">No imports yet.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </x-filament::section>

    </div>

</x-filament-panels::page>
