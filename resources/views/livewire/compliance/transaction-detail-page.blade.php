<div>
    <!-- Breadcrumb -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('compliance.transactions') }}" class="text-text-tertiary hover:text-brand-primary text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Transactions
        </a>
        <span class="text-text-tertiary">/</span>
        <span class="text-sm font-medium text-text-secondary">{{ $transaction->reference }}</span>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Left: Transaction Info + Timeline -->
        <div class="xl:col-span-1 space-y-5">

            <!-- Status Card -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-text-primary">{{ $transaction->reference }}</h2>
                        <p class="text-xs text-text-secondary mt-0.5">{{ $transaction->deal?->title }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                        @if($transaction->status === 'completed') bg-success-100 text-success-700
                        @elseif($transaction->status === 'cancelled') bg-danger-100 text-danger-700
                        @elseif($transaction->status === 'fica_verified') bg-info-100 text-info-700
                        @else bg-warning-100 text-warning-700 @endif">
                        {{ str_replace('_', ' ', $transaction->status) }}
                    </span>
                </div>

                <dl class="space-y-2.5 text-sm mb-4">
                    @if($transaction->contact)
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Client</dt>
                        <dd class="font-medium text-text-primary">{{ $transaction->contact->first_name }} {{ $transaction->contact->last_name }}</dd>
                    </div>
                    @endif
                    @if($transaction->listing)
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Property</dt>
                        <dd class="font-medium text-text-primary text-right max-w-[55%] leading-tight">{{ $transaction->listing->property->address_line_1 }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Sale Price</dt>
                        <dd class="font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($transaction->sale_price) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Agent</dt>
                        <dd class="font-medium text-text-primary">{{ $transaction->agent?->first_name ?? 'Unassigned' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">FICA Progress</dt>
                        <dd class="font-bold text-text-primary">{{ $transaction->ficaProgress }}%</dd>
                    </div>
                </dl>

                <!-- FICA Progress Bar -->
                <div class="w-full bg-surface-raised rounded-full h-2 mb-4">
                    <div class="h-2 rounded-full transition-all duration-500
                        @if($transaction->ficaProgress >= 100) bg-success-500
                        @elseif($transaction->ficaProgress >= 60) bg-warning-500
                        @else bg-danger-400 @endif"
                        style="width: {{ $transaction->ficaProgress }}%"></div>
                </div>

                <!-- Update Status -->
                <div class="pt-4 border-t border-border-default space-y-3">
                    <select wire:model.defer="newStatus" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        @foreach(['initiated', 'fica_pending', 'fica_verified', 'offer_accepted', 'conveyancing', 'registration', 'completed', 'cancelled'] as $s)
                        <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                    <button wire:click="updateStatus" class="w-full py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors">
                        <span wire:loading.remove wire:target="updateStatus">Update Status</span>
                        <span wire:loading wire:target="updateStatus">Updating...</span>
                    </button>
                </div>
            </div>

            <!-- Deadlines Card -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Deadlines</h3>
                    <button wire:click="$toggle('showDeadlineForm')" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-2.5 py-1.5 hover:bg-brand-primary/5 transition-colors" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle">{{ $showDeadlineForm ? 'Cancel' : 'Edit' }}</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>

                @if($showDeadlineForm)
                <form wire:submit.prevent="saveDeadlines" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Compliance Deadline</label>
                        <input wire:model.defer="deadline" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Est. Close Date</label>
                        <input wire:model.defer="estimated_close_date" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                        <textarea wire:model.defer="notes" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none"></textarea>
                    </div>
                    <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl text-sm font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove>Save</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </form>
                @else
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Compliance Deadline</dt>
                        <dd class="font-medium {{ $transaction->isOverdue ? 'text-danger-600' : 'text-text-primary' }}">
                            {{ $transaction->deadline?->format('d M Y') ?? 'Not set' }}
                            @if($transaction->isOverdue) <span class="text-[10px]">&#8358; Overdue</span> @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Est. Close</dt>
                        <dd class="font-medium text-text-primary">{{ $transaction->estimated_close_date?->format('d M Y') ?? 'Not set' }}</dd>
                    </div>
                    @if($transaction->notes)
                    <div class="pt-2 border-t border-border-default/40">
                        <p class="text-xs text-text-secondary mb-1">Notes</p>
                        <p class="text-xs text-text-primary">{{ $transaction->notes }}</p>
                    </div>
                    @endif
                </dl>
                @endif
            </div>

            <!-- Status Timeline -->
            @if($transaction->timeline)
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Status History</h3>
                <div class="space-y-2.5">
                    @foreach(array_reverse($transaction->timeline) as $event)
                    <div class="flex items-start gap-2.5">
                        <div class="h-2 w-2 rounded-full bg-brand-primary mt-1.5 shrink-0"></div>
                        <div>
                            <p class="text-xs font-medium text-text-primary capitalize">{{ str_replace('_', ' ', $event['status']) }}</p>
                            <p class="text-[10px] text-text-secondary">{{ \Carbon\Carbon::parse($event['at'])->diffForHumans() }} &#8358; {{ $event['by'] ?? 'System' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Right: FICA Documents -->
        <div class="xl:col-span-2 space-y-5">

            <!-- Upload Document -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-text-primary">Compliance Documents</h3>
                    <button wire:click="$toggle('showDocForm')" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle">{{ $showDocForm ? 'Cancel' : '+ Add Document' }}</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>

                @if($showDocForm)
                <form wire:submit.prevent="uploadDocument" class="space-y-3 mb-5 p-4 bg-surface-sunken/30 rounded-xl border border-border-default/40">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Document Type</label>
                            <select wire:model.defer="doc_type" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                                <option value="proof_of_identity">Proof of Identity</option>
                                <option value="proof_of_address">Proof of Address</option>
                                <option value="bank_statement">Bank Statement</option>
                                <option value="signed_mandate">Signed Mandate</option>
                                <option value="offer_to_purchase">Offer to Purchase</option>
                                <option value="tax_clearance">Tax Clearance</option>
                                <option value="source_of_funds">Source of Funds</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Title</label>
                            <input wire:model.defer="doc_title" type="text" placeholder="Document title" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                            @error('doc_title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model.defer="doc_is_fica" type="checkbox" class="h-4 w-4 rounded border-border-default text-brand-primary focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                            <span class="text-xs font-medium text-text-primary">FICA Required Document</span>
                        </label>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">File</label>
                        <input wire:model="doc_file" type="file" class="w-full text-sm text-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-primary/10 file:text-brand-primary hover:file:bg-brand-primary/20">
                        @error('doc_file') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="doc_file" class="text-xs text-brand-primary mt-1">Uploading...</div>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                        <span wire:loading.remove wire:target="uploadDocument">Upload Document</span>
                        <span wire:loading wire:target="uploadDocument">Uploading...</span>
                    </button>
                </form>
                @endif

                <!-- Document List -->
                @forelse($transaction->documents as $doc)
                <div class="flex items-center justify-between p-4 bg-surface-sunken/20 rounded-xl border border-border-default/40 mb-3 last:mb-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-10 w-10 bg-brand-primary/10 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-text-primary truncate">{{ $doc->title }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded uppercase
                                    @if($doc->status === 'approved') bg-success-100 text-success-700
                                    @elseif($doc->status === 'rejected') bg-danger-100 text-danger-700
                                    @elseif($doc->status === 'under_review') bg-warning-100 text-warning-700
                                    @else bg-surface-sunken text-text-secondary @endif">
                                    {{ $doc->status }}
                                </span>
                                @if($doc->is_fica_required)
                                <span class="text-[10px] font-medium bg-brand-primary/10 text-brand-primary px-1.5 py-0.5 rounded uppercase">FICA</span>
                                @endif
                                <span class="text-[10px] text-text-secondary">{{ $doc->uploadedBy?->first_name ?? 'System' }} &#8358; {{ $doc->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-3">
                        @if($doc->file_path)
                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="text-xs text-brand-primary hover:text-brand-secondary font-medium">View</a>
                        @endif
                        @if($doc->status === 'uploaded' || $doc->status === 'under_review')
                        <button wire:click="approveDocument({{ $doc->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-success-600 hover:text-success-700 font-medium border border-success-200 rounded-lg px-2 py-1 hover:bg-success-50 transition-colors" wire:loading.attr="disabled" wire:target="approveDocument">
                <span wire:loading.remove wire:target="approveDocument">Approve</span>
                <span wire:loading wire:target="approveDocument" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="rejectDocument({{ $doc->id }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-danger-500 hover:text-danger-700 font-medium border border-danger-200 rounded-lg px-2 py-1 hover:bg-danger-50 transition-colors" wire:loading.attr="disabled" wire:target="rejectDocument">
                <span wire:loading.remove wire:target="rejectDocument">Reject</span>
                <span wire:loading wire:target="rejectDocument" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <p class="text-sm text-text-secondary">No documents uploaded yet. Use the button above to upload FICA and compliance documents.</p>
                </div>
                @endforelse
            </div>

            <!-- Commission Summary (if exists) -->
            @if($transaction->commission)
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-4">Commission Summary</h3>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="text-center p-3 bg-surface-sunken/40 rounded-xl">
                        <p class="text-xl font-bold text-text-primary">{{ $currencySymbol }}{{ number_format($transaction->commission->gross_commission) }}</p>
                        <p class="text-xs text-text-secondary">Gross Commission</p>
                    </div>
                    <div class="text-center p-3 bg-brand-primary/5 rounded-xl">
                        <p class="text-xl font-bold text-brand-primary">{{ $currencySymbol }}{{ number_format($transaction->commission->agency_commission) }}</p>
                        <p class="text-xs text-text-secondary">Agency Net</p>
                    </div>
                    <div class="text-center p-3 bg-success-50 rounded-xl">
                        <p class="text-xl font-bold text-success-600">{{ $currencySymbol }}{{ number_format($transaction->commission->agent_commission) }}</p>
                        <p class="text-xs text-text-secondary">Agent Net</p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase
                        @if($transaction->commission->payment_status === 'paid') bg-success-100 text-success-700
                        @elseif($transaction->commission->payment_status === 'processing') bg-warning-100 text-warning-700
                        @else bg-surface-sunken text-text-secondary @endif">
                        {{ str_replace('_', ' ', $transaction->commission->payment_status) }}
                    </span>
                    <a href="{{ route('finance.commissions') }}" class="text-xs text-brand-primary hover:text-brand-secondary font-medium">View in Ledger ?</a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>



