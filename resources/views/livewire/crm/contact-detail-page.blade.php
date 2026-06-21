<div>
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('crm.contacts') }}" class="text-text-tertiary hover:text-brand-primary text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Contacts
        </a>
        <span class="text-text-tertiary">/</span>
        <span class="text-sm text-text-secondary dark:text-text-secondary font-medium">{{ $contact->full_name }}</span>
    </div>

    <!-- AI Next Best Action Banner -->
    @if($nextActionSuggestion)
    <div class="mb-5 flex items-start gap-3 p-4 rounded-2xl border border-brand-primary/30 bg-brand-primary/5">
        <div class="shrink-0 h-8 w-8 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary text-sm">?</div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-brand-primary mb-0.5">AI Suggestion</p>
            <p class="text-sm text-text-primary">{{ $nextActionSuggestion }}</p>
        </div>
        <button wire:click="dismissNextAction" class="disabled:opacity-70 disabled:cursor-not-allowed relative shrink-0 text-text-tertiary hover:text-text-secondary" wire:loading.attr="disabled" wire:target="dismissNextAction">
                <span wire:loading.remove wire:target="dismissNextAction"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></span>
                <span wire:loading wire:target="dismissNextAction" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Profile Card -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-surface-card rounded-2xl border border-border-default p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="h-16 w-16 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary text-2xl font-bold">
                        {{ $contact->initials }}
                    </div>
                    <div class="flex items-center gap-2">
                        @if($contact->email)
                        <button wire:click="sendEmail"
                                class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-white bg-brand-primary border border-brand-primary rounded-lg px-2.5 py-1.5 hover:opacity-90 transition-colors flex items-center gap-1" wire:loading.attr="disabled" wire:target="sendEmail">
                <span wire:loading.remove wire:target="sendEmail"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Email</span>
                <span wire:loading wire:target="sendEmail" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        @endif
                        <button wire:click="loadNextAction"
                            wire:loading.attr="disabled"
                            wire:target="loadNextAction"
                            class="text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-2.5 py-1.5 hover:bg-brand-primary/5 transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="loadNextAction">? AI</span>
                            <span wire:loading wire:target="loadNextAction">...</span>
                        </button>
                        <button wire:click="$toggle('showEditForm')" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-3 py-1.5 hover:bg-brand-primary/5 transition-colors" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle">{{ $showEditForm ? 'Cancel' : 'Edit' }}</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        <button wire:click="deleteContact"
                            onclick="return confirm('Permanently delete {{ addslashes($contact- wire:loading.attr="disabled" wire:target="deleteContact">
                <span wire:loading.remove wire:target="deleteContact">full_name) }}? This cannot be undone.')"
                            class="text-xs text-danger-600 border border-danger-200 rounded-lg px-2.5 py-1.5 hover:bg-danger-50 transition-colors">
                            Delete</span>
                <span wire:loading wire:target="deleteContact" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    </div>
                </div>

                @if($showEditForm)
                <form wire:submit.prevent="saveContact" class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">First Name</label>
                            <input wire:model.defer="first_name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                            @error('first_name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Last Name</label>
                            <input wire:model.defer="last_name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Email</label>
                        <input wire:model.defer="email" type="email" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Phone</label>
                        <input wire:model.defer="phone" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Status</label>
                        <select wire:model.defer="status" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                            <option value="new">New</option>
                            <option value="active">Active</option>
                            <option value="qualified">Qualified</option>
                            <option value="nurturing">Nurturing</option>
                            <option value="closed">Closed</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                        <textarea wire:model.defer="notes" rows="3" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                        <span wire:loading.remove wire:target="saveContact">Save Changes</span>
                        <span wire:loading wire:target="saveContact">Saving...</span>
                    </button>
                </form>
                @else
                <div>
                    <h2 class="text-xl font-bold text-text-primary">{{ $contact->full_name }}</h2>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-surface-sunken text-text-secondary uppercase tracking-wider">{{ $contact->type }}</span>
                    <div class="mt-4 space-y-2.5 text-sm">
                        @if($contact->email)
                        <div class="flex items-center gap-2 text-text-secondary">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <a href="mailto:{{ $contact->email }}" class="hover:text-brand-primary">{{ $contact->email }}</a>
                        </div>
                        @endif
                        @if($contact->phone)
                        <div class="flex items-center gap-2 text-text-secondary">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>{{ $contact->phone }}</span>
                        </div>
                        @endif
                        <div class="flex items-center gap-2 text-text-secondary">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="capitalize">{{ $contact->status }}</span>
                        </div>
                    </div>

                    <!-- Intent Score -->
                    <div class="mt-4 pt-4 border-t border-border-default">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-xs font-medium text-text-secondary">Intent Score</span>
                            <span class="text-xs font-bold text-text-primary">{{ $contact->intent_score }}%</span>
                        </div>
                        <div class="w-full bg-surface-raised rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-500
                                @if($contact->intent_score >= 80) bg-success-500
                                @elseif($contact->intent_score >= 50) bg-warning-500
                                @else bg-info-400 @endif"
                                style="width: {{ $contact->intent_score }}%">
                            </div>
                        </div>
                        <p class="mt-1 text-[10px] text-text-tertiary">AI-blended score</p>
                    </div>

                    @if($contact->notes)
                    <div class="mt-4 pt-4 border-t border-border-default">
                        <p class="text-xs font-medium text-text-secondary mb-1">Notes</p>
                        <p class="text-sm text-text-primary">{{ $contact->notes }}</p>
                    </div>
                    @endif

                    <!-- Custom Tags -->
                    <div class="mt-4 pt-4 border-t border-border-default">
                        <p class="text-xs font-semibold text-text-secondary mb-2">Custom Tags</p>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            @forelse($contact->tags ?? [] as $tag)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-bold bg-brand-primary/10 text-brand-primary">
                                {{ $tag }}
                                <button type="button" wire:click="removeTag('{{ $tag }}')" class="disabled:opacity-70 disabled:cursor-not-allowed relative hover:text-danger-600 focus:outline-none font-bold" wire:loading.attr="disabled" wire:target="removeTag">
                <span wire:loading.remove wire:target="removeTag">&times;</span>
                <span wire:loading wire:target="removeTag" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            </span>
                            @empty
                            <span class="text-2xs text-text-tertiary">No tags added yet.</span>
                            @endforelse
                        </div>
                        <form wire:submit.prevent="addTag" class="flex gap-1 mt-2">
                            <input wire:model.defer="newTag" type="text" placeholder="Add tag..." class="w-full text-2xs rounded-lg border border-border-default bg-surface-input px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page focus:border-brand-primary">
                            <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-2.5 py-1 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 text-2xs font-bold rounded-lg hover:bg-brand-secondary" wire:loading.attr="disabled">
                <span wire:loading.remove>Add</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        </form>
                    </div>

                    <div class="mt-4 pt-4 border-t border-border-default text-xs text-text-secondary space-y-1">
                        @if($contact->source)
                        <div>Source: <span class="font-medium text-text-primary capitalize">{{ str_replace('_', ' ', $contact->source) }}</span></div>
                        @endif
                        <div>Added: <span class="font-medium text-text-primary">{{ $contact->created_at->diffForHumans() }}</span></div>
                        @if($contact->last_contacted_at)
                        <div>Last contact: <span class="font-medium text-text-primary">{{ $contact->last_contacted_at->diffForHumans() }}</span></div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Buyer Preferences (if applicable) -->
            @if(in_array($contact->type, ['buyer', 'investor', 'tenant']))
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Buyer Preferences</h3>
                    <button wire:click="$toggle('showPreferencesForm')" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-2.5 py-1.5 hover:bg-brand-primary/5 transition-colors" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle">{{ $showPreferencesForm ? 'Cancel' : 'Edit' }}</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
                @if($showPreferencesForm)
                <form wire:submit.prevent="savePreferences" class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Min Budget ({{ $currencySymbol }})</label>
                            <input wire:model.defer="pref_min_budget" type="number" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Max Budget ({{ $currencySymbol }})</label>
                            <input wire:model.defer="pref_max_budget" type="number" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Min Bedrooms</label>
                        <input wire:model.defer="pref_min_bedrooms" type="number" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Preferred Areas <span class="font-normal text-text-tertiary">(comma-separated)</span></label>
                        <input wire:model.defer="pref_areas" type="text" placeholder="Lekki, Victoria Island" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Property Types <span class="font-normal text-text-tertiary">(comma-separated)</span></label>
                        <input wire:model.defer="pref_property_types" type="text" placeholder="Apartment, Detached" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Must-Have Features <span class="font-normal text-text-tertiary">(comma-separated)</span></label>
                        <input wire:model.defer="pref_must_have_features" type="text" placeholder="Pool, Generator" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Target Timeline</label>
                        <input wire:model.defer="pref_timeline" type="text" placeholder="e.g. Next 3 months, Immediate" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Preferences</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </form>
                @else
                @php $prefs = $contact->preferences ?? []; @endphp
                @if(!empty($prefs))
                <dl class="space-y-1.5 text-xs">
                    @if(!empty($prefs['min_budget']) || !empty($prefs['max_budget']))
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Budget</dt>
                        <dd class="font-medium text-text-primary">
                            {{ $currencySymbol }}{{ number_format($prefs['min_budget'] ?? 0) }} � {{ $currencySymbol }}{{ number_format($prefs['max_budget'] ?? 0) }}
                        </dd>
                    </div>
                    @endif
                    @if(!empty($prefs['min_bedrooms']))
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Min beds</dt>
                        <dd class="font-medium text-text-primary">{{ $prefs['min_bedrooms'] }}+</dd>
                    </div>
                    @endif
                    @if(!empty($prefs['areas']))
                    <div class="flex justify-between gap-2">
                        <dt class="text-text-secondary shrink-0">Areas</dt>
                        <dd class="font-medium text-text-primary text-right">{{ implode(', ', (array) $prefs['areas']) }}</dd>
                    </div>
                    @endif
                    @if(!empty($prefs['property_types']))
                    <div class="flex justify-between gap-2">
                        <dt class="text-text-secondary shrink-0">Types</dt>
                        <dd class="font-medium text-text-primary text-right">{{ implode(', ', (array) $prefs['property_types']) }}</dd>
                    </div>
                    @endif
                    @if(!empty($prefs['timeline']))
                    <div class="flex justify-between gap-2">
                        <dt class="text-text-secondary shrink-0">Timeline</dt>
                        <dd class="font-medium text-text-primary text-right">{{ $prefs['timeline'] }}</dd>
                    </div>
                    @endif
                </dl>
                @else
                <p class="text-xs text-text-tertiary">No preferences set.</p>
                @endif
                @endif
            </div>

            <!-- Matched Listings -->
            @if($matchedListings->isNotEmpty())
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Matched Listings <span class="text-text-tertiary font-normal">({{ $matchedListings->count() }})</span></h3>
                <div class="space-y-2">
                    @foreach($matchedListings->take(5) as $match)
                    <div class="p-2.5 rounded-lg bg-surface-sunken/40 border border-border-default/30">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-medium text-text-primary truncate">{{ $match['listing']->property->address_line_1 ?? 'Property' }}</p>
                            <span class="shrink-0 ml-2 text-[10px] font-bold text-brand-primary bg-brand-primary/10 rounded px-1.5 py-0.5">{{ $match['score'] }}%</span>
                        </div>
                        <p class="text-[10px] text-text-secondary">{{ implode(' � ', $match['reasons']) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endif

            <!-- Family Details -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Family Details</h3>
                    <button wire:click="$toggle('showFamilyForm')" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-2.5 py-1 hover:bg-brand-primary/5 transition-colors font-bold" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle">{{ $showFamilyForm ? 'Cancel' : 'Edit' }}</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
                @if($showFamilyForm)
                <form wire:submit.prevent="saveFamily" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Partner's Name</label>
                        <input wire:model.defer="fam_partner_name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Anniversary</label>
                        <input wire:model.defer="fam_anniversary" type="date" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-text-secondary">Children</span>
                            <button type="button" wire:click="addFamilyChild" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-brand-primary hover:underline font-bold" wire:loading.attr="disabled" wire:target="addFamilyChild">
                <span wire:loading.remove wire:target="addFamilyChild">+ Add Child</span>
                <span wire:loading wire:target="addFamilyChild" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        </div>
                        @foreach($fam_children as $index => $child)
                        <div class="p-2 border border-border-default/40 rounded-lg bg-surface-sunken/20 space-y-1.5 relative">
                            <button type="button" wire:click="removeFamilyChild({{ $index }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative absolute top-1.5 right-1.5 text-text-tertiary hover:text-danger-600 text-xs font-bold" wire:loading.attr="disabled" wire:target="removeFamilyChild">
                <span wire:loading.remove wire:target="removeFamilyChild">&times;</span>
                <span wire:loading wire:target="removeFamilyChild" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            <input wire:model.defer="fam_children.{{ $index }}.name" type="text" placeholder="Name" class="w-full rounded bg-surface-input border border-border-default px-2 py-1 text-2xs text-text-primary">
                            <div class="grid grid-cols-2 gap-1.5">
                                <input wire:model.defer="fam_children.{{ $index }}.birthday" type="date" placeholder="Birthday" class="w-full rounded bg-surface-input border border-border-default px-2 py-1 text-2xs text-text-primary">
                                <input wire:model.defer="fam_children.{{ $index }}.school" type="text" placeholder="School" class="w-full rounded bg-surface-input border border-border-default px-2 py-1 text-2xs text-text-primary">
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-1.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove>Save Family Details</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </form>
                @else
                @php $prefs = $contact->preferences ?? []; $family = $prefs['family'] ?? []; @endphp
                @if(!empty($family['partner_name']) || !empty($family['anniversary']) || !empty($family['children']))
                <dl class="space-y-1.5 text-xs">
                    @if(!empty($family['partner_name']))
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Partner</dt>
                        <dd class="font-medium text-text-primary">{{ $family['partner_name'] }}</dd>
                    </div>
                    @endif
                    @if(!empty($family['anniversary']))
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Anniversary</dt>
                        <dd class="font-medium text-text-primary">{{ $family['anniversary'] }}</dd>
                    </div>
                    @endif
                    @if(!empty($family['children']))
                    <div class="pt-1.5 border-t border-border-default/40">
                        <dt class="text-text-secondary font-semibold mb-1">Children</dt>
                        @foreach($family['children'] as $child)
                        <dd class="pl-2 border-l border-brand-primary/30 py-0.5 text-text-primary mb-1 last:mb-0">
                            <strong>{{ $child['name'] }}</strong>
                            @if(!empty($child['birthday']) || !empty($child['school']))
                            <span class="text-text-tertiary block text-[10px]">
                                {{ $child['birthday'] ? 'BD: ' . $child['birthday'] : '' }}
                                {{ $child['school'] ? ' � ' . $child['school'] : '' }}
                            </span>
                            @endif
                        </dd>
                        @endforeach
                    </div>
                    @endif
                </dl>
                @else
                <p class="text-xs text-text-tertiary">No family details set.</p>
                @endif
                @endif
            </div>

            <!-- Property Ownership History -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Ownership History</h3>
                    <button wire:click="$toggle('showHistoryForm')" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-2.5 py-1 hover:bg-brand-primary/5 transition-colors font-bold" wire:loading.attr="disabled" wire:target="$toggle">
                <span wire:loading.remove wire:target="$toggle">{{ $showHistoryForm ? 'Cancel' : 'Edit' }}</span>
                <span wire:loading wire:target="$toggle" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </div>
                @if($showHistoryForm)
                <form wire:submit.prevent="saveHistory" class="space-y-3">
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-text-secondary">Past Properties</span>
                            <button type="button" wire:click="addHistoryItem" class="disabled:opacity-70 disabled:cursor-not-allowed relative text-xs text-brand-primary hover:underline font-bold" wire:loading.attr="disabled" wire:target="addHistoryItem">
                <span wire:loading.remove wire:target="addHistoryItem">+ Add Property</span>
                <span wire:loading wire:target="addHistoryItem" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        </div>
                        @foreach($history_items as $index => $item)
                        <div class="p-2 border border-border-default/40 rounded-lg bg-surface-sunken/20 space-y-1.5 relative">
                            <button type="button" wire:click="removeHistoryItem({{ $index }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative absolute top-1.5 right-1.5 text-text-tertiary hover:text-danger-600 text-xs font-bold" wire:loading.attr="disabled" wire:target="removeHistoryItem">
                <span wire:loading.remove wire:target="removeHistoryItem">&times;</span>
                <span wire:loading wire:target="removeHistoryItem" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                            <input wire:model.defer="history_items.{{ $index }}.address" type="text" placeholder="Address / Property Name" class="w-full rounded bg-surface-input border border-border-default px-2 py-1 text-2xs text-text-primary">
                            <div class="grid grid-cols-3 gap-1">
                                <input wire:model.defer="history_items.{{ $index }}.price" type="text" placeholder="Price" class="w-full rounded bg-surface-input border border-border-default px-2 py-1 text-2xs text-text-primary">
                                <input wire:model.defer="history_items.{{ $index }}.year_acquired" type="text" placeholder="Acquired" class="w-full rounded bg-surface-input border border-border-default px-2 py-1 text-2xs text-text-primary">
                                <input wire:model.defer="history_items.{{ $index }}.year_sold" type="text" placeholder="Sold" class="w-full rounded bg-surface-input border border-border-default px-2 py-1 text-2xs text-text-primary">
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-1.5 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove>Save History</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                </form>
                @else
                @php $prefs = $contact->preferences ?? []; $history = $prefs['ownership_history'] ?? []; @endphp
                @if(!empty($history))
                <div class="space-y-2">
                    @foreach($history as $item)
                    <div class="p-2 border border-border-default/30 rounded-lg bg-surface-sunken/10">
                        <p class="text-xs font-bold text-text-primary truncate">{{ $item['address'] }}</p>
                        <p class="text-[10px] text-text-secondary mt-0.5">
                            @if(!empty($item['price'])) Value: {{ $currencySymbol }}{{ number_format((float)$item['price']) }} @endif
                            @if(!empty($item['year_acquired'])) � Acquired: {{ $item['year_acquired'] }} @endif
                            @if(!empty($item['year_sold'])) � Sold: {{ $item['year_sold'] }} @endif
                        </p>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-xs text-text-tertiary">No property ownership history recorded.</p>
                @endif
                @endif
            </div>

            <!-- Documents -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Documents</h3>
                
                @php $prefs = $contact->preferences ?? []; $documents = $prefs['documents'] ?? []; @endphp
                @if(!empty($documents))
                <ul class="divide-y divide-border-default/40 mb-4">
                    @foreach($documents as $index => $doc)
                    <li class="py-2 flex items-center justify-between gap-2 text-xs">
                        <div class="min-w-0">
                            <p class="font-bold text-text-primary truncate">{{ $doc['title'] }}</p>
                            <span class="text-[10px] text-text-tertiary">{{ $doc['file_name'] }} � {{ \Carbon\Carbon::parse($doc['uploaded_at'])->diffForHumans() }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="{{ Storage::url($doc['file_path']) }}" target="_blank" class="p-1 text-brand-primary hover:bg-brand-primary/10 rounded">
                                ??
                            </a>
                            <button type="button" wire:click="deleteDocument({{ $index }})" class="disabled:opacity-70 disabled:cursor-not-allowed relative p-1 text-text-tertiary hover:text-danger-600 rounded" wire:loading.attr="disabled" wire:target="deleteDocument">
                <span wire:loading.remove wire:target="deleteDocument">???</span>
                <span wire:loading wire:target="deleteDocument" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-xs text-text-tertiary mb-4">No documents uploaded.</p>
                @endif

                <form wire:submit.prevent="uploadDocument" class="space-y-2 pt-2 border-t border-border-default/40">
                    <input wire:model.defer="docTitle" type="text" placeholder="Doc title (e.g. ID Card)" class="w-full rounded bg-surface-input border border-border-default px-2 py-1 text-xs text-text-primary focus:outline-none focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <div class="flex items-center gap-2">
                        <input wire:model="docFile" type="file" class="text-2xs text-text-secondary w-full">
                        <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative px-2.5 py-1 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 text-xs font-semibold rounded-lg hover:bg-brand-secondary shrink-0" wire:loading.attr="disabled">
                <span wire:loading.remove>Upload</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    </div>
                    @error('docFile') <span class="text-2xs text-danger-600 block">{{ $message }}</span> @enderror
                    @error('docTitle') <span class="text-2xs text-danger-600 block">{{ $message }}</span> @enderror
                </form>
            </div>
        </div>

        <!-- Right: Activity Timeline -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Log Activity -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Log Activity</h3>
                </div>

                <div class="flex gap-2 mb-3 flex-wrap">
                    @foreach(['note' => 'Note', 'call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'sms' => 'SMS'] as $val => $label)
                    <button wire:click="$set('activityType', '{{ $val }}')"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $activityType === $val ? 'bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10' : 'bg-surface-sunken text-text-secondary hover:bg-brand-primary/10' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">{{ $label }}</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endforeach
                </div>

                <form wire:submit.prevent="saveActivity" class="space-y-2">
                    <input wire:model.defer="activitySubject" type="text" placeholder="Subject (optional)"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
                    <textarea wire:model.defer="activityBody" rows="3"
                        placeholder="{{ $activityType === 'call' ? 'Call summary...' : ($activityType === 'email' ? 'Email summary...' : 'Add a note...') }}"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page resize-none"></textarea>
                    @error('activityBody') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="saveActivity">Log {{ ucfirst($activityType) }}</span>
                            <span wire:loading wire:target="saveActivity">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab switcher: Activity / Emails -->
            <div class="flex gap-1 p-1 bg-surface-hover/50 rounded-xl w-fit">
                <button wire:click="$set('activeTab', 'activity')"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'activity' ? 'bg-white text-text-primary shadow-sm dark:bg-surface-card' : 'text-text-secondary hover:text-text-primary' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Activity</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                <button wire:click="$set('activeTab', 'emails')"
                        class="disabled:opacity-70 disabled:cursor-not-allowed relative px-4 py-1.5 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'emails' ? 'bg-white text-text-primary shadow-sm dark:bg-surface-card' : 'text-text-secondary hover:text-text-primary' }}" wire:loading.attr="disabled" wire:target="$set">
                <span wire:loading.remove wire:target="$set">Emails
                    @if($emailThreads->sum('unread_count') > 0)
                    <span class="ml-1 px-1.5 py-0.5 bg-brand-primary text-white text-xs rounded-full">{{ $emailThreads->sum('unread_count') }}</span>
                    @endif</span>
                <span wire:loading wire:target="$set" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </div>

            @if($activeTab === 'emails')
            <!-- Emails tab -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-text-primary">Email Threads</h3>
                    @if($contact->email)
                    <button wire:click="sendEmail"
                            class="disabled:opacity-70 disabled:cursor-not-allowed relative inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-primary text-white text-xs font-medium rounded-lg hover:opacity-90 transition" wire:loading.attr="disabled" wire:target="sendEmail">
                <span wire:loading.remove wire:target="sendEmail"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        New Email</span>
                <span wire:loading wire:target="sendEmail" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @endif
                </div>

                @forelse($emailThreads as $thread)
                <div class="border border-border-default rounded-xl overflow-hidden">
                    <div class="px-4 py-3 bg-surface-elevated flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                @if($thread->unread_count > 0)
                                <span class="w-2 h-2 rounded-full bg-brand-primary shrink-0"></span>
                                @endif
                                <p class="text-sm font-semibold text-text-primary truncate">{{ $thread->subject }}</p>
                            </div>
                            <p class="text-xs text-text-tertiary mt-0.5">{{ $thread->last_message_at?->diffForHumans() }} · {{ $thread->messages->count() }} message(s)</p>
                        </div>
                        <a href="{{ route('marketing.inbox') }}"
                           class="text-xs text-brand-primary hover:underline shrink-0">
                            Open →
                        </a>
                    </div>

                    {{-- Latest message preview --}}
                    @php $latest = $emailLogs->where('thread_id', $thread->id)->last(); @endphp
                    @if($latest)
                    <div class="px-4 py-2.5">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-medium text-text-secondary">{{ $latest->sender_label }}</span>
                            <span class="text-xs text-text-tertiary">{{ ($latest->sent_at ?? $latest->created_at)->format('d M H:i') }}</span>
                            <span class="text-xs px-1.5 py-0.5 rounded {{ $latest->direction === 'inbound' ? 'bg-green-50 text-green-700' : 'bg-brand-primary/10 text-brand-primary' }}">
                                {{ $latest->direction === 'inbound' ? 'Received' : 'Sent' }}
                            </span>
                        </div>
                        <p class="text-sm text-text-secondary line-clamp-2">
                            {{ $latest->body_text ? \Illuminate\Support\Str::limit($latest->body_text, 120) : \Illuminate\Support\Str::limit(strip_tags($latest->body_html ?? $latest->subject), 120) }}
                        </p>
                    </div>
                    @endif
                </div>
                @empty
                <div class="text-center py-8 text-text-tertiary">
                    <svg class="w-8 h-8 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm">No emails with this contact yet.</p>
                    @if($contact->email)
                    <button wire:click="sendEmail" class="disabled:opacity-70 disabled:cursor-not-allowed relative mt-2 text-xs text-brand-primary hover:underline" wire:loading.attr="disabled" wire:target="sendEmail">
                <span wire:loading.remove wire:target="sendEmail">Send the first email →</span>
                <span wire:loading wire:target="sendEmail" class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
                    @else
                    <p class="text-xs mt-1">Add an email address to this contact to start emailing.</p>
                    @endif
                </div>
                @endforelse
            </div>
            @else
            <!-- Timeline -->
            <div class="bg-surface-card rounded-2xl border border-border-default p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-4">Activity Timeline</h3>

                @forelse($activities as $activity)
                <div class="flex gap-3 mb-5 last:mb-0">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs
                            @switch($activity->type)
                                @case('call') bg-success-100 text-success-700 @break
                                @case('email') bg-info-100 text-info-700 @break
                                @case('meeting') bg-warning-100 text-warning-700 @break
                                @case('sms') bg-brand-primary/10 text-brand-primary @break
                                @case('status_change') bg-surface-sunken text-text-secondary @break
                                @default bg-surface-sunken text-text-secondary
                            @endswitch">
                            @switch($activity->type)
                                @case('call') ?? @break
                                @case('email') ?? @break
                                @case('meeting') ?? @break
                                @case('sms') ?? @break
                                @case('status_change') ?? @break
                                @default ??
                            @endswitch
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-text-primary capitalize">
                                    {{ $activity->subject ?: ucfirst($activity->type) }}
                                </p>
                                @php $sentiment = $activity->metadata['sentiment'] ?? null; @endphp
                                @if($sentiment)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold
                                    @if($sentiment === 'positive') bg-success-100 text-success-700
                                    @elseif($sentiment === 'negative') bg-danger-100 text-danger-700
                                    @elseif($sentiment === 'urgent') bg-warning-100 text-warning-700
                                    @else bg-surface-sunken text-text-secondary @endif">
                                    {{ ucfirst($sentiment) }}
                                </span>
                                @endif
                            </div>
                            <span class="text-xs text-text-secondary shrink-0">{{ $activity->occurred_at->diffForHumans() }}</span>
                        </div>
                        @if($activity->body)
                        <p class="mt-0.5 text-sm text-text-secondary">{{ $activity->body }}</p>
                        @endif
                        @if($activity->user)
                        <p class="mt-1 text-xs text-text-secondary">by {{ $activity->user->first_name }} {{ $activity->user->last_name }}</p>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <p class="text-sm text-text-secondary">No activities yet. Log the first interaction above.</p>
                </div>
                @endforelse
            </div>
            @endif

        </div>
    </div>
</div>



