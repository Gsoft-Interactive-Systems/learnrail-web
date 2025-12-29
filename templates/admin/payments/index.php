<!-- Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary">View all payment transactions</p>
    </div>
    <button class="btn btn-outline" onclick="exportPayments()">
        <i class="iconoir-download"></i>
        Export CSV
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Total Revenue</div>
            <div class="text-2xl font-bold">₦<?= number_format($stats['total_revenue'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">This Month</div>
            <div class="text-2xl font-bold text-success">₦<?= number_format($stats['this_month'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Successful</div>
            <div class="text-2xl font-bold text-primary"><?= number_format($stats['successful'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Failed</div>
            <div class="text-2xl font-bold text-danger"><?= number_format($stats['failed'] ?? 0) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form class="d-flex gap-4 items-end" method="GET">
            <div class="form-group mb-0" style="flex: 1;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-input" placeholder="User email or reference..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="success" <?= ($_GET['status'] ?? '') === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="failed" <?= ($_GET['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-input" value="<?= e($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-input" value="<?= e($_GET['date_to'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="iconoir-search"></i>
                Filter
            </button>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body p-0">
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <code class="text-sm"><?= e($payment['reference'] ?? '') ?></code>
                            </td>
                            <td>
                                <div>
                                    <div class="font-medium"><?= e($payment['user_name'] ?? '') ?></div>
                                    <div class="text-sm text-secondary"><?= e($payment['user_email'] ?? '') ?></div>
                                </div>
                            </td>
                            <td><?= ucfirst($payment['plan'] ?? 'basic') ?></td>
                            <td class="font-semibold">₦<?= number_format($payment['amount'] ?? 0) ?></td>
                            <td>
                                <span class="badge badge-secondary"><?= ucfirst($payment['payment_method'] ?? 'paystack') ?></span>
                            </td>
                            <td>
                                <div><?= format_date($payment['created_at'] ?? '') ?></div>
                                <div class="text-sm text-secondary"><?= date('H:i', strtotime($payment['created_at'] ?? '')) ?></div>
                            </td>
                            <td>
                                <?php
                                $statusBadge = match($payment['status'] ?? 'pending') {
                                    'success' => 'badge-success',
                                    'failed' => 'badge-danger',
                                    default => 'badge-warning'
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= ucfirst($payment['status'] ?? 'pending') ?></span>
                            </td>
                            <td>
                                <button class="btn btn-ghost btn-sm" onclick="viewPayment('<?= e($payment['reference'] ?? '') ?>')" title="View Details">
                                    <i class="iconoir-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-secondary">
                            No payments found.
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
                Showing <?= (($currentPage ?? 1) - 1) * 20 + 1 ?> to <?= min(($currentPage ?? 1) * 20, $totalPayments ?? 0) ?> of <?= $totalPayments ?? 0 ?> payments
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

<!-- Payment Details Modal -->
<div class="modal-overlay" id="payment-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Payment Details</h3>
            <button class="modal-close" onclick="Modal.close('payment-modal')">&times;</button>
        </div>
        <div class="modal-body" id="payment-details">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<script>
async function viewPayment(reference) {
    try {
        const response = await API.get('/admin/payments/' + reference);
        const payment = response.data;

        document.getElementById('payment-details').innerHTML = `
            <div class="mb-4">
                <strong>Reference:</strong>
                <code>${payment.reference || ''}</code>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><strong>User:</strong> ${payment.user_name || ''}</div>
                <div><strong>Email:</strong> ${payment.user_email || ''}</div>
                <div><strong>Plan:</strong> ${payment.plan || ''}</div>
                <div><strong>Amount:</strong> ₦${(payment.amount || 0).toLocaleString()}</div>
                <div><strong>Method:</strong> ${payment.payment_method || ''}</div>
                <div><strong>Status:</strong> ${payment.status || ''}</div>
                <div><strong>Date:</strong> ${payment.created_at || ''}</div>
                <div><strong>Gateway Ref:</strong> ${payment.gateway_reference || 'N/A'}</div>
            </div>
            ${payment.metadata ? `<div class="mt-4"><strong>Metadata:</strong><pre style="background: var(--gray-100); padding: 12px; border-radius: var(--radius); font-size: 12px; overflow: auto;">${JSON.stringify(payment.metadata, null, 2)}</pre></div>` : ''}
        `;

        Modal.open('payment-modal');
    } catch (error) {
        Toast.error('Failed to load payment details');
    }
}

function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '/admin/payments?' + params.toString();
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

code {
    background: var(--gray-100);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
}
</style>
