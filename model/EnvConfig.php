<?php

if (!class_exists('EnvConfig')) {
    class EnvConfig
    {
        private static bool $loaded = false;

        public static function loadFromDirectory(string $startDir): void
        {
            if (self::$loaded) {
                return;
            }

            $directory = realpath($startDir) ?: $startDir;
            while (is_string($directory) && $directory !== '') {
                $envPath = $directory . DIRECTORY_SEPARATOR . '.env';
                if (is_file($envPath)) {
                    self::loadFile($envPath);
                    self::$loaded = true;
                    return;
                }

                $parent = dirname($directory);
                if ($parent === $directory) {
                    break;
                }
                $directory = $parent;
            }

            self::$loaded = true;
        }

        public static function get(string $key, ?string $default = null): ?string
        {
            self::loadFromDirectory(__DIR__);

            $value = getenv($key);
            if ($value !== false) {
                return $value;
            }

            if (array_key_exists($key, $_ENV)) {
                return (string) $_ENV[$key];
            }

            if (array_key_exists($key, $_SERVER)) {
                return (string) $_SERVER[$key];
            }

            return $default;
        }

        private static function loadFile(string $path): void
        {
            $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!is_array($lines)) {
                return;
            }

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                $key = trim($parts[0]);
                if ($key === '') {
                    continue;
                }

                $value = trim($parts[1]);
                if ($value !== '') {
                    $firstChar = $value[0];
                    $lastChar = substr($value, -1);
                    if (($firstChar === '"' && $lastChar === '"') || ($firstChar === '\'' && $lastChar === '\'')) {
                        $value = substr($value, 1, -1);
                    }
                }

                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
