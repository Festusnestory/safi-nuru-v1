<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy server-side state and remove the browser's PHP session cookie.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
}
session_destroy();

// Remove remember-me cookie if it exists
if (isset($_COOKIE['nuru_auth'])) {
    setcookie(
        'nuru_auth',
        '',
        time() - 3600,
        '/',
        '',
        !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        true
    );
}

// Prevent caching of protected pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Redirect to login page
header("Location: ../authentication-login.php");
exit;
