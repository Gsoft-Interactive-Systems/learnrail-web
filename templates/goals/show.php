<?php
$progress = $goal['progress'] ?? 0;
$daysRemaining = $goal['days_remaining'] ?? 0;
$status = $goal['status'] ?? 'active';
$milestones = $goal['milestones'] ?? [];
$checkins = $goal['checkins'] ?? [];
?>

<div class="grid" style="grid-template-columns: 1fr 350px; gap: 24px;">
    <!-- Main Content -->
    <div>
        <!-- Goal Header -->
        <div class="card mb-6">
            <div class="card-body">
                <div class="d-flex justify-between items-start mb-4">
                    <div>
                        <span class="badge <?= $status === 'completed' ? 'badge-success' : ($status === 'overdue' ? 'badge-danger' : 'badge-primary') ?> mb-2">
                            <?= ucfirst($status) ?>
                        </span>
                        <h1 class="text-2xl font-bold"><?= e($goal['title']) ?></h1>
                    </div>
                    <button class="btn btn-ghost btn-sm" onclick="Modal.open('edit-goal-modal')">
                        <i class="iconoir-edit"></i>
                    </button>
                </div>

                <?php if (!empty($goal['description'])): ?>
                    <p class="text-secondary mb-4"><?= e($goal['description']) ?></p>
                <?php endif; ?>

                <div class="d-flex gap-4 text-sm text-secondary">
                    <span><i class="iconoir-calendar"></i> Target: <?= format_date($goal['target_date'] ?? '') ?></span>
                    <span><i class="iconoir-folder"></i> <?= ucfirst($goal['category'] ?? 'Learning') ?></span>
                    <?php if ($daysRemaining > 0): ?>
                        <span><i class="iconoir-clock"></i> <?= $daysRemaining ?> days remaining</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Progress -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Progress</h3>
                <span class="font-bold text-primary"><?= $progress ?>%</span>
            </div>
            <div class="card-body">
                <div class="progress-bar mb-4" style="height: 12px;">
                    <div class="progress-bar-fill" style="width: <?= $progress ?>%;"></div>
                </div>

                <button class="btn btn-success btn-block" onclick="Modal.open('checkin-modal')">
                    <i class="iconoir-check"></i>
                    Daily Check-in
                </button>
            </div>
        </div>

        <!-- Milestones -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Milestones</h3>
                <button class="btn btn-ghost btn-sm" onclick="Modal.open('add-milestone-modal')">
                    <i class="iconoir-plus"></i>
                    Add
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($milestones)): ?>
                    <?php foreach ($milestones as $milestone): ?>
                        <div class="d-flex items-center gap-4 p-4" style="border-bottom: 1px solid var(--gray-100);">
                            <input type="checkbox" <?= ($milestone['completed'] ?? false) ? 'checked' : '' ?>
                                   onchange="toggleMilestone(<?= e($milestone['id']) ?>, this.checked)"
                                   style="width: 20px; height: 20px; accent-color: var(--primary);">
                            <div class="flex-1">
                                <div class="<?= ($milestone['completed'] ?? false) ? 'text-secondary line-through' : 'font-medium' ?>">
                                    <?= e($milestone['title']) ?>
                                </div>
                            </div>
                            <?php if ($milestone['completed'] ?? false): ?>
                                <span class="badge badge-success">Done</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-6 text-center text-secondary">
                        No milestones yet. Add milestones to break down your goal.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Check-ins -->
        <?php if (!empty($checkins)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Check-ins</h3>
            </div>
            <div class="card-body p-0">
                <?php foreach (array_slice($checkins, 0, 5) as $checkin): ?>
                    <div class="p-4" style="border-bottom: 1px solid var(--gray-100);">
                        <div class="d-flex justify-between mb-2">
                            <span class="font-medium"><?= format_date($checkin['created_at'] ?? '') ?></span>
                            <span class="text-sm text-secondary"><?= time_ago($checkin['created_at'] ?? '') ?></span>
                        </div>
                        <?php if (!empty($checkin['notes'])): ?>
                            <p class="text-secondary text-sm"><?= e($checkin['notes']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
        <div class="card" style="position: sticky; top: 88px;">
            <div class="card-body text-center">
                <div style="font-size: 4rem; margin-bottom: 16px;">
                    <?php if ($status === 'completed'): ?>
                        <i class="iconoir-check-circle text-success"></i>
                    <?php elseif ($progress >= 75): ?>
                        <i class="iconoir-fire text-warning"></i>
                    <?php else: ?>
                        <i class="iconoir-target text-primary"></i>
                    <?php endif; ?>
                </div>
                <h3 class="font-bold text-xl mb-2">
                    <?php if ($status === 'completed'): ?>
                        Goal Achieved!
                    <?php elseif ($progress >= 75): ?>
                        Almost There!
                    <?php else: ?>
                        Keep Going!
                    <?php endif; ?>
                </h3>
                <p class="text-secondary mb-4">
                    <?php if ($status !== 'completed'): ?>
                        <?= $daysRemaining > 0 ? $daysRemaining . ' days to reach your goal' : 'Goal deadline has passed' ?>
                    <?php else: ?>
                        You completed this goal!
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Check-in Modal -->
<div class="modal-overlay" id="checkin-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Daily Check-in</h3>
            <button class="modal-close" onclick="Modal.close('checkin-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="checkin-form">
                <div class="form-group">
                    <label class="form-label">How's your progress?</label>
                    <textarea name="notes" class="form-textarea" placeholder="Share your progress, wins, or challenges..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('checkin-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitCheckin()">Submit Check-in</button>
        </div>
    </div>
</div>

<script>
async function submitCheckin() {
    const form = document.getElementById('checkin-form');
    const notes = form.querySelector('[name="notes"]').value;

    try {
        const response = await API.post('/goals/<?= e($goal['id']) ?>/checkin', { notes });
        if (response.success) {
            Toast.success('Check-in recorded!');
            Modal.close('checkin-modal');
            location.reload();
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to submit check-in');
    }
}

async function toggleMilestone(id, completed) {
    try {
        await API.post('/milestones/' + id + '/complete', { completed });
        Toast.success(completed ? 'Milestone completed!' : 'Milestone uncompleted');
    } catch (error) {
        Toast.error('Failed to update milestone');
    }
}
</script>
