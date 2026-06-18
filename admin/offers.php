<?php
define('NEJMT_ADMIN', 1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/offer.php';

admin_require_login();

$data   = offers_read();
$offers = $data['offers'] ?? [];
$csrf   = csrf_token();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>العروض — نجمة حور</title>
<link rel="icon" href="../images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;500;600;700;800&display=swap">
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
    <a href="dashboard.php" style="border-right-color:transparent">
      <span class="cat-icon">☕</span>
      <span>القائمة</span>
    </a>
    <a href="offers.php" class="active">
      <span class="cat-icon">🎁</span>
      <span>العروض</span>
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="../" target="_blank" class="sidebar-link">← عرض الموقع</a>
    <a href="logout.php" class="sidebar-link logout">تسجيل الخروج</a>
  </div>
</aside>

<!-- Main -->
<main class="main">
  <div class="topbar">
    <h2>🎁 العروض والتخفيضات <small>/ Offers</small></h2>
    <button class="btn-add" onclick="openModal()">+ إضافة عرض</button>
  </div>

  <?php if (empty($offers)): ?>
  <div style="text-align:center;padding:4rem 2rem;color:var(--muted)">
    <div style="font-size:3rem;margin-bottom:1rem">🎁</div>
    <p>لا توجد عروض حالياً. أضف أول عرض!</p>
  </div>
  <?php else: ?>
  <div class="offers-admin-grid">
    <?php foreach ($offers as $offer): ?>
    <div class="offer-admin-card" id="oc-<?= $offer['id'] ?>">
      <?php if ($offer['image']): ?>
      <div class="offer-admin-img">
        <img src="../<?= htmlspecialchars($offer['image']) ?>" alt=""
             onerror="this.parentElement.style.display='none'">
      </div>
      <?php endif; ?>
      <div class="offer-admin-body">
        <?php if ($offer['badge']): ?>
        <span class="offer-badge-admin"><?= htmlspecialchars($offer['badge']) ?></span>
        <?php endif; ?>
        <h3><?= htmlspecialchars($offer['title']) ?></h3>
        <?php if ($offer['desc']): ?>
        <p><?= htmlspecialchars($offer['desc']) ?></p>
        <?php endif; ?>
      </div>
      <div class="offer-admin-actions">
        <button class="btn-edit"
          onclick="editOffer(<?= htmlspecialchars(json_encode($offer, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">
          تعديل
        </button>
        <button class="btn-del"
          onclick="deleteOffer(<?= $offer['id'] ?>, '<?= htmlspecialchars(addslashes($offer['title'])) ?>')">
          حذف
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<!-- Modal: Add / Edit -->
<div class="modal-overlay" id="modal" onclick="closeModal(event)">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="modal-title">إضافة عرض جديد</h3>
      <button onclick="closeModal()" class="modal-close">✕</button>
    </div>

    <form id="offer-form" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="offer_id" id="f-offer-id" value="0">
      <input type="hidden" name="image" id="f-image" value="">

      <div class="form-group">
        <label>عنوان العرض *</label>
        <input type="text" name="title" id="f-title" required placeholder="مثال: اشتري كوبين واحصل على الثالث مجاناً">
      </div>

      <div class="form-group">
        <label>تفاصيل العرض</label>
        <textarea name="desc" id="f-desc" rows="3"
          style="width:100%;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:.7rem 1rem;color:var(--cream);font-family:inherit;font-size:.95rem;resize:vertical"
          placeholder="أضف وصفاً مختصراً للعرض..."></textarea>
      </div>

      <div class="form-group">
        <label>بادج / تصنيف (اختياري)</label>
        <input type="text" name="badge" id="f-badge" placeholder="مثال: محدود · جديد · اليوم فقط">
      </div>

      <div class="form-group">
        <label>صورة العرض (اختياري)</label>
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

<!-- Confirm delete -->
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
.offers-admin-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.25rem;
}
.offer-admin-card {
  background: var(--panel); border: 1px solid var(--border); border-radius: 16px;
  overflow: hidden; display: flex; flex-direction: column;
}
.offer-admin-img { height: 160px; overflow: hidden; }
.offer-admin-img img { width: 100%; height: 100%; object-fit: cover; }
.offer-admin-body { padding: 1rem 1.1rem; flex: 1; }
.offer-admin-body h3 { font-size: 1rem; font-weight: 700; color: var(--cream); margin-bottom: .4rem; }
.offer-admin-body p  { font-size: .85rem; color: var(--muted); line-height: 1.7; }
.offer-badge-admin {
  display: inline-block; background: rgba(200,149,75,0.15); color: var(--gold);
  border: 1px solid var(--border); border-radius: 50px; padding: 2px 10px;
  font-size: .75rem; font-weight: 700; margin-bottom: .5rem;
}
.offer-admin-actions {
  display: flex; gap: .5rem; padding: .75rem 1.1rem;
  border-top: 1px solid var(--border);
}
</style>

<script>
const CSRF = <?= json_encode($csrf) ?>;
let pendingDeleteId = null;

function openModal(offer = null) {
  document.getElementById('modal-title').textContent = offer ? 'تعديل العرض' : 'إضافة عرض جديد';
  document.getElementById('f-offer-id').value = offer?.id ?? 0;
  document.getElementById('f-title').value    = offer?.title ?? '';
  document.getElementById('f-desc').value     = offer?.desc  ?? '';
  document.getElementById('f-badge').value    = offer?.badge ?? '';
  document.getElementById('f-image').value    = offer?.image ?? '';
  const img = document.getElementById('preview-img');
  const ph  = document.getElementById('upload-placeholder');
  if (offer?.image) {
    img.src = '../' + offer.image; img.style.display = 'block'; ph.style.display = 'none';
  } else {
    img.style.display = 'none'; ph.style.display = 'flex';
  }
  document.getElementById('upload-status').textContent = '';
  document.getElementById('modal').classList.add('open');
}
function editOffer(o) { openModal(o); }
function closeModal(e) {
  if (!e || e.target === document.getElementById('modal')) {
    document.getElementById('modal').classList.remove('open');
  }
}

function deleteOffer(id, name) {
  pendingDeleteId = id;
  document.getElementById('del-msg').textContent = 'هل تريد حذف عرض "' + name + '"؟';
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
  fd.append('offer_id', pendingDeleteId);
  const res  = await fetch('api/delete_offer.php', { method: 'POST', body: fd });
  const data = await res.json();
  closeDelModal();
  if (data.ok) {
    document.getElementById('oc-' + pendingDeleteId)?.remove();
    toast('تم الحذف بنجاح', 'ok');
    if (!document.querySelector('.offer-admin-card')) location.reload();
  } else {
    toast('خطأ: ' + data.msg, 'err');
  }
}

/* File upload — reuse same upload_image.php */
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
      img.src = '../' + data.path; img.style.display = 'block';
      document.getElementById('upload-placeholder').style.display = 'none';
      statusEl.textContent = '✓ تم الرفع'; statusEl.style.color = 'var(--green)';
    } else {
      statusEl.textContent = '✗ ' + data.msg; statusEl.style.color = 'var(--red)';
    }
  } catch { statusEl.textContent = '✗ فشل الاتصال'; statusEl.style.color = 'var(--red)'; }
}

/* Save offer */
document.getElementById('offer-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('btn-save');
  btn.disabled = true; btn.textContent = 'جاري الحفظ...';
  const fd = new FormData(e.target);
  try {
    const res  = await fetch('api/save_offer.php', { method: 'POST', body: fd });
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

function toast(msg, type = 'ok') {
  const el = document.getElementById('toast');
  el.textContent = msg; el.className = 'toast show ' + type;
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 3000);
}
</script>
</body>
</html>
