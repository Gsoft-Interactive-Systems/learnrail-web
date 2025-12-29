<?php
$planName = $plan['name'] ?? 'Premium';
$planPrice = $plan['price'] ?? 0;
?>

<div class="grid" style="grid-template-columns: 1fr 400px; gap: 24px; max-width: 900px; margin: 0 auto;">
    <!-- Payment Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Payment Details</h3>
        </div>
        <div class="card-body">
            <form id="payment-form">
                <?= csrf_field() ?>
                <input type="hidden" name="plan_id" value="<?= e($plan['id']) ?>">

                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <?php foreach ($paymentMethods ?? [] as $method): ?>
                        <?php if (($method['is_active'] ?? false) || ($method['is_active'] ?? 0) == 1): ?>
                            <label class="form-radio d-flex gap-3 p-4 border rounded mb-2 cursor-pointer" style="border-color: var(--gray-200);">
                                <input type="radio" name="payment_method" value="<?= e($method['slug'] ?? $method['name']) ?>" <?= ($method['slug'] ?? '') === 'paystack' ? 'checked' : '' ?>>
                                <div class="flex-1">
                                    <div class="font-medium"><?= e($method['name'] ?? 'Payment Method') ?></div>
                                    <div class="text-sm text-secondary"><?= e($method['description'] ?? '') ?></div>
                                </div>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="pay-btn">
                    Pay <?= format_currency($planPrice) ?>
                </button>

                <p class="text-xs text-secondary text-center mt-4">
                    <i class="iconoir-lock"></i>
                    Secure payment powered by Paystack
                </p>
            </form>
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
                    <ul style="list-style: none;">
                        <li class="d-flex gap-2 mb-2">
                            <i class="iconoir-check text-success"></i>
                            Unlimited Course Access
                        </li>
                        <li class="d-flex gap-2 mb-2">
                            <i class="iconoir-check text-success"></i>
                            Goal Tracking
                        </li>
                        <li class="d-flex gap-2 mb-2">
                            <i class="iconoir-check text-success"></i>
                            Accountability Partner
                        </li>
                        <li class="d-flex gap-2">
                            <i class="iconoir-check text-success"></i>
                            AI Career Assistant
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('payment-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('pay-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span> Processing...';

    try {
        const formData = new FormData(this);
        const response = await API.post('/subscription/payment', {
            plan_id: formData.get('plan_id'),
            payment_method: formData.get('payment_method')
        });

        if (response.success && response.data.authorization_url) {
            // Redirect to Paystack
            window.location.href = response.data.authorization_url;
        } else if (response.success && response.data.reference) {
            // Use Paystack popup
            const handler = PaystackPop.setup({
                key: '<?= defined('PAYSTACK_PUBLIC_KEY') ? PAYSTACK_PUBLIC_KEY : '' ?>',
                email: '<?= e($user['email'] ?? '') ?>',
                amount: <?= $planPrice * 100 ?>,
                ref: response.data.reference,
                onClose: function() {
                    btn.disabled = false;
                    btn.innerHTML = 'Pay <?= format_currency($planPrice) ?>';
                },
                callback: function(response) {
                    window.location.href = '/subscription/verify?reference=' + response.reference;
                }
            });
            handler.openIframe();
        } else {
            throw new Error(response.data?.message || 'Payment initialization failed');
        }
    } catch (error) {
        Toast.error(error.message || 'Payment failed');
        btn.disabled = false;
        btn.innerHTML = 'Pay <?= format_currency($planPrice) ?>';
    }
});
</script>
