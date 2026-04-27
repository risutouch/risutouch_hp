/* ================================================
   りすたっち — Scroll Site
   ================================================ */

// ── シャボン玉バブル ────────────────────────────────
(function () {
  const layer = document.querySelector('.bubble-layer');
  if (!layer) return;

  const els   = Array.from(layer.querySelectorAll('.hero-bubble'));
  const ALIVE = 0, POPPING = 1, HIDDEN = 2;

  function rnd(a, b) { return a + Math.random() * (b - a); }

  function makeParams(el, baseSize) {
    el.style.cssText = `
      position:absolute;left:0;top:0;
      width:${baseSize}px;height:${baseSize}px;
      border-radius:50%;overflow:hidden;
      will-change:transform,opacity,filter;
    `;
    return {
      baseSize,
      status: ALIVE,
      popT:  0,
      born:  Date.now(),
      lastPx: 0, lastPy: 0, lastSx: 1, lastSy: 1, lastOp: 0.5,
      cx: rnd(12, 78),  // 移動中心X%
      cy: rnd(22, 72),  // 移動中心Y%（雲下から下部まで広く）
      xA1: rnd(5,12),  xF1: rnd(0.03,0.08), xP1: rnd(0, Math.PI*2),
      xA2: rnd(3, 7),  xF2: rnd(0.08,0.15), xP2: rnd(0, Math.PI*2),
      yA1: rnd(4, 9),  yF1: rnd(0.04,0.08), yP1: rnd(0, Math.PI*2),
      yA2: rnd(2, 5),  yF2: rnd(0.10,0.16), yP2: rnd(0, Math.PI*2),
      dF:  rnd(0.02, 0.05), dP: rnd(0, Math.PI*2),
      wobF: rnd(0.5, 1.4),  wobP: rnd(0, Math.PI*2), wobA: rnd(0.03, 0.06),
    };
  }

  const state = els.map(el => makeParams(el, rnd(60, 165)));

  function respawn(s, el) {
    const bs = rnd(60, 165);
    el.style.width  = bs + 'px';
    el.style.height = bs + 'px';
    Object.assign(s, makeParams(el, bs));
  }

  function tick() {
    const t  = Date.now() * 0.001;
    const lw = layer.offsetWidth;
    const lh = layer.offsetHeight;

    // 1st pass: 位置計算（衝突判定用）
    const pos = state.map(s => {
      if (s.status !== ALIVE) return null;
      const xPct  = s.cx + Math.sin(t*s.xF1+s.xP1)*s.xA1 + Math.sin(t*s.xF2+s.xP2)*s.xA2;
      const yPct  = s.cy + Math.sin(t*s.yF1+s.yP1)*s.yA1 + Math.cos(t*s.yF2+s.yP2)*s.yA2;
      const depth = 0.5 + 0.5 * Math.sin(t*s.dF+s.dP);
      const depSc = 0.35 + depth * 0.65;
      const wob   = Math.sin(t*s.wobF+s.wobP) * s.wobA;
      const sx    = depSc * (1 + wob);
      const sy    = depSc * (1 - wob * 0.6);
      const px    = (xPct/100)*lw - s.baseSize/2;
      const py    = (yPct/100)*lh - s.baseSize/2;
      const cx    = px + s.baseSize/2;
      const cy    = py + s.baseSize/2;
      const r     = s.baseSize * depSc / 2;
      const age   = Math.min(1, (Date.now() - s.born) / 1200);
      const opacity = (0.28 + depth * 0.54) * age;
      const blur    = 1.8 + (1 - depth) * 2.2;   // 常に最低1.8px
      const sat     = 68 + depth * 28;
      return { px, py, sx, sy, cx, cy, r, depth, opacity, blur, sat };
    });

    // 衝突判定
    for (let a = 0; a < state.length - 1; a++) {
      for (let b = a + 1; b < state.length; b++) {
        if (!pos[a] || !pos[b]) continue;
        if (state[a].status !== ALIVE || state[b].status !== ALIVE) continue;
        const dx = pos[a].cx - pos[b].cx;
        const dy = pos[a].cy - pos[b].cy;
        if (dx*dx + dy*dy < (pos[a].r + pos[b].r) ** 2) {
          [a, b].forEach(idx => {
            const s = state[idx]; const p = pos[idx];
            s.status = POPPING; s.popT = 0;
            s.lastPx = p.px; s.lastPy = p.py;
            s.lastSx = p.sx; s.lastSy = p.sy;
            s.lastOp = p.opacity;
          });
        }
      }
    }

    // 2nd pass: 描画
    state.forEach((s, i) => {
      const el = els[i];

      if (s.status === POPPING) {
        s.popT += 0.045;
        if (s.popT >= 1) {
          s.status = HIDDEN;
          el.style.opacity = '0';
          setTimeout(() => respawn(s, el), rnd(2000, 5000));
          return;
        }
        const e  = s.popT * s.popT;          // ease-in（速く膨らんで消える）
        const sc = 1 + e * 1.1;
        el.style.transform = `translate(${s.lastPx.toFixed(1)}px,${s.lastPy.toFixed(1)}px) scaleX(${(s.lastSx*sc).toFixed(4)}) scaleY(${(s.lastSy*sc).toFixed(4)})`;
        el.style.opacity   = (s.lastOp * (1 - s.popT * s.popT)).toFixed(3);
        el.style.filter    = `blur(${(s.popT * 6).toFixed(1)}px)`;
        return;
      }

      if (s.status === HIDDEN) return;

      const p = pos[i];
      if (!p) return;
      s.lastPx = p.px; s.lastPy = p.py; s.lastSx = p.sx; s.lastSy = p.sy; s.lastOp = p.opacity;
      el.style.transform = `translate(${p.px.toFixed(1)}px,${p.py.toFixed(1)}px) scaleX(${p.sx.toFixed(4)}) scaleY(${p.sy.toFixed(4)})`;
      el.style.opacity   = p.opacity.toFixed(3);
      el.style.filter    = `blur(${p.blur.toFixed(2)}px) saturate(${p.sat|0}%)`;
      el.style.zIndex    = Math.round(p.depth * 10);
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
