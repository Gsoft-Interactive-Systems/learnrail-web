<!-- Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary">Manage subscription plans and active subscribers</p>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Active Subscribers</div>
            <div class="text-2xl font-bold text-primary"><?= number_format($stats['active'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Basic Plan</div>
            <div class="text-2xl font-bold"><?= number_format($stats['basic'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Premium Plan</div>
            <div class="text-2xl font-bold text-warning"><?= number_format($stats['premium'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Expiring Soon</div>
            <div class="text-2xl font-bold text-danger"><?= number_format($stats['expiring_soon'] ?? 0) ?></div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs mb-6" x-data="{ tab: 'subscribers' }">
    <button class="tab-btn" :class="{ 'active': tab === 'subscribers' }" @click="tab = 'subscribers'">
        Active Subscribers
    </button>
    <button class="tab-btn" :class="{ 'active': tab === 'plans' }" @click="tab = 'plans'">
        Subscription Plans
    </button>

    <!-- Subscribers List -->
    <div x-show="tab === 'subscribers'" class="mt-6">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="d-flex gap-4 items-end" method="GET">
                    <div class="form-group mb-0" style="flex: 1;">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-input" placeholder="Name or email..." value="<?= e($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Plan</label>
                        <select name="plan" class="form-select">
                            <option value="">All Plans</option>
                            <option value="basic" <?= ($_GET['plan'] ?? '') === 'basic' ? 'selected' : '' ?>>Basic</option>
                            <option value="premium" <?= ($_GET['plan'] ?? '') === 'premium' ? 'selected' : '' ?>>Premium</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="cancelled" <?= ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="expired" <?= ($_GET['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
        </div>

        <!-- Subscribers Table -->
        <div class="card">
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Plan</th>
                            <th>Started</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subscribers)): ?>
                            <?php foreach ($subscribers as $sub): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex items-center gap-3">
                                            <div class="avatar avatar-sm" style="background: var(--gradient);">
                                                <?= strtoupper(substr($sub['user_name'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="font-medium"><?= e($sub['user_name'] ?? '') ?></div>
                                                <div class="text-sm text-secondary"><?= e($sub['user_email'] ?? '') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($sub['plan'] ?? '') === 'premium' ? 'badge-warning' : 'badge-primary' ?>">
                                            <?= ucfirst($sub['plan'] ?? 'basic') ?>
                                        </span>
                                    </td>
                                    <td><?= format_date($sub['started_at'] ?? '') ?></td>
                                    <td>
                                        <?php
                                        $expires = strtotime($sub['expires_at'] ?? '');
                                        $isExpiringSoon = $expires && ($expires - time()) < (7 * 24 * 60 * 60);
                                        ?>
                                        <span class="<?= $isExpiringSoon ? 'text-danger font-semibold' : '' ?>">
                                            <?= format_date($sub['expires_at'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = match($sub['status'] ?? 'active') {
                                            'active' => 'badge-success',
                                            'cancelled' => 'badge-warning',
                                            'expired' => 'badge-secondary',
                                            default => 'badge-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $statusBadge ?>"><?= ucfirst($sub['status'] ?? 'active') ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-ghost btn-sm" onclick="extendSubscription(<?= e($sub['id']) ?>)" title="Extend">
                                                <i class="iconoir-plus-circle"></i>
                                            </button>
                                            <?php if (($sub['status'] ?? 'active') === 'active'): ?>
                                                <button class="btn btn-ghost btn-sm text-danger" onclick="cancelSubscription(<?= e($sub['id']) ?>)" title="Cancel">
                                                    <i class="iconoir-cancel"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-secondary">
                                    No subscribers found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subscription Plans -->
    <div x-show="tab === 'plans'" class="mt-6">
        <div class="grid grid-cols-3 gap-6">
            <?php foreach (($plans ?? []) as $plan): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?= e($plan['name'] ?? '') ?></h3>
                        <button class="btn btn-ghost btn-sm" onclick="editPlan(<?= e($plan['id'] ?? 0) ?>)">
                            <i class="iconoir-edit"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="text-3xl font-bold mb-2">
                            ₦<?= number_format($plan['price'] ?? 0) ?>
                            <span class="text-sm font-normal text-secondary">/<?= $plan['duration'] ?? 'month' ?></span>
                        </div>

                        <ul style="list-style: none;" class="mt-4">
                            <?php foreach (($plan['features'] ?? []) as $feature): ?>
                                <li class="d-flex items-center gap-2 mb-2">
                                    <i class="iconoir-check-circle text-success"></i>
                                    <?= e($feature) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="mt-4 pt-4" style="border-top: 1px solid var(--gray-100);">
                            <div class="text-sm text-secondary">
                                <?= number_format($plan['subscriber_count'] ?? 0) ?> active subscribers
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Extend Subscription Modal -->
<div class="modal-overlay" id="extend-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Extend Subscription</h3>
            <button class="modal-close" onclick="Modal.close('extend-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="extend-form">
                <input type="hidden" name="subscription_id" id="extend-sub-id">
                <div class="form-group">
                    <label class="form-label">Extension Period</label>
                    <select name="period" class="form-select">
                        <option value="7">7 days</option>
                        <option value="14">14 days</option>
                        <option value="30">30 days (1 month)</option>
                        <option value="90">90 days (3 months)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason (Optional)</label>
                    <textarea name="reason" class="form-textarea" placeholder="Reason for extension..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('extend-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitExtension()">Extend</button>
        </div>
    </div>
</div>

<script>
function extendSubscription(id) {
    document.getElementById('extend-sub-id').value = id;
    Modal.open('extend-modal');
}

async function submitExtension() {
    const form = document.getElementById('extend-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    try {
        const response = await API.post('/admin/subscriptions/' + data.subscription_id + '/extend', data);
        if (response.success) {
            Toast.success('Subscription extended successfully');
            Modal.close('extend-modal');
            location.reload();
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to extend subscription');
    }
}

async function cancelSubscription(id) {
    if (!confirm('Are you sure you want to cancel this subscription?')) return;

    try {
        const response = await API.post('/admin/subscriptions/' + id + '/cancel');
        if (response.success) {
            Toast.success('Subscription cancelled');
            location.reload();
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to cancel subscription');
    }
}

function editPlan(id) {
    window.location.href = '/admin/subscriptions/plans/' + id + '/edit';
}
</script>

<style>
.tabs {
    border-bottom: 1px solid var(--gray-200);
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--gray-500);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-btn:hover {
    color: var(--gray-900);
}

.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--gray-100);
}

.table th {
    font-weight: 600;
    color: var(--gray-500);
    font-size: 13px;
    text-transform: uppercase;
    background: var(--gray-50);
}

.table tbody tr:hover {
    background: var(--gray-50);
}
</style>
