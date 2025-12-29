<header class="app-header">
    <div class="header-left">
        <button class="menu-toggle" onclick="Sidebar.toggle()">
            <i class="iconoir-menu"></i>
        </button>
        <h1 class="page-title"><?= e($title ?? 'Dashboard') ?></h1>
    </div>

    <div class="header-right">
        <div class="header-search">
            <i class="iconoir-search"></i>
            <input type="text" placeholder="Search courses..." id="header-search">
        </div>

        <a href="/notifications" class="header-icon">
            <i class="iconoir-bell"></i>
            <span class="notification-badge"></span>
        </a>

        <div class="dropdown" id="user-dropdown" x-data="dropdown">
            <button class="header-avatar" @click="toggle()">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e($user['avatar']) ?>" alt="Avatar">
                <?php else: ?>
                    <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                <?php endif; ?>
            </button>

            <div class="dropdown-menu" x-show="open" @click.away="close()">
                <a href="/profile" class="dropdown-item">
                    <i class="iconoir-user"></i>
                    Profile
                </a>
                <a href="/settings" class="dropdown-item">
                    <i class="iconoir-settings"></i>
                    Settings
                </a>
                <?php if ($isAdmin ?? false): ?>
                <a href="/admin" class="dropdown-item">
                    <i class="iconoir-dashboard"></i>
                    Admin Panel
                </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="/logout" class="dropdown-item">
                    <i class="iconoir-log-out"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>
