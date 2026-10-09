<?php
/**
 * Revierte la migracion de rutas de imagen: vuelve de /uploads/ a
 * /fundaciondu/public/uploads/, que es donde se sirve el sitio mientras el
 * VirtualHost de la raiz del dominio esta desactivado.
 *
 * Es la operacion inversa de migrate_upload_prefix.php. Corre este script cada
 * vez que actives o desactives el VirtualHost de httpd-vhosts.conf.
 */
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$pdo = $db->getConnection();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "No se pudo abrir la conexion.\n");
    exit(1);
}

$from = '/uploads/';
$to = '/fundaciondu/public/uploads/';

$tables = ['noticias', 'programas', 'eventos', 'miembros', 'convenios'];
$changes = 0;

foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT id, imagen FROM {$table}");
    $update = $pdo->prepare("UPDATE {$table} SET imagen = :new WHERE id = :id AND imagen = :old");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $imagen = (string) ($row['imagen'] ?? '');
        if ($imagen === '' || strpos($imagen, $from) !== 0) {
            continue;
        }
        $new = $to . substr($imagen, strlen($from));
        $update->execute([':new' => $new, ':id' => $row['id'], ':old' => $imagen]);
        $changes++;
        echo "  {$table} #{$row['id']}: {$imagen} -> {$new}\n";
    }
}

$stmt = $pdo->query("SELECT id, docentes FROM programas WHERE docentes IS NOT NULL AND docentes <> ''");
$updateDoc = $pdo->prepare("UPDATE programas SET docentes = :new WHERE id = :id AND docentes = :old");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $docentes = json_decode((string) $row['docentes'], true);
    if (!is_array($docentes)) {
        continue;
    }
    $touched = false;
    foreach ($docentes as &$docente) {
        if (!empty($docente['foto']) && is_string($docente['foto']) && strpos($docente['foto'], $from) === 0) {
            $docente['foto'] = $to . substr($docente['foto'], strlen($from));
            $touched = true;
        }
    }
    unset($docente);
    if ($touched) {
        $updateDoc->execute([
            ':new' => json_encode($docentes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':id'  => $row['id'],
            ':old' => (string) $row['docentes'],
        ]);
        $changes++;
        echo "  programas.docentes #{$row['id']}: fotos restauradas\n";
    }
}

echo "\nTotal de registros restaurados: {$changes}\n";
