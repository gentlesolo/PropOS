<div>
    <!-- Trigger Button (Floating FAB) -->
    <button wire:click="toggle" class="fixed bottom-6 right-6 z-40 h-14 w-14 rounded-full bg-brand-primary text-white shadow-lg shadow-brand flex items-center justify-center hover:bg-brand-secondary transition-all hover:-translate-y-1 hover:shadow-xl focus:outline-none">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
        <span class="absolute top-0 right-0 h-3.5 w-3.5 rounded-full bg-success-500 border-2 border-white dark:border-surface-page animate-pulse"></span>
    </button>

    <!-- Slide-over Panel -->
    <div x-data="{ open: @entangle('isOpen') }" x-show="open" class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" style="display: none;">
        <div class="absolute inset-0 overflow-hidden">
            <!-- Background backdrop -->
            <div x-show="open" x-transition:enter="ease-in-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-500" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-surface-overlay backdrop-blur-sm transition-opacity" @click="open = false"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div x-show="open" x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="pointer-events-auto w-screen max-w-md">
                    
                    <div class="flex h-full flex-col bg-surface-card border-l border-border-default/60 shadow-2xl">
                        
                        <!-- Header -->
                        <div class="px-6 py-4 bg-brand-primary/5 border-b border-border-default/60 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 rounded-xl bg-brand-primary flex items-center justify-center text-white shadow-sm">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-text-primary" id="slide-over-title">PropOS Copilot</h2>
                                    <p class="text-xs font-semibold text-success-500 flex items-center">
                                        <span class="h-1.5 w-1.5 rounded-full bg-success-500 mr-1.5 animate-pulse"></span> Online
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button wire:click="startNewSession" class="text-text-secondary hover:text-brand-primary transition-colors focus:outline-none" title="New Chat">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                <button @click="open = false" class="text-text-tertiary hover:text-text-primary transition-colors focus:outline-none">
                                    <span class="sr-only">Close panel</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Chat Messages Container -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-surface-page relative">
                            <!-- Subtle Background Pattern -->
                            <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg width=%2220%22 height=%2220%22 viewBox=%220 0 20 20%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22%239C92AC%22 fill-opacity=%220.4%22 fill-rule=%22evenodd%22%3E%3Ccircle cx=%223%22 cy=%223%22 r=%223%22/%3E%3Ccircle cx=%2213%22 cy=%2213%22 r=%223%22/%3E%3C/g%3E%3C/svg%3E');"></div>
                            
                            @foreach($this->messages as $msg)
                                @if($msg->role === 'assistant')
                                    <!-- Assistant Message -->
                                    <div class="flex items-start space-x-3 relative z-10">
                                        <div class="h-8 w-8 rounded-full bg-brand-primary text-white flex items-center justify-center shrink-0 shadow">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        </div>
                                        <div class="bg-surface-card border border-border-default/60 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm max-w-[85%]">
                                            <p class="text-sm text-text-primary leading-relaxed whitespace-pre-wrap">{{ $msg->content }}</p>
                                        </div>
                                    </div>
                                @else
                                    <!-- User Message -->
                                    <div class="flex items-start justify-end space-x-3 relative z-10">
                                        <div class="bg-brand-primary text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow max-w-[85%]">
                                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $msg->content }}</p>
                                        </div>
                                        <div class="h-8 w-8 rounded-full bg-surface-raised border border-border-default/60 text-text-primary font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ substr(auth()->user()->first_name, 0, 1) }}
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            
                            <div wire:loading wire:target="sendMessage" class="flex items-start space-x-3 relative z-10">
                                <div class="h-8 w-8 rounded-full bg-brand-primary text-white flex items-center justify-center shrink-0 shadow">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </div>
                                <div class="bg-surface-card border border-border-default/60 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm flex items-center space-x-2">
                                    <div class="h-2 w-2 bg-brand-primary/50 rounded-full animate-bounce"></div>
                                    <div class="h-2 w-2 bg-brand-primary/50 rounded-full animate-bounce delay-75"></div>
                                    <div class="h-2 w-2 bg-brand-primary/50 rounded-full animate-bounce delay-150"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Area -->
                        <div class="p-4 bg-surface-card border-t border-border-default/60">
                            <form wire:submit.prevent="sendMessage" class="relative">
                                <input wire:model="newMessage" type="text" class="w-full pl-4 pr-12 py-3 bg-surface-input border border-border-default/60 rounded-xl text-sm text-text-primary placeholder-text-tertiary focus:outline-none focus:ring-2 focus:ring-brand-primary focus:border-transparent transition-all" placeholder="Ask Copilot anything..." autocomplete="off" />
                                <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 aspect-square rounded-lg bg-brand-primary hover:bg-brand-secondary transition-colors text-white flex items-center justify-center disabled:opacity-50" wire:loading.attr="disabled">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                </button>
                            </form>
                            <div class="mt-3 flex items-center justify-between text-[10px] text-text-tertiary uppercase font-bold tracking-wider">
                                <span>Powered by OpenAI & DeepSeek</span>
                                <a href="#" class="hover:text-brand-primary">Prompt Guide</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
