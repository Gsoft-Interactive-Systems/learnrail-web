<div class="grid" style="grid-template-columns: 250px 1fr; gap: 24px;">
    <!-- Settings Nav -->
    <div>
        <div class="card" style="position: sticky; top: 88px;">
            <div class="card-body p-0">
                <nav class="settings-nav">
                    <a href="#general" class="settings-nav-item active" data-section="general">
                        <i class="iconoir-settings"></i>
                        General
                    </a>
                    <a href="#payment" class="settings-nav-item" data-section="payment">
                        <i class="iconoir-credit-card"></i>
                        Payment
                    </a>
                    <a href="#email" class="settings-nav-item" data-section="email">
                        <i class="iconoir-mail"></i>
                        Email
                    </a>
                    <a href="#notifications" class="settings-nav-item" data-section="notifications">
                        <i class="iconoir-bell"></i>
                        Notifications
                    </a>
                    <a href="#api" class="settings-nav-item" data-section="api">
                        <i class="iconoir-code"></i>
                        API Keys
                    </a>
                    <a href="#maintenance" class="settings-nav-item" data-section="maintenance">
                        <i class="iconoir-tools"></i>
                        Maintenance
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Settings Content -->
    <div>
        <!-- General Settings -->
        <div class="card mb-6" id="general">
            <div class="card-header">
                <h3 class="card-title">General Settings</h3>
            </div>
            <div class="card-body">
                <form action="/admin/settings/general" method="POST" data-loading>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-input" value="<?= e($settings['site_name'] ?? 'Learnrail') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tagline</label>
                        <input type="text" name="tagline" class="form-input" value="<?= e($settings['tagline'] ?? 'Learn at your own pace') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" class="form-input" value="<?= e($settings['contact_email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Support Phone</label>
                        <input type="text" name="support_phone" class="form-input" value="<?= e($settings['support_phone'] ?? '') ?>">
                    </div>

                    <div class="form-group mb-0">
                        <label class="d-flex items-center gap-3">
                            <input type="checkbox" name="new_registrations" value="1"
                                   <?= ($settings['new_registrations'] ?? true) ? 'checked' : '' ?>
                                   style="width: 18px; height: 18px;">
                            <span>Allow new user registrations</span>
                        </label>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payment Settings -->
        <div class="card mb-6" id="payment">
            <div class="card-header">
                <h3 class="card-title">Payment Settings</h3>
            </div>
            <div class="card-body">
                <form action="/admin/settings/payment" method="POST" data-loading>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-select">
                            <option value="NGN" <?= ($settings['currency'] ?? 'NGN') === 'NGN' ? 'selected' : '' ?>>NGN - Nigerian Naira</option>
                            <option value="USD" <?= ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                        </select>
                    </div>

                    <h4 class="font-semibold mb-4 mt-6">Payment Methods</h4>
                    <p class="text-sm text-secondary mb-4">Enable/disable payment methods shown to users during subscription checkout.</p>

                    <!-- Payment Methods from Database -->
                    <?php foreach ($paymentMethods ?? [] as $method): ?>
                    <div class="payment-method-card mb-4" data-method-id="<?= e($method['id']) ?>">
                        <div class="d-flex justify-between items-center">
                            <div class="d-flex items-center gap-3">
                                <?php
                                $logoStyle = match($method['slug'] ?? '') {
                                    'paystack' => 'background: #00C3F7;',
                                    'bank_transfer' => 'background: var(--primary);',
                                    'xpress' => 'background: #F5A623;',
                                    default => 'background: var(--secondary);'
                                };
                                $icon = match($method['slug'] ?? '') {
                                    'paystack' => 'iconoir-credit-card',
                                    'bank_transfer' => 'iconoir-bank',
                                    'xpress' => 'iconoir-wallet',
                                    default => 'iconoir-credit-card'
                                };
                                ?>
                                <div class="payment-method-logo" style="<?= $logoStyle ?>">
                                    <i class="<?= $icon ?>" style="color: white;"></i>
                                </div>
                                <div>
                                    <div class="font-semibold"><?= e($method['name'] ?? '') ?></div>
                                    <div class="text-sm text-secondary"><?= e($method['description'] ?? '') ?></div>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox"
                                       onchange="togglePaymentMethod(<?= e($method['id']) ?>, this.checked)"
                                       <?= ($method['is_active'] ?? 0) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <?php if (($method['slug'] ?? '') === 'bank_transfer'): ?>
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div class="form-group mb-0">
                                <label class="form-label text-sm">Bank Name</label>
                                <input type="text" name="bank_name" class="form-input" value="<?= e($settings['bank_name'] ?? 'Access Bank') ?>">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label text-sm">Account Number</label>
                                <input type="text" name="bank_account_number" class="form-input" value="<?= e($settings['bank_account_number'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group mt-3 mb-0">
                            <label class="form-label text-sm">Account Name</label>
                            <input type="text" name="bank_account_name" class="form-input" value="<?= e($settings['bank_account_name'] ?? 'Learnrail Limited') ?>">
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($paymentMethods)): ?>
                    <p class="text-secondary">No payment methods configured. Please add them to the database.</p>
                    <?php endif; ?>

                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">Save Payment Settings</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="card mb-6" id="email">
            <div class="card-header">
                <h3 class="card-title">Email Settings</h3>
            </div>
            <div class="card-body">
                <form action="/admin/settings/email" method="POST" data-loading>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-input" value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.example.com">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-input" value="<?= e($settings['smtp_port'] ?? '587') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Encryption</label>
                            <select name="smtp_encryption" class="form-select">
                                <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-input" value="<?= e($settings['smtp_username'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-input" value="<?= e($settings['smtp_password'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">From Email</label>
                        <input type="email" name="from_email" class="form-input" value="<?= e($settings['from_email'] ?? '') ?>" placeholder="noreply@learnrail.org">
                    </div>

                    <div class="form-group">
                        <label class="form-label">From Name</label>
                        <input type="text" name="from_name" class="form-input" value="<?= e($settings['from_name'] ?? 'Learnrail') ?>">
                    </div>

                    <div class="d-flex gap-3 mt-6">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-outline" onclick="testEmail()">Send Test Email</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="card mb-6" id="notifications">
            <div class="card-header">
                <h3 class="card-title">Notification Settings</h3>
            </div>
            <div class="card-body">
                <form action="/admin/settings/notifications" method="POST" data-loading>
                    <?= csrf_field() ?>

                    <h4 class="font-semibold mb-4">Email Notifications</h4>

                    <div class="form-group">
                        <label class="d-flex items-center gap-3">
                            <input type="checkbox" name="notify_new_user" value="1"
                                   <?= ($settings['notify_new_user'] ?? true) ? 'checked' : '' ?>
                                   style="width: 18px; height: 18px;">
                            <span>New user registration</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="d-flex items-center gap-3">
                            <input type="checkbox" name="notify_new_payment" value="1"
                                   <?= ($settings['notify_new_payment'] ?? true) ? 'checked' : '' ?>
                                   style="width: 18px; height: 18px;">
                            <span>New payment received</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="d-flex items-center gap-3">
                            <input type="checkbox" name="notify_subscription_expired" value="1"
                                   <?= ($settings['notify_subscription_expired'] ?? true) ? 'checked' : '' ?>
                                   style="width: 18px; height: 18px;">
                            <span>Subscription expiring soon</span>
                        </label>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- API Keys -->
        <div class="card mb-6" id="api">
            <div class="card-header">
                <h3 class="card-title">API Keys</h3>
            </div>
            <div class="card-body">
                <form action="/admin/settings/api" method="POST" data-loading>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label">OpenAI API Key</label>
                        <input type="password" name="openai_api_key" class="form-input" value="<?= e($settings['openai_api_key'] ?? '') ?>" placeholder="sk-...">
                        <p class="text-sm text-secondary mt-1">Used for AI Tutor feature</p>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Maintenance -->
        <div class="card" id="maintenance">
            <div class="card-header">
                <h3 class="card-title">Maintenance</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-between items-center mb-4 p-4" style="background: var(--gray-50); border-radius: var(--radius);">
                    <div>
                        <div class="font-semibold">Maintenance Mode</div>
                        <div class="text-sm text-secondary">Take the site offline for maintenance</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" <?= ($settings['maintenance_mode'] ?? false) ? 'checked' : '' ?> onchange="toggleMaintenance(this.checked)">
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="d-flex justify-between items-center mb-4 p-4" style="background: var(--gray-50); border-radius: var(--radius);">
                    <div>
                        <div class="font-semibold">Clear Cache</div>
                        <div class="text-sm text-secondary">Clear all cached data</div>
                    </div>
                    <button class="btn btn-outline btn-sm" onclick="clearCache()">Clear</button>
                </div>

                <div class="d-flex justify-between items-center p-4" style="background: var(--gray-50); border-radius: var(--radius);">
                    <div>
                        <div class="font-semibold">Database Backup</div>
                        <div class="text-sm text-secondary">Download a backup of the database</div>
                    </div>
                    <button class="btn btn-outline btn-sm" onclick="downloadBackup()">Download</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Smooth scroll to sections
document.querySelectorAll('.settings-nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        const section = this.getAttribute('data-section');
        document.getElementById(section).scrollIntoView({ behavior: 'smooth' });

        document.querySelectorAll('.settings-nav-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
    });
});

async function testEmail() {
    try {
        const response = await API.post('/admin/settings/test-email');
        if (response.success) {
            Toast.success('Test email sent successfully');
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to send test email');
    }
}

async function toggleMaintenance(enabled) {
    try {
        const response = await API.post('/admin/settings/maintenance', { enabled });
        Toast.success(enabled ? 'Maintenance mode enabled' : 'Maintenance mode disabled');
    } catch (error) {
        Toast.error('Failed to toggle maintenance mode');
    }
}

async function clearCache() {
    if (!confirm('Are you sure you want to clear all cached data?')) return;

    try {
        const response = await API.post('/admin/settings/clear-cache');
        if (response.success) {
            Toast.success('Cache cleared successfully');
        }
    } catch (error) {
        Toast.error('Failed to clear cache');
    }
}

function downloadBackup() {
    window.location.href = '/admin/settings/backup';
}

async function togglePaymentMethod(id, enabled) {
    try {
        const response = await API.post('/admin/payment-methods/' + id, {
            is_active: enabled
        });
        if (response.success) {
            Toast.success(enabled ? 'Payment method enabled' : 'Payment method disabled');
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to update payment method');
        // Revert the toggle
        const checkbox = document.querySelector(`[data-method-id="${id}"] input[type="checkbox"]`);
        if (checkbox) {
            checkbox.checked = !enabled;
        }
    }
}
</script>

<style>
.settings-nav {
    display: flex;
    flex-direction: column;
}

.settings-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: var(--gray-600);
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all 0.2s;
}

.settings-nav-item:hover {
    background: var(--gray-50);
    color: var(--gray-900);
}

.settings-nav-item.active {
    background: rgba(99, 102, 241, 0.05);
    color: var(--primary);
    border-left-color: var(--primary);
}

.switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--gray-300);
    transition: 0.3s;
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: var(--primary);
}

input:checked + .slider:before {
    transform: translateX(22px);
}
</style>
