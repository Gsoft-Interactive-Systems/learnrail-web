<nav class="mobile-nav">
    <div class="mobile-nav-items">
        <a href="/" class="mobile-nav-item <?= $_SERVER['REQUEST_URI'] === '/' ? 'active' : '' ?>">
            <i class="iconoir-home"></i>
            <span>Home</span>
        </a>
        <a href="/courses" class="mobile-nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/courses') ? 'active' : '' ?>">
            <i class="iconoir-book"></i>
            <span>Courses</span>
        </a>
        <?php if ($isSubscribed ?? false): ?>
        <a href="/goals" class="mobile-nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/goals') ? 'active' : '' ?>">
            <i class="iconoir-target"></i>
            <span>Goals</span>
        </a>
        <a href="/accountability" class="mobile-nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/accountability') ? 'active' : '' ?>">
            <i class="iconoir-chat-bubble"></i>
            <span>Partner</span>
        </a>
        <?php else: ?>
        <a href="/ai-tutor" class="mobile-nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/ai-tutor') ? 'active' : '' ?>">
            <i class="iconoir-brain"></i>
            <span>AI Tutor</span>
        </a>
        <a href="/subscription" class="mobile-nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/subscription') ? 'active' : '' ?>">
            <i class="iconoir-star"></i>
            <span>Premium</span>
        </a>
        <?php endif; ?>
        <a href="/profile" class="mobile-nav-item <?= str_starts_with($_SERVER['REQUEST_URI'], '/profile') ? 'active' : '' ?>">
            <i class="iconoir-user"></i>
            <span>Profile</span>
        </a>
        <a href="/logout" class="mobile-nav-item">
            <i class="iconoir-log-out"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>
