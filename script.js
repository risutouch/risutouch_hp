/* ================================================
   りすたっち — Scroll Site
   ================================================ */

// ── ヒーロー画像＋吹き出し ───────────────────────
(function () {
  const heroRoot = document.getElementById('top');
  const balloon  = document.getElementById('hero-balloon');
  if (!heroRoot) return;

  // データ読み込み（hero_data.js のグローバル変数を使用）
  const data = window.__heroData;
  if (!data) return;

  const { greetings, monthly, images, events } = data;
  if (!images || !images.length) return;

  // 時間帯判定
  function getTimeBucket() {
    const h = new Date().getHours();
    if (h >= 5  && h <= 9)  return 'morning';
    if (h >= 10 && h <= 16) return 'daytime';
    if (h >= 17 && h <= 19) return 'evening';
    if (h >= 20 && h <= 23) return 'night';
    return 'late_night';
  }

  function pickRandom(arr) {
    if (!arr || !arr.length) return null;
    return arr[Math.floor(Math.random() * arr.length)];
  }

  function showBalloon(text) {
    if (!balloon || !text) return;
    balloon.classList.remove('balloon-pop');
    void balloon.offsetWidth;
    balloon.textContent = text;
    balloon.classList.add('balloon-pop');
  }

  // ポップアップ UI 構築
  const popup = document.createElement('div');
  popup.className = 'featured-bubble-popup is-visible';
  const imgWrap = document.createElement('div');
  imgWrap.className = 'featured-bubble-img-wrap';
  const popupImg = document.createElement('img');
  imgWrap.appendChild(popupImg);
  popup.appendChild(imgWrap);
  const popupThumbs = document.createElement('div');
  popupThumbs.className = 'featured-bubble-thumbs';
  popup.appendChild(popupThumbs);
  heroRoot.appendChild(popup);

  let currentIndex = 0;
  let autoTimer   = null;

  const thumbEls = images.map((image, index) => {
    const thumb = document.createElement('button');
    thumb.type  = 'button';
    thumb.className = 'featured-bubble-thumb';
    thumb.setAttribute('aria-label', image.title || `商品画像 ${index + 1}`);
    const thumbImg = document.createElement('img');
    thumbImg.src = image.srcs[0];
    thumbImg.alt = '';
    thumb.appendChild(thumbImg);
    popupThumbs.appendChild(thumb);
    thumb.addEventListener('click', () => { showImage(index); restartAuto(); });
    return thumb;
  });

  // ケンバーンズ方向パターン（ランダム選択）
  const panVariants = ['pan-lt', 'pan-rt', 'pan-lb', 'pan-rb'];
  let lastPan = '';

  function showImage(index, withMessage = true) {
    currentIndex = (index + images.length) % images.length;
    const img = images[currentIndex];
    popupImg.src = pickRandom(img.srcs) || '';
    popupImg.alt = img.title || '';
    thumbEls.forEach((t, i) => t.classList.toggle('is-active', i === currentIndex));
    if (withMessage) showBalloon(pickRandom(img.messages));
    // リンク設定
    const link = img.link || null;
    imgWrap.classList.toggle('has-link', !!link);
    imgWrap.onclick = link ? () => {
      if (link.startsWith('#')) {
        document.querySelector(link)?.scrollIntoView({ behavior: 'smooth' });
      } else {
        window.open(link, '_blank', 'noopener');
      }
    } : null;
    // アニメーションリスタート（同じ方向を連続させない）
    const variants = panVariants.filter(v => v !== lastPan);
    const next = variants[Math.floor(Math.random() * variants.length)];
    lastPan = next;
    popupImg.classList.remove(...panVariants);
    void popupImg.offsetWidth;
    popupImg.classList.add(next);
  }

  function restartAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => showImage(currentIndex + 1), 8000);
  }

  // 期間限定イベントを検索
  function getActiveEvent() {
    if (!events || !events.length) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return events.find(e => {
      const from = new Date(e.from);
      const to   = new Date(e.to);
      to.setHours(23, 59, 59, 999);
      return today >= from && today <= to;
    }) || null;
  }

  // 起動シーケンス
  // 1. まず挨拶
  showImage(0, false);
  showBalloon(pickRandom(greetings[getTimeBucket()] || greetings.daytime));

  // 2. 4秒後：イベント → 今月メッセージ の順で表示
  const activeEvent = getActiveEvent();
  const monthMsg    = pickRandom(monthly[String(new Date().getMonth() + 1)]);

  setTimeout(() => {
    if (activeEvent) {
      showBalloon(activeEvent.message);
      if (activeEvent.src) { popupImg.src = activeEvent.src; popupImg.alt = activeEvent.title || ''; }
      // 3a. イベント表示後4秒で月メッセージ or サイクル
      setTimeout(() => {
        if (monthMsg) {
          showBalloon(monthMsg);
          setTimeout(() => { showImage(0); restartAuto(); }, 4000);
        } else {
          showImage(0); restartAuto();
        }
      }, 4000);
    } else {
      // 3b. イベントなし：月メッセージ or 即サイクル
      if (monthMsg) showBalloon(monthMsg);
      setTimeout(() => { showImage(0); restartAuto(); }, monthMsg ? 4000 : 0);
    }
  }, 4000);
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

// ── ミツバチ ───────────────────────────────────────
(function () {
  // fixed オーバーレイに bee を入れることで body レイアウトに影響しない
  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:190;overflow:hidden;';
  document.body.appendChild(overlay);

  const bee = document.createElement('img');
  bee.src = 'images/sozai/bee.png';
  bee.setAttribute('aria-hidden', 'true');
  bee.style.cssText = 'position:absolute;left:0;top:0;width:26px;height:auto;pointer-events:none;display:none;will-change:transform;';
  overlay.appendChild(bee);

  function fly() {
    const W    = window.innerWidth;
    const H    = window.innerHeight;
    const ltr  = Math.random() > 0.5;
    const x0   = ltr ? -70 : W + 70;
    const x1   = ltr ? W + 70 : -70;
    const y0   = H * (0.08 + Math.random() * 0.80);
    const dur  = 5000 + Math.random() * 4000;
    const wAmp = 18 + Math.random() * 22;
    const wFreq = 2.5 + Math.random() * 2;
    // 左向き画像：右から左はそのまま、左から右は反転
    const flip = ltr ? -1 : 1;
    let t0 = null;

    bee.style.display = 'block';

    function frame(ts) {
      if (!t0) t0 = ts;
      const p = Math.min((ts - t0) / dur, 1);

      const x    = x0 + (x1 - x0) * p;
      const dy   = Math.sin(p * Math.PI * 2 * wFreq) * wAmp;
      const y    = y0 + dy;
      const vy   = Math.cos(p * Math.PI * 2 * wFreq) * wAmp * (Math.PI * 2 * wFreq / dur) * 1000;
      const vx   = Math.abs((x1 - x0) / dur * 1000);
      const tilt = Math.atan2(vy, vx) * (180 / Math.PI) * 0.5;
      const rot  = ltr ? tilt : -tilt;

      bee.style.transform = `translate(${x.toFixed(1)}px,${y.toFixed(1)}px) scaleX(${flip}) rotate(${rot.toFixed(2)}deg)`;

      if (p < 1) requestAnimationFrame(frame);
      else { bee.style.display = 'none'; schedule(); }
    }

    requestAnimationFrame(frame);
  }

  function schedule() {
    setTimeout(fly, 12000 + Math.random() * 20000);
  }

  setTimeout(fly, 3000 + Math.random() * 5000);
})();

// ── 草・花・どんぐり・きのこ ランダム割り当て（初回のみ）──
(function () {
  const pool = [
    'images/sozai/hana1.png',
    'images/sozai/hana2.png',
    'images/sozai/hana3.png',
    'images/sozai/kusa1.png',
  ];

  const slots = ['.hs-kusa1', '.hs-hana1', '.hs-hana2', '.hs-hana3', '.hs-hana4']
    .map(s => document.querySelector(s))
    .filter(Boolean);

  const shuffled = [...pool].sort(() => Math.random() - 0.5);
  slots.forEach((el, i) => { el.src = shuffled[i % pool.length]; });
})();
