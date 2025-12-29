<?php

namespace Core;

class Router
{
    private array $routes = [];
    private array $groupStack = [];
    private string $currentGroupPrefix = '';
    private array $currentGroupMiddleware = [];

    public function get(string $path, $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function group(array $options, callable $callback): void
    {
        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddleware = $this->currentGroupMiddleware;

        if (isset($options['prefix'])) {
            $this->currentGroupPrefix .= '/' . trim($options['prefix'], '/');
        }

        if (isset($options['middleware'])) {
            $middleware = is_array($options['middleware']) ? $options['middleware'] : [$options['middleware']];
            $this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middleware);
        }

        $callback($this);

        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, $handler): self
    {
        $fullPath = $this->currentGroupPrefix . '/' . trim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');

        // Normalize root path
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $this->currentGroupMiddleware,
            'pattern' => $this->pathToPattern($fullPath)
        ];

        return $this;
    }

    private function pathToPattern(string $path): string
    {
        // Convert route parameters to regex
        // {id} becomes (?P<id>[^/]+)
        // {id:\d+} becomes (?P<id>\d+)
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            function ($matches) {
                $name = $matches[1];
                $regex = $matches[2] ?? '[^/]+';
                return '(?P<' . $name . '>' . $regex . ')';
            },
            $path
        );

        return '#^' . $pattern . '$#';
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove trailing slash except for root
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $middleware) {
                    if (!$this->runMiddleware($middleware)) {
                        return;
                    }
                }

                // Execute handler
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // No route found
        $this->notFound();
    }

    private function runMiddleware(string $middleware): bool
    {
        global $auth;

        switch ($middleware) {
            case 'auth':
                if (!$auth->check()) {
                    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
                    redirect('/login');
                    return false;
                }
                break;

            case 'guest':
                if ($auth->check()) {
                    redirect('/');
                    return false;
                }
                break;

            case 'admin':
                if (!$auth->check() || !$auth->isAdmin()) {
                    http_response_code(403);
                    echo "Access denied";
                    return false;
                }
                break;

            case 'subscribed':
                if (!$auth->check() || !$auth->isSubscribed()) {
                    redirect('/subscription');
                    return false;
                }
                break;
        }

        return true;
    }

    private function executeHandler($handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } elseif (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            call_user_func_array([$controller, $method], $params);
        } elseif (is_string($handler)) {
            [$class, $method] = explode('@', $handler);
            $controller = new $class();
            call_user_func_array([$controller, $method], $params);
        }
    }

    private function notFound(): void
    {
        http_response_code(404);
        require TEMPLATES_PATH . '/errors/404.php';
    }
}
