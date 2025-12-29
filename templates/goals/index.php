<!-- Header Actions -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary">Track your learning progress</p>
    </div>
    <a href="/goals/create" class="btn btn-primary">
        <i class="iconoir-plus"></i>
        Create Goal
    </a>
</div>

<!-- Goals Grid -->
<?php if (!empty($goals)): ?>
    <div class="grid grid-cols-2">
        <?php foreach ($goals as $goal): ?>
            <?php
            $progress = $goal['progress'] ?? 0;
            $daysRemaining = $goal['days_remaining'] ?? 0;
            $status = $goal['status'] ?? 'active';
            ?>
            <div class="goal-card">
                <div class="goal-card-header">
                    <div>
                        <h3 class="goal-card-title"><?= e($goal['title'] ?? 'Goal') ?></h3>
                        <span class="badge <?= $status === 'completed' ? 'badge-success' : ($status === 'overdue' ? 'badge-danger' : 'badge-primary') ?>">
                            <?= ucfirst($status) ?>
                        </span>
                    </div>
                    <div class="goal-card-date">
                        <?php if ($daysRemaining > 0): ?>
                            <?= $daysRemaining ?> days left
                        <?php elseif ($status === 'completed'): ?>
                            Completed
                        <?php else: ?>
                            Overdue
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($goal['description'])): ?>
                    <p class="text-sm text-secondary mb-4"><?= e($goal['description']) ?></p>
                <?php endif; ?>

                <div class="goal-card-progress">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: <?= $progress ?>%;"></div>
                    </div>
                </div>

                <div class="goal-card-footer">
                    <span class="text-secondary"><?= $progress ?>% complete</span>
                    <a href="/goals/<?= e($goal['id']) ?>" class="btn btn-ghost btn-sm">
                        View Details
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="iconoir-target"></i>
        </div>
        <h3 class="empty-state-title">No Goals Yet</h3>
        <p class="empty-state-text">Set your first learning goal and start tracking your progress!</p>
        <a href="/goals/create" class="btn btn-primary">
            <i class="iconoir-plus"></i>
            Create Your First Goal
        </a>
    </div>
<?php endif; ?>
