<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/menu.php';

header('Content-Type: application/json; charset=utf-8');

admin_require_login();
csrf_verify();

$catId  = trim($_POST['category_id'] ?? '');
$itemId = (int)($_POST['item_id'] ?? 0);

if (!$catId || !$itemId) {
    echo json_encode(['ok' => false, 'msg' => 'بيانات ناقصة']);
    exit;
}

echo json_encode(menu_delete_item($catId, $itemId));
