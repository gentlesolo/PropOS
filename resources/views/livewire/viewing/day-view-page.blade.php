<div class="h-[calc(100vh-8rem)] flex flex-col md:flex-row gap-8">
    <!-- Map Section (Left) -->
    <div class="hidden md:block w-full md:w-7/12 lg:w-2/3 h-full rounded-3xl overflow-hidden glass-panel border border-border-default/60 shadow-md relative group">
        <div class="absolute inset-0 bg-slate-100 dark:bg-slate-800" style="background-image: url('data:image/svg+xml,%3Csvg width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath d=%22M0 0h40v40H0V0zm20 20h20v20H20V20zM0 20h20v20H0V20z%22 fill=%22%239C92AC%22 fill-opacity=%220.05%22 fill-rule=%22evenodd%22/%3E%3C/svg%3E');"></div>
        <div class="absolute inset-0 p-12">
            <svg class="w-full h-full text-brand-primary/40 stroke-current" fill="none" stroke-width="4" stroke-dasharray="8 8" stroke-linecap="round">
                <path d="M 150 100 Q 250 150 200 300 T 400 450" />
            </svg>
            @foreach($viewings as $index => $viewing)
                @php $positions = [['top'=>'20%','left'=>'30%'],['top'=>'45%','left'=>'40%'],['top'=>'60%','left'=>'65%'],['top'=>'80%','left'=>'50%']]; $pos = $positions[$index % 4]; @endphp
                <div class="absolute group/pin hover-spring cursor-pointer" style="top: {{ $pos['top'] }}; left: {{ $pos['left'] }};">
                    <div class="relative -left-1/2 -top-full">
                        <div class="w-10 h-10 rounded-full border-4 border-white shadow-lg flex items-center justify-center font-black text-sm text-white {{ $viewing->status === 'completed' ? 'bg-emerald-500' : 'bg-brand-primary' }} hover:scale-110 transition-transform">{{ $index + 1 }}</div>
                        <div class="w-0.5 h-6 bg-brand-primary mx-auto -mt-1 shadow-sm"></div>
                        <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-3 w-48 p-3 bg-surface-card border border-border-default/60 shadow-xl rounded-2xl opacity-0 group-hover/pin:opacity-100 transition-opacity pointer-events-none z-10">
                            <p class="text-xs font-bold text-text-primary mb-1">{{ $viewing->scheduled_at->format('H:i') }}</p>
                            <p class="text-[10px] text-text-secondary leading-tight">{{ $viewing->listing?->property?->address_line_1 }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="absolute top-6 left-6 right-6 flex justify-between items-start pointer-events-none">
            <div class="bg-surface-card/90 backdrop-blur pointer-events-auto px-5 py-3 rounded-2xl border border-border-default/60 shadow-lg">
                <h2 class="text-sm font-black text-text-primary flex items-center">
                    <svg class="w-4 h-4 mr-2 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    AI Route Optimizer Active
                </h2>
                <p class="text-xs font-semibold text-text-secondary mt-1">{{ $viewings->count() }} viewings · Est. total: {{ $viewings->count() * 25 }} min drive</p>
            </div>
        </div>
    </div>

    <!-- Itinerary Section (Right) -->
    <div class="w-full md:w-5/12 lg:w-1/3 flex flex-col h-full">
        <!-- Date Navigator -->
        <div class="mb-4 flex items-center justify-between px-2">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-text-primary">Today's Route</h1>
                <p class="text-sm font-semibold text-text-secondary mt-1">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
            </div>
            <div class="flex items-center gap-1">
                <button wire:click="previousDay" class="h-8 w-8 rounded-full bg-surface-raised border border-border-default/60 flex items-center justify-center hover:bg-surface-sunken transition-colors">
                    <svg class="h-4 w-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button wire:click="$set('date', '{{ \Carbon\Carbon::today()->format('Y-m-d') }}')" class="px-2 py-1 text-xs font-bold text-brand-primary hover:bg-brand-primary/10 rounded-lg transition-colors">Today</button>
                <button wire:click="nextDay" class="h-8 w-8 rounded-full bg-surface-raised border border-border-default/60 flex items-center justify-center hover:bg-surface-sunken transition-colors">
                    <svg class="h-4 w-4 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- Feedback Modal -->
        @if($feedbackViewingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-surface-card rounded-2xl border border-border-default/60 shadow-2xl w-full max-w-md mx-4 p-6 overflow-y-auto max-h-[90vh]">
                <h2 class="text-lg font-bold text-text-primary mb-4">Post-Viewing Feedback</h2>
                <form wire:submit.prevent="saveFeedback" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Overall Rating</label>
                        <div class="flex gap-2">
                            @foreach([1,2,3,4,5] as $star)
                            <button type="button" wire:click="$set('overall_rating', {{ $star }})"
                                class="h-10 w-10 rounded-xl border flex items-center justify-center text-lg transition-colors
                                {{ $overall_rating >= $star ? 'bg-warning-100 border-warning-400 text-warning-600' : 'bg-surface-sunken border-border-default text-text-tertiary' }}">
                                ★
                            </button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Price Perception</label>
                        <div class="flex gap-2 flex-wrap">
                            @foreach([1 => 'Too High', 2 => 'Slightly High', 3 => 'Fair', 4 => 'Good Value', 5 => 'Excellent'] as $val => $label)
                            <button type="button" wire:click="$set('price_perception', {{ $val }})"
                                class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-colors
                                {{ $price_perception == $val ? 'bg-brand-primary text-white border-brand-primary' : 'bg-surface-sunken border-border-default text-text-secondary hover:border-brand-primary' }}">
                                {{ $label }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Interest Level</label>
                        <select wire:model.defer="interest_level" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                            <option value="very_interested">Very Interested</option>
                            <option value="interested">Interested</option>
                            <option value="maybe">Maybe</option>
                            <option value="not_interested">Not Interested</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Positives Noted</label>
                        <textarea wire:model.defer="positive_notes" rows="2" placeholder="What did the client like?" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Concerns / Objections</label>
                        <textarea wire:model.defer="concerns" rows="2" placeholder="What concerned the client?" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <input wire:model.defer="would_make_offer" type="checkbox" id="make-offer" class="h-4 w-4 rounded border-border-default text-brand-primary focus:ring-brand-primary">
                        <label for="make-offer" class="text-sm font-medium text-text-primary">Client would consider making an offer</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">Agent Notes (private)</label>
                        <textarea wire:model.defer="agent_notes" rows="2" placeholder="Internal notes..." class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-sm text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary resize-none"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 py-2.5 bg-brand-primary text-white rounded-xl font-semibold hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="saveFeedback">Save Feedback</span>
                            <span wire:loading wire:target="saveFeedback">Saving...</span>
                        </button>
                        <button type="button" wire:click="$set('feedbackViewingId', null)" class="flex-1 py-2.5 border border-border-default text-text-secondary rounded-xl font-semibold hover:bg-surface-sunken transition-colors">Skip</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Reschedule Modal -->
        @if($reschedulingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="$set('reschedulingId', null)"></div>
            <div class="relative bg-surface-card rounded-2xl border border-border-default/60 shadow-2xl w-full max-w-sm mx-4 p-6">
                <h2 class="text-lg font-bold text-text-primary mb-4">Reschedule Viewing</h2>
                <form wire:submit.prevent="saveReschedule" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">New Date</label>
                        <input wire:model.defer="newDate" type="date" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('newDate') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">New Time</label>
                        <input wire:model.defer="newTime" type="time" class="w-full rounded-xl border border-border-default bg-surface-input px-3 py-2 text-text-primary focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
                        @error('newTime') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 py-2 bg-brand-primary text-white rounded-xl text-sm font-semibold hover:bg-brand-secondary transition-colors">
                            <span wire:loading.remove wire:target="saveReschedule">Confirm</span>
                            <span wire:loading wire:target="saveReschedule">Saving...</span>
                        </button>
                        <button type="button" wire:click="$set('reschedulingId', null)" class="flex-1 py-2 border border-border-default text-text-secondary rounded-xl text-sm font-semibold hover:bg-surface-sunken transition-colors">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Viewings List -->
        <div class="flex-1 overflow-y-auto pr-2 pb-6">
            @if($viewings->isEmpty())
            <div class="text-center py-16">
                <div class="h-14 w-14 bg-surface-raised border border-border-default/60 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="h-7 w-7 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-sm font-bold text-text-primary">No viewings on this day.</p>
                <p class="text-xs text-text-secondary mt-1">Navigate to another day or schedule a viewing.</p>
            </div>
            @else
            <div class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-brand-primary/40 before:via-border-default/60 before:to-transparent">
                @foreach($viewings as $index => $viewing)
                <div class="relative flex items-start group">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-surface-page shadow shrink-0 z-10 mr-4
                        {{ $viewing->status === 'completed' ? 'bg-emerald-500 text-white' : ($index === 0 ? 'bg-brand-primary text-white ring-4 ring-brand-primary/20' : 'bg-surface-raised text-text-secondary') }}">
                        @if($viewing->status === 'completed')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-xs font-black">{{ $index + 1 }}</span>
                        @endif
                    </div>

                    <div class="flex-1 bg-surface-card border border-border-default/60 rounded-3xl p-5 shadow-sm hover:shadow hover-spring transition-all {{ $index === 0 && $viewing->status !== 'completed' ? 'border-brand-primary/40 shadow-md ring-1 ring-brand-primary/20' : '' }}">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-black text-text-primary leading-tight">{{ $viewing->scheduled_at->format('H:i') }} <span class="text-text-tertiary text-sm font-semibold ml-1">({{ $viewing->duration_minutes }}m)</span></h3>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider mt-1
                                    {{ $viewing->status === 'completed' ? 'bg-emerald-500/10 text-emerald-600' : ($viewing->status === 'confirmed' ? 'bg-brand-primary/10 text-brand-primary' : 'bg-surface-raised text-text-secondary') }}">
                                    {{ str_replace('_', ' ', $viewing->status) }}
                                </span>
                            </div>
                            @if($viewing->feedback)
                            <div class="flex items-center gap-0.5 text-warning-500">
                                @for($i = 1; $i <= 5; $i++)
                                <span class="text-sm {{ $i <= $viewing->feedback->overall_rating ? 'text-warning-500' : 'text-slate-200' }}">★</span>
                                @endfor
                            </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-start text-sm">
                                <svg class="h-5 w-5 mr-3 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <p class="font-bold text-text-primary">{{ $viewing->listing?->property?->address_line_1 }}</p>
                                    <p class="text-text-secondary text-xs mt-0.5">{{ $viewing->listing?->property?->city }}</p>
                                </div>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="h-5 w-5 mr-3 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <div class="flex items-center justify-between w-full">
                                    <p class="font-bold text-text-primary">{{ $viewing->contact?->first_name }} {{ $viewing->contact?->last_name }}</p>
                                    @if($viewing->contact?->phone)
                                    <a href="tel:{{ $viewing->contact->phone }}" class="text-brand-primary bg-brand-primary/10 px-2 py-1 rounded-md text-xs font-bold hover:bg-brand-primary hover:text-white transition-colors">Call</a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($viewing->status !== 'completed')
                        <div class="mt-5 pt-4 border-t border-border-default/40 grid grid-cols-2 gap-3">
                            <button wire:click="startReschedule({{ $viewing->id }})" class="bg-surface-raised text-text-primary text-xs font-bold py-2 rounded-xl border border-border-default/60 hover:bg-surface-sunken transition-colors">Reschedule</button>
                            <button wire:click="completeViewing({{ $viewing->id }})" class="bg-brand-primary text-white text-xs font-bold py-2 rounded-xl shadow-md hover:bg-brand-secondary hover-spring transition-colors">
                                <span wire:loading.remove wire:target="completeViewing({{ $viewing->id }})">Complete Viewing</span>
                                <span wire:loading wire:target="completeViewing({{ $viewing->id }})">...</span>
                            </button>
                        </div>
                        @elseif(!$viewing->feedback)
                        <div class="mt-4 pt-4 border-t border-border-default/40">
                            <button wire:click="$set('feedbackViewingId', {{ $viewing->id }})" class="w-full py-2 text-xs font-bold text-brand-primary border border-brand-primary/30 rounded-xl hover:bg-brand-primary/5 transition-colors">
                                + Log Feedback
                            </button>
                        </div>
                        @else
                        <div class="mt-4 pt-4 border-t border-border-default/40">
                            <p class="text-xs text-text-secondary capitalize">Interest: <span class="font-medium text-text-primary">{{ str_replace('_', ' ', $viewing->feedback->interest_level) }}</span></p>
                            @if($viewing->feedback->would_make_offer)
                            <p class="text-xs text-success-600 font-medium mt-1">✓ Would consider an offer</p>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                @if(!$loop->last)
                <div class="relative flex items-center ml-10 pl-8 py-3">
                    <div class="flex items-center space-x-2 bg-surface-page/80 backdrop-blur text-[10px] font-black tracking-wider text-text-secondary uppercase px-2 py-1 rounded border border-border-default/40 z-10">
                        <svg class="h-3 w-3 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>~{{ rand(15, 30) }} min drive</span>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
