<?php
/**
 * Course Card Component
 * @param array $course - Course data
 * @param bool $compact - Compact mode for horizontal scroll
 */
$compact = $compact ?? false;
$thumbnail = $course['thumbnail'] ?? '/images/course-placeholder.jpg';
$instructor = $course['instructor_name'] ?? $course['instructor']['name'] ?? 'Unknown';
$rating = number_format($course['rating'] ?? 0, 1);

// Duration is stored as hours in duration_hours
$durationHours = (float)($course['duration_hours'] ?? 0);
if ($durationHours >= 1) {
    $durationText = floor($durationHours) . 'h';
    $remainingMins = round(($durationHours - floor($durationHours)) * 60);
    if ($remainingMins > 0) {
        $durationText .= ' ' . $remainingMins . 'm';
    }
} elseif ($durationHours > 0) {
    $durationText = round($durationHours * 60) . 'm';
} else {
    $durationText = '0m';
}
?>
<a href="/courses/<?= e($course['id'] ?? $course['slug'] ?? '') ?>" class="course-card <?= $compact ? 'compact' : '' ?>" style="<?= $compact ? 'width: 280px;' : '' ?>">
    <div class="course-card-thumbnail">
        <img src="<?= e($thumbnail) ?>" alt="<?= e($course['title'] ?? 'Course') ?>" loading="lazy">
        <?php if (!empty($course['is_featured'])): ?>
            <span class="course-card-badge">Featured</span>
        <?php elseif (!empty($course['is_free'])): ?>
            <span class="course-card-badge" style="background: var(--secondary);">Free</span>
        <?php endif; ?>
    </div>
    <div class="course-card-body">
        <h3 class="course-card-title"><?= e($course['title'] ?? 'Untitled Course') ?></h3>
        <p class="course-card-instructor">By <?= e($instructor) ?></p>
        <div class="course-card-meta">
            <span>
                <i class="iconoir-star"></i>
                <?= $rating ?>
            </span>
            <span>
                <i class="iconoir-clock"></i>
                <?= $durationText ?>
            </span>
            <span>
                <i class="iconoir-book"></i>
                <?= e($course['total_lessons'] ?? 0) ?> lessons
            </span>
        </div>
    </div>
</a>
