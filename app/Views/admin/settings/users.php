<?php
/** @var string $csrfToken */
/** @var array $errors */
/** @var string $success */
/** @var array $administrativeRoles */
/** @var array $users */
$pageTitle = 'User Management';
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<?php require NURU_MATERIAL . '/_page_head.php'; ?>
</head>
<body>
<div id="main-wrapper">
<?php require NURU_MATERIAL . '/top-bar.php'; ?>
<?php require NURU_MATERIAL . '/left-sidebar.php'; ?>
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
                            <table class="table table-striped table-bordered">
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
<?php require NURU_MATERIAL . '/_page_scripts.php'; ?>
</body>
</html>
