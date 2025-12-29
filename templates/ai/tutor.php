<div class="chat-container" x-data="aiChat(<?= e($courseId ?? 'null') ?>)">
    <!-- Chat Messages -->
    <div class="chat-messages" x-ref="messages">
        <template x-for="(message, index) in messages" :key="index">
            <div class="chat-message" :class="message.role">
                <div class="chat-message-avatar avatar" :class="message.role === 'assistant' ? 'bg-primary' : ''">
                    <template x-if="message.role === 'assistant'">
                        <i class="iconoir-brain"></i>
                    </template>
                    <template x-if="message.role === 'user'">
                        <span><?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?></span>
                    </template>
                </div>
                <div class="chat-message-content" x-text="message.content"></div>
            </div>
        </template>

        <!-- Typing Indicator -->
        <div class="chat-message assistant" x-show="isTyping">
            <div class="chat-message-avatar avatar bg-primary">
                <i class="iconoir-brain"></i>
            </div>
            <div class="chat-message-content">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Questions -->
    <div class="p-4 border-t" style="border-color: var(--gray-200); background: var(--white);" x-show="messages.length <= 1">
        <p class="text-sm text-secondary mb-3">Suggested questions:</p>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-outline" @click="input = 'Explain this concept in simple terms'; sendMessage()">
                Explain simply
            </button>
            <button class="btn btn-sm btn-outline" @click="input = 'Give me a practice example'; sendMessage()">
                Practice example
            </button>
            <button class="btn btn-sm btn-outline" @click="input = 'What should I learn next?'; sendMessage()">
                What's next?
            </button>
            <button class="btn btn-sm btn-outline" @click="input = 'How can I apply this in real life?'; sendMessage()">
                Real-world use
            </button>
        </div>
    </div>

    <!-- Chat Input -->
    <div class="chat-input-container">
        <form class="chat-input-form" @submit.prevent="sendMessage()">
            <input type="text"
                   class="chat-input"
                   placeholder="Ask me anything about your course..."
                   x-model="input"
                   :disabled="isTyping">
            <button type="submit" class="chat-send-btn" :disabled="!input.trim() || isTyping">
                <i class="iconoir-send"></i>
            </button>
        </form>
    </div>
</div>

<style>
.chat-container {
    height: calc(100vh - var(--header-height) - 48px);
    display: flex;
    flex-direction: column;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.bg-primary {
    background: var(--gradient) !important;
}

.chat-message-content {
    white-space: pre-wrap;
    word-break: break-word;
}
</style>
