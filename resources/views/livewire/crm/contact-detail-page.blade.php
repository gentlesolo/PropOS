<div>
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('crm.contacts') }}" class="text-text-tertiary hover:text-brand-primary text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Contacts
        </a>
        <span class="text-text-tertiary">/</span>
        <span class="text-sm text-text-secondary dark:text-text-secondary font-medium">{{ $contact->full_name }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Profile Card -->
        <div class="lg:col-span-1 space-y-4">
            <div class="glass-panel rounded-2xl border border-border-default/60 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="h-16 w-16 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary text-2xl font-bold">
                        {{ $contact->initials }}
                    </div>
                    <button wire:click="$toggle('showEditForm')" class="text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-3 py-1.5 hover:bg-brand-primary/5 transition-colors">
                        {{ $showEditForm ? 'Cancel' : 'Edit' }}
                    </button>
                </div>

                @if($showEditForm)
                <form wire:submit.prevent="saveContact" class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">First Name</label>
                            <input wire:model.defer="first_name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            @error('first_name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-text-secondary mb-1">Last Name</label>
                            <input wire:model.defer="last_name" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Email</label>
                        <input wire:model.defer="email" type="email" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Phone</label>
                        <input wire:model.defer="phone" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Status</label>
                        <select wire:model.defer="status" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
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
                        <textarea wire:model.defer="notes" rows="3" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
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
                    <div class="mt-4 pt-4 border-t border-border-default/60">
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
                    </div>

                    @if($contact->notes)
                    <div class="mt-4 pt-4 border-t border-border-default/60">
                        <p class="text-xs font-medium text-text-secondary mb-1">Notes</p>
                        <p class="text-sm text-text-primary">{{ $contact->notes }}</p>
                    </div>
                    @endif

                    <div class="mt-4 pt-4 border-t border-border-default/60 text-xs text-text-secondary space-y-1">
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
        </div>

        <!-- Right: Activity Timeline -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Log Activity -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Log Activity</h3>
                </div>

                <!-- Type tabs -->
                <div class="flex gap-2 mb-3 flex-wrap">
                    @foreach(['note' => 'Note', 'call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'sms' => 'SMS'] as $val => $label)
                    <button wire:click="$set('activityType', '{{ $val }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $activityType === $val ? 'bg-brand-primary text-white' : 'bg-surface-sunken text-text-secondary hover:bg-brand-primary/10' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                <form wire:submit.prevent="saveActivity" class="space-y-2">
                    <input wire:model.defer="activitySubject" type="text" placeholder="Subject (optional)"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <textarea wire:model.defer="activityBody" rows="3"
                        placeholder="{{ $activityType === 'call' ? 'Call summary...' : ($activityType === 'email' ? 'Email summary...' : 'Add a note...') }}"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    @error('activityBody') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="saveActivity">Log {{ ucfirst($activityType) }}</span>
                            <span wire:loading wire:target="saveActivity">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Timeline -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
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
                                @case('call') 📞 @break
                                @case('email') ✉️ @break
                                @case('meeting') 📅 @break
                                @case('sms') 💬 @break
                                @case('status_change') 🔄 @break
                                @default 📝
                            @endswitch
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-sm font-medium text-text-primary capitalize">
                                {{ $activity->subject ?: ucfirst($activity->type) }}
                            </p>
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
        </div>
    </div>
</div>
