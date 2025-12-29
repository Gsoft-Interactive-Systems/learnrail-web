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
        global $api;

        $page = (int)($_GET['page'] ?? 1);
        $courses = $api->get('/admin/courses', ['page' => $page, 'per_page' => 20]);

        View::render('admin/courses/index', [
            'title' => 'Manage Courses',
            'courses' => $courses['data']['data'] ?? $courses['data'] ?? [],
            'meta' => $courses['data']['meta'] ?? []
        ], 'admin');
    });

    $router->get('/courses/create', function () {
        global $api;

        $categories = $api->get('/admin/categories');
        $instructors = $api->get('/admin/instructors');

        View::render('admin/courses/form', [
            'title' => 'Create Course',
            'course' => null,
            'categories' => $categories['data'] ?? [],
            'instructors' => $instructors['data'] ?? []
        ], 'admin');
    });

    $router->post('/courses/create', function () {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/courses/create');
        }

        $result = $api->post('/admin/courses', $_POST);

        if ($result['success']) {
            flash('success', 'Course created successfully');
            redirect('/admin/courses');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to create course');
            $_SESSION['old_input'] = $_POST;
            redirect('/admin/courses/create');
        }
    });

    $router->get('/courses/{id}/edit', function ($id) {
        global $api;

        $course = $api->get('/admin/courses/' . $id);
        $categories = $api->get('/admin/categories');
        $instructors = $api->get('/admin/instructors');

        if (!$course['success']) {
            View::error(404, 'Course not found');
        }

        View::render('admin/courses/form', [
            'title' => 'Edit Course',
            'course' => $course['data'],
            'categories' => $categories['data'] ?? [],
            'instructors' => $instructors['data'] ?? []
        ], 'admin');
    });

    $router->post('/courses/{id}/edit', function ($id) {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/courses/' . $id . '/edit');
        }

        $result = $api->put('/admin/courses/' . $id, $_POST);

        if ($result['success']) {
            flash('success', 'Course updated successfully');
            redirect('/admin/courses');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to update course');
            redirect('/admin/courses/' . $id . '/edit');
        }
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

    // AI Courses
    $router->get('/ai-courses', function () {
        global $api;

        $page = (int)($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $level = $_GET['level'] ?? '';

        $params = ['page' => $page, 'per_page' => 20];
        if ($search) $params['search'] = $search;
        if ($status) $params['status'] = $status;
        if ($level) $params['level'] = $level;

        $courses = $api->get('/admin/ai-courses', $params);

        View::render('admin/ai-courses/index', [
            'title' => 'AI Courses',
            'courses' => $courses['data']['data'] ?? $courses['data'] ?? [],
            'meta' => $courses['data']['meta'] ?? []
        ], 'admin');
    });

    $router->get('/ai-courses/create', function () {
        global $api;

        $categories = $api->get('/admin/categories');

        View::render('admin/ai-courses/form', [
            'title' => 'Create AI Course',
            'course' => null,
            'categories' => $categories['data'] ?? []
        ], 'admin');
    });

    $router->post('/ai-courses/store', function () {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/ai-courses/create');
        }

        $result = $api->post('/admin/ai-courses', $_POST);

        if ($result['success']) {
            flash('success', 'AI Course created successfully');
            $courseId = $result['data']['id'] ?? null;
            if ($courseId) {
                redirect('/admin/ai-courses/' . $courseId . '/curriculum');
            } else {
                redirect('/admin/ai-courses');
            }
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to create AI course');
            $_SESSION['old_input'] = $_POST;
            redirect('/admin/ai-courses/create');
        }
    });

    $router->get('/ai-courses/{id}/edit', function ($id) {
        global $api;

        $course = $api->get('/admin/ai-courses/' . $id);
        $categories = $api->get('/admin/categories');

        if (!$course['success']) {
            View::error(404, 'AI Course not found');
        }

        View::render('admin/ai-courses/form', [
            'title' => 'Edit AI Course',
            'course' => $course['data'],
            'categories' => $categories['data'] ?? []
        ], 'admin');
    });

    $router->post('/ai-courses/{id}/update', function ($id) {
        global $api;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/admin/ai-courses/' . $id . '/edit');
        }

        $result = $api->put('/admin/ai-courses/' . $id, $_POST);

        if ($result['success']) {
            flash('success', 'AI Course updated successfully');
            redirect('/admin/ai-courses/' . $id . '/edit');
        } else {
            flash('error', $result['data']['message'] ?? 'Failed to update AI course');
            redirect('/admin/ai-courses/' . $id . '/edit');
        }
    });

    $router->get('/ai-courses/{id}/curriculum', function ($id) {
        global $api;

        $course = $api->get('/admin/ai-courses/' . $id);

        if (!$course['success']) {
            View::error(404, 'AI Course not found');
        }

        View::render('admin/ai-courses/curriculum', [
            'title' => 'Manage Curriculum',
            'course' => $course['data']
        ], 'admin');
    });

    $router->put('/ai-courses/{id}/curriculum', function ($id) {
        global $api;

        $data = json_decode(file_get_contents('php://input'), true);

        // Save modules
        $result = ['success' => true];

        // First, get existing modules to know what to delete/update
        foreach ($data['modules'] ?? [] as $moduleIndex => $module) {
            if (isset($module['id'])) {
                // Update existing module
                $api->put('/admin/ai-modules/' . $module['id'], [
                    'title' => $module['title'],
                    'description' => $module['description'] ?? '',
                    'order' => $moduleIndex + 1
                ]);
            } else {
                // Create new module
                $moduleResult = $api->post('/admin/ai-courses/' . $id . '/modules', [
                    'title' => $module['title'],
                    'description' => $module['description'] ?? '',
                    'order' => $moduleIndex + 1
                ]);

                if ($moduleResult['success']) {
                    $module['id'] = $moduleResult['data']['id'];
                }
            }

            // Handle lessons for this module
            if (isset($module['id'])) {
                foreach ($module['lessons'] ?? [] as $lessonIndex => $lesson) {
                    if (isset($lesson['id'])) {
                        // Update existing lesson
                        $api->put('/admin/ai-lessons/' . $lesson['id'], [
                            'title' => $lesson['title'],
                            'content' => $lesson['content'] ?? '',
                            'objectives' => $lesson['objectives'] ?? '',
                            'estimated_time' => $lesson['estimated_time'] ?? '',
                            'order' => $lessonIndex + 1
                        ]);
                    } else {
                        // Create new lesson
                        $api->post('/admin/ai-modules/' . $module['id'] . '/lessons', [
                            'title' => $lesson['title'],
                            'content' => $lesson['content'] ?? '',
                            'objectives' => $lesson['objectives'] ?? '',
                            'estimated_time' => $lesson['estimated_time'] ?? '',
                            'order' => $lessonIndex + 1
                        ]);
                    }
                }
            }
        }

        View::json(['success' => true, 'message' => 'Curriculum saved']);
    });

    $router->delete('/ai-courses/{id}', function ($id) {
        global $api;

        $result = $api->delete('/admin/ai-courses/' . $id);
        View::json($result);
    });
});
