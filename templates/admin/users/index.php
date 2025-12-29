<!-- Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary">Manage all registered users</p>
    </div>
    <button class="btn btn-primary" onclick="Modal.open('add-user-modal')">
        <i class="iconoir-plus"></i>
        Add User
    </button>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form class="d-flex gap-4 items-end" method="GET">
            <div class="form-group mb-0" style="flex: 1;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-input" placeholder="Name or email..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="user" <?= ($_GET['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= ($_GET['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Subscription</label>
                <select name="subscription" class="form-select">
                    <option value="">All</option>
                    <option value="free" <?= ($_GET['subscription'] ?? '') === 'free' ? 'selected' : '' ?>>Free</option>
                    <option value="basic" <?= ($_GET['subscription'] ?? '') === 'basic' ? 'selected' : '' ?>>Basic</option>
                    <option value="premium" <?= ($_GET['subscription'] ?? '') === 'premium' ? 'selected' : '' ?>>Premium</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="iconoir-search"></i>
                Filter
            </button>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Subscription</th>
                    <th>XP</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex items-center gap-3">
                                    <div class="avatar avatar-sm" style="background: var(--gradient);">
                                        <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="font-medium"><?= e($user['first_name'] ?? '') ?> <?= e($user['last_name'] ?? '') ?></div>
                                        <div class="text-sm text-secondary">ID: <?= e($user['id'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($user['email'] ?? '') ?></td>
                            <td>
                                <span class="badge <?= ($user['role'] ?? 'user') === 'admin' ? 'badge-danger' : 'badge-secondary' ?>">
                                    <?= ucfirst($user['role'] ?? 'user') ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $subBadge = match($user['subscription_plan'] ?? 'free') {
                                    'premium' => 'badge-warning',
                                    'basic' => 'badge-primary',
                                    default => 'badge-secondary'
                                };
                                ?>
                                <span class="badge <?= $subBadge ?>">
                                    <?= ucfirst($user['subscription_plan'] ?? 'Free') ?>
                                </span>
                            </td>
                            <td><?= number_format($user['xp'] ?? 0) ?></td>
                            <td><?= format_date($user['created_at'] ?? '') ?></td>
                            <td>
                                <span class="badge <?= ($user['is_active'] ?? true) ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= ($user['is_active'] ?? true) ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-ghost btn-sm" onclick="viewUser(<?= e($user['id']) ?>)" title="View">
                                        <i class="iconoir-eye"></i>
                                    </button>
                                    <button class="btn btn-ghost btn-sm" onclick="editUser(<?= e($user['id']) ?>)" title="Edit">
                                        <i class="iconoir-edit"></i>
                                    </button>
                                    <?php if (($user['role'] ?? 'user') !== 'admin'): ?>
                                        <button class="btn btn-ghost btn-sm text-danger" onclick="confirmDelete(<?= e($user['id']) ?>)" title="Delete">
                                            <i class="iconoir-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-secondary">
                            No users found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="card-footer d-flex justify-between items-center">
            <span class="text-secondary">
                Showing <?= (($currentPage ?? 1) - 1) * 20 + 1 ?> to <?= min(($currentPage ?? 1) * 20, $totalUsers ?? 0) ?> of <?= $totalUsers ?? 0 ?> users
            </span>
            <div class="d-flex gap-2">
                <?php if (($currentPage ?? 1) > 1): ?>
                    <a href="?page=<?= ($currentPage ?? 1) - 1 ?>" class="btn btn-ghost btn-sm">Previous</a>
                <?php endif; ?>
                <?php if (($currentPage ?? 1) < ($totalPages ?? 1)): ?>
                    <a href="?page=<?= ($currentPage ?? 1) + 1 ?>" class="btn btn-ghost btn-sm">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="add-user-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New User</h3>
            <button class="modal-close" onclick="Modal.close('add-user-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="add-user-form">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required minlength="8">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('add-user-modal')">Cancel</button>
            <button class="btn btn-primary" onclick="createUser()">Create User</button>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal-overlay" id="view-user-modal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">User Details</h3>
            <button class="modal-close" onclick="Modal.close('view-user-modal')">&times;</button>
        </div>
        <div class="modal-body" id="view-user-content">
            <!-- Content loaded via JS -->
        </div>
    </div>
</div>

<script>
async function createUser() {
    const form = document.getElementById('add-user-form');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    try {
        const response = await API.post('/admin/users', data);
        if (response.success) {
            Toast.success('User created successfully');
            Modal.close('add-user-modal');
            location.reload();
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to create user');
    }
}

async function viewUser(id) {
    try {
        const response = await API.get('/admin/users/' + id);
        const user = response.data;

        document.getElementById('view-user-content').innerHTML = `
            <div class="d-flex items-center gap-4 mb-6">
                <div class="avatar avatar-lg" style="background: var(--gradient);">
                    ${user.first_name?.charAt(0).toUpperCase() || 'U'}
                </div>
                <div>
                    <h4 class="font-bold text-lg">${user.first_name || ''} ${user.last_name || ''}</h4>
                    <p class="text-secondary">${user.email || ''}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><strong>Role:</strong> ${user.role || 'user'}</div>
                <div><strong>Subscription:</strong> ${user.subscription_plan || 'Free'}</div>
                <div><strong>XP:</strong> ${user.xp || 0}</div>
                <div><strong>Streak:</strong> ${user.streak || 0} days</div>
                <div><strong>Courses Enrolled:</strong> ${user.courses_enrolled || 0}</div>
                <div><strong>Courses Completed:</strong> ${user.courses_completed || 0}</div>
                <div><strong>Joined:</strong> ${user.created_at || ''}</div>
                <div><strong>Last Active:</strong> ${user.last_active || 'Never'}</div>
            </div>
        `;

        Modal.open('view-user-modal');
    } catch (error) {
        Toast.error('Failed to load user details');
    }
}

function editUser(id) {
    window.location.href = '/admin/users/' + id + '/edit';
}

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        deleteUser(id);
    }
}

async function deleteUser(id) {
    try {
        const response = await API.delete('/admin/users/' + id);
        if (response.success) {
            Toast.success('User deleted successfully');
            location.reload();
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to delete user');
    }
}
</script>

<style>
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

.card-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--gray-100);
}
</style>
