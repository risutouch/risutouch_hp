/* ================================================
   りすたっち — Scroll Site
   ================================================ */

// ── シャボン玉バブル（画像ランダム＋ゆっくり漂う）────────────
(function () {
  const layer = document.querySelector('.bubble-layer');
  if (!layer) return;

  const imgs = [
    'images/products/products01.jpg',
    'images/products/products02.jpg',
    'images/products/products03.jpg',
    'images/products/products04.jpg',
    'images/products/products05.jpg',
    'images/products/products06.jpg',
    'images/products/products07.jpg',
  ];

  // 画像をランダムに割り当て
  const shuffled = [...imgs].sort(() => Math.random() - 0.5);
  const els = Array.from(layer.querySelectorAll('.hero-bubble'));
  els.forEach((el, i) => {
    el.querySelector('img').src = shuffled[i % shuffled.length];
    const ring = document.createElement('span');
    ring.className = 'bubble-ring';
    el.appendChild(ring);
  });

  // 漂いパラメータ（sin波 x2 重ね）
  const params = els.map(() => ({
    xAmp:  60 + Math.random() * 80,  yAmp:  50 + Math.random() * 60,
    xFreq: 0.02 + Math.random() * 0.03, yFreq: 0.015 + Math.random() * 0.025,
    xPh: Math.random() * Math.PI * 2,   yPh: Math.random() * Math.PI * 2,
    xAmp2: 20 + Math.random() * 30,  yAmp2: 15 + Math.random() * 25,
    xFreq2: 0.05 + Math.random() * 0.04, yFreq2: 0.04 + Math.random() * 0.04,
    xPh2: Math.random() * Math.PI * 2,  yPh2: Math.random() * Math.PI * 2,
  }));

  // 反発オフセット（スムージング済み）
  const rep = els.map(() => ({ x: 0, y: 0 }));

  // CSSベース位置を読み取る（transform適用前）
  let bases = [];
  function readBases() {
    bases = els.map(el => ({
      cx: el.offsetLeft + el.offsetWidth  / 2,
      cy: el.offsetTop  + el.offsetHeight / 2,
      r:  el.offsetWidth / 2,
    }));
  }
  readBases();
  window.addEventListener('resize', readBases, { passive: true });

  function tick() {
    const t = Date.now() * 0.001;

    // sin波オフセット計算
    const sw = params.map(p => ({
      tx: Math.sin(t*p.xFreq+p.xPh)*p.xAmp + Math.sin(t*p.xFreq2+p.xPh2)*p.xAmp2,
      ty: Math.sin(t*p.yFreq+p.yPh)*p.yAmp + Math.sin(t*p.yFreq2+p.yPh2)*p.yAmp2,
    }));

    // 反発力計算
    const tRep = els.map(() => ({ x: 0, y: 0 }));
    for (let a = 0; a < els.length - 1; a++) {
      for (let b = a + 1; b < els.length; b++) {
        const ax = bases[a].cx + sw[a].tx + rep[a].x;
        const ay = bases[a].cy + sw[a].ty + rep[a].y;
        const bx = bases[b].cx + sw[b].tx + rep[b].x;
        const by = bases[b].cy + sw[b].ty + rep[b].y;
        const dx = ax - bx, dy = ay - by;
        const dist = Math.sqrt(dx*dx + dy*dy) || 1;
        const minD = bases[a].r + bases[b].r + 65;
        if (dist < minD) {
          const str = ((minD - dist) / minD) * 80;
          const nx = dx / dist, ny = dy / dist;
          tRep[a].x += nx * str; tRep[a].y += ny * str;
          tRep[b].x -= nx * str; tRep[b].y -= ny * str;
        }
      }
    }

    // スムージング
    rep.forEach((r, i) => {
      r.x += (tRep[i].x - r.x) * 0.14;
      r.y += (tRep[i].y - r.y) * 0.14;
    });

    // 描画
    els.forEach((el, i) => {
      const tx = sw[i].tx + rep[i].x;
      const ty = sw[i].ty + rep[i].y;
      el.style.transform = `translate(${tx.toFixed(2)}px,${ty.toFixed(2)}px)`;
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
