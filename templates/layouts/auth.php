<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= e($title ?? 'Login') ?> - Learnrail</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css">

    <!-- Styles -->
    <link rel="stylesheet" href="/css/app.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/images/favicon.png">
</head>
<body>
    <div class="auth-layout">
        <div class="auth-card">
            <!-- Logo -->
            <div class="auth-header">
                <a href="https://learnrail.org" class="auth-logo">
                    <img src="/images/logo.png" alt="Learnrail" onerror="this.style.display='none'">
                    <span>Learnrail</span>
                </a>
                <h1 class="auth-title"><?= e($title ?? 'Welcome') ?></h1>
                <?php if (isset($subtitle)): ?>
                    <p class="auth-subtitle"><?= e($subtitle) ?></p>
                <?php endif; ?>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="iconoir-check-circle"></i>
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="iconoir-xmark-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- Content -->
            <?= $content ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/js/api.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
