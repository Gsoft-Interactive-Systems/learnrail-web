<!-- Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary">Analytics and reports overview</p>
    </div>
    <div class="d-flex gap-3">
        <select id="report-period" class="form-select" style="width: auto;" onchange="updateReports(this.value)">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="90">Last 90 days</option>
            <option value="365">Last year</option>
        </select>
        <button class="btn btn-outline" onclick="exportReport()">
            <i class="iconoir-download"></i>
            Export
        </button>
    </div>
</div>

<!-- Key Metrics -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-between items-start mb-2">
                <div class="text-sm text-secondary">New Users</div>
                <span class="badge <?= ($metrics['users_change'] ?? 0) >= 0 ? 'badge-success' : 'badge-danger' ?>">
                    <?= ($metrics['users_change'] ?? 0) >= 0 ? '+' : '' ?><?= $metrics['users_change'] ?? 0 ?>%
                </span>
            </div>
            <div class="text-2xl font-bold"><?= number_format($metrics['new_users'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-between items-start mb-2">
                <div class="text-sm text-secondary">Revenue</div>
                <span class="badge <?= ($metrics['revenue_change'] ?? 0) >= 0 ? 'badge-success' : 'badge-danger' ?>">
                    <?= ($metrics['revenue_change'] ?? 0) >= 0 ? '+' : '' ?><?= $metrics['revenue_change'] ?? 0 ?>%
                </span>
            </div>
            <div class="text-2xl font-bold">₦<?= number_format($metrics['revenue'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-between items-start mb-2">
                <div class="text-sm text-secondary">Course Completions</div>
                <span class="badge <?= ($metrics['completions_change'] ?? 0) >= 0 ? 'badge-success' : 'badge-danger' ?>">
                    <?= ($metrics['completions_change'] ?? 0) >= 0 ? '+' : '' ?><?= $metrics['completions_change'] ?? 0 ?>%
                </span>
            </div>
            <div class="text-2xl font-bold"><?= number_format($metrics['completions'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-between items-start mb-2">
                <div class="text-sm text-secondary">Active Users</div>
                <span class="badge <?= ($metrics['active_change'] ?? 0) >= 0 ? 'badge-success' : 'badge-danger' ?>">
                    <?= ($metrics['active_change'] ?? 0) >= 0 ? '+' : '' ?><?= $metrics['active_change'] ?? 0 ?>%
                </span>
            </div>
            <div class="text-2xl font-bold"><?= number_format($metrics['active_users'] ?? 0) ?></div>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <!-- Revenue Chart -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">Revenue Overview</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>

        <!-- User Growth Chart -->
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title">User Growth</h3>
            </div>
            <div class="card-body">
                <canvas id="userGrowthChart" height="250"></canvas>
            </div>
        </div>

        <!-- Top Courses -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Performing Courses</h3>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Enrollments</th>
                            <th>Completions</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($topCourses ?? []) as $course): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?= e($course['title'] ?? '') ?></div>
                                </td>
                                <td><?= number_format($course['enrollments'] ?? 0) ?></td>
                                <td>
                                    <?= number_format($course['completions'] ?? 0) ?>
                                    <span class="text-secondary text-sm">
                                        (<?= $course['completion_rate'] ?? 0 ?>%)
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex items-center gap-1">
                                        <i class="iconoir-star-solid text-warning"></i>
                                        <?= number_format($course['rating'] ?? 0, 1) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <!-- User Distribution -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">User Distribution</h3>
            </div>
            <div class="card-body">
                <canvas id="userDistChart" height="200"></canvas>
            </div>
        </div>

        <!-- Engagement Stats -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Engagement</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-between items-center mb-4">
                    <span class="text-secondary">Avg. Session Duration</span>
                    <span class="font-semibold"><?= $engagement['avg_session'] ?? '12m 30s' ?></span>
                </div>
                <div class="d-flex justify-between items-center mb-4">
                    <span class="text-secondary">Lessons/User/Day</span>
                    <span class="font-semibold"><?= $engagement['lessons_per_day'] ?? '2.3' ?></span>
                </div>
                <div class="d-flex justify-between items-center mb-4">
                    <span class="text-secondary">AI Tutor Queries</span>
                    <span class="font-semibold"><?= number_format($engagement['ai_queries'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-between items-center">
                    <span class="text-secondary">Goals Created</span>
                    <span class="font-semibold"><?= number_format($engagement['goals_created'] ?? 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
            </div>
            <div class="card-body p-0">
                <?php foreach (($recentActivity ?? []) as $activity): ?>
                    <div class="d-flex items-center gap-3 p-4" style="border-bottom: 1px solid var(--gray-100);">
                        <div class="avatar avatar-sm bg-<?= $activity['color'] ?? 'primary' ?>-light">
                            <i class="iconoir-<?= $activity['icon'] ?? 'bell' ?> text-<?= $activity['color'] ?? 'primary' ?>"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm"><?= e($activity['message'] ?? '') ?></div>
                            <div class="text-xs text-secondary"><?= time_ago($activity['created_at'] ?? '') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
        labels: <?= json_encode($revenueLabels ?? array_map(fn($i) => "Day $i", range(1, 30))) ?>,
        datasets: [{
            label: 'Revenue (₦)',
            data: <?= json_encode($revenueData ?? array_fill(0, 30, 0)) ?>,
            borderColor: '#6366F1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// User Growth Chart
const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
new Chart(userGrowthCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($userLabels ?? ['Week 1', 'Week 2', 'Week 3', 'Week 4']) ?>,
        datasets: [{
            label: 'New Users',
            data: <?= json_encode($userData ?? [0, 0, 0, 0]) ?>,
            backgroundColor: '#10B981'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// User Distribution Chart
const userDistCtx = document.getElementById('userDistChart').getContext('2d');
new Chart(userDistCtx, {
    type: 'doughnut',
    data: {
        labels: ['Free', 'Basic', 'Premium'],
        datasets: [{
            data: <?= json_encode([
                $distribution['free'] ?? 70,
                $distribution['basic'] ?? 20,
                $distribution['premium'] ?? 10
            ]) ?>,
            backgroundColor: ['#6B7280', '#6366F1', '#F59E0B']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

function updateReports(days) {
    window.location.href = '/admin/reports?period=' + days;
}

function exportReport() {
    const period = document.getElementById('report-period').value;
    window.location.href = '/admin/reports/export?period=' + period;
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

.bg-primary-light { background: rgba(99, 102, 241, 0.1); }
.bg-success-light { background: rgba(16, 185, 129, 0.1); }
.bg-warning-light { background: rgba(245, 158, 11, 0.1); }
</style>
