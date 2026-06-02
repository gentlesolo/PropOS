<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Bulk Import / Export</h1>
            <p class="text-sm text-text-secondary mt-0.5">Import contacts and listings from CSV, or export data</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <!-- Import -->
        <div class="bg-surface-card rounded-2xl border border-border-default p-6">
            <h2 class="text-base font-semibold text-text-primary mb-4">Import from CSV</h2>

            <div class="mb-4">
                <label class="block text-xs font-medium text-text-secondary mb-1">Import Type</label>
                <select wire:model.live="importType" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="contacts">Contacts</option>
                    <option value="listings">Listings</option>
                </select>
            </div>

            <!-- CSV Field Guide -->
            <div class="mb-4 p-3 bg-surface-hover/50 rounded-xl text-xs text-text-secondary">
                @if($importType === 'contacts')
                <p class="font-semibold text-text-primary mb-1">Expected columns:</p>
                <code class="text-brand-primary">first_name, last_name, email, phone, type, source</code>
                @else
                <p class="font-semibold text-text-primary mb-1">Expected columns:</p>
                <code class="text-brand-primary">listing_price, status, type, mandate_type</code>
                @endif
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-text-secondary mb-1">CSV File (max 5MB)</label>
                <input type="file" wire:model="csvFile" accept=".csv,.txt" class="w-full text-sm text-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 transition-colors">
                <div wire:loading wire:target="csvFile" class="text-xs text-text-tertiary mt-1">Parsing file…</div>
            </div>

            <!-- Preview -->
            @if(!empty($preview))
            <div class="mb-4">
                <p class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">Preview (first 5 rows)</p>
                <div class="overflow-x-auto rounded-lg border border-border-default">
                    <table class="text-xs w-full">
                        <thead class="bg-surface-hover/50">
                            <tr>
                                @foreach($preview['headers'] as $h)
                                <th class="px-2 py-1.5 text-left text-text-secondary font-medium">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-default">
                            @foreach($preview['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                <td class="px-2 py-1.5 text-text-primary">{{ $cell }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if(!empty($importResults))
            <div class="mb-4 p-3 rounded-xl {{ $importResults['failed'] > 0 ? 'bg-warning-50 border border-warning-200' : 'bg-success-50 border border-success-200' }}">
                <p class="text-sm font-semibold {{ $importResults['failed'] > 0 ? 'text-warning-700' : 'text-success-700' }}">
                    Imported {{ $importResults['imported'] }} records. {{ $importResults['failed'] }} failed.
                </p>
                @if(!empty($importResults['errors']))
                <ul class="mt-2 space-y-1">
                    @foreach(array_slice($importResults['errors'], 0, 5) as $error)
                    <li class="text-xs text-warning-600">{{ $error }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endif

            <button wire:click="import" @if(!$csvFile) disabled @endif class="w-full py-2.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="import">Import {{ ucfirst($importType) }}</span>
                <span wire:loading wire:target="import">Importing…</span>
            </button>
        </div>

        <!-- Export -->
        <div class="bg-surface-card rounded-2xl border border-border-default p-6">
            <h2 class="text-base font-semibold text-text-primary mb-4">Export to CSV</h2>

            <div class="mb-6">
                <label class="block text-xs font-medium text-text-secondary mb-1">Export Type</label>
                <select wire:model="exportType" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="contacts">All Contacts</option>
                    <option value="listings">All Listings</option>
                </select>
            </div>

            <div class="space-y-3 mb-6">
                @if($exportType === 'contacts')
                <div class="p-3 bg-surface-hover/50 rounded-xl text-xs text-text-secondary">
                    <p class="font-semibold text-text-primary mb-1">Includes columns:</p>
                    id, first_name, last_name, email, phone, type, source, intent_score, created_at
                </div>
                @else
                <div class="p-3 bg-surface-hover/50 rounded-xl text-xs text-text-secondary">
                    <p class="font-semibold text-text-primary mb-1">Includes columns:</p>
                    id, status, listing_price, type, mandate_type, days_on_market, health_score, created_at
                </div>
                @endif
            </div>

            <button wire:click="export" class="w-full py-2.5 bg-surface-hover border border-border-default text-text-primary rounded-xl text-sm font-medium hover:bg-surface-card transition-colors">
                <span wire:loading.remove wire:target="export">Export {{ ucfirst($exportType) }}</span>
                <span wire:loading wire:target="export">Preparing CSV…</span>
            </button>

            <!-- Tips -->
            <div class="mt-6 space-y-3 text-xs text-text-secondary">
                <p class="font-semibold text-text-primary text-sm">Import Tips</p>
                <ul class="space-y-1.5 list-disc list-inside">
                    <li>Save your spreadsheet as CSV (UTF-8 encoding)</li>
                    <li>First row must be the header row with exact column names</li>
                    <li>Duplicate emails are updated, not duplicated</li>
                    <li>Maximum 5,000 rows per import</li>
                </ul>
            </div>
        </div>
    </div>
</div>



