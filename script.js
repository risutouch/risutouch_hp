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
    } else if (entry.intersectionRatio === 0) {
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

// ── ヒーロー文字アニメーション ────────────────
document.querySelectorAll('.hero-char').forEach((el, i) => {
  el.style.animationDelay = `${0.04 * i + 0.5}s`;
});

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
    if (data.images && data.images.length >= 1) {
      const img1 = document.getElementById('hero-photo-img-1');
      const img2 = document.getElementById('hero-photo-img-2');
      if (img1 && data.images[0]) img1.src = data.images[0];
      if (img2 && data.images[1]) img2.src = data.images[1];
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

  // シャッフルして初期表示
  const pool = [...all].sort(() => Math.random() - 0.5);
  const shown = pool.slice(0, SLOTS);
  const reserve = pool.slice(SLOTS);

  wraps.forEach((wrap, i) => {
    const img = document.createElement('img');
    img.src = shown[i];
    img.alt = 'りすたっちの焼き菓子';
    img.style.transition = 'opacity 0.8s ease';
    wrap.appendChild(img);
  });

  // 画像が2枚以上あれば定期差し替え
  if (all.length <= SLOTS) return;

  setInterval(() => {
    const slot = Math.floor(Math.random() * SLOTS);
    const next = reserve.splice(0, 1)[0];
    const wrap = wraps[slot];
    const oldImg = wrap.querySelector('img');

    // 新画像を下に敷いてフェードイン、古い画像はそのまま上に残す
    const newImg = document.createElement('img');
    newImg.src = next;
    newImg.alt = 'りすたっちの焼き菓子';
    newImg.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 1s ease;';
    oldImg.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;';
    wrap.style.position = 'relative';
    wrap.insertBefore(newImg, oldImg);

    requestAnimationFrame(() => requestAnimationFrame(() => {
      newImg.style.opacity = '1';
    }));

    setTimeout(() => {
      wrap.removeChild(oldImg);
      newImg.style.cssText = 'width:100%;height:100%;object-fit:cover;transition:opacity 1s ease;';
      wrap.style.position = '';
      reserve.push(shown[slot]);
      shown[slot] = next;
    }, 1100);
  }, 5000);
})();
