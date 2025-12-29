<?php
$leaders = $leaderboard['users'] ?? $leaderboard ?? [];
$currentUserRank = $leaderboard['current_user_rank'] ?? null;
?>

<!-- Current User Rank -->
<?php if ($currentUserRank): ?>
<div class="card mb-6" style="background: var(--gradient); color: white;">
    <div class="card-body d-flex justify-between items-center">
        <div class="d-flex gap-4 items-center">
            <div style="font-size: 2rem; font-weight: 700;">#<?= e($currentUserRank['rank'] ?? '?') ?></div>
            <div>
                <div class="font-semibold">Your Rank</div>
                <div style="opacity: 0.8;"><?= number_format($currentUserRank['points'] ?? $user['total_points'] ?? 0) ?> points</div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm" style="opacity: 0.8;">Keep learning to climb!</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Leaderboard -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Top Learners</h3>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($leaders)): ?>
            <?php foreach ($leaders as $index => $leader): ?>
                <?php
                $rank = $index + 1;
                $isCurrentUser = ($leader['id'] ?? '') == ($user['id'] ?? -1);
                $rankIcon = match($rank) {
                    1 => '<span style="color: #FFD700; font-size: 1.5rem;">&#x1F947;</span>',
                    2 => '<span style="color: #C0C0C0; font-size: 1.5rem;">&#x1F948;</span>',
                    3 => '<span style="color: #CD7F32; font-size: 1.5rem;">&#x1F949;</span>',
                    default => '<span class="text-secondary font-bold">#' . $rank . '</span>'
                };
                ?>
                <div class="d-flex items-center gap-4 p-4 <?= $isCurrentUser ? 'bg-primary-light' : '' ?>" style="border-bottom: 1px solid var(--gray-100); <?= $isCurrentUser ? 'background: rgba(99, 102, 241, 0.05);' : '' ?>">
                    <div style="width: 40px; text-align: center;">
                        <?= $rankIcon ?>
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
                                <span class="badge badge-primary">You</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-secondary">Level <?= e($leader['current_level'] ?? 1) ?></div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-primary"><?= number_format($leader['total_points'] ?? 0) ?></div>
                        <div class="text-xs text-secondary">points</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-6 text-center text-secondary">
                No leaderboard data available.
            </div>
        <?php endif; ?>
    </div>
</div>
