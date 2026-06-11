<div class="relative">
    <!-- Header -->
    <div class="mb-8 text-center">
        <div class="h-16 w-16 bg-[#10B981]/10 rounded-full flex items-center justify-center mx-auto border border-[#10B981]/25 mb-4">
            <svg class="h-8 w-8 text-[#10B981]" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
        </div>
        <h2 class="text-2xl font-semibold tracking-tight text-[#FAFAFA] font-sans">Verify your email</h2>
        <p class="mt-4 text-sm text-[#A1A1AA] leading-relaxed">
            Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
        </p>
    </div>

    @if ($verificationLinkSent)
        <div class="mb-6 p-3.5 rounded-md bg-[#10B981]/10 border border-[#10B981]/20 text-[#10B981] text-xs text-center">
            A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    <div class="mt-6 flex flex-col space-y-4">
        <button wire:click="resend" type="button" class="cta-shimmer w-full h-[44px] bg-[#10B981] text-white text-sm font-semibold rounded-md shadow-[0_2px_8px_rgba(16,185,129,0.16)] hover:bg-[#10B981]/90 active:scale-[0.98] transition-all duration-150 flex items-center justify-center cursor-pointer">
            Resend Verification Email
        </button>

        <button wire:click="logout" type="button" class="w-full h-[44px] bg-transparent border border-white/10 text-[#FAFAFA] text-sm font-semibold rounded-md hover:bg-white/5 transition-all duration-150">
            Log Out
        </button>
    </div>
</div>
