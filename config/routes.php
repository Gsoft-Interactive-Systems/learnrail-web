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

// AI Tutor Chat API (requires authentication)
$router->post('/api/ai-tutor/chat', function () {
    global $auth;

    header('Content-Type: application/json');

    // Check authentication
    if (!$auth->check()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    $courseId = $input['course_id'] ?? 0;
    $lessonId = $input['lesson_id'] ?? 0;
    $message = trim($input['message'] ?? '');
    $history = $input['history'] ?? [];

    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message is required']);
        exit;
    }

    // Get course and lesson context
    $course = \Core\Database::queryOne("SELECT * FROM ai_courses WHERE id = ?", [$courseId]);
    $lesson = \Core\Database::queryOne("SELECT * FROM ai_lessons WHERE id = ?", [$lessonId]);

    if (!$course) {
        echo json_encode(['success' => false, 'error' => 'Course not found']);
        exit;
    }

    // Build system prompt with course context
    $systemPrompt = "You are an AI tutor for the course: {$course['title']}.\n";
    if (!empty($course['ai_instructions'])) {
        $systemPrompt .= "Teaching instructions: {$course['ai_instructions']}\n";
    }
    if ($lesson) {
        $systemPrompt .= "Current lesson: {$lesson['title']}\n";
        if (!empty($lesson['content_outline'])) {
            $systemPrompt .= "Lesson content to teach:\n{$lesson['content_outline']}\n";
        }
    }
    $systemPrompt .= "\nBe helpful, encouraging, and educational. Use simple language and provide examples when helpful.";

    // Get AI API key from settings
    $apiKey = \Core\Database::scalar("SELECT value FROM settings WHERE `key` = 'openai_api_key'");

    if (empty($apiKey)) {
        echo json_encode([
            'success' => false,
            'error' => 'AI Tutor is not configured. The OpenAI API key is missing. Please add "openai_api_key" in Admin > Settings to enable the AI tutor.'
        ]);
        exit;
    }

    // Call OpenAI API
    try {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        // Add chat history
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['content']
            ];
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $message];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7
            ]),
            CURLOPT_TIMEOUT => 30
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("AI Tutor cURL Error: " . $curlError);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to connect to AI service. Error: ' . $curlError
            ]);
            exit;
        }

        if ($httpCode === 200) {
            $data = json_decode($result, true);
            $aiResponse = $data['choices'][0]['message']['content'] ?? null;
            if ($aiResponse) {
                echo json_encode(['success' => true, 'response' => $aiResponse]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'AI returned an empty response. Please try again.'
                ]);
            }
        } elseif ($httpCode === 401) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid OpenAI API key. Please check your API key in Admin > Settings.'
            ]);
        } elseif ($httpCode === 429) {
            echo json_encode([
                'success' => false,
                'error' => 'OpenAI rate limit exceeded. Please wait a moment and try again.'
            ]);
        } else {
            $errorData = json_decode($result, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            error_log("AI Tutor API Error (HTTP $httpCode): " . $errorMsg);
            echo json_encode([
                'success' => false,
                'error' => "AI service error (HTTP $httpCode): $errorMsg"
            ]);
        }
    } catch (\Exception $e) {
        error_log("AI Tutor Exception: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'An error occurred: ' . $e->getMessage()
        ]);
    }
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

    // Reset Password
    $router->get('/reset-password', function () {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            flash('error', 'Invalid or missing reset token');
            redirect('/forgot-password');
        }

        View::render('auth/reset-password', [
            'title' => 'Reset Password',
            'token' => $token
        ], 'auth');
    });

    $router->post('/reset-password', function () {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/forgot-password');
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        if (empty($token)) {
            flash('error', 'Invalid or missing reset token');
            redirect('/forgot-password');
        }

        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters');
            redirect('/reset-password?token=' . urlencode($token));
        }

        if ($password !== $passwordConfirmation) {
            flash('error', 'Passwords do not match');
            redirect('/reset-password?token=' . urlencode($token));
        }

        // Call API to reset password
        $api = new \Core\ApiClient();
        $response = $api->post('/auth/reset-password', [
            'token' => $token,
            'password' => $password
        ]);

        if ($response['success']) {
            flash('success', 'Password reset successful! You can now login with your new password.');
            redirect('/login');
        } else {
            flash('error', $response['data']['message'] ?? 'Failed to reset password. The link may have expired.');
            redirect('/forgot-password');
        }
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

        // Build WHERE clauses for both regular and AI courses
        $whereRegular = "c.is_published = 1";
        $whereAI = "ac.is_published = 1";
        $params = [];
        $paramsAI = [];

        if ($search) {
            $whereRegular .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $whereAI .= " AND (ac.title LIKE ? OR ac.description LIKE ?)";
            $paramsAI[] = "%{$search}%";
            $paramsAI[] = "%{$search}%";
        }
        if ($category) {
            $whereRegular .= " AND c.category_id = ?";
            $params[] = $category;
            $whereAI .= " AND ac.category_id = ?";
            $paramsAI[] = $category;
        }
        if ($level) {
            $whereRegular .= " AND c.level = ?";
            $params[] = $level;
            $whereAI .= " AND ac.level = ?";
            $paramsAI[] = $level;
        }

        // Count total from both tables
        $totalRegular = (int) \Core\Database::scalar("SELECT COUNT(*) FROM courses c WHERE {$whereRegular}", $params);
        $totalAI = (int) \Core\Database::scalar("SELECT COUNT(*) FROM ai_courses ac WHERE {$whereAI}", $paramsAI);
        $total = $totalRegular + $totalAI;

        // Combined query using UNION
        $allParams = array_merge($params, $paramsAI);
        $courses = \Core\Database::query("
            (SELECT c.id, c.title, c.slug, c.description, c.thumbnail, c.level, c.rating,
                   c.duration_hours, c.is_featured, c.is_free, c.created_at,
                   cat.name as category_name, i.name as instructor_name,
                   (SELECT COUNT(*) FROM lessons l JOIN modules m ON l.module_id = m.id WHERE m.course_id = c.id) as total_lessons,
                   0 as is_ai_course
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE {$whereRegular})
            UNION ALL
            (SELECT ac.id, ac.title, ac.slug, ac.description, ac.thumbnail, ac.level, 0 as rating,
                   0 as duration_hours, 0 as is_featured, 0 as is_free, ac.created_at,
                   cat.name as category_name, 'AI Tutor' as instructor_name,
                   (SELECT COUNT(*) FROM ai_lessons l JOIN ai_modules m ON l.module_id = m.id WHERE m.course_id = ac.id) as total_lessons,
                   1 as is_ai_course
            FROM ai_courses ac
            LEFT JOIN categories cat ON ac.category_id = cat.id
            WHERE {$whereAI})
            ORDER BY is_featured DESC, created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ", $allParams);

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

        // Decode JSON fields
        if (!empty($course['what_you_learn'])) {
            $course['what_you_learn'] = json_decode($course['what_you_learn'], true) ?: [];
        }
        if (!empty($course['requirements'])) {
            $course['requirements'] = json_decode($course['requirements'], true) ?: [];
        }

        // Format instructor as array for template
        $course['instructor'] = [];
        if (!empty($course['instructor_name'])) {
            $course['instructor'] = [
                'name' => $course['instructor_name'],
                'bio' => $course['instructor_bio'] ?? '',
                'avatar' => $course['instructor_avatar'] ?? ''
            ];
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
        $user = $auth->user();

        // Check if course exists
        $course = \Core\Database::queryOne("SELECT id, is_free, title FROM courses WHERE id = ? OR slug = ?", [$id, $id]);
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

        // Check subscription for premium courses (not free courses)
        if (empty($course['is_free'])) {
            // Check if user is admin (admins can enroll in any course)
            $isAdmin = ($user['role'] ?? '') === 'admin';

            if (!$isAdmin) {
                // Check for active subscription
                $activeSubscription = \Core\Database::queryOne("
                    SELECT s.id, s.plan_id, sp.name as plan_name, sp.accessible_courses
                    FROM subscriptions s
                    JOIN subscription_plans sp ON s.plan_id = sp.id
                    WHERE s.user_id = ?
                    AND s.status = 'active'
                    AND (s.end_date IS NULL OR s.end_date >= CURDATE())
                    ORDER BY s.end_date DESC
                    LIMIT 1
                ", [$userId]);

                if (!$activeSubscription) {
                    flash('error', 'This is a premium course. Please subscribe to access this course.');
                    redirect('/subscription');
                }

                // Check if this specific course is accessible with the subscription plan
                if (!empty($activeSubscription['accessible_courses'])) {
                    $accessibleCourses = json_decode($activeSubscription['accessible_courses'], true);
                    if (is_array($accessibleCourses) && !in_array($course['id'], $accessibleCourses)) {
                        flash('error', 'Your current subscription plan does not include access to this course. Please upgrade your plan.');
                        redirect('/subscription');
                    }
                }
            }
        }

        // Create enrollment
        \Core\Database::execute("
            INSERT INTO enrollments (user_id, course_id, status, enrolled_at)
            VALUES (?, ?, 'enrolled', NOW())
        ", [$userId, $course['id']]);

        flash('success', 'Successfully enrolled in course!');
        redirect('/courses/' . $id);
    });

    // AI Courses - User-facing routes
    $router->get('/ai-courses/{id}', function ($id) {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Get AI course with modules and lessons
        $course = \Core\Database::queryOne("
            SELECT ac.*, cat.name as category_name
            FROM ai_courses ac
            LEFT JOIN categories cat ON ac.category_id = cat.id
            WHERE ac.id = ? AND ac.is_published = 1
        ", [$id]);

        if (!$course) {
            View::error(404, 'Course not found');
        }

        // Get modules with lessons
        $modules = \Core\Database::query("
            SELECT * FROM ai_modules WHERE course_id = ? ORDER BY sort_order, id
        ", [$course['id']]);

        foreach ($modules as &$module) {
            $module['lessons'] = \Core\Database::query("
                SELECT * FROM ai_lessons WHERE module_id = ? ORDER BY sort_order, id
            ", [$module['id']]);
        }
        $course['modules'] = $modules;

        // Count total lessons
        $course['total_lessons'] = (int) \Core\Database::scalar("
            SELECT COUNT(*) FROM ai_lessons l JOIN ai_modules m ON l.module_id = m.id WHERE m.course_id = ?
        ", [$course['id']]);

        // Check enrollment
        $enrollment = \Core\Database::queryOne("
            SELECT * FROM ai_enrollments WHERE user_id = ? AND course_id = ?
        ", [$userId, $course['id']]);
        $course['is_enrolled'] = !empty($enrollment);
        $course['enrollment'] = $enrollment;

        View::render('ai-courses/show', [
            'title' => $course['title'] ?? 'AI Course',
            'course' => $course
        ]);
    });

    $router->post('/ai-courses/{id}/enroll', function ($id) {
        global $auth;

        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect('/ai-courses/' . $id);
        }

        $userId = $auth->user()['id'] ?? 0;
        $user = $auth->user();

        // Check if AI course exists
        $course = \Core\Database::queryOne("SELECT id, is_premium, title FROM ai_courses WHERE id = ? AND is_published = 1", [$id]);
        if (!$course) {
            flash('error', 'Course not found');
            redirect('/courses');
        }

        // Check if already enrolled
        $existing = \Core\Database::queryOne("SELECT id FROM ai_enrollments WHERE user_id = ? AND course_id = ?", [$userId, $course['id']]);
        if ($existing) {
            flash('info', 'You are already enrolled in this course');
            redirect('/ai-courses/' . $id);
        }

        // Check subscription for premium AI courses
        if (!empty($course['is_premium'])) {
            $isAdmin = ($user['role'] ?? '') === 'admin';

            if (!$isAdmin) {
                $activeSubscription = \Core\Database::queryOne("
                    SELECT s.id FROM subscriptions s
                    WHERE s.user_id = ? AND s.status = 'active'
                    AND (s.end_date IS NULL OR s.end_date >= CURDATE())
                    LIMIT 1
                ", [$userId]);

                if (!$activeSubscription) {
                    flash('error', 'This is a premium AI course. Please subscribe to access this course.');
                    redirect('/subscription');
                }
            }
        }

        // Create enrollment
        \Core\Database::execute("
            INSERT INTO ai_enrollments (user_id, course_id, enrolled_at)
            VALUES (?, ?, NOW())
        ", [$userId, $course['id']]);

        flash('success', 'Successfully enrolled in AI course!');
        redirect('/ai-courses/' . $id . '/learn');
    });

    $router->get('/ai-courses/{id}/learn', function ($id) {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Get AI course
        $course = \Core\Database::queryOne("SELECT * FROM ai_courses WHERE id = ? AND is_published = 1", [$id]);
        if (!$course) {
            View::error(404, 'Course not found');
        }

        // Check enrollment
        $enrollment = \Core\Database::queryOne("
            SELECT * FROM ai_enrollments WHERE user_id = ? AND course_id = ?
        ", [$userId, $course['id']]);

        if (!$enrollment) {
            flash('error', 'Please enroll in this course first');
            redirect('/ai-courses/' . $id);
        }

        // Get modules with lessons
        $modules = \Core\Database::query("
            SELECT * FROM ai_modules WHERE course_id = ? ORDER BY sort_order, id
        ", [$course['id']]);

        foreach ($modules as &$module) {
            $module['lessons'] = \Core\Database::query("
                SELECT * FROM ai_lessons WHERE module_id = ? ORDER BY sort_order, id
            ", [$module['id']]);
        }
        $course['modules'] = $modules;

        View::render('ai-courses/learn', [
            'title' => 'Learn: ' . $course['title'],
            'course' => $course,
            'enrollment' => $enrollment
        ]);
    });

    $router->get('/courses/{courseId}/lessons/{lessonId}', function ($courseId, $lessonId) {
        global $auth;
        $userId = $auth->user()['id'] ?? 0;

        // Get lesson with module info and progress status
        $lesson = \Core\Database::queryOne("
            SELECT l.*, m.title as module_title, m.course_id,
                   (SELECT status FROM lesson_progress WHERE user_id = ? AND lesson_id = l.id) as progress_status
            FROM lessons l
            JOIN modules m ON l.module_id = m.id
            WHERE l.id = ?
        ", [$userId, $lessonId]);

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

        // Get all lessons for navigation with progress status
        $allLessons = \Core\Database::query("
            SELECT l.id, l.title, l.type, l.sort_order, m.sort_order as module_order,
                   (SELECT status FROM lesson_progress WHERE user_id = ? AND lesson_id = l.id) as progress_status
            FROM lessons l
            JOIN modules m ON l.module_id = m.id
            WHERE m.course_id = ? AND l.is_published = 1
            ORDER BY m.sort_order, l.sort_order
        ", [$userId, $lesson['course_id']]);

        // Find prev/next lessons
        $currentIndex = array_search($lessonId, array_column($allLessons, 'id'));
        $prevLesson = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
        $nextLesson = $currentIndex < count($allLessons) - 1 ? $allLessons[$currentIndex + 1] : null;

        // Calculate course progress
        $totalLessons = count($allLessons);
        $completedLessons = 0;
        foreach ($allLessons as $l) {
            if ($l['progress_status'] === 'completed') {
                $completedLessons++;
            }
        }
        $courseProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

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
            'courseId' => $courseId,
            'allLessons' => $allLessons,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
            'courseProgress' => $courseProgress
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

            View::json(['success' => true, 'message' => 'Lesson marked as complete', 'progress' => (int)$progress]);
            return;
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

        // Handle JSON input
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        // Check CSRF from header (sent by API.js) or from POST/JSON body
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['_token'] ?? $_POST['_token'] ?? '';
        if (!verify_csrf($csrfToken)) {
            View::json(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $userId = $auth->user()['id'] ?? 0;
        $planId = $input['plan_id'] ?? $_POST['plan_id'] ?? '';
        $paymentMethod = $input['payment_method'] ?? $_POST['payment_method'] ?? 'bank_transfer';

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

        // Prepare response based on payment method
        $responseData = [
            'reference' => $reference,
            'amount' => $plan['price'],
            'email' => $auth->user()['email'] ?? '',
            'plan_name' => $plan['name'],
            'payment_method' => $paymentMethod
        ];

        // Add bank details for bank transfer
        if ($paymentMethod === 'bank_transfer') {
            $responseData['bank_details'] = [
                'bank_name' => 'Access Bank',
                'account_number' => '1234567890',
                'account_name' => 'Learnrail Limited'
            ];
            $responseData['instructions'] = 'Please transfer the exact amount to the bank account above and upload your payment receipt. Your subscription will be activated after verification.';
        }

        // Add Paystack data if using Paystack
        if ($paymentMethod === 'paystack') {
            $responseData['amount'] = $plan['price'] * 100; // Convert to kobo for Paystack
        }

        View::json([
            'success' => true,
            'data' => $responseData
        ]);
    });

    // Upload payment receipt for bank transfer
    $router->post('/subscription/upload-receipt', function () {
        global $auth;

        // Check CSRF from header
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_token'] ?? '';
        if (!verify_csrf($csrfToken)) {
            View::json(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $userId = $auth->user()['id'] ?? 0;
        $reference = $_POST['reference'] ?? '';

        if (empty($reference)) {
            View::json(['success' => false, 'message' => 'Payment reference is required'], 400);
            return;
        }

        // Verify payment exists and belongs to user
        $payment = \Core\Database::queryOne("
            SELECT * FROM payments WHERE reference = ? AND user_id = ? AND status = 'pending'
        ", [$reference, $userId]);

        if (!$payment) {
            View::json(['success' => false, 'message' => 'Payment not found or already processed'], 400);
            return;
        }

        // Handle file upload
        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            View::json(['success' => false, 'message' => 'Please upload a valid receipt'], 400);
            return;
        }

        $file = $_FILES['receipt'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            View::json(['success' => false, 'message' => 'Invalid file type. Please upload an image or PDF.'], 400);
            return;
        }

        if ($file['size'] > $maxSize) {
            View::json(['success' => false, 'message' => 'File size must be less than 5MB'], 400);
            return;
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = PUBLIC_PATH . '/uploads/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'receipt_' . $reference . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            View::json(['success' => false, 'message' => 'Failed to save file'], 500);
            return;
        }

        // Update payment with receipt path
        $receiptUrl = '/uploads/receipts/' . $filename;
        \Core\Database::execute("
            UPDATE payments SET receipt_url = ?, updated_at = NOW() WHERE id = ?
        ", [$receiptUrl, $payment['id']]);

        View::json([
            'success' => true,
            'message' => 'Receipt uploaded successfully. We will verify your payment shortly.',
            'data' => ['receipt_url' => $receiptUrl]
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
                   (SELECT s.plan_id FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as current_plan_id,
                   (SELECT s.end_date FROM subscriptions s WHERE s.user_id = u.id AND s.status = 'active' LIMIT 1) as subscription_end_date,
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

        // Update user info
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

        // Handle subscription change
        $newPlanId = $_POST['subscription_plan'] ?? '';
        $currentSubscription = \Core\Database::queryOne(
            "SELECT id, plan_id FROM subscriptions WHERE user_id = ? AND status = 'active' LIMIT 1",
            [$id]
        );

        if ($newPlanId === '' || $newPlanId === 'free') {
            // Remove active subscription (set to cancelled)
            if ($currentSubscription) {
                \Core\Database::execute(
                    "UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
                    [$currentSubscription['id']]
                );
            }
        } else {
            // Get the plan details
            $plan = \Core\Database::queryOne("SELECT * FROM subscription_plans WHERE id = ?", [$newPlanId]);
            if ($plan) {
                $durationDays = ($plan['duration_days'] ?? 0) ?: ($plan['duration_months'] * 30);
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d', strtotime("+{$durationDays} days"));

                if ($currentSubscription) {
                    // Update existing subscription
                    \Core\Database::execute("
                        UPDATE subscriptions SET
                            plan_id = ?,
                            status = 'active',
                            amount_paid = ?,
                            start_date = ?,
                            end_date = ?,
                            payment_method = 'bank_transfer',
                            payment_reference = ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ", [
                        $newPlanId,
                        $plan['price'],
                        $startDate,
                        $endDate,
                        'ADMIN-' . strtoupper(uniqid()),
                        $currentSubscription['id']
                    ]);
                } else {
                    // Create new subscription
                    \Core\Database::execute("
                        INSERT INTO subscriptions (user_id, plan_id, status, amount_paid, start_date, end_date, payment_method, payment_reference, created_at)
                        VALUES (?, ?, 'active', ?, ?, ?, 'bank_transfer', ?, NOW())
                    ", [
                        $id,
                        $newPlanId,
                        $plan['price'],
                        $startDate,
                        $endDate,
                        'ADMIN-' . strtoupper(uniqid())
                    ]);
                }
            }
        }

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

        // Handle thumbnail upload
        $thumbnailPath = null;
        if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../public/uploads/courses/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowedExts)) {
                $filename = $slug . '-' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                    $thumbnailPath = '/uploads/courses/' . $filename;
                }
            }
        }

        // Handle instructor - create one if name provided
        $instructorId = null;
        $instructorName = trim($_POST['instructor'] ?? '');
        if (!empty($instructorName)) {
            // Check if instructor with this name exists
            $existingInstructor = \Core\Database::queryOne(
                "SELECT id FROM instructors WHERE name = ?",
                [$instructorName]
            );

            if ($existingInstructor) {
                $instructorId = $existingInstructor['id'];
            } else {
                // Create new instructor
                \Core\Database::execute(
                    "INSERT INTO instructors (name, created_at) VALUES (?, NOW())",
                    [$instructorName]
                );
                $instructorId = \Core\Database::lastInsertId();
            }
        }

        // Parse duration - convert text like "10 hours" or "2.5 hours" to decimal
        $durationHours = 0;
        $durationText = trim($_POST['duration'] ?? '');
        if (!empty($durationText)) {
            // Extract numeric value (handles "10 hours", "10h", "10", etc.)
            if (preg_match('/(\d+(?:\.\d+)?)/', $durationText, $matches)) {
                $durationHours = (float)$matches[1];
            }
        }

        // Process learning outcomes
        $learningOutcomes = [];
        if (!empty($_POST['learning_outcomes'])) {
            foreach ($_POST['learning_outcomes'] as $outcome) {
                $outcome = trim($outcome);
                if (!empty($outcome)) {
                    $learningOutcomes[] = $outcome;
                }
            }
        }
        $whatYouLearn = !empty($learningOutcomes) ? json_encode($learningOutcomes) : null;

        try {
            \Core\Database::execute("
                INSERT INTO courses (title, slug, description, category_id, level, is_published, is_free, what_you_learn, thumbnail, instructor_id, duration_hours, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $title,
                $slug,
                $_POST['description'] ?? '',
                !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                $_POST['level'] ?? 'beginner',
                $isPublished,
                $isFree,
                $whatYouLearn,
                $thumbnailPath,
                $instructorId,
                $durationHours
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
            SELECT c.*, cat.name as category_name, i.name as instructor_name
            FROM courses c
            LEFT JOIN categories cat ON c.category_id = cat.id
            LEFT JOIN instructors i ON c.instructor_id = i.id
            WHERE c.id = ?
        ", [$id]);

        if (!$course) {
            View::error(404, 'Course not found');
        }

        // Map instructor name to 'instructor' field for form
        $course['instructor'] = $course['instructor_name'] ?? '';

        // Format duration for display
        $hours = (float)($course['duration_hours'] ?? 0);
        $course['duration'] = $hours > 0 ? $hours . ' hours' : '';

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

        $course = \Core\Database::queryOne("SELECT id, slug, thumbnail FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            flash('error', 'Course not found');
            redirect('/admin/courses');
        }

        // Convert status to is_published (0 or 1)
        $isPublished = ($_POST['status'] ?? 'draft') === 'published' ? 1 : 0;
        // is_free is the inverse of is_premium (premium = NOT free)
        $isFree = isset($_POST['is_premium']) ? 0 : 1;

        // Handle thumbnail upload
        $thumbnailPath = $course['thumbnail']; // Keep existing if no new upload
        if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../public/uploads/courses/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowedExts)) {
                $filename = $course['slug'] . '-' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                    // Delete old thumbnail if exists
                    if (!empty($course['thumbnail'])) {
                        $oldPath = __DIR__ . '/../public' . $course['thumbnail'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $thumbnailPath = '/uploads/courses/' . $filename;
                }
            }
        }

        // Handle instructor - create one if name provided
        $instructorId = null;
        $instructorName = trim($_POST['instructor'] ?? '');
        if (!empty($instructorName)) {
            $existingInstructor = \Core\Database::queryOne(
                "SELECT id FROM instructors WHERE name = ?",
                [$instructorName]
            );

            if ($existingInstructor) {
                $instructorId = $existingInstructor['id'];
            } else {
                \Core\Database::execute(
                    "INSERT INTO instructors (name, created_at) VALUES (?, NOW())",
                    [$instructorName]
                );
                $instructorId = \Core\Database::lastInsertId();
            }
        }

        // Parse duration
        $durationHours = 0;
        $durationText = trim($_POST['duration'] ?? '');
        if (!empty($durationText)) {
            if (preg_match('/(\d+(?:\.\d+)?)/', $durationText, $matches)) {
                $durationHours = (float)$matches[1];
            }
        }

        // Process learning outcomes
        $learningOutcomes = [];
        if (!empty($_POST['learning_outcomes'])) {
            foreach ($_POST['learning_outcomes'] as $outcome) {
                $outcome = trim($outcome);
                if (!empty($outcome)) {
                    $learningOutcomes[] = $outcome;
                }
            }
        }
        $whatYouLearn = !empty($learningOutcomes) ? json_encode($learningOutcomes) : null;

        try {
            $rowsAffected = \Core\Database::execute("
                UPDATE courses SET
                    title = ?,
                    description = ?,
                    category_id = ?,
                    level = ?,
                    is_published = ?,
                    is_free = ?,
                    what_you_learn = ?,
                    thumbnail = ?,
                    instructor_id = ?,
                    duration_hours = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                $_POST['level'] ?? 'beginner',
                $isPublished,
                $isFree,
                $whatYouLearn,
                $thumbnailPath,
                $instructorId,
                $durationHours,
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

    // =============================================
    // LESSON MANAGEMENT
    // =============================================

    // List lessons for a course
    $router->get('/courses/{id}/lessons', function ($id) {
        $course = \Core\Database::queryOne("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            View::error(404, 'Course not found');
        }

        // Get modules with their lessons
        $modules = \Core\Database::query("SELECT * FROM modules WHERE course_id = ? ORDER BY sort_order, id", [$id]);
        foreach ($modules as &$module) {
            $module['lessons'] = \Core\Database::query(
                "SELECT * FROM lessons WHERE module_id = ? ORDER BY sort_order, id",
                [$module['id']]
            );
        }

        View::render('admin/courses/lessons', [
            'title' => 'Manage Lessons - ' . $course['title'],
            'course' => $course,
            'modules' => $modules
        ], 'admin');
    });

    // Create lesson form
    $router->get('/courses/{id}/lessons/create', function ($id) {
        $course = \Core\Database::queryOne("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            View::error(404, 'Course not found');
        }

        $modules = \Core\Database::query("SELECT * FROM modules WHERE course_id = ? ORDER BY sort_order, id", [$id]);

        View::render('admin/courses/lesson-form', [
            'title' => 'Add Lesson - ' . $course['title'],
            'course' => $course,
            'modules' => $modules,
            'lesson' => null
        ], 'admin');
    });

    // Create lesson
    $router->post('/courses/{id}/lessons/create', function ($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect("/admin/courses/{$id}/lessons/create");
        }

        $course = \Core\Database::queryOne("SELECT id FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            flash('error', 'Course not found');
            redirect('/admin/courses');
        }

        $moduleId = (int)($_POST['module_id'] ?? 0);
        if (!$moduleId) {
            flash('error', 'Please select a module');
            redirect("/admin/courses/{$id}/lessons/create");
        }

        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            flash('error', 'Lesson title is required');
            redirect("/admin/courses/{$id}/lessons/create");
        }

        $type = $_POST['type'] ?? 'video';
        $videoUrl = null;
        $videoDuration = 0;
        $content = null;

        if ($type === 'video') {
            // Build Bunny embed URL if library and video IDs provided
            $bunnyLibraryId = trim($_POST['bunny_library_id'] ?? '');
            $bunnyVideoId = trim($_POST['bunny_video_id'] ?? '');

            if ($bunnyLibraryId && $bunnyVideoId) {
                $videoUrl = "https://iframe.mediadelivery.net/embed/{$bunnyLibraryId}/{$bunnyVideoId}?autoplay=false&preload=true";
            } else {
                $videoUrl = trim($_POST['video_url'] ?? '');
            }
            $videoDuration = (int)($_POST['video_duration'] ?? 0);
        } else {
            $content = $_POST['content'] ?? '';
        }

        try {
            \Core\Database::execute("
                INSERT INTO lessons (module_id, title, description, type, video_url, video_duration, content, is_free_preview, is_published, sort_order, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [
                $moduleId,
                $title,
                $_POST['description'] ?? '',
                $type,
                $videoUrl,
                $videoDuration,
                $content,
                isset($_POST['is_free_preview']) ? 1 : 0,
                (int)($_POST['is_published'] ?? 1),
                (int)($_POST['sort_order'] ?? 0)
            ]);

            // Update course total_lessons count
            $totalLessons = \Core\Database::scalar("
                SELECT COUNT(*) FROM lessons l
                JOIN modules m ON l.module_id = m.id
                WHERE m.course_id = ?
            ", [$id]);
            \Core\Database::execute("UPDATE courses SET total_lessons = ? WHERE id = ?", [$totalLessons, $id]);

            flash('success', 'Lesson created successfully');
            redirect("/admin/courses/{$id}/lessons");
        } catch (\Exception $e) {
            error_log("Lesson creation error: " . $e->getMessage());
            flash('error', 'Failed to create lesson: ' . $e->getMessage());
            redirect("/admin/courses/{$id}/lessons/create");
        }
    });

    // Edit lesson form
    $router->get('/courses/{id}/lessons/{lessonId}/edit', function ($id, $lessonId) {
        $course = \Core\Database::queryOne("SELECT * FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            View::error(404, 'Course not found');
        }

        $lesson = \Core\Database::queryOne("
            SELECT l.* FROM lessons l
            JOIN modules m ON l.module_id = m.id
            WHERE l.id = ? AND m.course_id = ?
        ", [$lessonId, $id]);
        if (!$lesson) {
            View::error(404, 'Lesson not found');
        }

        // Parse Bunny URL to extract library and video IDs
        if (!empty($lesson['video_url']) && strpos($lesson['video_url'], 'mediadelivery.net') !== false) {
            if (preg_match('/embed\/(\d+)\/([a-f0-9-]+)/', $lesson['video_url'], $matches)) {
                $lesson['bunny_library_id'] = $matches[1];
                $lesson['bunny_video_id'] = $matches[2];
            }
        }

        $modules = \Core\Database::query("SELECT * FROM modules WHERE course_id = ? ORDER BY sort_order, id", [$id]);

        View::render('admin/courses/lesson-form', [
            'title' => 'Edit Lesson - ' . $lesson['title'],
            'course' => $course,
            'modules' => $modules,
            'lesson' => $lesson
        ], 'admin');
    });

    // Update lesson
    $router->post('/courses/{id}/lessons/{lessonId}/edit', function ($id, $lessonId) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect("/admin/courses/{$id}/lessons/{$lessonId}/edit");
        }

        $lesson = \Core\Database::queryOne("
            SELECT l.id FROM lessons l
            JOIN modules m ON l.module_id = m.id
            WHERE l.id = ? AND m.course_id = ?
        ", [$lessonId, $id]);
        if (!$lesson) {
            flash('error', 'Lesson not found');
            redirect("/admin/courses/{$id}/lessons");
        }

        $moduleId = (int)($_POST['module_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $type = $_POST['type'] ?? 'video';
        $videoUrl = null;
        $videoDuration = 0;
        $content = null;

        if ($type === 'video') {
            $bunnyLibraryId = trim($_POST['bunny_library_id'] ?? '');
            $bunnyVideoId = trim($_POST['bunny_video_id'] ?? '');

            if ($bunnyLibraryId && $bunnyVideoId) {
                $videoUrl = "https://iframe.mediadelivery.net/embed/{$bunnyLibraryId}/{$bunnyVideoId}?autoplay=false&preload=true";
            } else {
                $videoUrl = trim($_POST['video_url'] ?? '');
            }
            $videoDuration = (int)($_POST['video_duration'] ?? 0);
        } else {
            $content = $_POST['content'] ?? '';
        }

        try {
            \Core\Database::execute("
                UPDATE lessons SET
                    module_id = ?,
                    title = ?,
                    description = ?,
                    type = ?,
                    video_url = ?,
                    video_duration = ?,
                    content = ?,
                    is_free_preview = ?,
                    is_published = ?,
                    sort_order = ?,
                    updated_at = NOW()
                WHERE id = ?
            ", [
                $moduleId,
                $title,
                $_POST['description'] ?? '',
                $type,
                $videoUrl,
                $videoDuration,
                $content,
                isset($_POST['is_free_preview']) ? 1 : 0,
                (int)($_POST['is_published'] ?? 1),
                (int)($_POST['sort_order'] ?? 0),
                $lessonId
            ]);

            flash('success', 'Lesson updated successfully');
            redirect("/admin/courses/{$id}/lessons");
        } catch (\Exception $e) {
            error_log("Lesson update error: " . $e->getMessage());
            flash('error', 'Failed to update lesson');
            redirect("/admin/courses/{$id}/lessons/{$lessonId}/edit");
        }
    });

    // Delete lesson
    $router->post('/courses/{id}/lessons/{lessonId}/delete', function ($id, $lessonId) {
        header('Content-Type: application/json');

        try {
            $lesson = \Core\Database::queryOne("
                SELECT l.id FROM lessons l
                JOIN modules m ON l.module_id = m.id
                WHERE l.id = ? AND m.course_id = ?
            ", [$lessonId, $id]);

            if (!$lesson) {
                echo json_encode(['success' => false, 'message' => 'Lesson not found']);
                exit;
            }

            // Delete lesson progress first
            \Core\Database::execute("DELETE FROM lesson_progress WHERE lesson_id = ?", [$lessonId]);
            \Core\Database::execute("DELETE FROM lessons WHERE id = ?", [$lessonId]);

            // Update course total_lessons count
            $totalLessons = \Core\Database::scalar("
                SELECT COUNT(*) FROM lessons l
                JOIN modules m ON l.module_id = m.id
                WHERE m.course_id = ?
            ", [$id]);
            \Core\Database::execute("UPDATE courses SET total_lessons = ? WHERE id = ?", [$totalLessons, $id]);

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    });

    // =============================================
    // MODULE MANAGEMENT
    // =============================================

    // Create module
    $router->post('/courses/{id}/modules', function ($id) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect("/admin/courses/{$id}/lessons");
        }

        $course = \Core\Database::queryOne("SELECT id FROM courses WHERE id = ?", [$id]);
        if (!$course) {
            flash('error', 'Course not found');
            redirect('/admin/courses');
        }

        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            flash('error', 'Module title is required');
            redirect("/admin/courses/{$id}/lessons");
        }

        // Get next sort order
        $maxOrder = \Core\Database::scalar("SELECT MAX(sort_order) FROM modules WHERE course_id = ?", [$id]) ?? 0;

        try {
            \Core\Database::execute("
                INSERT INTO modules (course_id, title, description, sort_order, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ", [
                $id,
                $title,
                $_POST['description'] ?? '',
                $maxOrder + 1
            ]);

            flash('success', 'Module created successfully');
        } catch (\Exception $e) {
            error_log("Module creation error: " . $e->getMessage());
            flash('error', 'Failed to create module');
        }

        redirect("/admin/courses/{$id}/lessons");
    });

    // Update module
    $router->post('/courses/{id}/modules/{moduleId}', function ($id, $moduleId) {
        if (!verify_csrf($_POST['_token'] ?? '')) {
            flash('error', 'Invalid request');
            redirect("/admin/courses/{$id}/lessons");
        }

        $module = \Core\Database::queryOne("SELECT id FROM modules WHERE id = ? AND course_id = ?", [$moduleId, $id]);
        if (!$module) {
            flash('error', 'Module not found');
            redirect("/admin/courses/{$id}/lessons");
        }

        try {
            \Core\Database::execute("
                UPDATE modules SET title = ?, description = ? WHERE id = ?
            ", [
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                $moduleId
            ]);

            flash('success', 'Module updated successfully');
        } catch (\Exception $e) {
            flash('error', 'Failed to update module');
        }

        redirect("/admin/courses/{$id}/lessons");
    });

    // Delete module
    $router->post('/courses/{id}/modules/{moduleId}/delete', function ($id, $moduleId) {
        header('Content-Type: application/json');

        try {
            $module = \Core\Database::queryOne("SELECT id FROM modules WHERE id = ? AND course_id = ?", [$moduleId, $id]);
            if (!$module) {
                echo json_encode(['success' => false, 'message' => 'Module not found']);
                exit;
            }

            // Delete all lessons in this module (and their progress)
            $lessonIds = \Core\Database::query("SELECT id FROM lessons WHERE module_id = ?", [$moduleId]);
            foreach ($lessonIds as $lesson) {
                \Core\Database::execute("DELETE FROM lesson_progress WHERE lesson_id = ?", [$lesson['id']]);
            }
            \Core\Database::execute("DELETE FROM lessons WHERE module_id = ?", [$moduleId]);
            \Core\Database::execute("DELETE FROM modules WHERE id = ?", [$moduleId]);

            // Update course total_lessons count
            $totalLessons = \Core\Database::scalar("
                SELECT COUNT(*) FROM lessons l
                JOIN modules m ON l.module_id = m.id
                WHERE m.course_id = ?
            ", [$id]);
            \Core\Database::execute("UPDATE courses SET total_lessons = ? WHERE id = ?", [$totalLessons, $id]);

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
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

        // Build query with filters
        $where = ['1=1'];
        $params = [];

        if (!empty($_GET['status'])) {
            $where[] = 'p.status = ?';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['method'])) {
            $where[] = 'p.payment_method = ?';
            $params[] = $_GET['method'];
        }
        if (!empty($_GET['search'])) {
            $where[] = '(u.email LIKE ? OR p.reference LIKE ?)';
            $searchTerm = '%' . $_GET['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);

        $total = (int) \Core\Database::scalar("
            SELECT COUNT(*) FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE {$whereClause}
        ", $params);

        // Add pagination params
        $params[] = $perPage;
        $params[] = $offset;

        $payments = \Core\Database::query("
            SELECT p.*, u.first_name, u.last_name, u.email,
                   sp.name as plan_name
            FROM payments p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN subscriptions s ON p.subscription_id = s.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ", $params);

        // Get stats
        $stats = \Core\Database::queryOne("
            SELECT
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) as this_month,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
            FROM payments
        ");

        View::render('admin/payments/index', [
            'title' => 'Payments',
            'payments' => $payments,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage),
            'totalPayments' => $total
        ], 'admin');
    });

    // Get single payment
    $router->get('/payments/{id}', function ($id) {
        $payment = \Core\Database::queryOne("
            SELECT p.*, u.first_name, u.last_name, u.email,
                   sp.name as plan_name
            FROM payments p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN subscriptions s ON p.subscription_id = s.id
            LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE p.id = ?
        ", [$id]);

        if (!$payment) {
            View::json(['success' => false, 'message' => 'Payment not found'], 404);
            return;
        }

        View::json(['success' => true, 'data' => $payment]);
    });

    // Approve payment
    $router->put('/payments/{id}/approve', function ($id) {
        // Get payment with user info
        $payment = \Core\Database::queryOne("
            SELECT p.*, u.first_name, u.email
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ", [$id]);

        if (!$payment) {
            View::json(['success' => false, 'message' => 'Payment not found'], 404);
            return;
        }

        if ($payment['status'] !== 'pending') {
            View::json(['success' => false, 'message' => 'Payment already processed'], 400);
            return;
        }

        // Update payment status
        \Core\Database::execute("
            UPDATE payments SET status = 'completed', paid_at = NOW() WHERE id = ?
        ", [$id]);

        // Activate subscription if exists
        $endDate = null;
        if ($payment['subscription_id']) {
            $subscription = \Core\Database::queryOne("SELECT * FROM subscriptions WHERE id = ?", [$payment['subscription_id']]);
            if ($subscription) {
                $plan = \Core\Database::queryOne("SELECT * FROM subscription_plans WHERE id = ?", [$subscription['plan_id']]);
                $durationDays = $plan['duration_days'] ?? 30;

                $endDate = date('Y-m-d', strtotime("+{$durationDays} days"));

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

        // Create in-app notification for user
        try {
            \Core\Database::execute("
                INSERT INTO notifications (user_id, title, message, type, data, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ", [
                $payment['user_id'],
                'Payment Approved!',
                'Your payment has been verified and your subscription is now active until ' . ($endDate ?? 'N/A') . '.',
                'payment',
                json_encode(['payment_id' => $id, 'end_date' => $endDate])
            ]);
        } catch (\Exception $e) {
            // Notification table might not exist, continue anyway
        }

        View::json(['success' => true, 'message' => 'Payment approved and subscription activated']);
    });

    // Legacy POST route for backward compatibility
    $router->post('/payments/{id}/approve', function ($id) {
        // Redirect to PUT handler
        $payment = \Core\Database::queryOne("
            SELECT p.*, u.first_name, u.email
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ", [$id]);

        if (!$payment) {
            View::json(['success' => false, 'message' => 'Payment not found'], 404);
            return;
        }

        if ($payment['status'] !== 'pending') {
            View::json(['success' => false, 'message' => 'Payment already processed'], 400);
            return;
        }

        \Core\Database::execute("UPDATE payments SET status = 'completed', paid_at = NOW() WHERE id = ?", [$id]);

        if ($payment['subscription_id']) {
            $subscription = \Core\Database::queryOne("SELECT * FROM subscriptions WHERE id = ?", [$payment['subscription_id']]);
            if ($subscription) {
                $plan = \Core\Database::queryOne("SELECT * FROM subscription_plans WHERE id = ?", [$subscription['plan_id']]);
                $durationDays = $plan['duration_days'] ?? 30;
                \Core\Database::execute("
                    UPDATE subscriptions SET status = 'active', start_date = CURDATE(), end_date = DATE_ADD(CURDATE(), INTERVAL ? DAY), updated_at = NOW() WHERE id = ?
                ", [$durationDays, $payment['subscription_id']]);
            }
        }

        View::json(['success' => true, 'message' => 'Payment approved']);
    });

    // Reject payment
    $router->put('/payments/{id}/reject', function ($id) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $reason = $input['reason'] ?? 'Payment could not be verified';

        // Get payment with user info
        $payment = \Core\Database::queryOne("
            SELECT p.*, u.first_name, u.email
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ", [$id]);

        if (!$payment) {
            View::json(['success' => false, 'message' => 'Payment not found'], 404);
            return;
        }

        if ($payment['status'] !== 'pending') {
            View::json(['success' => false, 'message' => 'Payment already processed'], 400);
            return;
        }

        // Update payment status
        \Core\Database::execute("
            UPDATE payments SET status = 'failed' WHERE id = ?
        ", [$id]);

        // Cancel subscription
        if ($payment['subscription_id']) {
            \Core\Database::execute("
                UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE id = ?
            ", [$payment['subscription_id']]);
        }

        // Create in-app notification for user
        try {
            \Core\Database::execute("
                INSERT INTO notifications (user_id, title, message, type, data, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ", [
                $payment['user_id'],
                'Payment Issue',
                'Your payment could not be verified. Reason: ' . $reason,
                'payment',
                json_encode(['payment_id' => $id, 'reason' => $reason])
            ]);
        } catch (\Exception $e) {
            // Notification table might not exist, continue anyway
        }

        View::json(['success' => true, 'message' => 'Payment rejected']);
    });

    // POST fallback for reject (for servers that don't support PUT)
    $router->post('/payments/{id}/reject', function ($id) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $reason = $input['reason'] ?? 'Payment could not be verified';

        $payment = \Core\Database::queryOne("
            SELECT p.*, u.first_name, u.email
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ", [$id]);

        if (!$payment) {
            View::json(['success' => false, 'message' => 'Payment not found'], 404);
            return;
        }

        if ($payment['status'] !== 'pending') {
            View::json(['success' => false, 'message' => 'Payment already processed'], 400);
            return;
        }

        \Core\Database::execute("UPDATE payments SET status = 'failed' WHERE id = ?", [$id]);

        if ($payment['subscription_id']) {
            \Core\Database::execute("
                UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE id = ?
            ", [$payment['subscription_id']]);
        }

        try {
            \Core\Database::execute("
                INSERT INTO notifications (user_id, title, message, type, data, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ", [
                $payment['user_id'],
                'Payment Issue',
                'Your payment could not be verified. Reason: ' . $reason,
                'payment',
                json_encode(['payment_id' => $id, 'reason' => $reason])
            ]);
        } catch (\Exception $e) {}

        View::json(['success' => true, 'message' => 'Payment rejected']);
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

    // Helper function to parse curriculum text into modules and lessons
    // Must be defined BEFORE routes that use it
    function parseCurriculumText($courseId, $text) {
        if (empty($text)) {
            error_log("parseCurriculumText: Empty text provided for course {$courseId}");
            return false;
        }

        $lines = explode("\n", $text);
        $currentModuleId = null;
        $moduleOrder = 0;
        $lessonOrder = 0;
        $modulesCreated = 0;
        $lessonsCreated = 0;

        error_log("parseCurriculumText: Processing " . count($lines) . " lines for course {$courseId}");

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check if this is a module line (starts with "Module")
            if (stripos($line, 'Module') === 0) {
                // Extract module title - remove "Module X:" prefix
                $moduleTitle = preg_replace('/^Module\s*\d*[:.]?\s*/i', '', $line);
                $moduleTitle = trim($moduleTitle);

                if (!empty($moduleTitle)) {
                    $moduleOrder++;
                    $lessonOrder = 0;

                    try {
                        \Core\Database::execute("
                            INSERT INTO ai_modules (course_id, title, sort_order, created_at)
                            VALUES (?, ?, ?, NOW())
                        ", [$courseId, $moduleTitle, $moduleOrder]);

                        $currentModuleId = \Core\Database::getConnection()->lastInsertId();
                        $modulesCreated++;
                        error_log("parseCurriculumText: Created module '{$moduleTitle}' with ID {$currentModuleId}");
                    } catch (\Exception $e) {
                        error_log("parseCurriculumText: Failed to create module '{$moduleTitle}': " . $e->getMessage());
                    }
                }
                continue;
            }

            // Check if this is a lesson line (starts with "-" or "Lesson")
            if ($currentModuleId && (strpos($line, '-') === 0 || stripos($line, 'Lesson') === 0)) {
                // Extract lesson title - remove "- " or "Lesson X:" prefix
                $lessonTitle = preg_replace('/^[-*]\s*/', '', $line);
                $lessonTitle = preg_replace('/^Lesson\s*\d*[:.]?\s*/i', '', $lessonTitle);
                $lessonTitle = trim($lessonTitle);

                if (!empty($lessonTitle)) {
                    $lessonOrder++;

                    try {
                        \Core\Database::execute("
                            INSERT INTO ai_lessons (module_id, title, sort_order, created_at)
                            VALUES (?, ?, ?, NOW())
                        ", [$currentModuleId, $lessonTitle, $lessonOrder]);
                        $lessonsCreated++;
                        error_log("parseCurriculumText: Created lesson '{$lessonTitle}' for module {$currentModuleId}");
                    } catch (\Exception $e) {
                        error_log("parseCurriculumText: Failed to create lesson '{$lessonTitle}': " . $e->getMessage());
                    }
                }
            }
        }

        error_log("parseCurriculumText: Completed - Created {$modulesCreated} modules and {$lessonsCreated} lessons");
        return ['modules' => $modulesCreated, 'lessons' => $lessonsCreated];
    }

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
        $isPremium = isset($_POST['is_premium']) ? 1 : 0;

        // Handle thumbnail upload
        $thumbnailPath = null;
        if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            // Use realpath to handle Windows paths correctly
            $publicDir = realpath(__DIR__ . '/../public');
            $uploadDir = $publicDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ai-courses' . DIRECTORY_SEPARATOR;

            error_log("AI Course thumbnail upload - Upload dir: " . $uploadDir);

            if (!is_dir($uploadDir)) {
                $created = @mkdir($uploadDir, 0755, true);
                error_log("AI Course thumbnail upload - Created dir: " . ($created ? 'yes' : 'no'));
            }

            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowedExts)) {
                $filename = $slug . '-' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;

                error_log("AI Course thumbnail upload - Target path: " . $targetPath);
                error_log("AI Course thumbnail upload - Temp file: " . $_FILES['thumbnail']['tmp_name']);

                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                    $thumbnailPath = '/uploads/ai-courses/' . $filename;
                    error_log("AI Course thumbnail upload - Success: " . $thumbnailPath);
                } else {
                    error_log("AI Course thumbnail upload - move_uploaded_file failed");
                }
            } else {
                error_log("AI Course thumbnail upload - Invalid extension: " . $ext);
            }
        } else {
            if (empty($_FILES['thumbnail']['name'])) {
                error_log("AI Course thumbnail upload - No file selected");
            } elseif ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
                error_log("AI Course thumbnail upload - Upload error code: " . $_FILES['thumbnail']['error']);
            }
        }

        try {
            $pdo = \Core\Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO ai_courses (title, slug, description, thumbnail, category_id, level, is_published, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $title,
                $slug,
                $_POST['description'] ?? '',
                $thumbnailPath,
                $categoryId,
                $_POST['level'] ?? 'beginner',
                $isPublished
            ]);

            $courseId = $pdo->lastInsertId();

            if ($courseId) {
                // Parse curriculum text if provided
                $curriculumText = trim($_POST['curriculum_text'] ?? '');
                $curriculumResult = null;
                if (!empty($curriculumText)) {
                    error_log("Store route: Calling parseCurriculumText for course {$courseId}");
                    error_log("Store route: Curriculum text length: " . strlen($curriculumText));
                    $curriculumResult = parseCurriculumText($courseId, $curriculumText);
                    error_log("Store route: parseCurriculumText returned: " . json_encode($curriculumResult));
                }

                if ($curriculumResult && $curriculumResult['modules'] > 0) {
                    flash('success', "AI Course created with {$curriculumResult['modules']} modules and {$curriculumResult['lessons']} lessons");
                } else {
                    flash('success', 'AI Course created successfully. Add modules and lessons manually.');
                }
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

        $course = \Core\Database::queryOne("SELECT * FROM ai_courses WHERE id = ?", [$id]);
        if (!$course) {
            flash('error', 'Course not found');
            redirect('/admin/ai-courses');
        }

        // Determine published status from 'status' field
        $isPublished = ($_POST['status'] ?? 'draft') === 'published' ? 1 : 0;

        // Handle thumbnail upload
        $thumbnailPath = $course['thumbnail']; // Keep existing if no new upload
        if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $publicDir = realpath(__DIR__ . '/../public');
            $uploadDir = $publicDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ai-courses' . DIRECTORY_SEPARATOR;

            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowedExts)) {
                // Generate slug for filename
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['title'] ?? 'course')));
                $filename = $slug . '-' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                    // Delete old thumbnail if exists
                    if (!empty($course['thumbnail'])) {
                        $oldPath = $publicDir . str_replace('/', DIRECTORY_SEPARATOR, $course['thumbnail']);
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $thumbnailPath = '/uploads/ai-courses/' . $filename;
                }
            }
        }

        \Core\Database::execute("
            UPDATE ai_courses SET
                title = ?,
                description = ?,
                thumbnail = ?,
                category_id = ?,
                level = ?,
                is_published = ?
            WHERE id = ?
        ", [
            $_POST['title'] ?? '',
            $_POST['description'] ?? '',
            $thumbnailPath,
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
