<aside class="sidebar">
    <div class="sidebar-header">
        <a href="/" class="sidebar-logo">
            <img src="/images/logo.png" alt="Learnrail" onerror="this.style.display='none'">
            <span>Learnrail</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="/" class="nav-item <?= $_SERVER['REQUEST_URI'] === '/' ? 'active' : '' ?>">
                <i class="iconoir-home"></i>
                <span>Home</span>
            </a>
            <a href="/courses" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/courses') ? 'active' : '' ?>">
                <i class="iconoir-book"></i>
                <span>Courses</span>
            </a>
            <a href="/goals" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/goals') ? 'active' : '' ?>">
                <i class="iconoir-target"></i>
                <span>Goals</span>
                <?php if (!($isSubscribed ?? false)): ?>
                    <i class="iconoir-lock" style="margin-left: auto; font-size: 14px; opacity: 0.6;"></i>
                <?php endif; ?>
            </a>
            <a href="/accountability" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/accountability') ? 'active' : '' ?>">
                <i class="iconoir-chat-bubble"></i>
                <span>Partner</span>
                <?php if (!($isSubscribed ?? false)): ?>
                    <i class="iconoir-lock" style="margin-left: auto; font-size: 14px; opacity: 0.6;"></i>
                <?php endif; ?>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Explore</div>
            <a href="/career" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/career') ? 'active' : '' ?>">
                <i class="iconoir-suitcase"></i>
                <span>Career Guide</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Community</div>
            <a href="/leaderboard" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/leaderboard') ? 'active' : '' ?>">
                <i class="iconoir-medal"></i>
                <span>Leaderboard</span>
            </a>
            <a href="/achievements" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/achievements') ? 'active' : '' ?>">
                <i class="iconoir-trophy"></i>
                <span>Achievements</span>
            </a>
        </div>

        <?php if (!($isSubscribed ?? false)): ?>
        <div class="nav-section">
            <a href="/subscription" class="nav-item" style="background: var(--gradient); color: white;">
                <i class="iconoir-star"></i>
                <span>Go Premium</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <?php if ($isAdmin ?? false): ?>
        <a href="/admin" class="nav-item">
            <i class="iconoir-dashboard"></i>
            <span>Admin Panel</span>
        </a>
        <?php endif; ?>

        <a href="/logout" class="nav-item" style="color: var(--text-secondary);">
            <i class="iconoir-log-out"></i>
            <span>Logout</span>
        </a>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" alt="Avatar">
                <?php else: ?>
                    <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
                <div class="sidebar-user-role">
                    <?php if ($isSubscribed ?? false): ?>
                        <span class="badge badge-success">Premium</span>
                    <?php else: ?>
                        Free Plan
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</aside>
