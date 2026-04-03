/* ================================================
   りすたっち — Full-page Fade Panel Engine
   ================================================ */

// ── パネル管理 ──────────────────────────────────
const panels  = Array.from(document.querySelectorAll('.panel'));
const pdots   = Array.from(document.querySelectorAll('.pdot'));
const siteHeader = document.getElementById('site-header');
let current   = 0;
let animating = false;
const LOCK_MS = 850;

function goTo(n) {
  if (animating) return;
  if (n < 0 || n >= panels.length) return;
  if (n === current) return;

  animating = true;
  panels[current].classList.remove('active');
  panels[n].classList.add('active');
  pdots.forEach((d, i) => d.classList.toggle('active', i === n));
  if (siteHeader) siteHeader.classList.toggle('scrolled', n > 0);

  current = n;
  setTimeout(() => { animating = false; }, LOCK_MS);
}

// ── ホイール ────────────────────────────────────
let wheelBuf = 0;
window.addEventListener('wheel', e => {
  e.preventDefault();
  if (animating) return;
  wheelBuf += e.deltaY;
  if (wheelBuf >  60) { goTo(current + 1); wheelBuf = 0; }
  if (wheelBuf < -60) { goTo(current - 1); wheelBuf = 0; }
}, { passive: false });

// ── タッチ ──────────────────────────────────────
let touchY = 0;
window.addEventListener('touchstart', e => {
  touchY = e.touches[0].clientY;
}, { passive: true });
window.addEventListener('touchmove', e => {
  e.preventDefault();
}, { passive: false });
window.addEventListener('touchend', e => {
  const diff = touchY - e.changedTouches[0].clientY;
  if (diff >  50) goTo(current + 1);
  if (diff < -50) goTo(current - 1);
});

// ── キーボード ──────────────────────────────────
window.addEventListener('keydown', e => {
  if (e.key === 'ArrowDown' || e.key === ' ') { e.preventDefault(); goTo(current + 1); }
  if (e.key === 'ArrowUp')                    { e.preventDefault(); goTo(current - 1); }
});

// ── ドット クリック ──────────────────────────────
pdots.forEach(d => d.addEventListener('click', () => goTo(+d.dataset.panel)));

// ── ハンバーガー ────────────────────────────────
function toggleMenu() {
  document.getElementById('hamburger').classList.toggle('open');
  document.getElementById('mobile-nav').classList.toggle('open');
  document.getElementById('mobile-overlay').classList.toggle('open');
}
function closeMenu() {
  ['hamburger', 'mobile-nav', 'mobile-overlay'].forEach(id =>
    document.getElementById(id)?.classList.remove('open')
  );
}

// ── ヒーロー文字アニメーション ────────────────
document.querySelectorAll('.hero-char').forEach((el, i) => {
  el.style.animationDelay = `${0.04 * i + 0.5}s`;
});
