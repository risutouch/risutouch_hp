/* ================================================
   りすたっち — Scroll Site
   ================================================ */

const C = window.__siteConfig;

// ── SVGアイコン定数 ───────────────────────────────
const SVG_INSTAGRAM = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>';
const SVG_WEB       = '<svg style="fill:none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';

// ── メインビジュアル フレーズ描画 ─────────────────
(function () {
  const data = window.__phrasesData;
  if (!Array.isArray(data)) return;
  const top = document.getElementById('top');
  if (!top) return;
  const anchor = top.querySelector('.scroll-indicator');
  data.forEach(phrase => {
    const div = document.createElement('div');
    div.className = `hp-item hp-item--${phrase.position}`;
    phrase.cols.forEach(text => {
      const span = document.createElement('span');
      span.className = 'hp-col';
      span.textContent = text;
      div.appendChild(span);
    });
    top.insertBefore(div, anchor);
  });
})();

// ── お店一覧 描画 ────────────────────────────────
(function () {
  const data = window.__shopsData;
  if (!Array.isArray(data)) return;
  const grid = document.getElementById('shops-grid');
  if (!grid) return;

  data.forEach(shop => {
    const card = document.createElement('div');
    card.className = 'shop-card reveal';

    // 写真スライド
    const photos = document.createElement('div');
    photos.className = 'shop-card-photos';
    shop.photos.forEach((src, i) => {
      const img = document.createElement('img');
      img.src = src; img.alt = shop.name;
      img.className = 'shop-slide' + (i === 0 ? ' active' : '');
      photos.appendChild(img);
    });
    const dotsDiv = document.createElement('div');
    dotsDiv.className = 'shop-dots';
    shop.photos.forEach((_, i) => {
      const btn = document.createElement('button');
      btn.className = 'shop-dot' + (i === 0 ? ' active' : '');
      btn.dataset.index = i;
      dotsDiv.appendChild(btn);
    });
    photos.appendChild(dotsDiv);
    card.appendChild(photos);

    // 基本情報
    const basic = document.createElement('div');
    basic.className = 'shop-card-basic';

    const nameDiv = document.createElement('div');
    nameDiv.className = 'shop-card-name';
    const logo = document.createElement('img');
    logo.src = shop.logo; logo.alt = shop.name; logo.className = 'shop-logo';
    const h3 = document.createElement('h3');
    h3.textContent = shop.name;
    nameDiv.appendChild(logo); nameDiv.appendChild(h3);
    basic.appendChild(nameDiv);

    ['area', 'hours', 'closed'].forEach(key => {
      const p = document.createElement('p');
      p.className = 'shop-' + key;
      p.textContent = shop[key];
      basic.appendChild(p);
    });

    // リンク
    if (shop.links && shop.links.length) {
      const linksDiv = document.createElement('div');
      linksDiv.className = 'shop-links';
      shop.links.forEach(link => {
        const a = document.createElement('a');
        a.href = link.url; a.className = 'shop-ig';
        a.target = '_blank'; a.rel = 'noopener';
        a.setAttribute('aria-label', link.type === 'instagram' ? 'Instagram' : 'ホームページ');
        a.innerHTML = link.type === 'instagram' ? SVG_INSTAGRAM : SVG_WEB;
        linksDiv.appendChild(a);
      });
      basic.appendChild(linksDiv);
    }

    const desc = document.createElement('p');
    desc.className = 'shop-desc'; desc.textContent = shop.desc;
    basic.appendChild(desc);

    card.appendChild(basic);
    grid.appendChild(card);
  });
})();

// ── 商品カルーセル 描画 ───────────────────────────
(function () {
  const data = window.__productsData;
  if (!Array.isArray(data)) return;
  const grid = document.getElementById('products-carousel');
  if (!grid) return;

  data.forEach(product => {
    const card = document.createElement('div');
    card.className = 'product-card' + (product.seasonal ? ' is-seasonal' : '');

    const imgWrap = document.createElement('div');
    imgWrap.className = 'product-img-wrap';
    const img = document.createElement('img');
    img.src = product.image; img.alt = product.name; img.loading = 'lazy';
    imgWrap.appendChild(img);
    if (product.seasonal) {
      const badge = document.createElement('span');
      badge.className = 'product-seasonal-badge';
      badge.textContent = '季節限定';
      imgWrap.appendChild(badge);
    }
    card.appendChild(imgWrap);

    const info = document.createElement('div');
    info.className = 'product-info';
    const h3 = document.createElement('h3');
    h3.className = 'product-name'; h3.textContent = product.name;
    const p = document.createElement('p');
    p.className = 'product-desc'; p.textContent = product.desc;
    info.appendChild(h3); info.appendChild(p);
    card.appendChild(info);

    grid.appendChild(card);
  });
})();

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
  const DISPLAY   = C.hero.displayMs;
  const FADE      = C.hero.fadeMs;
  const COL_DELAY = C.hero.colDelayMs;

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

    const phraseTimers = [];
    function cancelPhraseTimers() {
      phraseTimers.splice(0).forEach(id => clearTimeout(id));
    }
    function hideAllPhrases() {
      phrases.forEach(p => p.querySelectorAll('.hp-col').forEach(col => col.classList.remove('is-in')));
    }
    function showPhrase(i) {
      const item = phrases[i % phrases.length];
      if (!item) return;
      item.querySelectorAll('.hp-col').forEach((col, ci) => {
        phraseTimers.push(setTimeout(() => col.classList.add('is-in'), ci * COL_DELAY));
      });
    }

    let current = 0;
    activate(current);
    phraseTimers.push(setTimeout(() => showPhrase(current), 1800));

    if (layers.length > 1) {
      setInterval(() => {
        const prev = current;
        current = (current + 1) % layers.length;
        cancelPhraseTimers();
        hideAllPhrases();
        deactivate(prev);
        activate(current);
        phraseTimers.push(setTimeout(() => showPhrase(current), 800));
      }, DISPLAY);
    }
  });
})();

// ── ヒーロー画像＋吹き出し ───────────────────────
(function () {
  const heroRoot = document.getElementById('top');
  const balloon  = document.getElementById('hero-balloon');
  if (!heroRoot) return;

  const data = window.__heroData;
  if (!data) return;

  const { greetings, monthly, images, events } = data;
  if (!images || !images.length) return;

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

  const panVariants = ['pan-lt', 'pan-rt', 'pan-lb', 'pan-rb'];
  let lastPan = '';

  function showImage(index, withMessage = true) {
    currentIndex = (index + images.length) % images.length;
    const img = images[currentIndex];
    popupImg.src = pickRandom(img.srcs) || '';
    popupImg.alt = img.title || '';
    thumbEls.forEach((t, i) => t.classList.toggle('is-active', i === currentIndex));
    if (withMessage) showBalloon(pickRandom(img.messages));
    const link = img.link || null;
    imgWrap.classList.toggle('has-link', !!link);
    imgWrap.onclick = link ? () => {
      if (link.startsWith('#')) {
        document.querySelector(link)?.scrollIntoView({ behavior: 'smooth' });
      } else {
        window.open(link, '_blank', 'noopener');
      }
    } : null;
    const variants = panVariants.filter(v => v !== lastPan);
    const next = variants[Math.floor(Math.random() * variants.length)];
    lastPan = next;
    popupImg.classList.remove(...panVariants);
    void popupImg.offsetWidth;
    popupImg.classList.add(next);
  }

  function restartAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => showImage(currentIndex + 1), C.heroBalloon.cycleMs);
  }

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

  const CYCLE = C.heroBalloon.cycleMs;

  showImage(0, false);
  showBalloon(pickRandom(greetings[getTimeBucket()] || greetings.daytime));

  const activeEvent = getActiveEvent();
  const monthMsg    = pickRandom(monthly[String(new Date().getMonth() + 1)]);

  setTimeout(() => {
    if (activeEvent) {
      showBalloon(activeEvent.message);
      if (activeEvent.src) { popupImg.src = activeEvent.src; popupImg.alt = activeEvent.title || ''; }
      setTimeout(() => {
        if (monthMsg) {
          showBalloon(monthMsg);
          setTimeout(() => { showImage(0); restartAuto(); }, CYCLE);
        } else {
          showImage(0); restartAuto();
        }
      }, CYCLE);
    } else {
      if (monthMsg) showBalloon(monthMsg);
      setTimeout(() => { showImage(0); restartAuto(); }, monthMsg ? CYCLE : 0);
    }
  }, CYCLE);
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

// ── FAQ レンダリング ─────────────────────────────
(()=>{
  const list = document.getElementById('faq-list');
  if (!list || !window.__faqData) return;
  list.innerHTML = window.__faqData.map(item => `
    <details class="faq-item">
      <summary>${item.q}</summary>
      <div class="faq-content">${item.a}</div>
    </details>`).join('');
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
    timer = setInterval(() => goTo((current + 1) % slides.length), C.shopSlide.intervalMs);
  }

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      clearInterval(timer);
      goTo(i);
      startTimer();
    });
  });

  setTimeout(startTimer, Math.random() * 4000);
});


// ── 商品カルーセル（左右無限ループ）─────────────────
(function () {
  const grid = document.getElementById('products-carousel');
  if (!grid) return;

  const origCards = Array.from(grid.querySelectorAll('.product-card'));
  const n = origCards.length;
  if (n < 2) return;

  origCards.map(c => { const cl = c.cloneNode(true); cl.setAttribute('aria-hidden','true'); return cl; })
    .reverse().forEach(cl => grid.insertBefore(cl, grid.firstChild));
  origCards.forEach(c => { const cl = c.cloneNode(true); cl.setAttribute('aria-hidden','true'); grid.appendChild(cl); });

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

  function initPos() {
    grid.style.scrollBehavior = 'auto';
    grid.scrollLeft = sw();
    requestAnimationFrame(() => { grid.style.scrollBehavior = ''; });
  }
  requestAnimationFrame(() => requestAnimationFrame(initPos));

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

  let autoTimer = null;
  function advance() { grid.scrollBy({ left: getStep(), behavior: 'smooth' }); }
  function startAuto() { clearInterval(autoTimer); autoTimer = setInterval(advance, C.productCarousel.autoMs); }
  function stopAuto()  { clearInterval(autoTimer); autoTimer = null; }

  startAuto();

  grid.addEventListener('mouseenter', stopAuto);
  grid.addEventListener('mouseleave', () => { if (!drag) startAuto(); });

  grid.addEventListener('touchstart', () => { busy = true; stopAuto(); }, { passive: true });
  grid.addEventListener('touchend',   () => { busy = false; setTimeout(checkLoop, 200); setTimeout(startAuto, 2000); }, { passive: true });

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

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      const target = allCards()[n + i];
      if (!target) return;
      stopAuto(); busy = true;
      grid.scrollTo({ left: target.offsetLeft - (grid.clientWidth - target.offsetWidth) / 2, behavior: 'smooth' });
      setTimeout(() => { busy = false; checkLoop(); startAuto(); }, 1200);
    });
  });

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
  bee.style.cssText = 'position:absolute;left:0;top:0;width:42px;height:auto;pointer-events:auto;cursor:pointer;display:none;will-change:transform;';
  overlay.appendChild(bee);

  let curX = 0, curDisplayY = 0, curLtr = true;
  let active = false, shouldFlee = false;

  bee.addEventListener('click', () => { if (active) shouldFlee = true; });
  bee.addEventListener('touchstart', () => { if (active) shouldFlee = true; }, { passive: true });

  function flee() {
    active = false;
    const W = window.innerWidth;
    const H = window.innerHeight;
    const fleeX1   = curLtr ? -70 : W + 70;
    const fleeFlip = curLtr ? 1 : -1;
    const fleeDur  = Math.abs(fleeX1 - curX) / C.bee.fleeSpeed;
    const startX   = curX;
    const startY   = curDisplayY;
    let t0 = null;

    bee.style.display = 'block';
    function fleeFrame(ts) {
      if (!t0) t0 = ts;
      const p        = Math.min((ts - t0) / fleeDur, 1);
      const x        = startX + (fleeX1 - startX) * p;
      const displayY = startY + Math.sin(p * Math.PI * 8) * 5;
      bee.style.display = (displayY < -60 || displayY > H + 60) ? 'none' : 'block';
      bee.style.transform = `translate(${x.toFixed(1)}px,${displayY.toFixed(1)}px) scaleX(${fleeFlip}) rotate(0deg)`;
      if (p < 1) requestAnimationFrame(fleeFrame);
      else { bee.style.display = 'none'; schedule(); }
    }
    requestAnimationFrame(fleeFrame);
  }

  function fly() {
    const heroEl = document.getElementById('top');
    if (heroEl && heroEl.getBoundingClientRect().bottom > 0) { schedule(); return; }

    const W    = window.innerWidth;
    const H    = window.innerHeight;
    const ltr  = Math.random() > 0.5;
    const x0   = ltr ? -70 : W + 70;
    const x1   = ltr ? W + 70 : -70;
    const scrollY = window.scrollY;
    const topZone = Math.random() > 0.5;
    const y0   = scrollY + (topZone
      ? H * (0.08 + Math.random() * 0.14)
      : H * (0.74 + Math.random() * 0.16));
    const dur  = Math.abs(x1 - x0) / (C.bee.speedMin + Math.random() * C.bee.speedRange);
    const wAmp = 10 + Math.random() * 14;
    const wFreq = 2.5 + Math.random() * 2;
    const flip = ltr ? -1 : 1;
    let t0 = null;

    curLtr = ltr;
    active = true;
    shouldFlee = false;
    bee.style.display = 'block';

    function frame(ts) {
      if (!t0) t0 = ts;
      if (shouldFlee) { shouldFlee = false; flee(); return; }
      const p        = Math.min((ts - t0) / dur, 1);
      const x        = x0 + (x1 - x0) * p;
      const dy       = Math.sin(p * Math.PI * 2 * wFreq) * wAmp;
      const displayY = y0 + dy - window.scrollY;
      const vy       = Math.cos(p * Math.PI * 2 * wFreq) * wAmp * (Math.PI * 2 * wFreq / dur) * 1000;
      const tilt     = Math.atan2(vy, Math.abs((x1 - x0) / dur * 1000)) * (180 / Math.PI) * 0.5;
      curX = x; curDisplayY = displayY;
      bee.style.display = (displayY < -60 || displayY > H + 60) ? 'none' : 'block';
      bee.style.transform = `translate(${x.toFixed(1)}px,${displayY.toFixed(1)}px) scaleX(${flip}) rotate(${(ltr ? tilt : -tilt).toFixed(2)}deg)`;
      if (p < 1) requestAnimationFrame(frame);
      else { bee.style.display = 'none'; active = false; schedule(); }
    }
    requestAnimationFrame(frame);
  }

  function schedule() {
    setTimeout(fly, C.bee.intervalMin + Math.random() * C.bee.intervalRange);
  }

  setTimeout(fly, C.bee.firstDelayMin + Math.random() * C.bee.firstDelayRange);
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

// ── お知らせバブル ＆ モーダル ────────────────────────
(function () {
  const bubble  = document.getElementById('notice-bubble');
  const btn     = document.getElementById('notice-bubble-btn');
  const textEl  = document.getElementById('notice-bubble-text');
  const modal   = document.getElementById('notice-modal');
  const overlay = document.getElementById('notice-modal-overlay');
  const closeBtn= document.getElementById('notice-modal-close');
  const photosEl = document.getElementById('notice-modal-photos');
  const titleEl  = document.getElementById('notice-modal-title');
  const bodyEl   = document.getElementById('notice-modal-body');
  const linkEl   = document.getElementById('notice-modal-link');
  if (!bubble || !modal) return;

  const data = window.__noticeData;
  if (!Array.isArray(data)) return;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const active = data.find(n => {
    const s = new Date(n.start); s.setHours(0, 0, 0, 0);
    const e = new Date(n.end);   e.setHours(23, 59, 59, 999);
    return today >= s && today <= e;
  });
  if (!active) return;

  const key = `notice_${active.start}_${active.end}`;
  if (sessionStorage.getItem(key)) return;

  textEl.textContent = active.content;

  titleEl.textContent = active.title || active.content;
  bodyEl.textContent  = active.body  || '';

  const imgs = Array.isArray(active.images) ? active.images.filter(Boolean) : [];
  if (imgs.length > 0) {
    photosEl.innerHTML = imgs.map((src, i) =>
      `<img class="notice-slide${i === 0 ? ' active' : ''}" src="${src}" alt="">`
    ).join('');
    if (imgs.length > 1) {
      photosEl.insertAdjacentHTML('beforeend',
        `<div class="notice-dots">${imgs.map((_, i) =>
          `<button class="notice-dot${i === 0 ? ' active' : ''}" aria-label="${i+1}枚目"></button>`
        ).join('')}</div>`
      );
      const slides = photosEl.querySelectorAll('.notice-slide');
      const dots   = photosEl.querySelectorAll('.notice-dot');
      let cur = 0, slideTimer;
      function noticGoTo(idx) {
        slides[cur].classList.remove('active'); dots[cur].classList.remove('active');
        cur = idx;
        slides[cur].classList.add('active'); dots[cur].classList.add('active');
      }
      function noticeStartTimer() {
        slideTimer = setInterval(() => noticGoTo((cur + 1) % slides.length), C.shopSlide.intervalMs);
      }
      dots.forEach((d, i) => d.addEventListener('click', () => {
        clearInterval(slideTimer); noticGoTo(i); noticeStartTimer();
      }));
      noticeStartTimer();
    }
    photosEl.removeAttribute('hidden');
  }

  if (active.link) {
    linkEl.href = active.link;
    linkEl.removeAttribute('hidden');
  }

  function openModal() {
    modal.removeAttribute('hidden');
    requestAnimationFrame(() => requestAnimationFrame(() => modal.classList.add('is-open')));
    document.addEventListener('keydown', onKeyDown);
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.addEventListener('transitionend', () => modal.setAttribute('hidden', ''), { once: true });
    document.removeEventListener('keydown', onKeyDown);
  }

  function onKeyDown(e) { if (e.key === 'Escape') closeModal(); }

  btn.addEventListener('click', openModal);
  overlay.addEventListener('click', closeModal);
  closeBtn.addEventListener('click', closeModal);

  bubble.removeAttribute('hidden');
  setTimeout(() => bubble.classList.add('is-visible'), C.notice.showDelayMs);
})();
