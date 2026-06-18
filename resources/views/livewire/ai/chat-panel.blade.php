<div>
    <!-- Trigger Button (Floating FAB) - Only on Mobile -->
    <button wire:click="toggle" class="md:hidden fixed bottom-6 right-6 z-40 h-14 w-14 rounded-full bg-gradient-to-br from-[#10B981] to-[#0ea5e9] text-white shadow-lg shadow-[#10B981]/25 border border-white/15 flex items-center justify-center hover:scale-105 active:scale-95 transition-all focus:outline-none">
        <svg class="h-6 w-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21l-.813-5.096L3 15l5.096-.813L9 9l.813 5.096L15 15l-5.188.904zm9.193-7.658L18 12l-1.006-3.754L13.25 7.25l3.744-1.006L18 2.5l1.006 3.744 3.744 1.006-3.744 1.006z"/>
        </svg>
        <span class="absolute top-0 right-0 h-3 w-3 rounded-full bg-[#22C55E] border-2 border-[#030712] animate-pulse"></span>
    </button>

    <!-- Slide-over Panel -->
    <div x-data="{ open: @entangle('isOpen') }"
         x-show="open"
         class="fixed inset-0 z-50 overflow-hidden" 
         aria-labelledby="slide-over-title" 
         role="dialog" 
         aria-modal="true" 
         style="display: none;">
        <div class="absolute inset-0 overflow-hidden">
            <!-- Background backdrop -->
            <div x-show="open" 
                 x-transition:enter="ease-in-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in-out duration-300" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="absolute inset-0 bg-[#030712]/60 backdrop-blur-sm transition-opacity" 
                 @click="open = false"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div x-show="open" 
                     x-transition:enter="transform transition ease-spring duration-300" 
                     x-transition:enter-start="translate-x-full" 
                     x-transition:enter-end="translate-x-0" 
                     x-transition:leave="transform transition ease-spring duration-300" 
                     x-transition:leave-start="translate-x-0" 
                     x-transition:leave-end="translate-x-full" 
                     class="pointer-events-auto w-screen max-w-md">
                    
                    <div class="flex h-full flex-col bg-[#090d16]/95 backdrop-blur-xl border-l border-white/5 shadow-2xl">
                        
                        <!-- Header -->
                        <div class="px-6 py-4 bg-[#030712]/40 border-b border-white/5 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-9 w-9 rounded-md bg-gradient-to-br from-[#10B981] to-[#0ea5e9] flex items-center justify-center text-white shadow-md shadow-[#10B981]/25 border border-white/10">
                                    <svg class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <div>
                                    <h2 class="text-xs font-black uppercase tracking-wider text-[#FAFAFA]" id="slide-over-title">AI Command Assistant</h2>
                                    <p class="text-[9px] font-black text-[#10B981] flex items-center mt-0.5 tracking-widest uppercase">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#10B981] mr-1.5 animate-pulse"></span> Terminal Active
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button wire:click="startNewSession" class="p-1.5 rounded text-[#A1A1AA] hover:text-[#10B981] hover:bg-[#111827] transition-colors focus:outline-none" title="New Session">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                <button @click="open = false" class="p-1.5 rounded text-[#52525B] hover:text-[#FAFAFA] hover:bg-[#111827] transition-colors focus:outline-none">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Quick Prompts Chips -->
                        <div class="px-6 py-2.5 bg-[#030712]/20 border-b border-white/5 flex gap-1.5 overflow-x-auto whitespace-nowrap scrollbar-none select-none">
                            <button @click="$wire.set('newMessage', 'My tasks today'); $wire.sendMessage()" 
                                    class="px-2.5 py-1 bg-[#111827] hover:bg-[#10B981]/10 border border-white/5 hover:border-[#10B981]/30 rounded text-[10px] font-bold text-[#A1A1AA] hover:text-[#10B981] transition-all">
                                Tasks Today
                            </button>
                            <button @click="$wire.set('newMessage', 'Find quiet leads'); $wire.sendMessage()" 
                                    class="px-2.5 py-1 bg-[#111827] hover:bg-[#10B981]/10 border border-white/5 hover:border-[#10B981]/30 rounded text-[10px] font-bold text-[#A1A1AA] hover:text-[#10B981] transition-all">
                                Quiet Leads
                            </button>
                            <button @click="$wire.set('newMessage', 'Draft follow-up email'); $wire.sendMessage()" 
                                    class="px-2.5 py-1 bg-[#111827] hover:bg-[#10B981]/10 border border-white/5 hover:border-[#10B981]/30 rounded text-[10px] font-bold text-[#A1A1AA] hover:text-[#10B981] transition-all">
                                Draft Follow-Up
                            </button>
                        </div>

                        <!-- Chat Messages Container -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-[#030712]/30 relative"
                             wire:poll.2000ms="checkForResponse">
                            <!-- Subtle Grid Background -->
                            <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#10B981 1px, transparent 0); background-size: 12px 12px;"></div>
                            
                            @foreach($this->messages as $msg)
                                @if($msg->role === 'assistant')
                                    <!-- Assistant Message (Geist Mono font family for terminal look) -->
                                    <div class="flex items-start space-x-3 relative z-10">
                                        <div class="h-7 w-7 rounded bg-gradient-to-br from-[#10B981] to-[#10B981]/80 text-[#FAFAFA] flex items-center justify-center shrink-0 border border-white/10 shadow shadow-[#10B981]/20">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        </div>
                                        <div class="bg-[#111827]/80 border border-white/5 rounded px-3.5 py-2.5 shadow-sm max-w-[85%] font-mono text-[11px] leading-relaxed text-[#FAFAFA]">
                                            @if($msg->content !== null)
                                                <p class="whitespace-pre-wrap">{{ $msg->content }}</p>
                                            @else
                                                <div class="flex items-center space-x-1.5 py-0.5">
                                                    <div class="h-1.5 w-1.5 bg-[#10B981]/60 rounded-full animate-bounce"></div>
                                                    <div class="h-1.5 w-1.5 bg-[#10B981]/60 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                                                    <div class="h-1.5 w-1.5 bg-[#10B981]/60 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <!-- User Message -->
                                    <div class="flex items-start justify-end space-x-3 relative z-10">
                                        <div class="bg-[#10B981]/10 border border-[#10B981]/25 text-[#10B981] rounded px-3.5 py-2.5 shadow max-w-[85%] text-xs font-bold leading-relaxed">
                                            <p class="whitespace-pre-wrap">{{ $msg->content }}</p>
                                        </div>
                                        <div class="h-7 w-7 rounded bg-[#111827] border border-white/5 text-[#FAFAFA] font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ substr(auth()->user()->first_name, 0, 1) }}
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            
                            <div wire:loading wire:target="sendMessage" class="flex items-start space-x-3 relative z-10">
                                <div class="h-7 w-7 rounded bg-gradient-to-br from-[#10B981] to-[#10B981]/80 text-[#FAFAFA] flex items-center justify-center shrink-0 border border-white/10 shadow">
                                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </div>
                                <div class="bg-[#111827]/80 border border-white/5 rounded px-3.5 py-2 shadow-sm flex items-center space-x-1.5">
                                    <div class="h-1.5 w-1.5 bg-[#10B981]/60 rounded-full animate-bounce"></div>
                                    <div class="h-1.5 w-1.5 bg-[#10B981]/60 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                                    <div class="h-1.5 w-1.5 bg-[#10B981]/60 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Area with voice icon -->
                        <div class="p-4 bg-[#090d16] border-t border-white/5">
                            <form wire:submit.prevent="sendMessage" class="relative flex items-center gap-2">
                                <div class="relative flex-1">
                                    <input wire:model="newMessage" 
                                           type="text" 
                                           class="w-full pl-3 pr-10 py-2.5 bg-[#030712] border border-white/5 rounded text-xs text-[#FAFAFA] placeholder-[#52525B] focus:outline-none focus:border-[#10B981]/50 focus:ring-1 focus:ring-[#10B981]/20 transition-all font-mono" 
                                           placeholder="Type terminal instruction..." 
                                           autocomplete="off" />
                                    <!-- Voice icon inside input -->
                                    <button type="button" 
                                            class="absolute right-3 top-2 text-[#52525B] hover:text-[#10B981] transition-colors"
                                            title="Voice Input (mocked)">
                                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                                        </svg>
                                    </button>
                                </div>
                                <button type="submit" 
                                        class="p-2.5 rounded bg-[#10B981] hover:bg-[#10B981]/80 transition-colors text-white flex items-center justify-center shrink-0 disabled:opacity-50" 
                                        wire:loading.attr="disabled">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"></path></svg>
                                </button>
                            </form>
                            <div class="mt-3 flex items-center justify-between text-[9px] text-[#52525B] font-black uppercase tracking-wider">
                                <span>Engine: DeepSeek-R1-Hybrid</span>
                                <span class="text-[#10B981]">Terminal V2.4</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
