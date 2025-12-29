<?php
$thumbnail = $course['thumbnail'] ?? '/images/course-placeholder.jpg';
$instructor = $course['instructor'] ?? [];
$modules = $course['modules'] ?? [];
$enrollment = $course['enrollment'] ?? null;
$isEnrolled = $enrollment !== null;
$progress = $enrollment['progress_percent'] ?? 0;
?>

<div class="grid" style="grid-template-columns: 1fr 350px; gap: 24px;">
    <!-- Main Content -->
    <div>
        <!-- Course Header -->
        <div class="card mb-6">
            <div class="course-card-thumbnail" style="aspect-ratio: 21/9;">
                <img src="<?= e($thumbnail) ?>" alt="<?= e($course['title']) ?>">
                <?php if ($course['is_featured'] ?? false): ?>
                    <span class="course-card-badge">Featured</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <span class="badge badge-primary"><?= e(ucfirst($course['level'] ?? 'Beginner')) ?></span>
                    <span class="badge badge-gray"><?= e($course['category_name'] ?? 'General') ?></span>
                </div>
                <h1 class="text-2xl font-bold mb-3"><?= e($course['title']) ?></h1>
                <p class="text-secondary mb-4"><?= e($course['short_description'] ?? '') ?></p>

                <div class="d-flex gap-4 items-center">
                    <?php if (!empty($instructor)): ?>
                        <div class="d-flex gap-3 items-center">
                            <div class="avatar">
                                <?php if (!empty($instructor['avatar'])): ?>
                                    <img src="<?= e($instructor['avatar']) ?>" alt="">
                                <?php else: ?>
                                    <?= strtoupper(substr($instructor['name'] ?? 'I', 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="text-sm font-medium"><?= e($instructor['name'] ?? 'Instructor') ?></div>
                                <div class="text-xs text-secondary">Instructor</div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex gap-4 text-sm text-secondary">
                        <span><i class="iconoir-star text-warning"></i> <?= number_format($course['rating'] ?? 0, 1) ?></span>
                        <span><i class="iconoir-group"></i> <?= number_format($course['total_enrollments'] ?? 0) ?> students</span>
                        <span><i class="iconoir-clock"></i> <?= format_duration(($course['duration_hours'] ?? 0) * 60) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Description -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">About This Course</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <?= nl2br(e($course['description'] ?? 'No description available.')) ?>
                </div>

                <?php if (!empty($course['what_you_learn'])): ?>
                    <h4 class="font-semibold mb-3">What You'll Learn</h4>
                    <ul class="mb-4" style="list-style: none;">
                        <?php foreach ($course['what_you_learn'] as $item): ?>
                            <li class="d-flex gap-2 mb-2">
                                <i class="iconoir-check-circle text-success"></i>
                                <?= e($item) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($course['requirements'])): ?>
                    <h4 class="font-semibold mb-3">Requirements</h4>
                    <ul class="mb-4" style="list-style: disc; margin-left: 20px;">
                        <?php foreach ($course['requirements'] as $req): ?>
                            <li class="mb-1"><?= e($req) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Course Content -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Course Content</h3>
                <span class="text-sm text-secondary">
                    <?= count($modules) ?> modules &bull; <?= $course['total_lessons'] ?? 0 ?> lessons
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($modules)): ?>
                    <?php foreach ($modules as $index => $module): ?>
                        <div class="border-b" style="border-color: var(--gray-100);" x-data="{ open: <?= $index === 0 ? 'true' : 'false' ?> }">
                            <button class="w-100 d-flex justify-between items-center p-4 cursor-pointer" style="background: none; border: none; text-align: left;" @click="open = !open">
                                <div class="d-flex gap-3 items-center">
                                    <i class="iconoir-folder text-primary"></i>
                                    <div>
                                        <div class="font-medium"><?= e($module['title']) ?></div>
                                        <div class="text-sm text-secondary"><?= count($module['lessons'] ?? []) ?> lessons</div>
                                    </div>
                                </div>
                                <i class="iconoir-nav-arrow-down" :class="{ 'rotate-180': open }" style="transition: transform 0.3s;"></i>
                            </button>
                            <div x-show="open" x-collapse>
                                <?php foreach ($module['lessons'] ?? [] as $lesson): ?>
                                    <div class="d-flex justify-between items-center p-4" style="padding-left: 48px; border-top: 1px solid var(--gray-100);">
                                        <div class="d-flex gap-3 items-center">
                                            <i class="iconoir-<?= ($lesson['type'] ?? 'text') === 'video' ? 'play' : 'page' ?> text-secondary"></i>
                                            <span><?= e($lesson['title']) ?></span>
                                            <?php if ($lesson['is_free_preview'] ?? false): ?>
                                                <span class="badge badge-success">Preview</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($isEnrolled || ($lesson['is_free_preview'] ?? false)): ?>
                                            <a href="/courses/<?= e($course['id']) ?>/lessons/<?= e($lesson['id']) ?>" class="btn btn-ghost btn-sm">
                                                View
                                            </a>
                                        <?php else: ?>
                                            <i class="iconoir-lock text-secondary"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-6 text-center text-secondary">
                        No content available yet.
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
                    <!-- Enrolled State -->
                    <div class="text-center mb-4">
                        <div class="badge badge-success mb-2">Enrolled</div>
                        <div class="progress-bar mb-2">
                            <div class="progress-bar-fill" style="width: <?= $progress ?>%;"></div>
                        </div>
                        <p class="text-sm text-secondary"><?= $progress ?>% complete</p>
                    </div>
                    <a href="/courses/<?= e($course['id']) ?>/lessons/<?= e($enrollment['last_lesson_id'] ?? ($modules[0]['lessons'][0]['id'] ?? '')) ?>" class="btn btn-primary btn-block btn-lg mb-3">
                        <?= $progress > 0 ? 'Continue Learning' : 'Start Course' ?>
                    </a>
                <?php else: ?>
                    <!-- Not Enrolled State -->
                    <?php if ($course['is_free'] ?? false): ?>
                        <div class="text-center mb-4">
                            <div class="text-3xl font-bold text-success">Free</div>
                        </div>
                    <?php else: ?>
                        <div class="text-center mb-4">
                            <div class="text-3xl font-bold"><?= format_currency($course['price'] ?? 0) ?></div>
                        </div>
                    <?php endif; ?>
                    <form action="/courses/<?= e($course['id']) ?>/enroll" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-block btn-lg mb-3">
                            Enroll Now
                        </button>
                    </form>
                <?php endif; ?>

                <div class="text-sm text-secondary">
                    <div class="d-flex justify-between py-2 border-b" style="border-color: var(--gray-100);">
                        <span>Lessons</span>
                        <strong><?= $course['total_lessons'] ?? 0 ?></strong>
                    </div>
                    <div class="d-flex justify-between py-2 border-b" style="border-color: var(--gray-100);">
                        <span>Duration</span>
                        <strong><?= format_duration(($course['duration_hours'] ?? 0) * 60) ?></strong>
                    </div>
                    <div class="d-flex justify-between py-2 border-b" style="border-color: var(--gray-100);">
                        <span>Level</span>
                        <strong><?= ucfirst($course['level'] ?? 'Beginner') ?></strong>
                    </div>
                    <div class="d-flex justify-between py-2">
                        <span>Certificate</span>
                        <strong>Yes</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
