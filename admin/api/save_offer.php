<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/offer.php';

header('Content-Type: application/json; charset=utf-8');
admin_require_login();
csrf_verify();

echo json_encode(offer_save($_POST));
