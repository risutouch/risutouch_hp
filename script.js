/* ================================================
   りすたっち — Scroll Story Engine
   ================================================ */

const raf = requestAnimationFrame;
let ticking = false;

// ── ユーティリティ ──────────────────────────────
function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }
function lerp(a, b, t) { return a + (b - a) * t; }

// セクション内のスクロール進行度 0→1
function getProgress(el) {
  const rect = el.getBoundingClientRect();
  const scrollable = el.offsetHeight - window.innerHeight;
  return clamp(-rect.top / scrollable, 0, 1);
}

// ── ヘッダー ────────────────────────────────────
const header = document.getElementById('site-header');
function updateHeader() {
  header.classList.toggle('scrolled', window.scrollY > 60);
}

// ── ハンバーガー ────────────────────────────────
function toggleMenu() {
  document.getElementById('hamburger').classList.toggle('open');
  document.getElementById('mobile-nav').classList.toggle('open');
  document.getElementById('mobile-overlay').classList.toggle('open');
}
function closeMenu() {
  document.getElementById('hamburger').classList.remove('open');
  document.getElementById('mobile-nav').classList.remove('open');
  document.getElementById('mobile-overlay').classList.remove('open');
}

// ── Hero: パララックス ───────────────────────────
const heroShapes = document.querySelectorAll('.hero-shape');
const heroDeco   = document.querySelector('.hero-deco');
const heroContent = document.querySelector('.hero-content');

function updateHero() {
  const y = window.scrollY;
  heroShapes.forEach((s, i) => {
    const speed = [0.3, 0.18, 0.25][i] ?? 0.2;
    s.style.transform = `translateY(${y * speed * -1}px)`;
  });
  if (heroDeco) heroDeco.style.transform = `translateY(${y * 0.12}px)`;
  if (heroContent) heroContent.style.transform = `translateY(${y * 0.22}px)`;
}

// ── Story Theater: スクロールで章が切り替わる ──
const storyTheater = document.getElementById('story-theater');
const spBgs    = document.querySelectorAll('.sp-bg');
const spPanels = document.querySelectorAll('.story-panel');
const spDots   = document.querySelectorAll('.sp-dot');

function updateStoryTheater() {
  if (!storyTheater) return;
  const rect       = storyTheater.getBoundingClientRect();
  const scrollable = storyTheater.offsetHeight - window.innerHeight;
  const p          = clamp(-rect.top / scrollable, 0, 1);

  // 4章: 各章が全体の 0.25 を担当
  const chCount = 4;
  const chF     = p * chCount;
  const chIndex = clamp(Math.floor(chF), 0, chCount - 1);
  const chP     = chF - chIndex; // 0→1 within current chapter

  // 背景フェード
  spBgs.forEach((bg, i) => {
    let o = 0;
    if (i === chIndex) {
      if      (chP < 0.12)               o = chP / 0.12;
      else if (chP < 0.82)               o = 1;
      else if (i < chCount - 1)          o = 1 - (chP - 0.82) / 0.18;
      else                               o = 1;
    }
    bg.style.opacity = o;
  });

  // テキストパネル: 下からフェードイン → 上へフェードアウト
  spPanels.forEach((panel, i) => {
    let o = 0, ty = 0;
    if (i === chIndex) {
      if (chP < 0.18) {
        const t = chP / 0.18;
        o = t; ty = lerp(40, 0, t);
      } else if (chP < 0.78) {
        o = 1; ty = 0;
      } else if (i < chCount - 1) {
        const t = (chP - 0.78) / 0.22;
        o = 1 - t; ty = lerp(0, -24, t);
      } else {
        o = 1; ty = 0;
      }
    }
    panel.style.opacity   = o;
    panel.style.transform = `translateY(${ty}px)`;
  });

  // ドット更新
  spDots.forEach((dot, i) => dot.classList.toggle('active', i === chIndex));
}

// ── Shops / Contact: 通常のIntersectionObserver ──
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── メインループ ────────────────────────────────
function onScroll() {
  if (!ticking) {
    raf(() => {
      updateHeader();
      updateHero();
      updateStoryTheater();
      ticking = false;
    });
    ticking = true;
  }
}

window.addEventListener('scroll', onScroll, { passive: true });
window.addEventListener('resize', onScroll);
onScroll(); // 初期実行

// ── Hero タイトル: 文字ごとにアニメーション ──────
document.querySelectorAll('.hero-char').forEach((el, i) => {
  el.style.animationDelay = `${0.04 * i + 0.5}s`;
});
