<div class="card" style="max-width: 700px;">
    <div class="card-header">
        <h3 class="card-title">Notifications</h3>
        <?php if (!empty($notifications)): ?>
            <button class="btn btn-ghost btn-sm" onclick="markAllRead()">
                Mark all as read
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <?php
                $isUnread = empty($notification['read_at']);
                $icon = match($notification['type'] ?? 'info') {
                    'achievement' => 'trophy',
                    'course' => 'book',
                    'goal' => 'target',
                    'partner' => 'group',
                    'subscription' => 'credit-card',
                    'payment' => 'wallet',
                    'reminder' => 'bell',
                    default => 'bell-notification'
                };
                $color = match($notification['type'] ?? 'info') {
                    'achievement' => 'warning',
                    'course' => 'primary',
                    'goal' => 'success',
                    'partner' => 'info',
                    'subscription' => 'danger',
                    'payment' => 'success',
                    default => 'secondary'
                };
                ?>
                <div class="notification-item <?= $isUnread ? 'unread' : '' ?>"
                     onclick="handleNotificationClick(<?= e($notification['id']) ?>, '<?= e($notification['action_url'] ?? '') ?>')">
                    <div class="notification-icon bg-<?= $color ?>-light">
                        <i class="iconoir-<?= $icon ?> text-<?= $color ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?= e($notification['title'] ?? '') ?></div>
                        <div class="notification-message"><?= e($notification['body'] ?? '') ?></div>
                        <div class="notification-time"><?= time_ago($notification['created_at'] ?? '') ?></div>
                    </div>
                    <?php if ($isUnread): ?>
                        <div class="notification-badge"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Load More -->
            <?php if ($hasMore ?? false): ?>
                <div class="p-4 text-center">
                    <button class="btn btn-ghost" onclick="loadMore()">
                        Load More
                    </button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state py-8">
                <div class="empty-state-icon">
                    <i class="iconoir-bell"></i>
                </div>
                <h3 class="empty-state-title">No Notifications</h3>
                <p class="empty-state-text">You're all caught up! Check back later for updates.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: background 0.2s;
    position: relative;
}

.notification-item:hover {
    background: var(--gray-50);
}

.notification-item.unread {
    background: rgba(99, 102, 241, 0.05);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    margin-bottom: 4px;
}

.notification-message {
    color: var(--gray-600);
    font-size: 14px;
    margin-bottom: 4px;
}

.notification-time {
    color: var(--gray-400);
    font-size: 12px;
}

.notification-badge {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--primary);
    position: absolute;
    top: 20px;
    right: 20px;
}

.bg-warning-light { background: rgba(245, 158, 11, 0.1); }
.bg-primary-light { background: rgba(99, 102, 241, 0.1); }
.bg-success-light { background: rgba(16, 185, 129, 0.1); }
.bg-info-light { background: rgba(59, 130, 246, 0.1); }
.bg-danger-light { background: rgba(239, 68, 68, 0.1); }
.bg-secondary-light { background: rgba(107, 114, 128, 0.1); }

.text-info { color: #3B82F6; }
</style>

<script>
let currentPage = 1;

async function handleNotificationClick(id, actionUrl) {
    // Mark as read
    try {
        await API.post('/notifications/' + id + '/read');
    } catch (e) {}

    // Navigate if action URL exists
    if (actionUrl) {
        window.location.href = actionUrl;
    } else {
        location.reload();
    }
}

async function markAllRead() {
    try {
        const response = await API.post('/notifications/mark-all-read');
        if (response.success) {
            Toast.success('All notifications marked as read');
            location.reload();
        }
    } catch (error) {
        Toast.error('Failed to mark notifications as read');
    }
}

async function loadMore() {
    currentPage++;
    try {
        const response = await API.get('/notifications?page=' + currentPage);
        if (response.data && response.data.length > 0) {
            // Append notifications (would need proper DOM manipulation)
            location.href = '/notifications?page=' + currentPage;
        }
    } catch (error) {
        Toast.error('Failed to load more notifications');
    }
}
</script>
