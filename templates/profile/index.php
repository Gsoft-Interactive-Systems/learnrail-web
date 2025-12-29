<?php
$stats = $profile['stats'] ?? [];
$subscription = $profile['subscription'] ?? null;
$recentBadges = $profile['recent_badges'] ?? [];
?>

<!-- Profile Header -->
<div class="card mb-6">
    <div class="card-body">
        <div class="d-flex gap-6 items-center flex-wrap">
            <div class="avatar avatar-xl">
                <?php if (!empty($profile['avatar'])): ?>
                    <img src="<?= e($profile['avatar']) ?>" alt="Avatar">
                <?php else: ?>
                    <?= strtoupper(substr($profile['first_name'] ?? 'U', 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-bold mb-1">
                    <?= e(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')) ?>
                </h1>
                <p class="text-secondary mb-3"><?= e($profile['email'] ?? '') ?></p>
                <div class="d-flex gap-3">
                    <?php if ($subscription): ?>
                        <span class="badge badge-success">Premium Member</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Free Plan</span>
                    <?php endif; ?>
                    <span class="badge badge-primary">Level <?= e($profile['current_level'] ?? 1) ?></span>
                </div>
            </div>
            <div class="d-flex gap-3">
                <a href="/profile/edit" class="btn btn-outline">
                    <i class="iconoir-edit"></i>
                    Edit Profile
                </a>
                <a href="/settings" class="btn btn-ghost">
                    <i class="iconoir-settings"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-4 mb-6">
    <div class="stat-card text-center">
        <div class="stat-card-value"><?= number_format($stats['enrolled_courses'] ?? 0) ?></div>
        <div class="stat-card-label">Enrolled Courses</div>
    </div>
    <div class="stat-card text-center">
        <div class="stat-card-value"><?= number_format($stats['completed_courses'] ?? 0) ?></div>
        <div class="stat-card-label">Completed</div>
    </div>
    <div class="stat-card text-center">
        <div class="stat-card-value"><?= number_format($stats['certificates'] ?? 0) ?></div>
        <div class="stat-card-label">Certificates</div>
    </div>
    <div class="stat-card text-center">
        <div class="stat-card-value"><?= number_format($profile['total_points'] ?? 0) ?></div>
        <div class="stat-card-label">Points</div>
    </div>
</div>

<div class="grid grid-cols-2">
    <!-- Subscription Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Subscription</h3>
        </div>
        <div class="card-body">
            <?php if ($subscription): ?>
                <div class="d-flex justify-between items-center mb-4">
                    <div>
                        <h4 class="font-semibold"><?= e($subscription['plan_name'] ?? 'Premium') ?></h4>
                        <p class="text-sm text-secondary">Active until <?= format_date($subscription['end_date'] ?? '') ?></p>
                    </div>
                    <span class="badge badge-success">Active</span>
                </div>
                <div class="text-sm text-secondary">
                    <div class="d-flex gap-2 mb-2">
                        <i class="iconoir-check text-success"></i>
                        Goal Tracking
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <i class="iconoir-check text-success"></i>
                        Accountability Partner
                    </div>
                    <div class="d-flex gap-2">
                        <i class="iconoir-check text-success"></i>
                        Unlimited Courses
                    </div>
                </div>
            <?php else: ?>
                <p class="text-secondary mb-4">You're on the free plan. Upgrade to unlock premium features.</p>
                <a href="/subscription" class="btn btn-primary btn-block">
                    <i class="iconoir-star"></i>
                    Upgrade Now
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Badges -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Badges</h3>
            <a href="/achievements" class="section-link">View All</a>
        </div>
        <div class="card-body">
            <?php if (!empty($recentBadges)): ?>
                <div class="d-flex gap-4 flex-wrap">
                    <?php foreach ($recentBadges as $badge): ?>
                        <div class="text-center" style="width: 80px;">
                            <div class="avatar avatar-lg mb-2" style="background: var(--accent);">
                                <i class="iconoir-trophy"></i>
                            </div>
                            <div class="text-xs font-medium"><?= e($badge['name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-secondary">No badges earned yet. Start learning to earn badges!</p>
            <?php endif; ?>
        </div>
    </div>
</div>
