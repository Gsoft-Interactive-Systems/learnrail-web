<?php
$lessonType = $lesson['type'] ?? 'text';
$videoUrl = $lesson['video_url'] ?? '';
$content = $lesson['content'] ?? '';
$isCompleted = ($lesson['progress_status'] ?? '') === 'completed';

// Check if this is a Bunny Stream URL
$isBunnyEmbed = strpos($videoUrl, 'mediadelivery.net') !== false || strpos($videoUrl, 'iframe.') !== false;
?>

<div class="grid" style="grid-template-columns: 1fr 350px; gap: 24px;">
    <!-- Main Content -->
    <div>
        <!-- Video or Content -->
        <?php if ($lessonType === 'video' && $videoUrl): ?>
            <div class="card mb-6">
                <div class="video-container" style="aspect-ratio: 16/9; background: #000; border-radius: var(--radius-lg) var(--radius-lg) 0 0; overflow: hidden;">
                    <?php if ($isBunnyEmbed): ?>
                        <!-- Bunny Stream Embed (no download, secure streaming) -->
                        <iframe
                            src="<?= e($videoUrl) ?>"
                            loading="lazy"
                            style="border: none; width: 100%; height: 100%;"
                            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;"
                            allowfullscreen="true"
                        ></iframe>
                    <?php else: ?>
                        <!-- Fallback video player (for non-Bunny URLs) -->
                        <video
                            controls
                            controlsList="nodownload"
                            oncontextmenu="return false;"
                            style="width: 100%; height: 100%;"
                            id="lesson-video"
                        >
                            <source src="<?= e($videoUrl) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h1 class="text-xl font-bold mb-2"><?= e($lesson['title']) ?></h1>
                    <div class="d-flex gap-4 text-sm text-secondary">
                        <?php if ($lesson['video_duration'] ?? 0): ?>
                            <span><i class="iconoir-clock"></i> <?= format_duration($lesson['video_duration']) ?></span>
                        <?php endif; ?>
                        <span><i class="iconoir-book"></i> <?= e($course['title'] ?? 'Course') ?></span>
                        <?php if ($lesson['module_title'] ?? ''): ?>
                            <span><i class="iconoir-folder"></i> <?= e($lesson['module_title']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Text Lesson -->
            <div class="card mb-6">
                <div class="card-header">
                    <h1 class="text-xl font-bold"><?= e($lesson['title']) ?></h1>
                    <div class="d-flex gap-4 text-sm text-secondary mt-2">
                        <span><i class="iconoir-book"></i> <?= e($course['title'] ?? 'Course') ?></span>
                        <?php if ($lesson['module_title'] ?? ''): ?>
                            <span><i class="iconoir-folder"></i> <?= e($lesson['module_title']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body lesson-content">
                    <div class="prose">
                        <?php
                        // Simple markdown parsing for common elements
                        $html = e($content);
                        // Headers
                        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
                        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
                        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
                        // Bold and italic
                        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
                        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
                        // Code blocks
                        $html = preg_replace('/```(\w+)?\n([\s\S]+?)```/', '<pre><code>$2</code></pre>', $html);
                        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
                        // Lists
                        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
                        $html = preg_replace('/(<li>.*<\/li>\n?)+/', '<ul>$0</ul>', $html);
                        // Line breaks
                        $html = nl2br($html);
                        echo $html;
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lesson Description -->
        <?php if (!empty($lesson['description'])): ?>
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">About This Lesson</h3>
                </div>
                <div class="card-body">
                    <?= nl2br(e($lesson['description'])) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation & Complete -->
        <div class="d-flex gap-4 flex-wrap">
            <?php if (!empty($prevLesson)): ?>
                <a href="/courses/<?= e($courseId) ?>/lessons/<?= e($prevLesson['id']) ?>" class="btn btn-secondary">
                    <i class="iconoir-arrow-left"></i>
                    Previous Lesson
                </a>
            <?php else: ?>
                <a href="/courses/<?= e($courseId) ?>" class="btn btn-secondary">
                    <i class="iconoir-arrow-left"></i>
                    Back to Course
                </a>
            <?php endif; ?>

            <?php if ($isCompleted): ?>
                <button class="btn btn-secondary flex-1" id="complete-btn" disabled>
                    <i class="iconoir-check-circle"></i>
                    Completed
                </button>
            <?php else: ?>
                <button class="btn btn-success flex-1" id="complete-btn" onclick="markComplete()">
                    <i class="iconoir-check"></i>
                    Mark as Complete
                </button>
            <?php endif; ?>

            <?php if (!empty($nextLesson)): ?>
                <a href="/courses/<?= e($courseId) ?>/lessons/<?= e($nextLesson['id']) ?>" class="btn btn-primary">
                    Next Lesson
                    <i class="iconoir-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Course Progress -->
        <div class="card mb-4" style="position: sticky; top: 88px;">
            <div class="card-header">
                <h3 class="card-title">Course Progress</h3>
            </div>
            <div class="card-body">
                <div class="progress-bar mb-2">
                    <div class="progress-bar-fill" style="width: <?= $courseProgress ?? 0 ?>%;"></div>
                </div>
                <p class="text-sm text-secondary mb-4"><?= $courseProgress ?? 0 ?>% complete</p>

                <!-- Lesson List -->
                <div class="lesson-nav">
                    <?php foreach ($allLessons ?? [] as $navLesson): ?>
                        <a href="/courses/<?= e($courseId) ?>/lessons/<?= e($navLesson['id']) ?>"
                           class="lesson-nav-item <?= ($navLesson['id'] == $lesson['id']) ? 'active' : '' ?> <?= ($navLesson['progress_status'] ?? '') === 'completed' ? 'completed' : '' ?>">
                            <span class="lesson-nav-icon">
                                <?php if (($navLesson['progress_status'] ?? '') === 'completed'): ?>
                                    <i class="iconoir-check-circle"></i>
                                <?php elseif ($navLesson['type'] === 'video'): ?>
                                    <i class="iconoir-play"></i>
                                <?php else: ?>
                                    <i class="iconoir-page"></i>
                                <?php endif; ?>
                            </span>
                            <span class="lesson-nav-title"><?= e($navLesson['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- AI Tutor -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="iconoir-brain text-primary"></i>
                    AI Tutor
                </h3>
            </div>
            <div class="card-body">
                <p class="text-sm text-secondary mb-4">
                    Have questions about this lesson? Ask our AI tutor for help!
                </p>
                <a href="/ai-tutor?course_id=<?= e($courseId) ?>&lesson_id=<?= e($lesson['id']) ?>" class="btn btn-primary btn-block">
                    <i class="iconoir-chat-bubble"></i>
                    Ask AI Tutor
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Prose styling for text lessons */
.prose h1, .prose h2, .prose h3 {
    margin-top: 1.5em;
    margin-bottom: 0.5em;
    font-weight: 600;
}
.prose h1 { font-size: 1.5rem; }
.prose h2 { font-size: 1.25rem; }
.prose h3 { font-size: 1.1rem; }
.prose p { margin-bottom: 1em; }
.prose ul {
    list-style: disc;
    margin-left: 1.5em;
    margin-bottom: 1em;
}
.prose li { margin-bottom: 0.25em; }
.prose code {
    background: var(--gray-100);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9em;
}
.prose pre {
    background: var(--gray-900);
    color: var(--gray-100);
    padding: 16px;
    border-radius: var(--radius);
    overflow-x: auto;
    margin-bottom: 1em;
}
.prose pre code {
    background: none;
    padding: 0;
    color: inherit;
}

/* Lesson navigation */
.lesson-nav {
    max-height: 300px;
    overflow-y: auto;
}
.lesson-nav-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    margin: 4px 0;
    border-radius: var(--radius);
    color: var(--gray-600);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}
.lesson-nav-item:hover {
    background: var(--gray-50);
    color: var(--gray-900);
}
.lesson-nav-item.active {
    background: var(--primary-light);
    color: var(--primary);
    font-weight: 500;
}
.lesson-nav-item.completed .lesson-nav-icon {
    color: var(--success);
}
.lesson-nav-icon {
    width: 20px;
    flex-shrink: 0;
}
.lesson-nav-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Video container - prevent right-click */
.video-container {
    user-select: none;
}
</style>

<script>
async function markComplete() {
    const btn = document.getElementById('complete-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span> Saving...';

    try {
        const response = await API.post('/lessons/<?= e($lesson['id']) ?>/complete');
        if (response.success) {
            Toast.success('Lesson marked as complete!');
            btn.innerHTML = '<i class="iconoir-check-circle"></i> Completed';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');

            // Update progress bar if provided
            if (response.progress !== undefined) {
                const progressBar = document.querySelector('.progress-bar-fill');
                const progressText = document.querySelector('.progress-bar + p');
                if (progressBar) progressBar.style.width = response.progress + '%';
                if (progressText) progressText.textContent = response.progress + '% complete';
            }

            // Mark current lesson as completed in nav
            const currentNavItem = document.querySelector('.lesson-nav-item.active');
            if (currentNavItem) {
                currentNavItem.classList.add('completed');
                currentNavItem.querySelector('.lesson-nav-icon').innerHTML = '<i class="iconoir-check-circle"></i>';
            }
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to mark as complete');
        btn.disabled = false;
        btn.innerHTML = '<i class="iconoir-check"></i> Mark as Complete';
    }
}
</script>
