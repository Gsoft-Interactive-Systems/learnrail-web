<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">Create New Goal</h3>
    </div>
    <div class="card-body">
        <form action="/goals/create" method="POST" data-loading>
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="title">Goal Title</label>
                <input type="text" id="title" name="title" class="form-input" placeholder="e.g., Complete Python Course" value="<?= old('title') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description (Optional)</label>
                <textarea id="description" name="description" class="form-textarea" placeholder="Describe your goal and why it's important to you..."><?= old('description') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="category">Category</label>
                <select id="category" name="category" class="form-select">
                    <option value="learning" <?= old('category') === 'learning' ? 'selected' : '' ?>>Learning</option>
                    <option value="career" <?= old('category') === 'career' ? 'selected' : '' ?>>Career</option>
                    <option value="certification" <?= old('category') === 'certification' ? 'selected' : '' ?>>Certification</option>
                    <option value="project" <?= old('category') === 'project' ? 'selected' : '' ?>>Project</option>
                    <option value="other" <?= old('category') === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="target_date">Target Date</label>
                <input type="date" id="target_date" name="target_date" class="form-input" value="<?= old('target_date') ?>" required min="<?= date('Y-m-d') ?>">
            </div>

            <div class="d-flex gap-3 mt-6">
                <button type="submit" class="btn btn-primary">
                    <i class="iconoir-check"></i>
                    Create Goal
                </button>
                <a href="/goals" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tips -->
<div class="card mt-6" style="max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">Tips for Setting Goals</h3>
    </div>
    <div class="card-body">
        <ul style="list-style: none;">
            <li class="d-flex gap-3 mb-3">
                <i class="iconoir-check-circle text-success"></i>
                <span><strong>Be Specific</strong> - Define exactly what you want to achieve</span>
            </li>
            <li class="d-flex gap-3 mb-3">
                <i class="iconoir-check-circle text-success"></i>
                <span><strong>Set Realistic Deadlines</strong> - Give yourself enough time</span>
            </li>
            <li class="d-flex gap-3 mb-3">
                <i class="iconoir-check-circle text-success"></i>
                <span><strong>Break It Down</strong> - Add milestones to track progress</span>
            </li>
            <li class="d-flex gap-3">
                <i class="iconoir-check-circle text-success"></i>
                <span><strong>Check In Regularly</strong> - Update your progress often</span>
            </li>
        </ul>
    </div>
</div>
