<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">CMA Reports</h1>
            <p class="text-sm text-text-secondary mt-0.5">Generate Comparative Market Analysis reports for clients</p>
        </div>
        <button wire:click="$toggle('showCreateForm')" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New CMA Report
        </button>
    </div>

    @if($showCreateForm)
    <div class="bg-surface-card rounded-2xl border border-border-default p-5 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Generate CMA Report</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Report Title *</label>
                <input wire:model="title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page" placeholder="CMA &#8358; 45 Oak Avenue, Sandton">
                @error('title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Subject Property Address *</label>
                <input wire:model="subject_address" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                @error('subject_address') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Estimated Value Low ({{ $currencySymbol }})</label>
                <input wire:model="estimated_value_low" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Estimated Value High ({{ $currencySymbol }})</label>
                <input wire:model="estimated_value_high" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Recommended List Price ({{ $currencySymbol }})</label>
                <input wire:model="recommended_list_price" type="number" min="0" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Link to Contact</label>
                <select wire:model="contact_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="">None</option>
                    @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Executive Summary</label>
                <textarea wire:model="summary" rows="3" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none" placeholder="Market context, key findings, recommendation&#8358;"></textarea>
            </div>
        </div>

        <!-- Comparable Sales -->
        <div class="border-t border-border-default pt-4 mb-4">
            <h3 class="text-sm font-semibold text-text-primary mb-3">Comparable Sales ({{ count($comparable_sales) }})</h3>
            @foreach($comparable_sales as $i => $comp)
            <div class="flex items-center gap-3 p-3 bg-surface-hover/30 rounded-lg mb-2">
                <div class="flex-1 text-sm">
                    <span class="font-medium text-text-primary">{{ $comp['address'] }}</span>
                    <span class="text-text-secondary ml-2">{{ $currencySymbol }}{{ number_format($comp['sale_price']) }}</span>
                    @if($comp['sale_date']) <span class="text-text-tertiary ml-2 text-xs">{{ $comp['sale_date'] }}</span> @endif
                </div>
                <button wire:click="removeComparable({{ $i }})" class="text-danger-500 hover:text-danger-700 text-sm">&#8358;</button>
            </div>
            @endforeach
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mt-2">
                <input wire:model="comp_address" type="text" placeholder="Address *" class="md:col-span-2 rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                <input wire:model="comp_sale_price" type="number" placeholder="Sale Price *" class="rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                <input wire:model="comp_sale_date" type="date" class="rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                <button type="button" wire:click="addComparable" class="px-3 py-2 bg-surface-hover border border-border-default rounded-lg text-sm text-text-secondary hover:bg-surface-card transition-colors">+ Add</button>
            </div>
        </div>

        <div class="flex gap-3">
            <button wire:click="generate" class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                <span wire:loading.remove wire:target="generate">Generate Report</span>
                <span wire:loading wire:target="generate">Generating&#8358;</span>
            </button>
            <button wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm text-text-secondary hover:bg-surface-hover transition-colors">Cancel</button>
        </div>
    </div>
    @endif

    <!-- Reports Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($reports as $report)
        <div class="bg-surface-card rounded-2xl border border-border-default p-5">
            <h3 class="font-semibold text-text-primary mb-1 truncate">{{ $report->title }}</h3>
            <p class="text-xs text-text-secondary mb-3 truncate">{{ $report->subject_address }}</p>
            <dl class="space-y-1.5 text-sm mb-4">
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Value Range</dt>
                    <dd class="font-medium text-text-primary text-xs">{{ $report->valueRange }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Recommended</dt>
                    <dd class="font-bold text-text-primary">{{ $report->recommended_list_price ? $currencySymbol.number_format($report->recommended_list_price) : '&#8358;' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Comparables</dt>
                    <dd class="font-medium text-text-primary">{{ count($report->comparable_sales ?? []) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-text-secondary">Created</dt>
                    <dd class="text-text-tertiary text-xs">{{ $report->created_at->format('d M Y') }}</dd>
                </div>
            </dl>
            @if($report->pdf_path)
            <a href="{{ Storage::url($report->pdf_path) }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-brand-primary hover:underline">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                Download PDF
            </a>
            @endif
        </div>
        @empty
        <div class="md:col-span-3 bg-surface-card rounded-2xl border border-border-default p-12 text-center">
            <p class="text-text-tertiary text-sm">No CMA reports yet. Generate your first one above.</p>
        </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $reports->links() }}</div>
</div>



