<?php
defined('NEJMT_ADMIN') or die('Direct access forbidden.');

function admin_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,      // always HTTPS on production
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function admin_is_logged_in(): bool {
    admin_session_start();
    if (empty($_SESSION['logged_in']))    return false;
    if (empty($_SESSION['last_active']))  return false;
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
    if (!defined('ADMIN_HASH') || str_contains(ADMIN_HASH, 'PLACEHOLDER')) return false;
    if (!password_verify($password, ADMIN_HASH)) return false;

    admin_session_start();
    session_regenerate_id(true);
    $_SESSION['logged_in']   = true;
    $_SESSION['last_active'] = time();
    $_SESSION['csrf_token']  = bin2hex(random_bytes(32));
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die(json_encode(['ok' => false, 'msg' => 'Method not allowed']));
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die(json_encode(['ok' => false, 'msg' => 'CSRF token mismatch']));
    }
}

function admin_url(string $page = 'dashboard.php'): string {
    $base = dirname($_SERVER['SCRIPT_NAME']);
    $base = rtrim(str_replace('\\', '/', $base), '/');
    if (!str_ends_with($base, '/admin')) {
        $base = rtrim($base, '/') . '/admin';
    }
    return $base . '/' . ltrim($page, '/');
}

/* ════════════════════════════════════════════
   Rate limiting — file-based, per IP
   Max 10 failed attempts → 15-minute lockout
   ════════════════════════════════════════════ */
function _rl_file(string $ip): string {
    return sys_get_temp_dir() . '/nejmt_rl_' . md5($ip) . '.json';
}

function rate_limit_ok(string $ip): bool {
    $d = @json_decode(@file_get_contents(_rl_file($ip)) ?: '{}', true) ?? [];
    return empty($d['until']) || (int)$d['until'] <= time();
}

function rate_limit_fail(string $ip): void {
    $f = _rl_file($ip);
    $d = @json_decode(@file_get_contents($f) ?: '{}', true) ?? [];
    $n = ($d['count'] ?? 0) + 1;
    if ($n >= 10) {
        file_put_contents($f, json_encode(['count' => 0, 'until' => time() + 900]), LOCK_EX);
    } else {
        file_put_contents($f, json_encode(['count' => $n]), LOCK_EX);
    }
}

function rate_limit_clear(string $ip): void {
    @unlink(_rl_file($ip));
}
