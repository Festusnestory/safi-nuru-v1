<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Setting;

/**
 * Admin settings, user management, database/backup, and audit-log pages
 * (html/material/settings-general.php, settings-email.php,
 * settings-database.php, backup-restore.php, user-management.php,
 * activity-log.php). All admin only.
 */
final class SettingsController extends Controller
{
    public function general(): void
    {
        $this->requireRole(['admin']);
        require_once \NURU_MATERIAL . '/includes/functions.php';
        $csrfToken = Auth::csrfToken('general_settings');
        $model = new Setting($this->pdo);

        $success = '';
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'general_settings')) {
            http_response_code(419);
            $error = 'Your session has expired. Reload the page and try again.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $appName = trim((string) ($_POST['app_name'] ?? ''));
            $timezone = trim((string) ($_POST['app_timezone'] ?? ''));
            if (mb_strlen($appName) < 2 || mb_strlen($appName) > 100) {
                $error = 'Application name must be between 2 and 100 characters.';
            } elseif (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
                $error = 'Select a valid timezone.';
            } else {
                $this->pdo->beginTransaction();
                $model->save('app_name', $appName);
                $model->save('app_timezone', $timezone);
                $this->pdo->commit();
                global $nuruSettings;
                $nuruSettings['app_name'] = $appName;
                $nuruSettings['app_timezone'] = $timezone;
                date_default_timezone_set($timezone);
                $success = 'Settings saved.';
                \logActivity((int) $_SESSION['user_id'], 'GENERAL_SETTINGS_UPDATED', 'Updated application name and timezone', 'settings', 'warning');
            }
        }

        $this->render('admin.settings.general', [
            'csrfToken' => $csrfToken,
            'success' => $success,
            'error' => $error,
            'settings' => $model->all(),
        ]);
    }

    public function email(): void
    {
        $this->requireRole(['admin']);
        require_once \NURU_MATERIAL . '/includes/functions.php';
        require_once \NURU_MATERIAL . '/config/mailer.php';
        $csrfToken = Auth::csrfToken('email_settings');
        $model = new Setting($this->pdo);

        $success = '';
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'email_settings')) {
            http_response_code(419);
            $error = 'Your session has expired. Reload the page and try again.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'save') === 'test') {
            $testEmail = strtolower(trim((string) ($_POST['test_email'] ?? '')));
            if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL) || strlen($testEmail) > 255) {
                $error = 'Enter a valid test recipient address.';
            } else {
                $sent = \nuruSendMail(
                    $testEmail,
                    'Nuru email delivery test',
                    'This is a Nuru Real Estate delivery test generated at ' . date(DATE_ATOM) . '.',
                    false,
                    'admin_test',
                    (int) $_SESSION['user_id']
                );
                if ($sent) {
                    $success = 'Test email accepted by the configured mail transport.';
                    \logActivity((int) $_SESSION['user_id'], 'EMAIL_TEST_SENT', 'Mail transport accepted a test message', 'settings', 'info');
                } else {
                    $error = 'The configured mail transport rejected the test message. Review the server mail configuration before production use.';
                    \logActivity((int) $_SESSION['user_id'], 'EMAIL_TEST_FAILED', 'Mail transport rejected a test message', 'settings', 'warning');
                }
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fromEmail = strtolower(trim((string) ($_POST['smtp_from_email'] ?? '')));
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || strlen($fromEmail) > 255) {
                $error = 'Enter a valid sender email address.';
            } else {
                $model->save('smtp_from_email', $fromEmail);
                global $nuruSettings;
                $nuruSettings['smtp_from_email'] = $fromEmail;
                $success = 'Email sender saved.';
                \logActivity((int) $_SESSION['user_id'], 'EMAIL_SETTINGS_UPDATED', 'Updated the outbound sender address', 'settings', 'warning');
            }
        }

        $settings = $model->all();

        $testAddressStatement = $this->pdo->prepare('SELECT email FROM admin_users WHERE id = ? LIMIT 1');
        $testAddressStatement->execute([(int) $_SESSION['user_id']]);
        $testAddress = (string) ($testAddressStatement->fetchColumn() ?: '');
        $deliveryRows = $this->pdo->query(
            'SELECT purpose, delivery_status, failure_code, created_at
             FROM email_delivery_log
             ORDER BY id DESC
             LIMIT 10'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin.settings.email', [
            'csrfToken' => $csrfToken,
            'success' => $success,
            'error' => $error,
            'settings' => $settings,
            'testAddress' => $testAddress,
            'deliveryRows' => $deliveryRows,
        ]);
    }

    public function database(): void
    {
        $this->requireRole(['admin']);

        $dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
        $tables = $this->pdo->prepare('
            SELECT table_name, table_type, table_rows, ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
        ');
        $tables->execute([$dbName]);
        $tables = $tables->fetchAll(\PDO::FETCH_ASSOC);
        $totalSizeMb = array_sum(array_column($tables, 'size_mb'));

        $this->render('admin.settings.database', [
            'dbName' => $dbName,
            'tables' => $tables,
            'totalSizeMb' => $totalSizeMb,
        ]);
    }

    public function backup(): void
    {
        $this->requireRole(['admin']);
        require_once \NURU_MATERIAL . '/includes/functions.php';
        $csrfToken = Auth::csrfToken('database_backup');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'download') {
            if (!Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'database_backup')) {
                http_response_code(419);
                exit('Your session has expired. Reload the page and try again.');
            }

            $pdo = $this->pdo;
            $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
            $objectStatement = $pdo->prepare(
                'SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = ? ORDER BY table_type, table_name'
            );
            $objectStatement->execute([$dbName]);
            $objects = $objectStatement->fetchAll(\PDO::FETCH_ASSOC);
            $tables = array_column(array_filter($objects, static fn (array $object): bool => $object['table_type'] === 'BASE TABLE'), 'table_name');
            $views = array_column(array_filter($objects, static fn (array $object): bool => $object['table_type'] === 'VIEW'), 'table_name');

            \logActivity((int) $_SESSION['user_id'], 'DATABASE_BACKUP_DOWNLOADED', 'Downloaded a full database backup', 'backup', 'warning');

            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="nuru_backup_' . date('Ymd_His') . '.sql"');
            header('Cache-Control: no-store, private, max-age=0');
            header('X-Content-Type-Options: nosniff');

            echo "-- Nuru database backup generated " . date('Y-m-d H:i:s') . "\n";
            echo "SET NAMES utf8mb4;\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $safeTable = str_replace('`', '``', $table);
                $createStmt = $pdo->query("SHOW CREATE TABLE `$safeTable`")->fetch(\PDO::FETCH_NUM);
                echo "DROP TABLE IF EXISTS `$table`;\n";
                echo ($createStmt[1] ?? '') . ";\n\n";

                $rowStmt = $pdo->query("SELECT * FROM `$safeTable`");
                while ($row = $rowStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $values = array_map(function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, $row);
                    echo "INSERT INTO `$table` (`" . implode('`, `', array_keys($row)) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                echo "\n";
            }

            // Views must be created after their source tables. Remove database-bound
            // DEFINER clauses so the backup can be restored by a different account.
            foreach ($views as $view) {
                $safeView = str_replace('`', '``', $view);
                $createView = $pdo->query("SHOW CREATE VIEW `$safeView`")->fetch(\PDO::FETCH_NUM);
                $definition = (string) ($createView[1] ?? '');
                $definition = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/i', '', $definition);
                echo "DROP VIEW IF EXISTS `$safeView`;\n";
                echo $definition . ";\n\n";
            }

            $triggers = $pdo->query('SHOW TRIGGERS')->fetchAll(\PDO::FETCH_ASSOC);
            if ($triggers) {
                echo "DELIMITER ;;\n";
                foreach ($triggers as $trigger) {
                    $triggerName = str_replace('`', '``', (string) $trigger['Trigger']);
                    $createTrigger = $pdo->query("SHOW CREATE TRIGGER `$triggerName`")->fetch(\PDO::FETCH_NUM);
                    $definition = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/i', '', (string) ($createTrigger[2] ?? $createTrigger[1] ?? ''));
                    echo "DROP TRIGGER IF EXISTS `$triggerName`;;\n";
                    echo $definition . ";;\n";
                }
                echo "DELIMITER ;\n\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            exit;
        }

        $this->render('admin.settings.backup', [
            'csrfToken' => $csrfToken,
        ]);
    }

    public function users(): void
    {
        $this->requireRole(['admin']);
        require_once \NURU_MATERIAL . '/includes/functions.php';
        $csrfToken = Auth::csrfToken('user_management');

        $errors = [];
        $success = '';
        $administrativeRoles = ['admin', 'manager', 'agent_consultant'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::validCsrfToken($_POST['csrf_token'] ?? null, 'user_management')) {
            http_response_code(419);
            $errors[] = 'Your session has expired. Please reload the page and try again.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors && ($_POST['action'] ?? '') === 'create_user') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? '';
            $password = $_POST['password'] ?? '';

            if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $username)) {
                $errors[] = 'Username must be 3-64 characters, using only letters, numbers, dots, underscores, and hyphens (no spaces).';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Provide a valid email address.';
            }
            if ($fullName === '' || mb_strlen($fullName) > 255) {
                $errors[] = 'Provide a full name of no more than 255 characters.';
            }
            if (!in_array($role, $administrativeRoles, true)) {
                $errors[] = 'Select a valid role.';
            }
            if (strlen($password) < 12) {
                $errors[] = 'Password must be at least 12 characters long.';
            } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $errors[] = 'Password must contain at least one letter and one number.';
            }

            if (!$errors) {
                $check = $this->pdo->prepare('SELECT id FROM admin_users WHERE email = ? OR username = ?');
                $check->execute([$email, $username]);
                if ($check->fetch()) {
                    $errors[] = 'A user with this email or username already exists.';
                } else {
                    $stmt = $this->pdo->prepare('
                        INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active, must_change_password, created_at)
                        VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
                    ');
                    $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName, $role]);
                    $success = 'User created successfully.';
                    \logActivity((int) $_SESSION['user_id'], 'USER_CREATED', "Created user {$username}", 'user_management', 'info');
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors && ($_POST['action'] ?? '') === 'update_role') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $role = $_POST['role'] ?? '';
            if (!$userId || !in_array($role, $administrativeRoles, true)) {
                $errors[] = 'Invalid role change request.';
            } elseif ($userId === (int) $_SESSION['user_id']) {
                $errors[] = 'You cannot change your own role.';
            } else {
                try {
                    $this->pdo->beginTransaction();
                    $target = $this->pdo->prepare('SELECT role, is_active FROM admin_users WHERE id = ? FOR UPDATE');
                    $target->execute([$userId]);
                    $targetUser = $target->fetch(\PDO::FETCH_ASSOC);
                    if (!$targetUser) {
                        throw new \RuntimeException('User not found.');
                    }
                    if (!in_array($targetUser['role'], $administrativeRoles, true)) {
                        throw new \RuntimeException('Buyer, seller, and coordinator roles are managed through their application workflows.');
                    }
                    if ($targetUser['role'] === 'admin' && $role !== 'admin' && (int) $targetUser['is_active'] === 1) {
                        $adminCount = (int) $this->pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")->fetchColumn();
                        if ($adminCount <= 1) {
                            throw new \RuntimeException('At least one active administrator must remain.');
                        }
                    }
                    $this->pdo->prepare('UPDATE admin_users SET role = ? WHERE id = ?')->execute([$role, $userId]);
                    $this->pdo->commit();
                    $success = 'Role updated.';
                    \logActivity((int) $_SESSION['user_id'], 'USER_ROLE_CHANGED', "Updated role for user #{$userId} from {$targetUser['role']} to {$role}", 'user_management', 'warning');
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    $errors[] = $e instanceof \RuntimeException ? $e->getMessage() : 'Unable to update the role.';
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors && ($_POST['action'] ?? '') === 'toggle_active') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            if (!$userId || $userId === (int) $_SESSION['user_id']) {
                $errors[] = 'You cannot change your own account status.';
            } else {
                try {
                    $this->pdo->beginTransaction();
                    $target = $this->pdo->prepare('SELECT role, is_active FROM admin_users WHERE id = ? FOR UPDATE');
                    $target->execute([$userId]);
                    $targetUser = $target->fetch(\PDO::FETCH_ASSOC);
                    if (!$targetUser) {
                        throw new \RuntimeException('User not found.');
                    }
                    if ($targetUser['role'] === 'admin' && (int) $targetUser['is_active'] === 1) {
                        $adminCount = (int) $this->pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")->fetchColumn();
                        if ($adminCount <= 1) {
                            throw new \RuntimeException('At least one active administrator must remain.');
                        }
                    }
                    $newState = (int) !((int) $targetUser['is_active']);
                    $this->pdo->prepare('UPDATE admin_users SET is_active = ? WHERE id = ?')->execute([$newState, $userId]);
                    $this->pdo->commit();
                    $success = 'User status updated.';
                    \logActivity((int) $_SESSION['user_id'], 'USER_STATUS_CHANGED', "Set user #{$userId} " . ($newState ? 'active' : 'inactive'), 'user_management', 'warning');
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    $errors[] = $e instanceof \RuntimeException ? $e->getMessage() : 'Unable to update the account status.';
                }
            }
        }

        $users = $this->pdo->query('SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM admin_users ORDER BY created_at DESC')->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin.settings.users', [
            'csrfToken' => $csrfToken,
            'errors' => $errors,
            'success' => $success,
            'administrativeRoles' => $administrativeRoles,
            'users' => $users,
        ]);
    }

    public function activityLog(): void
    {
        $this->requireRole(['admin']);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM activity_log')->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $stmt = $this->pdo->prepare('
            SELECT al.id, al.level, al.category, al.action, al.description, al.ip_address, al.created_at, au.full_name
            FROM activity_log al
            LEFT JOIN admin_users au ON au.id = al.user_id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin.settings.activity-log', [
            'total' => $total,
            'totalPages' => $totalPages,
            'page' => $page,
            'logs' => $logs,
        ]);
    }

    // The list view above stays server-side paginated (activity logs can
    // grow large - loading the whole thing client-side for DataTables would
    // defeat the point) so export re-runs the same query without the
    // LIMIT/OFFSET and streams every matching row instead.
    public function activityLogExport(): void
    {
        $this->requireRole(['admin']);

        $stmt = $this->pdo->query('
            SELECT al.created_at, au.full_name, al.level, al.category, al.action, al.description, al.ip_address
            FROM activity_log al
            LEFT JOIN admin_users au ON au.id = al.user_id
            ORDER BY al.created_at DESC
        ');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="activity-log-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['When', 'User', 'Level', 'Category', 'Action', 'Description', 'IP']);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $row['created_at'],
                $row['full_name'] ?? 'System',
                ucfirst($row['level']),
                $row['category'],
                $row['action'],
                $row['description'],
                $row['ip_address'] ?? '',
            ]);
        }
        fclose($out);
    }
}
