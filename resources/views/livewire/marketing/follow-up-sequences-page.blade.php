<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Follow-Up Sequences</h1>
            <p class="mt-2 text-text-secondary">Automated email, SMS, and task touches to nurture lead relationships.</p>
        </div>
        <div>
            <button wire:click="$toggle('showCreateForm')" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary transition-colors">
                {{ $showCreateForm ? 'Cancel Creation' : '+ New Sequence' }}
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-8">
        @foreach(['total' => 'Total', 'active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed'] as $key => $label)
        <div class="glass-panel p-5 rounded-2xl border border-border-default/60 text-center">
            <p class="text-2xl font-extrabold text-text-primary">{{ $stats[$key] }}</p>
            <p class="text-xs font-medium text-text-secondary mt-1">{{ $label }}</p>
        </div>
        @endforeach
    </div>

    @if($showCreateForm)
    <!-- Creation Wizard Form -->
    <div class="glass-panel rounded-2xl border border-border-default/60 shadow-md p-6 mb-8 bg-white/70">
        <h2 class="text-lg font-bold text-text-primary mb-4">Create Nurture Sequence</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-xs font-bold text-text-secondary uppercase mb-2">Preset Template</label>
                <select wire:model.live="selectedTemplate" wire:change="applyTemplate" class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white focus:ring-2 focus:ring-brand-primary text-sm">
                    <option value="">-- Custom (Start from scratch) --</option>
                    <option value="new_lead">New Lead Welcome Sequence</option>
                    <option value="post_viewing">Post-Viewing Feedback Loop</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-text-secondary uppercase mb-2">Assign Contact</label>
                <select wire:model="contact_id" class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white focus:ring-2 focus:ring-brand-primary text-sm">
                    <option value="">Select a contact...</option>
                    @foreach($contacts as $contact)
                    <option value="{{ $contact->id }}">{{ $contact->first_name }} {{ $contact->last_name }} ({{ ucfirst($contact->type) }})</option>
                    @endforeach
                </select>
                @error('contact_id') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-xs font-bold text-text-secondary uppercase mb-2">Sequence Name</label>
            <input wire:model="name" type="text" placeholder="e.g. 3-Touch Cold Buyer Nurture" class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white focus:ring-2 focus:ring-brand-primary text-sm">
            @error('name') <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <!-- Dynamic Steps -->
        <div class="space-y-4 mb-6">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-text-primary">Sequence Steps</h3>
                <button type="button" wire:click="addEmptyStep" class="text-xs text-brand-primary hover:underline font-bold">+ Add Step</button>
            </div>

            @foreach($steps as $index => $step)
            <div class="p-4 bg-surface-sunken/40 rounded-xl border border-border-default/60 relative">
                <button type="button" wire:click="removeStep({{ $index }})" class="absolute top-4 right-4 text-xs text-danger-500 hover:text-danger-700 font-medium">Remove</button>
                
                <p class="text-xs font-bold text-brand-primary mb-3">Step #{{ $index + 1 }}</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1">Channel / Type</label>
                        <select wire:model="steps.{{ $index }}.type" class="w-full px-2 py-1.5 border border-border-strong rounded-lg bg-white text-xs">
                            <option value="email">Email Message</option>
                            <option value="sms">WhatsApp / SMS</option>
                            <option value="call">Phone Call Task</option>
                            <option value="task">General Reminder Task</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1">Delay (Days)</label>
                        <input wire:model="steps.{{ $index }}.delay_days" type="number" min="0" class="w-full px-2 py-1.5 border border-border-strong rounded-lg bg-white text-xs">
                    </div>
                    @if(($steps[$index]['type'] ?? 'email') === 'email')
                    <div>
                        <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1">Email Subject</label>
                        <input wire:model="steps.{{ $index }}.subject" type="text" placeholder="e.g. Following up" class="w-full px-2 py-1.5 border border-border-strong rounded-lg bg-white text-xs">
                    </div>
                    @endif
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-text-secondary uppercase mb-1">Message Template / Description</label>
                    <textarea wire:model="steps.{{ $index }}.message_template" rows="2" placeholder="Write message body or task description..." class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white text-xs"></textarea>
                    @error("steps.{$index}.message_template") <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-3">
            <button type="button" wire:click="$set('showCreateForm', false)" class="px-4 py-2 border border-border-default rounded-xl text-sm font-medium hover:bg-surface-sunken">Cancel</button>
            <button type="button" wire:click="createSequence" class="px-4 py-2 bg-brand-primary text-white rounded-xl text-sm font-bold hover:bg-brand-secondary">Start Sequence</button>
        </div>
    </div>
    @endif

    <!-- Search and Filter -->
    <div class="glass-panel rounded-2xl border border-border-default/60 overflow-hidden shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-border-default/60 flex items-center justify-between bg-surface-sunken/30 gap-4">
            <div class="w-1/3">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search sequences..."
                    class="w-full px-3 py-2 border border-border-strong rounded-lg bg-white/50 focus:ring-2 focus:ring-brand-primary text-sm">
            </div>
            <div class="flex gap-2">
                @foreach(['' => 'All', 'active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
                <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                    {{ $statusFilter === $val ? 'bg-brand-primary text-white' : 'bg-surface-card border border-border-default/60 text-text-secondary hover:bg-surface-sunken' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-border-default text-left text-xs font-bold text-text-secondary uppercase">
                            <th class="pb-3 pl-2">Sequence</th>
                            <th class="pb-3">Contact</th>
                            <th class="pb-3 text-center">Steps</th>
                            <th class="pb-3">Next Step Trigger</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3 pr-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/40">
                        @forelse($sequences as $seq)
                        <tr class="hover:bg-surface-sunken/20 transition-colors">
                            <td class="py-4 pl-2 font-bold text-sm text-text-primary">{{ $seq->name }}</td>
                            <td class="py-4 text-sm text-text-secondary">
                                <a href="{{ route('crm.contact.detail', $seq->contact_id) }}" class="text-brand-primary hover:underline font-medium">
                                    {{ $seq->contact->first_name }} {{ $seq->contact->last_name }}
                                </a>
                            </td>
                            <td class="py-4 text-center">
                                <span class="px-2 py-1 bg-surface-sunken text-xs rounded-lg font-bold">
                                    {{ $seq->current_step }} / {{ $seq->steps->count() }}
                                </span>
                            </td>
                            <td class="py-4 text-sm text-text-secondary">
                                @if($seq->status === 'active' && $seq->next_action_at)
                                    {{ $seq->next_action_at->format('d M Y H:i') }}
                                @else
                                    <span class="text-text-secondary/60">-</span>
                                @endif
                            </td>
                            <td class="py-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                    @if($seq->status === 'active') bg-success-100 text-success-700
                                    @elseif($seq->status === 'paused') bg-warning-100 text-warning-700
                                    @elseif($seq->status === 'completed') bg-info-100 text-info-700
                                    @else bg-surface-sunken text-text-secondary @endif">
                                    {{ $seq->status }}
                                </span>
                            </td>
                            <td class="py-4 pr-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($seq->status === 'active')
                                    <button wire:click="pauseSequence({{ $seq->id }})" class="px-2 py-1 text-xs font-semibold text-warning-600 border border-warning-200 hover:bg-warning-50 rounded-lg">Pause</button>
                                    @elseif($seq->status === 'paused')
                                    <button wire:click="resumeSequence({{ $seq->id }})" class="px-2 py-1 text-xs font-semibold text-success-600 border border-success-200 hover:bg-success-50 rounded-lg">Resume</button>
                                    @endif

                                    @if(in_array($seq->status, ['active', 'paused']))
                                    <button wire:click="cancelSequence({{ $seq->id }})" wire:confirm="Cancel this sequence?" class="px-2 py-1 text-xs font-semibold text-danger-600 border border-danger-200 hover:bg-danger-50 rounded-lg">Cancel</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-text-secondary text-sm">
                                No follow-up sequences found. Click "+ New Sequence" to create one.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($sequences->hasPages())
        <div class="px-6 py-3 border-t border-border-default/60">
            {{ $sequences->links() }}
        </div>
        @endif
    </div>
</div>
