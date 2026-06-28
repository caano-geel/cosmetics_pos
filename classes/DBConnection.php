<?php
if (!defined('DB_SERVER')) {
    require_once('../initialize.php');
}

class DBConnection
{
    public $conn;

    public function __construct()
    {
        if (!isset($this->conn)) {
            $this->conn = $this->open_connection();

            if ($this->conn->connect_errno) {
                if (defined('APP_ENV') && APP_ENV === 'production') {
                    error_log(sprintf(
                        'Database connection failed (host=%s port=%d ssl=%s): %s',
                        defined('DB_SERVER') ? DB_SERVER : '?',
                        defined('DB_PORT') ? (int) DB_PORT : 0,
                        (defined('DB_SSL') && DB_SSL) ? 'yes' : 'no',
                        $this->conn->connect_error
                    ));
                    exit('Database connection error. Please try again later.');
                }
                exit('Cannot connect to database server: ' . $this->conn->connect_error);
            }
        }
    }

    private function connection_port()
    {
        return defined('DB_PORT') ? (int) DB_PORT : 3306;
    }

    private function connection_use_ssl()
    {
        return defined('DB_SSL') && DB_SSL;
    }

    private function connection_ssl_ca()
    {
        if (!defined('DB_SSL_CA') || DB_SSL_CA === '') {
            return '';
        }
        $ca = DB_SSL_CA;
        if (!is_file($ca) && defined('base_app') && is_file(base_app . $ca)) {
            $ca = base_app . $ca;
        }
        return $ca;
    }

    private function open_connection()
    {
        $host = DB_SERVER;
        $user = DB_USERNAME;
        $pass = DB_PASSWORD;
        $name = DB_NAME;
        $port = $this->connection_port();

        if ($this->connection_use_ssl() && function_exists('mysqli_init')) {
            $mysqli = mysqli_init();
            if (!$mysqli) {
                exit('Cannot initialize database connection.');
            }

            if (defined('MYSQLI_OPT_CONNECT_TIMEOUT')) {
                mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 15);
            }

            $ca = $this->connection_ssl_ca();
            if ($ca !== '' && is_file($ca)) {
                mysqli_ssl_set($mysqli, null, null, $ca, null, null);
            } else {
                mysqli_ssl_set($mysqli, null, null, null, null, null);
            }

            $flags = defined('MYSQLI_CLIENT_SSL') ? MYSQLI_CLIENT_SSL : 0;
            $connected = mysqli_real_connect(
                $mysqli,
                $host,
                $user,
                $pass,
                $name,
                $port,
                null,
                $flags
            );

            if (!$connected) {
                $error = mysqli_connect_error();
                if (defined('APP_ENV') && APP_ENV === 'production') {
                    error_log(sprintf(
                        'Database SSL connection failed (host=%s port=%d): %s',
                        $host,
                        $port,
                        $error
                    ));
                    exit('Database connection error. Please try again later.');
                }
                exit('Cannot connect to database server: ' . $error);
            }

            return $mysqli;
        }

        return new mysqli($host, $user, $pass, $name, $port);
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
