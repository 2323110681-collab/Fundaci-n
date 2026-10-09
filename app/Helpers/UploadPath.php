<?php

/**
 * Rutas de la carpeta public/uploads.
 *
 * Esa carpeta siempre cuelga de la raiz publica del sitio, pero la raiz cambia:
 * en local puede ser /fundaciondu/public y en produccion ser "/". Por eso la
 * URL se deduce de SCRIPT_NAME en cada peticion en lugar de tenerla escrita en
 * el codigo, y las validaciones aceptan cualquier prefijo que termine en
 * /uploads/<archivo>. Asi el proyecto funciona igual en cualquier dominio o
 * carpeta sin volver a tocar el codigo.
 */
class UploadPath
{
    public const DIR = 'uploads';

    /** Ruta web de la carpeta de subidas: /uploads o /fundaciondu/public/uploads. */
    public static function baseUrl(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir = rtrim(dirname($script), '/');
        return $dir . '/' . self::DIR;
    }

    /** Ruta web de un archivo que ya vive en la carpeta de subidas. */
    public static function fileUrl(string $name): string
    {
        return self::baseUrl() . '/' . ltrim($name, '/');
    }

    /**
     * Ruta en disco de un archivo de la carpeta de subidas, o null si el nombre
     * no es seguro, se sale de la carpeta o el archivo no existe.
     */
    public static function diskPath(string $name): ?string
    {
        $safe = basename($name);
        if ($safe === '' || in_array($safe, ['.', '..'], true)) {
            return null;
        }

        $uploadDir = realpath(dirname(__DIR__, 2) . '/public/' . self::DIR);
        if ($uploadDir === false) {
            return null;
        }

        $file = realpath($uploadDir . DIRECTORY_SEPARATOR . $safe);
        if ($file === false || !is_file($file) || strpos($file, $uploadDir . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return $file;
    }

    /**
     * Indica si un valor es una ruta web de un archivo dentro de uploads.
     * Se acepta el prefijo que sea, siempre que termine en /uploads/<archivo>.
     */
    public static function isManagedPath($path): bool
    {
        if (!is_string($path) || $path === '') {
            return false;
        }
        if (preg_match('#/uploads/([A-Za-z0-9_.-]+)$#D', $path) !== 1) {
            return false;
        }
        return !in_array(basename($path), ['.', '..'], true);
    }

    /** Reescribe una ruta guardada con el prefijo que usa el sitio ahora mismo. */
    public static function normalize(string $path): string
    {
        return self::fileUrl(basename($path));
    }
}
