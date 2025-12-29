<?php

namespace Core;

class Auth
{
    private ?string $token = null;
    private ?string $refreshToken = null;
    private ?array $user = null;
    private ApiClient $api;

    public function __construct()
    {
        $this->token = $_COOKIE['learnrail_token'] ?? null;
        $this->refreshToken = $_COOKIE['learnrail_refresh'] ?? null;
        $this->api = new ApiClient($this->token);

        if ($this->token) {
            $this->loadUser();
        }
    }

    private function loadUser(): void
    {
        // Check if user is cached in session
        if (isset($_SESSION['user']) && isset($_SESSION['user_loaded_at'])) {
            // Cache user for 5 minutes to reduce API calls
            if (time() - $_SESSION['user_loaded_at'] < 300) {
                $this->user = $_SESSION['user'];
                return;
            }
        }

        // Fetch from API
        $response = $this->api->get('/auth/me');

        if ($response['success'] && isset($response['data'])) {
            $this->user = $response['data'];
            $_SESSION['user'] = $this->user;
            $_SESSION['user_loaded_at'] = time();
        } else {
            // Token might be expired, try to refresh
            if ($this->refreshToken) {
                $this->refreshAccessToken();
            } else {
                $this->clearAuth();
            }
        }
    }

    private function refreshAccessToken(): void
    {
        $api = new ApiClient($this->refreshToken);
        $response = $api->post('/auth/refresh', [
            'refresh_token' => $this->refreshToken
        ]);

        if ($response['success'] && isset($response['data']['token'])) {
            $this->setTokenCookies(
                $response['data']['token'],
                $response['data']['refresh_token'] ?? $this->refreshToken
            );
            $this->token = $response['data']['token'];
            $this->api = new ApiClient($this->token);
            $this->loadUser();
        } else {
            $this->clearAuth();
        }
    }

    public function login(string $email, string $password): array
    {
        $api = new ApiClient();
        $response = $api->post('/auth/login', [
            'email' => $email,
            'password' => $password
        ]);

        if ($response['success'] && isset($response['data']['token'])) {
            $this->setTokenCookies(
                $response['data']['token'],
                $response['data']['refresh_token'] ?? ''
            );

            $this->user = $response['data']['user'] ?? null;
            $_SESSION['user'] = $this->user;
            $_SESSION['user_loaded_at'] = time();

            return ['success' => true, 'user' => $this->user];
        }

        return [
            'success' => false,
            'message' => $response['data']['message'] ?? 'Login failed'
        ];
    }

    public function register(array $data): array
    {
        $api = new ApiClient();
        $response = $api->post('/auth/register', $data);

        if ($response['success'] && isset($response['data']['token'])) {
            $this->setTokenCookies(
                $response['data']['token'],
                $response['data']['refresh_token'] ?? ''
            );

            $this->user = $response['data']['user'] ?? null;
            $_SESSION['user'] = $this->user;
            $_SESSION['user_loaded_at'] = time();

            return ['success' => true, 'user' => $this->user];
        }

        return [
            'success' => false,
            'message' => $response['data']['message'] ?? 'Registration failed',
            'errors' => $response['data']['errors'] ?? []
        ];
    }

    public function forgotPassword(string $email): array
    {
        $api = new ApiClient();
        $response = $api->post('/auth/forgot-password', ['email' => $email]);

        return [
            'success' => $response['success'],
            'message' => $response['data']['message'] ?? ($response['success'] ? 'Password reset email sent' : 'Failed to send reset email')
        ];
    }

    public function logout(): void
    {
        if ($this->token) {
            $this->api->post('/auth/logout');
        }
        $this->clearAuth();
    }

    private function setTokenCookies(string $token, string $refreshToken): void
    {
        $tokenExpiry = time() + (86400 * 7); // 7 days
        $refreshExpiry = time() + (86400 * 30); // 30 days

        $cookieOptions = [
            'path' => '/',
            'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
            'secure' => defined('COOKIE_SECURE') ? COOKIE_SECURE : true,
            'httponly' => defined('COOKIE_HTTPONLY') ? COOKIE_HTTPONLY : true,
            'samesite' => defined('COOKIE_SAMESITE') ? COOKIE_SAMESITE : 'Lax'
        ];

        setcookie('learnrail_token', $token, array_merge($cookieOptions, ['expires' => $tokenExpiry]));

        if ($refreshToken) {
            setcookie('learnrail_refresh', $refreshToken, array_merge($cookieOptions, ['expires' => $refreshExpiry]));
        }

        $this->token = $token;
        $this->refreshToken = $refreshToken;
    }

    private function clearAuth(): void
    {
        $cookieOptions = [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
            'secure' => defined('COOKIE_SECURE') ? COOKIE_SECURE : true,
            'httponly' => defined('COOKIE_HTTPONLY') ? COOKIE_HTTPONLY : true,
            'samesite' => defined('COOKIE_SAMESITE') ? COOKIE_SAMESITE : 'Lax'
        ];

        setcookie('learnrail_token', '', $cookieOptions);
        setcookie('learnrail_refresh', '', $cookieOptions);

        unset($_SESSION['user']);
        unset($_SESSION['user_loaded_at']);

        $this->token = null;
        $this->refreshToken = null;
        $this->user = null;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user['id'] ?? null;
    }

    public function isAdmin(): bool
    {
        return $this->user && ($this->user['role'] ?? '') === 'admin';
    }

    public function isSubscribed(): bool
    {
        if (!$this->user) {
            return false;
        }

        $subscription = $this->user['subscription'] ?? $this->user['active_subscription'] ?? null;
        if (!$subscription) {
            return false;
        }

        return ($subscription['status'] ?? '') === 'active';
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function refreshUser(): void
    {
        unset($_SESSION['user']);
        unset($_SESSION['user_loaded_at']);
        $this->loadUser();
    }
}
