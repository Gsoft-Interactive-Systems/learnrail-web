<!-- Stats Overview -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <div class="stat-card-icon bg-primary-light">
            <i class="iconoir-group text-primary"></i>
        </div>
        <div class="stat-card-content">
            <div class="stat-card-value"><?= number_format($stats['total_users'] ?? 0) ?></div>
            <div class="stat-card-label">Total Users</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon bg-success-light">
            <i class="iconoir-book text-success"></i>
        </div>
        <div class="stat-card-content">
            <div class="stat-card-value"><?= number_format($stats['total_courses'] ?? 0) ?></div>
            <div class="stat-card-label">Courses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon bg-warning-light">
            <i class="iconoir-dollar text-warning"></i>
        </div>
        <div class="stat-card-content">
            <div class="stat-card-value">₦<?= number_format($stats['revenue_this_month'] ?? 0) ?></div>
            <div class="stat-card-label">Revenue (This Month)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon bg-info-light">
            <i class="iconoir-star text-info"></i>
        </div>
        <div class="stat-card-content">
            <div class="stat-card-value"><?= number_format($stats['active_subscribers'] ?? 0) ?></div>
            <div class="stat-card-label">Active Subscribers</div>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Main Content -->
    <div>
        <!-- Revenue Chart -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Revenue Overview</h3>
                <select class="form-select" style="width: auto;" onchange="updateRevenueChart(this.value)">
                    <option value="7">Last 7 days</option>
                    <option value="30" selected>Last 30 days</option>
                    <option value="90">Last 90 days</option>
                </select>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Recent Users</h3>
                <a href="/admin/users" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($recentUsers ?? []) as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex items-center gap-3">
                                        <div class="avatar avatar-sm">
                                            <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <span><?= e($user['first_name'] ?? '') ?> <?= e($user['last_name'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td><?= e($user['email'] ?? '') ?></td>
                                <td><?= time_ago($user['created_at'] ?? '') ?></td>
                                <td>
                                    <span class="badge <?= ($user['is_active'] ?? true) ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= ($user['is_active'] ?? true) ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Payments</h3>
                <a href="/admin/payments" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($recentPayments ?? []) as $payment): ?>
                            <tr>
                                <td><?= e($payment['user_name'] ?? '') ?></td>
                                <td><?= e($payment['plan_name'] ?? '') ?></td>
                                <td>₦<?= number_format($payment['amount'] ?? 0) ?></td>
                                <td><?= format_date($payment['created_at'] ?? '') ?></td>
                                <td>
                                    <span class="badge <?= ($payment['status'] ?? '') === 'success' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= ucfirst($payment['status'] ?? 'pending') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- User Growth -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">User Growth</h3>
            </div>
            <div class="card-body">
                <canvas id="userGrowthChart" height="200"></canvas>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <a href="/admin/courses/create" class="btn btn-primary btn-block mb-3">
                    <i class="iconoir-plus"></i>
                    Add New Course
                </a>
                <a href="/admin/users" class="btn btn-outline btn-block mb-3">
                    <i class="iconoir-group"></i>
                    Manage Users
                </a>
                <a href="/admin/reports" class="btn btn-outline btn-block">
                    <i class="iconoir-reports"></i>
                    View Reports
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Status</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-between items-center mb-3">
                    <span>API Server</span>
                    <span class="badge badge-success">Online</span>
                </div>
                <div class="d-flex justify-between items-center mb-3">
                    <span>Database</span>
                    <span class="badge badge-success">Connected</span>
                </div>
                <div class="d-flex justify-between items-center">
                    <span>Storage</span>
                    <span class="badge badge-success">Normal</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($revenueLabels ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) ?>,
        datasets: [{
            label: 'Revenue (₦)',
            data: <?= json_encode($revenueData ?? [0, 0, 0, 0, 0, 0, 0]) ?>,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// User Growth Chart
const userCtx = document.getElementById('userGrowthChart').getContext('2d');
new Chart(userCtx, {
    type: 'doughnut',
    data: {
        labels: ['Free Users', 'Subscribers', 'Premium'],
        datasets: [{
            data: <?= json_encode([
                $stats['free_users'] ?? 100,
                $stats['basic_subscribers'] ?? 30,
                $stats['premium_subscribers'] ?? 10
            ]) ?>,
            backgroundColor: ['#6B7280', '#6366F1', '#10B981']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

function updateRevenueChart(days) {
    // Fetch new data and update chart
    console.log('Updating chart for', days, 'days');
}
</script>

<style>
.stat-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow);
}

.stat-card-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-card-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.stat-card-label {
    color: var(--gray-500);
    font-size: 14px;
}

.bg-primary-light { background: rgba(99, 102, 241, 0.1); }
.bg-success-light { background: rgba(16, 185, 129, 0.1); }
.bg-warning-light { background: rgba(245, 158, 11, 0.1); }
.bg-info-light { background: rgba(59, 130, 246, 0.1); }

.text-info { color: #3B82F6; }

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
}

.table tbody tr:hover {
    background: var(--gray-50);
}
</style>
