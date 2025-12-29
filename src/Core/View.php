<?php

namespace Core;

class View
{
    private static array $shared = [];

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], string $layout = 'app'): void
    {
        global $auth;

        // TEMPORARY DEBUG: Output at start of render
        echo "<!-- VIEW_RENDER_START template={$template} layout={$layout} -->\n";

        // Merge shared data
        $data = array_merge(self::$shared, $data);

        // Add common variables
        $data['auth'] = $auth;
        $data['user'] = $auth ? $auth->user() : null;
        $data['isAuthenticated'] = $auth ? $auth->check() : false;
        $data['isAdmin'] = $auth ? $auth->isAdmin() : false;
        $data['isSubscribed'] = $auth ? $auth->isSubscribed() : false;
        $data['csrfToken'] = csrf_token();

        // Flash messages
        $data['success'] = flash('success');
        $data['error'] = flash('error');
        $data['warning'] = flash('warning');
        $data['info'] = flash('info');

        // Extract data to variables
        extract($data);

        // Check if output has already been sent
        if (ob_get_level() > 0) {
            // Clean any existing output buffers
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        // Capture template content
        ob_start();
        $templatePath = TEMPLATES_PATH . '/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($templatePath)) {
            ob_end_clean();
            throw new \Exception("Template not found: {$template}");
        }

        require $templatePath;
        $content = ob_get_clean();

        // Render with layout or just content
        if ($layout) {
            $layoutPath = TEMPLATES_PATH . '/layouts/' . $layout . '.php';

            // DEBUG: Check if layout path is correct
            if (!file_exists($layoutPath)) {
                // Fallback: output content directly with basic HTML wrapper
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><link rel="stylesheet" href="/css/app.css"></head><body>';
                echo '<!-- DEBUG: Layout not found at: ' . htmlspecialchars($layoutPath) . ' -->';
                echo $content;
                echo '</body></html>';
                return;
            }

            // Ensure $content is available in the layout scope
            $__content = $content;

            // DEBUG: About to include layout
            echo "<!-- VIEW_RENDER_LAYOUT_ABOUT_TO_INCLUDE path={$layoutPath} exists=" . (file_exists($layoutPath) ? 'yes' : 'no') . " -->\n";

            // Include layout
            require $layoutPath;

            // DEBUG: After layout included
            echo "<!-- VIEW_RENDER_LAYOUT_INCLUDED -->\n";
        } else {
            echo $content;
        }
    }

    public static function component(string $name, array $data = []): void
    {
        extract($data);

        $componentPath = TEMPLATES_PATH . '/components/' . $name . '.php';

        if (!file_exists($componentPath)) {
            throw new \Exception("Component not found: {$name}");
        }

        require $componentPath;
    }

    public static function partial(string $name, array $data = []): void
    {
        extract($data);

        $partialPath = TEMPLATES_PATH . '/partials/' . $name . '.php';

        if (!file_exists($partialPath)) {
            throw new \Exception("Partial not found: {$name}");
        }

        require $partialPath;
    }

    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function error(int $code, string $message = ''): void
    {
        http_response_code($code);

        $errorPath = TEMPLATES_PATH . '/errors/' . $code . '.php';

        if (file_exists($errorPath)) {
            $data = ['message' => $message];
            extract($data);
            require $errorPath;
        } else {
            echo "Error {$code}: {$message}";
        }

        exit;
    }
}
