<?php

define('DB_HOST',    'localhost');
define('DB_NAME',    'PlantCareDB');
define('DB_USER',    'root');       
define('DB_PASS',    '');          
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?mysqli $instance = null;

    public static function getConnection(): mysqli {
        if (self::$instance === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset(DB_CHARSET);

            self::$instance = $conn;
        }
        return self::$instance;
    }

    private function __clone() {}
    public function __wakeup() {}
}
