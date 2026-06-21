<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Document Repository</h1>
            <p class="text-sm text-text-secondary mt-0.5">Centralised, searchable vault for all agency documents</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('governance.documents.export.csv', ['category' => $categoryFilter, 'status' => $statusFilter]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-success-50 text-success-700 border border-success-200 rounded-xl text-sm font-medium hover:bg-success-100 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <button wire:click="$toggle('showUploadForm')" class="disabled:opacity-70 disabled:cursor-not-allowed relative inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                Upload Document</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([
            ['label'=>'Total Documents','val'=>$stats['total'],'color'=>'brand'],
            ['label'=>'Approved','val'=>$stats['approved'],'color'=>'success'],
            ['label'=>'Expiring Soon','val'=>$stats['expiring_soon'],'color'=>'warning'],
            ['label'=>'Expired','val'=>$stats['expired'],'color'=>'danger'],
        ] as $s)
        <div class="bg-surface-card rounded-2xl border border-border-default p-4 text-center">
            <div class="text-2xl font-bold text-text-primary">{{ $s['val'] }}</div>
            <div class="text-xs text-text-secondary mt-1">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Upload Form --}}
    @if($showUploadForm)
    <div class="bg-surface-card rounded-2xl border border-brand-primary/30 p-6 mb-6">
        <h2 class="text-base font-semibold text-text-primary mb-4">Upload New Document</h2>
        <form wire:submit.prevent="uploadDocument" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Title *</label>
                <input wire:model="doc_title" type="text" placeholder="e.g. Lease Agreement – Unit 4B"
                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                @error('doc_title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Category *</label>
                <select wire:model="doc_category" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    @foreach(['lease_agreement'=>'Lease Agreement','compliance_record'=>'Compliance Record','inspection_report'=>'Inspection Report','contract'=>'Contract','identity'=>'Identity Document','financial'=>'Financial','other'=>'Other'] as $val=>$label)
                    <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Document Type</label>
                <input wire:model="doc_type" type="text" placeholder="e.g. Rental Contract, Gas Certificate"
                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Expiry Date</label>
                <input wire:model="doc_expiry_date" type="date"
                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
            </div>
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Link To</label>
                <select wire:model.live="linked_type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="">No link (standalone)</option>
                    <option value="transaction">Transaction</option>
                    <option value="lease">Lease</option>
                    <option value="listing">Listing</option>
                    <option value="property">Property</option>
                </select>
            </div>
            @if($linked_type)
            <div>
                <label class="block text-xs font-medium text-text-secondary mb-1">Select {{ ucfirst($linked_type) }}</label>
                <select wire:model="linked_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <option value="">-- choose --</option>
                    @if($linked_type === 'transaction')
                        @foreach($transactions as $t)
                        <option value="{{ $t->id }}">{{ $t->reference }}</option>
                        @endforeach
                    @elseif($linked_type === 'lease')
                        @foreach($leases as $l)
                        <option value="{{ $l->id }}">Lease #{{ $l->id }} – {{ $l->tenant?->contact?->first_name ?? 'Tenant' }}</option>
                        @endforeach
                    @elseif($linked_type === 'listing')
                        @foreach($listings as $li)
                        <option value="{{ $li->id }}">{{ $li->property?->address_line_1 ?? 'Listing #'.$li->id }}</option>
                        @endforeach
                    @elseif($linked_type === 'property')
                        @foreach($properties as $p)
                        <option value="{{ $p->id }}">{{ $p->address_line_1 }}, {{ $p->city }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            @endif
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                <textarea wire:model="doc_notes" rows="2" placeholder="Optional notes..."
                    class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page"></textarea>
            </div>
            <div class="md:col-span-2 flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                    <input wire:model="doc_is_fica" type="checkbox" id="doc_is_fica" class="rounded border-border-default text-brand-primary focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <label for="doc_is_fica" class="text-sm text-text-secondary">FICA required document</label>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-text-secondary mb-1">File * <span class="text-text-tertiary">(max 20 MB)</span></label>
                <input wire:model="doc_file" type="file" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
                @error('doc_file') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                <div wire:loading wire:target="doc_file" class="text-xs text-brand-primary mt-1">Uploading...</div>
            </div>
            <div class="md:col-span-2 flex gap-3">
                <button type="submit" wire:loading.attr="disabled"
                    class="px-5 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="uploadDocument">Save Document</span>
                    <span wire:loading wire:target="uploadDocument">Saving...</span>
                </button>
                <button type="button" wire:click="$set('showUploadForm', false)"
                    class="disabled:opacity-70 disabled:cursor-not-allowed relative px-5 py-2 bg-surface-hover text-text-secondary rounded-xl text-sm font-medium hover:text-text-primary transition-colors" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Cancel</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-5">
        <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by title or type..."
                class="w-full pl-9 pr-4 py-2 rounded-xl border border-border-default bg-surface-input text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
        </div>
        <select wire:model.live="categoryFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Categories</option>
            @foreach(['lease_agreement'=>'Lease Agreements','compliance_record'=>'Compliance Records','inspection_report'=>'Inspection Reports','contract'=>'Contracts','identity'=>'Identity Docs','financial'=>'Financial','other'=>'Other'] as $val=>$label)
            <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Statuses</option>
            <option value="uploaded">Uploaded</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
        <select wire:model.live="expiryFilter" class="rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary">
            <option value="">All Expiry</option>
            <option value="expiring_soon">Expiring Soon (30d)</option>
            <option value="expired">Expired</option>
            <option value="no_expiry">No Expiry</option>
        </select>
    </div>

    {{-- Document Table --}}
    <div class="bg-surface-card rounded-2xl border border-border-default overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border-default">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Document</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider hidden md:table-cell">Category</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider hidden lg:table-cell">Linked To</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider hidden md:table-cell">Expiry</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-default/40">
                @forelse($documents as $doc)
                <tr class="hover:bg-surface-hover/40 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-brand-primary/10 flex items-center justify-center shrink-0">
                                @if(str_contains($doc->mime_type ?? '', 'pdf'))
                                    <svg class="w-4 h-4 text-danger-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </div>
                            <div>
                                <div class="font-medium text-text-primary leading-tight">{{ $doc->title }}</div>
                                <div class="text-xs text-text-tertiary mt-0.5">{{ $doc->file_name ?? '—' }} · {{ $doc->file_size ? number_format($doc->file_size / 1024, 0).' KB' : '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-surface-hover text-text-secondary capitalize">
                            {{ str_replace('_', ' ', $doc->category) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell text-text-secondary text-xs">
                        @if($doc->transaction)
                            <span class="text-brand-primary">Txn: {{ $doc->transaction->reference }}</span>
                        @elseif($doc->lease)
                            <span>Lease #{{ $doc->lease_id }}</span>
                        @elseif($doc->listing?->property)
                            <span>{{ $doc->listing?->property?->address_line_1 ?? 'Listing #'.$doc->listing_id }}</span>
                        @elseif($doc->property)
                            <span>{{ $doc->property->address_line_1 }}</span>
                        @else
                            <span class="text-text-tertiary">Standalone</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell text-xs">
                        @if($doc->expiry_date)
                            @php $expiryStatus = $doc->expiry_status; @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-medium
                                {{ $expiryStatus === 'expired' ? 'bg-danger-50 text-danger-700' : ($expiryStatus === 'expiring_soon' ? 'bg-warning-50 text-warning-700' : 'bg-success-50 text-success-700') }}">
                                @if($expiryStatus === 'expired') &#x26A0; @endif
                                {{ $doc->expiry_date->format('d M Y') }}
                            </span>
                        @else
                            <span class="text-text-tertiary">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $colors = ['approved'=>'success','rejected'=>'danger','under_review'=>'warning','uploaded'=>'info','required'=>'slate'];
                            $color = $colors[$doc->status] ?? 'slate';
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-{{ $color }}-50 text-{{ $color }}-700 capitalize">
                            {{ str_replace('_', ' ', $doc->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @if($doc->url)
                            <a href="{{ $doc->url }}" target="_blank"
                                class="text-xs text-brand-primary hover:underline font-medium">View</a>
                            @endif
                            @if($doc->status !== 'approved')
                            <button wire:click="approveDocument({{ $doc->id }})"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-success-600 hover:underline font-medium" wire:loading.attr="disabled" wire:target="approveDocument">
                <span wire:loading.remove wire:target="approveDocument">Approve</span>
                <span wire:loading wire:target="approveDocument" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            @endif
                            <button wire:click="deleteDocument({{ $doc->id }})"
                                wire:confirm="Delete this document permanently?"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-danger-500 hover:underline font-medium" wire:loading.attr="disabled" wire:target="deleteDocument">
                <span wire:loading.remove wire:target="deleteDocument">Delete</span>
                <span wire:loading wire:target="deleteDocument" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-text-tertiary text-sm">
                        No documents found. Upload your first document above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($documents->hasPages())
    <div class="mt-4">{{ $documents->links() }}</div>
    @endif
</div>



