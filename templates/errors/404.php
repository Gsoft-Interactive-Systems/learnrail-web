<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Learnrail</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <div class="auth-layout">
        <div class="auth-card text-center">
            <div class="empty-state-icon" style="margin-bottom: 24px;">
                <i class="iconoir-page-not-found"></i>
            </div>
            <h1 class="auth-title">Page Not Found</h1>
            <p class="text-secondary mb-6">
                <?= e($message ?? "The page you're looking for doesn't exist or has been moved.") ?>
            </p>
            <a href="/" class="btn btn-primary">
                <i class="iconoir-home"></i>
                Go Home
            </a>
        </div>
    </div>
</body>
</html>
