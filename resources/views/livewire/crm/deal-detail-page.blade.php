<div>
    <!-- Breadcrumb -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('crm.pipeline') }}" class="text-text-tertiary hover:text-brand-primary text-sm flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Pipeline
        </a>
        <span class="text-text-tertiary">/</span>
        <span class="text-sm font-medium text-text-secondary">{{ $deal->title }}</span>
    </div>

    <!-- AI Next Best Action Banner -->
    @if($nextActionSuggestion)
    <div class="mb-5 flex items-start gap-3 p-4 rounded-2xl border border-brand-primary/30 bg-brand-primary/5">
        <div class="shrink-0 h-8 w-8 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary text-sm">✦</div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-brand-primary mb-0.5">AI Suggestion</p>
            <p class="text-sm text-text-primary">{{ $nextActionSuggestion }}</p>
        </div>
        <button wire:click="dismissNextAction" class="shrink-0 text-text-tertiary hover:text-text-secondary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Left: Deal Info + Checklist + Follow-ups -->
        <div class="xl:col-span-1 space-y-5">

            <!-- Deal Card -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h1 class="text-xl font-bold text-text-primary">{{ $deal->title }}</h1>
                        <span class="inline-block mt-1 text-xs font-medium text-text-secondary capitalize">
                            {{ $deal->stage?->name ?? 'No Stage' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Momentum Badge -->
                        <div class="px-2.5 py-1 rounded-lg border text-xs font-bold
                            @if($deal->momentum_score >= 70) bg-success-50 border-success-200 text-success-700
                            @elseif($deal->momentum_score >= 40) bg-warning-50 border-warning-200 text-warning-700
                            @else bg-danger-50 border-danger-200 text-danger-700 @endif">
                            {{ $deal->momentum_score }} · {{ $deal->momentumLabel }}
                        </div>
                        <button wire:click="$toggle('showEditForm')" class="text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-2.5 py-1.5 hover:bg-brand-primary/5 transition-colors">
                            {{ $showEditForm ? 'Cancel' : 'Edit' }}
                        </button>
                    </div>
                </div>

                @if($showEditForm)
                <form wire:submit.prevent="saveDeal" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Title</label>
                        <input wire:model.defer="title" type="text" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('title') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Value (₦)</label>
                        <input wire:model.defer="value" type="number" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Stage</label>
                        <select wire:model.defer="pipeline_stage_id" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            @foreach($stages as $stage)
                            <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Notes</label>
                        <textarea wire:model.defer="notes" rows="2" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                        <span wire:loading.remove wire:target="saveDeal">Save Changes</span>
                        <span wire:loading wire:target="saveDeal">Saving...</span>
                    </button>
                </form>
                @else
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Value</dt>
                        <dd class="font-bold text-text-primary">₦{{ number_format($deal->value) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Type</dt>
                        <dd class="font-medium text-text-primary capitalize">{{ $deal->type }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Agent</dt>
                        <dd class="font-medium text-text-primary">{{ $deal->agent?->first_name ?? 'Unassigned' }}</dd>
                    </div>
                    @if($deal->contact)
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Contact</dt>
                        <dd class="font-medium text-text-primary">
                            <a href="{{ route('crm.contact.detail', $deal->contact) }}" class="text-brand-primary hover:underline">
                                {{ $deal->contact->first_name }} {{ $deal->contact->last_name }}
                            </a>
                        </dd>
                    </div>
                    @endif
                    @if($deal->listing)
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Property</dt>
                        <dd class="font-medium text-text-primary text-right">{{ $deal->listing->property->address_line_1 }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-text-secondary">Last Updated</dt>
                        <dd class="font-medium text-text-primary">{{ $deal->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
                @if($deal->notes)
                <div class="mt-4 pt-4 border-t border-border-default/60">
                    <p class="text-xs font-medium text-text-secondary mb-1">Notes</p>
                    <p class="text-sm text-text-primary">{{ $deal->notes }}</p>
                </div>
                @endif

                <!-- AI Next Action Button -->
                <div class="mt-4 pt-4 border-t border-border-default/60">
                    <button wire:click="loadNextAction"
                        wire:loading.attr="disabled"
                        wire:target="loadNextAction"
                        class="w-full flex items-center justify-center gap-2 py-2 rounded-lg border border-brand-primary/30 text-brand-primary text-xs font-medium hover:bg-brand-primary/5 transition-colors disabled:opacity-50">
                        <span wire:loading.remove wire:target="loadNextAction">✦ Suggest Next Action</span>
                        <span wire:loading wire:target="loadNextAction">Thinking...</span>
                    </button>
                </div>
                @endif
            </div>

            <!-- Stage Checklist -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-3">
                    Stage Checklist
                    @if($deal->checklistItems->count() > 0)
                    <span class="text-xs text-text-secondary ml-1">({{ $deal->checklistItems->where('completed', true)->count() }}/{{ $deal->checklistItems->count() }})</span>
                    @endif
                </h3>

                <div class="space-y-2 mb-3">
                    @forelse($deal->checklistItems as $item)
                    <div class="flex items-center gap-2 group">
                        <button wire:click="toggleChecklistItem({{ $item->id }})"
                            class="flex-shrink-0 h-5 w-5 rounded border flex items-center justify-center transition-colors
                            {{ $item->completed ? 'bg-success-500 border-success-500 text-white' : 'border-border-default hover:border-brand-primary' }}">
                            @if($item->completed)
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </button>
                        <span class="flex-1 text-sm {{ $item->completed ? 'line-through text-text-secondary' : 'text-text-primary' }}">{{ $item->title }}</span>
                        <button wire:click="deleteChecklistItem({{ $item->id }})" class="opacity-0 group-hover:opacity-100 text-danger-400 hover:text-danger-600 transition-opacity">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    @empty
                    <p class="text-xs text-text-secondary">No checklist items. Add tasks below.</p>
                    @endforelse
                </div>

                <form wire:submit.prevent="addChecklistItem" class="flex gap-2">
                    <input wire:model.defer="newChecklistItem" type="text" placeholder="Add checklist item..."
                        class="flex-1 rounded-lg border border-border-default bg-surface-input px-3 py-1.5 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <button type="submit" class="px-3 py-1.5 bg-brand-primary text-white rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors">Add</button>
                </form>
            </div>

            <!-- Follow-up Sequences -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Follow-up Sequences</h3>
                    <button wire:click="$toggle('showFollowUpForm')" class="text-xs text-brand-primary border border-brand-primary/30 rounded-lg px-2.5 py-1.5 hover:bg-brand-primary/5 transition-colors">
                        {{ $showFollowUpForm ? 'Cancel' : '+ New Sequence' }}
                    </button>
                </div>

                @if($showFollowUpForm)
                <form wire:submit.prevent="saveFollowUpSequence" class="space-y-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Sequence Name</label>
                        <input wire:model.defer="followUpName" type="text" placeholder="e.g. Post-viewing follow-up" class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('followUpName') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>

                    @foreach($followUpSteps as $i => $step)
                    <div class="p-3 bg-surface-sunken/40 rounded-xl border border-border-default/40 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-text-secondary">Step {{ $i + 1 }}</span>
                            @if(count($followUpSteps) > 1)
                            <button type="button" wire:click="removeFollowUpStep({{ $i }})" class="text-xs text-danger-500 hover:text-danger-700">Remove</button>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <select wire:model="followUpSteps.{{ $i }}.type" class="rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                <option value="email">Email</option>
                                <option value="call">Call</option>
                                <option value="sms">SMS</option>
                                <option value="task">Task</option>
                            </select>
                            <div class="flex items-center gap-1">
                                <input wire:model.defer="followUpSteps.{{ $i }}.delay_days" type="number" min="0" placeholder="Days" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                                <span class="text-xs text-text-secondary whitespace-nowrap">day(s)</span>
                            </div>
                        </div>
                        <input wire:model.defer="followUpSteps.{{ $i }}.subject" type="text" placeholder="Subject (email only)" class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        <textarea wire:model.defer="followUpSteps.{{ $i }}.message_template" rows="3" placeholder="Message template..." class="w-full rounded-lg border border-border-default bg-surface-input px-2 py-1.5 text-xs text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                        @error("followUpSteps.{$i}.message_template") <span class="text-xs text-danger-600">{{ $message }}</span> @enderror

                        <!-- AI Generate Button -->
                        <button type="button"
                            wire:click="generateStepMessage({{ $i }})"
                            wire:loading.attr="disabled"
                            wire:target="generateStepMessage({{ $i }})"
                            class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg border border-brand-primary/30 text-brand-primary text-xs font-medium hover:bg-brand-primary/5 transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="generateStepMessage({{ $i }})">✦ Generate with AI</span>
                            <span wire:loading wire:target="generateStepMessage({{ $i }})">Generating...</span>
                        </button>
                    </div>
                    @endforeach

                    <div class="flex gap-2">
                        <button type="button" wire:click="addFollowUpStep" class="flex-1 py-1.5 border border-border-default text-text-secondary rounded-lg text-xs font-medium hover:bg-surface-sunken transition-colors">+ Add Step</button>
                        <button type="submit" class="flex-1 py-1.5 bg-brand-primary text-white rounded-lg text-xs font-medium hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="saveFollowUpSequence">Create Sequence</span>
                            <span wire:loading wire:target="saveFollowUpSequence">Saving...</span>
                        </button>
                    </div>
                </form>
                @endif

                @forelse($followUpSequences as $seq)
                <div class="mb-3 p-3 bg-surface-sunken/30 rounded-xl border border-border-default/40">
                    <div class="flex items-center justify-between mb-1.5">
                        <p class="text-xs font-medium text-text-primary">{{ $seq->name }}</p>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase
                            {{ $seq->status === 'active' ? 'bg-success-100 text-success-700' : 'bg-surface-sunken text-text-secondary' }}">
                            {{ $seq->status }}
                        </span>
                    </div>
                    <p class="text-[10px] text-text-secondary">{{ $seq->steps->count() }} steps · Next: {{ $seq->next_action_at?->diffForHumans() ?? 'TBD' }}</p>
                </div>
                @empty
                <p class="text-xs text-text-secondary text-center py-2">No follow-up sequences. Create one to stay on top of this contact.</p>
                @endforelse
            </div>
        </div>

        <!-- Right: Activity Log -->
        <div class="xl:col-span-2 space-y-5">

            <!-- Log Activity -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Log Activity</h3>
                <div class="flex gap-2 mb-3 flex-wrap">
                    @foreach(['note' => 'Note', 'call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting'] as $val => $label)
                    <button wire:click="$set('activityType', '{{ $val }}')"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $activityType === $val ? 'bg-brand-primary text-white' : 'bg-surface-sunken text-text-secondary hover:bg-brand-primary/10' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <form wire:submit.prevent="logActivity" class="space-y-2">
                    <input wire:model.defer="activitySubject" type="text" placeholder="Subject (optional)"
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                    <textarea wire:model.defer="activityBody" rows="3" placeholder="Add notes about this activity..."
                        class="w-full rounded-lg border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    @error('activityBody') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-brand-primary text-white rounded-lg text-sm font-medium hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="logActivity">Log {{ ucfirst($activityType) }}</span>
                            <span wire:loading wire:target="logActivity">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Activity Timeline -->
            <div class="glass-panel rounded-2xl border border-border-default/60 p-5">
                <h3 class="text-sm font-semibold text-text-primary mb-4">Activity Timeline</h3>
                @forelse($deal->activities as $activity)
                <div class="flex gap-3 mb-5 last:mb-0">
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs
                            @switch($activity->type)
                                @case('call') bg-success-100 text-success-700 @break
                                @case('email') bg-info-100 text-info-700 @break
                                @case('meeting') bg-warning-100 text-warning-700 @break
                                @case('system') bg-surface-sunken text-text-tertiary @break
                                @default bg-surface-sunken text-text-secondary
                            @endswitch">
                            @switch($activity->type)
                                @case('call') 📞 @break
                                @case('email') ✉️ @break
                                @case('meeting') 📅 @break
                                @case('system') 🔄 @break
                                @default 📝
                            @endswitch
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-medium text-text-primary capitalize">{{ $activity->subject ?: ucfirst($activity->type) }}</p>
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
                        <p class="mt-1 text-xs text-text-secondary">by {{ $activity->user->first_name }}</p>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-sm text-text-secondary text-center py-6">No activities yet. Log the first one above.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
