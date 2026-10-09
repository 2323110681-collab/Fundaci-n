<?php
/**
 * Migracion puntual: cambia el prefijo de las rutas de imagen guardadas en la
 * base de datos de /fundaciondu/public/uploads/ a /uploads/, que es como queda
 * el sitio ahora que se sirve en la raiz del dominio.
 *
 * Deja copia de los valores anteriores en database/migracion-imagenes-antes.csv
 * por si hay que revertir.
 */
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$pdo = $db->getConnection();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "No se pudo abrir la conexion.\n");
    exit(1);
}

$oldPrefix = '/fundaciondu/public/uploads/';
$newPrefix = '/uploads/';

$tables = ['noticias', 'programas', 'eventos', 'miembros', 'convenios'];
$backup = [];
$changes = 0;

foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT id, imagen FROM {$table}");
    $update = $pdo->prepare("UPDATE {$table} SET imagen = :new WHERE id = :id AND imagen = :old");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $imagen = (string) ($row['imagen'] ?? '');
        if ($imagen === '' || strpos($imagen, $oldPrefix) !== 0) {
            continue;
        }
        $new = $newPrefix . substr($imagen, strlen($oldPrefix));
        $update->execute([':new' => $new, ':id' => $row['id'], ':old' => $imagen]);
        $backup[] = [$table, $row['id'], $imagen, $new];
        $changes++;
        echo "  {$table} #{$row['id']}: {$imagen} -> {$new}\n";
    }
}

// Los docentes se guardan como JSON dentro de programas.docentes.
$stmt = $pdo->query("SELECT id, docentes FROM programas WHERE docentes IS NOT NULL AND docentes <> ''");
$updateDoc = $pdo->prepare("UPDATE programas SET docentes = :new WHERE id = :id AND docentes = :old");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $docentes = json_decode((string) $row['docentes'], true);
    if (!is_array($docentes)) {
        continue;
    }
    $touched = false;
    foreach ($docentes as &$docente) {
        if (!empty($docente['foto']) && is_string($docente['foto']) && strpos($docente['foto'], $oldPrefix) === 0) {
            $docente['foto'] = $newPrefix . substr($docente['foto'], strlen($oldPrefix));
            $touched = true;
        }
    }
    unset($docente);
    if ($touched) {
        $old = (string) $row['docentes'];
        $new = json_encode($docentes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $updateDoc->execute([':new' => $new, ':id' => $row['id'], ':old' => $old]);
        $backup[] = ['programas.docentes', $row['id'], $old, $new];
        $changes++;
        echo "  programas.docentes #{$row['id']}: fotos actualizadas\n";
    }
}

if ($backup) {
    $csv = __DIR__ . '/migracion-imagenes-antes.csv';
    $fh = fopen($csv, 'w');
    fputcsv($fh, ['tabla', 'id', 'valor_anterior', 'valor_nuevo']);
    foreach ($backup as $b) {
        fputcsv($fh, $b);
    }
    fclose($fh);
    echo "\nCopia guardada en database/migracion-imagenes-antes.csv\n";
}

echo "\nTotal de registros actualizados: {$changes}\n";
