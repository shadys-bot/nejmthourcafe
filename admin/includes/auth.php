<?php
defined('NEJMT_ADMIN') or die('Direct access forbidden.');

function admin_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function admin_is_logged_in(): bool {
    admin_session_start();
    if (empty($_SESSION['logged_in'])) return false;
    if (empty($_SESSION['last_active'])) return false;
    if (time() - $_SESSION['last_active'] > SESSION_MAXAGE) {
        admin_logout();
        return false;
    }
    $_SESSION['last_active'] = time();
    return true;
}

function admin_require_login(): void {
    if (!admin_is_logged_in()) {
        header('Location: ' . admin_url('index.php') . '?expired=1');
        exit;
    }
}

function admin_login(string $password): bool {
    // Reject if hash is the placeholder
    if (str_contains(ADMIN_HASH, 'placeholder')) return false;
    if (!password_verify($password, ADMIN_HASH)) return false;

    admin_session_start();
    session_regenerate_id(true);
    $_SESSION['logged_in']    = true;
    $_SESSION['last_active']  = time();
    $_SESSION['csrf_token']   = bin2hex(random_bytes(32));
    return true;
}

function admin_logout(): void {
    admin_session_start();
    $_SESSION = [];
    session_destroy();
}

function csrf_token(): string {
    admin_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die(json_encode(['ok' => false, 'msg' => 'CSRF token mismatch']));
    }
}

function admin_url(string $page = 'dashboard.php'): string {
    $base = dirname($_SERVER['SCRIPT_NAME']);
    // Normalize so we always point to /admin/
    $base = rtrim(str_replace('\\', '/', $base), '/');
    if (!str_ends_with($base, '/admin')) {
        $base = rtrim($base, '/') . '/admin';
    }
    return $base . '/' . ltrim($page, '/');
}
