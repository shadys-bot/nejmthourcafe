<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

admin_require_login();
csrf_verify();

if (empty($_FILES['image']['tmp_name'])) {
    echo json_encode(['ok' => false, 'msg' => 'لم يتم إرسال ملف']);
    exit;
}

$file     = $_FILES['image'];
$maxBytes = MAX_UPLOAD_MB * 1024 * 1024;

if ($file['size'] > $maxBytes) {
    echo json_encode(['ok' => false, 'msg' => 'حجم الملف يتجاوز ' . MAX_UPLOAD_MB . 'MB']);
    exit;
}

// Validate MIME via finfo (not trusting extension or browser-reported type)
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!in_array($mimeType, ALLOWED_TYPES, true)) {
    echo json_encode(['ok' => false, 'msg' => 'نوع الملف غير مسموح به']);
    exit;
}

$ext      = match($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default      => 'jpg',
};

// Ensure upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Safe random filename — no user input in the name
$filename = bin2hex(random_bytes(12)) . '.' . $ext;
$destPath = UPLOAD_DIR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['ok' => false, 'msg' => 'فشل رفع الملف']);
    exit;
}

echo json_encode(['ok' => true, 'path' => 'images/menu/' . $filename]);
