document.addEventListener('DOMContentLoaded', () => {
  renderMenu('hot');
  setupTabs();
  setupMobileNav();
  setupScrollHeader();
});

function setupTabs() {
  const tabs = document.querySelectorAll('.tab-btn');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      renderMenu(tab.dataset.category);
    });
  });
}

function renderMenu(category) {
  const grid = document.getElementById('menu-grid');
  const items = menuData[category] || [];
  grid.innerHTML = items.map(item => `
    <div class="menu-card">
      <div class="menu-card-body">
        <div class="menu-card-names">
          <span class="name-ar">${item.ar}</span>
          <span class="name-en">${item.en}</span>
        </div>
        <div class="menu-card-info">
          <span class="calories">🔥 ${item.cal} سعر</span>
          <span class="price">${item.price} <small>ريال</small></span>
        </div>
      </div>
    </div>
  `).join('');

  // Animate in
  requestAnimationFrame(() => {
    document.querySelectorAll('.menu-card').forEach((card, i) => {
      card.style.animationDelay = `${i * 40}ms`;
      card.classList.add('animate-in');
    });
  });
}

function setupMobileNav() {
  const toggle = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  toggle?.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
  navLinks?.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => navLinks.classList.remove('open'));
  });
}

function setupScrollHeader() {
  const header = document.querySelector('header');
  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 50);
  });
}
