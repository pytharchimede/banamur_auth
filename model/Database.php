<?php
require_once __DIR__ . '/EnvConfig.php';

if (!class_exists('Database')) {
    class Database
    {
        private static $serverPdo = null;
        private static $pdo = null;

        public static function getServerConnection()
        {
            if (self::$serverPdo === null) {
                try {
                    \EnvConfig::loadFromDirectory(__DIR__);

                    self::$serverPdo = new PDO(
                        'mysql:host=' . self::getHost() . ';charset=utf8mb4',
                        self::getUser(),
                        self::getPassword(),
                        self::getOptions()
                    );
                } catch (PDOException $exception) {
                    throw new RuntimeException('Connexion serveur MySQL impossible: ' . $exception->getMessage(), 0, $exception);
                }
            }

            return self::$serverPdo;
        }

        public static function databaseExists($databaseName)
        {
            $statement = self::getServerConnection()->prepare(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :database_name'
            );
            $statement->execute(['database_name' => $databaseName]);

            return (bool) $statement->fetchColumn();
        }

        public static function ensureDatabaseExists()
        {
            $databaseName = self::getDatabaseName();

            if (self::databaseExists($databaseName)) {
                return;
            }

            $quotedDatabaseName = str_replace('`', '``', $databaseName);
            self::getServerConnection()->exec(
                'CREATE DATABASE IF NOT EXISTS `' . $quotedDatabaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
        }

        public static function getConnection()
        {
            if (self::$pdo === null) {
                try {
                    self::ensureDatabaseExists();

                    self::$pdo = new PDO(
                        'mysql:host=' . self::getHost() . ';dbname=' . self::getDatabaseName() . ';charset=utf8mb4',
                        self::getUser(),
                        self::getPassword(),
                        self::getOptions()
                    );
                } catch (PDOException $exception) {
                    throw new RuntimeException('Connexion a la base impossible: ' . $exception->getMessage(), 0, $exception);
                }
            }

            return self::$pdo;
        }

        private static function getHost()
        {
            return \EnvConfig::get('PROJECT_DB_HOST', 'localhost');
        }

        private static function getDatabaseName()
        {
            return \EnvConfig::get('PROJECT_DB_NAME', 'banamur_auth');
        }

        private static function getUser()
        {
            return \EnvConfig::get('PROJECT_DB_USER', 'root');
        }

        private static function getPassword()
        {
            return \EnvConfig::get('PROJECT_DB_PASS', '');
        }

        private static function getOptions()
        {
            return [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            ];
        }
    }
}
