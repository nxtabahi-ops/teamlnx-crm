<x-filament-panels::page>
    <div wire:poll.3s class="flex h-[calc(100vh-11rem)] overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#111b21] shadow-lg font-sans">
        
        <!-- PANE 1: Conversation List (Left Column) -->
        <div class="w-1/3 min-w-[320px] max-w-[400px] border-r border-gray-200 dark:border-gray-800 flex flex-col bg-gray-50/50 dark:bg-[#111b21]">
            <!-- Search & Filters Header -->
            <div class="p-3 border-b border-gray-200 dark:border-gray-800 space-y-2 bg-gray-100/60 dark:bg-[#202c33]">
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Search or start new chat" 
                        class="w-full text-xs rounded-lg border-none bg-white dark:bg-[#111b21] text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-emerald-500 pl-8 py-2"
                    />
                    <span class="absolute left-2.5 top-2.5 text-gray-400 text-xs">🔍</span>
                </div>

                <!-- Status Filter Tabs -->
                <div class="flex space-x-1 text-xs">
                    @foreach(['all' => 'All', 'open' => 'Open', 'pending' => 'Pending', 'resolved' => 'Resolved'] as $stKey => $stLabel)
                        <button 
                            wire:click="$set('selectedStatus', '{{ $stKey }}')"
                            class="px-3 py-1 rounded-full text-[11px] font-medium transition-colors {{ $selectedStatus === $stKey ? 'bg-emerald-600 text-white' : 'bg-gray-200 dark:bg-[#2a3942] text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-[#3b4a54]' }}"
                        >
                            {{ $stLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Conversations Scrollable Area -->
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800/40">
                @forelse($this->conversations as $conv)
                    <div 
                        wire:click="selectConversation('{{ $conv->id }}')"
                        class="p-3 flex items-center space-x-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-[#202c33] transition-colors {{ $selectedConversationId === $conv->id ? 'bg-gray-200/70 dark:bg-[#2a3942]' : '' }}"
                    >
                        <div class="w-11 h-11 rounded-full bg-emerald-700 text-white flex items-center justify-center font-bold text-sm flex-shrink-0 shadow-sm">
                            {{ strtoupper(substr($conv->contact->profile_name ?? 'C', 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline mb-1">
                                <h4 class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $conv->contact->profile_name ?? $conv->contact->phone_number }}
                                </h4>
                                <span class="text-[10px] text-gray-400 font-mono">
                                    {{ $conv->last_message_at ? $conv->last_message_at->setTimezone('Asia/Kolkata')->format('h:i A') : '' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $conv->last_message_preview ?? 'No messages yet' }}
                            </p>
                            @if($conv->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($conv->tags as $t)
                                        <span class="px-1.5 py-0.5 text-[9px] rounded text-white font-medium" style="background-color: {{ $t->color }}">
                                            {{ $t->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if($conv->unread_count > 0)
                            <span class="bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                {{ $conv->unread_count }}
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-gray-400">
                        No WhatsApp conversations found.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- PANE 2: Main Active Chat Thread (Center Column) -->
        <div class="flex-1 flex flex-col bg-white dark:bg-[#0b141a] border-r border-gray-200 dark:border-gray-800 min-w-0">
            @if($this->activeConversation)
                <!-- WhatsApp Top Header -->
                <div class="px-4 py-2.5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-100/80 dark:bg-[#202c33]">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                            {{ strtoupper(substr($this->activeConversation->contact->profile_name, 0, 2)) }}
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->activeConversation->contact->profile_name }}
                            </h3>
                            <span class="text-[11px] text-emerald-500 dark:text-emerald-400 font-mono">
                                {{ $this->activeConversation->contact->phone_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions Bar -->
                    <div class="flex items-center space-x-2">
                        <!-- Agent Selector -->
                        <select 
                            wire:change="assignUser($event.target.value)"
                            class="text-xs rounded-lg border-none bg-white dark:bg-[#111b21] text-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-emerald-500 py-1"
                        >
                            <option value="">-- Assign Agent --</option>
                            @foreach($this->teamUsers as $u)
                                <option value="{{ $u->id }}" {{ $this->activeConversation->assigned_user_id === $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Status Selector -->
                        <select 
                            wire:change="updateStatus($event.target.value)"
                            class="text-xs rounded-lg border-none bg-white dark:bg-[#111b21] text-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-emerald-500 py-1"
                        >
                            @foreach(['open', 'pending', 'resolved', 'archived'] as $st)
                                <option value="{{ $st }}" {{ $this->activeConversation->status === $st ? 'selected' : '' }}>
                                    {{ ucfirst($st) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Messages Thread Scroll Area -->
                <div id="whatsapp-chat-thread" class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#efeae2] dark:bg-[#0b141a]">
                    @foreach($this->activeConversation->messages as $msg)
                        <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[70%] rounded-xl px-3 py-2 text-xs shadow {{ $msg->direction === 'outbound' ? 'bg-[#d9fdd3] dark:bg-[#005c4b] text-gray-900 dark:text-white rounded-tr-none' : 'bg-white dark:bg-[#202c33] text-gray-900 dark:text-gray-100 rounded-tl-none' }}">
                                
                                <!-- Message Attachment Render -->
                                @if($msg->media_url)
                                    <div class="mb-1 overflow-hidden rounded-lg max-w-xs">
                                        @if(in_array($msg->type, ['image', 'sticker']))
                                            <a href="{{ $msg->media_url }}" target="_blank">
                                                <img src="{{ $msg->media_url }}" class="w-full h-auto max-h-60 object-cover rounded-lg hover:opacity-95 transition-opacity" />
                                            </a>
                                        @elseif($msg->type === 'video')
                                            <video controls src="{{ $msg->media_url }}" class="w-full max-h-60 rounded-lg"></video>
                                        @elseif(in_array($msg->type, ['voice', 'audio']))
                                            <audio controls src="{{ $msg->media_url }}" class="w-full my-1"></audio>
                                        @elseif($msg->type === 'document')
                                            <a href="{{ $msg->media_url }}" target="_blank" class="flex items-center space-x-2 bg-gray-100 dark:bg-[#111b21] p-2 rounded-lg text-emerald-600 dark:text-emerald-400 font-semibold hover:underline">
                                                <span>📄</span>
                                                <span class="truncate max-w-[200px]">{{ $msg->media_filename ?? 'Download Document' }}</span>
                                            </a>
                                        @endif
                                    </div>
                                @endif

                                @if($msg->body)
                                    <p class="whitespace-pre-line leading-relaxed break-words font-normal">{{ $msg->body }}</p>
                                @endif

                                <div class="mt-1 flex items-center justify-end space-x-1 text-[10px] opacity-75">
                                    <span class="font-mono">{{ $msg->created_at->setTimezone('Asia/Kolkata')->format('h:i A') }}</span>
                                    @if($msg->direction === 'outbound')
                                        <span>
                                            @if($msg->status === 'read')
                                                <span class="text-sky-400 font-bold">✓✓</span>
                                            @elseif($msg->status === 'delivered')
                                                <span class="text-gray-400 font-bold">✓✓</span>
                                            @elseif($msg->status === 'sent')
                                                <span class="text-gray-400 font-bold">✓</span>
                                            @else
                                                <span>⏱️</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- WhatsApp Smart Reply Footer -->
                <div class="p-3 border-t border-gray-200 dark:border-gray-800 bg-gray-100/90 dark:bg-[#202c33] space-y-2">
                    <!-- AI Reply Suggestions -->
                    @if(!empty($aiReplySuggestions))
                        <div class="flex flex-wrap gap-1 mb-1">
                            <span class="text-[10px] text-gray-400 self-center">✨ AI:</span>
                            @foreach($aiReplySuggestions as $sug)
                                <button 
                                    wire:click="useAiSuggestion(@js($sug))"
                                    class="text-[10px] bg-emerald-100 dark:bg-[#111b21] text-emerald-700 dark:text-emerald-300 px-2 py-0.5 rounded-full hover:bg-emerald-200"
                                >
                                    "{{ Str::limit($sug, 35) }}"
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <!-- Attachment Selected Preview Pill -->
                    @if($attachment)
                        <div class="flex items-center justify-between bg-white dark:bg-[#111b21] px-3 py-1.5 rounded-lg border border-emerald-500 text-xs">
                            <span class="truncate max-w-xs text-emerald-600 dark:text-emerald-400 font-medium">📎 {{ $attachment->getClientOriginalName() }}</span>
                            <button type="button" wire:click="removeAttachment" class="text-red-500 font-bold hover:text-red-700 ml-2">✖</button>
                        </div>
                    @endif

                    <form wire:submit.prevent="sendReply" class="flex items-center space-x-2">
                        <!-- File Attachment Button -->
                        <label class="cursor-pointer p-2 rounded-full text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#2a3942] transition-colors">
                            <input type="file" wire:model="attachment" class="hidden" />
                            <span class="text-lg">📎</span>
                        </label>

                        <!-- Input Message Box -->
                        <input 
                            type="text"
                            wire:model="replyText" 
                            placeholder="Type a message"
                            class="flex-1 text-xs rounded-lg border-none bg-white dark:bg-[#2a3942] text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-emerald-500 py-2.5 px-3"
                        />
                        
                        <!-- Actions -->
                        <button 
                            type="button" 
                            wire:click="generateAiSuggestions" 
                            title="Generate AI Reply"
                            class="p-2 text-xs rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 hover:bg-purple-200"
                        >
                            ✨ AI
                        </button>

                        <button 
                            type="submit" 
                            class="p-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white shadow transition-colors flex items-center justify-center"
                            title="Send Message"
                        >
                            <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path>
                            </svg>
                        </button>
                    </form>
                </div>

            @else
                <div class="flex-1 flex flex-col items-center justify-center text-gray-400 text-xs space-y-2">
                    <span class="text-4xl">💬</span>
                    <span>Select a WhatsApp conversation to start messaging.</span>
                </div>
            @endif
        </div>

        <!-- PANE 3: Contact Profile, Tags, Notes & AI Summary (Right Column) -->
        <div class="w-72 border-l border-gray-200 dark:border-gray-800 bg-gray-50/40 dark:bg-[#111b21] overflow-y-auto p-4 space-y-4">
            @if($this->activeConversation)
                <!-- Contact Card -->
                <div class="text-center p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#202c33] shadow-sm">
                    <div class="w-14 h-14 rounded-full bg-emerald-700 text-white font-bold text-xl mx-auto flex items-center justify-center mb-2 shadow">
                        {{ strtoupper(substr($this->activeConversation->contact->profile_name, 0, 2)) }}
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">
                        {{ $this->activeConversation->contact->profile_name }}
                    </h3>
                    <p class="text-xs text-emerald-500 font-mono">
                        {{ $this->activeConversation->contact->phone_number }}
                    </p>

                    @if($this->activeConversation->contact->people)
                        <div class="mt-2 text-left bg-gray-50 dark:bg-[#111b21] p-2 rounded-lg text-[11px]">
                            <span class="font-semibold text-gray-600 dark:text-gray-400">Relaticle CRM:</span>
                            <a href="/admin/people/{{ $this->activeConversation->contact->people_id }}" target="_blank" class="text-emerald-500 font-medium underline block mt-0.5 truncate">
                                👤 {{ $this->activeConversation->contact->people->name }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Tags Manager -->
                <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#202c33] shadow-sm space-y-2">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">Conversation Tags</h4>
                    <div class="flex flex-wrap gap-1">
                        @foreach($this->tags as $tag)
                            @php
                                $isAttached = $this->activeConversation->tags->contains($tag->id);
                            @endphp
                            <button 
                                wire:click="toggleTag('{{ $tag->id }}')"
                                class="px-2 py-0.5 text-[10px] rounded-full font-medium transition-opacity {{ $isAttached ? 'text-white shadow-sm' : 'bg-gray-100 text-gray-600 opacity-50 dark:bg-gray-800 dark:text-gray-400' }}"
                                style="{{ $isAttached ? 'background-color: ' . $tag->color : '' }}"
                            >
                                {{ $tag->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- AI Summary Tool -->
                <div class="p-3 rounded-xl border border-purple-200 dark:border-purple-900 bg-purple-50/40 dark:bg-purple-950/20 shadow-sm space-y-2">
                    <div class="flex justify-between items-center">
                        <h4 class="text-xs font-bold text-purple-900 dark:text-purple-300">✨ AI Summary</h4>
                        <button 
                            wire:click="generateAiSummary" 
                            class="text-[10px] text-purple-700 dark:text-purple-400 underline hover:text-purple-900"
                        >
                            Generate
                        </button>
                    </div>
                    @if($aiSummary)
                        <div class="text-[11px] text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed border-t border-purple-200 dark:border-purple-900 pt-2">
                            {{ $aiSummary }}
                        </div>
                    @endif
                </div>

                <!-- Internal Notes -->
                <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#202c33] shadow-sm space-y-2">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">Agent Notes</h4>
                    <form wire:submit.prevent="addNote" class="space-y-1.5">
                        <textarea 
                            wire:model="newNoteText" 
                            rows="2" 
                            placeholder="Add private note..."
                            class="w-full text-xs rounded-lg border-none bg-gray-100 dark:bg-[#111b21] text-gray-900 dark:text-gray-100 py-1.5 px-2"
                        ></textarea>
                        <button type="submit" class="w-full py-1 text-[11px] font-semibold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                            Add Note
                        </button>
                    </form>

                    <div class="space-y-2 mt-3 max-h-48 overflow-y-auto">
                        @foreach($this->activeConversation->notes as $note)
                            <div class="bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 p-2 rounded text-[11px]">
                                <div class="flex justify-between text-[9px] text-amber-700 dark:text-amber-400 mb-1">
                                    <span>{{ $note->user->name }}</span>
                                    <span>{{ $note->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-gray-800 dark:text-gray-200 leading-snug">{{ $note->content }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

            @else
                <div class="p-6 text-center text-xs text-gray-400">
                    No chat selected.
                </div>
            @endif
        </div>

    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const scrollThread = () => {
                const el = document.getElementById('whatsapp-chat-thread');
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            };
            scrollThread();
            Livewire.on('scroll-to-bottom', () => setTimeout(scrollThread, 100));
        });
    </script>
</x-filament-panels::page>
