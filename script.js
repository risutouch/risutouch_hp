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

// ── Chapter: スティッキーセクション ──────────────
const chapters = document.querySelectorAll('.chapter');

function updateChapters() {
  chapters.forEach(ch => {
    const p = getProgress(ch);
    const id = ch.dataset.chapter;

    if (id === '1') animateChapter1(ch, p);
    if (id === '2') animateChapter2(ch, p);
    if (id === '3') animateChapter3(ch, p);
    if (id === '4') animateChapter4(ch, p);
  });
}

// Chapter 1: 場所 — 背景画像がスライドイン、テキストがフェード
function animateChapter1(ch, p) {
  const img   = ch.querySelector('.ch-img-wrap');
  const texts = ch.querySelectorAll('.ch-animate');

  // 背景画像: 右からスライドイン p 0→0.25
  if (img) {
    const x = clamp(lerp(80, 0, p / 0.25), 0, 80);
    const o = clamp(lerp(0, 1, p / 0.25), 0, 1);
    img.style.transform = `translateX(${x}px)`;
    img.style.opacity   = o;
  }

  // テキスト: p 0.05→0.35 で表示、0.75→1 で消える
  texts.forEach((el, i) => {
    const delay = i * 0.08;
    const inStart  = 0.05 + delay;
    const inEnd    = 0.35 + delay;
    const outStart = 0.75;
    const outEnd   = 0.95;

    let o = 0;
    let y = 30;
    if (p >= inStart && p <= outStart) {
      const t = clamp((p - inStart) / (inEnd - inStart), 0, 1);
      o = t; y = lerp(30, 0, t);
    }
    if (p > outStart) {
      const t = clamp((p - outStart) / (outEnd - outStart), 0, 1);
      o = 1 - t; y = lerp(0, -30, t);
    }
    el.style.opacity   = o;
    el.style.transform = `translateY(${y}px)`;
  });
}

// Chapter 2: 洋菓子店 — 画像が下からズームアップ、テキストが上から落ちてくる
function animateChapter2(ch, p) {
  const img   = ch.querySelector('.ch-img-wrap');
  const texts = ch.querySelectorAll('.ch-animate');

  if (img) {
    const scale = lerp(0.7, 1, clamp(p / 0.3, 0, 1));
    const o     = clamp(p / 0.25, 0, 1);
    const y     = lerp(60, 0, clamp(p / 0.3, 0, 1));
    img.style.transform = `translateY(${y}px) scale(${scale})`;
    img.style.opacity   = o;
  }

  texts.forEach((el, i) => {
    const delay   = i * 0.1;
    const inStart = 0.1 + delay;
    const inEnd   = 0.35 + delay;
    const outStart = 0.75, outEnd = 0.92;

    let o = 0, y = -40; // 上から落ちてくる
    if (p >= inStart && p <= outStart) {
      const t = clamp((p - inStart) / (inEnd - inStart), 0, 1);
      o = t; y = lerp(-40, 0, t);
    }
    if (p > outStart) {
      const t = clamp((p - outStart) / (outEnd - outStart), 0, 1);
      o = 1 - t; y = lerp(0, 20, t);
    }
    el.style.opacity   = o;
    el.style.transform = `translateY(${y}px)`;
  });
}

// Chapter 3: 受賞 — 背景が締まりスポットライト、テキストがスケールイン
function animateChapter3(ch, p) {
  const sticky  = ch.querySelector('.chapter-sticky');
  const img     = ch.querySelector('.ch-img-wrap');
  const texts   = ch.querySelectorAll('.ch-animate');

  // 背景をだんだん暗くして受賞を際立たせる
  if (sticky) {
    const darkness = clamp(p / 0.4, 0, 0.18);
    sticky.style.background = `rgba(58,42,32,${darkness})`;
  }

  // 画像: 回転しながらフェードイン（表彰式っぽく）
  if (img) {
    const t     = clamp(p / 0.3, 0, 1);
    const scale = lerp(1.15, 1, t);
    const o     = t;
    img.style.transform = `scale(${scale})`;
    img.style.opacity   = o;
  }

  // テキスト: 中央からスケールアップ
  texts.forEach((el, i) => {
    const delay    = i * 0.12;
    const inStart  = 0.08 + delay;
    const inEnd    = 0.38 + delay;
    const outStart = 0.78, outEnd = 0.94;

    let o = 0, scale = 0.85;
    if (p >= inStart && p <= outStart) {
      const t = clamp((p - inStart) / (inEnd - inStart), 0, 1);
      o = t; scale = lerp(0.85, 1, t);
    }
    if (p > outStart) {
      const t = clamp((p - outStart) / (outEnd - outStart), 0, 1);
      o = 1 - t; scale = lerp(1, 1.05, t);
    }
    el.style.opacity   = o;
    el.style.transform = `scale(${scale})`;
  });
}

// Chapter 4: オープン — 左右から両方同時にスライドイン（扉が開く感じ）
function animateChapter4(ch, p) {
  const img   = ch.querySelector('.ch4-img-wrap');
  const texts = ch.querySelectorAll('.ch4-animate');

  // 画像: 左から
  if (img) {
    const t = clamp(p / 0.3, 0, 1);
    const x = lerp(-100, 0, t);
    const o = t;
    img.style.transform = `translateX(${x}px)`;
    img.style.opacity   = o;
  }

  // テキスト: 右から（逆方向）
  texts.forEach((el, i) => {
    const delay    = i * 0.1;
    const inStart  = 0.05 + delay;
    const inEnd    = 0.32 + delay;
    const outStart = 0.76, outEnd = 0.93;

    let o = 0, x = 100;
    if (p >= inStart && p <= outStart) {
      const t = clamp((p - inStart) / (inEnd - inStart), 0, 1);
      o = t; x = lerp(100, 0, t);
    }
    if (p > outStart) {
      const t = clamp((p - outStart) / (outEnd - outStart), 0, 1);
      o = 1 - t; x = lerp(0, -30, t);
    }
    el.style.opacity   = o;
    el.style.transform = `translateX(${x}px)`;
  });
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
      updateChapters();
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
