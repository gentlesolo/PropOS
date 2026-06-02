<div class="min-h-screen flex items-center justify-center bg-surface-page">
    <div class="w-full max-w-md">
        <div class="bg-surface-card rounded-2xl border border-border-default shadow-xl p-8">
            <div class="text-center mb-6">
                <div class="h-14 w-14 bg-brand-primary/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="h-7 w-7 text-brand-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-text-primary">Two-Factor Verification</h1>
                <p class="mt-2 text-sm text-text-secondary">Enter the 6-digit code from your authenticator app.</p>
            </div>

            <form wire:submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-1">Authentication Code</label>
                    <input wire:model.defer="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                        autofocus placeholder="000000"
                        class="w-full rounded-xl border border-border-default bg-surface-input px-4 py-3 text-center text-2xl font-mono tracking-widest text-text-primary focus:border-brand-primary focus:ring-2 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page/20">
                    @error('code') <span class="text-xs text-danger-600 mt-1 block text-center">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                    class="w-full py-3 bg-gradient-to-br from-brand-primary to-brand-primary/80 text-white shadow-brand-sm ring-1 ring-white/10 rounded-xl font-semibold hover:bg-brand-secondary transition-colors hover-spring active:scale-95 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="submit">Verify & Sign In</span>
                    <span wire:loading wire:target="submit">Verifying...</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-text-secondary hover:text-brand-primary transition-colors">
                    Back to login
                </a>
            </div>
        </div>
    </div>
</div>




