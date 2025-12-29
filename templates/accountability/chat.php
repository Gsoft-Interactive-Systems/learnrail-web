<div class="chat-container" x-data="accountabilityChat(<?= e($partner['id'] ?? 'null') ?>)">
    <!-- Chat Header -->
    <div class="chat-header">
        <a href="/accountability" class="btn btn-ghost btn-sm">
            <i class="iconoir-arrow-left"></i>
        </a>
        <div class="d-flex items-center gap-3 flex-1">
            <div class="avatar" style="background: var(--gradient);">
                <?= strtoupper(substr($partner['first_name'] ?? 'P', 0, 1)) ?>
            </div>
            <div>
                <div class="font-semibold"><?= e($partner['first_name'] ?? '') ?> <?= e($partner['last_name'] ?? '') ?></div>
                <div class="text-sm text-secondary" x-text="isOnline ? 'Online' : 'Offline'"></div>
            </div>
        </div>
        <div class="dropdown" x-data="dropdown()">
            <button class="btn btn-ghost btn-sm" @click="toggle()">
                <i class="iconoir-more-vert"></i>
            </button>
            <div class="dropdown-menu dropdown-right" x-show="open" @click.away="close()">
                <a href="/accountability" class="dropdown-item">
                    <i class="iconoir-user"></i>
                    View Profile
                </a>
                <button class="dropdown-item" @click="clearChat()">
                    <i class="iconoir-trash"></i>
                    Clear Chat
                </button>
            </div>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="chat-messages" x-ref="messages">
        <!-- Date Separator -->
        <div class="chat-date-separator" x-show="messages.length > 0">
            <span>Today</span>
        </div>

        <template x-for="(message, index) in messages" :key="message.id || index">
            <div class="chat-message" :class="message.sender_id == currentUserId ? 'user' : 'assistant'">
                <div class="chat-message-avatar avatar"
                     :style="message.sender_id == currentUserId ? '' : 'background: var(--gradient)'">
                    <span x-text="message.sender_id == currentUserId ? '<?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>' : '<?= strtoupper(substr($partner['first_name'] ?? 'P', 0, 1)) ?>'"></span>
                </div>
                <div>
                    <div class="chat-message-content" x-text="message.content"></div>
                    <div class="chat-message-time" x-text="formatTime(message.created_at)"></div>
                </div>
            </div>
        </template>

        <!-- Typing Indicator -->
        <div class="chat-message assistant" x-show="partnerTyping">
            <div class="chat-message-avatar avatar" style="background: var(--gradient);">
                <?= strtoupper(substr($partner['first_name'] ?? 'P', 0, 1)) ?>
            </div>
            <div class="chat-message-content">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="chat-empty" x-show="messages.length === 0 && !loading">
            <i class="iconoir-chat-bubble-empty" style="font-size: 3rem; color: var(--gray-300);"></i>
            <p class="text-secondary mt-3">No messages yet. Say hello to your partner!</p>
        </div>
    </div>

    <!-- Quick Replies -->
    <div class="p-3 border-t" style="border-color: var(--gray-200); background: var(--white);" x-show="messages.length <= 3">
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-outline" @click="input = 'Hey! How\\'s your learning going?'; sendMessage()">
                👋 Say hello
            </button>
            <button class="btn btn-sm btn-outline" @click="input = 'Just completed a lesson!'; sendMessage()">
                🎉 Share progress
            </button>
            <button class="btn btn-sm btn-outline" @click="input = 'Need some motivation today!'; sendMessage()">
                💪 Ask for motivation
            </button>
        </div>
    </div>

    <!-- Chat Input -->
    <div class="chat-input-container">
        <form class="chat-input-form" @submit.prevent="sendMessage()">
            <input type="text"
                   class="chat-input"
                   placeholder="Type a message..."
                   x-model="input"
                   @input="handleTyping()"
                   :disabled="sending">
            <button type="submit" class="chat-send-btn" :disabled="!input.trim() || sending">
                <i class="iconoir-send"></i>
            </button>
        </form>
    </div>
</div>

<style>
.chat-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--white);
    border-bottom: 1px solid var(--gray-200);
}

.chat-date-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.chat-date-separator span {
    background: var(--gray-100);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    color: var(--gray-500);
}

.chat-message-time {
    font-size: 11px;
    color: var(--gray-400);
    margin-top: 4px;
}

.chat-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px;
    text-align: center;
}

.chat-message.user .chat-message-content {
    background: var(--primary);
    color: white;
}

.chat-message.user {
    flex-direction: row-reverse;
}

.chat-message.user .chat-message-time {
    text-align: right;
}
</style>
