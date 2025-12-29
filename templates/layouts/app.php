<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= e($title ?? 'Dashboard') ?> - Learnrail</title>

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
    <?php if ($warning): ?>
        <div data-flash-warning="<?= e($warning) ?>"></div>
    <?php endif; ?>
    <?php if ($info): ?>
        <div data-flash-info="<?= e($info) ?>"></div>
    <?php endif; ?>

    <div class="app-shell">
        <!-- Sidebar -->
        <?php require TEMPLATES_PATH . '/partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php require TEMPLATES_PATH . '/partials/header.php'; ?>

            <!-- Page Content -->
            <div class="page-content">
                <?= $content ?>
            </div>
        </main>

        <!-- Mobile Navigation -->
        <?php require TEMPLATES_PATH . '/partials/nav-mobile.php'; ?>
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
