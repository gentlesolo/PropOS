<div class="min-h-screen bg-slate-950 text-slate-100 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Event Header Card -->
        <div class="p-8 bg-gradient-to-br from-brand-primary/20 via-slate-900 to-slate-950 rounded-3xl border border-brand-primary/30 backdrop-blur-md text-center space-y-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-brand-primary/20 text-brand-primary border border-brand-primary/30">
                📢 Upcoming Open House Invitation
            </span>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">
                {{ $openHouse->listing->headline ?? $openHouse->listing->property->address_line_1 }}
            </h1>
            <p class="text-slate-300">{{ $openHouse->listing->property->address_line_1 }}, {{ $openHouse->listing->property->city }}</p>
            
            <div class="inline-grid grid-cols-2 gap-4 border-t border-slate-800/80 pt-6 mt-4 w-full text-left">
                <div class="p-3 bg-slate-900/60 rounded-2xl border border-slate-800">
                    <p class="text-xs text-slate-400 font-medium uppercase tracking-wider">Starts At</p>
                    <p class="text-sm font-bold text-white mt-1">{{ $openHouse->starts_at->format('M j, Y \a\t g:i A') }}</p>
                </div>
                <div class="p-3 bg-slate-900/60 rounded-2xl border border-slate-800">
                    <p class="text-xs text-slate-400 font-medium uppercase tracking-wider">Ends At</p>
                    <p class="text-sm font-bold text-white mt-1">{{ $openHouse->ends_at->format('M j, Y \a\t g:i A') }}</p>
                </div>
            </div>
        </div>

        @if($registered)
        <!-- Registration Success Card -->
        <div class="p-8 bg-emerald-950/20 rounded-3xl border border-emerald-500/30 text-center space-y-4">
            <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center text-emerald-400 text-3xl font-bold mx-auto">
                ✓
            </div>
            <h2 class="text-2xl font-bold text-white">Registration Confirmed!</h2>
            <p class="text-slate-300 text-sm">
                Thank you for RSVPing, {{ $name }}. We have sent a confirmation email to <span class="font-semibold text-white">{{ $email }}</span>.
            </p>
            <p class="text-xs text-slate-400">We look forward to welcoming you to the property soon!</p>
        </div>
        @else
        <!-- RSVP Form Card -->
        <div class="p-8 bg-slate-900/60 rounded-3xl border border-slate-800 backdrop-blur-md space-y-6">
            <h3 class="text-xl font-bold text-white border-b border-slate-800 pb-4">Secure Your Attendance</h3>
            
            <form wire:submit.prevent="submitRsvp" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Full Name</label>
                    <input wire:model.defer="name" type="text" required placeholder="John Doe" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-100 focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page focus:outline-none">
                    @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Email Address</label>
                        <input wire:model.defer="email" type="email" required placeholder="john@example.com" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-100 focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page focus:outline-none">
                        @error('email') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Phone Number</label>
                        <input wire:model.defer="phone" type="tel" placeholder="+234..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-100 focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page focus:outline-none">
                        @error('phone') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Additional Notes / Request (Optional)</label>
                    <textarea wire:model.defer="notes" rows="3" placeholder="Let us know if you're representing a client or have specific questions..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-100 focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page focus:outline-none resize-none"></textarea>
                    @error('notes') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="disabled:opacity-70 disabled:cursor-not-allowed relative w-full py-4 bg-brand-primary hover:bg-brand-secondary text-white font-semibold text-sm rounded-xl transition shadow-lg shadow-brand-primary/20" wire:loading.attr="disabled">
                <span wire:loading.remove>Register RSVP</span>
                <span wire:loading class="flex items-center space-x-2 absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </span>
            </button>
            </form>
        </div>
        @endif

        <!-- Footer / Agent Contact -->
        @if($openHouse->agent)
        <div class="p-6 bg-slate-900/40 rounded-3xl border border-slate-800 text-center">
            <p class="text-xs text-slate-400">Hosting Agent</p>
            <h4 class="text-sm font-bold text-white mt-1">{{ $openHouse->agent->first_name }} {{ $openHouse->agent->last_name }}</h4>
            <p class="text-xs text-brand-primary mt-1">{{ $openHouse->agent->email }}</p>
        </div>
        @endif
    </div>
</div>

