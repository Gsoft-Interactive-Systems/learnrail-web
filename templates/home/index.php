<!-- Welcome Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary mb-1">Welcome back,</p>
        <h2 class="text-2xl font-bold"><?= e($user['first_name'] ?? 'Learner') ?></h2>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid mb-6">
    <?php Core\View::component('stat-card', [
        'icon' => 'medal',
        'color' => 'warning',
        'label' => 'Points',
        'value' => number_format($user['total_points'] ?? 0)
    ]); ?>
    <?php Core\View::component('stat-card', [
        'icon' => 'graph-up',
        'color' => 'success',
        'label' => 'Level',
        'value' => $user['current_level'] ?? 1
    ]); ?>
    <?php Core\View::component('stat-card', [
        'icon' => 'fire',
        'color' => 'danger',
        'label' => 'Streak',
        'value' => ($user['streak'] ?? 0) . ' days'
    ]); ?>
</div>

<!-- Continue Learning -->
<?php if (!empty($enrolledCourses)): ?>
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Continue Learning</h3>
        <a href="/courses" class="section-link">View All</a>
    </div>
    <div class="card-body">
        <?php $course = $enrolledCourses[0]; ?>
        <div class="d-flex gap-4 items-center">
            <img src="<?= e($course['thumbnail'] ?? '/images/course-placeholder.jpg') ?>" alt="" style="width: 120px; height: 80px; object-fit: cover; border-radius: var(--radius);">
            <div class="flex-1">
                <h4 class="font-semibold mb-1"><?= e($course['title'] ?? 'Course') ?></h4>
                <p class="text-sm text-secondary mb-2">By <?= e($course['instructor_name'] ?? 'Instructor') ?></p>
                <div class="progress-bar" style="max-width: 200px;">
                    <div class="progress-bar-fill" style="width: <?= e($course['progress_percent'] ?? 0) ?>%;"></div>
                </div>
                <p class="text-xs text-secondary mt-1"><?= e($course['progress_percent'] ?? 0) ?>% complete</p>
            </div>
            <a href="/courses/<?= e($course['course_id'] ?? $course['id']) ?>" class="btn btn-primary">
                Continue
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="mb-6">
    <h3 class="section-title mb-4">Quick Actions</h3>
    <div class="quick-actions">
        <a href="/courses" class="quick-action">
            <div class="quick-action-icon primary">
                <i class="iconoir-book"></i>
            </div>
            <span>Courses</span>
        </a>
        <a href="/career" class="quick-action">
            <div class="quick-action-icon success">
                <i class="iconoir-suitcase"></i>
            </div>
            <span>Career Guide</span>
        </a>
        <a href="/leaderboard" class="quick-action">
            <div class="quick-action-icon warning">
                <i class="iconoir-medal"></i>
            </div>
            <span>Leaderboard</span>
        </a>
        <?php if ($isSubscribed ?? false): ?>
        <a href="/goals" class="quick-action">
            <div class="quick-action-icon danger">
                <i class="iconoir-target"></i>
            </div>
            <span>Goals</span>
        </a>
        <?php else: ?>
        <a href="/subscription" class="quick-action">
            <div class="quick-action-icon danger">
                <i class="iconoir-star"></i>
            </div>
            <span>Go Premium</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Featured Courses -->
<?php if (!empty($featuredCourses)): ?>
<div class="mb-6">
    <div class="section-header">
        <h3 class="section-title">Featured Courses</h3>
        <a href="/courses" class="section-link">View All</a>
    </div>
    <div class="scroll-container">
        <?php foreach ($featuredCourses as $course): ?>
            <?php Core\View::component('course-card', ['course' => $course, 'compact' => true]); ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Subscription CTA (if not subscribed) -->
<?php if (!($isSubscribed ?? false)): ?>
<div class="card" style="background: var(--gradient); color: white;">
    <div class="card-body text-center">
        <h3 class="font-bold text-xl mb-2">Unlock Premium Features</h3>
        <p class="mb-4" style="opacity: 0.9;">Get access to goal tracking, accountability partners, and unlimited courses.</p>
        <a href="/subscription" class="btn btn-white">
            <i class="iconoir-star"></i>
            Upgrade Now
        </a>
    </div>
</div>
<?php endif; ?>
