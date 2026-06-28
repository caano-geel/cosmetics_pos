<?php
/**
 * Application bootstrap.
 *
 * Database config priority:
 * 1. initialize.local.php (optional local overrides)
 * 2. Environment variables (Render / Docker): DB_HOST, DB_USER, DB_PASSWORD, DB_NAME
 * 3. Local XAMPP when host is localhost
 * 4. InfinityFree + initialize.production.php (optional password file on server)
 */
if (!defined('base_app')) {
    define('base_app', str_replace('\\', '/', __DIR__) . '/');
}

if (!function_exists('app_env')) {
    function app_env($key, $default = '')
    {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        return $default;
    }
}

if (!function_exists('app_uses_env_database')) {
    function app_uses_env_database()
    {
        return app_env('DB_HOST') !== '' || app_env('DB_NAME') !== '';
    }
}

$__http_host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
$__is_local = in_array($__http_host, array('localhost', '127.0.0.1'), true)
    || strpos($__http_host, 'localhost:') === 0
    || strpos($__http_host, '127.0.0.1:') === 0;

if (is_file(__DIR__ . '/initialize.local.php')) {
    require_once __DIR__ . '/initialize.local.php';
}

if (!defined('APP_ENV')) {
    $env_app = app_env('APP_ENV');
    if ($env_app !== '') {
        define('APP_ENV', $env_app);
    } else {
        define('APP_ENV', $__is_local ? 'local' : 'production');
    }
}

if (!defined('DB_SERVER')) {
    if (app_uses_env_database()) {
        define('DB_SERVER', app_env('DB_HOST', 'localhost'));
        define('DB_USERNAME', app_env('DB_USER', 'root'));
        define('DB_PASSWORD', app_env('DB_PASSWORD', ''));
        define('DB_NAME', app_env('DB_NAME', 'cbpos_db'));
    } elseif (APP_ENV === 'local' || $__is_local) {
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', '');
        define('DB_NAME', 'cbpos_db');
    } else {
        define('DB_SERVER', 'sql305.infinityfree.com');
        define('DB_USERNAME', 'if0_42288113');
        define('DB_NAME', 'if0_42288113_cbpos_db');
        if (is_file(__DIR__ . '/initialize.production.php')) {
            require_once __DIR__ . '/initialize.production.php';
        }
        if (!defined('DB_PASSWORD')) {
            define('DB_PASSWORD', '');
        }
    }
}

if (!defined('DB_HOST')) {
    define('DB_HOST', DB_SERVER);
}

if (!function_exists('app_resolve_base_url')) {
    function app_resolve_base_url()
    {
        $forced = app_env('APP_URL');
        if ($forced !== '') {
            return rtrim($forced, '/') . '/';
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
        $app_root = str_replace('\\', '/', realpath(__DIR__));
        $path = '/';
        if ($doc_root && $app_root && strpos($app_root, $doc_root) === 0) {
            $rel = substr($app_root, strlen($doc_root));
            $rel = trim(str_replace('\\', '/', $rel), '/');
            $path = $rel === '' ? '/' : '/' . $rel . '/';
        }
        return $scheme . '://' . $host . $path;
    }
}

if (!defined('base_url')) {
    define('base_url', app_resolve_base_url());
}

if (APP_ENV === 'production') {
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
    @error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
} else {
    @ini_set('display_errors', '1');
    @error_reporting(E_ALL);
}
