/* ================================================
   りすたっち — Scroll Site
   ================================================ */

// ── ヒーロー背景 自動読み込み＆クロスフェード ──────
(function () {
  const heroBg = document.querySelector('.hero-bg');
  if (!heroBg) return;

  function tryLoad(url) {
    return new Promise(resolve => {
      const img = new Image();
      img.onload  = () => resolve(url);
      img.onerror = () => resolve(null);
      img.src = url;
    });
  }

  async function findImages() {
    const urls = [];
    for (let i = 1; i <= 3; i++) {
      const num = String(i).padStart(2, '0');
      const exts = ['png', 'jpg', 'jpeg', 'webp'];
      let found = null;
      for (const ext of exts) {
        found = await tryLoad(`images/main/main${num}.${ext}`);
        if (found) break;
      }
      if (found) urls.push(found);
    }
    return urls;
  }

  const KB_PC     = ['hbZoomIn', 'hbPanL', 'hbZoomOut'];
  const KB_MOBILE = ['hbMobileLR', 'hbMobileRL', 'hbMobileLR'];
  const isMobile  = window.matchMedia('(max-width: 860px)').matches;
  const DISPLAY   = isMobile ? 20000 : 10000;
  const FADE      = 2000;
  const COL_DELAY = 1500;

  findImages().then(urls => {
    if (urls.length === 0) return;

    const phrases = Array.from(document.querySelectorAll('.hp-item'));

    const layers = urls.map(url => {
      const div = document.createElement('div');
      div.className = 'hero-bg-layer';
      div.style.backgroundImage = `url('${url}')`;
      heroBg.appendChild(div);
      return div;
    });

    function activate(i) {
      const l    = layers[i];
      const anim = (isMobile ? KB_MOBILE : KB_PC)[i % 3];
      l.style.animation = `${anim} ${DISPLAY + FADE}ms ease-in-out forwards`;
      l.style.opacity   = '1';
    }
    function deactivate(i) {
      layers[i].style.opacity = '0';
      setTimeout(() => {
        layers[i].style.animation = '';
        void layers[i].offsetWidth;
      }, FADE + 100);
    }

    function showPhrase(i) {
      const item = phrases[i % phrases.length];
      if (!item) return;
      item.querySelectorAll('.hp-col').forEach((col, ci) => {
        setTimeout(() => col.classList.add('is-in'), ci * COL_DELAY);
      });
    }
    function hidePhrase(i) {
      const item = phrases[i % phrases.length];
      if (!item) return;
      item.querySelectorAll('.hp-col').forEach(col => col.classList.remove('is-in'));
    }

    let current = 0;
    activate(current);
    setTimeout(() => showPhrase(current), 1800);

    if (layers.length > 1) {
      setInterval(() => {
        const prev = current;
        current = (current + 1) % layers.length;
        hidePhrase(prev);
        deactivate(prev);
        activate(current);
        setTimeout(() => showPhrase(current), 800);
      }, DISPLAY);
    }
  });
})();

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
    const span = document.createElement('span');
    span.className = 'hero-balloon-content';
    span.textContent = text;
    balloon.innerHTML = '';
    balloon.appendChild(span);
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
  const heroCenter = heroRoot.querySelector('.hero-center');
  if (!heroCenter) return;
  heroCenter.insertBefore(popup, heroCenter.firstChild);

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

  // 2. 8秒後：イベント → 今月メッセージ の順で表示
  const activeEvent = getActiveEvent();
  const monthMsg    = pickRandom(monthly[String(new Date().getMonth() + 1)]);

  setTimeout(() => {
    if (activeEvent) {
      showBalloon(activeEvent.message);
      if (activeEvent.src) { popupImg.src = activeEvent.src; popupImg.alt = activeEvent.title || ''; }
      // 3a. イベント表示後8秒で月メッセージ or サイクル
      setTimeout(() => {
        if (monthMsg) {
          showBalloon(monthMsg);
          setTimeout(() => { showImage(0); restartAuto(); }, 8000);
        } else {
          showImage(0); restartAuto();
        }
      }, 8000);
    } else {
      // 3b. イベントなし：月メッセージ or 即サイクル
      if (monthMsg) showBalloon(monthMsg);
      setTimeout(() => { showImage(0); restartAuto(); }, monthMsg ? 8000 : 0);
    }
  }, 8000);
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


// ── 商品カルーセル（左右無限ループ）─────────────────
(function () {
  const grid = document.getElementById('products-carousel');
  if (!grid) return;

  const origCards = Array.from(grid.querySelectorAll('.product-card'));
  const n = origCards.length;
  if (n < 2) return;

  // 前後にクローンを追加：[pre1..preN, orig1..origN, app1..appN]
  // prepend: 逆順で insertBefore → DOM上は正順(pre1, pre2, ... preN)になる
  origCards.map(c => { const cl = c.cloneNode(true); cl.setAttribute('aria-hidden','true'); return cl; })
    .reverse().forEach(cl => grid.insertBefore(cl, grid.firstChild));
  origCards.forEach(c => { const cl = c.cloneNode(true); cl.setAttribute('aria-hidden','true'); grid.appendChild(cl); });

  // ドット生成（クローン後に取得したカード配列を使う）
  const allCards = () => Array.from(grid.querySelectorAll('.product-card'));
  const dotsWrap = document.createElement('div');
  dotsWrap.className = 'carousel-dots';
  for (let i = 0; i < n; i++) {
    const dot = document.createElement('button');
    dot.className = 'carousel-dot';
    dot.setAttribute('aria-label', `商品 ${i + 1}`);
    dotsWrap.appendChild(dot);
  }
  grid.closest('.carousel-outer').after(dotsWrap);
  const dots = Array.from(dotsWrap.querySelectorAll('.carousel-dot'));

  function getStep() {
    const gap = parseFloat(getComputedStyle(grid).gap) || 24;
    return grid.querySelector('.product-card').offsetWidth + gap;
  }
  function sw() { return getStep() * n; }

  // 初期スクロール位置：orig1 の先頭
  function initPos() {
    grid.style.scrollBehavior = 'auto';
    grid.scrollLeft = sw();
    requestAnimationFrame(() => { grid.style.scrollBehavior = ''; });
  }
  requestAnimationFrame(() => requestAnimationFrame(initPos));

  // ドラッグ中・タッチ中はリセットしない
  let busy = false;
  let resetTimer;

  function checkLoop() {
    if (busy) return;
    const s = grid.scrollLeft, width = sw();
    if (s >= 2 * width) {
      grid.style.scrollBehavior = 'auto';
      grid.scrollLeft = s - width;
      requestAnimationFrame(() => { grid.style.scrollBehavior = ''; });
    } else if (s < width) {
      grid.style.scrollBehavior = 'auto';
      grid.scrollLeft = s + width;
      requestAnimationFrame(() => { grid.style.scrollBehavior = ''; });
    }
  }

  grid.addEventListener('scroll', () => {
    clearTimeout(resetTimer);
    resetTimer = setTimeout(checkLoop, 150);
  }, { passive: true });

  // 自動スクロール（前進）
  let autoTimer = null;
  function advance() { grid.scrollBy({ left: getStep(), behavior: 'smooth' }); }
  function startAuto() { clearInterval(autoTimer); autoTimer = setInterval(advance, 3500); }
  function stopAuto()  { clearInterval(autoTimer); autoTimer = null; }

  startAuto();

  // ホバーで停止（PC）
  grid.addEventListener('mouseenter', stopAuto);
  grid.addEventListener('mouseleave', () => { if (!drag) startAuto(); });

  // タッチで停止（スマホ）
  grid.addEventListener('touchstart', () => { busy = true; stopAuto(); }, { passive: true });
  grid.addEventListener('touchend',   () => { busy = false; setTimeout(checkLoop, 200); setTimeout(startAuto, 2000); }, { passive: true });

  // マウスドラッグ（PC）
  let drag = null;
  function onMove(e) { if (drag) grid.scrollLeft = drag.sl - (e.clientX - drag.x); }
  function onUp() {
    if (!drag) return;
    drag = null; busy = false;
    grid.classList.remove('is-dragging');
    grid.style.scrollBehavior = '';
    window.removeEventListener('mousemove', onMove);
    window.removeEventListener('mouseup', onUp);
    setTimeout(checkLoop, 200);
    setTimeout(startAuto, 1500);
  }
  grid.addEventListener('mousedown', e => {
    drag = { x: e.clientX, sl: grid.scrollLeft };
    busy = true;
    grid.classList.add('is-dragging');
    grid.style.scrollBehavior = 'auto';
    stopAuto();
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
  });

  // 中央カードをハイライト＋ドット更新（PC）
  function updateActiveCard() {
    const center = grid.scrollLeft + grid.clientWidth / 2;
    const step = getStep();
    let closestIdx = 0, closestDist = Infinity;
    allCards().forEach((card, i) => {
      const cardCenter = card.offsetLeft + card.offsetWidth / 2;
      const dist = Math.abs(cardCenter - center);
      card.style.opacity = dist / step < 0.5 ? 1 : dist / step < 1.5 ? 0.6 : 0.3;
      card.style.cursor = dist / step < 0.5 ? 'default' : 'pointer';
      if (dist < closestDist) { closestDist = dist; closestIdx = i; }
    });
    const origIdx = closestIdx % n;
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === origIdx));
  }
  grid.addEventListener('scroll', updateActiveCard, { passive: true });
  requestAnimationFrame(() => requestAnimationFrame(updateActiveCard));

  // ドットクリックで対応カードへ
  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      const target = allCards()[n + i];
      if (!target) return;
      stopAuto(); busy = true;
      grid.scrollTo({ left: target.offsetLeft - (grid.clientWidth - target.offsetWidth) / 2, behavior: 'smooth' });
      setTimeout(() => { busy = false; checkLoop(); startAuto(); }, 1200);
    });
  });

  // 非中央カードをクリックで中央に（マウス操作時のみ）
  const isMouse = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  if (isMouse) {
    grid.addEventListener('click', e => {
      if (drag) return;
      const card = e.target.closest('.product-card');
      if (!card) return;
      const cardCenter = card.offsetLeft + card.offsetWidth / 2;
      const gridCenter = grid.scrollLeft + grid.clientWidth / 2;
      if (Math.abs(cardCenter - gridCenter) < card.offsetWidth / 2) return;
      stopAuto();
      busy = true;
      grid.scrollTo({ left: card.offsetLeft - (grid.clientWidth - card.offsetWidth) / 2, behavior: 'smooth' });
      setTimeout(() => { busy = false; checkLoop(); startAuto(); }, 1200);
    });
  }

  // リサイズ時に位置リセット
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(initPos, 200);
  });
})();

// ── ミツバチ ───────────────────────────────────────
(function () {
  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:7;overflow:hidden;';
  document.body.appendChild(overlay);

  const bee = document.createElement('img');
  bee.src = 'images/deco/bee.png';
  bee.setAttribute('aria-hidden', 'true');
  bee.style.cssText = 'position:absolute;left:0;top:0;width:42px;height:auto;pointer-events:none;display:none;will-change:transform;';
  overlay.appendChild(bee);

  function fly() {
    const W    = window.innerWidth;
    const H    = window.innerHeight;
    const ltr  = Math.random() > 0.5;
    const x0   = ltr ? -70 : W + 70;
    const x1   = ltr ? W + 70 : -70;
    // ページ内の現在のスクロール位置を基準にY座標を決める
    const scrollY = window.scrollY;
    const topZone = Math.random() > 0.5;
    const y0   = scrollY + (topZone
      ? H * (0.08 + Math.random() * 0.14)
      : H * (0.74 + Math.random() * 0.16));
    const dist = Math.abs(x1 - x0);
    const speed = 0.08 + Math.random() * 0.04; // px/ms（画面幅に関わらず一定速度）
    const dur  = dist / speed;
    const wAmp = 10 + Math.random() * 14;
    const wFreq = 2.5 + Math.random() * 2;
    // 左向き画像：右から左はそのまま、左から右は反転
    const flip = ltr ? -1 : 1;
    let t0 = null;

    bee.style.display = 'block';

    function frame(ts) {
      if (!t0) t0 = ts;
      const p = Math.min((ts - t0) / dur, 1);

      const x        = x0 + (x1 - x0) * p;
      const dy       = Math.sin(p * Math.PI * 2 * wFreq) * wAmp;
      const displayY = y0 + dy - window.scrollY;
      const vy       = Math.cos(p * Math.PI * 2 * wFreq) * wAmp * (Math.PI * 2 * wFreq / dur) * 1000;
      const vx       = Math.abs((x1 - x0) / dur * 1000);
      const tilt     = Math.atan2(vy, vx) * (180 / Math.PI) * 0.5;
      const rot      = ltr ? tilt : -tilt;

      bee.style.display = (displayY < -60 || displayY > H + 60) ? 'none' : 'block';
      bee.style.transform = `translate(${x.toFixed(1)}px,${displayY.toFixed(1)}px) scaleX(${flip}) rotate(${rot.toFixed(2)}deg)`;

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
    'images/deco/hana1.png',
    'images/deco/hana2.png',
    'images/deco/hana3.png',
    'images/deco/kusa1.png',
  ];

  const slots = ['.hs-kusa1', '.hs-hana1', '.hs-hana2', '.hs-hana3', '.hs-hana4']
    .map(s => document.querySelector(s))
    .filter(Boolean);

  const shuffled = [...pool].sort(() => Math.random() - 0.5);
  slots.forEach((el, i) => { el.src = shuffled[i % pool.length]; });
})();

// ── お知らせバブル ───────────────────────────────────
(function () {
  const bubble = document.getElementById('notice-bubble');
  const linkEl = document.getElementById('notice-bubble-link');
  const textEl = document.getElementById('notice-bubble-text');
  if (!bubble) return;

  const data = window.__noticeData;
  if (!Array.isArray(data)) return;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const active = data.find(n => {
    const s = new Date(n.start); s.setHours(0,0,0,0);
    const e = new Date(n.end);   e.setHours(23,59,59,999);
    return today >= s && today <= e;
  });
  if (!active) return;

  const key = `notice_${active.start}_${active.end}`;
  if (sessionStorage.getItem(key)) return;

  textEl.textContent = active.content;
  if (active.link) linkEl.href = active.link;

  bubble.removeAttribute('hidden');
  setTimeout(() => bubble.classList.add('is-visible'), 8000);
})();

