<?php
$planName = $plan['name'] ?? 'Premium';
$planPrice = $plan['price'] ?? 0;
$planId = $plan['id'] ?? 0;
?>

<div class="grid" style="grid-template-columns: 1fr 400px; gap: 24px; max-width: 900px; margin: 0 auto;">
    <!-- Payment Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payment Details</h3>
        </div>
        <div class="card-body">
            <!-- Step 1: Payment Method Selection -->
            <div id="step-method">
                <form id="payment-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= e($planId) ?>">

                    <div class="form-group">
                        <label class="form-label">Select Payment Method</label>
                        <?php
                        $firstActive = true;
                        foreach ($paymentMethods ?? [] as $method):
                            $isActive = ($method['is_active'] ?? 0) == 1;
                            if (!$isActive) continue;
                        ?>
                            <label class="form-radio d-flex gap-3 p-4 border rounded mb-2 cursor-pointer payment-method-option" style="border-color: var(--gray-200);" data-method="<?= e($method['slug'] ?? $method['name']) ?>">
                                <input type="radio" name="payment_method" value="<?= e($method['slug'] ?? $method['name']) ?>" <?= $firstActive ? 'checked' : '' ?>>
                                <div class="flex-1">
                                    <div class="font-medium"><?= e($method['name'] ?? 'Payment Method') ?></div>
                                    <div class="text-sm text-secondary"><?= e($method['description'] ?? '') ?></div>
                                </div>
                            </label>
                        <?php
                        $firstActive = false;
                        endforeach;
                        ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="pay-btn">
                        Proceed to Pay <?= format_currency($planPrice) ?>
                    </button>

                    <p class="text-xs text-secondary text-center mt-4">
                        <i class="iconoir-lock"></i>
                        Your payment is secure
                    </p>
                </form>
            </div>

            <!-- Step 2: Bank Transfer Details -->
            <div id="step-bank-details" style="display: none;">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-soft mb-4">
                        <i class="iconoir-bank text-primary text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-lg">Bank Transfer</h3>
                    <p class="text-secondary">Transfer <?= format_currency($planPrice) ?> to the account below</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 mb-6" style="background: var(--gray-50);">
                    <div class="mb-4">
                        <div class="text-sm text-secondary mb-1">Bank Name</div>
                        <div class="font-semibold d-flex justify-between items-center">
                            <span id="bank-name">Access Bank</span>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="copyToClipboard('bank-name')">
                                <i class="iconoir-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="text-sm text-secondary mb-1">Account Number</div>
                        <div class="font-semibold d-flex justify-between items-center">
                            <span id="account-number" class="text-xl tracking-wider">1234567890</span>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="copyToClipboard('account-number')">
                                <i class="iconoir-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="text-sm text-secondary mb-1">Account Name</div>
                        <div class="font-semibold d-flex justify-between items-center">
                            <span id="account-name">Learnrail Limited</span>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="copyToClipboard('account-name')">
                                <i class="iconoir-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="pt-3 border-t" style="border-color: var(--gray-200);">
                        <div class="text-sm text-secondary mb-1">Amount</div>
                        <div class="font-bold text-xl text-primary"><?= format_currency($planPrice) ?></div>
                    </div>
                </div>

                <div class="alert alert-info mb-6" style="background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; padding: 12px 16px; border-radius: 8px;">
                    <div class="d-flex gap-3">
                        <i class="iconoir-info-circle text-lg"></i>
                        <div>
                            <strong>Reference:</strong> <code id="payment-reference" style="background: #DBEAFE; padding: 2px 6px; border-radius: 4px;">-</code>
                            <p class="text-sm mt-1 mb-0">Include this reference in your transfer narration for faster verification.</p>
                        </div>
                    </div>
                </div>

                <form id="receipt-form" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="reference" id="receipt-reference" value="">

                    <div class="form-group">
                        <label class="form-label">Upload Payment Receipt</label>
                        <div class="upload-area" id="upload-area" style="border: 2px dashed var(--gray-300); border-radius: 8px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s;">
                            <i class="iconoir-upload text-3xl text-secondary mb-2"></i>
                            <p class="text-secondary mb-1">Click to upload or drag and drop</p>
                            <p class="text-xs text-secondary">PNG, JPG or PDF (max. 5MB)</p>
                            <input type="file" name="receipt" id="receipt-file" accept="image/*,.pdf" style="display: none;">
                        </div>
                        <div id="file-preview" style="display: none;" class="mt-3 p-3 bg-gray-50 rounded d-flex items-center gap-3" style="background: var(--gray-50);">
                            <i class="iconoir-page text-xl text-primary"></i>
                            <span id="file-name" class="flex-1"></span>
                            <button type="button" class="btn btn-ghost btn-sm text-danger" onclick="removeFile()">
                                <i class="iconoir-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="submit-receipt-btn" disabled>
                        Submit Receipt for Verification
                    </button>
                </form>

                <div class="text-center mt-4">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="goBack()">
                        <i class="iconoir-arrow-left"></i> Choose different payment method
                    </button>
                </div>
            </div>

            <!-- Step 3: Success -->
            <div id="step-success" style="display: none;">
                <div class="text-center py-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-success-soft mb-4" style="background: #D1FAE5;">
                        <i class="iconoir-check text-success text-3xl"></i>
                    </div>
                    <h3 class="font-semibold text-xl mb-2">Receipt Submitted!</h3>
                    <p class="text-secondary mb-6">Your payment is being verified. You'll receive an email once your subscription is activated.</p>
                    <a href="/subscription" class="btn btn-primary">View Subscription Status</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Summary -->
    <div>
        <div class="card" style="position: sticky; top: 88px;">
            <div class="card-header">
                <h3 class="card-title">Order Summary</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-between mb-3">
                    <span><?= e($planName) ?> Plan</span>
                    <strong><?= format_currency($planPrice) ?></strong>
                </div>
                <div class="border-t pt-3 mt-3" style="border-color: var(--gray-100);">
                    <div class="d-flex justify-between">
                        <span class="font-semibold">Total</span>
                        <span class="font-bold text-xl"><?= format_currency($planPrice) ?></span>
                    </div>
                </div>

                <div class="mt-6 text-sm text-secondary">
                    <h4 class="font-medium mb-2">What's included:</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php
                        $features = $plan['features'] ?? [];
                        if (empty($features)) {
                            $features = ['Unlimited Course Access', 'Goal Tracking', 'Accountability Partner', 'AI Career Assistant'];
                        }
                        foreach ($features as $feature):
                        ?>
                            <li class="d-flex gap-2 mb-2">
                                <i class="iconoir-check text-success"></i>
                                <?= e(is_array($feature) ? ($feature['text'] ?? '') : $feature) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
let currentReference = '';

// Payment method selection styles
document.querySelectorAll('.payment-method-option').forEach(option => {
    const radio = option.querySelector('input[type="radio"]');

    // Set initial state
    if (radio.checked) {
        option.style.borderColor = 'var(--primary)';
        option.style.background = 'var(--primary-soft)';
    }

    radio.addEventListener('change', () => {
        document.querySelectorAll('.payment-method-option').forEach(o => {
            o.style.borderColor = 'var(--gray-200)';
            o.style.background = 'transparent';
        });
        if (radio.checked) {
            option.style.borderColor = 'var(--primary)';
            option.style.background = 'var(--primary-soft)';
        }
    });
});

// Payment form submission
document.getElementById('payment-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('pay-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span> Processing...';

    try {
        const formData = new FormData(this);
        const paymentMethod = formData.get('payment_method');

        const response = await API.post('/subscription/payment', {
            plan_id: formData.get('plan_id'),
            payment_method: paymentMethod
        });

        if (!response.success) {
            throw new Error(response.message || 'Payment initialization failed');
        }

        currentReference = response.data.reference;

        if (paymentMethod === 'bank_transfer') {
            // Show bank transfer details
            document.getElementById('payment-reference').textContent = currentReference;
            document.getElementById('receipt-reference').value = currentReference;

            if (response.data.bank_details) {
                document.getElementById('bank-name').textContent = response.data.bank_details.bank_name;
                document.getElementById('account-number').textContent = response.data.bank_details.account_number;
                document.getElementById('account-name').textContent = response.data.bank_details.account_name;
            }

            document.getElementById('step-method').style.display = 'none';
            document.getElementById('step-bank-details').style.display = 'block';
        } else if (paymentMethod === 'paystack') {
            // Handle Paystack payment
            if (response.data.authorization_url) {
                window.location.href = response.data.authorization_url;
            } else {
                const handler = PaystackPop.setup({
                    key: '<?= defined('PAYSTACK_PUBLIC_KEY') ? PAYSTACK_PUBLIC_KEY : '' ?>',
                    email: '<?= e($user['email'] ?? '') ?>',
                    amount: response.data.amount,
                    ref: response.data.reference,
                    onClose: function() {
                        btn.disabled = false;
                        btn.innerHTML = 'Proceed to Pay <?= format_currency($planPrice) ?>';
                    },
                    callback: function(response) {
                        window.location.href = '/subscription/verify?reference=' + response.reference;
                    }
                });
                handler.openIframe();
            }
        } else {
            throw new Error('Unsupported payment method');
        }
    } catch (error) {
        Toast.error(error.message || 'Payment failed');
        btn.disabled = false;
        btn.innerHTML = 'Proceed to Pay <?= format_currency($planPrice) ?>';
    }
});

// File upload handling
const uploadArea = document.getElementById('upload-area');
const fileInput = document.getElementById('receipt-file');
const filePreview = document.getElementById('file-preview');
const fileName = document.getElementById('file-name');
const submitBtn = document.getElementById('submit-receipt-btn');

uploadArea.addEventListener('click', () => fileInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = 'var(--primary)';
    uploadArea.style.background = 'var(--primary-soft)';
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.style.borderColor = 'var(--gray-300)';
    uploadArea.style.background = 'transparent';
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.style.borderColor = 'var(--gray-300)';
    uploadArea.style.background = 'transparent';

    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        showFilePreview(e.dataTransfer.files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length) {
        showFilePreview(e.target.files[0]);
    }
});

function showFilePreview(file) {
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        Toast.error('File size must be less than 5MB');
        return;
    }

    fileName.textContent = file.name;
    uploadArea.style.display = 'none';
    filePreview.style.display = 'flex';
    submitBtn.disabled = false;
}

function removeFile() {
    fileInput.value = '';
    uploadArea.style.display = 'block';
    filePreview.style.display = 'none';
    submitBtn.disabled = true;
}

// Receipt submission
document.getElementById('receipt-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('submit-receipt-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span> Uploading...';

    try {
        const formData = new FormData(this);
        const response = await API.upload('/subscription/upload-receipt', formData);

        if (response.success) {
            document.getElementById('step-bank-details').style.display = 'none';
            document.getElementById('step-success').style.display = 'block';
        } else {
            throw new Error(response.message || 'Upload failed');
        }
    } catch (error) {
        Toast.error(error.message || 'Failed to upload receipt');
        btn.disabled = false;
        btn.innerHTML = 'Submit Receipt for Verification';
    }
});

function goBack() {
    document.getElementById('step-bank-details').style.display = 'none';
    document.getElementById('step-method').style.display = 'block';
    document.getElementById('pay-btn').disabled = false;
    document.getElementById('pay-btn').innerHTML = 'Proceed to Pay <?= format_currency($planPrice) ?>';
}

function copyToClipboard(elementId) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        Toast.success('Copied to clipboard!');
    }).catch(() => {
        // Fallback for older browsers
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        Toast.success('Copied to clipboard!');
    });
}
</script>

<style>
.upload-area:hover {
    border-color: var(--primary) !important;
    background: var(--primary-soft) !important;
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

code {
    font-family: 'SF Mono', Monaco, 'Courier New', monospace;
}
</style>
