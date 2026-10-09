<?php

class Popup
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->conn->exec("CREATE TABLE IF NOT EXISTS popup_config (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            image_path VARCHAR(500) NOT NULL DEFAULT '',
            link_url VARCHAR(1000) NOT NULL DEFAULT '',
            starts_on DATE NOT NULL,
            ends_on DATE NOT NULL,
            display_seconds TINYINT UNSIGNED NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function read(): ?array
    {
        $statement = $this->conn->query('SELECT image_path, link_url, starts_on, ends_on, display_seconds, active FROM popup_config WHERE id = 1 LIMIT 1');
        $configuration = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($configuration) ? $configuration : null;
    }

    public function save(array $configuration): void
    {
        $statement = $this->conn->prepare(
            'INSERT INTO popup_config (id, image_path, link_url, starts_on, ends_on, display_seconds, active)
             VALUES (1, :image_path, :link_url, :starts_on, :ends_on, :display_seconds, :active)
             ON DUPLICATE KEY UPDATE image_path = VALUES(image_path), link_url = VALUES(link_url),
                 starts_on = VALUES(starts_on), ends_on = VALUES(ends_on),
                 display_seconds = VALUES(display_seconds), active = VALUES(active)'
        );
        $statement->execute(array(
            ':image_path' => $configuration['image_path'],
            ':link_url' => $configuration['link_url'],
            ':starts_on' => $configuration['starts_on'],
            ':ends_on' => $configuration['ends_on'],
            ':display_seconds' => $configuration['display_seconds'],
            ':active' => $configuration['active'],
        ));
    }
}
