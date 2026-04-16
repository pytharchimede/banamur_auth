<?php
require_once __DIR__ . '/EnvConfig.php';

// Évite une redéclaration si un autre fichier a déjà défini Database (ex: decaissement/model/Database.php)
if (!class_exists('Database')) {
    class Database
    {
        private static $pdo;

        public static function getConnection()
        {
            if (self::$pdo === null) {
                try {
                    \EnvConfig::loadFromDirectory(__DIR__);
                    $host = \EnvConfig::get('PROJECT_DB_HOST', 'localhost');
                    $name = \EnvConfig::get('PROJECT_DB_NAME', 'fidestci_projet_db');
                    $user = \EnvConfig::get('PROJECT_DB_USER', 'fidestci_ulrich');
                    $pass = \EnvConfig::get('PROJECT_DB_PASS', '@Succes2019');

                    self::$pdo = new PDO(
                        "mysql:host={$host};dbname={$name};charset=utf8mb4",
                        $user,
                        $pass,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                        ]
                    );
                } catch (PDOException $e) {
                    die('Database connection failed: ' . $e->getMessage());
                }
            }
            return self::$pdo;
        }
    }
}
