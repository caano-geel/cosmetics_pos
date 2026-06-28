<?php
/**
 * Lightweight .env loader (no Composer dependency).
 */
if (!defined('base_app')) {
    return;
}

if (!function_exists('app_load_dotenv')) {
    function app_load_dotenv()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $file = base_app . '.env';
        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if (getenv($key) === false || getenv($key) === '') {
                putenv($key . '=' . $value);
            }
            if (!isset($_ENV[$key]) || $_ENV[$key] === '') {
                $_ENV[$key] = $value;
            }
            if (!isset($_SERVER[$key]) || $_SERVER[$key] === '') {
                $_SERVER[$key] = $value;
            }
        }
    }
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

if (!function_exists('app_dotenv_has_database')) {
    function app_dotenv_has_database()
    {
        return app_env('DB_HOST') !== '' || app_env('DB_NAME') !== '';
    }
}
