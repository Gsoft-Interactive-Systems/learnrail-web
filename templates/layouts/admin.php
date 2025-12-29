<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= e($title ?? 'Admin') ?> - Learnrail Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css">

    <!-- Styles -->
    <link rel="stylesheet" href="/css/app.css">

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/images/favicon.png">
</head>
<body>
    <!-- Flash Messages -->
    <?php if ($success): ?>
        <div data-flash-success="<?= e($success) ?>"></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div data-flash-error="<?= e($error) ?>"></div>
    <?php endif; ?>

    <div class="app-shell">
        <!-- Admin Sidebar -->
        <aside class="sidebar admin-sidebar">
            <div class="sidebar-header">
                <a href="/admin" class="sidebar-logo">
                    <img src="/images/logo-white.png" alt="Learnrail" onerror="this.style.display='none'">
                    <span>Learnrail</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="/admin" class="nav-item <?= ($_SERVER['REQUEST_URI'] === '/admin' || $_SERVER['REQUEST_URI'] === '/admin/') ? 'active' : '' ?>">
                        <i class="iconoir-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <a href="/admin/users" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/users') ? 'active' : '' ?>">
                        <i class="iconoir-group"></i>
                        <span>Users</span>
                    </a>
                    <a href="/admin/courses" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/courses') && !str_contains($_SERVER['REQUEST_URI'], 'ai-courses') ? 'active' : '' ?>">
                        <i class="iconoir-book"></i>
                        <span>Courses</span>
                    </a>
                    <a href="/admin/ai-courses" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/ai-courses') ? 'active' : '' ?>">
                        <i class="iconoir-brain"></i>
                        <span>AI Courses</span>
                    </a>
                    <a href="/admin/payments" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/payments') ? 'active' : '' ?>">
                        <i class="iconoir-wallet"></i>
                        <span>Payments</span>
                    </a>
                    <a href="/admin/subscriptions" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/subscriptions') ? 'active' : '' ?>">
                        <i class="iconoir-credit-card"></i>
                        <span>Subscriptions</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Analytics</div>
                    <a href="/admin/reports" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/reports') ? 'active' : '' ?>">
                        <i class="iconoir-stats-up-square"></i>
                        <span>Reports</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="/admin/settings" class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/settings') ? 'active' : '' ?>">
                        <i class="iconoir-settings"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="/" class="nav-item">
                    <i class="iconoir-arrow-left"></i>
                    <span>Back to App</span>
                </a>
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= e($user['avatar']) ?>" alt="Avatar">
                        <?php else: ?>
                            <?= strtoupper(substr($user['first_name'] ?? 'A', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name"><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
                        <div class="sidebar-user-role">Administrator</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="app-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="Sidebar.toggle()">
                        <i class="iconoir-menu"></i>
                    </button>
                    <h1 class="page-title"><?= e($title ?? 'Admin') ?></h1>
                </div>

                <div class="header-right">
                    <a href="/logout" class="btn btn-ghost btn-sm">
                        <i class="iconoir-log-out"></i>
                        Logout
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
                <?= $content ?>
            </div>
        </main>
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" onclick="Sidebar.close()"></div>

    <!-- Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/app.js"></script>

    <?php if (isset($scripts) && is_array($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= e($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($inlineScript)): ?>
        <script><?= $inlineScript ?></script>
    <?php endif; ?>
</body>
</html>
