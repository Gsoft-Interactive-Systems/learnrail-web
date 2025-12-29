<?php
$isEdit = !empty($plan);
$action = $isEdit ? "/admin/subscriptions/plans/{$plan['id']}/update" : '/admin/subscriptions/plans/store';
$title = $isEdit ? 'Edit Plan: ' . ($plan['name'] ?? '') : 'Create Plan';
?>

<div class="d-flex justify-between items-center mb-6">
    <div>
        <a href="/admin/subscriptions" class="btn btn-ghost btn-sm mb-2">
            <i class="iconoir-arrow-left"></i>
            Back to Subscriptions
        </a>
        <h1 class="text-2xl font-bold"><?= e($title) ?></h1>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <form action="<?= $action ?>" method="POST" data-loading id="plan-form">
            <?= csrf_field() ?>

            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Plan Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Plan Name</label>
                        <input type="text" name="name" class="form-input" value="<?= e($plan['name'] ?? '') ?>" required placeholder="e.g., Premium Monthly">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-input" value="<?= e($plan['slug'] ?? '') ?>" required placeholder="e.g., premium-monthly">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" rows="3" placeholder="Brief description of the plan..."><?= e($plan['description'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Price (NGN)</label>
                            <input type="number" name="price" class="form-input" value="<?= e($plan['price'] ?? '') ?>" required step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Original Price (Optional)</label>
                            <input type="number" name="original_price" class="form-input" value="<?= e($plan['original_price'] ?? '') ?>" step="0.01" min="0" placeholder="For showing discount">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Duration (Days)</label>
                            <input type="number" name="duration_days" class="form-input" value="<?= e($plan['duration_days'] ?? '30') ?>" required min="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Duration (Months)</label>
                            <input type="number" name="duration_months" class="form-input" value="<?= e($plan['duration_months'] ?? '1') ?>" required min="1">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Features</h3>
                </div>
                <div class="card-body" x-data="{ features: <?= json_encode($plan['features'] ?? ['']) ?> }">
                    <template x-for="(feature, index) in features" :key="index">
                        <div class="d-flex gap-3 mb-3">
                            <input type="text" :name="'features[' + index + ']'" class="form-input" x-model="features[index]" placeholder="Feature description...">
                            <button type="button" class="btn btn-ghost text-danger" @click="features.splice(index, 1)" x-show="features.length > 1">
                                <i class="iconoir-trash"></i>
                            </button>
                        </div>
                    </template>
                    <button type="button" class="btn btn-ghost btn-sm" @click="features.push('')">
                        <i class="iconoir-plus"></i>
                        Add Feature
                    </button>
                </div>
            </div>

            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary">
                    <i class="iconoir-check"></i>
                    <?= $isEdit ? 'Update Plan' : 'Create Plan' ?>
                </button>
                <a href="/admin/subscriptions" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Options</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="d-flex items-center gap-3">
                        <input type="checkbox" name="is_active" value="1" <?= ($plan['is_active'] ?? true) ? 'checked' : '' ?> style="width: 18px; height: 18px;" form="plan-form">
                        <span>Active</span>
                    </label>
                    <p class="text-sm text-secondary mt-1">Only active plans are shown to users</p>
                </div>

                <div class="form-group">
                    <label class="d-flex items-center gap-3">
                        <input type="checkbox" name="is_popular" value="1" <?= ($plan['is_popular'] ?? false) ? 'checked' : '' ?> style="width: 18px; height: 18px;" form="plan-form">
                        <span>Mark as Popular</span>
                    </label>
                    <p class="text-sm text-secondary mt-1">Shows "Popular" badge on the plan</p>
                </div>

                <div class="form-group">
                    <label class="d-flex items-center gap-3">
                        <input type="checkbox" name="includes_goal_tracker" value="1" <?= ($plan['includes_goal_tracker'] ?? false) ? 'checked' : '' ?> style="width: 18px; height: 18px;" form="plan-form">
                        <span>Include Goal Tracker</span>
                    </label>
                </div>

                <div class="form-group mb-0">
                    <label class="d-flex items-center gap-3">
                        <input type="checkbox" name="includes_accountability_partner" value="1" <?= ($plan['includes_accountability_partner'] ?? false) ? 'checked' : '' ?> style="width: 18px; height: 18px;" form="plan-form">
                        <span>Include Accountability Partner</span>
                    </label>
                </div>
            </div>
        </div>

        <?php if ($isEdit): ?>
        <div class="card mt-4">
            <div class="card-body">
                <p class="text-sm text-secondary mb-3">
                    <?= number_format($plan['subscriber_count'] ?? 0) ?> active subscribers on this plan
                </p>
                <?php if (($plan['subscriber_count'] ?? 0) == 0): ?>
                <button class="btn btn-danger btn-block" onclick="confirmDelete()">
                    <i class="iconoir-trash"></i>
                    Delete Plan
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isEdit): ?>
<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this plan? This cannot be undone.')) {
        fetch('/admin/subscriptions/plans/<?= e($plan['id']) ?>/delete', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                window.location.href = '/admin/subscriptions';
            } else {
                Toast.error(data.message || 'Failed to delete plan');
            }
        });
    }
}
</script>
<?php endif; ?>
