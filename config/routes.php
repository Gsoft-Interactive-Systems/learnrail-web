<?php
/**
 * Application Routes
 * Learnrail Web App
 */

use Core\View;

// ===========================================
// PUBLIC API ROUTES (For landing page and external access)
// ===========================================

$router->get('/api/plans', function () {
    // Enable CORS for the landing page
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Content-Type: application/json');

    $plans = \Core\Database::query("
        SELECT id, name, slug, description, duration_months, duration_days, price, original_price,
               currency, features, is_popular, includes_goal_tracker, includes_accountability_partner
        FROM subscription_plans
        WHERE is_active = 1
        ORDER BY sort_order, price
    ");

    // Decode features JSON for each plan
    foreach ($plans as &$plan) {
        $plan['features'] = json_decode($plan['features'] ?? '[]', true) ?: [];
        $plan['price'] = (float) $plan['price'];
        $plan['original_price'] = $plan['original_price'] ? (float) $plan['original_price'] : null;
        $plan['is_popular'] = (bool) $plan['is_popular'];
        $plan['includes_goal_tracker'] = (bool) $plan['includes_goal_tracker'];
        $plan['includes_accountability_partner'] = (bool) $plan['includes_accountability_partner'];
    }

    echo json_encode([
        'success' => true,
        'data' => $plans
    ]);
    exit;
});

// ===========================================
// PUBLIC ROUTES (No authentication required)
// ===========================================

$router->group(['middleware' => 'guest'], function ($router) {
    // Auth pages
    $router->get('/login', function () {
        View::render('auth/login', ['title' => 'Login'], 'auth');
    });

    $router->post('/login', function () {
        global $auth;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/login');
        }

        $result = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '');

        if ($result['success']) {
            $intended = $_SESSION['intended_url'] ?? '/';
            unset($_SESSION['intended_url']);
            redirect($intended);
        } else {
            flash('error', $result['message']);
            $_SESSION['old_input'] = ['email' => $_POST['email'] ?? ''];
            redirect('/login');
        }
    });

    $router->get('/register', function () {
        View::render('auth/register', ['title' => 'Create Account'], 'auth');
    });

    $router->post('/register', function () {
        global $auth;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/register');
        }

        $result = $auth->register([
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? '',
            'phone' => $_POST['phone'] ?? ''
        ]);

        if ($result['success']) {
            redirect('/');
        } else {
            flash('error', $result['message']);
            $_SESSION['old_input'] = $_POST;
            redirect('/register');
        }
    });

    $router->get('/forgot-password', function () {
        View::render('auth/forgot-password', ['title' => 'Forgot Password'], 'auth');
    });

    $router->post('/forgot-password', function () {
        global $auth;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/forgot-password');
        }

        $result = $auth->forgotPassword($_POST['email'] ?? '');

        if ($result['success']) {
            flash('success', 'Password reset instructions sent to your email');
        } else {
            flash('error', $result['message']);
        }
        redirect('/forgot-password');
    });
});

// ===========================================
// AUTHENTICATED ROUTES
// ===========================================

$router->group(['middleware' => 'auth'], function ($router) {
    // Logout
    $router->get('/logout', function () {
        global $auth;
        $auth->logout();
        redirect('/login');
    });

    $router->post('/logout', function () {
        global $auth;
        $auth->logout();
        redirect('/login');
    });

    // Dashboard / Home
    $router->get('/', function () {
        global $auth;

        $user = $auth->user();
        $userId = $user['id'] ?? 0;

        // Get user's enrolled courses with progress
        $enrolledCourses = \Core\Database::query("
            SELECT c.*, e.progress_percent, e.status as enrollment_status, e.enrolled_at,
                   cat.name as category_name
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE e.user_id = ? AND e.status IN ('enrolled', 'in_progress')
            ORDER BY e.last_accessed_at DESC
            LIMIT 3
        ", [$userId]);

        // Get featured courses
        $featuredCourses = \Core\Database::query("
            SELECT c.*, cat.name as category_name,
                   (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) as lesson_count
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE c.is_featured = 1 AND c.is_published = 1
            ORDER BY c.created_at DESC
            LIMIT 6
        ");

        View::render('home/index', [
            'title' => 'Dashboard',
            'enrolledCourses' => $enrolledCourses,
            'featuredCourses' => $featuredCourses
        ]);
    });

    // Courses
    $router->get('/courses', function () {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $level = $_GET['level'] ?? '';

        $where = "c.is_published = 1";
        $params = [];

        if ($search) {
            $where .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($category) {
            $where .= " AND c.category_id = ?";
            $params[] = $category;
        }
        if ($level) {
            $where .= " AND c.level = ?";
            $params[] = $level;
        }

        $total = (int) \Core\Database::scalar("SELECT COUNT(*) FROM courses c WHERE {$where}", $params);

        $courses = \Core\Database::query("
            SELECT c.*, cat.name as category_name,
                   (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) as lesson_count
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE {$where}
            ORDER BY c.is_featured DESC, c.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        $categories = \Core\Database::query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name");

        View::render('courses/index', [
            'title' => 'Courses',
            'courses' => $courses,
            'meta' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'total' => $total
            ],
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedLevel' => $level
        ]);
    });

    $router->get('/courses/{id}', function ($id) {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Get course with instructor and category
        $course = \Core\Database::queryOne("
            SELECT c.*, cat.name as category_name,
                   i.name as instructor_name, i.bio as instructor_bio, i.avatar as instructor_avatar
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE c.id = ? OR c.slug = ?
        ", [$id, $id]);

        if (!$course) {
            View::error(404, 'Course not found');
        }

        // Get modules with lessons
        $modules = \Core\Database::query("
            SELECT * FROM modules WHERE course_id = ? ORDER BY sort_order
        ", [$course['id']]);

        foreach ($modules as &$module) {
            $module['lessons'] = \Core\Database::query("
                SELECT l.*,
                       (SELECT status FROM lesson_progress WHERE user_id = ? AND lesson_id = l.id) as progress_status
                FROM lessons l
                WHERE l.module_id = ?
                ORDER BY l.sort_order
            ", [$userId, $module['id']]);
        }
        $course['modules'] = $modules;

        // Check if user is enrolled
        $enrollment = \Core\Database::queryOne("
            SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?
        ", [$userId, $course['id']]);
        $course['is_enrolled'] = !empty($enrollment);
        $course['enrollment'] = $enrollment;

        View::render('courses/show', [
            'title' => $course['title'] ?? 'Course Details',
            'course' => $course
        ]);
    });

    $router->post('/courses/{id}/enroll', function ($id) {
        global $auth;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/courses/' . $id);
        }

        $userId = $auth->user()['id'] ?? 0;

        // Check if course exists
        $course = \Core\Database::queryOne("SELECT id FROM courses WHERE id = ? OR slug = ?", [$id, $id]);
        if (!$course) {
            flash('error', 'Course not found');
            redirect('/courses');
        }

        // Check if already enrolled
        $existing = \Core\Database::queryOne("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?", [$userId, $course['id']]);
        if ($existing) {
            flash('info', 'You are already enrolled in this course');
            redirect('/courses/' . $id);
        }

        // Create enrollment
        \Core\Database::execute("
            INSERT INTO enrollments (user_id, course_id, status, enrolled_at)
            VALUES (?, ?, 'enrolled', NOW())
        ", [$userId, $course['id']]);

        flash('success', 'Successfully enrolled in course!');
        redirect('/courses/' . $id);
    });

    $router->get('/courses/{courseId}/lessons/{lessonId}', function ($courseId, $lessonId) {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Get lesson with module info
        $lesson = \Core\Database::queryOne("
            SELECT l.*, m.title as module_title, m.course_id
            FROM lessons l
            JOIN modules m ON l.module_id = m.id
            WHERE l.id = ?
        ", [$lessonId]);

        if (!$lesson) {
            View::error(404, 'Lesson not found');
        }

        // Get course
        $course = \Core\Database::queryOne("
            SELECT c.*, cat.name as category_name
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE c.id = ?
        ", [$lesson['course_id']]);

        // Get all lessons for navigation
        $allLessons = \Core\Database::query("
            SELECT l.id, l.title, l.sort_order, m.sort_order as module_order
            FROM lessons l
            JOIN modules m ON l.module_id = m.id
            WHERE m.course_id = ?
            ORDER BY m.sort_order, l.sort_order
        ", [$lesson['course_id']]);

        // Find prev/next lessons
        $currentIndex = array_search($lessonId, array_column($allLessons, 'id'));
        $lesson['prev_lesson'] = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
        $lesson['next_lesson'] = $currentIndex < count($allLessons) - 1 ? $allLessons[$currentIndex + 1] : null;

        // Update/create lesson progress
        $progress = \Core\Database::queryOne("SELECT id FROM lesson_progress WHERE user_id = ? AND lesson_id = ?", [$userId, $lessonId]);
        if (!$progress) {
            \Core\Database::execute("
                INSERT INTO lesson_progress (user_id, lesson_id, status, created_at)
                VALUES (?, ?, 'in_progress', NOW())
            ", [$userId, $lessonId]);
        }

        View::render('courses/lesson', [
            'title' => $lesson['title'] ?? 'Lesson',
            'lesson' => $lesson,
            'course' => $course ?? [],
            'courseId' => $courseId
        ]);
    });

    $router->post('/lessons/{id}/complete', function ($id) {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Update lesson progress
        $existing = \Core\Database::queryOne("SELECT id FROM lesson_progress WHERE user_id = ? AND lesson_id = ?", [$userId, $id]);

        if ($existing) {
            \Core\Database::execute("
                UPDATE lesson_progress SET status = 'completed', completed_at = NOW(), updated_at = NOW()
                WHERE user_id = ? AND lesson_id = ?
            ", [$userId, $id]);
        } else {
            \Core\Database::execute("
                INSERT INTO lesson_progress (user_id, lesson_id, status, completed_at, created_at)
                VALUES (?, ?, 'completed', NOW(), NOW())
            ", [$userId, $id]);
        }

        // Update enrollment progress
        $lesson = \Core\Database::queryOne("
            SELECT m.course_id FROM lessons l JOIN modules m ON l.module_id = m.id WHERE l.id = ?
        ", [$id]);

        if ($lesson) {
            $totalLessons = (int) \Core\Database::scalar("
                SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = ?
            ", [$lesson['course_id']]);

            $completedLessons = (int) \Core\Database::scalar("
                SELECT COUNT(*) FROM lesson_progress lp
                JOIN lessons l ON lp.lesson_id = l.id
                JOIN modules m ON l.module_id = m.id
                WHERE lp.user_id = ? AND m.course_id = ? AND lp.status = 'completed'
            ", [$userId, $lesson['course_id']]);

            $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;
            $status = $progress >= 100 ? 'completed' : 'in_progress';

            \Core\Database::execute("
                UPDATE enrollments SET
                    progress_percent = ?,
                    completed_lessons = ?,
                    status = ?,
                    last_accessed_at = NOW()
                WHERE user_id = ? AND course_id = ?
            ", [$progress, $completedLessons, $status, $userId, $lesson['course_id']]);

            // Award points for lesson completion
            \Core\Database::execute("UPDATE users SET total_points = total_points + 10 WHERE id = ?", [$userId]);
        }

        View::json(['success' => true, 'message' => 'Lesson marked as complete']);
    });

    // Profile
    $router->get('/profile', function () {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Get user profile with stats
        $profile = \Core\Database::queryOne("SELECT * FROM users WHERE id = ?", [$userId]);

        if ($profile) {
            // Get enrollment stats
            $profile['courses_enrolled'] = (int) \Core\Database::scalar("
                SELECT COUNT(*) FROM enrollments WHERE user_id = ?
            ", [$userId]);

            $profile['courses_completed'] = (int) \Core\Database::scalar("
                SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND status = 'completed'
            ", [$userId]);

            $profile['lessons_completed'] = (int) \Core\Database::scalar("
                SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND status = 'completed'
            ", [$userId]);

            // Get badges count
            $profile['badges_earned'] = (int) \Core\Database::scalar("
                SELECT COUNT(*) FROM user_badges WHERE user_id = ?
            ", [$userId]);
        }

        View::render('profile/index', [
            'title' => 'Profile',
            'profile' => $profile ?? []
        ]);
    });

    $router->get('/profile/edit', function () {
        global $auth;

        View::render('profile/edit', [
            'title' => 'Edit Profile'
        ]);
    });

    $router->post('/profile/edit', function () {
        global $auth;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/profile/edit');
        }

        $userId = $auth->user()['id'] ?? 0;

        \Core\Database::execute("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                phone = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [
            $_POST['first_name'] ?? '',
            $_POST['last_name'] ?? '',
            $_POST['phone'] ?? '',
            $userId
        ]);

        $auth->refreshUser();
        flash('success', 'Profile updated successfully');
        redirect('/profile');
    });

    $router->get('/settings', function () {
        View::render('profile/settings', [
            'title' => 'Settings'
        ]);
    });

    // Subscription
    $router->get('/subscription', function () {
        $plans = \Core\Database::query("
            SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order, price
        ");

        // Decode features JSON for each plan
        foreach ($plans as &$plan) {
            $plan['features'] = json_decode($plan['features'] ?? '[]', true) ?: [];
        }

        View::render('subscription/plans', [
            'title' => 'Subscription Plans',
            'plans' => $plans
        ]);
    });

    $router->get('/subscription/payment/{planId}', function ($planId) {
        $selectedPlan = \Core\Database::queryOne("
            SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1
        ", [$planId]);

        if (!$selectedPlan) {
            flash('error', 'Invalid plan selected');
            redirect('/subscription');
        }

        $selectedPlan['features'] = json_decode($selectedPlan['features'] ?? '[]', true) ?: [];

        $paymentMethods = \Core\Database::query("
            SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order
        ");

        View::render('subscription/payment', [
            'title' => 'Complete Payment',
            'plan' => $selectedPlan,
            'paymentMethods' => $paymentMethods
        ]);
    });

    $router->post('/subscription/payment', function () {
        global $auth;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            View::json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $userId = $auth->user()['id'] ?? 0;
        $planId = $_POST['plan_id'] ?? '';
        $paymentMethod = $_POST['payment_method'] ?? 'paystack';

        // Get plan details
        $plan = \Core\Database::queryOne("SELECT * FROM subscription_plans WHERE id = ?", [$planId]);
        if (!$plan) {
            View::json(['success' => false, 'message' => 'Invalid plan']);
        }

        // Generate payment reference
        $reference = 'LR_' . strtoupper(bin2hex(random_bytes(8)));

        // Create pending subscription
        \Core\Database::execute("
            INSERT INTO subscriptions (user_id, plan_id, status, payment_method, payment_reference, amount_paid, created_at)
            VALUES (?, ?, 'pending', ?, ?, ?, NOW())
        ", [$userId, $planId, $paymentMethod, $reference, $plan['price']]);

        $subscriptionId = \Core\Database::lastInsertId();

        // Create payment record
        \Core\Database::execute("
            INSERT INTO payments (user_id, subscription_id, amount, currency, payment_method, reference, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ", [$userId, $subscriptionId, $plan['price'], $plan['currency'] ?? 'NGN', $paymentMethod, $reference]);

        // Return payment initialization data (for Paystack, etc.)
        View::json([
            'success' => true,
            'data' => [
                'reference' => $reference,
                'amount' => $plan['price'] * 100, // Convert to kobo for Paystack
                'email' => $auth->user()['email'] ?? '',
                'plan_name' => $plan['name']
            ]
        ]);
    });

    // Gamification
    $router->get('/leaderboard', function () {
        // Get top 50 users ordered by points
        $leaders = \Core\Database::query("
            SELECT id, first_name, last_name, avatar, total_points, current_level
            FROM users
            WHERE total_points > 0
            ORDER BY total_points DESC
            LIMIT 50
        ");

        // Get current user's rank
        $currentUserRank = null;
        if (isset($GLOBALS['user']['id'])) {
            $userId = $GLOBALS['user']['id'];
            $userPoints = $GLOBALS['user']['total_points'] ?? 0;

            // Count users with more points
            $rank = (int) \Core\Database::scalar("
                SELECT COUNT(*) + 1 FROM users WHERE total_points > ?
            ", [$userPoints]);

            $currentUserRank = [
                'rank' => $rank,
                'points' => $userPoints
            ];
        }

        View::render('gamification/leaderboard', [
            'title' => 'Leaderboard',
            'leaderboard' => ['users' => $leaders, 'current_user_rank' => $currentUserRank]
        ]);
    });

    $router->get('/achievements', function () {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Get all achievements with user progress
        $achievements = \Core\Database::query("
            SELECT a.*,
                   ua.current_value,
                   ua.is_completed,
                   ua.completed_at
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
            WHERE a.is_active = 1
            ORDER BY a.id
        ", [$userId]);

        // Get all badges with earned status
        $badges = \Core\Database::query("
            SELECT b.*,
                   ub.earned_at,
                   CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END as is_earned
            FROM badges b
            LEFT JOIN user_badges ub ON b.id = ub.badge_id AND ub.user_id = ?
            WHERE b.is_active = 1
            ORDER BY b.points_required
        ", [$userId]);

        View::render('gamification/achievements', [
            'title' => 'Achievements',
            'achievements' => $achievements,
            'badges' => $badges
        ]);
    });

    // AI Features
    $router->get('/ai-tutor', function () {
        $courseId = $_GET['course_id'] ?? null;

        View::render('ai/tutor', [
            'title' => 'AI Tutor',
            'courseId' => $courseId
        ]);
    });

    $router->post('/ai/chat', function () {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        $data = json_decode(file_get_contents('php://input'), true);
        $message = $data['message'] ?? '';
        $sessionId = $data['session_id'] ?? uniqid('ai_session_');
        $courseId = $data['course_id'] ?? null;

        // Store user message
        \Core\Database::execute("
            INSERT INTO ai_chat_history (user_id, session_id, role, content, course_id, created_at)
            VALUES (?, ?, 'user', ?, ?, NOW())
        ", [$userId, $sessionId, $message, $courseId]);

        // For now, return a placeholder response
        // In production, this would call an AI API (OpenAI, Claude, etc.)
        $aiResponse = "I'm your AI tutor. This feature requires integration with an AI service. Your message was: " . $message;

        // Store AI response
        \Core\Database::execute("
            INSERT INTO ai_chat_history (user_id, session_id, role, content, course_id, created_at)
            VALUES (?, ?, 'assistant', ?, ?, NOW())
        ", [$userId, $sessionId, $aiResponse, $courseId]);

        View::json([
            'success' => true,
            'data' => [
                'message' => $aiResponse,
                'session_id' => $sessionId
            ]
        ]);
    });

    $router->get('/career', function () {
        View::render('ai/career', [
            'title' => 'Career Assistant'
        ]);
    });

    // Notifications
    $router->get('/notifications', function () {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        $notifications = \Core\Database::query("
            SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ", [$userId]);

        // Mark as read
        \Core\Database::execute("
            UPDATE notifications SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ", [$userId]);

        View::render('notifications/index', [
            'title' => 'Notifications',
            'notifications' => $notifications
        ]);
    });
});

// ===========================================
// PREMIUM FEATURES (Goals & Accountability)
// These routes show an upgrade prompt to non-subscribers
// ===========================================

$router->group(['middleware' => 'auth'], function ($router) {
    // Goals - Show upgrade prompt for non-subscribers
    $router->get('/goals', function () {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Check if user is subscribed with goal tracker access
        if (!$auth->isSubscribed()) {
            // Fetch plans from database
            $plans = \Core\Database::query("
                SELECT id, name, slug, description, duration_months, price, currency,
                       is_popular, includes_goal_tracker, includes_accountability_partner
                FROM subscription_plans
                WHERE is_active = 1
                ORDER BY sort_order, price
            ");

            View::render('goals/upgrade', [
                'title' => 'Goal Tracker - Premium Feature',
                'feature' => 'Goal Tracker',
                'description' => 'Set meaningful goals, track your progress with milestones, and stay motivated with regular check-ins.',
                'benefits' => [
                    'Create unlimited learning goals',
                    'Break goals into actionable milestones',
                    'Track progress with visual dashboards',
                    'Get reminders to stay on track',
                    'Celebrate achievements with badges'
                ],
                'plans' => $plans
            ]);
            return;
        }

        $goals = \Core\Database::query("
            SELECT g.*,
                   (SELECT COUNT(*) FROM milestones WHERE goal_id = g.id) as milestone_count,
                   (SELECT COUNT(*) FROM milestones WHERE goal_id = g.id AND is_completed = 1) as completed_milestones
            FROM goals g
            WHERE g.user_id = ?
            ORDER BY g.status = 'active' DESC, g.created_at DESC
        ", [$userId]);

        View::render('goals/index', [
            'title' => 'My Goals',
            'goals' => $goals
        ]);
    });

    $router->get('/goals/create', function () {
        global $auth;
        if (!$auth->isSubscribed()) {
            redirect('/goals');
        }

        View::render('goals/create', [
            'title' => 'Create Goal'
        ]);
    });

    $router->post('/goals/create', function () {
        global $auth;

        if (!$auth->isSubscribed()) {
            flash('error', 'Please subscribe to access this feature');
            redirect('/subscription');
        }

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/goals/create');
        }

        $userId = $auth->user()['id'] ?? 0;

        \Core\Database::execute("
            INSERT INTO goals (user_id, title, description, category, target_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ", [
            $userId,
            $_POST['title'] ?? '',
            $_POST['description'] ?? '',
            $_POST['category'] ?? 'learning',
            !empty($_POST['target_date']) ? $_POST['target_date'] : null
        ]);

        flash('success', 'Goal created successfully!');
        redirect('/goals');
    });

    $router->get('/goals/{id}', function ($id) {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        if (!$auth->isSubscribed()) {
            redirect('/goals');
        }

        $goal = \Core\Database::queryOne("
            SELECT * FROM goals WHERE id = ? AND user_id = ?
        ", [$id, $userId]);

        if (!$goal) {
            View::error(404, 'Goal not found');
        }

        // Get milestones
        $goal['milestones'] = \Core\Database::query("
            SELECT * FROM milestones WHERE goal_id = ? ORDER BY sort_order
        ", [$id]);

        // Get recent checkins
        $goal['checkins'] = \Core\Database::query("
            SELECT * FROM goal_checkins WHERE goal_id = ? ORDER BY created_at DESC LIMIT 10
        ", [$id]);

        View::render('goals/show', [
            'title' => $goal['title'] ?? 'Goal Details',
            'goal' => $goal
        ]);
    });

    $router->post('/goals/{id}/checkin', function ($id) {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        if (!$auth->isSubscribed()) {
            View::json(['success' => false, 'message' => 'Subscription required']);
        }

        // Verify goal belongs to user
        $goal = \Core\Database::queryOne("SELECT id FROM goals WHERE id = ? AND user_id = ?", [$id, $userId]);
        if (!$goal) {
            View::json(['success' => false, 'message' => 'Goal not found']);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        \Core\Database::execute("
            INSERT INTO goal_checkins (goal_id, note, mood, progress_update, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ", [
            $id,
            $data['notes'] ?? $_POST['notes'] ?? '',
            $data['mood'] ?? null,
            $data['progress_update'] ?? null
        ]);

        // Update goal progress if provided
        if (!empty($data['progress_update'])) {
            \Core\Database::execute("
                UPDATE goals SET progress_percent = ?, updated_at = NOW() WHERE id = ?
            ", [$data['progress_update'], $id]);
        }

        View::json(['success' => true, 'message' => 'Check-in recorded']);
    });

    // Accountability - Show upgrade prompt for non-subscribers
    $router->get('/accountability', function () {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Check if user is subscribed with accountability partner access
        if (!$auth->isSubscribed()) {
            // Fetch plans from database
            $plans = \Core\Database::query("
                SELECT id, name, slug, description, duration_months, price, currency,
                       is_popular, includes_goal_tracker, includes_accountability_partner
                FROM subscription_plans
                WHERE is_active = 1
                ORDER BY sort_order, price
            ");

            View::render('goals/upgrade', [
                'title' => 'Accountability Partner - Premium Feature',
                'feature' => 'Accountability Partner',
                'description' => 'Stay motivated with a dedicated accountability partner who checks in on your progress and keeps you on track.',
                'benefits' => [
                    'Get matched with a dedicated partner',
                    'Direct messaging with your partner',
                    'Share your goals and progress',
                    'Receive encouragement and support',
                    'Stay accountable to your commitments'
                ],
                'plans' => $plans
            ]);
            return;
        }

        // Get accountability partner
        $assignment = \Core\Database::queryOne("
            SELECT aa.*, u.id as partner_user_id, u.first_name, u.last_name, u.avatar, u.email
            FROM accountability_assignments aa
            JOIN users u ON aa.partner_id = u.id
            WHERE aa.user_id = ? AND aa.status = 'active'
        ", [$userId]);

        $partner = $assignment ? [
            'id' => $assignment['partner_user_id'],
            'first_name' => $assignment['first_name'],
            'last_name' => $assignment['last_name'],
            'avatar' => $assignment['avatar'],
            'email' => $assignment['email']
        ] : null;

        // Get conversations
        $conversations = [];
        if ($partner) {
            $conversations = \Core\Database::query("
                SELECT c.*, m.content as last_message, m.created_at as last_message_at
                FROM conversations c
                LEFT JOIN messages m ON c.id = m.conversation_id
                WHERE (c.participant_1 = ? OR c.participant_2 = ?)
                ORDER BY c.last_message_at DESC
            ", [$userId, $userId]);
        }

        View::render('accountability/index', [
            'title' => 'Accountability Partner',
            'partner' => $partner,
            'conversations' => $conversations
        ]);
    });

    $router->get('/accountability/chat', function () {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        if (!$auth->isSubscribed()) {
            redirect('/accountability');
        }

        // Get accountability partner
        $assignment = \Core\Database::queryOne("
            SELECT aa.*, u.id as partner_user_id, u.first_name, u.last_name, u.avatar
            FROM accountability_assignments aa
            JOIN users u ON aa.partner_id = u.id
            WHERE aa.user_id = ? AND aa.status = 'active'
        ", [$userId]);

        $partner = $assignment ? [
            'id' => $assignment['partner_user_id'],
            'first_name' => $assignment['first_name'],
            'last_name' => $assignment['last_name'],
            'avatar' => $assignment['avatar']
        ] : null;

        $messages = [];
        if ($partner) {
            // Get or create conversation
            $conversation = \Core\Database::queryOne("
                SELECT * FROM conversations
                WHERE (participant_1 = ? AND participant_2 = ?) OR (participant_1 = ? AND participant_2 = ?)
            ", [$userId, $partner['id'], $partner['id'], $userId]);

            if ($conversation) {
                $messages = \Core\Database::query("
                    SELECT m.*, u.first_name, u.last_name, u.avatar
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.conversation_id = ?
                    ORDER BY m.created_at ASC
                ", [$conversation['id']]);
            }
        }

        View::render('accountability/chat', [
            'title' => 'Chat with Partner',
            'partner' => $partner,
            'messages' => $messages
        ]);
    });

    $router->post('/accountability/messages', function () {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        if (!$auth->isSubscribed()) {
            View::json(['success' => false, 'message' => 'Subscription required']);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $message = $data['message'] ?? '';

        // Get partner
        $assignment = \Core\Database::queryOne("
            SELECT partner_id FROM accountability_assignments WHERE user_id = ? AND status = 'active'
        ", [$userId]);

        if (!$assignment) {
            View::json(['success' => false, 'message' => 'No accountability partner assigned']);
        }

        $partnerId = $assignment['partner_id'];

        // Get or create conversation
        $conversation = \Core\Database::queryOne("
            SELECT id FROM conversations
            WHERE (participant_1 = ? AND participant_2 = ?) OR (participant_1 = ? AND participant_2 = ?)
        ", [$userId, $partnerId, $partnerId, $userId]);

        if (!$conversation) {
            \Core\Database::execute("
                INSERT INTO conversations (participant_1, participant_2, created_at)
                VALUES (?, ?, NOW())
            ", [$userId, $partnerId]);
            $conversationId = \Core\Database::lastInsertId();
        } else {
            $conversationId = $conversation['id'];
        }

        // Insert message
        \Core\Database::execute("
            INSERT INTO messages (conversation_id, sender_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ", [$conversationId, $userId, $message]);

        // Update conversation last_message_at
        \Core\Database::execute("
            UPDATE conversations SET last_message_at = NOW() WHERE id = ?
        ", [$conversationId]);

        View::json(['success' => true, 'message' => 'Message sent']);
    });
});

// ===========================================
// ADMIN ROUTES
// ===========================================

$router->group(['prefix' => '/admin', 'middleware' => 'admin'], function ($router) {
    // Dashboard
    $router->get('/', function () {
        // Get dashboard stats
        $stats = [
            'total_users' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM users"),
            'total_courses' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM courses"),
            'total_enrollments' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM enrollments"),
            'total_revenue' => (float) \Core\Database::scalar("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'"),
            'active_subscriptions' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'"),
            'new_users_today' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()"),
            'new_users_month' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")
        ];

        // Recent users
        $stats['recent_users'] = \Core\Database::query("
            SELECT id, first_name, last_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5
        ");

        // Recent payments
        $stats['recent_payments'] = \Core\Database::query("
            SELECT p.*, u.first_name, u.last_name, u.email
            FROM payments p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT 5
        ");

        View::render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $stats
        ], 'admin');
    });

    // Users
    $router->get('/users', function () {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $search = $_GET['search'] ?? '';

        $where = "1=1";
        $params = [];

        if ($search) {
            $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $total = (int) \Core\Database::scalar("SELECT COUNT(*) FROM users WHERE {$where}", $params);

        $users = \Core\Database::query("
            SELECT u.*,
                   (SELECT COUNT(*) FROM enrollments WHERE user_id = u.id) as enrollment_count,
                   (SELECT COUNT(*) FROM subscriptions WHERE user_id = u.id AND status = 'active') as active_subscription
            FROM users u
            WHERE {$where}
            ORDER BY u.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        View::render('admin/users/index', [
            'title' => 'Manage Users',
            'users' => $users,
            'meta' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'total' => $total
            ],
            'search' => $search
        ], 'admin');
    });

    $router->get('/users/{id}', function ($id) {
        $user = \Core\Database::queryOne("
            SELECT u.*,
                   (SELECT COUNT(*) FROM enrollments WHERE user_id = u.id) as enrollment_count,
                   (SELECT COUNT(*) FROM enrollments WHERE user_id = u.id AND status = 'completed') as courses_completed,
                   (SELECT sp.name FROM subscriptions s JOIN subscription_plans sp ON s.plan_id = sp.id WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as subscription_plan
            FROM users u
            WHERE u.id = ?
        ", [$id]);

        if (!$user) {
            View::json(['success' => false, 'message' => 'User not found'], 404);
        }

        View::json(['success' => true, 'data' => $user]);
    });

    $router->get('/users/{id}/edit', function ($id) {
        $user = \Core\Database::queryOne("
            SELECT u.*,
                   (SELECT COUNT(*) FROM enrollments WHERE user_id = u.id) as enrollment_count,
                   (SELECT COUNT(*) FROM enrollments WHERE user_id = u.id AND status = 'completed') as courses_completed,
                   (SELECT s.id FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as active_subscription_id,
                   (SELECT sp.name FROM subscriptions s JOIN subscription_plans sp ON s.plan_id = sp.id WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as subscription_plan
            FROM users u
            WHERE u.id = ?
        ", [$id]);

        if (!$user) {
            flash('error', 'User not found');
            redirect('/admin/users');
        }

        $plans = \Core\Database::query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order, price");

        View::render('admin/users/edit', [
            'title' => 'Edit User',
            'user' => $user,
            'plans' => $plans
        ], 'admin');
    });

    $router->post('/users/{id}/edit', function ($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/users/' . $id . '/edit');
        }

        $user = \Core\Database::queryOne("SELECT id FROM users WHERE id = ?", [$id]);
        if (!$user) {
            flash('error', 'User not found');
            redirect('/admin/users');
        }

        \Core\Database::execute("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                role = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [
            $_POST['first_name'] ?? '',
            $_POST['last_name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['phone'] ?? '',
            $_POST['role'] ?? 'user',
            $_POST['status'] ?? 'active',
            $id
        ]);

        flash('success', 'User updated successfully');
        redirect('/admin/users');
    });

    $router->post('/users/{id}/role', function ($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $role = $data['role'] ?? 'user';

        // Validate role
        if (!in_array($role, ['user', 'admin', 'partner'])) {
            View::json(['success' => false, 'message' => 'Invalid role']);
        }

        \Core\Database::execute("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?", [$role, $id]);

        View::json(['success' => true, 'message' => 'Role updated successfully']);
    });

    $router->post('/users/{id}/delete', function ($id) {
        global $auth;
        $currentUser = $auth->user();

        // Prevent self-deletion
        if ($currentUser['id'] == $id) {
            View::json(['success' => false, 'message' => 'Cannot delete your own account']);
        }

        $user = \Core\Database::queryOne("SELECT id, role FROM users WHERE id = ?", [$id]);
        if (!$user) {
            View::json(['success' => false, 'message' => 'User not found']);
        }

        // Prevent deleting other admins (optional safety)
        if ($user['role'] === 'admin') {
            View::json(['success' => false, 'message' => 'Cannot delete admin users']);
        }

        \Core\Database::execute("DELETE FROM users WHERE id = ?", [$id]);

        View::json(['success' => true, 'message' => 'User deleted successfully']);
    });

    $router->post('/users', function () {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password'])) {
            View::json(['success' => false, 'message' => 'All fields are required']);
        }

        // Check if email exists
        $existing = \Core\Database::queryOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
        if ($existing) {
            View::json(['success' => false, 'message' => 'Email already exists']);
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        \Core\Database::execute("
            INSERT INTO users (first_name, last_name, email, password, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ", [
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $hashedPassword,
            $data['role'] ?? 'user'
        ]);

        View::json(['success' => true, 'message' => 'User created successfully']);
    });

    // Courses
    $router->get('/courses', function () {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $level = $_GET['level'] ?? '';
        $status = $_GET['status'] ?? '';

        $where = "1=1";
        $params = [];

        if ($search) {
            $where .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($category) {
            $where .= " AND c.category_id = ?";
            $params[] = $category;
        }
        if ($level) {
            $where .= " AND c.level = ?";
            $params[] = $level;
        }
        if ($status === 'published') {
            $where .= " AND c.is_published = 1";
        } elseif ($status === 'draft') {
            $where .= " AND c.is_published = 0";
        }

        $total = (int) \Core\Database::scalar("SELECT COUNT(*) FROM courses c WHERE {$where}", $params);

        // Simplified query - just get courses
        $courses = \Core\Database::query("
            SELECT c.*,
                   COALESCE(cat.name, 'Uncategorized') as category_name,
                   0 as lesson_count,
                   0 as enrollment_count,
                   0 as rating
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE {$where}
            ORDER BY c.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        $categories = \Core\Database::query("SELECT * FROM categories ORDER BY name");

        View::render('admin/courses/index', [
            'title' => 'Manage Courses',
            'courses' => $courses,
            'categories' => $categories,
            'totalCourses' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage)
        ], 'admin');
    });

    $router->get('/courses/create', function () {
        $categories = \Core\Database::query("SELECT * FROM categories ORDER BY name");

        View::render('admin/courses/form', [
            'title' => 'Create Course',
            'course' => null,
            'categories' => $categories,
            'instructors' => []
        ], 'admin');
    });

    $router->post('/courses/create', function () {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/courses/create');
        }

        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            flash('error', 'Title is required');
            $_SESSION['old_input'] = $_POST;
            redirect('/admin/courses/create');
        }

        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $existingSlug = \Core\Database::scalar("SELECT COUNT(*) FROM courses WHERE slug = ?", [$slug]);
        if ($existingSlug > 0) {
            $slug .= '-' . time();
        }

        // Convert status to is_published
        $isPublished = ($_POST['status'] ?? 'draft') === 'published' ? 1 : 0;

        // is_free is the inverse of is_premium (premium = NOT free)
        $isFree = isset($_POST['is_premium']) ? 0 : 1;

        try {
            \Core\Database::execute("
                INSERT INTO courses (title, slug, description, category_id, level, is_published, is_free, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $title,
                $slug,
                $_POST['description'] ?? '',
                !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                $_POST['level'] ?? 'beginner',
                $isPublished,
                $isFree
            ]);

            flash('success', 'Course created successfully');
            redirect('/admin/courses');
        } catch (\Exception $e) {
            error_log("Course creation error: " . $e->getMessage());
            flash('error', 'Failed to create course: ' . $e->getMessage());
            $_SESSION['old_input'] = $_POST;
            redirect('/admin/courses/create');
        }
    });

    $router->get('/courses/{id}/edit', function ($id) {
        $course = \Core\Database::queryOne("
            SELECT c.*, cat.name as category_name
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE c.id = ?
        ", [$id]);

        if (!$course) {
            View::error(404, 'Course not found');
        }

        // Get lessons for this course
        $course['lessons'] = \Core\Database::query("
            SELECT * FROM lessons WHERE course_id = ? ORDER BY sort_order, id
        ", [$id]);

        $categories = \Core\Database::query("SELECT * FROM categories ORDER BY name");

        View::render('admin/courses/form', [
            'title' => 'Edit Course',
            'course' => $course,
            'categories' => $categories,
            'instructors' => []
        ], 'admin');
    });

    $router->post('/courses/{id}/edit', function ($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/courses/' . $id . '/edit');
        }

        $course = \Core\Database::queryOne("SELECT id FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            flash('error', 'Course not found');
            redirect('/admin/courses');
        }

        // Convert status to is_published (0 or 1)
        $isPublished = ($_POST['status'] ?? 'draft') === 'published' ? 1 : 0;
        // is_free is the inverse of is_premium (premium = NOT free)
        $isFree = isset($_POST['is_premium']) ? 0 : 1;

        try {
            $rowsAffected = \Core\Database::execute("
                UPDATE courses SET
                    title = ?,
                    description = ?,
                    category_id = ?,
                    level = ?,
                    is_published = ?,
                    is_free = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                $_POST['level'] ?? 'beginner',
                $isPublished,
                $isFree,
                $id
            ]);

            if ($rowsAffected > 0) {
                flash('success', 'Course updated successfully');
            } else {
                flash('warning', 'No changes detected');
            }
            redirect('/admin/courses');
        } catch (\Exception $e) {
            error_log("Course update error: " . $e->getMessage());
            flash('error', 'Failed to update course: ' . $e->getMessage());
            redirect('/admin/courses/' . $id . '/edit');
        }
    });

    // Delete course (POST-based for JS compatibility)
    $router->post('/courses/{id}/delete', function ($id) {
        header('Content-Type: application/json');
        try {
            // Check if course exists first
            $course = \Core\Database::queryOne("SELECT id FROM courses WHERE id = ?", [$id]);
            if (!$course) {
                echo json_encode(['success' => false, 'message' => 'Course not found']);
                exit;
            }

            // Delete related records (ignore errors if tables/columns don't exist)
            try {
                \Core\Database::execute("DELETE FROM lesson_progress WHERE lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)", [$id]);
            } catch (\Exception $e) { /* ignore */ }

            try {
                \Core\Database::execute("DELETE FROM lessons WHERE course_id = ?", [$id]);
            } catch (\Exception $e) { /* ignore */ }

            try {
                \Core\Database::execute("DELETE FROM enrollments WHERE course_id = ?", [$id]);
            } catch (\Exception $e) { /* ignore */ }

            // Delete the course itself
            \Core\Database::execute("DELETE FROM courses WHERE id = ?", [$id]);

            echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
        } catch (\Exception $e) {
            error_log("Course delete error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to delete: ' . $e->getMessage()]);
        }
        exit;
    });

    // Payments
    $router->get('/payments', function () {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $total = (int) \Core\Database::scalar("SELECT COUNT(*) FROM payments");

        $payments = \Core\Database::query("
            SELECT p.*, u.first_name, u.last_name, u.email,
                   sp.name as plan_name
            FROM payments p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN subscriptions s ON p.subscription_id = s.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            ORDER BY p.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");

        View::render('admin/payments/index', [
            'title' => 'Payments',
            'payments' => $payments,
            'meta' => [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'total' => $total
            ]
        ], 'admin');
    });

    $router->post('/payments/{id}/approve', function ($id) {
        // Get payment
        $payment = \Core\Database::queryOne("SELECT * FROM payments WHERE id = ?", [$id]);
        if (!$payment) {
            View::json(['success' => false, 'message' => 'Payment not found']);
        }

        // Update payment status
        \Core\Database::execute("
            UPDATE payments SET status = 'completed', paid_at = NOW() WHERE id = ?
        ", [$id]);

        // Activate subscription if exists
        if ($payment['subscription_id']) {
            $subscription = \Core\Database::queryOne("SELECT * FROM subscriptions WHERE id = ?", [$payment['subscription_id']]);
            if ($subscription) {
                $plan = \Core\Database::queryOne("SELECT * FROM subscription_plans WHERE id = ?", [$subscription['plan_id']]);
                $durationDays = $plan['duration_days'] ?? 30;

                \Core\Database::execute("
                    UPDATE subscriptions SET
                        status = 'active',
                        start_date = CURDATE(),
                        end_date = DATE_ADD(CURDATE(), INTERVAL ? DAY),
                        updated_at = NOW()
                    WHERE id = ?
                ", [$durationDays, $payment['subscription_id']]);
            }
        }

        View::json(['success' => true, 'message' => 'Payment approved']);
    });

    // Subscriptions
    $router->get('/subscriptions', function () {
        $subscriptions = \Core\Database::query("
            SELECT s.*, u.first_name, u.last_name, u.email, sp.name as plan_name
            FROM subscriptions s
            JOIN users u ON s.user_id = u.id
            JOIN subscription_plans sp ON s.plan_id = sp.id
            ORDER BY s.created_at DESC
            LIMIT 50
        ");

        $plans = \Core\Database::query("SELECT * FROM subscription_plans ORDER BY sort_order, price");
        foreach ($plans as &$plan) {
            $plan['features'] = json_decode($plan['features'] ?? '[]', true) ?: [];
        }

        View::render('admin/subscriptions/index', [
            'title' => 'Subscriptions',
            'subscriptions' => $subscriptions,
            'plans' => $plans
        ], 'admin');
    });

    // Subscription Plans CRUD
    $router->get('/subscriptions/plans/create', function () {
        View::render('admin/subscriptions/edit-plan', [
            'title' => 'Create Plan',
            'plan' => null
        ], 'admin');
    });

    $router->post('/subscriptions/plans/store', function () {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/subscriptions/plans/create');
        }

        $features = array_values(array_filter($_POST['features'] ?? [], fn($f) => !empty(trim($f))));
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'] ?? '')));

        \Core\Database::execute("
            INSERT INTO subscription_plans (name, slug, description, price, original_price, duration_days, duration_months, features, is_active, is_popular, includes_goal_tracker, includes_accountability_partner, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            $_POST['name'] ?? '',
            $slug,
            $_POST['description'] ?? '',
            $_POST['price'] ?? 0,
            !empty($_POST['original_price']) ? $_POST['original_price'] : null,
            $_POST['duration_days'] ?? 30,
            $_POST['duration_months'] ?? 1,
            json_encode($features),
            isset($_POST['is_active']) ? 1 : 0,
            isset($_POST['is_popular']) ? 1 : 0,
            isset($_POST['includes_goal_tracker']) ? 1 : 0,
            isset($_POST['includes_accountability_partner']) ? 1 : 0
        ]);

        flash('success', 'Plan created successfully');
        redirect('/admin/subscriptions');
    });

    $router->get('/subscriptions/plans/{id}/edit', function ($id) {
        $plan = \Core\Database::queryOne("SELECT * FROM subscription_plans WHERE id = ?", [$id]);

        if (!$plan) {
            flash('error', 'Plan not found');
            redirect('/admin/subscriptions');
        }

        $plan['features'] = json_decode($plan['features'] ?? '[]', true) ?: [];

        View::render('admin/subscriptions/edit-plan', [
            'title' => 'Edit Plan',
            'plan' => $plan
        ], 'admin');
    });

    $router->post('/subscriptions/plans/{id}/update', function ($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/subscriptions/plans/' . $id . '/edit');
        }

        $features = array_values(array_filter($_POST['features'] ?? [], fn($f) => !empty(trim($f))));

        \Core\Database::execute("
            UPDATE subscription_plans SET
                name = ?,
                description = ?,
                price = ?,
                original_price = ?,
                duration_days = ?,
                duration_months = ?,
                features = ?,
                is_active = ?,
                is_popular = ?,
                includes_goal_tracker = ?,
                includes_accountability_partner = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [
            $_POST['name'] ?? '',
            $_POST['description'] ?? '',
            $_POST['price'] ?? 0,
            !empty($_POST['original_price']) ? $_POST['original_price'] : null,
            $_POST['duration_days'] ?? 30,
            $_POST['duration_months'] ?? 1,
            json_encode($features),
            isset($_POST['is_active']) ? 1 : 0,
            isset($_POST['is_popular']) ? 1 : 0,
            isset($_POST['includes_goal_tracker']) ? 1 : 0,
            isset($_POST['includes_accountability_partner']) ? 1 : 0,
            $id
        ]);

        flash('success', 'Plan updated successfully');
        redirect('/admin/subscriptions');
    });

    $router->delete('/subscriptions/plans/{id}/delete', function ($id) {
        // Check if plan has active subscriptions
        $activeCount = (int) \Core\Database::scalar("SELECT COUNT(*) FROM subscriptions WHERE plan_id = ? AND status = 'active'", [$id]);

        if ($activeCount > 0) {
            View::json(['success' => false, 'message' => 'Cannot delete plan with active subscriptions']);
        }

        \Core\Database::execute("DELETE FROM subscription_plans WHERE id = ?", [$id]);
        View::json(['success' => true, 'message' => 'Plan deleted successfully']);
    });

    // Reports
    $router->get('/reports', function () {
        // User Report
        $userReport = [
            'total' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM users"),
            'active' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM users WHERE status = 'active'"),
            'new_this_month' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'by_role' => \Core\Database::query("SELECT role, COUNT(*) as count FROM users GROUP BY role")
        ];

        // Revenue Report
        $revenueReport = [
            'total' => (float) \Core\Database::scalar("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'"),
            'this_month' => (float) \Core\Database::scalar("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'by_method' => \Core\Database::query("SELECT payment_method, COUNT(*) as count, SUM(amount) as total FROM payments WHERE status = 'completed' GROUP BY payment_method")
        ];

        // Course Report
        $courseReport = [
            'total' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM courses"),
            'published' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM courses WHERE is_published = 1"),
            'total_enrollments' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM enrollments"),
            'completions' => (int) \Core\Database::scalar("SELECT COUNT(*) FROM enrollments WHERE status = 'completed'"),
            'top_courses' => \Core\Database::query("
                SELECT c.id, c.title, COUNT(e.id) as enrollment_count
                FROM courses c
                LEFT JOIN enrollments e ON c.id = e.course_id
                GROUP BY c.id
                ORDER BY enrollment_count DESC
                LIMIT 5
            ")
        ];

        View::render('admin/reports', [
            'title' => 'Reports & Analytics',
            'userReport' => $userReport,
            'revenueReport' => $revenueReport,
            'courseReport' => $courseReport
        ], 'admin');
    });

    // Settings
    $router->get('/settings', function () {
        $settings = \Core\Database::query("SELECT * FROM settings ORDER BY id");

        // Convert to key-value array
        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting['key']] = $setting['value'];
        }

        $paymentMethods = \Core\Database::query("SELECT * FROM payment_methods ORDER BY sort_order");

        View::render('admin/settings/index', [
            'title' => 'Platform Settings',
            'settings' => $settingsArray,
            'paymentMethods' => $paymentMethods
        ], 'admin');
    });

    $router->post('/settings', function () {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/settings');
        }

        // Update each setting
        foreach ($_POST as $key => $value) {
            if ($key === '_token') continue;

            // Check if setting exists
            $exists = \Core\Database::scalar("SELECT COUNT(*) FROM settings WHERE `key` = ?", [$key]);

            if ($exists) {
                \Core\Database::execute("UPDATE settings SET value = ?, updated_at = NOW() WHERE `key` = ?", [$value, $key]);
            } else {
                \Core\Database::execute("INSERT INTO settings (`key`, value, type, updated_at) VALUES (?, ?, 'string', NOW())", [$key, $value]);
            }
        }

        flash('success', 'Settings updated successfully');
        redirect('/admin/settings');
    });

    $router->post('/payment-methods/{id}', function ($id) {
        $data = json_decode(file_get_contents('php://input'), true);

        \Core\Database::execute("
            UPDATE payment_methods SET
                is_active = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [
            isset($data['is_active']) ? 1 : 0,
            $id
        ]);

        View::json(['success' => true, 'message' => 'Payment method updated']);
    });

    // AI Courses - Direct Database Access (no API)
    $router->get('/ai-courses', function () {

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $level = $_GET['level'] ?? '';

        $where = "1=1";
        $params = [];

        if ($search) {
            $where .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($status === 'published') {
            $where .= " AND is_published = 1";
        } elseif ($status === 'draft') {
            $where .= " AND is_published = 0";
        }
        if ($level) {
            $where .= " AND level = ?";
            $params[] = $level;
        }

        // Get total count
        $total = (int) \Core\Database::scalar("SELECT COUNT(*) FROM ai_courses WHERE {$where}", $params);

        // Get courses with counts (LIMIT/OFFSET as integers, safe to interpolate)
        $courses = \Core\Database::query("
            SELECT c.*,
                   (SELECT COUNT(*) FROM ai_modules WHERE course_id = c.id) as module_count,
                   (SELECT COUNT(*) FROM ai_lessons l JOIN ai_modules m ON l.module_id = m.id WHERE m.course_id = c.id) as lesson_count,
                   (SELECT COUNT(*) FROM ai_enrollments WHERE course_id = c.id) as enrollment_count
            FROM ai_courses c
            WHERE {$where}
            ORDER BY c.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $params);

        View::render('admin/ai-courses/index', [
            'title' => 'AI Courses',
            'courses' => $courses,
            'totalCourses' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage)
        ], 'admin');
    });

    $router->get('/ai-courses/create', function () {
        $categories = \Core\Database::query("SELECT * FROM categories ORDER BY name");

        View::render('admin/ai-courses/form', [
            'title' => 'Create AI Course',
            'course' => null,
            'categories' => $categories
        ], 'admin');
    });

    $router->post('/ai-courses/store', function () {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/ai-courses/create');
        }

        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            flash('error', 'Title is required');
            $_SESSION['old_input'] = $_POST;
            redirect('/admin/ai-courses/create');
        }

        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $existingSlug = \Core\Database::scalar("SELECT COUNT(*) FROM ai_courses WHERE slug = ?", [$slug]);
        if ($existingSlug > 0) {
            $slug .= '-' . time();
        }

        // Determine if published based on status field
        $isPublished = ($_POST['status'] ?? 'draft') === 'published' ? 1 : 0;
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

        try {
            $pdo = \Core\Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO ai_courses (title, slug, description, category_id, level, is_published, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $title,
                $slug,
                $_POST['description'] ?? '',
                $categoryId,
                $_POST['level'] ?? 'beginner',
                $isPublished
            ]);

            $courseId = $pdo->lastInsertId();

            if ($courseId) {
                flash('success', 'AI Course created successfully');
                redirect('/admin/ai-courses/' . $courseId . '/curriculum');
            } else {
                flash('error', 'Failed to create AI course - no ID returned');
                $_SESSION['old_input'] = $_POST;
                redirect('/admin/ai-courses/create');
            }
        } catch (\PDOException $e) {
            error_log("AI Course creation error: " . $e->getMessage());
            flash('error', 'Database error: ' . $e->getMessage());
            $_SESSION['old_input'] = $_POST;
            redirect('/admin/ai-courses/create');
        }
    });

    $router->get('/ai-courses/{id}/edit', function ($id) {
        $course = \Core\Database::queryOne("SELECT * FROM ai_courses WHERE id = ?", [$id]);
        if (!$course) {
            View::error(404, 'AI Course not found');
        }

        $categories = \Core\Database::query("SELECT * FROM categories ORDER BY name");

        View::render('admin/ai-courses/form', [
            'title' => 'Edit AI Course',
            'course' => $course,
            'categories' => $categories
        ], 'admin');
    });

    $router->post('/ai-courses/{id}/update', function ($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/ai-courses/' . $id . '/edit');
        }

        $course = \Core\Database::queryOne("SELECT id FROM ai_courses WHERE id = ?", [$id]);
        if (!$course) {
            flash('error', 'Course not found');
            redirect('/admin/ai-courses');
        }

        // Determine published status from 'status' field
        $isPublished = ($_POST['status'] ?? 'draft') === 'published' ? 1 : 0;

        \Core\Database::execute("
            UPDATE ai_courses SET
                title = ?,
                description = ?,
                category_id = ?,
                level = ?,
                is_published = ?
            WHERE id = ?
        ", [
            $_POST['title'] ?? '',
            $_POST['description'] ?? '',
            !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            $_POST['level'] ?? 'beginner',
            $isPublished,
            $id
        ]);

        flash('success', 'AI Course updated successfully');
        redirect('/admin/ai-courses/' . $id . '/edit');
    });

    $router->get('/ai-courses/{id}/curriculum', function ($id) {
        $course = \Core\Database::queryOne("SELECT * FROM ai_courses WHERE id = ?", [$id]);
        if (!$course) {
            View::error(404, 'AI Course not found');
        }

        // Get modules with lessons
        $modules = \Core\Database::query("
            SELECT * FROM ai_modules WHERE course_id = ? ORDER BY sort_order
        ", [$id]);

        foreach ($modules as &$module) {
            $module['lessons'] = \Core\Database::query("
                SELECT * FROM ai_lessons WHERE module_id = ? ORDER BY sort_order
            ", [$module['id']]);
        }

        $course['modules'] = $modules;

        View::render('admin/ai-courses/curriculum', [
            'title' => 'Manage Curriculum',
            'course' => $course
        ], 'admin');
    });

    $router->put('/ai-courses/{id}/curriculum', function ($id) {
        $data = json_decode(file_get_contents('php://input'), true);

        foreach ($data['modules'] ?? [] as $moduleIndex => $module) {
            if (!empty($module['id'])) {
                // Update existing module
                \Core\Database::execute("
                    UPDATE ai_modules SET title = ?, description = ?, sort_order = ? WHERE id = ?
                ", [$module['title'], $module['description'] ?? '', $moduleIndex + 1, $module['id']]);
                $moduleId = $module['id'];
            } else {
                // Create new module
                \Core\Database::execute("
                    INSERT INTO ai_modules (course_id, title, description, sort_order, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ", [$id, $module['title'], $module['description'] ?? '', $moduleIndex + 1]);
                $moduleId = \Core\Database::lastInsertId();
            }

            // Handle lessons
            foreach ($module['lessons'] ?? [] as $lessonIndex => $lesson) {
                if (!empty($lesson['id'])) {
                    \Core\Database::execute("
                        UPDATE ai_lessons SET title = ?, description = ?, teaching_notes = ?, estimated_minutes = ?, sort_order = ?
                        WHERE id = ?
                    ", [
                        $lesson['title'],
                        $lesson['description'] ?? '',
                        $lesson['teaching_notes'] ?? '',
                        $lesson['estimated_minutes'] ?? 15,
                        $lessonIndex + 1,
                        $lesson['id']
                    ]);
                } else {
                    \Core\Database::execute("
                        INSERT INTO ai_lessons (module_id, title, description, teaching_notes, estimated_minutes, sort_order, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ", [
                        $moduleId,
                        $lesson['title'],
                        $lesson['description'] ?? '',
                        $lesson['teaching_notes'] ?? '',
                        $lesson['estimated_minutes'] ?? 15,
                        $lessonIndex + 1
                    ]);
                }
            }
        }

        // Update lesson count
        \Core\Database::execute("
            UPDATE ai_courses SET total_lessons = (
                SELECT COUNT(*) FROM ai_lessons l
                JOIN ai_modules m ON l.module_id = m.id
                WHERE m.course_id = ?
            ) WHERE id = ?
        ", [$id, $id]);

        View::json(['success' => true, 'message' => 'Curriculum saved']);
    });

    $router->delete('/ai-courses/{id}', function ($id) {
        try {
            $pdo = \Core\Database::getConnection();

            $stmt = $pdo->prepare("SELECT id FROM ai_courses WHERE id = ?");
            $stmt->execute([$id]);
            $course = $stmt->fetch();

            if (!$course) {
                View::json(['success' => false, 'message' => 'Course not found'], 404);
                return;
            }

            // Delete cascade - modules and lessons will be deleted automatically via FK
            $stmt = $pdo->prepare("DELETE FROM ai_courses WHERE id = ?");
            $stmt->execute([$id]);

            View::json(['success' => true, 'message' => 'AI course deleted successfully']);
        } catch (\PDOException $e) {
            error_log("AI Course delete error: " . $e->getMessage());
            View::json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    });

    // POST-based delete (fallback for servers that don't support DELETE)
    $router->post('/ai-courses/{id}/delete', function ($id) {
        try {
            $pdo = \Core\Database::getConnection();

            $stmt = $pdo->prepare("SELECT id FROM ai_courses WHERE id = ?");
            $stmt->execute([$id]);
            $course = $stmt->fetch();

            if (!$course) {
                View::json(['success' => false, 'message' => 'Course not found'], 404);
                return;
            }

            $stmt = $pdo->prepare("DELETE FROM ai_courses WHERE id = ?");
            $stmt->execute([$id]);

            View::json(['success' => true, 'message' => 'AI course deleted successfully']);
        } catch (\PDOException $e) {
            error_log("AI Course delete error: " . $e->getMessage());
            View::json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    });
});
