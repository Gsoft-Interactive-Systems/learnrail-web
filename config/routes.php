<?php
/**
 * Application Routes
 * Learnrail Web App
 */

use Core\View;

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
        global $api, $auth;

        $user = $auth->user();

        // Fetch dashboard data
        $enrollments = $api->get('/enrollments', ['limit' => 3, 'status' => 'enrolled']);
        $featuredCourses = $api->get('/courses', ['featured' => true, 'limit' => 6]);

        View::render('home/index', [
            'title' => 'Dashboard',
            'enrolledCourses' => $enrollments['data']['data'] ?? $enrollments['data'] ?? [],
            'featuredCourses' => $featuredCourses['data']['data'] ?? $featuredCourses['data'] ?? []
        ]);
    });

    // Courses
    $router->get('/courses', function () {
        global $api;

        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $level = $_GET['level'] ?? '';

        $params = ['page' => $page, 'per_page' => 12];
        if ($search) $params['search'] = $search;
        if ($category) $params['category'] = $category;
        if ($level) $params['level'] = $level;

        $courses = $api->get('/courses', $params);
        $categories = $api->get('/categories');

        View::render('courses/index', [
            'title' => 'Courses',
            'courses' => $courses['data']['data'] ?? $courses['data'] ?? [],
            'meta' => $courses['data']['meta'] ?? [],
            'categories' => $categories['data'] ?? [],
            'search' => $search,
            'selectedCategory' => $category,
            'selectedLevel' => $level
        ]);
    });

    $router->get('/courses/{id}', function ($id) {
        global $api;

        $course = $api->get('/courses/' . $id);

        if (!$course['success']) {
            View::error(404, 'Course not found');
        }

        View::render('courses/show', [
            'title' => $course['data']['title'] ?? 'Course Details',
            'course' => $course['data']
        ]);
    });

    $router->post('/courses/{id}/enroll', function ($id) {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/courses/' . $id);
        }

        $result = $api->post('/courses/' . $id . '/enroll');

        if ($result['success']) {
            flash('success', 'Successfully enrolled in course!');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to enroll');
        }

        redirect('/courses/' . $id);
    });

    $router->get('/courses/{courseId}/lessons/{lessonId}', function ($courseId, $lessonId) {
        global $api;

        $lesson = $api->get('/lessons/' . $lessonId);
        $course = $api->get('/courses/' . $courseId);

        if (!$lesson['success']) {
            View::error(404, 'Lesson not found');
        }

        View::render('courses/lesson', [
            'title' => $lesson['data']['title'] ?? 'Lesson',
            'lesson' => $lesson['data'],
            'course' => $course['data'] ?? [],
            'courseId' => $courseId
        ]);
    });

    $router->post('/lessons/{id}/complete', function ($id) {
        global $api;

        $result = $api->post('/lessons/' . $id . '/complete');
        View::json($result);
    });

    // Profile
    $router->get('/profile', function () {
        global $api;

        $profile = $api->get('/profile');

        View::render('profile/index', [
            'title' => 'Profile',
            'profile' => $profile['data'] ?? []
        ]);
    });

    $router->get('/profile/edit', function () {
        global $auth;

        View::render('profile/edit', [
            'title' => 'Edit Profile'
        ]);
    });

    $router->post('/profile/edit', function () {
        global $api, $auth;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/profile/edit');
        }

        $data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'phone' => $_POST['phone'] ?? ''
        ];

        $result = $api->put('/profile', $data);

        if ($result['success']) {
            $auth->refreshUser();
            flash('success', 'Profile updated successfully');
            redirect('/profile');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to update profile');
            redirect('/profile/edit');
        }
    });

    $router->get('/settings', function () {
        View::render('profile/settings', [
            'title' => 'Settings'
        ]);
    });

    // Subscription
    $router->get('/subscription', function () {
        global $api;

        $plans = $api->get('/subscription-plans');

        View::render('subscription/plans', [
            'title' => 'Subscription Plans',
            'plans' => $plans['data'] ?? []
        ]);
    });

    $router->get('/subscription/payment/{planId}', function ($planId) {
        global $api;

        $plans = $api->get('/subscription-plans');
        $selectedPlan = null;

        foreach ($plans['data'] ?? [] as $plan) {
            if ($plan['id'] == $planId) {
                $selectedPlan = $plan;
                break;
            }
        }

        if (!$selectedPlan) {
            flash('error', 'Invalid plan selected');
            redirect('/subscription');
        }

        $paymentMethods = $api->get('/payment-methods');

        View::render('subscription/payment', [
            'title' => 'Complete Payment',
            'plan' => $selectedPlan,
            'paymentMethods' => $paymentMethods['data'] ?? []
        ]);
    });

    $router->post('/subscription/payment', function () {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            View::json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $result = $api->post('/payments/initialize', [
            'subscription_plan_id' => $_POST['plan_id'] ?? '',
            'payment_method' => $_POST['payment_method'] ?? 'paystack'
        ]);

        View::json($result);
    });

    // Gamification
    $router->get('/leaderboard', function () {
        global $api;

        $leaderboard = $api->get('/leaderboard');

        View::render('gamification/leaderboard', [
            'title' => 'Leaderboard',
            'leaderboard' => $leaderboard['data'] ?? []
        ]);
    });

    $router->get('/achievements', function () {
        global $api;

        $achievements = $api->get('/achievements');
        $badges = $api->get('/badges');

        View::render('gamification/achievements', [
            'title' => 'Achievements',
            'achievements' => $achievements['data'] ?? [],
            'badges' => $badges['data'] ?? []
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
        global $api;

        $data = json_decode(file_get_contents('php://input'), true);

        $result = $api->post('/ai/chat', [
            'message' => $data['message'] ?? '',
            'session_id' => $data['session_id'] ?? null,
            'course_id' => $data['course_id'] ?? null
        ]);

        View::json($result);
    });

    $router->get('/career', function () {
        View::render('ai/career', [
            'title' => 'Career Assistant'
        ]);
    });

    // Notifications
    $router->get('/notifications', function () {
        global $api;

        $notifications = $api->get('/notifications');

        View::render('notifications/index', [
            'title' => 'Notifications',
            'notifications' => $notifications['data'] ?? []
        ]);
    });
});

// ===========================================
// SUBSCRIBER-ONLY ROUTES
// ===========================================

$router->group(['middleware' => 'subscribed'], function ($router) {
    // Goals
    $router->get('/goals', function () {
        global $api;

        $goals = $api->get('/goals');

        View::render('goals/index', [
            'title' => 'My Goals',
            'goals' => $goals['data']['data'] ?? $goals['data'] ?? []
        ]);
    });

    $router->get('/goals/create', function () {
        View::render('goals/create', [
            'title' => 'Create Goal'
        ]);
    });

    $router->post('/goals/create', function () {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/goals/create');
        }

        $result = $api->post('/goals', [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'target_date' => $_POST['target_date'] ?? '',
            'category' => $_POST['category'] ?? 'learning'
        ]);

        if ($result['success']) {
            flash('success', 'Goal created successfully!');
            redirect('/goals');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to create goal');
            $_SESSION['old_input'] = $_POST;
            redirect('/goals/create');
        }
    });

    $router->get('/goals/{id}', function ($id) {
        global $api;

        $goal = $api->get('/goals/' . $id);

        if (!$goal['success']) {
            View::error(404, 'Goal not found');
        }

        View::render('goals/show', [
            'title' => $goal['data']['title'] ?? 'Goal Details',
            'goal' => $goal['data']
        ]);
    });

    $router->post('/goals/{id}/checkin', function ($id) {
        global $api;

        $result = $api->post('/goals/' . $id . '/checkin', [
            'notes' => $_POST['notes'] ?? ''
        ]);

        View::json($result);
    });

    // Accountability
    $router->get('/accountability', function () {
        global $api;

        $partner = $api->get('/accountability/partner');
        $conversations = $api->get('/accountability/conversations');

        View::render('accountability/index', [
            'title' => 'Accountability Partner',
            'partner' => $partner['data'] ?? null,
            'conversations' => $conversations['data'] ?? []
        ]);
    });

    $router->get('/accountability/chat', function () {
        global $api;

        $partner = $api->get('/accountability/partner');
        $messages = [];

        if ($partner['success'] && isset($partner['data']['id'])) {
            $messagesResponse = $api->get('/accountability/messages/' . $partner['data']['id']);
            $messages = $messagesResponse['data'] ?? [];
        }

        View::render('accountability/chat', [
            'title' => 'Chat with Partner',
            'partner' => $partner['data'] ?? null,
            'messages' => $messages
        ]);
    });

    $router->post('/accountability/messages', function () {
        global $api;

        $data = json_decode(file_get_contents('php://input'), true);

        $result = $api->post('/accountability/messages', [
            'message' => $data['message'] ?? ''
        ]);

        View::json($result);
    });
});

// ===========================================
// ADMIN ROUTES
// ===========================================

$router->group(['prefix' => '/admin', 'middleware' => 'admin'], function ($router) {
    // Dashboard
    $router->get('/', function () {
        global $api;

        $dashboard = $api->get('/admin/dashboard');

        View::render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $dashboard['data'] ?? []
        ], 'admin');
    });

    // Users
    $router->get('/users', function () {
        global $api;

        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';

        $params = ['page' => $page, 'per_page' => 20];
        if ($search) $params['search'] = $search;

        $users = $api->get('/admin/users', $params);

        View::render('admin/users/index', [
            'title' => 'Manage Users',
            'users' => $users['data']['data'] ?? $users['data'] ?? [],
            'meta' => $users['data']['meta'] ?? [],
            'search' => $search
        ], 'admin');
    });

    $router->post('/users/{id}/role', function ($id) {
        global $api;

        $data = json_decode(file_get_contents('php://input'), true);

        $result = $api->put('/admin/users/' . $id, [
            'role' => $data['role'] ?? 'user'
        ]);

        View::json($result);
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
            $where .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($category) {
            $where .= " AND category_id = ?";
            $params[] = $category;
        }
        if ($level) {
            $where .= " AND level = ?";
            $params[] = $level;
        }
        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $total = (int) \Core\Database::scalar("SELECT COUNT(*) FROM courses WHERE {$where}", $params);

        $params[] = $perPage;
        $params[] = $offset;
        $courses = \Core\Database::query("
            SELECT c.*, cat.name as category_name,
                   (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lesson_count,
                   (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollment_count
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE {$where}
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
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

        try {
            \Core\Database::execute("
                INSERT INTO courses (title, slug, description, instructor, duration, category_id, level, status, is_premium, tags, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $title,
                $slug,
                $_POST['description'] ?? '',
                $_POST['instructor'] ?? '',
                $_POST['duration'] ?? '',
                !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                $_POST['level'] ?? 'beginner',
                $_POST['status'] ?? 'draft',
                isset($_POST['is_premium']) ? 1 : 0,
                $_POST['tags'] ?? ''
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

        try {
            \Core\Database::execute("
                UPDATE courses SET
                    title = ?,
                    description = ?,
                    instructor = ?,
                    duration = ?,
                    category_id = ?,
                    level = ?,
                    status = ?,
                    is_premium = ?,
                    tags = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                $_POST['instructor'] ?? '',
                $_POST['duration'] ?? '',
                !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                $_POST['level'] ?? 'beginner',
                $_POST['status'] ?? 'draft',
                isset($_POST['is_premium']) ? 1 : 0,
                $_POST['tags'] ?? '',
                $id
            ]);

            flash('success', 'Course updated successfully');
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
        global $api;

        $page = (int)($_GET['page'] ?? 1);
        $payments = $api->get('/admin/payments', ['page' => $page, 'per_page' => 20]);

        View::render('admin/payments/index', [
            'title' => 'Payments',
            'payments' => $payments['data']['data'] ?? $payments['data'] ?? [],
            'meta' => $payments['data']['meta'] ?? []
        ], 'admin');
    });

    $router->post('/payments/{id}/approve', function ($id) {
        global $api;

        $result = $api->put('/admin/payments/' . $id . '/approve');
        View::json($result);
    });

    // Subscriptions
    $router->get('/subscriptions', function () {
        global $api;

        $subscriptions = $api->get('/admin/subscriptions', ['per_page' => 20]);
        $plans = $api->get('/admin/subscription-plans');

        View::render('admin/subscriptions/index', [
            'title' => 'Subscriptions',
            'subscriptions' => $subscriptions['data']['data'] ?? $subscriptions['data'] ?? [],
            'plans' => $plans['data'] ?? []
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
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/subscriptions/plans/create');
        }

        $features = array_filter($_POST['features'] ?? [], fn($f) => !empty(trim($f)));

        $result = $api->post('/admin/subscription-plans', [
            'name' => $_POST['name'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'original_price' => $_POST['original_price'] ?? null,
            'duration_days' => $_POST['duration_days'] ?? 30,
            'duration_months' => $_POST['duration_months'] ?? 1,
            'features' => $features,
            'is_active' => isset($_POST['is_active']),
            'is_popular' => isset($_POST['is_popular']),
            'includes_goal_tracker' => isset($_POST['includes_goal_tracker']),
            'includes_accountability_partner' => isset($_POST['includes_accountability_partner'])
        ]);

        if ($result['success']) {
            flash('success', 'Plan created successfully');
            redirect('/admin/subscriptions');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to create plan');
            $_SESSION['old_input'] = $_POST;
            redirect('/admin/subscriptions/plans/create');
        }
    });

    $router->get('/subscriptions/plans/{id}/edit', function ($id) {
        global $api;

        $plan = $api->get('/admin/subscription-plans/' . $id);

        if (!$plan['success']) {
            flash('error', 'Plan not found');
            redirect('/admin/subscriptions');
        }

        View::render('admin/subscriptions/edit-plan', [
            'title' => 'Edit Plan',
            'plan' => $plan['data']
        ], 'admin');
    });

    $router->post('/subscriptions/plans/{id}/update', function ($id) {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/subscriptions/plans/' . $id . '/edit');
        }

        $features = array_filter($_POST['features'] ?? [], fn($f) => !empty(trim($f)));

        $result = $api->put('/admin/subscription-plans/' . $id, [
            'name' => $_POST['name'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'original_price' => $_POST['original_price'] ?? null,
            'duration_days' => $_POST['duration_days'] ?? 30,
            'duration_months' => $_POST['duration_months'] ?? 1,
            'features' => $features,
            'is_active' => isset($_POST['is_active']),
            'is_popular' => isset($_POST['is_popular']),
            'includes_goal_tracker' => isset($_POST['includes_goal_tracker']),
            'includes_accountability_partner' => isset($_POST['includes_accountability_partner'])
        ]);

        if ($result['success']) {
            flash('success', 'Plan updated successfully');
            redirect('/admin/subscriptions');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to update plan');
            redirect('/admin/subscriptions/plans/' . $id . '/edit');
        }
    });

    $router->delete('/subscriptions/plans/{id}/delete', function ($id) {
        global $api;

        $result = $api->delete('/admin/subscription-plans/' . $id);
        View::json($result);
    });

    // Reports
    $router->get('/reports', function () {
        global $api;

        $userReport = $api->get('/admin/reports/users');
        $revenueReport = $api->get('/admin/reports/revenue');
        $courseReport = $api->get('/admin/reports/courses');

        View::render('admin/reports', [
            'title' => 'Reports & Analytics',
            'userReport' => $userReport['data'] ?? [],
            'revenueReport' => $revenueReport['data'] ?? [],
            'courseReport' => $courseReport['data'] ?? []
        ], 'admin');
    });

    // Settings
    $router->get('/settings', function () {
        global $api;

        $settings = $api->get('/admin/settings');
        $paymentMethods = $api->get('/admin/payment-methods');

        View::render('admin/settings/index', [
            'title' => 'Platform Settings',
            'settings' => $settings['data'] ?? [],
            'paymentMethods' => $paymentMethods['data'] ?? []
        ], 'admin');
    });

    $router->post('/settings', function () {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/settings');
        }

        $result = $api->put('/admin/settings', $_POST);

        if ($result['success']) {
            flash('success', 'Settings updated successfully');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to update settings');
        }

        redirect('/admin/settings');
    });

    $router->post('/payment-methods/{id}', function ($id) {
        global $api;

        $data = json_decode(file_get_contents('php://input'), true);

        $result = $api->put('/admin/payment-methods/' . $id, $data);
        View::json($result);
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

        // Get courses with counts
        $params[] = $perPage;
        $params[] = $offset;
        $courses = \Core\Database::query("
            SELECT c.*,
                   (SELECT COUNT(*) FROM ai_modules WHERE course_id = c.id) as module_count,
                   (SELECT COUNT(*) FROM ai_lessons l JOIN ai_modules m ON l.module_id = m.id WHERE m.course_id = c.id) as lesson_count,
                   (SELECT COUNT(*) FROM ai_enrollments WHERE course_id = c.id) as enrollment_count
            FROM ai_courses c
            WHERE {$where}
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
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
