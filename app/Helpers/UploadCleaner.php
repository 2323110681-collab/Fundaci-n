<?php

require_once __DIR__ . '/UploadPath.php';

/**
 * Borra archivos de public/uploads que ya no usa ningún registro.
 * Solo toca archivos que estén dentro de esa carpeta y que ningún otro registro siga usando.
 */
class UploadCleaner
{
    public static function isManagedPath($path): bool
    {
        return UploadPath::isManagedPath($path);
    }

    /** Extrae las fotos guardadas de una lista de docentes (JSON o arreglo). */
    public static function teacherPhotos($docentes): array
    {
        if (is_string($docentes)) {
            $docentes = json_decode($docentes, true);
        }
        if (!is_array($docentes)) {
            return [];
        }
        $photos = [];
        foreach ($docentes as $docente) {
            if (is_array($docente) && !empty($docente['foto']) && is_string($docente['foto'])) {
                $photos[] = $docente['foto'];
            }
        }
        return $photos;
    }

    private static function isReferenced(PDO $db, string $path): bool
    {
        $queries = [
            'SELECT 1 FROM noticias WHERE imagen = :p LIMIT 1',
            'SELECT 1 FROM eventos WHERE imagen = :p LIMIT 1',
            'SELECT 1 FROM programas WHERE imagen = :p LIMIT 1',
            'SELECT 1 FROM miembros WHERE imagen = :p LIMIT 1',
            'SELECT 1 FROM convenios WHERE imagen = :p LIMIT 1',
            'SELECT 1 FROM programas WHERE INSTR(docentes, :p) > 0 LIMIT 1',
        ];
        foreach ($queries as $sql) {
            try {
                $stmt = $db->prepare($sql);
                $stmt->execute([':p' => $path]);
                if ($stmt->fetchColumn() !== false) {
                    return true;
                }
            } catch (Throwable $e) {
                // Ante cualquier duda, no se borra nada.
                return true;
            }
        }
        return false;
    }

    public static function deleteIfUnused(PDO $db, $path): void
    {
        if (!self::isManagedPath($path) || self::isReferenced($db, $path)) {
            return;
        }

        $file = UploadPath::diskPath($path);
        if ($file === null) {
            return;
        }

        @unlink($file);
    }

    public static function deleteManyIfUnused(PDO $db, array $paths): void
    {
        foreach (array_unique(array_filter($paths, 'is_string')) as $path) {
            self::deleteIfUnused($db, $path);
        }
    }
}
