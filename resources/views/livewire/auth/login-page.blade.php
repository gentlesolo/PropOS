<div>
    <!-- Logo & Header -->
    <div class="mb-8">
        <div class="text-3xl font-extrabold tracking-tight bg-gradient-brand bg-clip-text text-transparent">
            PropOS
        </div>
        <h2 class="mt-6 text-2xl font-bold tracking-tight text-text-primary">Sign in to your account</h2>
        <p class="mt-2 text-sm text-text-secondary">
            Or
            <a href="{{ route('register') }}" class="font-semibold text-brand-primary hover:text-brand-secondary transition-colors">register a new agency</a>
        </p>
    </div>

    <!-- Error Alerts -->
    @if(session()->has('error'))
        <div class="mb-4 p-4 rounded-xl bg-danger-50 border border-danger-100 text-danger-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Form -->
    <form wire:submit.prevent="submit" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-semibold text-text-primary">Email Address</label>
            <div class="mt-1.5">
                <input wire:model="email" id="email" type="email" required class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page sm:text-sm">
                @error('email') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-text-primary">Password</label>
            <div class="mt-1.5">
                <input wire:model="password" id="password" type="password" required class="block w-full rounded-xl border border-border-default py-2.5 px-3.5 text-text-primary bg-surface-input shadow-sm placeholder:text-text-tertiary focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page sm:text-sm">
                @error('password') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center">
            <input wire:model="remember" id="remember" type="checkbox" class="h-4 w-4 rounded border-border-default text-brand-primary focus:ring-brand-primary focus:ring-offset-2 focus:ring-offset-surface-page">
            <label for="remember" class="ml-2 block text-sm text-text-secondary">Remember me</label>
        </div>

        <div>
            <button type="submit" class="flex w-full justify-center rounded-xl bg-brand-primary px-4 py-3 text-sm font-semibold leading-6 text-white shadow-sm hover:opacity-90 hover:shadow-brand-md hover-spring active:scale-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-primary cursor-pointer">
                Sign in
            </button>
        </div>
    </form>
</div>


