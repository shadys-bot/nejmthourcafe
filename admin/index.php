<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in?
if (admin_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$expired = isset($_GET['expired']);
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (!rate_limit_ok($ip)) {
        $error = 'تم تجاوز عدد المحاولات المسموح بها. حاول مجدداً بعد 15 دقيقة.';
    } elseif (admin_login($password)) {
        rate_limit_clear($ip);
        header('Location: dashboard.php');
        exit;
    } else {
        rate_limit_fail($ip);
        sleep(1);
        $error = 'كلمة المرور غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول — نجمة حور</title>
<link rel="icon" href="../images/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-page">

<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <img src="../images/logo.png" alt="نجمة حور">
      <h1>مقهى نجمة حور</h1>
      <p>لوحة التحكم</p>
    </div>

    <?php if ($expired): ?>
      <div class="alert alert-warn">انتهت جلستك. يرجى تسجيل الدخول مجدداً.</div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" novalidate>
      <div class="form-group">
        <label for="password">كلمة المرور</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••••••" required autofocus>
      </div>
      <button type="submit" class="btn-submit">دخول →</button>
    </form>

    <?php if (str_contains(ADMIN_HASH, 'placeholder')): ?>
      <div class="alert alert-warn" style="margin-top:1rem;">
        ⚠️ لم يتم ضبط كلمة المرور بعد.
        <a href="setup.php">اضغط هنا للإعداد</a>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
