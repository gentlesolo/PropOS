<div class="space-y-8 max-w-5xl">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-text-primary">Email Accounts</h1>
            <p class="text-sm text-text-secondary mt-1">Connect mailboxes so you can send and receive email directly inside the platform.</p>
        </div>
        <button wire:click="openCreate"
                class="px-4 py-2 bg-brand-primary text-white text-sm font-medium rounded-lg hover:opacity-90">
            + Connect Account
        </button>
    </div>

    {{-- Account list --}}
    @if($accounts->isEmpty())
    <div class="text-center py-16 text-text-secondary">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <p class="font-medium">No email accounts connected yet.</p>
        <p class="text-sm mt-1">Click "Connect Account" to add your first mailbox.</p>
    </div>
    @else
    <div class="space-y-3">
        @foreach($accounts as $account)
        <div class="bg-surface-card border border-border-default rounded-xl p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-brand-primary/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-text-primary text-sm">{{ $account->name }}</span>
                            @if($account->is_default)
                            <span class="px-2 py-0.5 bg-brand-primary/10 text-brand-primary text-xs rounded-full font-medium">Default</span>
                            @endif
                            @if($account->is_shared)
                            <span class="px-2 py-0.5 bg-surface-elevated text-text-secondary text-xs rounded-full">Shared</span>
                            @endif
                            @if(! $account->is_active)
                            <span class="px-2 py-0.5 bg-red-50 text-red-600 text-xs rounded-full">Paused</span>
                            @endif
                        </div>
                        <p class="text-xs text-text-secondary mt-0.5">{{ $account->email_address }}</p>
                        @if($account->sync_error)
                        <p class="text-xs text-red-500 mt-1">⚠ {{ Str::limit($account->sync_error, 80) }}</p>
                        @elseif($account->last_synced_at)
                        <p class="text-xs text-text-tertiary mt-1">Last synced {{ $account->last_synced_at->diffForHumans() }}</p>
                        @else
                        <p class="text-xs text-text-tertiary mt-1">Never synced</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button wire:click="syncNow({{ $account->id }})"
                            class="px-3 py-1.5 text-xs font-medium text-text-secondary border border-border-default rounded-lg hover:bg-surface-elevated">
                        Sync now
                    </button>
                    <button wire:click="openEdit({{ $account->id }})"
                            class="px-3 py-1.5 text-xs font-medium text-text-secondary border border-border-default rounded-lg hover:bg-surface-elevated">
                        Edit
                    </button>
                    <button wire:click="toggleActive({{ $account->id }})"
                            class="px-3 py-1.5 text-xs font-medium text-text-secondary border border-border-default rounded-lg hover:bg-surface-elevated">
                        {{ $account->is_active ? 'Pause' : 'Resume' }}
                    </button>
                    <button wire:click="delete({{ $account->id }})"
                            wire:confirm="Remove this account? Synced emails will remain."
                            class="px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                        Remove
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Create / Edit Modal --}}
    @if($showForm)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-surface-card rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-border-default flex items-center justify-between">
                <h2 class="text-lg font-semibold text-text-primary">
                    {{ $editingId ? 'Edit Email Account' : 'Connect Email Account' }}
                </h2>
                <button wire:click="$set('showForm', false)" class="text-text-tertiary hover:text-text-primary">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-5">
                {{-- Basic info --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-sm font-medium text-text-primary mb-1">Account Name *</label>
                        <input type="text" wire:model="name" placeholder="e.g. Agency Inbox"
                               class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page focus:ring-2 focus:ring-brand-primary focus:border-brand-primary">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-sm font-medium text-text-primary mb-1">Email Address *</label>
                        <input type="email" wire:model="email_address" placeholder="hello@youroffice.com"
                               class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page focus:ring-2 focus:ring-brand-primary focus:border-brand-primary">
                        @error('email_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_shared" class="rounded border-border-default text-brand-primary">
                        <span class="text-sm text-text-primary">Shared agency inbox (visible to all agents)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="is_default" class="rounded border-border-default text-brand-primary">
                        <span class="text-sm text-text-primary">Set as default sending account</span>
                    </label>
                </div>

                {{-- IMAP --}}
                <div>
                    <h3 class="text-sm font-semibold text-text-primary mb-3">IMAP Settings (Incoming)</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-3 sm:col-span-1">
                            <label class="block text-xs text-text-secondary mb-1">Host *</label>
                            <input type="text" wire:model="imap_host" placeholder="imap.gmail.com"
                                   class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page">
                            @error('imap_host') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-text-secondary mb-1">Port *</label>
                            <input type="number" wire:model="imap_port"
                                   class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page">
                        </div>
                        <div>
                            <label class="block text-xs text-text-secondary mb-1">Encryption *</label>
                            <select wire:model="imap_encryption" class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page">
                                <option value="ssl">SSL</option>
                                <option value="tls">TLS</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- SMTP --}}
                <div>
                    <h3 class="text-sm font-semibold text-text-primary mb-1">SMTP Settings (Outgoing) <span class="font-normal text-text-tertiary">— optional, overrides agency default</span></h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-3 sm:col-span-1">
                            <label class="block text-xs text-text-secondary mb-1">Host</label>
                            <input type="text" wire:model="smtp_host" placeholder="smtp.gmail.com"
                                   class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page">
                        </div>
                        <div>
                            <label class="block text-xs text-text-secondary mb-1">Port</label>
                            <input type="number" wire:model="smtp_port"
                                   class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page">
                        </div>
                        <div>
                            <label class="block text-xs text-text-secondary mb-1">Encryption</label>
                            <select wire:model="smtp_encryption" class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Credentials --}}
                <div>
                    <h3 class="text-sm font-semibold text-text-primary mb-3">Credentials</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-text-secondary mb-1">Username / Email *</label>
                            <input type="text" wire:model="username" placeholder="you@gmail.com"
                                   class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page">
                            @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-text-secondary mb-1">
                                Password {{ $editingId ? '(leave blank to keep current)' : '*' }}
                            </label>
                            <input type="password" wire:model="password" placeholder="App password or IMAP password"
                                   class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page">
                            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-xs text-text-tertiary mt-2">For Gmail/Outlook, use an app-specific password. Credentials are stored encrypted.</p>
                </div>

                {{-- Email Signature --}}
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Email Signature <span class="font-normal text-text-tertiary">(optional HTML)</span></label>
                    <textarea wire:model="email_signature_html" rows="3"
                              placeholder="<p>Best regards,<br><strong>Your Name</strong></p>"
                              class="w-full border border-border-default rounded-lg px-3 py-2 text-sm bg-surface-page font-mono"></textarea>
                </div>

                {{-- Test connection --}}
                @if($testResult)
                <div class="rounded-lg px-4 py-3 text-sm {{ $testPassed ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
                    {{ $testResult }}
                </div>
                @endif

                <div class="flex items-center justify-between pt-2">
                    <button wire:click="testConnection" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium border border-border-default rounded-lg text-text-primary hover:bg-surface-elevated">
                        <span wire:loading.remove wire:target="testConnection">Test IMAP Connection</span>
                        <span wire:loading wire:target="testConnection">Testing...</span>
                    </button>
                    <div class="flex gap-3">
                        <button wire:click="$set('showForm', false)"
                                class="px-4 py-2 text-sm font-medium border border-border-default rounded-lg text-text-secondary hover:bg-surface-elevated">
                            Cancel
                        </button>
                        <button wire:click="save" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium bg-brand-primary text-white rounded-lg hover:opacity-90">
                            <span wire:loading.remove wire:target="save">Save Account</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
