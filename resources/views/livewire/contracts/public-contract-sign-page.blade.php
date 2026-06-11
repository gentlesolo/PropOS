<div class="min-h-screen bg-slate-900 py-12 px-4 sm:px-6 lg:px-8 flex flex-col justify-center items-center text-slate-100">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600&family=Playball&family=Reenie+Beanie&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .font-signature-dancing { font-family: 'Dancing Script', cursive; }
        .font-signature-playball { font-family: 'Playball', cursive; }
        .font-signature-reenie { font-family: 'Reenie Beanie', cursive; }
    </style>

    <div class="w-full max-w-4xl font-outfit">
        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 items-center justify-center font-black text-white text-lg shadow-lg shadow-blue-500/20 border border-white/10 mb-3">
                P
            </div>
            <h2 class="text-xl font-bold tracking-tight text-white">VillaCRM Secure Sign</h2>
            <p class="text-xs text-slate-400 mt-1">eSignature Audit ID: {{ $contract->reference }}</p>
        </div>

        @if($signed)
        <div class="bg-slate-800/80 border border-green-500/30 backdrop-blur-md rounded-3xl p-8 text-center shadow-xl max-w-md mx-auto animate-fade-in">
            <div class="w-16 h-16 bg-green-500/10 border border-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Document Fully Signed</h3>
            <p class="text-sm text-slate-400 mb-6">Thank you. The signed document has been locked and a copy was sent to your agent.</p>
            <div class="text-left text-xs bg-slate-900/60 rounded-xl p-4 space-y-2 border border-slate-700/40">
                <div class="font-semibold text-slate-300">Signatory Audit log:</div>
                @foreach($contract->signed_at ?? [] as $sig)
                <div class="text-slate-400">
                    <span class="text-blue-400 font-medium">{{ $sig['name'] }}</span> signed at {{ $sig['signed_at'] }} (IP: {{ $sig['ip_address'] }})
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Document Viewer -->
            <div class="lg:col-span-2 bg-slate-800/80 border border-slate-700/50 rounded-3xl overflow-hidden shadow-xl flex flex-col">
                <div class="bg-slate-850 px-6 py-4 border-b border-slate-700/40 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white truncate">{{ $contract->title }}</h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20 uppercase tracking-wide">Awaiting Signature</span>
                </div>
                <div class="p-6 overflow-y-auto max-h-[500px] bg-slate-900/40 font-mono text-xs leading-relaxed whitespace-pre-wrap text-slate-300 select-none">
                    {{ $contract->body ?? 'No text defined for this contract.' }}
                </div>
            </div>

            <!-- Right: Signature Form -->
            <div class="bg-slate-800/80 border border-slate-700/50 rounded-3xl p-6 shadow-xl flex flex-col justify-between">
                <form wire:submit.prevent="submitSignature" class="space-y-5">
                    <div>
                        <h4 class="text-sm font-bold text-white mb-1">Adopt Your Signature</h4>
                        <p class="text-xs text-slate-400">Type your full name and select a signature script style.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Full Name *</label>
                        <input wire:model="fullName" type="text" class="w-full rounded-xl border border-slate-700 bg-slate-900/50 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="e.g. John Doe">
                        @error('fullName') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Initials *</label>
                        <input wire:model="initials" type="text" class="w-full rounded-xl border border-slate-700 bg-slate-900/50 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="JD">
                        @error('initials') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1.5">Signature Style</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" wire:click="$set('signatureStyle', 'dancing')" class="px-2 py-1.5 rounded-lg border text-xs {{ $signatureStyle === 'dancing' ? 'border-blue-500 bg-blue-500/10 text-blue-400' : 'border-slate-700 bg-slate-900/30 text-slate-400' }}">Dancing</button>
                            <button type="button" wire:click="$set('signatureStyle', 'playball')" class="px-2 py-1.5 rounded-lg border text-xs {{ $signatureStyle === 'playball' ? 'border-blue-500 bg-blue-500/10 text-blue-400' : 'border-slate-700 bg-slate-900/30 text-slate-400' }}">Playball</button>
                            <button type="button" wire:click="$set('signatureStyle', 'reenie')" class="px-2 py-1.5 rounded-lg border text-xs {{ $signatureStyle === 'reenie' ? 'border-blue-500 bg-blue-500/10 text-blue-400' : 'border-slate-700 bg-slate-900/30 text-slate-400' }}">Reenie</button>
                        </div>
                    </div>

                    <!-- Signature Preview Panel -->
                    <div class="h-24 bg-slate-950/70 border border-slate-700/30 rounded-2xl flex items-center justify-center p-3 relative overflow-hidden select-none">
                        <span class="absolute top-1.5 left-2.5 text-[9px] text-slate-500 uppercase tracking-widest">Adopted Signature Preview</span>
                        <div class="text-2xl text-blue-400 tracking-wide mt-2
                            {{ $signatureStyle === 'dancing' ? 'font-signature-dancing' : '' }}
                            {{ $signatureStyle === 'playball' ? 'font-signature-playball text-3xl' : '' }}
                            {{ $signatureStyle === 'reenie' ? 'font-signature-reenie text-4xl' : '' }}">
                            {{ $fullName ?: 'Your Signature' }}
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-start gap-2.5 cursor-pointer text-xs text-slate-400 leading-normal">
                            <input wire:model="agreed" type="checkbox" class="mt-0.5 rounded border-slate-700 bg-slate-900 text-blue-500 focus:ring-blue-500 focus:ring-offset-slate-800">
                            <span>I agree that this is a legally binding electronic signature and I accept the terms of the document.</span>
                        </label>
                        @error('agreed') <div class="text-xs text-red-400 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white rounded-xl text-sm font-bold tracking-wide transition-colors shadow-lg shadow-blue-600/10">
                        Sign Document
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
