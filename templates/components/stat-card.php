<?php
/**
 * Stat Card Component
 * @param string $icon - Iconoir icon name
 * @param string $color - primary, success, warning, danger
 * @param string $label - Label text
 * @param string|int $value - Display value
 */
$colorClass = $color ?? 'primary';
?>
<div class="stat-card">
    <div class="stat-card-icon <?= $colorClass ?>">
        <i class="iconoir-<?= e($icon ?? 'star') ?>"></i>
    </div>
    <div class="stat-card-value"><?= e($value ?? '0') ?></div>
    <div class="stat-card-label"><?= e($label ?? 'Label') ?></div>
</div>
