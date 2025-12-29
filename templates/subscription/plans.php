<?php
$activePlans = array_filter($plans ?? [], fn($p) => ($p['is_active'] ?? false) || ($p['is_active'] ?? 0) == 1);
usort($activePlans, fn($a, $b) => ($a['price'] ?? 0) - ($b['price'] ?? 0));
?>

<!-- Current Subscription -->
<?php if ($isSubscribed ?? false): ?>
<div class="alert alert-success mb-6">
    <i class="iconoir-check-circle"></i>
    <span>You're currently subscribed to the <strong><?= e($user['subscription']['plan_name'] ?? 'Premium') ?></strong> plan.</span>
</div>
<?php endif; ?>

<!-- Plans Grid -->
<div class="grid grid-cols-3 mb-6">
    <?php foreach ($activePlans as $index => $plan): ?>
        <?php
        $isFeatured = $index === 1; // Middle plan is featured
        $features = $plan['features'] ?? [];
        if (is_string($features)) {
            $features = json_decode($features, true) ?? [];
        }
        ?>
        <div class="card <?= $isFeatured ? 'border-primary' : '' ?>" style="<?= $isFeatured ? 'border: 2px solid var(--primary); transform: scale(1.02);' : '' ?>">
            <?php if ($isFeatured): ?>
                <div class="text-center" style="background: var(--primary); color: white; padding: 8px; font-size: 0.75rem; font-weight: 600;">
                    MOST POPULAR
                </div>
            <?php endif; ?>
            <div class="card-body text-center">
                <h3 class="font-semibold text-lg mb-2"><?= e($plan['name'] ?? 'Plan') ?></h3>
                <div class="mb-4">
                    <span class="text-3xl font-bold"><?= format_currency($plan['price'] ?? 0) ?></span>
                    <span class="text-secondary">/<?= e($plan['duration_text'] ?? strtolower($plan['duration'] ?? 'month')) ?></span>
                </div>

                <?php if (($plan['discount_percent'] ?? 0) > 0): ?>
                    <span class="badge badge-success mb-4">Save <?= e($plan['discount_percent']) ?>%</span>
                <?php endif; ?>

                <ul class="text-left mb-6" style="list-style: none;">
                    <li class="d-flex gap-2 mb-3">
                        <i class="iconoir-check text-success"></i>
                        Unlimited Courses
                    </li>
                    <li class="d-flex gap-2 mb-3">
                        <i class="iconoir-check text-success"></i>
                        Goal Tracking
                    </li>
                    <li class="d-flex gap-2 mb-3">
                        <i class="iconoir-check text-success"></i>
                        Accountability Partner
                    </li>
                    <li class="d-flex gap-2 mb-3">
                        <i class="iconoir-check text-success"></i>
                        AI Career Assistant
                    </li>
                    <li class="d-flex gap-2 mb-3">
                        <i class="iconoir-check text-success"></i>
                        Priority Support
                    </li>
                    <?php foreach ($features as $feature): ?>
                        <li class="d-flex gap-2 mb-3">
                            <i class="iconoir-check text-success"></i>
                            <?= e($feature) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <a href="/subscription/payment/<?= e($plan['id']) ?>" class="btn <?= $isFeatured ? 'btn-primary' : 'btn-outline' ?> btn-block">
                    <?= ($isSubscribed ?? false) ? 'Switch Plan' : 'Get Started' ?>
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- FAQ -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Frequently Asked Questions</h3>
    </div>
    <div class="card-body">
        <div x-data="{ open: null }">
            <div class="border-b" style="border-color: var(--gray-100);">
                <button class="w-100 d-flex justify-between items-center py-4" style="background: none; border: none; cursor: pointer; text-align: left;" @click="open = open === 1 ? null : 1">
                    <span class="font-medium">Can I cancel my subscription anytime?</span>
                    <i class="iconoir-nav-arrow-down" :class="{ 'rotate-180': open === 1 }"></i>
                </button>
                <div x-show="open === 1" x-collapse class="pb-4 text-secondary">
                    Yes, you can cancel your subscription at any time. You'll continue to have access until the end of your billing period.
                </div>
            </div>
            <div class="border-b" style="border-color: var(--gray-100);">
                <button class="w-100 d-flex justify-between items-center py-4" style="background: none; border: none; cursor: pointer; text-align: left;" @click="open = open === 2 ? null : 2">
                    <span class="font-medium">What payment methods do you accept?</span>
                    <i class="iconoir-nav-arrow-down" :class="{ 'rotate-180': open === 2 }"></i>
                </button>
                <div x-show="open === 2" x-collapse class="pb-4 text-secondary">
                    We accept payments via Paystack, which supports debit cards, bank transfers, and mobile money.
                </div>
            </div>
            <div>
                <button class="w-100 d-flex justify-between items-center py-4" style="background: none; border: none; cursor: pointer; text-align: left;" @click="open = open === 3 ? null : 3">
                    <span class="font-medium">Is there a free trial?</span>
                    <i class="iconoir-nav-arrow-down" :class="{ 'rotate-180': open === 3 }"></i>
                </button>
                <div x-show="open === 3" x-collapse class="pb-4 text-secondary">
                    You can access free courses without a subscription. Premium features require a subscription.
                </div>
            </div>
        </div>
    </div>
</div>
