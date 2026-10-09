<?php

class Congreso
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $tableExists = $this->connection->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name'
        );
        $tableExists->execute(array(':table_name' => 'congresos'));
        $schemaAlreadyExisted = (int) $tableExists->fetchColumn() > 0;

        $this->connection->exec("CREATE TABLE IF NOT EXISTS congresos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            contenido LONGTEXT NOT NULL,
            publicado TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if ($schemaAlreadyExisted) {
            return;
        }

        $count = (int) $this->connection->query('SELECT COUNT(*) FROM congresos')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $legacyExists = $this->connection->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name'
        );
        $legacyExists->execute(array(':table_name' => 'congreso_contenido'));
        if ((int) $legacyExists->fetchColumn() > 0) {
            $legacyContent = $this->connection->query(
                'SELECT contenido FROM congreso_contenido WHERE id = 1'
            )->fetchColumn();
            if (is_string($legacyContent)) {
                $insert = $this->connection->prepare(
                    'INSERT IGNORE INTO congresos (id, contenido, publicado) VALUES (1, :contenido, 1)'
                );
                $insert->execute(array(':contenido' => $legacyContent));
                return;
            }
        }

        $insert = $this->connection->prepare(
            'INSERT IGNORE INTO congresos (id, contenido, publicado) VALUES (1, :contenido, 1)'
        );
        $insert->execute(array(':contenido' => $this->encode($this->defaultContent())));
    }

    public function read(int $id = 1, bool $includeDraft = false): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT contenido, publicado FROM congresos WHERE id = :id'
        );
        $statement->execute(array(':id' => $id));
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row) || (!$includeDraft && !(bool) $row['publicado'])) {
            return null;
        }

        return $this->decode($row['contenido'], $id, (bool) $row['publicado']);
    }

    public function readAll(bool $publishedOnly = false): array
    {
        $sql = 'SELECT id, contenido, publicado FROM congresos';
        if ($publishedOnly) {
            $sql .= ' WHERE publicado = 1';
        }
        $sql .= ' ORDER BY id ASC';
        $rows = $this->connection->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $congresses = array();
        foreach ($rows as $row) {
            $congresses[] = $this->decode(
                $row['contenido'],
                (int) $row['id'],
                (bool) $row['publicado']
            );
        }
        return $congresses;
    }

    public function createPublished(array $content): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO congresos (contenido, publicado) VALUES (:contenido, 1)'
        );
        $statement->execute(array(':contenido' => $this->encode($content)));
        return (int) $this->connection->lastInsertId();
    }

    public function save(int $id, array $content): void
    {
        $statement = $this->connection->prepare(
            'UPDATE congresos SET contenido = :contenido, publicado = 1 WHERE id = :id'
        );
        $statement->execute(array(
            ':contenido' => $this->encode($content),
            ':id' => $id,
        ));
        if ($statement->rowCount() === 0 && $this->read($id, true) === null) {
            throw new RuntimeException('No se encontró el congreso que se intentó guardar.');
        }
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM congresos WHERE id = :id');
        $statement->execute(array(':id' => $id));
        return $statement->rowCount() > 0;
    }

    private function decode(string $storedContent, int $id, bool $published): array
    {
        $content = json_decode($storedContent, true);
        if (!is_array($content) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('El contenido guardado del congreso no tiene un formato válido.');
        }
        $speakers = $content['speakers'] ?? array();
        foreach ($speakers as &$speaker) {
            $speaker['flag'] = $this->countryCode((string) ($speaker['country'] ?? ''));
        }
        unset($speaker);
        $content['speakers'] = $speakers;
        $content['countries'] = is_array($content['countries'] ?? null)
            ? $content['countries']
            : array();
        $content['time'] = is_string($content['time'] ?? null) ? $content['time'] : '';
        $content['summary'] = is_string($content['summary'] ?? null) && trim($content['summary']) !== ''
            ? $content['summary']
            : 'Es un espacio internacional de encuentro académico y profesional que reunirá a expertos, investigadores y estudiantes para compartir conocimientos, experiencias y avances en ciencia de datos e inteligencia artificial, impulsando la innovación y la colaboración global.';
        $content['payment']['yape_enabled'] = ($content['payment']['yape_enabled'] ?? true) !== false;
        if (!isset($content['payment']['wallet_name'])) {
            $content['payment']['wallet_name'] = 'Yape';
        }
        if (($content['payment']['note'] ?? '') === 'Confirma que estos datos sigan vigentes con la organización antes de realizar un pago.') {
            $content['payment']['note'] = '';
        }
        $content['id'] = $id;
        $content['published'] = $published;
        return $content;
    }

    private function encode(array $content): string
    {
        unset($content['id'], $content['published']);
        $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('No se pudo codificar la información del congreso.');
        }
        return $json;
    }

    private function defaultContent(): array
    {
        $speakersPath = dirname(__DIR__, 2) . '/public/assets/data/congreso-ponentes.json';
        $speakerJson = is_file($speakersPath) ? file_get_contents($speakersPath) : false;
        $speakers = is_string($speakerJson) ? json_decode($speakerJson, true) : null;
        if (!is_array($speakers) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('No se pudo cargar la lista inicial de ponentes del congreso.');
        }
        foreach ($speakers as &$speaker) {
            $speaker['flag'] = $this->countryCode((string) ($speaker['country'] ?? ''));
        }
        unset($speaker);

        return array(
            'title' => 'Congreso Internacional CDIA',
            'subtitle' => 'Ciencia de Datos e Inteligencia Artificial',
            'intro' => 'Conoce a los ponentes, tarifas y medios de pago consignados para el congreso.',
            'summary' => 'Es un espacio internacional de encuentro académico y profesional que reunirá a expertos, investigadores y estudiantes para compartir conocimientos, experiencias y avances en ciencia de datos e inteligencia artificial, impulsando la innovación y la colaboración global.',
            'logo' => 'assets/images/congreso/logo-cdia.jpg',
            'date' => 'Por confirmar',
            'time' => '',
            'modality' => 'Por confirmar',
            'venue' => 'Por confirmar',
            'registration_url' => '',
            'registration_qr' => '',
            'contact_email' => 'congresocidia@fundaciondu.org',
            'sponsors' => array('Bitel', 'Simmetryc'),
            'countries' => array(),
            'fees' => array(
                array('audience' => 'Estudiantes UNTELS', 'amount' => 'S/ 50.00'),
                array('audience' => 'Personal UNTELS', 'amount' => 'S/ 100.00'),
                array('audience' => 'Estudiantes en general', 'amount' => 'S/ 100.00'),
                array('audience' => 'Público en general', 'amount' => 'S/ 200.00'),
            ),
            'certifications' => array(
                'La tarifa incluye certificación de Perú.',
                'Certificación doble (Perú–China): S/ 50.00 adicionales.',
                'Certificación triple (Perú–China–Brasil): S/ 100.00 adicionales.',
            ),
            'payment' => array(
                'bank' => 'Banco de Crédito del Perú (BCP)',
                'holder' => 'Fundación para el Desarrollo Universitario UNTELS',
                'account' => '194-6929357-0-03',
                'cci' => '00219400692935700397',
                'wallet_name' => 'Yape',
                'yape' => '940 404 384',
                'yape_holder' => 'Luzbeth Karin Navarrete Leal',
                'note' => '',
            ),
            'speakers' => $speakers,
        );
    }

    private function countryCode(string $country): string
    {
        $codes = array(
            'Argentina' => 'AR',
            'Bolivia' => 'BO',
            'Brasil' => 'BR',
            'Chile' => 'CL',
            'China' => 'CN',
            'Colombia' => 'CO',
            'Costa Rica' => 'CR',
            'Cuba' => 'CU',
            'Ecuador' => 'EC',
            'El Salvador' => 'SV',
            'España' => 'ES',
            'Estados Unidos' => 'US',
            'Guatemala' => 'GT',
            'Honduras' => 'HN',
            'México' => 'MX',
            'Nicaragua' => 'NI',
            'Panamá' => 'PA',
            'Paraguay' => 'PY',
            'Perú' => 'PE',
            'Portugal' => 'PT',
            'República Dominicana' => 'DO',
            'Uruguay' => 'UY',
            'Venezuela' => 'VE',
        );
        return $codes[$country] ?? '';
    }
}
