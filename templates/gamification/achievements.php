<?php
$earnedBadges = array_filter($badges ?? [], fn($b) => !empty($b['earned_at']));
$lockedBadges = array_filter($badges ?? [], fn($b) => empty($b['earned_at']));
?>

<!-- Stats -->
<div class="grid grid-cols-3 mb-6">
    <div class="stat-card text-center">
        <div class="stat-card-value"><?= count($earnedBadges) ?></div>
        <div class="stat-card-label">Badges Earned</div>
    </div>
    <div class="stat-card text-center">
        <div class="stat-card-value"><?= number_format($user['total_points'] ?? 0) ?></div>
        <div class="stat-card-label">Total Points</div>
    </div>
    <div class="stat-card text-center">
        <div class="stat-card-value">Level <?= $user['current_level'] ?? 1 ?></div>
        <div class="stat-card-label">Current Level</div>
    </div>
</div>

<!-- Earned Badges -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">Earned Badges</h3>
        <span class="badge badge-success"><?= count($earnedBadges) ?></span>
    </div>
    <div class="card-body">
        <?php if (!empty($earnedBadges)): ?>
            <div class="grid grid-cols-4">
                <?php foreach ($earnedBadges as $badge): ?>
                    <div class="text-center p-4">
                        <div class="avatar avatar-lg mb-3" style="margin: 0 auto; background: var(--gradient);">
                            <i class="iconoir-trophy"></i>
                        </div>
                        <div class="font-medium mb-1"><?= e($badge['name'] ?? 'Badge') ?></div>
                        <div class="text-xs text-secondary"><?= e($badge['description'] ?? '') ?></div>
                        <div class="text-xs text-success mt-2">
                            Earned <?= time_ago($badge['earned_at']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="iconoir-trophy"></i>
                </div>
                <h3 class="empty-state-title">No Badges Yet</h3>
                <p class="empty-state-text">Start learning to earn your first badge!</p>
                <a href="/courses" class="btn btn-primary">Browse Courses</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Locked Badges -->
<?php if (!empty($lockedBadges)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Badges to Unlock</h3>
        <span class="badge badge-gray"><?= count($lockedBadges) ?></span>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-4">
            <?php foreach ($lockedBadges as $badge): ?>
                <div class="text-center p-4" style="opacity: 0.5;">
                    <div class="avatar avatar-lg mb-3" style="margin: 0 auto; background: var(--gray-300);">
                        <i class="iconoir-lock"></i>
                    </div>
                    <div class="font-medium mb-1"><?= e($badge['name'] ?? 'Badge') ?></div>
                    <div class="text-xs text-secondary"><?= e($badge['description'] ?? '') ?></div>
                    <?php if (!empty($badge['requirement'])): ?>
                        <div class="text-xs text-primary mt-2"><?= e($badge['requirement']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
