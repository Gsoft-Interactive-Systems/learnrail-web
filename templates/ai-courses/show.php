<?php
$isEnrolled = $course['is_enrolled'] ?? false;
$totalLessons = $course['total_lessons'] ?? 0;
?>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Main Content -->
    <div>
        <!-- Course Header -->
        <div class="card mb-6">
            <?php if (!empty($course['thumbnail'])): ?>
                <div class="course-hero" style="background-image: url('<?= e($course['thumbnail']) ?>');"></div>
            <?php else: ?>
                <div class="course-hero ai-gradient">
                    <i class="iconoir-brain" style="font-size: 4rem; color: white;"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <span class="badge badge-primary"><i class="iconoir-brain"></i> AI-Powered Course</span>
                    <?php if (!empty($course['level'])): ?>
                        <span class="badge badge-secondary"><?= ucfirst(e($course['level'])) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($course['category_name'])): ?>
                        <span class="badge badge-outline"><?= e($course['category_name']) ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl font-bold mb-3"><?= e($course['title']) ?></h1>
                <p class="text-secondary mb-4"><?= nl2br(e($course['description'] ?? '')) ?></p>

                <div class="d-flex gap-4 text-sm text-secondary">
                    <span><i class="iconoir-book"></i> <?= $totalLessons ?> lessons</span>
                    <?php if (!empty($course['estimated_duration'])): ?>
                        <span><i class="iconoir-clock"></i> <?= e($course['estimated_duration']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- How AI Learning Works -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title"><i class="iconoir-brain text-primary"></i> How AI Learning Works</h3>
            </div>
            <div class="card-body">
                <div class="ai-features-grid">
                    <div class="ai-feature">
                        <div class="ai-feature-icon">
                            <i class="iconoir-chat-bubble"></i>
                        </div>
                        <h4>Interactive Conversations</h4>
                        <p>Learn through natural dialogue with our AI tutor that adapts to your pace.</p>
                    </div>
                    <div class="ai-feature">
                        <div class="ai-feature-icon">
                            <i class="iconoir-help-circle"></i>
                        </div>
                        <h4>Ask Questions Anytime</h4>
                        <p>Get instant answers and clarifications on any concept you're learning.</p>
                    </div>
                    <div class="ai-feature">
                        <div class="ai-feature-icon">
                            <i class="iconoir-target"></i>
                        </div>
                        <h4>Personalized Learning</h4>
                        <p>The AI adjusts explanations based on your understanding and background.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Learning Objectives -->
        <?php if (!empty($course['learning_objectives'])): ?>
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">What You'll Learn</h3>
            </div>
            <div class="card-body">
                <p><?= nl2br(e($course['learning_objectives'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Curriculum -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Course Curriculum</h3>
                <span class="text-sm text-secondary"><?= count($course['modules'] ?? []) ?> modules, <?= $totalLessons ?> lessons</span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($course['modules'])): ?>
                    <?php foreach ($course['modules'] as $moduleIndex => $module): ?>
                        <div class="curriculum-module">
                            <div class="curriculum-module-header">
                                <span class="badge badge-primary"><?= $moduleIndex + 1 ?></span>
                                <span class="font-semibold"><?= e($module['title']) ?></span>
                                <span class="text-sm text-secondary ml-auto"><?= count($module['lessons'] ?? []) ?> lessons</span>
                            </div>
                            <?php if (!empty($module['lessons'])): ?>
                                <div class="curriculum-lessons">
                                    <?php foreach ($module['lessons'] as $lesson): ?>
                                        <div class="curriculum-lesson">
                                            <i class="iconoir-brain text-primary"></i>
                                            <span><?= e($lesson['title']) ?></span>
                                            <?php if (!empty($lesson['estimated_minutes'])): ?>
                                                <span class="text-sm text-secondary ml-auto"><?= $lesson['estimated_minutes'] ?> min</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-6 text-center text-secondary">
                        Curriculum is being prepared...
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <div class="card" style="position: sticky; top: 88px;">
            <div class="card-body">
                <?php if ($isEnrolled): ?>
                    <a href="/ai-courses/<?= e($course['id']) ?>/learn" class="btn btn-primary btn-block btn-lg mb-3">
                        <i class="iconoir-play"></i>
                        Continue Learning
                    </a>
                    <p class="text-sm text-secondary text-center">You're enrolled in this course</p>
                <?php else: ?>
                    <form action="/ai-courses/<?= e($course['id']) ?>/enroll" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-block btn-lg mb-3">
                            <i class="iconoir-brain"></i>
                            Start Learning with AI
                        </button>
                    </form>
                    <?php if (!empty($course['is_premium'])): ?>
                        <p class="text-sm text-secondary text-center">
                            <i class="iconoir-star"></i> Premium course - Requires subscription
                        </p>
                    <?php else: ?>
                        <p class="text-sm text-secondary text-center">Free to enroll</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="card-body border-top">
                <h4 class="font-semibold mb-3">This course includes:</h4>
                <ul class="feature-list">
                    <li><i class="iconoir-brain text-primary"></i> AI-powered interactive tutoring</li>
                    <li><i class="iconoir-book text-primary"></i> <?= $totalLessons ?> comprehensive lessons</li>
                    <li><i class="iconoir-chat-bubble text-primary"></i> Ask unlimited questions</li>
                    <li><i class="iconoir-clock text-primary"></i> Learn at your own pace</li>
                    <li><i class="iconoir-smartphone text-primary"></i> Access on any device</li>
                </ul>
            </div>

            <?php if (!empty($course['prerequisites'])): ?>
            <div class="card-body border-top">
                <h4 class="font-semibold mb-3">Prerequisites</h4>
                <p class="text-sm text-secondary"><?= nl2br(e($course['prerequisites'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.course-hero {
    height: 200px;
    background-size: cover;
    background-position: center;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.course-hero.ai-gradient {
    background: linear-gradient(135deg, #8B5CF6 0%, #6366F1 50%, #4F46E5 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.ai-features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.ai-feature {
    text-align: center;
    padding: 20px;
}

.ai-feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    color: var(--primary);
    font-size: 1.5rem;
}

.ai-feature h4 {
    font-size: 0.95rem;
    margin-bottom: 8px;
}

.ai-feature p {
    font-size: 0.85rem;
    color: var(--gray-500);
    margin: 0;
}

.curriculum-module {
    border-bottom: 1px solid var(--gray-100);
}

.curriculum-module:last-child {
    border-bottom: none;
}

.curriculum-module-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--gray-50);
}

.curriculum-lessons {
    padding: 8px 16px 16px 48px;
}

.curriculum-lesson {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    font-size: 14px;
    color: var(--gray-600);
    border-bottom: 1px solid var(--gray-100);
}

.curriculum-lesson:last-child {
    border-bottom: none;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    font-size: 14px;
    color: var(--gray-600);
}

.border-top {
    border-top: 1px solid var(--gray-100);
}

.btn-lg {
    padding: 14px 24px;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .ai-features-grid {
        grid-template-columns: 1fr;
    }
}
</style>
