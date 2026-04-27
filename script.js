/* ================================================
   りすたっち — Scroll Site
   ================================================ */

// ── シャボン玉バブル（GPU transform のみ・滑らか）────────
(function () {
  const layer = document.querySelector('.bubble-layer');
  if (!layer) return;

  const els = Array.from(layer.querySelectorAll('.hero-bubble'));

  const state = els.map((el, i) => {
    const baseSize = 60 + Math.random() * 105;
    // 初期位置・サイズはCSSで固定（レイアウト変更なし）
    el.style.cssText = `
      position:absolute; left:0; top:0;
      width:${baseSize}px; height:${baseSize}px;
      border-radius:50%; overflow:hidden;
      will-change:transform,opacity,filter;
    `;
    return {
      x:          15 + Math.random() * 70,
      y:          42 + Math.random() * 40,
      depth:      Math.random(),
      baseSize,
      vx:         (Math.random() - 0.5) * 0.018,
      vy:         (Math.random() - 0.5) * 0.010,
      depthV:     (Math.random() - 0.5) * 0.0022,
      wobPhase:   Math.random() * Math.PI * 2,
      wobSpeed:   0.55 + Math.random() * 0.9,
      wobAmp:     0.03  + Math.random() * 0.05,
      floatPhase: Math.random() * Math.PI * 2,
      floatSpeed: 0.22  + Math.random() * 0.32,
    };
  });

  function tick() {
    const t  = Date.now() * 0.001;
    const lw = layer.offsetWidth;
    const lh = layer.offsetHeight;

    state.forEach((s, i) => {
      // 奥行き更新
      s.depth += s.depthV;
      if (s.depth > 1) { s.depth = 1; s.depthV *= -1; }
      if (s.depth < 0) { s.depth = 0; s.depthV *= -1; }

      // 位置更新（手前ほど速い）
      const spd = 0.25 + s.depth * 0.75;
      s.x += s.vx * spd;
      s.y += s.vy * spd;
      if (s.x < 4)  { s.x = 4;  s.vx =  Math.abs(s.vx); }
      if (s.x > 96) { s.x = 96; s.vx = -Math.abs(s.vx); }
      if (s.y < 42) { s.y = 42; s.vy =  Math.abs(s.vy); }
      if (s.y > 88) { s.y = 88; s.vy = -Math.abs(s.vy); }

      // px換算（transformのみで移動 → レイアウト不要）
      const px     = (s.x / 100) * lw - s.baseSize / 2;
      const py     = (s.y / 100) * lh - s.baseSize / 2;
      const floatY = Math.sin(t * s.floatSpeed + s.floatPhase) * 9;

      // 奥行きスケール + ぐにゃぐにゃ
      const depSc  = 0.35 + s.depth * 0.65;
      const wob    = Math.sin(t * s.wobSpeed + s.wobPhase) * s.wobAmp;
      const sx     = depSc * (1 + wob);
      const sy     = depSc * (1 - wob * 0.6);

      // 視覚（奥行きで変化）
      const opacity = 0.28 + s.depth * 0.54;
      const blur    = (1 - s.depth) * 2.5;
      const sat     = 68  + s.depth * 28;
      const zi      = Math.round(s.depth * 10);

      const el = els[i];
      el.style.transform = `translate(${px.toFixed(1)}px,${(py + floatY).toFixed(1)}px) scaleX(${sx.toFixed(4)}) scaleY(${sy.toFixed(4)})`;
      el.style.opacity   = opacity.toFixed(3);
      el.style.filter    = `blur(${blur.toFixed(2)}px) saturate(${sat|0}%)`;
      el.style.zIndex    = zi;
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
