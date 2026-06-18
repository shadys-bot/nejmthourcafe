<?php
defined('NEJMT_ADMIN') or die('Direct access forbidden.');

/* ── Paths ── */
define('ROOT_DIR',    dirname(__DIR__, 2));
define('MENU_FILE',   ROOT_DIR . '/menu.json');
define('UPLOAD_DIR',  ROOT_DIR . '/images/menu/');
define('UPLOAD_URL',  '../images/menu/');

/* ── Password hash ──
   Stored in admin/includes/secret.php (gitignored, lives only on server).
   To set/change: php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT, ['cost'=>12]);"
   Then put result in secret.php as: define('ADMIN_HASH', '$2y$12$...');
*/
if (file_exists(__DIR__ . '/secret.php')) {
    require_once __DIR__ . '/secret.php';
} else {
    define('ADMIN_HASH', 'PLACEHOLDER_create_secret_php_on_server');
}

/* ── Session ── */
define('SESSION_NAME',   'nejmt_admin');
define('SESSION_MAXAGE', 3600 * 8); // 8 hours

/* ── Upload limits ── */
define('MAX_UPLOAD_MB',  8);
define('ALLOWED_TYPES',  ['image/jpeg','image/png','image/webp','image/gif']);
