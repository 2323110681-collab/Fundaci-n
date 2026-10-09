<?php

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        $base = __DIR__ . '/..';
        $paths = [
            $base . '/Core/' . $class . '.php',
            $base . '/Controllers/' . $class . '.php',
            $base . '/Models/' . $class . '.php',
            // Necesaria: nosotros.php y mensaje-presidente.php usan Database
            // sin incluir config/database.php de forma explicita.
            $base . '/../config/' . $class . '.php',
            $base . '/Helpers/' . $class . '.php',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }
}
