<?php
/**
 * SETUP — Run once to set the admin password, then DELETE this file.
 */
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/includes/config.php';

$done  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw  = $_POST['password']  ?? '';
    $pw2 = $_POST['password2'] ?? '';

    if (strlen($pw) < 10) {
        $error = 'كلمة المرور يجب أن تكون 10 أحرف على الأقل';
    } elseif ($pw !== $pw2) {
        $error = 'كلمتا المرور غير متطابقتين';
    } else {
        $hash    = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
        $config  = file_get_contents(__DIR__ . '/includes/config.php');
        $updated = preg_replace(
            "/define\('ADMIN_HASH',\s*'[^']*'\);/",
            "define('ADMIN_HASH', '" . addslashes($hash) . "');",
            $config
        );
        if (file_put_contents(__DIR__ . '/includes/config.php', $updated)) {
            $done = true;
        } else {
            $error = 'فشل الكتابة على ملف الإعدادات — تأكد من صلاحيات الملف';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>إعداد كلمة المرور</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-page">
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <img src="../images/logo.png" alt="نجمة حور">
      <h1>إعداد كلمة المرور</h1>
      <p>خطوة واحدة فقط ثم احذف هذا الملف</p>
    </div>

    <?php if ($done): ?>
      <div class="alert alert-ok">
        ✅ تم حفظ كلمة المرور بنجاح.<br>
        <strong>احذف ملف setup.php الآن من السيرفر.</strong>
        <br><a href="index.php" style="margin-top:.5rem;display:inline-block;">تسجيل الدخول →</a>
      </div>
    <?php elseif ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$done): ?>
    <form method="POST">
      <div class="form-group">
        <label>كلمة المرور الجديدة (10 أحرف على الأقل)</label>
        <input type="password" name="password" required autofocus>
      </div>
      <div class="form-group">
        <label>تأكيد كلمة المرور</label>
        <input type="password" name="password2" required>
      </div>
      <button type="submit" class="btn-submit">حفظ كلمة المرور</button>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
