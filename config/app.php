<?php
/**
 * Application Configuration
 * Learnrail Web App
 */

// Error reporting - TEMP: display errors to debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');

// Timezone
date_default_timezone_set('UTC');

// Application settings
define('APP_NAME', 'Learnrail');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production');
define('APP_DEBUG', false);
define('APP_URL', 'https://app.learnrail.org');

// API Configuration
define('API_BASE_URL', 'https://api.learnrail.org/api');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('SRC_PATH', ROOT_PATH . '/src');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Session Configuration
define('SESSION_NAME', 'learnrail_session');
define('SESSION_LIFETIME', 86400 * 7); // 7 days

// Cookie Configuration
define('COOKIE_DOMAIN', '.learnrail.org');
define('COOKIE_SECURE', true);
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax');

// CSRF Token
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper functions
function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_field(): string {
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function verify_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function back(): void {
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($referer);
}

function old(string $key, string $default = ''): string {
    return htmlspecialchars($_SESSION['old_input'][$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, $value = null) {
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
    } else {
        $flash = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
}

function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string {
    return '/' . ltrim($path, '/');
}

function url(string $path = ''): string {
    return APP_URL . '/' . ltrim($path, '/');
}

function format_date(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

function format_currency(float $amount, string $currency = 'NGN'): string {
    $symbols = ['NGN' => '₦', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

function format_duration(int $minutes): string {
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
}

function time_ago(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';

    return date('M d, Y', $time);
}

// Email Configuration (same as API)
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'mail.learnrail.org');
define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 587));
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'noreply@learnrail.org');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@learnrail.org');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Learnrail');
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'ssl');

/**
 * Send email using PHPMailer
 */
function sendEmail(string $to, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        // Try to load composer autoload
        $autoloadPath = ROOT_PATH . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        } else {
            error_log("PHPMailer not installed. Run 'composer install' in web app directory.");
            return false;
        }
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->addReplyTo(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("Email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send payment approval email
 */
function sendPaymentApprovalEmail(string $email, string $firstName, float $amount, ?string $endDate): bool {
    $formattedAmount = format_currency($amount);
    $formattedEndDate = $endDate ? format_date($endDate, 'F j, Y') : 'N/A';
    $year = date('Y');

    $subject = 'Payment Approved - Your Subscription is Active!';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f3f4f6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" style="max-width: 600px; width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="background: linear-gradient(135deg, #10B981, #059669); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                            <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">Payment Approved!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #ffffff; padding: 40px 30px; border-radius: 0 0 10px 10px;">
                            <h2 style="margin: 0 0 20px; color: #1f2937; font-size: 22px;">Hi {$firstName},</h2>
                            <p style="margin: 0 0 20px; color: #4b5563;">Great news! Your payment of <strong>{$formattedAmount}</strong> has been verified and approved.</p>

                            <div style="background: #D1FAE5; padding: 20px; border-radius: 8px; margin: 20px 0;">
                                <p style="margin: 0; color: #065F46; font-size: 16px;">
                                    <strong>Your subscription is now active!</strong><br>
                                    Valid until: <strong>{$formattedEndDate}</strong>
                                </p>
                            </div>

                            <p style="margin: 20px 0; color: #4b5563;">You now have full access to all premium features including:</p>
                            <ul style="color: #4b5563; padding-left: 20px;">
                                <li>Unlimited Course Access</li>
                                <li>Goal Tracking</li>
                                <li>Accountability Partner</li>
                                <li>AI Career Assistant</li>
                            </ul>

                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-top: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="https://app.learnrail.org/dashboard" style="display: inline-block; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Start Learning</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 30px 0 0; color: #4b5563;">Happy Learning!<br><strong>The Learnrail Team</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; text-align: center;">
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">&copy; {$year} Learnrail. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    $textBody = "Hi {$firstName},\n\nGreat news! Your payment of {$formattedAmount} has been verified and approved.\n\nYour subscription is now active until {$formattedEndDate}.\n\nYou now have full access to all premium features.\n\nStart learning: https://app.learnrail.org/dashboard\n\nHappy Learning!\nThe Learnrail Team";

    return sendEmail($email, $firstName, $subject, $htmlBody, $textBody);
}

/**
 * Send payment rejection email
 */
function sendPaymentRejectionEmail(string $email, string $firstName, float $amount, string $reason): bool {
    $formattedAmount = format_currency($amount);
    $year = date('Y');

    $subject = 'Payment Could Not Be Verified';

    $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f3f4f6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" style="max-width: 600px; width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="background: #EF4444; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                            <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">Payment Issue</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #ffffff; padding: 40px 30px; border-radius: 0 0 10px 10px;">
                            <h2 style="margin: 0 0 20px; color: #1f2937; font-size: 22px;">Hi {$firstName},</h2>
                            <p style="margin: 0 0 20px; color: #4b5563;">We were unable to verify your payment of <strong>{$formattedAmount}</strong>.</p>

                            <div style="background: #FEE2E2; padding: 20px; border-radius: 8px; margin: 20px 0;">
                                <p style="margin: 0; color: #991B1B; font-size: 14px;">
                                    <strong>Reason:</strong><br>
                                    {$reason}
                                </p>
                            </div>

                            <p style="margin: 20px 0; color: #4b5563;">If you believe this is an error, please:</p>
                            <ol style="color: #4b5563; padding-left: 20px;">
                                <li>Double-check your payment was sent to the correct account</li>
                                <li>Ensure you used the correct payment reference</li>
                                <li>Try uploading a clearer receipt image</li>
                                <li>Contact our support team for assistance</li>
                            </ol>

                            <table role="presentation" style="width: 100%; border-collapse: collapse; margin-top: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="https://app.learnrail.org/subscription" style="display: inline-block; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">Try Again</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 30px 0 0; color: #6b7280; font-size: 14px;">Need help? Reply to this email or contact us at support@learnrail.org</p>

                            <p style="margin: 20px 0 0; color: #4b5563;">Best regards,<br><strong>The Learnrail Team</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; text-align: center;">
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">&copy; {$year} Learnrail. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    $textBody = "Hi {$firstName},\n\nWe were unable to verify your payment of {$formattedAmount}.\n\nReason: {$reason}\n\nIf you believe this is an error, please contact our support team.\n\nTry again: https://app.learnrail.org/subscription\n\nBest regards,\nThe Learnrail Team";

    return sendEmail($email, $firstName, $subject, $htmlBody, $textBody);
}
