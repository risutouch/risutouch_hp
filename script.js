/* ================================================
   りすたっち — Scroll Site
   ================================================ */

// ── シャボン玉バブル（sin波イージング・有機的な動き）────────
(function () {
  const layer = document.querySelector('.bubble-layer');
  if (!layer) return;

  const els = Array.from(layer.querySelectorAll('.hero-bubble'));

  // すべての動きをsin波で定義 → 端で自然に減速・加速するイージングになる
  const state = els.map((el) => {
    const baseSize = 60 + Math.random() * 105;
    el.style.cssText = `
      position:absolute; left:0; top:0;
      width:${baseSize}px; height:${baseSize}px;
      border-radius:50%; overflow:hidden;
      will-change:transform,opacity,filter;
    `;
    const cx = 15 + Math.random() * 70;  // 移動中心X (%)
    const cy = 45 + Math.random() * 35;  // 移動中心Y (%)
    return {
      baseSize,
      cx, cy,
      // X移動：2つのsin波を重ねてランダムな軌跡に
      xA1: 5  + Math.random() * 10, xF1: 0.04 + Math.random() * 0.06, xP1: Math.random() * Math.PI * 2,
      xA2: 3  + Math.random() * 6,  xF2: 0.09 + Math.random() * 0.08, xP2: Math.random() * Math.PI * 2,
      // Y移動
      yA1: 4  + Math.random() * 7,  yF1: 0.05 + Math.random() * 0.05, yP1: Math.random() * Math.PI * 2,
      yA2: 2  + Math.random() * 4,  yF2: 0.11 + Math.random() * 0.07, yP2: Math.random() * Math.PI * 2,
      // 奥行き：ゆっくりしたsin波
      dF:  0.025 + Math.random() * 0.035, dP: Math.random() * Math.PI * 2,
      // ぐにゃぐにゃ
      wobF: 0.5  + Math.random() * 0.9,   wobP: Math.random() * Math.PI * 2,
      wobA: 0.03 + Math.random() * 0.05,
    };
  });

  function tick() {
    const t  = Date.now() * 0.001;
    const lw = layer.offsetWidth;
    const lh = layer.offsetHeight;

    state.forEach((s, i) => {
      // 位置：sin波の重ね合わせ → 有機的な曲線軌跡
      const xPct = s.cx + Math.sin(t * s.xF1 + s.xP1) * s.xA1
                        + Math.sin(t * s.xF2 + s.xP2) * s.xA2;
      const yPct = s.cy + Math.sin(t * s.yF1 + s.yP1) * s.yA1
                        + Math.cos(t * s.yF2 + s.yP2) * s.yA2;

      // 奥行き：0〜1のsin波（端でゆっくり・中央で速い）
      const depth = 0.5 + 0.5 * Math.sin(t * s.dF + s.dP);

      // サイズスケール
      const depSc = 0.35 + depth * 0.65;

      // ぐにゃぐにゃ
      const wob = Math.sin(t * s.wobF + s.wobP) * s.wobA;
      const sx  = depSc * (1 + wob);
      const sy  = depSc * (1 - wob * 0.6);

      // px位置
      const px = (xPct / 100) * lw - s.baseSize / 2;
      const py = (yPct / 100) * lh - s.baseSize / 2;

      // 見た目
      const opacity = 0.28 + depth * 0.54;
      const blur    = (1 - depth) * 2.5;
      const sat     = 68 + depth * 28;

      const el = els[i];
      el.style.transform = `translate(${px.toFixed(1)}px,${py.toFixed(1)}px) scaleX(${sx.toFixed(4)}) scaleY(${sy.toFixed(4)})`;
      el.style.opacity   = opacity.toFixed(3);
      el.style.filter    = `blur(${blur.toFixed(2)}px) saturate(${sat|0}%)`;
      el.style.zIndex    = Math.round(depth * 10);
    });

    requestAnimationFrame(tick);
  }

  tick();
})();

const siteHeader = document.getElementById('site-header');

// ── ヘッダー スクロール状態 ──────────────────────
window.addEventListener('scroll', () => {
  siteHeader.classList.toggle('scrolled', window.scrollY > 60);
}, { passive: true });

// ── スクロールアニメーション（再スクロールで再発火）──
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.intersectionRatio >= 0.15) {
      entry.target.classList.add('visible');
    } else if (entry.intersectionRatio === 0 && entry.boundingClientRect.top > 0) {
      entry.target.classList.remove('visible');
    }
  });
}, { threshold: [0, 0.15] });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

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


// ── ヒーローカルーセル ──────────────────────────
(function () {
  const carousel = document.getElementById('hero-carousel');
  if (!carousel) return;

  const items = Array.from(carousel.querySelectorAll('.hero-ci'));
  const n = items.length;
  let current = 0;

  function update() {
    items.forEach((item, i) => {
      const isCenter = i === current;
      item.classList.toggle('is-center', isCenter);
      item.style.opacity = isCenter ? '1' : '0';
      item.style.zIndex  = isCenter ? '2' : '1';
    });
    document.querySelectorAll('.hero-thumb').forEach((t, i) => {
      t.classList.toggle('active', i === current);
    });
  }

  items.forEach(el => el.style.transition = 'none');
  update();
  requestAnimationFrame(() => requestAnimationFrame(() => {
    items.forEach(el => el.style.transition = 'opacity 0.8s ease');
  }));

  setInterval(() => {
    current = (current + 1) % n;
    update();
  }, 3500);
})();

// ── キャラクター吹き出し ──────────────────────
(async () => {
  const balloon = document.getElementById('hero-balloon');
  if (!balloon) return;
  try {
    const res = await fetch('data.json?v=' + Date.now());
    const data = await res.json();
    const messages = data.messages || (data.message ? [data.message] : []);
    const msg = messages[Math.floor(Math.random() * messages.length)];
    if (msg) {
      balloon.textContent = msg;
      if (data.post_url) {
        const a = document.createElement('a');
        a.href = data.post_url;
        a.target = '_blank';
        a.rel = 'noopener';
        a.textContent = data.post_label || ' →詳細を見る';
        balloon.appendChild(a);
      }
    }
  } catch {
    // data.jsonが取得できなければデフォルトのまま
  }
})();


// ── FAQアコーディオン アニメーション ─────────────
document.querySelectorAll('.faq-item').forEach(details => {
  const summary = details.querySelector('summary');
  const content = details.querySelector('.faq-content');

  summary.addEventListener('click', e => {
    e.preventDefault();
    if (details.open) {
      content.style.maxHeight = content.scrollHeight + 'px';
      requestAnimationFrame(() => requestAnimationFrame(() => {
        content.style.maxHeight = '0';
      }));
      content.addEventListener('transitionend', () => {
        details.removeAttribute('open');
      }, { once: true });
    } else {
      details.setAttribute('open', '');
      content.style.maxHeight = '0';
      requestAnimationFrame(() => requestAnimationFrame(() => {
        content.style.maxHeight = content.scrollHeight + 'px';
      }));
    }
  });
});

// ── 店舗写真クロスフェード＋ドット ───────────────
document.querySelectorAll('.shop-card-photos').forEach(photos => {
  const slides = photos.querySelectorAll('.shop-slide');
  const dots   = photos.querySelectorAll('.shop-dot');
  let current  = 0;
  let timer;

  function goTo(index) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = index;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
  }

  function startTimer() {
    timer = setInterval(() => goTo((current + 1) % slides.length), 6000);
  }

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      clearInterval(timer);
      goTo(i);
      startTimer();
    });
  });

  // 店舗ごとにランダムな初期遅延（0〜4秒）
  setTimeout(startTimer, Math.random() * 4000);
});

// ── 商品ギャラリー（ランダム表示 + 定期差し替え） ──
(function () {
  const gallery = document.getElementById('products-gallery');
  if (!gallery) return;

  const all = [
    'images/products/products01.jpg',
    'images/products/products02.jpg',
    'images/products/products03.jpg',
    'images/products/products04.jpg',
    'images/products/products05.jpg',
    'images/products/products06.jpg',
    'images/products/products07.jpg',
  ];

  const wraps = Array.from(gallery.querySelectorAll('.gallery-img-wrap'));
  const SLOTS = wraps.length;

  const pool = [...all].sort(() => Math.random() - 0.5);
  const shown = pool.slice(0, SLOTS);
  const reserve = pool.slice(SLOTS);

  // 最初から position:relative + position:absolute で統一
  const BASE = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;';
  wraps.forEach((wrap, i) => {
    wrap.style.position = 'relative';
    const img = document.createElement('img');
    img.src = shown[i];
    img.alt = 'りすたっちの焼き菓子';
    img.style.cssText = BASE;
    wrap.appendChild(img);
  });

  if (reserve.length === 0) return;

  setInterval(() => {
    if (reserve.length === 0) return;

    const slot = Math.floor(Math.random() * SLOTS);
    const next = reserve.shift();
    const wrap = wraps[slot];
    const oldImg = wrap.querySelector('img');

    const newImg = document.createElement('img');
    newImg.alt = 'りすたっちの焼き菓子';
    newImg.style.cssText = BASE + 'opacity:0;transition:opacity 3s ease;';
    wrap.appendChild(newImg);

    newImg.onload = () => {
      requestAnimationFrame(() => requestAnimationFrame(() => {
        newImg.style.opacity = '1';
      }));
    };
    newImg.src = next;

    setTimeout(() => {
      if (wrap.contains(oldImg)) wrap.removeChild(oldImg);
      newImg.style.cssText = BASE;
      reserve.push(shown[slot]);
      shown[slot] = next;
    }, 3500);
  }, 5000);
})();

// ── 草・花・どんぐり・きのこ ランダム割り当て（初回のみ）──
(function () {
  const pool = [
    'images/sozai/hana1.png',
    'images/sozai/hana2.png',
    'images/sozai/hana3.png',
    'images/sozai/kusa1.png',
    'images/sozai/kinoko1.png',
  ];

  const slots = ['.hs-kusa1', '.hs-hana1', '.hs-hana2', '.hs-hana3', '.hs-hana4']
    .map(s => document.querySelector(s))
    .filter(Boolean);

  const shuffled = [...pool].sort(() => Math.random() - 0.5);
  slots.forEach((el, i) => { el.src = shuffled[i % pool.length]; });
})();
