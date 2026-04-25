/* ================================================
   りすたっち — Scroll Site
   ================================================ */

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

// ── シャボン玉ヒーロー ──────────────────────────
(function () {
  const section = document.getElementById('top');
  const container = document.getElementById('hero-bubbles');
  if (!container || !section) return;

  const images = [
    'images/products/products01.jpg',
    'images/products/products02.jpg',
    'images/products/products03.jpg',
    'images/products/products04.jpg',
    'images/products/products05.jpg',
  ];

  const MIN_SIZE = 60, MAX_SIZE = 120;
  function sw() { return section.offsetWidth; }
  function sh() { return section.offsetHeight; }
  function clamp(v, lo, hi) { return Math.max(lo, Math.min(hi, v)); }
  function randSize() { return MIN_SIZE + Math.random() * (MAX_SIZE - MIN_SIZE); }

  const bubbles = images.map(src => {
    const el = document.createElement('div');
    el.className = 'hero-bubble';
    const img = document.createElement('img');
    img.src = src; img.alt = '';
    el.appendChild(img);
    container.appendChild(el);

    const size = randSize();
    const x = 40 + Math.random() * (sw() - size - 80);
    const y = 110 + Math.random() * (sh() - size - 200);
    el.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px;opacity:0.42;border-radius:50%;z-index:2;box-shadow:0 4px 16px rgba(58,42,32,0.12),inset 0 0 18px rgba(255,255,255,0.3);`;
    return { el, x, y, size, vx: (Math.random()-0.5)*0.4, vy: (Math.random()-0.5)*0.4, featured: false };
  });

  // ふわふわ浮遊ループ
  function tick() {
    bubbles.forEach(b => {
      if (b.featured) return;
      b.vx += (Math.random()-0.5) * 0.06;
      b.vy += (Math.random()-0.5) * 0.06;
      b.vx = clamp(b.vx, -0.55, 0.55);
      b.vy = clamp(b.vy, -0.55, 0.55);
      b.x += b.vx; b.y += b.vy;
      b.x = clamp(b.x, 20, sw()-b.size-20);
      b.y = clamp(b.y, 90, sh()-b.size-100);
      if (b.x <= 20 || b.x >= sw()-b.size-20) b.vx *= -1;
      if (b.y <= 90 || b.y >= sh()-b.size-100) b.vy *= -1;
      b.el.style.left = b.x + 'px';
      b.el.style.top  = b.y + 'px';
    });
    requestAnimationFrame(tick);
  }

  function feature(i) {
    const b = bubbles[i];
    b.featured = true;
    const mobile = sw() < 860;
    const FW = mobile ? Math.min(sw() * 0.85, 360) : Math.min(sw() * 0.55, 560);
    const fx = (sw() - FW) / 2;
    const fy = mobile ? 110 : (sh() - FW) / 2 - 30;
    b.el.classList.add('hero-bubble--transition');
    Object.assign(b.el.style, {
      width: FW+'px', height: FW+'px',
      left: fx+'px', top: fy+'px',
      opacity: '1', borderRadius: '24px',
      zIndex: '5', boxShadow: '0 16px 48px rgba(58,42,32,0.26)'
    });
  }

  function unfeature(i) {
    const b = bubbles[i];
    b.size = randSize();
    b.x = 40 + Math.random() * (sw()-b.size-80);
    b.y = 110 + Math.random() * (sh()-b.size-200);
    b.vx = (Math.random()-0.5)*0.4;
    b.vy = (Math.random()-0.5)*0.4;
    b.el.classList.add('hero-bubble--transition');
    Object.assign(b.el.style, {
      width: b.size+'px', height: b.size+'px',
      left: b.x+'px', top: b.y+'px',
      opacity: '0.42', borderRadius: '50%',
      zIndex: '2', boxShadow: '0 4px 16px rgba(58,42,32,0.12),inset 0 0 18px rgba(255,255,255,0.3)'
    });
    setTimeout(() => {
      b.el.classList.remove('hero-bubble--transition');
      b.featured = false;
    }, 1300);
  }

  let current = 0;
  requestAnimationFrame(tick);
  setTimeout(() => {
    feature(current);
    setInterval(() => {
      const prev = current;
      current = (current + 1) % bubbles.length;
      unfeature(prev);
      setTimeout(() => feature(current), 500);
    }, 4500);
  }, 800);
})();
