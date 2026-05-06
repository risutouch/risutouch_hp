/*
 * 🍪 焼き菓子.りすたっち 🍪
 * 
 * こんにちは、コンソールを覗いてくださってありがとうございます！
 * あなたのような技術に詳しい方に、私たちの手作り焼き菓子を
 * 知っていただけて嬉しいです。
 * 
 * このサイトは愛情を込めて一から作られています。
 * まるで私たちの焼き菓子のように...
 * 
 * もしよろしければ、ぜひ実際の焼き菓子も味わってみてください！
 * console.log('美味しさを', 'お届けします', '🥧');
 * 
 * P.S. ロゴをダブルクリックしてみてください... 何かが起こるかも？
 */

console.log('🍪 Welcome to りすたっち! コンソールまでお疲れ様です！ 🍪');
console.log('ロゴをダブルクリック（スマホではダブルタップ）すると、どんぐりが落ちてきます🌰');

// スペシャルモード案内
console.log('%c🌰🌰 スペシャルモード', 'color: #8B4513; font-weight: bold; font-size: 14px;');
console.log('enableSpecialMode() でどんぐり2個＆強い勢い！');

// コンソール表示フラグ（初期は無効）
let consoleViewerMode = false;

// スペシャルモードロゴ揺れアニメーション
let logoShakeInterval = null;

function startLogoShake() {
    const logoImage = document.querySelector('.hero-logo-image');
    if (!logoImage) return;
    
    if (logoShakeInterval) clearInterval(logoShakeInterval);
    
    let shakePhase = 0;
    logoShakeInterval = setInterval(() => {
        shakePhase += 0.2;
        const shakeX = Math.sin(shakePhase) * 3;
        const shakeY = Math.cos(shakePhase * 1.3) * 2;
        logoImage.style.transform = `translate(${shakeX}px, ${shakeY}px)`;
    }, 50);
    
}

// コンソールでスペシャルモードを有効化する処理
window.enableSpecialMode = function() {
    consoleViewerMode = true;
    startLogoShake();
    console.log('🎉 スペシャルモードが有効になりました！');
    return '🌰🌰 スペシャルモード有効！';
};

// ===== GSAP ANIMATIONS =====
if (typeof gsap !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);
}

// ===== LOADING SCREEN =====
function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loading-screen');
    setTimeout(() => {
        loadingScreen.classList.add('hidden');
        setTimeout(() => {
            loadingScreen.remove();
            // ローディング完了後にロゴをゆっくり表示
            showHeroLogo();
        }, 300);
    }, 800);
}

// ===== HERO LOGO ANIMATION =====
function showHeroLogo() {
    const heroLogo = document.querySelector('.hero-logo-image');
    if (heroLogo) {
        // ロゴ表示開始と同時にアイコンメニュー表示も開始
        setTimeout(() => {
            showScrollIndicatorAndNav();
        }, 1500); // ロゴ表示開始から1.5秒後にアイコンメニューを表示
        
        if (typeof gsap !== 'undefined') {
            gsap.to(heroLogo, {
                opacity: 1,
                duration: 4.0,
                ease: 'power2.out',
                delay: 0.3
            });
        } else {
            // GSAPが利用できない場合のフォールバック
            heroLogo.style.opacity = '1';
            heroLogo.style.transition = 'opacity 4.0s ease';
        }
    }
}

// ===== SCROLL INDICATOR AND NAVIGATION =====
function showScrollIndicatorAndNav() {
    setTimeout(() => {
        // スクロールインジケーターを表示
        const scrollIndicator = document.querySelector('.hero-scroll');
        const navigation = document.querySelector('.hero-navigation');
        
        if (scrollIndicator) {
            gsap.to(scrollIndicator, {
                opacity: 1,
                y: 0,
                duration: 1,
                ease: 'power2.out'
            });
        }
        
        if (navigation) {
            gsap.to(navigation, {
                opacity: 1,
                y: 0,
                duration: 1,
                ease: 'power2.out',
                onComplete: () => {
                    // ナビゲーション表示完了後にInstagramアイコンを表示
                    showInstagramIcon();
                }
            });
        }
    }, 50);
}

// ===== INSTAGRAM ICON ANIMATION =====
function showInstagramIcon() {
    const instagramIcon = document.querySelector('.fixed-instagram');
    
    if (instagramIcon && typeof gsap !== 'undefined') {
        gsap.to(instagramIcon, {
            opacity: 1,
            y: 0,
            duration: 1.2,
            ease: 'power2.out',
            delay: 0.5
        });
    } else if (instagramIcon) {
        // GSAPが利用できない場合のフォールバック
        setTimeout(() => {
            instagramIcon.style.opacity = '1';
            instagramIcon.style.transform = 'translateY(0)';
            instagramIcon.style.transition = 'opacity 1.2s ease, transform 1.2s ease';
        }, 500);
    }
}

// ===== RANDOM BLINK ANIMATION =====
function initRandomBlinkAnimation() {
    const logoImage = document.querySelector('.hero-logo-image');
    
    if (!logoImage) return;
    
    let isBlinking = false;
    const originalSrc = logoImage.src;
    const blinkSrc = 'assets/images/logo2.png';
    
    // 1回瞬きアニメーション関数
    function singleBlink() {
        logoImage.src = blinkSrc;
        setTimeout(() => {
            logoImage.src = originalSrc;
        }, 100);
    }
    
    // 2回瞬きアニメーション関数
    function doubleBlink() {
        logoImage.src = blinkSrc;
        setTimeout(() => {
            logoImage.src = originalSrc;
            setTimeout(() => {
                logoImage.src = blinkSrc;
                setTimeout(() => {
                    logoImage.src = originalSrc;
                }, 100);
            }, 150);
        }, 100);
    }
    
    // 瞬きアニメーション関数
    function blink() {
        if (isBlinking) return;
        
        isBlinking = true;
        
        // 30%の確率で2回瞬き、70%の確率で1回瞬き
        if (Math.random() < 0.3) {
            doubleBlink();
            setTimeout(() => {
                isBlinking = false;
            }, 400);
        } else {
            singleBlink();
            setTimeout(() => {
                isBlinking = false;
            }, 200);
        }
    }
    
    // ランダムな間隔で瞬きを実行
    function scheduleRandomBlink() {
        // 2秒～5秒の間隔でランダムに瞬き（頻度向上）
        const randomDelay = Math.random() * 3000 + 2000;
        
        setTimeout(() => {
            // 瞬きを実行
            blink();
            
            // 次の瞬きをスケジュール
            scheduleRandomBlink();
        }, randomDelay);
    }
    
    // 初回の瞬きをスケジュール
    scheduleRandomBlink();
}

// ===== SCROLL INDICATOR FADE =====
function initScrollIndicatorFade() {
    const scrollIndicator = document.querySelector('.hero-scroll');
    
    if (!scrollIndicator) return;
    
    window.addEventListener('scroll', () => {
        const scrollY = window.scrollY;
        const windowHeight = window.innerHeight;
        const fadeStart = windowHeight * 0.1; // 10%スクロールで消え始める
        const fadeEnd = windowHeight * 0.3; // 30%スクロールで完全に消える
        
        if (scrollY <= fadeStart) {
            scrollIndicator.style.opacity = '1';
        } else if (scrollY >= fadeEnd) {
            scrollIndicator.style.opacity = '0';
        } else {
            const progress = (scrollY - fadeStart) / (fadeEnd - fadeStart);
            scrollIndicator.style.opacity = (1 - progress).toString();
        }
    });
}

// ===== ABOUT SCROLL ANIMATION =====
function initAboutScrollAnimation() {
    const aboutSection = document.querySelector('.about-scroll-section');
    const aboutContainer = document.querySelector('.about-container');
    const panels = document.querySelectorAll('.about-panel');
    
    if (!aboutSection || !aboutContainer || panels.length === 0) return;
    
    // Debug: Found panels
    
    // 初期状態：最初のパネルを表示
    panels[0].classList.add('active');
    
    // スクロール連動アニメーション
    gsap.registerPlugin(ScrollTrigger);
    
    // モバイルデバイス検出
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    // セクションをピン留めし、スクロールに応じてパネルを切り替え
    let tl = gsap.timeline({
        scrollTrigger: {
            trigger: aboutSection,
            start: 'top top',
            end: () => `+=${window.innerHeight * 2}`, // 2画面分の高さ
            scrub: isMobile ? 0.5 : 1, // モバイルでscrub値を調整
            pin: aboutContainer,
            pinSpacing: true,
            invalidateOnRefresh: true,
            refreshPriority: 1,
            anticipatePin: 1,
            onUpdate: (self) => {
                const progress = self.progress;
                const totalPanels = panels.length;
                const panelIndex = Math.min(Math.floor(progress * totalPanels), totalPanels - 1);
                
                // Debug: Progress tracking
                
                // すべてのパネルから active クラスを削除
                panels.forEach((panel, index) => {
                    panel.classList.remove('active');
                    if (index < panelIndex) {
                        panel.style.transform = 'translateX(-100px)';
                        panel.style.opacity = '0';
                    } else if (index === panelIndex) {
                        panel.style.transform = 'translateX(0)';
                        panel.style.opacity = '1';
                    } else {
                        panel.style.transform = 'translateX(100px)';
                        panel.style.opacity = '0';
                    }
                });
                
                // 現在のパネルに active クラスを追加
                if (panels[panelIndex]) {
                    panels[panelIndex].classList.add('active');
                }
            },
            onComplete: () => {
                // スクロール完了後にScrollTriggerをリフレッシュ
                ScrollTrigger.refresh();
            },
            onLeave: () => {
                // セクションを離れる時にScrollTriggerをリフレッシュ
                if (isMobile) {
                    setTimeout(() => {
                        ScrollTrigger.refresh();
                    }, 300); // モバイルでは少し長めの遅延
                } else {
                    setTimeout(() => {
                        ScrollTrigger.refresh();
                    }, 100);
                }
            },
            onEnterBack: () => {
                // セクションに戻る時もリフレッシュ
                if (isMobile) {
                    setTimeout(() => {
                        ScrollTrigger.refresh();
                    }, 300);
                }
            }
        }
    });
    
    // ウィンドウリサイズ時にScrollTriggerをリフレッシュ
    window.addEventListener('resize', () => {
        ScrollTrigger.refresh();
    });
    
    // モバイル向け追加対策：orientation change時のリフレッシュ
    if (isMobile) {
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                ScrollTrigger.refresh();
            }, 500);
        });
        
        // モバイルでの慣性スクロール後のリフレッシュ
        let mobileScrollTimeout;
        window.addEventListener('scroll', () => {
            if (mobileScrollTimeout) {
                clearTimeout(mobileScrollTimeout);
            }
            mobileScrollTimeout = setTimeout(() => {
                ScrollTrigger.refresh();
            }, 300);
        }, { passive: true });
    }
}

// ===== DOM CONTENT LOADED =====
document.addEventListener('DOMContentLoaded', function() {
    // ローディング画面を非表示
    hideLoadingScreen();
    
    // データ読み込み
    loadProducts();
    loadShops();
    // loadNews(); // トピックシステムに変更したため無効化
    loadTopics();
    loadMenu();
    
    // ナビゲーション設定
    setupNavigation();
    
    // Handle window resize for menu display
    window.addEventListener('resize', () => {
        loadMenu(); // Reload menu to switch between mobile/desktop display
    });
    
    // ヒーローナビゲーション設定
    setupHeroNavigation();
    
    // GSAP アニメーション初期化
    initAnimations();
    
    // ランダム瞬きアニメーション初期化
    initRandomBlinkAnimation();
    
    // イースターエッグ初期化
    initEasterEgg();
    
    // スクロールインジケーターのフェード初期化
    initScrollIndicatorFade();
    
    // About セクションのスクロールアニメーション（一時無効化）
    // initAboutScrollAnimation();
    
    // スクロール連動アニメーション
    initScrollAnimations();
});

// ===== PRODUCT DATA LOADING =====
let allProducts = [];
let displayedProducts = 0;
const initialProductsCount = 6; // 初回表示商品数
const productsPerPage = 3; // もっと見るで表示する商品数

async function loadProducts() {
    try {
        const response = await fetch('assets/data/products.json');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data || !data.products || !Array.isArray(data.products)) {
            throw new Error('商品データの形式が正しくありません');
        }
        
        // 表示可能な商品のみをフィルタリング
        allProducts = data.products.filter(product => product.visible !== false);
        displayedProducts = 0;
        
        // Debug: Product data loaded
        displayInitialProducts();
    } catch (error) {
        // Error: Failed to load product data
        displayProductsError(error.message);
    }
}

function displayInitialProducts() {
    const productsGrid = document.getElementById('products-grid');
    productsGrid.innerHTML = ''; // 既存の商品をクリア
    
    displayedProducts = 0;
    
    // 初回は6件表示
    const initialProducts = allProducts.slice(0, initialProductsCount);
    initialProducts.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
    
    displayedProducts = initialProducts.length;
    
    // もっと見るボタンの表示制御
    updateLoadMoreButton();
}

function displayMoreProducts() {
    const productsGrid = document.getElementById('products-grid');
    const loadMoreContainer = document.getElementById('load-more-container');
    
    // 現在表示中の商品数から次のページの商品を取得
    const nextProducts = allProducts.slice(displayedProducts, displayedProducts + productsPerPage);
    
    nextProducts.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
    
    displayedProducts += nextProducts.length;
    
    // さらに読み込むボタンの表示/非表示
    updateLoadMoreButton();
}

function updateLoadMoreButton() {
    let loadMoreContainer = document.getElementById('load-more-container');
    
    // コンテナが存在しない場合は作成
    if (!loadMoreContainer) {
        loadMoreContainer = document.createElement('div');
        loadMoreContainer.id = 'load-more-container';
        loadMoreContainer.className = 'load-more-container';
        
        // products-gridの親要素（products-showcase）に追加
        const productsGrid = document.getElementById('products-grid');
        const productsShowcase = productsGrid ? productsGrid.parentElement : null;
        
        if (productsShowcase) {
            productsShowcase.appendChild(loadMoreContainer);
        } else {
            // Error: Products showcase container not found
            return;
        }
    }
    
    // まだ表示していない商品があるかチェック
    if (displayedProducts < allProducts.length) {
        loadMoreContainer.innerHTML = `
            <button class="btn btn-load-more" onclick="displayMoreProducts()">
                さらに表示 (${allProducts.length - displayedProducts}件)
            </button>
        `;
        loadMoreContainer.style.display = 'block';
    } else {
        loadMoreContainer.style.display = 'none';
    }
}

function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    
    // 季節限定商品の判定
    if (product.seasonal) {
        card.classList.add('seasonal');
    }
    
    const seasonalBadge = product.seasonal ? 
        '<span class="seasonal-badge">季節限定</span>' : '';
    
    // 商品画像の処理
    const hasImage = product.images && product.images.length > 0;
    const productImage = hasImage ? product.images[0] : 'assets/images/loading.png';
    
    card.innerHTML = `
        <div class="product-image">
            <img src="${productImage}" alt="${product.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px 16px 0 0;">
        </div>
        <div class="product-content">
            <h3 class="product-name">${product.name}</h3>
            <p class="product-description">${product.description}</p>
            <div class="product-footer">
                ${seasonalBadge}
            </div>
        </div>
    `;
    
    // 画像の読み込みエラーを検出してloading.pngを表示
    const productImg = card.querySelector('.product-image img');
    if (productImg) {
        productImg.onerror = function() {
            this.src = 'assets/images/loading.png';
            this.style.objectFit = 'contain';
            this.style.padding = '60px';
        };
    }
    
    // ホバー時の追加アニメーション
    card.addEventListener('mouseenter', function() {
        gsap.to(this.querySelector('.product-image'), {
            scale: 1.1,
            rotation: 5,
            duration: 0.3,
            ease: 'power2.out'
        });
    });
    
    card.addEventListener('mouseleave', function() {
        gsap.to(this.querySelector('.product-image'), {
            scale: 1,
            rotation: 0,
            duration: 0.3,
            ease: 'power2.out'
        });
    });
    
    return card;
}

function getProductEmoji(category) {
    const emojis = {
        'クッキー': '🍪',
        'マドレーヌ': '🧁',
        'フィナンシェ': '🥧',
        'ブラウニー': '🍫',
        'タルト': '🥧',
        'スコーン': '🍰',
        'パン': '🍞',
        'ケーキ': '🎂'
    };
    return emojis[category] || '🍪';
}

function displayProductsError(errorMessage = '不明なエラーが発生しました') {
    const productsGrid = document.getElementById('products-grid');
    productsGrid.innerHTML = `
        <div class="error-message">
            <p>商品情報の読み込みに失敗しました。</p>
            <p>エラー: ${errorMessage}</p>
            <p>しばらくしてから再度お試しください。</p>
        </div>
    `;
}

// ===== SHOP DATA LOADING =====
async function loadShops() {
    try {
        const response = await fetch('assets/data/shops.json');
        const data = await response.json();
        displayShops(data.shops);
    } catch (error) {
        // Error: Failed to load shop data
        displayShopsError();
    }
}

function displayShops(shops) {
    const shopsGrid = document.getElementById('shops-grid');
    const shopNavigation = document.getElementById('shop-navigation');
    
    // 店舗ナビゲーションを生成
    shops.forEach((shop, index) => {
        // visible が false の店舗は表示しない
        if (shop.visible === false) {
            return;
        }
        
        const navItem = document.createElement('a');
        navItem.className = 'shop-nav-item';
        navItem.href = `#shop-${shop.id}`;
        
        navItem.innerHTML = `
            ${shop.logo ? 
                `<img src="${shop.logo}" alt="${shop.name}" class="shop-nav-icon">` : 
                `<div class="shop-nav-emoji">🏪</div>`
            }
            <div class="shop-nav-name">${shop.name}</div>
        `;
        
        // クリック時のスムーススクロール
        navItem.addEventListener('click', (e) => {
            e.preventDefault();
            const targetCard = document.querySelector(`[data-shop-id="${shop.id}"]`);
            if (targetCard) {
                targetCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
        
        shopNavigation.appendChild(navItem);
    });
    
    // 店舗カードを生成
    shops.forEach(shop => {
        // visible が false の店舗は表示しない
        if (shop.visible === false) {
            return;
        }
        
        const shopCard = createShopCard(shop);
        shopCard.setAttribute('data-shop-id', shop.id);
        shopsGrid.appendChild(shopCard);
    });
}

function createShopCard(shop) {
    const card = document.createElement('div');
    card.className = 'shop-card';
    
    const socialLinks = createSocialLinks(shop.social);
    let currentImageIndex = 1; // 通常は2枚目の画像（index 1）を表示
    
    card.innerHTML = `
        <div class="shop-image-container">
            ${shop.shopImages && shop.shopImages.length > 0 ? 
                `<img src="${shop.shopImages[0]}" alt="${shop.name}" class="shop-main-image">
                 <img src="${shop.shopImages[0]}" alt="${shop.name}" class="shop-main-image-overlay">` : 
                '<div class="shop-image-placeholder">🏪</div>'}
            ${shop.logo ? `<img src="${shop.logo}" alt="${shop.name}" class="shop-icon">` : `<div class="shop-logo">🏪</div>`}
        </div>
        <div class="shop-content">
            <h3 class="shop-name">${shop.name}</h3>
            <div class="shop-address">${shop.address}</div>
            <div class="shop-hours">${shop.hours} / 定休日: ${shop.closed}</div>
            <p class="shop-description">${shop.description}</p>
            <div class="shop-links">
                ${shop.website ? `<a href="${shop.website}" target="_blank" class="shop-link">ウェブサイト</a>` : ''}
                ${socialLinks}
            </div>
        </div>
    `;
    
    // 店舗画像がない場合の処理
    if (!shop.shopImages || shop.shopImages.length === 0) {
        const imageContainer = card.querySelector('.shop-image-container');
        const placeholder = imageContainer.querySelector('.shop-image-placeholder');
        // プレースホルダーのままでOK
    }
    
    // 複数店舗画像がある場合の自動切り替え機能（ディゾルブ効果）
    if (shop.shopImages && shop.shopImages.length >= 2) {
        const mainImage = card.querySelector('.shop-main-image');
        const overlayImage = card.querySelector('.shop-main-image-overlay');
        
        if (mainImage && overlayImage) {
            let imageIndex = 0; // 最初の店舗画像から開始
            
            // 3-4秒間隔で画像を自動切り替え（ディゾルブ）
            setInterval(() => {
                imageIndex = (imageIndex + 1) % shop.shopImages.length;
                
                // 新しい画像を事前に読み込み
                const newImage = new Image();
                newImage.onload = () => {
                    // オーバーレイ画像に新しい画像をセット
                    overlayImage.src = shop.shopImages[imageIndex];
                    overlayImage.style.opacity = '1';
                    
                    // ディゾルブ完了後にメイン画像を更新
                    setTimeout(() => {
                        mainImage.src = shop.shopImages[imageIndex];
                        overlayImage.style.opacity = '0';
                    }, 800);
                };
                newImage.src = shop.shopImages[imageIndex];
                
            }, 3500 + Math.random() * 1000); // 3.5-4.5秒の間隔でランダム
        }
    }
    
    return card;
}

function createShopThumbnails(images) {
    const thumbnailsHtml = images.map((image, index) => 
        `<div class="shop-thumbnail" data-index="${index}"></div>`
    ).join('');
    
    return `
        <div class="shop-images">
            ${thumbnailsHtml}
        </div>
    `;
}

function createSocialLinks(social) {
    if (!social) return '';
    
    let links = '';
    
    if (social.instagram) {
        links += `<a href="https://instagram.com/${social.instagram.replace('@', '')}" target="_blank" class="shop-link">Instagram</a>`;
    }
    
    if (social.twitter) {
        links += `<a href="https://twitter.com/${social.twitter.replace('@', '')}" target="_blank" class="shop-link">Twitter</a>`;
    }
    
    if (social.facebook) {
        links += `<a href="https://facebook.com/${social.facebook}" target="_blank" class="shop-link">Facebook</a>`;
    }
    
    return links;
}

function displayShopsError() {
    const shopsGrid = document.getElementById('shops-grid');
    shopsGrid.innerHTML = `
        <div class="error-message">
            <p>店舗情報の読み込みに失敗しました。</p>
            <p>しばらくしてから再度お試しください。</p>
        </div>
    `;
}

// ===== NEWS DATA LOADING =====
async function loadNews() {
    try {
        const response = await fetch('assets/data/news.json');
        const data = await response.json();
        displayNews(data.news);
    } catch (error) {
        // Error: Failed to load news data
        displayNewsError();
    }
}

function displayNews(news) {
    const newsGrid = document.getElementById('news-grid');
    
    // 公開されているお知らせのみ表示
    const publishedNews = news.filter(item => item.published);
    
    if (publishedNews.length === 0) {
        newsGrid.innerHTML = '<p style="text-align: center; color: var(--gray); grid-column: 1 / -1;">現在、お知らせはありません。</p>';
        return;
    }
    
    // 最新3件を表示
    const latestNews = publishedNews.slice(0, 3);
    
    latestNews.forEach(newsItem => {
        const newsCard = createNewsCard(newsItem);
        newsGrid.appendChild(newsCard);
    });
}

function createNewsCard(newsItem) {
    const card = document.createElement('div');
    card.className = 'news-card';
    
    const formattedDate = new Date(newsItem.date).toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    card.innerHTML = `
        <div class="news-image">
            ${newsItem.image ? 
                `<img src="${newsItem.image}" alt="${newsItem.title}" loading="lazy">` : 
                '<div class="news-image-placeholder">📢</div>'
            }
        </div>
        <div class="news-content">
            <div class="news-date">${formattedDate}</div>
            <h3 class="news-title">${newsItem.title}</h3>
            <p class="news-description">${newsItem.description}</p>
            ${newsItem.source === 'instagram' ? 
                '<div class="news-source"><span class="news-source-tag">Instagram</span></div>' : 
                ''
            }
        </div>
    `;
    
    // Instagram投稿の場合、クリックでInstagramに遷移
    if (newsItem.sourceUrl) {
        card.addEventListener('click', () => {
            window.open(newsItem.sourceUrl, '_blank');
        });
    }
    
    return card;
}

function displayNewsError() {
    const newsGrid = document.getElementById('news-grid');
    newsGrid.innerHTML = `
        <div style="text-align: center; color: var(--gray); grid-column: 1 / -1;">
            <p>お知らせの読み込みに失敗しました。</p>
            <p>しばらくしてから再度お試しください。</p>
        </div>
    `;
}

// ===== TOPICS DATA LOADING =====
async function loadTopics() {
    try {
        const response = await fetch('assets/data/topics_config.json');
        const data = await response.json();
        
        // Debug: Topic configuration loaded
        displayTopics(data);
    } catch (error) {
        // Error: Failed to load topic configuration
        // デフォルト設定で表示（全て表示）
        displayTopics({});
    }
}

function displayTopics(topicsConfig) {
    const entertainmentGrid = document.getElementById('entertainment-grid');
    if (!entertainmentGrid) return;
    
    // 各トピックの表示/非表示を制御
    const sweetsQuizItem = entertainmentGrid.querySelector('.entertainment-item[onclick*="sweets-quiz"]');
    const seasonalCalendarItem = entertainmentGrid.querySelector('.entertainment-item[onclick*="seasonal-calendar"]');
    
    // sweets-quiz の表示制御
    if (sweetsQuizItem) {
        const sweetsQuizPublished = topicsConfig['sweets-quiz']?.published !== false;
        sweetsQuizItem.style.display = sweetsQuizPublished ? 'block' : 'none';
        
        // サムネイル画像の更新
        if (topicsConfig['sweets-quiz']?.thumbnail) {
            const thumbnailImg = document.getElementById('sweets-quiz-thumbnail');
            if (thumbnailImg) {
                thumbnailImg.src = topicsConfig['sweets-quiz'].thumbnail + '?t=' + Date.now();
            }
        }
        
        // タイトルと説明の更新
        if (topicsConfig['sweets-quiz']?.title) {
            const titleElement = sweetsQuizItem.querySelector('h3');
            if (titleElement) {
                titleElement.textContent = topicsConfig['sweets-quiz'].title;
            }
        }
        if (topicsConfig['sweets-quiz']?.description) {
            const descElement = sweetsQuizItem.querySelector('p');
            if (descElement) {
                descElement.textContent = topicsConfig['sweets-quiz'].description;
            }
        }
    }
    
    // seasonal-calendar の表示制御
    if (seasonalCalendarItem) {
        const seasonalCalendarPublished = topicsConfig['seasonal-calendar']?.published !== false;
        seasonalCalendarItem.style.display = seasonalCalendarPublished ? 'block' : 'none';
        
        // サムネイル画像の更新
        if (topicsConfig['seasonal-calendar']?.thumbnail) {
            const thumbnailImg = document.getElementById('seasonal-calendar-thumbnail');
            if (thumbnailImg) {
                thumbnailImg.src = topicsConfig['seasonal-calendar'].thumbnail + '?t=' + Date.now();
            }
        }
        
        // タイトルと説明の更新
        if (topicsConfig['seasonal-calendar']?.title) {
            const titleElement = seasonalCalendarItem.querySelector('h3');
            if (titleElement) {
                titleElement.textContent = topicsConfig['seasonal-calendar'].title;
            }
        }
        if (topicsConfig['seasonal-calendar']?.description) {
            const descElement = seasonalCalendarItem.querySelector('p');
            if (descElement) {
                descElement.textContent = topicsConfig['seasonal-calendar'].description;
            }
        }
    }
}

// ===== MENU DISPLAY (シンプル版) =====
let currentMenuImage = '';

async function loadMenu() {
    try {
        const response = await fetch('assets/data/menu.json');
        const data = await response.json();
        
        if (data.menu && data.menu.image) {
            currentMenuImage = data.menu.image;
            showMenuButton('メニュー表');
        } else {
            showMenuButton('メニュー表（準備中）');
        }
    } catch (error) {
        // Warning: Failed to load menu information
        // フォールバック - 既存の画像パスを使用
        currentMenuImage = 'assets/uploads/menus/menu_20250719215549_687bf8454ab3d.png';
        showMenuButton('メニュー表');
    }
}

function showMenuButton(menuName) {
    const menuDisplay = document.getElementById('menu-display');
    
    if (!menuDisplay) {
        // Error: menu-display element not found
        return;
    }
    
    menuDisplay.innerHTML = `
        <div class="menu-container">
            <button class="menu-open-btn" onclick="openResponsiveMenuModal()">
                <span class="menu-icon">📋</span>
                メニューを見る
            </button>
        </div>
    `;
}


function openResponsiveMenuModal() {
    if (!currentMenuImage) {
        alert('メニューが見つかりません');
        return;
    }
    
    // 既存のモーダルを削除
    const existingModal = document.getElementById('responsive-menu-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // レスポンシブなモーダルを作成
    const modal = document.createElement('div');
    modal.id = 'responsive-menu-modal';
    modal.innerHTML = `
        <div class="responsive-modal-overlay" onclick="closeResponsiveMenuModal()">
            <div class="responsive-modal-content" onclick="event.stopPropagation()">
                <div class="responsive-modal-header">
                    <h3>メニュー表</h3>
                    <button onclick="closeResponsiveMenuModal()" class="close-btn">&times;</button>
                </div>
                <div class="responsive-modal-body">
                    <div class="menu-image-container" id="responsive-menu-container">
                        <img src="${currentMenuImage}" alt="メニュー表" class="responsive-menu-image" id="responsive-menu-image">
                    </div>
                </div>
                <div class="responsive-modal-footer">
                    <p>★印は季節商品。すべて税込価格です。</p>
                    <p class="touch-help">📱 ピンチでズーム・ドラッグで移動できます</p>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // モーダル表示
    setTimeout(() => {
        modal.classList.add('active');
        initializeResponsiveMenu();
    }, 10);
}

let scale = 1;
let translateX = 0;
let translateY = 0;
let isDragging = false;
let startX = 0;
let startY = 0;
let startTranslateX = 0;
let startTranslateY = 0;
let isPinching = false;
let initialDistance = 0;
let initialScale = 1;

function initializeResponsiveMenu() {
    const container = document.getElementById('responsive-menu-container');
    const image = document.getElementById('responsive-menu-image');
    
    if (!container || !image) return;
    
    // 初期スケールを画面幅に合わせて設定
    image.onload = function() {
        fitImageToScreen();
    };
    
    if (image.complete) {
        fitImageToScreen();
    }
    
    // タッチイベント
    container.addEventListener('touchstart', handleResponsiveTouchStart, {passive: false});
    container.addEventListener('touchmove', handleResponsiveTouchMove, {passive: false});
    container.addEventListener('touchend', handleResponsiveTouchEnd, {passive: false});
    
    // マウスイベント
    container.addEventListener('mousedown', handleResponsiveMouseDown);
    container.addEventListener('mousemove', handleResponsiveMouseMove);
    container.addEventListener('mouseup', handleResponsiveMouseUp);
    container.addEventListener('mouseleave', handleResponsiveMouseUp);
    
    // ホイールでズーム
    container.addEventListener('wheel', handleResponsiveWheel, {passive: false});
    
}

function fitImageToScreen() {
    const container = document.getElementById('responsive-menu-container');
    const image = document.getElementById('responsive-menu-image');
    
    if (!container || !image) return;
    
    const containerWidth = container.clientWidth;
    const containerHeight = container.clientHeight;
    const imageWidth = image.naturalWidth;
    const imageHeight = image.naturalHeight;
    
    // 画面幅に合わせてスケールを計算（文字が読める程度に拡大）
    const widthScale = containerWidth / imageWidth;
    const heightScale = containerHeight / imageHeight;
    scale = Math.min(widthScale * 1.2, heightScale * 1.2); // 20%余裕を持たせる
    
    translateX = 0;
    translateY = 0;
    isZoomed = false;
    
    updateImageTransform();
}


function updateImageTransform() {
    const image = document.getElementById('responsive-menu-image');
    if (image) {
        // Googleマップスタイルの即座の反応
        image.style.transition = 'none';
        image.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
    }
}

function initializeSwipeMenu() {
    const wrapper = document.getElementById('menu-image-wrapper');
    const image = document.getElementById('swipeable-menu-image');
    
    if (!wrapper || !image) return;
    
    // 画像が読み込まれたら最大オフセットを計算
    image.onload = function() {
        const containerWidth = wrapper.clientWidth;
        const imageWidth = image.clientWidth;
        maxOffset = Math.max(0, imageWidth - containerWidth);
        updateProgressBar();
    };
    
    // すでに読み込まれている場合
    if (image.complete) {
        const containerWidth = wrapper.clientWidth;
        const imageWidth = image.clientWidth;
        maxOffset = Math.max(0, imageWidth - containerWidth);
        updateProgressBar();
    }
    
    // タッチイベント
    wrapper.addEventListener('touchstart', handleTouchStart, {passive: false});
    wrapper.addEventListener('touchmove', handleTouchMove, {passive: false});
    wrapper.addEventListener('touchend', handleTouchEnd, {passive: false});
    
    // マウスイベント（デスクトップ用）
    wrapper.addEventListener('mousedown', handleMouseDown);
    wrapper.addEventListener('mousemove', handleMouseMove);
    wrapper.addEventListener('mouseup', handleMouseUp);
    wrapper.addEventListener('mouseleave', handleMouseUp);
}

function handleTouchStart(e) {
    isDragging = true;
    startX = e.touches[0].clientX;
    startOffset = currentOffset;
    e.preventDefault();
}

function handleTouchMove(e) {
    if (!isDragging) return;
    
    const currentX = e.touches[0].clientX;
    const deltaX = startX - currentX;
    const newOffset = Math.max(0, Math.min(maxOffset, startOffset + deltaX));
    
    updateImagePosition(newOffset);
    e.preventDefault();
}

function handleTouchEnd(e) {
    isDragging = false;
    e.preventDefault();
}

function handleMouseDown(e) {
    isDragging = true;
    startX = e.clientX;
    startOffset = currentOffset;
    e.preventDefault();
}

function handleMouseMove(e) {
    if (!isDragging) return;
    
    const deltaX = startX - e.clientX;
    const newOffset = Math.max(0, Math.min(maxOffset, startOffset + deltaX));
    
    updateImagePosition(newOffset);
    e.preventDefault();
}

function handleMouseUp(e) {
    isDragging = false;
}

function updateImagePosition(offset) {
    currentOffset = offset;
    const image = document.getElementById('swipeable-menu-image');
    if (image) {
        image.style.transform = `translateX(-${offset}px)`;
    }
    updateProgressBar();
}

function updateProgressBar() {
    const progressFill = document.getElementById('menu-progress-fill');
    if (progressFill && maxOffset > 0) {
        const progress = (currentOffset / maxOffset) * 100;
        progressFill.style.width = `${progress}%`;
    }
}

function getDistance(touch1, touch2) {
    const dx = touch1.clientX - touch2.clientX;
    const dy = touch1.clientY - touch2.clientY;
    return Math.sqrt(dx * dx + dy * dy);
}

function handleResponsiveTouchStart(e) {
    if (e.touches.length === 1) {
        // 単指タッチ（ドラッグ）
        isDragging = true;
        isPinching = false;
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        startTranslateX = translateX;
        startTranslateY = translateY;
    } else if (e.touches.length === 2) {
        // 二指タッチ（ピンチ）
        isDragging = false;
        isPinching = true;
        initialDistance = getDistance(e.touches[0], e.touches[1]);
        initialScale = scale;
    }
    e.preventDefault();
}

function handleResponsiveTouchMove(e) {
    if (isPinching && e.touches.length === 2) {
        // Googleマップスタイルのピンチズーム
        const currentDistance = getDistance(e.touches[0], e.touches[1]);
        const scaleRatio = currentDistance / initialDistance;
        const newScale = initialScale * scaleRatio;
        
        // スケールを適切な範囲に制限
        scale = Math.max(0.3, Math.min(5, newScale));
        
        updateImageTransform();
    } else if (isDragging && e.touches.length === 1) {
        // ドラッグ処理
        const deltaX = (e.touches[0].clientX - startX) / scale;
        const deltaY = (e.touches[0].clientY - startY) / scale;
        
        translateX = startTranslateX + deltaX;
        translateY = startTranslateY + deltaY;
        
        updateImageTransform();
    }
    e.preventDefault();
}

function handleResponsiveTouchEnd(e) {
    if (e.touches.length === 0) {
        isDragging = false;
        isPinching = false;
    } else if (e.touches.length === 1 && isPinching) {
        // ピンチから単指に変化した場合
        isPinching = false;
        isDragging = true;
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        startTranslateX = translateX;
        startTranslateY = translateY;
    }
    e.preventDefault();
}

function handleResponsiveMouseDown(e) {
    isDragging = true;
    startX = e.clientX;
    startY = e.clientY;
    startTranslateX = translateX;
    startTranslateY = translateY;
    e.preventDefault();
}

function handleResponsiveMouseMove(e) {
    if (!isDragging) return;
    
    const deltaX = (e.clientX - startX) / scale;
    const deltaY = (e.clientY - startY) / scale;
    
    translateX = startTranslateX + deltaX;
    translateY = startTranslateY + deltaY;
    
    updateImageTransform();
    e.preventDefault();
}

function handleResponsiveMouseUp(e) {
    isDragging = false;
}

function handleResponsiveWheel(e) {
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    scale = Math.max(0.5, Math.min(5, scale * delta));
    updateImageTransform();
    e.preventDefault();
}

function closeResponsiveMenuModal() {
    const modal = document.getElementById('responsive-menu-modal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
            // リセット
            scale = 1;
            translateX = 0;
            translateY = 0;
            isPinching = false;
            isDragging = false;
        }, 300);
    }
}

function closeSimpleMenuModal() {
    closeResponsiveMenuModal();
}

// グローバル関数として登録
window.openSimpleMenuModal = openSimpleMenuModal;
window.closeSimpleMenuModal = closeSimpleMenuModal;
window.openResponsiveMenuModal = openResponsiveMenuModal;
window.closeResponsiveMenuModal = closeResponsiveMenuModal;
window.displayMoreProducts = displayMoreProducts;

function createMenuModal(menu) {
    // Remove existing modal if it exists
    const existingModal = document.getElementById('menu-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create new modal
    const modal = document.createElement('div');
    modal.id = 'menu-modal';
    modal.className = 'menu-modal';
    
    let modalHTML = `
        <div class="menu-modal-content">
            <div class="menu-modal-header">
                <h3>メニュー表</h3>
                <span class="menu-close" onclick="closeMenuModal()">&times;</span>
            </div>
            <div class="menu-modal-body">
                <div class="menu-table">
    `;
    
    menu.categories.forEach(category => {
        modalHTML += `
            <div class="menu-category">
                <div class="menu-category-header">
                    <h4 class="menu-category-name">${category.name}</h4>
                    ${category.note ? `<span class="menu-category-price">${category.note}</span>` : ''}
                </div>
                <div class="menu-items">
        `;
        
        category.items.forEach(item => {
            const seasonalMark = item.seasonal ? '★' : '';
            
            modalHTML += `
                <div class="menu-item">
                    <div class="menu-item-left">
                        <div class="menu-item-name">
                            ${seasonalMark}${item.name}
                        </div>
                        ${item.note ? `<div class="menu-item-note">${item.note}</div>` : ''}
                    </div>
                    <div class="menu-item-price">
                        ${item.price ? '¥' + item.price.toLocaleString() : ''}
                    </div>
                </div>
            `;
        });
        
        modalHTML += `
                </div>
            </div>
        `;
    });
    
    modalHTML += `
                </div>
            </div>
            <div class="menu-modal-footer">
    `;
    
    menu.notes.forEach(note => {
        modalHTML += `<p class="menu-note">${note}</p>`;
    });
    
    modalHTML += `
                <div class="menu-update-note">最終更新: ${menu.lastUpdated}</div>
            </div>
        </div>
    `;
    
    modal.innerHTML = modalHTML;
    document.body.appendChild(modal);
    
    // Close modal when clicking outside
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeMenuModal();
        }
    });
}

function openMenuModal() {
    const modal = document.getElementById('menu-modal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(() => modal.classList.add('active'), 10);
    }
}

function openSimpleMenuModal() {
    // シンプルなメニューモーダルを開く関数
    openMenuModal();
}

function closeMenuModal() {
    const modal = document.getElementById('menu-modal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

// ===== NAVIGATION SETUP =====
function setupNavigation() {
    const footerLinks = document.querySelectorAll('.footer-links a');
    
    // フッターリンクのクリック処理
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                const targetPosition = targetElement.offsetTop;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // スクロール完了後にScrollTriggerをリフレッシュ
                setTimeout(() => {
                    ScrollTrigger.refresh();
                }, 1000);
            }
        });
    });
    
    // スクロール終了の検出とリフレッシュ
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            ScrollTrigger.refresh();
        }, 150);
    });
}

// ===== HERO NAVIGATION SETUP =====
function setupHeroNavigation() {
    const heroNavItems = document.querySelectorAll('.hero-nav-item');
    
    heroNavItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                const targetPosition = targetElement.offsetTop;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // スクロール完了後にScrollTriggerをリフレッシュ
                setTimeout(() => {
                    ScrollTrigger.refresh();
                }, 1000);
            }
        });
    });
}

// ===== GSAP ANIMATIONS =====
function initAnimations() {
    // ヒーローセクションアニメーション（要素が存在する場合のみ）
    if (document.querySelector('.hero-title')) {
        gsap.from('.hero-title', {
            duration: 1.5,
            y: 100,
            opacity: 0,
            ease: 'power3.out'
        });
    }
    
    if (document.querySelector('.hero-subtitle')) {
        gsap.from('.hero-subtitle', {
            duration: 1.2,
            y: 50,
            opacity: 0,
            delay: 0.5,
            ease: 'power2.out'
        });
    }
    
    if (document.querySelector('.squirrel-illustration')) {
        gsap.from('.squirrel-illustration', {
            duration: 2,
            scale: 0,
            rotation: 360,
            delay: 1,
            ease: 'back.out(1.7)'
        });
    }
    
    if (document.querySelector('.acorn-decoration')) {
        gsap.from('.acorn-decoration', {
            duration: 1.5,
            scale: 0,
            rotation: -180,
            delay: 1.2,
            ease: 'back.out(1.7)',
            stagger: 0.2
        });
    }
    
    gsap.from('.scroll-indicator', {
        duration: 1,
        y: 30,
        opacity: 0,
        delay: 1.8,
        ease: 'power2.out'
    });
    
    // スクロールトリガーアニメーション
    gsap.utils.toArray('.section-title').forEach(title => {
        gsap.from(title, {
            scrollTrigger: {
                trigger: title,
                start: 'top 80%',
                end: 'bottom 20%',
                toggleActions: 'play none none reverse'
            },
            duration: 1,
            y: 50,
            opacity: 0,
            ease: 'power2.out'
        });
    });
    
    // Aboutセクションアニメーション（各パネル個別に）
    gsap.utils.toArray('.about-panel').forEach((panel, index) => {
        const aboutText = panel.querySelector('.about-text');
        const aboutImg = panel.querySelector('.about-img');
        
        if (aboutText) {
            gsap.from(aboutText, {
                scrollTrigger: {
                    trigger: panel,
                    start: 'top 80%',
                    end: 'bottom 20%',
                    toggleActions: 'play none none reverse'
                },
                duration: 1,
                x: -100,
                opacity: 0,
                ease: 'power2.out',
                delay: 0.2
            });
        }
        
        if (aboutImg) {
            gsap.from(aboutImg, {
                scrollTrigger: {
                    trigger: panel,
                    start: 'top 80%',
                    end: 'bottom 20%',
                    toggleActions: 'play none none reverse'
                },
                duration: 1,
                x: 100,
                opacity: 0,
                ease: 'power2.out'
            });
        }
    });
    
    // コンタクトセクションアニメーション
    gsap.from('.contact-info', {
        scrollTrigger: {
            trigger: '.contact-info',
            start: 'top 80%',
            end: 'bottom 20%',
            toggleActions: 'play none none reverse'
        },
        duration: 1,
        x: -100,
        opacity: 0,
        ease: 'power2.out'
    });
    
    if (document.querySelector('.contact-hours')) {
        gsap.from('.contact-hours', {
            scrollTrigger: {
                trigger: '.contact-hours',
                start: 'top 80%',
                end: 'bottom 20%',
                toggleActions: 'play none none reverse'
            },
            duration: 1,
            x: 100,
            opacity: 0,
            delay: 0.3,
            ease: 'power2.out'
        });
    }
    
    // LINEボタンアニメーション
    if (document.querySelector('.line-btn')) {
        gsap.from('.line-btn', {
            scrollTrigger: {
                trigger: '.line-btn',
                start: 'top 90%',
                end: 'bottom 10%',
                toggleActions: 'play none none reverse'
            },
            duration: 0.8,
            y: 30,
            opacity: 0,
            delay: 0.3,
            ease: 'power2.out'
        });
    }
    
    // フッターアニメーション
    gsap.from('.footer-content', {
        scrollTrigger: {
            trigger: '.footer-content',
            start: 'top 90%',
            end: 'bottom 10%',
            toggleActions: 'play none none reverse'
        },
        duration: 1,
        y: 50,
        opacity: 0,
        ease: 'power2.out'
    });
}

// ===== SCROLL ANIMATIONS =====
function initScrollAnimations() {
    // ヒーロー背景のスケールアニメーション
    const heroImage = document.querySelector('.hero-bg-image');
    if (heroImage) {
        gsap.to(heroImage, {
            scale: 1.2,
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: 'bottom top',
                scrub: true
            }
        });
    }
    
    // ヒーローコンテンツのパララックス
    gsap.to('.hero-content', {
        y: -100,
        opacity: 0.3,
        scrollTrigger: {
            trigger: '.hero',
            start: 'top top',
            end: 'bottom top',
            scrub: true
        }
    });
    
    // 商品カードのスタガーアニメーション
    ScrollTrigger.batch('.product-card', {
        onEnter: (elements) => {
            gsap.from(elements, {
                y: 100,
                opacity: 0,
                duration: 1,
                ease: 'power2.out',
                stagger: 0.15
            });
        },
        onLeave: (elements) => {
            gsap.to(elements, {
                y: -100,
                opacity: 0,
                duration: 0.5,
                ease: 'power2.in',
                stagger: 0.1
            });
        },
        onEnterBack: (elements) => {
            gsap.to(elements, {
                y: 0,
                opacity: 1,
                duration: 0.8,
                ease: 'power2.out',
                stagger: 0.15
            });
        }
    });
    
    // 店舗カードのスタガーアニメーション
    ScrollTrigger.batch('.shop-card', {
        onEnter: (elements) => {
            gsap.from(elements, {
                y: 100,
                opacity: 0,
                duration: 1,
                ease: 'power2.out',
                stagger: 0.15
            });
        },
        onLeave: (elements) => {
            gsap.to(elements, {
                y: -100,
                opacity: 0,
                duration: 0.5,
                ease: 'power2.in',
                stagger: 0.1
            });
        },
        onEnterBack: (elements) => {
            gsap.to(elements, {
                y: 0,
                opacity: 1,
                duration: 0.8,
                ease: 'power2.out',
                stagger: 0.15
            });
        }
    });
    
    // セクション間のパララックス効果
    gsap.utils.toArray('section').forEach(section => {
        const bg = section.querySelector('.section-bg');
        if (bg) {
            gsap.to(bg, {
                yPercent: -50,
                ease: 'none',
                scrollTrigger: {
                    trigger: section,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: true
                }
            });
        }
    });
}

// ===== UTILITY FUNCTIONS =====
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== SCROLL TO TOP =====
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// ===== MOBILE MENU STYLES =====
const mobileMenuStyles = `
    @media (max-width: 767px) {
        .nav {
            position: fixed;
            top: 100%;
            left: 0;
            width: 100%;
            background: var(--white);
            box-shadow: var(--shadow);
            flex-direction: column;
            padding: 20px;
            transition: top 0.3s ease;
            z-index: 999;
        }
        
        .nav.active {
            top: 80px;
        }
        
        .hamburger.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }
        
        .header.scrolled {
            background: rgba(255, 255, 255, 0.98);
        }
    }
`;

// スタイルを動的に追加
const style = document.createElement('style');
style.textContent = mobileMenuStyles;
document.head.appendChild(style);

// ===== TOPIC MODAL FUNCTIONS =====
async function openTopic(topicId, title) {
    const modal = document.getElementById('topic-modal');
    const modalTitle = document.getElementById('topic-modal-title');
    const modalBody = document.getElementById('topic-modal-body');
    
    // タイトルを設定
    modalTitle.textContent = title;
    
    // ローディング表示
    modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><p>読み込み中...</p></div>';
    
    // モーダルを表示
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    try {
        // トピックファイルを読み込み
        const response = await fetch(`assets/topics/${topicId}.html`);
        if (!response.ok) {
            throw new Error('コンテンツが見つかりません');
        }
        const content = await response.text();
        
        // コンテンツを表示
        modalBody.innerHTML = content;
        
        // scriptタグを実行
        const scripts = modalBody.querySelectorAll('script');
        scripts.forEach(script => {
            if (script.textContent.trim()) {
                try {
                    const newScript = document.createElement('script');
                    newScript.textContent = script.textContent;
                    modalBody.appendChild(newScript);
                    // 少し待ってから関数の存在確認
                    setTimeout(() => {
                        if (typeof startQuiz === 'function') {
                            // Debug: startQuiz function available
                        }
                    }, 100);
                } catch (error) {
                    // Error: Script execution failed
                }
            }
        });
        
        // 必要に応じてJavaScriptを実行
        executeTopicScripts(topicId);
        
    } catch (error) {
        // Error: Failed to load topic
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <h3>申し訳ございません</h3>
                <p>コンテンツの読み込みに失敗しました。</p>
                <p>しばらく後でもう一度お試しください。</p>
            </div>
        `;
    }
}

function closeTopic() {
    const modal = document.getElementById('topic-modal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function executeTopicScripts(topicId) {
    // 各トピック固有のJavaScriptがある場合はここで実行
    switch(topicId) {
        case 'sweets-quiz':
            // 診断ロジックを初期化
            if (typeof initSweetsQuiz === 'function') {
                initSweetsQuiz();
            }
            break;
        case 'memory-game':
            // ゲームロジックを初期化
            if (typeof initMemoryGame === 'function') {
                initMemoryGame();
            }
            break;
    }
}

// モーダル外クリックで閉じる
window.addEventListener('click', function(event) {
    const modal = document.getElementById('topic-modal');
    if (event.target === modal) {
        closeTopic();
    }
});

// ===== EASTER EGG - PHYSICS ACORNS =====
let easterEggActive = false;
let lastTapTime = 0;
let tapCount = 0;
let acorns = [];
let animationFrame;

// ダブルクリック/ダブルタップ検出（ロゴ画像のみ）
function initEasterEgg() {
    const logoImage = document.querySelector('.hero-logo-image');
    if (!logoImage) {
        // Error: Hero logo image not found
        return;
    }
    
    // Debug: Easter egg initialized
    
    // ロゴ画像を再度クリック可能にする（CSSで無効化されているため）
    logoImage.style.pointerEvents = 'auto';
    logoImage.style.cursor = 'pointer';
    
    // デスクトップ用ダブルクリック
    logoImage.addEventListener('dblclick', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // ロゴの中心座標を取得
        const rect = logoImage.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        
        // Debug: Logo double click detected
        if (consoleViewerMode) {
            // コンソール表示時は2個のどんぐりを生成
            createAcornAtPosition(centerX - 20, centerY, true);
            createAcornAtPosition(centerX + 20, centerY, true);
        } else {
            createAcornAtPosition(centerX, centerY, false);
        }
    });
    
    // モバイル用ダブルタップ（重複防止強化）
    let touchStartTime = 0;
    let touchEndTime = 0;
    let isDoubleTapProcessed = false;
    
    logoImage.addEventListener('touchstart', function(e) {
        touchStartTime = new Date().getTime();
        isDoubleTapProcessed = false;
    });
    
    logoImage.addEventListener('touchend', function(e) {
        e.preventDefault(); // デフォルト動作を防ぐ
        e.stopPropagation();
        
        touchEndTime = new Date().getTime();
        const currentTime = touchEndTime;
        const timeDiff = currentTime - lastTapTime;
        
        // 長押しやドラッグを除外
        if (touchEndTime - touchStartTime > 200) {
            tapCount = 0;
            return;
        }
        
        if (timeDiff < 400 && timeDiff > 50 && !isDoubleTapProcessed) {
            tapCount++;
            if (tapCount === 2) {
                isDoubleTapProcessed = true;
                
                // ロゴの中心座標を取得
                const rect = logoImage.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                
                // Debug: Logo double tap detected
                if (consoleViewerMode) {
                    // コンソール表示時は2個のどんぐりを生成
                    createAcornAtPosition(centerX - 20, centerY, true);
                    createAcornAtPosition(centerX + 20, centerY, true);
                } else {
                    createAcornAtPosition(centerX, centerY, false);
                }
                tapCount = 0;
            }
        } else {
            tapCount = 1;
        }
        
        lastTapTime = currentTime;
        
        // タップカウントリセット
        setTimeout(() => {
            tapCount = 0;
        }, 400);
    });
    
    // 物理演算ループを開始
    startPhysicsLoop();
}

// 物理演算ループ
function startPhysicsLoop() {
    const physicsAcorns = acorns.filter(a => a.physics);
    // Debug: Starting physics loop
    
    function updatePhysics() {
        // 全てのどんぐりの位置を更新
        acorns.forEach((acorn, index) => {
            if (!acorn.physics) return; // 物理演算対象外のものはスキップ
            
            // 重力と空気抵抗
            acorn.physics.velocityY += 0.4; // 重力
            acorn.physics.velocityX *= 0.98; // 空気抵抗
            acorn.physics.velocityY *= 0.99; // 空気抵抗
            
            // 位置更新
            acorn.physics.x += acorn.physics.velocityX;
            acorn.physics.y += acorn.physics.velocityY;
            
            // 画面境界での反発
            if (acorn.physics.x <= acorn.physics.size / 2) {
                acorn.physics.x = acorn.physics.size / 2;
                acorn.physics.velocityX *= -0.7;
            }
            if (acorn.physics.x >= window.innerWidth - acorn.physics.size / 2) {
                acorn.physics.x = window.innerWidth - acorn.physics.size / 2;
                acorn.physics.velocityX *= -0.7;
            }
            if (acorn.physics.y >= window.innerHeight - acorn.physics.size / 2) {
                acorn.physics.y = window.innerHeight - acorn.physics.size / 2;
                acorn.physics.velocityY *= -0.6;
                acorn.physics.velocityX *= 0.9; // 地面摩擦
            }
            
            // 回転更新
            acorn.physics.rotation += acorn.physics.velocityX * 0.2;
            
            // DOM要素の位置更新
            const transform = `translate(${acorn.physics.x - acorn.physics.size/2}px, ${acorn.physics.y - acorn.physics.size/2}px) rotate(${acorn.physics.rotation}deg) scale(1)`;
            acorn.style.transform = transform;
            
            // 最初の数フレームの位置を表示
            if (index === 0 && acorn.physics.y < 100) {
                // Debug: Physics update
            }
        });
        
        // どんぐり同士の衝突判定
        for (let i = 0; i < acorns.length; i++) {
            for (let j = i + 1; j < acorns.length; j++) {
                if (acorns[i].physics && acorns[j].physics) {
                    checkAcornCollision(acorns[i], acorns[j]);
                }
            }
        }
        
        // 物理演算対象のどんぐりがある限り継続
        const physicsAcorns = acorns.filter(a => a.physics);
        if (physicsAcorns.length > 0) {
            animationFrame = requestAnimationFrame(updatePhysics);
        } else {
            // Debug: No physics acorns, stopping loop
            animationFrame = null; // アニメーションフレームをリセット
        }
    }
    
    // 常に物理演算ループを開始（どんぐりがなくても準備）
    updatePhysics();
}

// どんぐり同士の衝突判定
function checkAcornCollision(acorn1, acorn2) {
    const dx = acorn2.physics.x - acorn1.physics.x;
    const dy = acorn2.physics.y - acorn1.physics.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    const minDistance = (acorn1.physics.size + acorn2.physics.size) / 2;
    
    if (distance < minDistance) {
        // 衝突発生
        const angle = Math.atan2(dy, dx);
        const sin = Math.sin(angle);
        const cos = Math.cos(angle);
        
        // 重なりを解消
        const overlap = minDistance - distance;
        const moveX = overlap * cos * 0.5;
        const moveY = overlap * sin * 0.5;
        
        acorn1.physics.x -= moveX;
        acorn1.physics.y -= moveY;
        acorn2.physics.x += moveX;
        acorn2.physics.y += moveY;
        
        // 速度の交換（簡易版）
        const tempVelX = acorn1.physics.velocityX;
        const tempVelY = acorn1.physics.velocityY;
        
        acorn1.physics.velocityX = acorn2.physics.velocityX * 0.8;
        acorn1.physics.velocityY = acorn2.physics.velocityY * 0.8;
        acorn2.physics.velocityX = tempVelX * 0.8;
        acorn2.physics.velocityY = tempVelY * 0.8;
    }
}

// ダブルクリック/ダブルタップした位置にどんぐりを配置
function createAcornAtPosition(x, y, isConsoleMode = false) {
    // Debug: Creating acorn
    
    if (!easterEggActive) {
        easterEggActive = true;
        // Debug: Physics Acorn Easter Egg Activated
    }
    
    // テキスト選択をクリア
    if (window.getSelection) {
        window.getSelection().removeAllRanges();
    } else if (document.selection) {
        document.selection.empty();
    }
    
    // どんぐり要素を作成
    const acorn = document.createElement('div');
    acorn.className = 'easter-acorn';
    
    // 画像要素を作成
    const img = document.createElement('img');
    img.src = 'assets/images/loading.png'; // どんぐり画像
    img.alt = 'どんぐり';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'cover';
    img.onerror = function() {
        // Debug: Acorn image load failed, using fallback
        this.src = 'assets/images/logo.png'; // フォールバック画像
    };
    
    acorn.appendChild(img);
    
    // サイズとスタイル（大きいどんぐりの確率を下げる）
    const randomSize = Math.random();
    let size;
    if (randomSize < 0.75) {
        // 75%の確率で通常サイズ（40-60px）
        size = 40 + Math.random() * 20;
    } else if (randomSize < 0.92) {
        // 17%の確率で大きめサイズ（60-100px）
        size = 60 + Math.random() * 40;
    } else {
        // 8%の確率で特大サイズ（100-140px）
        size = 100 + Math.random() * 40;
    }
    const rotation = Math.random() * 360; // ランダムな回転
    
    acorn.style.cssText = `
        position: fixed;
        left: 0px;
        top: 0px;
        width: ${size}px;
        height: ${size}px;
        border-radius: 50%;
        overflow: hidden;
        z-index: 9999;
        pointer-events: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border: 2px solid rgba(255,255,255,0.8);
        background: rgba(255,255,255,0.9);
        transform: translate(${x - size/2}px, ${y - size/2}px) rotate(${rotation}deg) scale(0);
        transition: none;
    `;
    
    // Debug: Acorn initial transform set
    
    document.body.appendChild(acorn);
    // Debug: Acorn added to DOM
    
    // 物理演算プロパティを追加（コンソールモード時は強い勢い）
    const velocityMultiplier = isConsoleMode ? 2.5 : 1;
    acorn.physics = {
        x: x,
        y: y,
        velocityX: (Math.random() - 0.5) * 4 * velocityMultiplier, // コンソールモード時は強い横方向速度
        velocityY: (-8 - Math.random() * 4) * velocityMultiplier, // コンソールモード時は強い上向き初速度
        size: size,
        rotation: rotation
    };
    
    // 出現アニメーション
    setTimeout(() => {
        const newTransform = `translate(${x - size/2}px, ${y - size/2}px) rotate(${rotation}deg) scale(1)`;
        acorn.style.transform = newTransform;
        // Debug: Acorn appearance animation
    }, 10);
    
    // 配列に追加
    acorns.push(acorn);
    // Debug: Acorn added to array
    
    // 物理演算が動いていなければ開始
    if (!animationFrame) {
        // Debug: Starting physics for new acorn
        startPhysicsLoop();
    }
    
    // 15秒後に自動削除
    setTimeout(() => {
        if (acorn.parentNode) {
            // 物理演算停止
            acorn.physics = null;
            
            // 消失アニメーション
            acorn.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
            acorn.style.transform = acorn.style.transform.replace('scale(1)', 'scale(0)');
            acorn.style.opacity = '0';
            
            setTimeout(() => {
                acorn.remove();
                const index = acorns.indexOf(acorn);
                if (index > -1) {
                    acorns.splice(index, 1);
                }
                // Debug: Acorn removed
            }, 300);
        }
    }, 15000);
}