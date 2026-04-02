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
    if (id === '2') animateChapter1(ch, p); // ch2 uses same animation as ch1
    if (id === '3') animateChapter1(ch, p); // ch3 uses same animation as ch1
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

// Chapter 2: 哲学 — 単語ごとにスクロールで出現
function animateChapter2(ch, p) {
  const words = ch.querySelectorAll('.word');
  const sub   = ch.querySelector('.ch-sub');

  words.forEach((w, i) => {
    const total = words.length;
    const start = (i / total) * 0.6;
    const end   = start + 0.2;
    const t = clamp((p - start) / (end - start), 0, 1);
    w.style.opacity   = t;
    w.style.transform = `translateY(${lerp(24, 0, t)}px)`;
  });

  if (sub) {
    const t = clamp((p - 0.55) / 0.25, 0, 1);
    sub.style.opacity   = t;
    sub.style.transform = `translateY(${lerp(20, 0, t)}px)`;
  }
}

// Chapter 3: お菓子写真 — スケールアップ + キャプション
function animateChapter3(ch, p) {
  const photo   = ch.querySelector('.ch-photo');
  const caption = ch.querySelector('.ch-caption');
  const overlay = ch.querySelector('.ch-overlay');

  if (photo) {
    const scale = lerp(0.82, 1, clamp(p / 0.5, 0, 1));
    const o     = clamp(p / 0.3, 0, 1);
    photo.style.transform = `scale(${scale})`;
    photo.style.opacity   = o;
  }

  if (caption) {
    const t = clamp((p - 0.35) / 0.3, 0, 1);
    caption.style.opacity   = t;
    caption.style.transform = `translateX(${lerp(40, 0, t)}px)`;
  }

  if (overlay) {
    // 最後に薄くオーバーレイ
    const t = clamp((p - 0.8) / 0.2, 0, 1);
    overlay.style.opacity = t * 0.3;
  }
}

// Chapter 4: 受賞 — Chapter 1 と同形式（画像左スライド + テキスト）
function animateChapter4(ch, p) {
  const img   = ch.querySelector('.ch4-img-wrap');
  const texts = ch.querySelectorAll('.ch4-animate');

  if (img) {
    const x = clamp(lerp(-80, 0, p / 0.25), -80, 0);
    const o = clamp(p / 0.25, 0, 1);
    img.style.transform = `translateX(${x}px)`;
    img.style.opacity   = o;
  }

  texts.forEach((el, i) => {
    const delay = i * 0.08;
    const inStart  = 0.05 + delay;
    const inEnd    = 0.35 + delay;
    const outStart = 0.75;
    const outEnd   = 0.95;

    let o = 0, y = 30;
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
