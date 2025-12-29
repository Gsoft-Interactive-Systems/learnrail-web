<?php
$leaders = $leaderboard['users'] ?? $leaderboard ?? [];
$currentUserRank = $leaderboard['current_user_rank'] ?? null;
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold mb-2">Leaderboard</h1>
    <p class="text-secondary">See how you rank against other learners</p>
</div>

<!-- Current User Rank -->
<?php if ($currentUserRank): ?>
<div class="card mb-6" style="background: var(--gradient); color: white;">
    <div class="card-body">
        <div class="d-flex justify-between items-center flex-wrap gap-4">
            <div class="d-flex gap-4 items-center">
                <div style="font-size: 2.5rem; font-weight: 700; opacity: 0.9;">#<?= e($currentUserRank['rank'] ?? '?') ?></div>
                <div>
                    <div class="font-semibold text-lg">Your Current Rank</div>
                    <div style="opacity: 0.85;"><?= number_format($currentUserRank['points'] ?? $user['total_points'] ?? 0) ?> points</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm" style="opacity: 0.85;">Keep learning to climb higher!</div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card mb-6" style="background: var(--gradient); color: white;">
    <div class="card-body">
        <div class="d-flex justify-between items-center flex-wrap gap-4">
            <div class="d-flex gap-4 items-center">
                <div style="font-size: 2.5rem; font-weight: 700; opacity: 0.9;"><?= number_format($user['total_points'] ?? 0) ?></div>
                <div>
                    <div class="font-semibold text-lg">Your Points</div>
                    <div style="opacity: 0.85;">Level <?= e($user['current_level'] ?? 1) ?></div>
                </div>
            </div>
            <a href="/courses" class="btn btn-white">
                <i class="iconoir-book"></i>
                Start Learning
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Leaderboard -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="iconoir-trophy" style="color: var(--accent);"></i>
            Top Learners
        </h3>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($leaders)): ?>
            <?php foreach ($leaders as $index => $leader): ?>
                <?php
                $rank = $index + 1;
                $isCurrentUser = ($leader['id'] ?? '') == ($user['id'] ?? -1);
                ?>
                <div class="leaderboard-item <?= $isCurrentUser ? 'current-user' : '' ?>">
                    <div class="leaderboard-rank">
                        <?php if ($rank === 1): ?>
                            <span class="leaderboard-medal">🥇</span>
                        <?php elseif ($rank === 2): ?>
                            <span class="leaderboard-medal">🥈</span>
                        <?php elseif ($rank === 3): ?>
                            <span class="leaderboard-medal">🥉</span>
                        <?php else: ?>
                            <span class="text-secondary font-bold text-lg">#<?= $rank ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="avatar">
                        <?php if (!empty($leader['avatar'])): ?>
                            <img src="<?= e($leader['avatar']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($leader['first_name'] ?? 'U', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium">
                            <?= e(($leader['first_name'] ?? '') . ' ' . ($leader['last_name'] ?? '')) ?>
                            <?php if ($isCurrentUser): ?>
                                <span class="badge badge-primary ml-2">You</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-secondary">Level <?= e($leader['current_level'] ?? 1) ?></div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-primary text-lg"><?= number_format($leader['total_points'] ?? 0) ?></div>
                        <div class="text-xs text-secondary">points</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="iconoir-trophy"></i>
                </div>
                <div class="empty-state-title">No Leaderboard Data Yet</div>
                <div class="empty-state-text">Start learning to appear on the leaderboard!</div>
            </div>
        <?php endif; ?>
    </div>
</div>
