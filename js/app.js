'use strict';

let menuData = null;
let activeCategory = 'hot';

const SAR_SVG = `<svg class="sar-icon" viewBox="0 0 1124.14 1256.39" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <path fill="currentColor" d="M699.62,1113.02h0c-20.06,44.48-33.32,92.75-38.4,143.37l424.51-90.24c20.06-44.47,33.31-92.75,38.4-143.37l-424.51,90.24Z"/>
  <path fill="currentColor" d="M1085.73,895.8c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.33v-135.2l292.27-62.11c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.27V66.13c-50.67,28.45-95.67,66.32-132.25,110.99v403.35l-132.25,28.11V0c-50.67,28.44-95.67,66.32-132.25,110.99v525.69l-295.91,62.88c-20.06,44.47-33.33,92.75-38.42,143.37l334.33-71.05v170.26l-358.3,76.14c-20.06,44.47-33.32,92.75-38.4,143.37l375.04-79.7c30.53-6.35,56.77-24.4,73.83-49.24l68.78-101.97v-.02c7.14-10.55,11.3-23.27,11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28Z"/>
</svg>`;

async function init() {
  try {
    const res = await fetch('menu.json?v=2');
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
  setupTheme();
}

/* ── Theme toggle ── */
function setupTheme() {
  const btn = document.getElementById('theme-toggle');
  if (!btn) return;

  const saved = localStorage.getItem('theme');
  if (saved === 'light') {
    document.body.classList.add('light');
    btn.textContent = '☀️';
  }

  btn.addEventListener('click', () => {
    const isLight = document.body.classList.toggle('light');
    btn.textContent = isLight ? '☀️' : '🌙';
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
  });
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

  const isGames = cat.id === 'games';

  grid.innerHTML = cat.items.map((item, i) => `
    <div class="menu-card${isGames ? ' card-game' : ''}" style="transition-delay:${Math.min(i * 45, 400)}ms">
      <div class="card-img-wrap">
        <img src="${item.image}" alt="${item.ar}" loading="lazy"
             onerror="this.src='images/menu/coffee.jpg'">
        ${!isGames ? '<div class="card-img-overlay"></div>' : ''}
      </div>
      <div class="card-body">
        <div class="card-names">
          <span class="name-ar">${item.ar}</span>
          <span class="name-en">${item.en}</span>
        </div>
        ${!isGames ? `<div class="card-meta">
          ${item.cal !== '-' ? `<span class="calories">🔥 ${item.cal} سعر حراري</span>` : ''}
          ${item.price > 0 ? `<span class="price-badge"><span class="price-num">${item.price}</span>${SAR_SVG}</span>` : ''}
        </div>` : ''}
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
  if (!toggle || !links) return;

  toggle.addEventListener('click', e => {
    e.stopPropagation();
    links.classList.toggle('open');
  });

  // Close when clicking a link
  links.querySelectorAll('a').forEach(a => a.addEventListener('click', () => links.classList.remove('open')));

  // Close when clicking outside the nav
  document.addEventListener('click', e => {
    if (!e.target.closest('nav')) links.classList.remove('open');
  });

  // Close on scroll
  window.addEventListener('scroll', () => links.classList.remove('open'), { passive: true });
}

/* ── Scroll reveal ── */
function setupReveal() {
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
  }, { threshold: 0.12 });
  document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
}

document.addEventListener('DOMContentLoaded', init);
