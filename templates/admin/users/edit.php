<!-- Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <a href="/admin/users" class="text-secondary d-inline-flex items-center gap-2 mb-2">
            <i class="iconoir-arrow-left"></i>
            Back to Users
        </a>
        <h1 class="text-2xl font-bold">Edit User</h1>
    </div>
</div>

<div class="grid grid-cols-3 gap-6">
    <!-- Main Form -->
    <div class="col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">User Information</h3>
            </div>
            <div class="card-body">
                <form action="/admin/users/<?= e($user['id']) ?>/edit" method="POST">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-input" value="<?= e($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-input" value="<?= e($user['last_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" value="<?= e($user['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" value="<?= e($user['phone'] ?? '') ?>">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="user" <?= ($user['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="partner" <?= ($user['role'] ?? '') === 'partner' ? 'selected' : '' ?>>Partner</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="iconoir-check"></i>
                            Save Changes
                        </button>
                        <a href="/admin/users" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-span-1">
        <!-- User Profile Card -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="avatar avatar-xl mx-auto mb-4" style="background: var(--gradient); width: 80px; height: 80px; font-size: 32px;">
                    <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                </div>
                <h4 class="font-bold"><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h4>
                <p class="text-secondary text-sm"><?= e($user['email'] ?? '') ?></p>
                <div class="mt-3">
                    <span class="badge <?= ($user['role'] ?? 'user') === 'admin' ? 'badge-danger' : 'badge-secondary' ?>">
                        <?= ucfirst($user['role'] ?? 'user') ?>
                    </span>
                    <span class="badge <?= ($user['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-warning' ?>">
                        <?= ucfirst($user['status'] ?? 'active') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">User Stats</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-between mb-3">
                    <span class="text-secondary">Total Points</span>
                    <span class="font-bold"><?= number_format($user['total_points'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-between mb-3">
                    <span class="text-secondary">Current Streak</span>
                    <span class="font-bold"><?= $user['current_streak'] ?? 0 ?> days</span>
                </div>
                <div class="d-flex justify-between mb-3">
                    <span class="text-secondary">Courses Enrolled</span>
                    <span class="font-bold"><?= $user['enrollment_count'] ?? 0 ?></span>
                </div>
                <div class="d-flex justify-between mb-3">
                    <span class="text-secondary">Courses Completed</span>
                    <span class="font-bold"><?= $user['courses_completed'] ?? 0 ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span class="text-secondary">Subscription</span>
                    <span class="font-bold"><?= e($user['subscription_plan'] ?? 'Free') ?></span>
                </div>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Account Info</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-between mb-3">
                    <span class="text-secondary">User ID</span>
                    <span class="font-mono">#<?= e($user['id'] ?? '') ?></span>
                </div>
                <div class="d-flex justify-between mb-3">
                    <span class="text-secondary">Joined</span>
                    <span><?= format_date($user['created_at'] ?? '') ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span class="text-secondary">Last Login</span>
                    <span><?= $user['last_login'] ? format_date($user['last_login']) : 'Never' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.col-span-2 {
    grid-column: span 2;
}
.col-span-1 {
    grid-column: span 1;
}
.avatar-xl {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: white;
}
.mx-auto {
    margin-left: auto;
    margin-right: auto;
}
.font-mono {
    font-family: monospace;
}
</style>
