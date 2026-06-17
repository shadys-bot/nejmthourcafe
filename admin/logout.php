<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
admin_logout();
header('Location: index.php');
exit;
