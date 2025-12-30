<div class="upgrade-container">
    <div class="upgrade-card">
        <div class="upgrade-icon">
            <i class="iconoir-lock"></i>
        </div>
        <h1 class="upgrade-title"><?= e($feature ?? 'Premium Feature') ?></h1>
        <p class="upgrade-description"><?= e($description ?? 'This feature is available for premium subscribers.') ?></p>

        <?php if (!empty($benefits)): ?>
        <div class="upgrade-benefits">
            <h3>What you get:</h3>
            <ul>
                <?php foreach ($benefits as $benefit): ?>
                    <li>
                        <i class="iconoir-check-circle"></i>
                        <span><?= e($benefit) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="upgrade-cta">
            <a href="/subscription" class="btn btn-primary btn-lg">
                <i class="iconoir-star"></i>
                Upgrade to Premium
            </a>
            <p class="upgrade-note">Unlock all premium features and accelerate your learning journey</p>
        </div>

        <?php if (!empty($plans)): ?>
        <div class="upgrade-plans-preview">
            <h4>Choose a plan that works for you:</h4>
            <div class="mini-plans">
                <?php foreach ($plans as $plan): ?>
                <a href="/subscription" class="mini-plan <?= $plan['is_popular'] ? 'featured' : '' ?>">
                    <span class="plan-name"><?= e($plan['name']) ?></span>
                    <span class="plan-price">
                        <?= $plan['currency'] === 'NGN' ? 'N' : $plan['currency'] ?><?= number_format($plan['price']) ?>
                        <?php if ($plan['duration_months'] == 1): ?>/month
                        <?php elseif ($plan['duration_months'] == 3): ?>/3 months
                        <?php elseif ($plan['duration_months'] == 6): ?>/6 months
                        <?php elseif ($plan['duration_months'] == 12): ?>/year
                        <?php else: ?>/<?= $plan['duration_months'] ?> months
                        <?php endif; ?>
                    </span>
                    <span class="plan-features">
                        <?php if ($plan['includes_goal_tracker']): ?>
                            <i class="iconoir-check" title="Goal Tracker"></i>
                        <?php endif; ?>
                        <?php if ($plan['includes_accountability_partner']): ?>
                            <i class="iconoir-users" title="Accountability Partner"></i>
                        <?php endif; ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.upgrade-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 80vh;
    padding: 20px;
}

.upgrade-card {
    background: white;
    border-radius: 16px;
    padding: 48px;
    text-align: center;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.upgrade-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
}

.upgrade-icon i {
    font-size: 36px;
    color: white;
}

.upgrade-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 12px;
}

.upgrade-description {
    font-size: 16px;
    color: var(--gray-600);
    margin-bottom: 32px;
    line-height: 1.6;
}

.upgrade-benefits {
    background: var(--gray-50);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
    text-align: left;
}

.upgrade-benefits h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 16px;
}

.upgrade-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.upgrade-benefits li {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    color: var(--gray-700);
}

.upgrade-benefits li i {
    color: var(--success);
    font-size: 18px;
}

.upgrade-cta {
    margin-bottom: 32px;
}

.upgrade-cta .btn-lg {
    padding: 16px 32px;
    font-size: 16px;
    font-weight: 600;
}

.upgrade-note {
    font-size: 13px;
    color: var(--gray-500);
    margin-top: 12px;
}

.upgrade-plans-preview {
    border-top: 1px solid var(--gray-100);
    padding-top: 24px;
}

.upgrade-plans-preview h4 {
    font-size: 14px;
    color: var(--gray-600);
    margin-bottom: 16px;
}

.mini-plans {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.mini-plan {
    background: var(--gray-50);
    border-radius: 8px;
    padding: 12px 16px;
    flex: 1;
    max-width: 180px;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
    display: block;
    text-align: center;
}

.mini-plan:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.mini-plan.featured {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.mini-plan .plan-name {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
}

.mini-plan .plan-price {
    display: block;
    font-size: 12px;
    opacity: 0.8;
    margin-bottom: 4px;
}

.mini-plan .plan-features {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 6px;
}

.mini-plan .plan-features i {
    font-size: 14px;
    opacity: 0.9;
}

@media (max-width: 600px) {
    .upgrade-card {
        padding: 32px 24px;
    }

    .upgrade-title {
        font-size: 24px;
    }

    .mini-plans {
        flex-direction: column;
    }

    .mini-plan {
        max-width: 100%;
    }
}
</style>
