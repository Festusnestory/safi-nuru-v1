<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Key/value rows in app_settings, backing settings-general.php and
 * settings-email.php. Mirrors those pages' original inline
 * read-all-into-assoc-array and upsert-one-row queries exactly.
 */
final class Setting
{
    public function __construct(private \PDO $pdo)
    {
    }

    /** All settings as setting_name => setting_value. */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT setting_name, setting_value FROM app_settings')->fetchAll(\PDO::FETCH_ASSOC);
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        return $settings;
    }

    /** Insert or update a single string setting. */
    public function save(string $name, string $value): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO app_settings (setting_name, setting_value, setting_type) VALUES (?, ?, 'string') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute([$name, $value]);
    }
}
