<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/menu.php';

admin_require_login();

$data = menu_read();
$cats = $data['categories'] ?? [];
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إدارة الفئات — نجمة حور</title>
<link rel="icon" href="../images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="css/admin.css">
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="../images/logo.png" alt="نجمة حور">
    <span>نجمة حور</span>
  </div>
  <nav class="sidebar-nav">
    <a href="offers.php">
      <span class="cat-icon">🎁</span><span>العروض</span>
    </a>
    <a href="categories.php" class="active">
      <span class="cat-icon">📂</span><span>الفئات</span>
    </a>
    <div style="height:1px;background:var(--border);margin:.4rem 1.25rem"></div>
    <?php foreach ($cats as $cat): ?>
    <a href="dashboard.php?cat=<?= htmlspecialchars($cat['id']) ?>">
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

<main class="main">
  <div class="topbar">
    <h2>📂 إدارة الفئات <small>/ Categories</small></h2>
    <button class="btn-add" onclick="openModal()">+ إضافة فئة</button>
  </div>

  <div class="cats-grid">
    <?php foreach ($cats as $idx => $cat):
      $count = count($cat['items']);
      $noPrice = ($cat['has_price'] ?? true) === false;
      $isHidden = ($cat['hidden'] ?? false) === true;
    ?>
    <div class="cat-card" id="cc-<?= htmlspecialchars($cat['id']) ?>">
      <div class="cat-card-icon"><?= $cat['icon'] ?></div>
      <div class="cat-card-info">
        <strong><?= htmlspecialchars($cat['label_ar']) ?></strong>
        <span class="dim"><?= htmlspecialchars($cat['label_en'] ?? '') ?></span>
        <span class="cat-meta">
          <code><?= htmlspecialchars($cat['id']) ?></code>
          · <?= $count ?> عنصر
          <?= $noPrice ? '<span class="badge" style="background:rgba(200,149,75,0.12)">بدون أسعار</span>' : '' ?>
          <?= $isHidden ? '<span class="badge badge-hidden">مخفية</span>' : '' ?>
        </span>
      </div>
      <div class="actions">
        <button class="btn-move" <?= $idx === 0 ? 'disabled' : '' ?>
          onclick="moveCat(<?= htmlspecialchars(json_encode($cat['id']), ENT_QUOTES) ?>, 'up')">↑</button>
        <button class="btn-move" <?= $idx === count($cats) - 1 ? 'disabled' : '' ?>
          onclick="moveCat(<?= htmlspecialchars(json_encode($cat['id']), ENT_QUOTES) ?>, 'down')">↓</button>
        <button class="btn-edit"
          onclick="editCat(<?= htmlspecialchars(json_encode([
            'id'       => $cat['id'],
            'icon'     => $cat['icon'],
            'label_ar' => $cat['label_ar'],
            'label_en' => $cat['label_en'] ?? '',
            'has_price'=> ($cat['has_price'] ?? true) ? '1' : '0',
            'is_hidden'=> ($cat['hidden'] ?? false) === true ? '1' : '0',
          ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">تعديل</button>
        <button class="btn-del"
          <?= $count > 0 ? 'disabled title="انقل العناصر أولاً"' : '' ?>
          onclick="deleteCat(<?= htmlspecialchars(json_encode($cat['id']), ENT_QUOTES) ?>, '<?= htmlspecialchars($cat['label_ar']) ?>')">
          حذف
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</main>

<!-- Modal: Add / Edit -->
<div class="modal-overlay" id="modal" onclick="closeModal(event)">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="modal-title">إضافة فئة جديدة</h3>
      <button onclick="closeModal()" class="modal-close">✕</button>
    </div>
    <form id="cat-form" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" id="f-action" value="add">

      <div class="form-row">
        <div class="form-group">
          <label>المعرف (slug) * <small style="color:var(--muted);font-size:.75rem">— لا يتغير بعد الإنشاء</small></label>
          <input type="text" name="cat_id" id="f-cat-id" required placeholder="hot-drinks" dir="ltr"
                 pattern="[a-z0-9\-]+" title="أحرف إنجليزية صغيرة وأرقام وشرطات">
        </div>
        <div class="form-group">
          <label>الأيقونة (emoji)</label>
          <input type="text" name="icon" id="f-icon" placeholder="☕" style="font-size:1.4rem">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>الاسم بالعربية *</label>
          <input type="text" name="label_ar" id="f-label-ar" required placeholder="مشروبات حارة">
        </div>
        <div class="form-group">
          <label>الاسم بالإنجليزية</label>
          <input type="text" name="label_en" id="f-label-en" placeholder="Hot Drinks" dir="ltr">
        </div>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:.75rem">
        <input type="checkbox" name="no_price" id="f-no-price" style="width:18px;height:18px;accent-color:var(--gold)">
        <label for="f-no-price" style="margin:0;cursor:pointer">فئة بدون أسعار <small style="color:var(--muted)">(مثال: ألعاب)</small></label>
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:.75rem">
        <input type="checkbox" name="is_hidden_toggle" id="f-is-hidden" style="width:18px;height:18px;accent-color:var(--gold)">
        <label for="f-is-hidden" style="margin:0;cursor:pointer">إخفاء الفئة من المنيو</label>
      </div>
      <div class="modal-actions">
        <button type="button" onclick="closeModal()" class="btn-cancel">إلغاء</button>
        <button type="submit" class="btn-save" id="btn-save">حفظ</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete confirm -->
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

<style>
.cats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
}
.cat-card {
  background: var(--panel); border: 1px solid var(--border); border-radius: 14px;
  padding: 1.1rem 1.25rem; display: flex; align-items: center; gap: 1rem;
}
.cat-card-icon { font-size: 2rem; flex-shrink: 0; }
.cat-card-info { flex: 1; display: flex; flex-direction: column; gap: 3px; }
.cat-card-info strong { font-size: .97rem; color: var(--cream); }
.cat-card-info .dim   { font-size: .82rem; color: var(--muted); }
.cat-meta { font-size: .78rem; color: var(--muted); display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.cat-meta code { background: rgba(200,149,75,0.1); color: var(--gold); padding: 1px 6px; border-radius: 4px; font-size: .75rem; }
.badge-hidden { background: rgba(224,85,85,0.14); color: var(--red); }
.btn-del[disabled] { opacity: .35; cursor: not-allowed; }
.btn-move {
  width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
  background: rgba(240,222,200,0.06); color: var(--cream); border: 1px solid var(--border);
  border-radius: 8px; font-size: .9rem; cursor: pointer; transition: all .2s;
}
.btn-move:hover { border-color: var(--gold); color: var(--gold-l); }
.btn-move[disabled] { opacity: .28; cursor: not-allowed; }
</style>

<script>
const CSRF = <?= json_encode($csrf) ?>;
let pendingDeleteId = null;

function openModal(cat = null) {
  const isEdit = !!cat;
  document.getElementById('modal-title').textContent = isEdit ? 'تعديل الفئة' : 'إضافة فئة جديدة';
  document.getElementById('f-action').value   = isEdit ? 'edit' : 'add';
  document.getElementById('f-cat-id').value   = cat?.id       ?? '';
  document.getElementById('f-cat-id').readOnly = isEdit;
  document.getElementById('f-cat-id').style.opacity = isEdit ? '.5' : '1';
  document.getElementById('f-icon').value     = cat?.icon     ?? '';
  document.getElementById('f-label-ar').value = cat?.label_ar ?? '';
  document.getElementById('f-label-en').value = cat?.label_en ?? '';
  document.getElementById('f-no-price').checked = cat ? cat.has_price === '0' : false;
  document.getElementById('f-is-hidden').checked = cat ? cat.is_hidden === '1' : false;
  document.getElementById('modal').classList.add('open');
}
function editCat(cat) { openModal(cat); }
function closeModal(e) {
  if (!e || e.target === document.getElementById('modal')) {
    document.getElementById('modal').classList.remove('open');
  }
}

function deleteCat(id, name) {
  pendingDeleteId = id;
  document.getElementById('del-msg').textContent = 'هل تريد حذف فئة "' + name + '"؟ لا يمكن التراجع.';
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
  fd.append('action', 'delete');
  fd.append('cat_id', pendingDeleteId);
  const res  = await fetch('api/save_category.php', { method: 'POST', body: fd });
  const data = await res.json();
  closeDelModal();
  if (data.ok) {
    document.getElementById('cc-' + pendingDeleteId)?.remove();
    toast('تم الحذف بنجاح', 'ok');
  } else {
    toast('خطأ: ' + data.msg, 'err');
  }
}

async function moveCat(id, direction) {
  const fd = new FormData();
  fd.append('csrf_token', CSRF);
  fd.append('action', 'reorder');
  fd.append('cat_id', id);
  fd.append('direction', direction);

  try {
    const res  = await fetch('api/save_category.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      toast('تم تحديث الترتيب ✓', 'ok');
      setTimeout(() => location.reload(), 350);
    } else {
      toast('Ø®Ø·Ø£: ' + data.msg, 'err');
    }
  } catch { toast('ÙØ´Ù„ Ø§Ù„Ø§ØªØµØ§Ù„', 'err'); }
}

document.getElementById('cat-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('btn-save');
  btn.disabled = true; btn.textContent = 'جاري الحفظ...';
  const fd = new FormData(e.target);
  // Convert checkbox to has_price value
  fd.append('has_price', document.getElementById('f-no-price').checked ? '0' : '1');
  fd.append('is_hidden', document.getElementById('f-is-hidden').checked ? '1' : '0');
  try {
    const res  = await fetch('api/save_category.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      toast('تم الحفظ بنجاح ✓', 'ok');
      closeModal();
      setTimeout(() => location.reload(), 700);
    } else {
      toast('خطأ: ' + data.msg, 'err');
    }
  } catch { toast('فشل الاتصال', 'err'); }
  finally { btn.disabled = false; btn.textContent = 'حفظ'; }
});

document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  if (document.getElementById('modal').classList.contains('open'))     closeModal();
  if (document.getElementById('del-modal').classList.contains('open')) closeDelModal();
});

function toast(msg, type = 'ok') {
  const el = document.getElementById('toast');
  el.textContent = msg; el.className = 'toast show ' + type;
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 3000);
}
</script>
</body>
</html>
