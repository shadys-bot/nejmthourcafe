<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/offer.php';

header('Content-Type: application/json; charset=utf-8');
admin_require_login();
csrf_verify();

$id = (int)($_POST['offer_id'] ?? 0);
if (!$id) { echo json_encode(['ok' => false, 'msg' => 'معرف العرض مطلوب']); exit; }

echo json_encode(offer_delete($id));
