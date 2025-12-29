<?php
$lessonType = $lesson['type'] ?? 'text';
$videoUrl = $lesson['video_url'] ?? '';
$content = $lesson['content'] ?? '';
?>

<div class="grid" style="grid-template-columns: 1fr 350px; gap: 24px;">
    <!-- Main Content -->
    <div>
        <!-- Video or Content -->
        <?php if ($lessonType === 'video' && $videoUrl): ?>
            <div class="card mb-6">
                <div style="aspect-ratio: 16/9; background: #000; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                    <video controls style="width: 100%; height: 100%;" id="lesson-video">
                        <source src="<?= e($videoUrl) ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div class="card-body">
                    <h1 class="text-xl font-bold mb-2"><?= e($lesson['title']) ?></h1>
                    <div class="d-flex gap-4 text-sm text-secondary">
                        <span><i class="iconoir-clock"></i> <?= format_duration($lesson['video_duration'] ?? 0) ?></span>
                        <span><i class="iconoir-book"></i> <?= e($course['title'] ?? 'Course') ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-6">
                <div class="card-header">
                    <h1 class="text-xl font-bold"><?= e($lesson['title']) ?></h1>
                </div>
                <div class="card-body">
                    <?= nl2br(e($content)) ?>
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

        <!-- Mark Complete -->
        <div class="d-flex gap-4">
            <a href="/courses/<?= e($courseId) ?>" class="btn btn-secondary">
                <i class="iconoir-arrow-left"></i>
                Back to Course
            </a>
            <button class="btn btn-success flex-1" id="complete-btn" onclick="markComplete()">
                <i class="iconoir-check"></i>
                Mark as Complete
            </button>
        </div>
    </div>

    <!-- Sidebar - AI Tutor -->
    <div>
        <div class="card" style="position: sticky; top: 88px;">
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
                <a href="/ai-tutor?course_id=<?= e($courseId) ?>" class="btn btn-primary btn-block">
                    <i class="iconoir-chat-bubble"></i>
                    Ask AI Tutor
                </a>
            </div>
        </div>
    </div>
</div>

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
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to mark as complete');
        btn.disabled = false;
        btn.innerHTML = '<i class="iconoir-check"></i> Mark as Complete';
    }
}
</script>
