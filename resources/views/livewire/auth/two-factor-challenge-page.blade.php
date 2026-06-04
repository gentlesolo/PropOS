<div>
    <style>
        @keyframes slide-down {
            0% { opacity: 0; transform: translateY(-4px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-down {
            animation: slide-down 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>

    <div class="mb-8">
        <!-- Mini mobile logo mark (visible on mobile only) -->
        <div class="lg:hidden flex items-center space-x-2.5 mb-6">
            <div class="h-8 w-8 rounded-md bg-[#10B981]/10 flex items-center justify-center border border-[#10B981]/25">
                <span class="font-bold text-base text-[#10B981]">P</span>
            </div>
            <span class="text-lg font-bold tracking-tight text-[#FAFAFA]">PropOS</span>
        </div>

        <div class="h-12 w-12 bg-[#10B981]/10 rounded-md flex items-center justify-center mb-4 border border-[#10B981]/25">
            <svg class="h-6 w-6 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-semibold tracking-tight text-[#FAFAFA] font-sans">Two-Factor Verification</h2>
        <p class="mt-2 text-sm text-[#A1A1AA]">
            Enter the 6-digit verification code from your authenticator app.
        </p>
    </div>

    <form wire:submit.prevent="submit" class="space-y-5">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[#A1A1AA] mb-1.5">Authentication Code</label>
            <input wire:model.defer="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                autofocus placeholder="000000"
                class="w-full h-12 bg-[#111827] border @error('code') border-[#F43F5E] @else border-white/10 @enderror rounded-md text-center text-xl font-mono tracking-[0.3em] text-[#FAFAFA] placeholder-[#52525B] px-3.5 focus:outline-none focus:border-[#10B981] focus:ring-1 focus:ring-[#10B981] focus:shadow-[0_0_12px_rgba(16,185,129,0.16)] transition-all duration-200">
            @error('code') <span class="block text-xs text-[#F43F5E] mt-1.5 animate-slide-down">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit"
                class="cta-shimmer w-full h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all flex items-center justify-center cursor-pointer">
                <span wire:loading.remove wire:target="submit">Verify & Sign In</span>
                <span wire:loading wire:target="submit">Verifying...</span>
            </button>
        </div>
    </form>

    <div class="mt-8 pt-6 border-t border-white/5 text-center">
        <a href="{{ route('login') }}" class="text-xs text-[#A1A1AA] hover:text-[#FAFAFA] transition-colors">
            Back to login
        </a>
    </div>
</div>
