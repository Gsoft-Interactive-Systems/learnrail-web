<!-- Header -->
<div class="d-flex justify-between items-center mb-6">
    <div>
        <p class="text-secondary">View and manage payment transactions</p>
    </div>
    <div class="d-flex gap-2">
        <!-- Debug test button -->
        <button class="btn btn-warning btn-sm" onclick="alert('JavaScript onclick works!')">Test JS</button>
        <button class="btn btn-outline" onclick="exportPayments()">
            <i class="iconoir-download"></i>
            Export CSV
        </button>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Total Revenue</div>
            <div class="text-2xl font-bold"><?= format_currency($stats['total_revenue'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">This Month</div>
            <div class="text-2xl font-bold text-success"><?= format_currency($stats['this_month'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Pending</div>
            <div class="text-2xl font-bold text-warning"><?= number_format($stats['pending'] ?? 0) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="text-sm text-secondary mb-1">Completed</div>
            <div class="text-2xl font-bold text-primary"><?= number_format($stats['completed'] ?? 0) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form class="d-flex gap-4 items-end flex-wrap" method="GET">
            <div class="form-group mb-0" style="flex: 1; min-width: 200px;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-input" placeholder="User email or reference..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="failed" <?= ($_GET['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Method</label>
                <select name="method" class="form-select">
                    <option value="">All Methods</option>
                    <option value="bank_transfer" <?= ($_GET['method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="paystack" <?= ($_GET['method'] ?? '') === 'paystack' ? 'selected' : '' ?>>Paystack</option>
                    <option value="xpress" <?= ($_GET['method'] ?? '') === 'xpress' ? 'selected' : '' ?>>Xpress</option>
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
            <a href="/admin/payments" class="btn btn-ghost">Clear</a>
        </form>
    </div>
</div>

<!-- Pending Bank Transfers Alert -->
<?php
$pendingBankTransfers = array_filter($payments ?? [], fn($p) =>
    ($p['status'] ?? '') === 'pending' && ($p['payment_method'] ?? '') === 'bank_transfer'
);
if (count($pendingBankTransfers) > 0):
?>
<div class="alert alert-warning mb-6" style="background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; padding: 16px; border-radius: 8px;">
    <div class="d-flex items-center gap-3">
        <i class="iconoir-warning-triangle text-xl"></i>
        <div>
            <strong><?= count($pendingBankTransfers) ?> pending bank transfer(s)</strong> require verification.
            <a href="?status=pending&method=bank_transfer" style="color: inherit; text-decoration: underline;">View all</a>
        </div>
    </div>
</div>
<?php endif; ?>

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
                        <tr data-payment-id="<?= e($payment['id'] ?? '') ?>">
                            <td>
                                <code class="text-sm"><?= e($payment['reference'] ?? '') ?></code>
                                <?php if (!empty($payment['receipt_url'])): ?>
                                    <div class="mt-1">
                                        <a href="<?= e($payment['receipt_url']) ?>" target="_blank" class="text-xs text-primary">
                                            <i class="iconoir-page"></i> Receipt
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <div class="font-medium"><?= e(($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? '')) ?></div>
                                    <div class="text-sm text-secondary"><?= e($payment['email'] ?? $payment['user_email'] ?? '') ?></div>
                                </div>
                            </td>
                            <td><?= e($payment['plan_name'] ?? $payment['plan'] ?? 'N/A') ?></td>
                            <td class="font-semibold"><?= format_currency($payment['amount'] ?? 0) ?></td>
                            <td>
                                <?php
                                $methodBadge = match($payment['payment_method'] ?? '') {
                                    'bank_transfer' => 'badge-secondary',
                                    'paystack' => 'badge-info',
                                    'xpress' => 'badge-purple',
                                    default => 'badge-secondary'
                                };
                                $methodLabel = match($payment['payment_method'] ?? '') {
                                    'bank_transfer' => 'Bank Transfer',
                                    'paystack' => 'Paystack',
                                    'xpress' => 'Xpress',
                                    default => ucfirst($payment['payment_method'] ?? 'Unknown')
                                };
                                ?>
                                <span class="badge <?= $methodBadge ?>"><?= $methodLabel ?></span>
                            </td>
                            <td>
                                <div><?= format_date($payment['created_at'] ?? '') ?></div>
                                <div class="text-sm text-secondary"><?= date('H:i', strtotime($payment['created_at'] ?? 'now')) ?></div>
                            </td>
                            <td>
                                <?php
                                $statusBadge = match($payment['status'] ?? 'pending') {
                                    'completed' => 'badge-success',
                                    'success' => 'badge-success',
                                    'failed' => 'badge-danger',
                                    default => 'badge-warning'
                                };
                                ?>
                                <span class="badge <?= $statusBadge ?>"><?= ucfirst($payment['status'] ?? 'pending') ?></span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline btn-sm" onclick="viewPayment(<?= $payment['id'] ?>)" title="View Details">
                                        <i class="iconoir-eye"></i> View
                                    </button>
                                    <?php if (($payment['status'] ?? '') === 'pending'): ?>
                                        <button class="btn btn-success btn-sm" onclick="approvePayment(<?= $payment['id'] ?>)" title="Approve Payment">
                                            <i class="iconoir-check"></i> Approve
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="rejectPayment(<?= $payment['id'] ?>)" title="Reject Payment">
                                            <i class="iconoir-xmark"></i> Reject
                                        </button>
                                    <?php endif; ?>
                                </div>
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
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Payment Details</h3>
            <button class="modal-close" onclick="Modal.close('payment-modal')">&times;</button>
        </div>
        <div class="modal-body" id="payment-details">
            <!-- Loaded via JS -->
        </div>
        <div class="modal-footer" id="payment-actions" style="display: none;">
            <!-- Actions for pending payments -->
        </div>
    </div>
</div>

<!-- Reject Reason Modal -->
<div class="modal-overlay" id="reject-modal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Reject Payment</h3>
            <button class="modal-close" onclick="Modal.close('reject-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <p class="text-secondary mb-4">Please provide a reason for rejecting this payment. The user will be notified.</p>
            <div class="form-group mb-0">
                <label class="form-label">Reason</label>
                <textarea id="reject-reason" class="form-input" rows="3" placeholder="e.g., Payment not received, Invalid receipt..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="Modal.close('reject-modal')">Cancel</button>
            <button class="btn btn-danger" id="confirm-reject-btn">Reject Payment</button>
        </div>
    </div>
</div>

<script>
// Debug: Script is loading
console.log('=== PAYMENTS ADMIN SCRIPT LOADING ===');
console.log('API defined?', typeof API !== 'undefined');
console.log('Modal defined?', typeof Modal !== 'undefined');
console.log('Toast defined?', typeof Toast !== 'undefined');

// Wait for dependencies to be available
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DOMContentLoaded fired ===');
    console.log('API now defined?', typeof API !== 'undefined');
    console.log('Modal now defined?', typeof Modal !== 'undefined');

    // Ensure API is available (might be defined after this script)
    if (typeof API === 'undefined') {
        console.error('API not loaded - buttons will not work');
        return;
    }
});

let currentPaymentId = null;

async function viewPayment(id) {
    console.log('viewPayment called with id:', id);
    if (typeof API === 'undefined') {
        alert('Error: Page not fully loaded. Please refresh.');
        return;
    }
    currentPaymentId = id;
    try {
        const response = await API.get('/admin/payments/' + id);
        const payment = response.data;

        let receiptHtml = '';
        if (payment.receipt_url) {
            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(payment.receipt_url);
            if (isImage) {
                receiptHtml = `
                    <div class="mt-4">
                        <strong>Payment Receipt:</strong>
                        <a href="${payment.receipt_url}" target="_blank" class="d-block mt-2">
                            <img src="${payment.receipt_url}" alt="Receipt" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        </a>
                    </div>
                `;
            } else {
                receiptHtml = `
                    <div class="mt-4">
                        <strong>Payment Receipt:</strong>
                        <a href="${payment.receipt_url}" target="_blank" class="btn btn-outline btn-sm mt-2">
                            <i class="iconoir-download"></i> Download Receipt
                        </a>
                    </div>
                `;
            }
        }

        document.getElementById('payment-details').innerHTML = `
            <div class="mb-4 p-3" style="background: var(--gray-50); border-radius: 8px;">
                <div class="text-sm text-secondary mb-1">Reference</div>
                <code style="font-size: 14px;">${payment.reference || ''}</code>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-secondary mb-1">User</div>
                    <div class="font-medium">${payment.first_name || ''} ${payment.last_name || ''}</div>
                </div>
                <div>
                    <div class="text-sm text-secondary mb-1">Email</div>
                    <div>${payment.email || ''}</div>
                </div>
                <div>
                    <div class="text-sm text-secondary mb-1">Plan</div>
                    <div class="font-medium">${payment.plan_name || 'N/A'}</div>
                </div>
                <div>
                    <div class="text-sm text-secondary mb-1">Amount</div>
                    <div class="font-bold text-lg">${formatCurrency(payment.amount || 0)}</div>
                </div>
                <div>
                    <div class="text-sm text-secondary mb-1">Method</div>
                    <div>${formatPaymentMethod(payment.payment_method)}</div>
                </div>
                <div>
                    <div class="text-sm text-secondary mb-1">Status</div>
                    <div><span class="badge ${getStatusBadge(payment.status)}">${(payment.status || 'pending').toUpperCase()}</span></div>
                </div>
                <div>
                    <div class="text-sm text-secondary mb-1">Created</div>
                    <div>${payment.created_at || ''}</div>
                </div>
                <div>
                    <div class="text-sm text-secondary mb-1">Paid At</div>
                    <div>${payment.paid_at || 'Not yet'}</div>
                </div>
            </div>
            ${receiptHtml}
        `;

        // Show actions for pending payments
        const actionsDiv = document.getElementById('payment-actions');
        if (payment.status === 'pending') {
            actionsDiv.style.display = 'flex';
            actionsDiv.innerHTML = `
                <button class="btn btn-ghost" onclick="Modal.close('payment-modal')">Close</button>
                <div class="d-flex gap-2">
                    <button class="btn btn-danger" onclick="Modal.close('payment-modal'); rejectPayment(${id})">
                        <i class="iconoir-xmark"></i> Reject
                    </button>
                    <button class="btn btn-success" onclick="approvePayment(${id})">
                        <i class="iconoir-check"></i> Approve
                    </button>
                </div>
            `;
        } else {
            actionsDiv.style.display = 'none';
        }

        Modal.open('payment-modal');
    } catch (error) {
        Toast.error('Failed to load payment details');
    }
}

async function approvePayment(id) {
    console.log('approvePayment called with id:', id);
    if (typeof API === 'undefined') {
        alert('Error: Page not fully loaded. Please refresh.');
        return;
    }
    if (!confirm('Are you sure you want to approve this payment? This will activate the user\'s subscription.')) {
        return;
    }

    try {
        const response = await API.post('/admin/payments/' + id + '/approve');
        if (response.success) {
            Toast.success('Payment approved! User subscription activated.');
            // Update the row
            const row = document.querySelector(`tr[data-payment-id="${id}"]`);
            if (row) {
                row.querySelector('.badge-warning')?.classList.replace('badge-warning', 'badge-success');
                row.querySelector('.badge-warning')?.textContent = 'COMPLETED';
                row.querySelector('.btn-success')?.remove();
                row.querySelector('.btn-danger')?.remove();
            }
            Modal.close('payment-modal');
            // Optionally reload
            setTimeout(() => window.location.reload(), 1000);
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to approve payment');
    }
}

function rejectPayment(id) {
    console.log('rejectPayment called with id:', id);
    if (typeof Modal === 'undefined') {
        alert('Error: Page not fully loaded. Please refresh.');
        return;
    }
    currentPaymentId = id;
    document.getElementById('reject-reason').value = '';
    Modal.open('reject-modal');
}

// Wait for DOM before adding event listener
document.addEventListener('DOMContentLoaded', function() {
    const rejectBtn = document.getElementById('confirm-reject-btn');
    if (rejectBtn) {
        rejectBtn.addEventListener('click', handleRejectConfirm);
    }
});

async function handleRejectConfirm() {
    const reason = document.getElementById('reject-reason').value.trim();
    if (!reason) {
        Toast.error('Please provide a rejection reason');
        return;
    }

    const btn = document.getElementById('confirm-reject-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner" style="width:16px;height:16px;border-width:2px;"></span> Rejecting...';
    }

    try {
        const response = await API.post('/admin/payments/' + currentPaymentId + '/reject', { reason });
        if (response.success) {
            Toast.success('Payment rejected. User has been notified.');
            Modal.close('reject-modal');
            setTimeout(() => window.location.reload(), 1000);
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to reject payment');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = 'Reject Payment';
        }
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
}

function formatPaymentMethod(method) {
    const methods = {
        'bank_transfer': 'Bank Transfer',
        'paystack': 'Paystack',
        'xpress': 'Xpress'
    };
    return methods[method] || method;
}

function getStatusBadge(status) {
    const badges = {
        'completed': 'badge-success',
        'success': 'badge-success',
        'pending': 'badge-warning',
        'failed': 'badge-danger'
    };
    return badges[status] || 'badge-secondary';
}

function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '/admin/payments?' + params.toString();
}

// Make functions globally accessible for onclick handlers
window.viewPayment = viewPayment;
window.approvePayment = approvePayment;
window.rejectPayment = rejectPayment;
window.exportPayments = exportPayments;

console.log('=== PAYMENTS ADMIN SCRIPT LOADED SUCCESSFULLY ===');
console.log('viewPayment is:', typeof viewPayment);
console.log('window.viewPayment is:', typeof window.viewPayment);
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

.modal-footer {
    display: flex;
    justify-content: space-between;
    padding: 16px 20px;
    border-top: 1px solid var(--gray-100);
}

code {
    background: var(--gray-100);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
}

.btn-success {
    background: #10B981 !important;
    color: white !important;
    border: 1px solid #10B981 !important;
}

.btn-success:hover {
    background: #059669 !important;
    border-color: #059669 !important;
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #DC2626;
}

.btn-outline-danger {
    background: transparent;
    color: var(--danger);
    border: 1px solid var(--danger);
}

.btn-outline-danger:hover {
    background: var(--danger);
    color: white;
}

.badge-info {
    background: #DBEAFE;
    color: #1E40AF;
}

.badge-purple {
    background: #EDE9FE;
    color: #6D28D9;
}

.loading-spinner {
    display: inline-block;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
