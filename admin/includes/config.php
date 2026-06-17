<?php
defined('NEJMT_ADMIN') or die('Direct access forbidden.');

/* ── Paths ── */
define('ROOT_DIR',    dirname(__DIR__, 2));
define('MENU_FILE',   ROOT_DIR . '/menu.json');
define('UPLOAD_DIR',  ROOT_DIR . '/images/menu/');
define('UPLOAD_URL',  '../images/menu/');

/* ── Password hash ──
   Default password: Admin@Nejmt2025
   Run setup.php to change it.
*/
define('ADMIN_HASH', '$2y$12$placeholder_run_setup_php_first_xxxxxxxxxxxxxxxxxx');

/* ── Session ── */
define('SESSION_NAME',   'nejmt_admin');
define('SESSION_MAXAGE', 3600 * 8); // 8 hours

/* ── Upload limits ── */
define('MAX_UPLOAD_MB',  8);
define('ALLOWED_TYPES',  ['image/jpeg','image/png','image/webp','image/gif']);
