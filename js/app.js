'use strict';

let menuData = null;
let activeCategory = 'hot';

async function init() {
  try {
    const res = await fetch('menu.json?v=' + Date.now());
    menuData = await res.json();
  } catch (e) {
    console.error('Failed to load menu.json', e);
    return;
  }
  buildTabs();
  renderCategory(activeCategory);
  setupScrollHeader();
  setupMobileNav();
  setupReveal();
}

/* ── Tabs ── */
function buildTabs() {
  const tabsEl = document.getElementById('tabs');
  tabsEl.innerHTML = menuData.categories.map(cat => `
    <button class="tab-btn${cat.id === activeCategory ? ' active' : ''}"
            data-id="${cat.id}">
      <span>${cat.icon}</span> ${cat.label_ar}
    </button>
  `).join('');

  tabsEl.addEventListener('click', e => {
    const btn = e.target.closest('.tab-btn');
    if (!btn) return;
    tabsEl.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeCategory = btn.dataset.id;
    renderCategory(activeCategory);
  });
}

/* ── Render menu items ── */
function renderCategory(catId) {
  const cat = menuData.categories.find(c => c.id === catId);
  if (!cat) return;
  const grid = document.getElementById('menu-grid');

  grid.innerHTML = cat.items.map((item, i) => `
    <div class="menu-card" style="transition-delay:${Math.min(i * 45, 400)}ms">
      <div class="card-img-wrap">
        <img src="${item.image}" alt="${item.ar}" loading="lazy"
             onerror="this.src='images/menu/coffee.jpg'">
        <div class="card-img-overlay"></div>
        <span class="price-badge">${item.price} ريال</span>
      </div>
      <div class="card-body">
        <div class="card-names">
          <span class="name-ar">${item.ar}</span>
          <span class="name-en">${item.en}</span>
        </div>
        <div class="card-meta">
          <span class="calories">🔥 ${item.cal} سعر حراري</span>
        </div>
      </div>
    </div>
  `).join('');

  // Trigger animation
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      grid.querySelectorAll('.menu-card').forEach(c => c.classList.add('visible'));
    });
  });
}

/* ── Scroll header ── */
function setupScrollHeader() {
  const header = document.querySelector('header');
  const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 60);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

/* ── Mobile nav ── */
function setupMobileNav() {
  const toggle = document.querySelector('.nav-toggle');
  const links  = document.querySelector('.nav-links');
  toggle?.addEventListener('click', () => links.classList.toggle('open'));
  links?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));
}

/* ── Scroll reveal ── */
function setupReveal() {
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
}

document.addEventListener('DOMContentLoaded', init);
