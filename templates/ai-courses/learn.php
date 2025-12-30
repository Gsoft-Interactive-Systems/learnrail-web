<?php
$currentLessonId = $_GET['lesson'] ?? null;
$currentLesson = null;
$currentModule = null;

// Find current lesson or default to first
foreach ($course['modules'] ?? [] as $module) {
    foreach ($module['lessons'] ?? [] as $lesson) {
        if ($currentLessonId && $lesson['id'] == $currentLessonId) {
            $currentLesson = $lesson;
            $currentModule = $module;
            break 2;
        }
        if (!$currentLesson) {
            $currentLesson = $lesson;
            $currentModule = $module;
        }
    }
}
?>

<div class="ai-learn-container">
    <!-- Sidebar - Curriculum -->
    <div class="ai-learn-sidebar">
        <div class="ai-learn-sidebar-header">
            <a href="/ai-courses/<?= e($course['id']) ?>" class="btn btn-ghost btn-sm">
                <i class="iconoir-arrow-left"></i>
            </a>
            <h3><?= e($course['title']) ?></h3>
        </div>
        <div class="ai-learn-curriculum">
            <?php foreach ($course['modules'] ?? [] as $moduleIndex => $module): ?>
                <div class="ai-module">
                    <div class="ai-module-header">
                        <span class="text-sm font-semibold text-secondary">Module <?= $moduleIndex + 1 ?></span>
                        <span class="font-medium"><?= e($module['title']) ?></span>
                    </div>
                    <div class="ai-module-lessons">
                        <?php foreach ($module['lessons'] ?? [] as $lesson): ?>
                            <a href="/ai-courses/<?= e($course['id']) ?>/learn?lesson=<?= e($lesson['id']) ?>"
                               class="ai-lesson-link <?= ($currentLesson && $currentLesson['id'] == $lesson['id']) ? 'active' : '' ?>">
                                <i class="iconoir-brain"></i>
                                <span><?= e($lesson['title']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="ai-learn-main">
        <div class="ai-chat-header">
            <div>
                <h2 class="font-semibold"><?= e($currentLesson['title'] ?? 'Select a lesson') ?></h2>
                <p class="text-sm text-secondary"><?= e($currentModule['title'] ?? '') ?></p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-ghost btn-sm" onclick="toggleSidebar()">
                    <i class="iconoir-menu"></i>
                </button>
            </div>
        </div>

        <div class="ai-chat-container" id="chat-container">
            <!-- Welcome message -->
            <div class="ai-message">
                <div class="ai-message-avatar">
                    <i class="iconoir-brain"></i>
                </div>
                <div class="ai-message-content">
                    <p>Hello! I'm your AI tutor for <strong><?= e($course['title']) ?></strong>.</p>
                    <?php if ($currentLesson): ?>
                        <p>We're about to explore <strong><?= e($currentLesson['title']) ?></strong>.</p>
                        <?php if (!empty($currentLesson['content_outline'])): ?>
                            <p class="mt-3">In this lesson, we'll cover:</p>
                            <div class="ai-outline"><?= nl2br(e($currentLesson['content_outline'])) ?></div>
                        <?php endif; ?>
                        <p class="mt-3">Ready to begin? Just type "start" or ask me anything about this topic!</p>
                    <?php else: ?>
                        <p>Select a lesson from the sidebar to begin learning.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat Input -->
        <div class="ai-chat-input-container">
            <form id="chat-form" class="ai-chat-form">
                <input type="hidden" id="course-id" value="<?= e($course['id']) ?>">
                <input type="hidden" id="lesson-id" value="<?= e($currentLesson['id'] ?? '') ?>">
                <input type="text" id="chat-input" class="ai-chat-input"
                       placeholder="Ask a question or type 'start' to begin..."
                       autocomplete="off" <?= !$currentLesson ? 'disabled' : '' ?>>
                <button type="submit" class="btn btn-primary" <?= !$currentLesson ? 'disabled' : '' ?>>
                    <i class="iconoir-send"></i>
                </button>
            </form>
            <p class="text-xs text-secondary text-center mt-2">
                AI responses are generated. Always verify important information.
            </p>
        </div>
    </div>
</div>

<style>
.ai-learn-container {
    display: flex;
    height: calc(100vh - var(--header-height) - 40px);
    margin: -24px;
    background: var(--white);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.ai-learn-sidebar {
    width: 320px;
    border-right: 1px solid var(--gray-200);
    display: flex;
    flex-direction: column;
    background: var(--gray-50);
}

.ai-learn-sidebar-header {
    padding: 16px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--white);
}

.ai-learn-sidebar-header h3 {
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ai-learn-curriculum {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

.ai-module {
    margin-bottom: 16px;
}

.ai-module-header {
    padding: 8px 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ai-module-lessons {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ai-lesson-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: var(--radius);
    color: var(--gray-600);
    font-size: 14px;
    transition: all 0.2s;
}

.ai-lesson-link:hover {
    background: var(--white);
    color: var(--gray-900);
}

.ai-lesson-link.active {
    background: var(--primary);
    color: var(--white);
}

.ai-learn-main {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.ai-chat-header {
    padding: 16px 24px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ai-chat-container {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ai-message {
    display: flex;
    gap: 12px;
    max-width: 80%;
}

.ai-message.user {
    flex-direction: row-reverse;
    align-self: flex-end;
}

.ai-message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B5CF6 0%, #6366F1 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.ai-message.user .ai-message-avatar {
    background: var(--gray-300);
    color: var(--gray-600);
}

.ai-message-content {
    background: var(--gray-100);
    padding: 16px;
    border-radius: var(--radius-lg);
    border-top-left-radius: 4px;
}

.ai-message.user .ai-message-content {
    background: var(--primary);
    color: var(--white);
    border-top-left-radius: var(--radius-lg);
    border-top-right-radius: 4px;
}

.ai-message-content p {
    margin-bottom: 8px;
}

.ai-message-content p:last-child {
    margin-bottom: 0;
}

.ai-outline {
    background: rgba(255,255,255,0.5);
    padding: 12px;
    border-radius: var(--radius);
    font-size: 0.9rem;
    margin-top: 8px;
}

.ai-chat-input-container {
    padding: 16px 24px;
    border-top: 1px solid var(--gray-200);
    background: var(--white);
}

.ai-chat-form {
    display: flex;
    gap: 12px;
}

.ai-chat-input {
    flex: 1;
    padding: 14px 20px;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius-full);
    font-size: 15px;
    outline: none;
    transition: all 0.2s;
}

.ai-chat-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.ai-chat-input:disabled {
    background: var(--gray-100);
}

.ai-typing {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 8px 0;
}

.ai-typing span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--gray-400);
    animation: typing 1.4s infinite both;
}

.ai-typing span:nth-child(2) { animation-delay: 0.2s; }
.ai-typing span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
}

@media (max-width: 768px) {
    .ai-learn-sidebar {
        position: fixed;
        left: -320px;
        top: 0;
        height: 100vh;
        z-index: 1000;
        transition: left 0.3s;
    }

    .ai-learn-sidebar.open {
        left: 0;
    }

    .ai-message {
        max-width: 95%;
    }
}
</style>

<script>
const chatContainer = document.getElementById('chat-container');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const courseId = document.getElementById('course-id').value;
const lessonId = document.getElementById('lesson-id').value;

// Chat history for context
let chatHistory = [];

chatForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    const message = chatInput.value.trim();
    if (!message) return;

    // Add user message to UI
    addMessage(message, 'user');
    chatInput.value = '';

    // Add to history
    chatHistory.push({ role: 'user', content: message });

    // Show typing indicator
    const typingId = showTyping();

    try {
        const response = await fetch('/api/ai-tutor/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= csrf_token() ?>'
            },
            body: JSON.stringify({
                course_id: courseId,
                lesson_id: lessonId,
                message: message,
                history: chatHistory.slice(-10) // Last 10 messages for context
            })
        });

        const data = await response.json();
        removeTyping(typingId);

        if (data.success && data.response) {
            addMessage(data.response, 'ai');
            chatHistory.push({ role: 'assistant', content: data.response });
        } else {
            addMessage(data.error || 'Sorry, I encountered an error. Please try again.', 'ai');
        }
    } catch (error) {
        removeTyping(typingId);
        addMessage('Sorry, I had trouble processing that. Please try again.', 'ai');
    }
});

function addMessage(content, type) {
    const div = document.createElement('div');
    div.className = `ai-message ${type}`;

    const avatarIcon = type === 'user' ? 'iconoir-user' : 'iconoir-brain';

    div.innerHTML = `
        <div class="ai-message-avatar">
            <i class="${avatarIcon}"></i>
        </div>
        <div class="ai-message-content">
            <p>${escapeHtml(content).replace(/\n/g, '<br>')}</p>
        </div>
    `;

    chatContainer.appendChild(div);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function showTyping() {
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'ai-message';
    div.innerHTML = `
        <div class="ai-message-avatar">
            <i class="iconoir-brain"></i>
        </div>
        <div class="ai-message-content">
            <div class="ai-typing">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;
    chatContainer.appendChild(div);
    chatContainer.scrollTop = chatContainer.scrollHeight;
    return id;
}

function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleSidebar() {
    document.querySelector('.ai-learn-sidebar').classList.toggle('open');
}
</script>
