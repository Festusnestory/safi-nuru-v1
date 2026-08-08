<?php
session_start();
require_once __DIR__ . '/config/role_helpers.php';
requireRole(['admin']);
include("./config/pdo.php");
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'User Management';
$csrfToken = csrfToken('user_management');

$errors = [];
$success = '';
$administrativeRoles = ['admin', 'manager', 'agent_consultant'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validCsrfToken($_POST['csrf_token'] ?? null, 'user_management')) {
    http_response_code(419);
    $errors[] = 'Your session has expired. Please reload the page and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors && ($_POST['action'] ?? '') === 'create_user') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $fullName === '' || mb_strlen($fullName) > 255 || !in_array($role, $administrativeRoles, true) || strlen($password) < 12 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Provide a valid username, email, name, role, and a password of at least 12 characters containing letters and numbers.';
    } else {
        $check = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            $errors[] = 'A user with this email or username already exists.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active, must_change_password, created_at)
                VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
            ");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName, $role]);
            $success = 'User created successfully.';
            logActivity((int)$_SESSION['user_id'], 'USER_CREATED', "Created user {$username}", 'user_management', 'info');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors && ($_POST['action'] ?? '') === 'update_role') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';
    if (!$userId || !in_array($role, $administrativeRoles, true)) {
        $errors[] = 'Invalid role change request.';
    } elseif ($userId === (int)$_SESSION['user_id']) {
        $errors[] = 'You cannot change your own role.';
    } else {
        try {
            $pdo->beginTransaction();
            $target = $pdo->prepare('SELECT role, is_active FROM admin_users WHERE id = ? FOR UPDATE');
            $target->execute([$userId]);
            $targetUser = $target->fetch(PDO::FETCH_ASSOC);
            if (!$targetUser) {
                throw new RuntimeException('User not found.');
            }
            if (!in_array($targetUser['role'], $administrativeRoles, true)) {
                throw new RuntimeException('Buyer, seller, and coordinator roles are managed through their application workflows.');
            }
            if ($targetUser['role'] === 'admin' && $role !== 'admin' && (int)$targetUser['is_active'] === 1) {
                $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")->fetchColumn();
                if ($adminCount <= 1) {
                    throw new RuntimeException('At least one active administrator must remain.');
                }
            }
            $pdo->prepare('UPDATE admin_users SET role = ? WHERE id = ?')->execute([$role, $userId]);
            $pdo->commit();
            $success = 'Role updated.';
            logActivity((int)$_SESSION['user_id'], 'USER_ROLE_CHANGED', "Updated role for user #{$userId} from {$targetUser['role']} to {$role}", 'user_management', 'warning');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update the role.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors && ($_POST['action'] ?? '') === 'toggle_active') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if (!$userId || $userId === (int)$_SESSION['user_id']) {
        $errors[] = 'You cannot change your own account status.';
    } else {
        try {
            $pdo->beginTransaction();
            $target = $pdo->prepare('SELECT role, is_active FROM admin_users WHERE id = ? FOR UPDATE');
            $target->execute([$userId]);
            $targetUser = $target->fetch(PDO::FETCH_ASSOC);
            if (!$targetUser) {
                throw new RuntimeException('User not found.');
            }
            if ($targetUser['role'] === 'admin' && (int)$targetUser['is_active'] === 1) {
                $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")->fetchColumn();
                if ($adminCount <= 1) {
                    throw new RuntimeException('At least one active administrator must remain.');
                }
            }
            $newState = (int)!((int)$targetUser['is_active']);
            $pdo->prepare('UPDATE admin_users SET is_active = ? WHERE id = ?')->execute([$newState, $userId]);
            $pdo->commit();
            $success = 'User status updated.';
            logActivity((int)$_SESSION['user_id'], 'USER_STATUS_CHANGED', "Set user #{$userId} " . ($newState ? 'active' : 'inactive'), 'user_management', 'warning');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update the account status.';
        }
    }
}

$users = $pdo->query("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM admin_users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php include('_page_head.php'); ?>
</head>
<body>
<div id="main-wrapper">
<?php include("top-bar.php"); ?>
<?php include("left-sidebar.php"); ?>
<div class="page-wrapper">
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">User Management</li>
            </ol>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">Add User</h4></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="create_user">
                            <div class="mb-3">
                                <label class="form-label" for="new-username">Username</label>
                                <input id="new-username" type="text" name="username" class="form-control" autocomplete="username" maxlength="64" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="new-full-name">Full Name</label>
                                <input id="new-full-name" type="text" name="full_name" class="form-control" autocomplete="name" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="new-email">Email</label>
                                <input id="new-email" type="email" name="email" class="form-control" autocomplete="email" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="new-role">Role</label>
                                <select id="new-role" name="role" class="form-select" required>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="agent_consultant">Agent Consultant</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="new-password">Temporary Password</label>
                                <input id="new-password" type="password" name="password" class="form-control" minlength="12" autocomplete="new-password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create User</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="border-bottom title-part-padding"><h4 class="card-title mb-0">All Users</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id'] && in_array($u['role'], $administrativeRoles, true)): ?>
                                                <form method="post" class="d-flex gap-1">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <label class="visually-hidden" for="role-<?= (int)$u['id'] ?>">Role for <?= htmlspecialchars($u['full_name']) ?></label>
                                                    <select id="role-<?= (int)$u['id'] ?>" name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <?php foreach ($administrativeRoles as $r): ?>
                                                            <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $r)) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                                <?php else: ?>
                                                    <span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $u['role']))) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                            <td><?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : 'Never' ?></td>
                                            <td>
                                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-<?= $u['is_active'] ? 'danger' : 'success' ?>">
                                                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer">All Rights Reserved by Nuru.</footer>
</div>
</div>
<?php include('_page_scripts.php'); ?>
</body>
</html>
