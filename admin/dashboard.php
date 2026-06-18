<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/menu.php';

admin_require_login();

$menu    = menu_read();
$cats    = $menu['categories'] ?? [];
$active  = $_GET['cat'] ?? ($cats[0]['id'] ?? '');
$activeCat = null;
foreach ($cats as $c) { if ($c['id'] === $active) { $activeCat = $c; break; } }
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>لوحة التحكم — نجمة حور</title>
<link rel="icon" href="../images/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/admin.css">
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="../images/logo.png" alt="نجمة حور">
    <span>نجمة حور</span>
  </div>
  <nav class="sidebar-nav">
    <a href="offers.php" style="border-right-color:transparent">
      <span class="cat-icon">🎁</span>
      <span>العروض</span>
    </a>
    <a href="categories.php" style="border-right-color:transparent">
      <span class="cat-icon">📂</span>
      <span>الفئات</span>
    </a>
    <div style="height:1px;background:var(--border);margin:.4rem 1.25rem"></div>
    <?php foreach ($cats as $cat): ?>
      <a href="?cat=<?= htmlspecialchars($cat['id']) ?>"
         class="<?= $cat['id'] === $active ? 'active' : '' ?>">
        <span class="cat-icon"><?= $cat['icon'] ?></span>
        <span><?= htmlspecialchars($cat['label_ar']) ?></span>
        <span class="badge"><?= count($cat['items']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <a href="../" target="_blank" class="sidebar-link">← عرض الموقع</a>
    <a href="logout.php" class="sidebar-link logout">تسجيل الخروج</a>
  </div>
</aside>

<!-- Main -->
<main class="main">
  <div class="topbar">
    <h2>
      <?= $activeCat ? $activeCat['icon'] . ' ' . htmlspecialchars($activeCat['label_ar']) : 'القائمة' ?>
      <small><?= $activeCat ? '/ ' . htmlspecialchars($activeCat['label_en'] ?? '') : '' ?></small>
    </h2>
    <button class="btn-add" onclick="openModal()">+ إضافة صنف</button>
  </div>

  <!-- Items table -->
  <?php if ($activeCat): ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>الصورة</th>
          <th>الاسم عربي</th>
          <th>الاسم إنجليزي</th>
          <th>السعر</th>
          <th>السعرات</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody id="items-body">
        <?php foreach ($activeCat['items'] as $item): ?>
        <tr data-id="<?= $item['id'] ?>">
          <td>
            <img class="thumb" src="../<?= htmlspecialchars($item['image']) ?>"
                 alt="<?= htmlspecialchars($item['ar']) ?>" onerror="this.src='../images/menu/coffee.jpg'">
          </td>
          <td class="fw"><?= htmlspecialchars($item['ar']) ?></td>
          <td class="dim"><?= htmlspecialchars($item['en']) ?></td>
          <td><span class="price-tag"><?= number_format($item['price'], 0) ?> ريال</span></td>
          <td class="dim"><?= htmlspecialchars($item['cal']) ?></td>
          <td>
            <div class="actions">
              <button class="btn-edit" onclick="editItem(<?= htmlspecialchars(json_encode(array_merge($item, ['_cat' => $active]), JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">تعديل</button>
              <button class="btn-del"  onclick="deleteItem(<?= $item['id'] ?>, <?= htmlspecialchars(json_encode($item['ar']), ENT_QUOTES) ?>)">حذف</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <p style="padding:2rem;color:var(--muted)">اختر فئة من القائمة الجانبية</p>
  <?php endif; ?>
</main>

<!-- Modal: Add / Edit -->
<div class="modal-overlay" id="modal" onclick="closeModal(event)">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="modal-title">إضافة صنف جديد</h3>
      <button onclick="closeModal()" class="modal-close">✕</button>
    </div>

    <form id="item-form" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="item_id" id="f-item-id" value="0">
      <input type="hidden" name="image"    id="f-image" value="">

      <div class="form-group">
        <label>الفئة *</label>
        <select name="category_id" id="f-cat-id">
          <?php foreach ($cats as $cat): ?>
          <option value="<?= htmlspecialchars($cat['id']) ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['label_ar']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>الاسم بالعربية *</label>
          <input type="text" name="ar" id="f-ar" required placeholder="مثال: لاتيه">
        </div>
        <div class="form-group">
          <label>الاسم بالإنجليزية *</label>
          <input type="text" name="en" id="f-en" required placeholder="Latte" dir="ltr">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>السعر (ريال) *</label>
          <input type="number" name="price" id="f-price" min="0" step="0.5" required placeholder="13">
        </div>
        <div class="form-group">
          <label>السعرات الحرارية</label>
          <input type="text" name="cal" id="f-cal" placeholder="120-170" dir="ltr">
        </div>
      </div>

      <div class="form-group">
        <label>الصورة</label>
        <div class="upload-area" id="upload-area">
          <img id="preview-img" src="" alt="" style="display:none;max-height:140px;border-radius:8px;object-fit:cover;">
          <div id="upload-placeholder">
            <span>📷</span>
            <p>اسحب صورة هنا أو اضغط للاختيار</p>
            <small>JPG, PNG, WebP — حتى <?= MAX_UPLOAD_MB ?>MB</small>
          </div>
          <input type="file" id="file-input" accept="image/*" style="display:none">
        </div>
        <div id="upload-status"></div>
      </div>

      <div class="modal-actions">
        <button type="button" onclick="closeModal()" class="btn-cancel">إلغاء</button>
        <button type="submit" class="btn-save" id="btn-save">حفظ</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm delete dialog -->
<div class="modal-overlay" id="del-modal" onclick="closeDelModal(event)">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h3>تأكيد الحذف</h3>
      <button onclick="closeDelModal()" class="modal-close">✕</button>
    </div>
    <p id="del-msg" style="margin:.5rem 0 1.5rem;color:var(--muted)"></p>
    <div class="modal-actions">
      <button onclick="closeDelModal()" class="btn-cancel">إلغاء</button>
      <button onclick="confirmDelete()" class="btn-del-confirm">نعم، احذف</button>
    </div>
  </div>
</div>

<div id="toast" class="toast"></div>

<script>
const CSRF       = <?= json_encode($csrf) ?>;
const CAT        = <?= json_encode($active) ?>;
const CATEGORIES = <?= json_encode(array_column($cats, 'id')) ?>;
let pendingDeleteId = null;

/* ── Modal ── */
function openModal(item = null) {
  document.getElementById('modal-title').textContent = item ? 'تعديل الصنف' : 'إضافة صنف جديد';
  document.getElementById('f-item-id').value = item?.id ?? 0;
  const catSel = document.getElementById('f-cat-id');
  catSel.value = item?._cat ?? CAT;
  document.getElementById('f-ar').value      = item?.ar ?? '';
  document.getElementById('f-en').value      = item?.en ?? '';
  document.getElementById('f-price').value   = item?.price ?? '';
  document.getElementById('f-cal').value     = item?.cal ?? '';
  document.getElementById('f-image').value   = item?.image ?? '';
  const img = document.getElementById('preview-img');
  const ph  = document.getElementById('upload-placeholder');
  if (item?.image) {
    img.src = '../' + item.image; img.style.display = 'block'; ph.style.display = 'none';
  } else {
    img.style.display = 'none'; ph.style.display = 'flex';
  }
  document.getElementById('upload-status').textContent = '';
  document.getElementById('modal').classList.add('open');
}
function editItem(item) { openModal(item); }
function closeModal(e) {
  if (!e || e.target === document.getElementById('modal')) {
    document.getElementById('modal').classList.remove('open');
  }
}

/* ── Delete ── */
function deleteItem(id, name) {
  pendingDeleteId = id;
  document.getElementById('del-msg').textContent = 'هل تريد حذف "' + name + '"؟ لا يمكن التراجع عن هذا.';
  document.getElementById('del-modal').classList.add('open');
}
function closeDelModal(e) {
  if (!e || e.target === document.getElementById('del-modal')) {
    document.getElementById('del-modal').classList.remove('open');
    pendingDeleteId = null;
  }
}
async function confirmDelete() {
  if (!pendingDeleteId) return;
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('category_id', CAT);
  fd.append('item_id', pendingDeleteId);
  const res  = await fetch('api/delete_item.php', { method: 'POST', body: fd });
  const data = await res.json();
  closeDelModal();
  if (data.ok) {
    document.querySelector(`tr[data-id="${pendingDeleteId}"]`)?.remove();
    toast('تم الحذف بنجاح', 'ok');
  } else {
    toast('خطأ: ' + data.msg, 'err');
  }
}

/* ── File upload ── */
const uploadArea = document.getElementById('upload-area');
const fileInput  = document.getElementById('file-input');
uploadArea.addEventListener('click', () => fileInput.click());
uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('drag'); });
uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag'));
uploadArea.addEventListener('drop', e => { e.preventDefault(); uploadArea.classList.remove('drag'); handleFile(e.dataTransfer.files[0]); });
fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));

async function handleFile(file) {
  if (!file) return;
  const statusEl = document.getElementById('upload-status');
  statusEl.textContent = 'جاري الرفع...';
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('image', file);
  try {
    const res  = await fetch('api/upload_image.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      document.getElementById('f-image').value = data.path;
      const img = document.getElementById('preview-img');
      img.src = '../' + data.path;
      img.style.display = 'block';
      document.getElementById('upload-placeholder').style.display = 'none';
      statusEl.textContent = '✓ تم الرفع';
      statusEl.style.color = 'var(--green)';
    } else {
      statusEl.textContent = '✗ ' + data.msg;
      statusEl.style.color = 'var(--red)';
    }
  } catch { statusEl.textContent = '✗ فشل الاتصال'; statusEl.style.color = 'var(--red)'; }
}

/* ── Save item ── */
document.getElementById('item-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('btn-save');
  btn.disabled = true; btn.textContent = 'جاري الحفظ...';
  const fd = new FormData(e.target);
  try {
    const res  = await fetch('api/save_item.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      toast('تم الحفظ بنجاح ✓', 'ok');
      closeModal();
      setTimeout(() => location.reload(), 800);
    } else {
      toast('خطأ: ' + data.msg, 'err');
    }
  } catch { toast('فشل الاتصال بالسيرفر', 'err'); }
  finally { btn.disabled = false; btn.textContent = 'حفظ'; }
});

/* ── Escape key ── */
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (document.getElementById('modal').classList.contains('open'))     closeModal();
  if (document.getElementById('del-modal').classList.contains('open')) closeDelModal();
});

/* ── Toast ── */
function toast(msg, type = 'ok') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = 'toast show ' + type;
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 3000);
}
</script>
</body>
</html>
