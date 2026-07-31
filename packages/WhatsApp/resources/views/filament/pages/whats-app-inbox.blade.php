<x-filament-panels::page>
    <div class="flex h-[calc(100vh-12rem)] overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
        
        <!-- PANE 1: Conversation List (Left Column) -->
        <div class="w-1/3 min-w-[300px] border-r border-gray-200 dark:border-gray-800 flex flex-col bg-gray-50/50 dark:bg-gray-900/50">
            <!-- Search & Filters Header -->
            <div class="p-3 border-b border-gray-200 dark:border-gray-800 space-y-2">
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Search chats, numbers..." 
                        class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:ring-primary-500 pl-8"
                    />
                    <span class="absolute left-2.5 top-2.5 text-gray-400">🔍</span>
                </div>

                <!-- Status Filter Tabs -->
                <div class="flex space-x-1 text-xs">
                    @foreach(['open' => 'Open', 'pending' => 'Pending', 'resolved' => 'Resolved', 'all' => 'All'] as $stKey => $stLabel)
                        <button 
                            wire:click="$set('selectedStatus', '{{ $stKey }}')"
                            class="px-2.5 py-1 rounded-md transition-colors {{ $selectedStatus === $stKey ? 'bg-primary-600 text-white font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800' }}"
                        >
                            {{ $stLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Conversations Scrollable Area -->
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800/50">
                @forelse($this->conversations as $conv)
                    <div 
                        wire:click="selectConversation('{{ $conv->id }}')"
                        class="p-3 flex items-start space-x-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800/60 transition-colors {{ $selectedConversationId === $conv->id ? 'bg-primary-50/60 dark:bg-primary-950/20 border-l-4 border-primary-600' : '' }}"
                    >
                        <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-sm flex-shrink-0">
                            {{ strtoupper(substr($conv->contact->profile_name ?? 'C', 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline mb-1">
                                <h4 class="text-xs font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $conv->contact->profile_name ?? $conv->contact->phone_number }}
                                </h4>
                                <span class="text-[10px] text-gray-400">
                                    {{ $conv->last_message_at ? $conv->last_message_at->format('H:i') : '' }}
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
                            <span class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                                {{ $conv->unread_count }}
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-gray-400">
                        No conversations found.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- PANE 2: Main Active Chat Thread (Center Column) -->
        <div class="flex-1 flex flex-col bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 min-w-0">
            @if($this->activeConversation)
                <!-- Chat Top Header -->
                <div class="p-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/40 dark:bg-gray-900/40">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
                            💬
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->activeConversation->contact->profile_name }}
                            </h3>
                            <span class="text-[11px] text-gray-400">
                                {{ $this->activeConversation->contact->phone_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions Bar -->
                    <div class="flex items-center space-x-2">
                        <!-- Agent Selector -->
                        <select 
                            wire:change="assignUser($event.target.value)"
                            class="text-xs rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-700 dark:text-gray-200"
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
                            class="text-xs rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-700 dark:text-gray-200"
                        >
                            @foreach(['open', 'pending', 'resolved', 'archived'] as $st)
                                <option value="{{ $st }}" {{ $this->activeConversation->status === $st ? 'selected' : '' }}>
                                    {{ ucfirst($st) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Messages Thread Scroll -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50/20 dark:bg-gray-950/20">
                    @foreach($this->activeConversation->messages as $msg)
                        <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[75%] rounded-2xl px-4 py-2 text-xs shadow-sm {{ $msg->direction === 'outbound' ? 'bg-primary-600 text-white rounded-br-none' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-100 dark:border-gray-700 rounded-bl-none' }}">
                                
                                <!-- Message Header / Type -->
                                @if($msg->media_url)
                                    @if(in_array($msg->type, ['image', 'sticker']))
                                        <img src="{{ $msg->media_url }}" class="rounded-lg max-h-48 mb-1 object-cover" />
                                    @elseif($msg->type === 'video')
                                        <video controls src="{{ $msg->media_url }}" class="rounded-lg max-h-48 mb-1"></video>
                                    @elseif(in_array($msg->type, ['voice', 'audio']))
                                        <audio controls src="{{ $msg->media_url }}" class="mb-1 w-full"></audio>
                                    @elseif($msg->type === 'document')
                                        <a href="{{ $msg->media_url }}" target="_blank" class="flex items-center underline mb-1">
                                            📄 {{ $msg->media_filename ?? 'Download Document' }}
                                        </a>
                                    @endif
                                @endif

                                <p class="whitespace-pre-line leading-relaxed">{{ $msg->body }}</p>

                                <div class="mt-1 flex items-center justify-end space-x-1 text-[9px] opacity-75">
                                    <span>{{ $msg->created_at->format('H:i') }}</span>
                                    @if($msg->direction === 'outbound')
                                        <span>
                                            @if($msg->status === 'read') ✔️✔️ @elseif($msg->status === 'delivered') ✔️ @else ⏱️ @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Smart Reply Input Box -->
                <div class="p-3 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 space-y-2">
                    <!-- AI Reply Suggestions Pills -->
                    @if(!empty($aiReplySuggestions))
                        <div class="flex flex-wrap gap-1 mb-2">
                            <span class="text-[10px] text-gray-400 self-center">✨ AI Suggestions:</span>
                            @foreach($aiReplySuggestions as $sug)
                                <button 
                                    wire:click="useAiSuggestion(@js($sug))"
                                    class="text-[10px] bg-primary-50 dark:bg-primary-950 text-primary-600 dark:text-primary-300 px-2 py-0.5 rounded border border-primary-200 dark:border-primary-800 hover:bg-primary-100"
                                >
                                    "{{ Str::limit($sug, 40) }}"
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <form wire:submit.prevent="sendReply" class="flex items-center space-x-2">
                        <textarea 
                            wire:model="replyText" 
                            rows="2" 
                            placeholder="Type a message to customer..."
                            class="flex-1 text-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:ring-primary-500"
                        ></textarea>
                        
                        <div class="flex flex-col space-y-1">
                            <button 
                                type="button" 
                                wire:click="generateAiSuggestions" 
                                title="Get AI Reply Suggestions"
                                class="p-2 text-xs rounded-md bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 hover:bg-purple-200"
                            >
                                ✨ AI
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                            >
                                Send 🚀
                            </button>
                        </div>
                    </form>
                </div>

            @else
                <div class="flex-1 flex items-center justify-center text-gray-400 text-sm">
                    Select a conversation to start messaging.
                </div>
            @endif
        </div>

        <!-- PANE 3: Contact Profile, Tags, Internal Notes & AI Assistant (Right Column) -->
        <div class="w-80 border-l border-gray-200 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-900/30 overflow-y-auto p-4 space-y-4">
            @if($this->activeConversation)
                <!-- Contact Card -->
                <div class="text-center p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                    <div class="w-14 h-14 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 font-bold text-xl mx-auto flex items-center justify-center mb-2">
                        {{ strtoupper(substr($this->activeConversation->contact->profile_name, 0, 2)) }}
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                        {{ $this->activeConversation->contact->profile_name }}
                    </h3>
                    <p class="text-xs text-gray-400 font-mono">
                        {{ $this->activeConversation->contact->phone_number }}
                    </p>

                    @if($this->activeConversation->contact->people)
                        <div class="mt-2 text-left bg-gray-50 dark:bg-gray-800 p-2 rounded text-[11px]">
                            <span class="font-semibold text-gray-600 dark:text-gray-300">Relaticle CRM Link:</span>
                            <a href="/admin/people/{{ $this->activeConversation->contact->people_id }}" target="_blank" class="text-primary-600 dark:text-primary-400 underline block mt-0.5">
                                👤 {{ $this->activeConversation->contact->people->name }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Tags Manager -->
                <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm space-y-2">
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
                <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm space-y-2">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">Agent Notes</h4>
                    <form wire:submit.prevent="addNote" class="space-y-1.5">
                        <textarea 
                            wire:model="newNoteText" 
                            rows="2" 
                            placeholder="Add private note..."
                            class="w-full text-xs rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                        ></textarea>
                        <button type="submit" class="w-full py-1 text-[11px] font-semibold bg-gray-800 text-white dark:bg-gray-700 rounded hover:bg-gray-700">
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
                    No active chat selected.
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
