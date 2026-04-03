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

// Chapter 1: 全画面イラスト、テキストが下からせり上がる
function animateChapter1(ch, p) {
  const img   = ch.querySelector('.ch1-bg-img');
  const texts = ch.querySelectorAll('.ch-animate');

  // 背景: ゆっくりフェードイン + 微妙なズームアウト
  if (img) {
    const t = clamp(p / 0.2, 0, 1);
    img.style.opacity   = t;
    img.style.transform = `scale(${lerp(1.06, 1, clamp(p / 0.5, 0, 1))})`;
  }

  // テキスト: 下からせり上がる
  texts.forEach((el, i) => {
    const delay    = i * 0.1;
    const inStart  = 0.1 + delay;
    const inEnd    = 0.36 + delay;
    const outStart = 0.78, outEnd = 0.94;
    let o = 0, y = 50;
    if (p >= inStart && p <= outStart) {
      const t = clamp((p - inStart) / (inEnd - inStart), 0, 1);
      o = t; y = lerp(50, 0, t);
    }
    if (p > outStart) {
      const t = clamp((p - outStart) / (outEnd - outStart), 0, 1);
      o = 1 - t; y = lerp(0, -20, t);
    }
    el.style.opacity   = o;
    el.style.transform = `translateY(${y}px)`;
  });
}

// Chapter 2: 大きなテキストが左から、小さい画像が下からズームアップ
function animateChapter2(ch, p) {
  const img     = ch.querySelector('.ch-img-wrap');
  const massive = ch.querySelector('.ch2-massive');
  const texts   = ch.querySelectorAll('.ch-animate:not(.ch2-massive)');

  // 大見出し: 左からスライド
  if (massive) {
    const t = clamp(p / 0.28, 0, 1);
    const outT = clamp((p - 0.76) / 0.18, 0, 1);
    massive.style.opacity   = t * (1 - outT);
    massive.style.transform = `translateX(${lerp(-80, 0, t)}px)`;
  }

  // 小画像: 下からズームアップ、回転少し
  if (img) {
    const t     = clamp((p - 0.1) / 0.3, 0, 1);
    const scale = lerp(0.65, 1, t);
    const y     = lerp(80, 0, t);
    const rot   = lerp(-6, 0, t);
    img.style.opacity   = t;
    img.style.transform = `translateY(${y}px) scale(${scale}) rotate(${rot}deg)`;
  }

  // サブテキスト: 上から落ちてくる
  texts.forEach((el, i) => {
    const delay = i * 0.12;
    const t     = clamp((p - 0.2 - delay) / 0.25, 0, 1);
    const outT  = clamp((p - 0.78) / 0.16, 0, 1);
    el.style.opacity   = t * (1 - outT);
    el.style.transform = `translateY(${lerp(-30, 0, t)}px)`;
  });
}

// Chapter 3: 背景イラストがフワッと現れ、カードが中央からスケールアップ
function animateChapter3(ch, p) {
  const bgImg  = ch.querySelector('.ch3-bg-img');
  const card   = ch.querySelector('.ch3-center-text');
  const texts  = ch.querySelectorAll('.ch-animate');

  // 背景: ゆっくりフェードイン、少しズームアウト
  if (bgImg) {
    const t = clamp(p / 0.35, 0, 1);
    bgImg.style.opacity   = t * 0.45; // 薄くして前面テキストを際立たせる
    bgImg.style.transform = `scale(${lerp(1.1, 1, t)})`;
  }

  // カード全体: スケールアップ
  if (card) {
    const t    = clamp(p / 0.3, 0, 1);
    const outT = clamp((p - 0.78) / 0.18, 0, 1);
    const scale = lerp(0.8, 1, t);
    card.style.opacity   = t * (1 - outT);
    card.style.transform = `scale(${scale})`;
  }

  // テキスト内部: 時間差フェード
  texts.forEach((el, i) => {
    const delay = i * 0.14;
    const t     = clamp((p - 0.08 - delay) / 0.28, 0, 1);
    const outT  = clamp((p - 0.8) / 0.15, 0, 1);
    el.style.opacity   = t * (1 - outT);
    el.style.transform = `translateY(${lerp(16, 0, t)}px)`;
  });
}

// Chapter 4: 画像が上から降りてくる、テキストが左右から同時イン
function animateChapter4(ch, p) {
  const img   = ch.querySelector('.ch4-img-wrap');
  const texts = ch.querySelectorAll('.ch4-animate');

  // 画像: 上からスライドダウン
  if (img) {
    const t = clamp(p / 0.28, 0, 1);
    img.style.opacity   = t;
    img.style.transform = `translateY(${lerp(-60, 0, t)}px)`;
  }

  // テキスト: 左から、時間差
  texts.forEach((el, i) => {
    const delay    = i * 0.12;
    const inStart  = 0.1 + delay;
    const inEnd    = 0.36 + delay;
    const outStart = 0.78, outEnd = 0.93;
    let o = 0, x = -60;
    if (p >= inStart && p <= outStart) {
      const t = clamp((p - inStart) / (inEnd - inStart), 0, 1);
      o = t; x = lerp(-60, 0, t);
    }
    if (p > outStart) {
      const t = clamp((p - outStart) / (outEnd - outStart), 0, 1);
      o = 1 - t;
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
