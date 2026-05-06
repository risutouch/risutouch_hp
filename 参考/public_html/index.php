<?php
// CSPヘッダーを設定（Google Fonts対応）
header_remove('Content-Security-Policy');
header_remove('Content-Security-Policy-Report-Only');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'");

// 他のセキュリティヘッダー
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");  
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// 設定ファイルから連絡先情報を読み込み
$configFile = 'assets/data/site_config.json';
$siteConfig = [];
if (file_exists($configFile)) {
    $siteConfig = json_decode(file_get_contents($configFile), true) ?: [];
}

// デフォルト連絡先情報
$contactInfo = [
    'email' => $siteConfig['contact']['email'] ?? 'risutouch@gmail.com',
    'phone' => $siteConfig['contact']['phone'] ?? '090-8370-2871',
    'address' => $siteConfig['contact']['address'] ?? '山口県長門市油谷新別名1009',
    'instagram' => $siteConfig['contact']['instagram'] ?? 'risutouch'
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>焼き菓子.りすたっち - 山口県長門市の手作り焼き菓子専門店 | 委託販売・ギフト対応</title>
    <meta name="description" content="山口県長門市の手作り焼き菓子専門店「焼き菓子.りすたっち」公式サイト。旬の素材を使った季節の焼き菓子、クッキー、フロランタンなどを委託販売。ギフト・贈り物にも最適です。">
    <meta name="keywords" content="焼き菓子,りすたっち,山口県,長門市,手作り,委託販売,クッキー,フロランタン,ギフト,お菓子,スイーツ">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="焼き菓子.りすたっち - 山口県長門市の手作り焼き菓子専門店">
    <meta property="og:description" content="旬の素材を使った季節の焼き菓子を心を込めて手作り。委託販売・ギフト対応も承ります。">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://risutouch.com">
    <meta property="og:image" content="https://risutouch.com/assets/images/og-image.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="焼き菓子.りすたっち - 山口県長門市の手作り焼き菓子専門店">
    <meta name="twitter:description" content="旬の素材を使った季節の焼き菓子を心を込めて手作り。委託販売・ギフト対応も承ります。">
    <link rel="canonical" href="https://risutouch.com">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicons/favicon.ico">
    <link rel="icon" type="image/png" href="assets/favicons/favicon.png">
    <link rel="apple-touch-icon" href="assets/favicons/apple-touch-icon.png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4a3c2a">
    
    <!-- Google Search Console 認証用メタタグ (管理画面で設定可能) -->
    <!-- google-site-verification メタタグは設定で追加されます -->
    
    <link rel="stylesheet" href="assets/css/style.css?v=2025012901">
</head>
<body>
    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-content">
            <img src="assets/images/loading.png" alt="Loading..." class="loading-image">
        </div>
    </div>

    <!-- Hero Section -->
    <section id="hero" class="hero">
        <div class="hero-navigation">
            <a href="#products" class="hero-nav-item">
                <div class="hero-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- クッキー/焼き菓子アイコン -->
                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.5" fill="none"/>
                        <circle cx="8" cy="9" r="1" fill="currentColor"/>
                        <circle cx="16" cy="9" r="1" fill="currentColor"/>
                        <circle cx="9" cy="15" r="1" fill="currentColor"/>
                        <circle cx="15" cy="15" r="1" fill="currentColor"/>
                        <circle cx="12" cy="7" r="0.8" fill="currentColor"/>
                        <circle cx="12" cy="17" r="0.8" fill="currentColor"/>
                        <circle cx="6" cy="12" r="0.8" fill="currentColor"/>
                        <circle cx="18" cy="12" r="0.8" fill="currentColor"/>
                        <path d="M10 11.5c.5.5 1.5.5 2 0 .5-.5 1.5-.5 2 0" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="hero-nav-text">焼き菓子</span>
            </a>
            <a href="#shops" class="hero-nav-item">
                <div class="hero-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="1.5"/>
                        <polyline points="9,22 9,12 15,12 15,22" stroke="currentColor" stroke-width="1.5"/>
                        <rect x="6" y="5" width="3" height="2" stroke="currentColor" stroke-width="1.5"/>
                        <rect x="15" y="5" width="3" height="2" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                </div>
                <span class="hero-nav-text">販売店</span>
            </a>
            <a href="#news" class="hero-nav-item">
                <div class="hero-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.5"/>
                        <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="1.5"/>
                        <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="1.5"/>
                        <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="1.5"/>
                        <polyline points="10,9 9,9 8,9" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                </div>
                <span class="hero-nav-text">トピック</span>
            </a>
            <a href="#contact" class="hero-nav-item">
                <div class="hero-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                </div>
                <span class="hero-nav-text">お問合せ</span>
            </a>
        </div>
        <div class="hero-content">
            <div class="hero-logo">
                <img src="assets/images/logo.png" alt="りすたっち ロゴ" class="hero-logo-image">
            </div>
        </div>
        <div class="hero-scroll">
            <div class="scroll-indicator">
                <span class="scroll-line"></span>
                <span class="scroll-text">Scroll</span>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-scroll-section">
        <div class="about-container">
            <div class="about-sticky-content">
                <div class="about-panel about-panel-1">
                    <div class="about-story">
                        <div class="about-image">
                            <img src="assets/images/about-1.jpg" alt="はじまりの風景" class="about-img">
                        </div>
                        <div class="about-text">
                            <h3 class="about-title">はじまり</h3>
                            <p class="about-description">
                                2021年4月、豆腐店として使われていた場所を改装し、<br>
                                「焼き菓子 りすたっち」をオープンしました。<br>
                                現在は主に、地域のお店や施設で委託販売という形でお菓子をお届けしています。
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="about-panel about-panel-2">
                    <div class="about-story">
                        <div class="about-image">
                            <img src="assets/images/about-2.jpg" alt="お菓子づくりの様子" class="about-img">
                        </div>
                        <div class="about-text">
                            <h3 class="about-title">お菓子づくりのこと</h3>
                            <p class="about-description">
                                旬の素材を見て「これを使って何か作ってみたい」と感じる直感を大切にしています。<br>
                                その素材の彩りや香りを活かしながら、季節感を楽しめるお菓子になるよう工夫しています。
                            </p>
                            <p class="about-description">
                                また、日常で気軽に楽しんでもらえるように、定番の焼き菓子は、食べやすさや見た目にも気を配りながら仕上げています。
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="about-panel about-panel-3">
                    <div class="about-story">
                        <div class="about-image">
                            <img src="assets/images/about-3.jpg" alt="つくっている人" class="about-img">
                        </div>
                        <div class="about-text">
                            <h3 class="about-title">つくっている人</h3>
                            <p class="about-description">
                                ・製菓専門学校で洋菓子づくりを学ぶ<br>
                                ・洋菓子店に勤務し、現場経験を積む<br>
                                ・2012年 ジャパンケーキショー東京 小型工芸部門<br>
                                　連合会会長賞（最高ランク）受賞<br>
                                ・地元企業で洋菓子の企画・販売に携わる<br>
                                ・2021年、「焼き菓子 りすたっち」を開業
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="products">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">焼き菓子</h2>
                <p class="section-subtitle">主な焼き菓子のご紹介</p>
            </div>
            
            <!-- 主なお菓子の紹介 -->
            <div class="products-showcase">
                <div class="products-grid" id="products-grid">
                    <!-- 商品はJavaScriptで動的に生成 -->
                </div>
            </div>
            
            <!-- メニュー表 -->
            <div class="menu-section">
                <div class="menu-header">
                    <h3 class="menu-section-title">メニュー表</h3>
                    <p class="menu-section-subtitle">ギフトのご予約や商品選びの参考にご覧ください</p>
                </div>
                <div class="menu-display" id="menu-display">
                    <!-- メニューPDFボタンとプレビュー -->
                </div>
            </div>
            
            <!-- メニューPDFモーダル -->
            <div id="menu-modal" class="menu-modal">
                <div class="menu-modal-content">
                    <div class="menu-modal-header">
                        <h3>メニュー表</h3>
                        <button class="menu-close" onclick="closeMenuModal()">&times;</button>
                    </div>
                    <div class="menu-modal-body">
                        <div id="menu-pdf-container" class="menu-pdf-container">
                            <!-- PDFがここに表示される -->
                        </div>
                    </div>
                    <div class="menu-modal-footer">
                        <div class="menu-note">
                            <p>★印は季節商品。すべて税込価格です。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Shops Section -->
    <section id="shops" class="shops">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">販売店</h2>
                <p class="section-subtitle">りすたっちの焼き菓子をお求めいただける店舗</p>
                <div class="shop-navigation" id="shop-navigation">
                    <!-- 店舗ナビゲーションはJavaScriptで動的に生成 -->
                </div>
            </div>
            <div class="shops-grid" id="shops-grid">
                <!-- 店舗情報はJavaScriptで動的に生成 -->
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section id="news" class="news">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">トピック</h2>
                <p class="section-subtitle">りすたっちに関する様々なコンテンツ</p>
            </div>
            <div class="entertainment-grid" id="entertainment-grid">
                <!-- エンターテイメントコンテンツ -->
                <div class="entertainment-item" onclick="openTopic('sweets-quiz', 'あなたにピッタリのお菓子診断')">
                    <div class="entertainment-banner">
                        <div class="banner-image">
                            <img id="sweets-quiz-thumbnail" src="assets/images/entertainment/sweets-quiz.jpg" alt="お菓子診断" class="banner-img" onerror="this.src='assets/images/logo.png'">
                        </div>
                        <div class="banner-content">
                            <h3 class="banner-title">あなたにピッタリのお菓子診断</h3>
                            <p class="banner-description">簡単な質問に答えて、あなたの好みに合う焼き菓子を見つけよう！</p>
                        </div>
                    </div>
                </div>
                
                <div class="entertainment-item" onclick="openTopic('seasonal-calendar', '季節の素材暦')">
                    <div class="entertainment-banner">
                        <div class="banner-image">
                            <img id="seasonal-calendar-thumbnail" src="assets/images/logo.png" alt="季節の素材暦" class="banner-img">
                        </div>
                        <div class="banner-content">
                            <h3 class="banner-title">季節の素材暦</h3>
                            <p class="banner-description">四季を通じた旬の素材と、それを使った焼き菓子の魅力をご紹介</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">お問い合わせ</h2>
                <p class="section-subtitle">ご注文やご相談はこちら</p>
            </div>
            <div class="contact-content">
                <div class="contact-primary-content">
                    <p class="contact-intro">商品のご注文など、お気軽にお問い合わせください。<br>一人で製造しているため、ご予約はお早めにいただけると助かります。</p>
                    <div class="contact-primary">
                        <a href="https://lin.ee/75Gqk1m" class="contact-btn contact-primary-btn" target="_blank">
                            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                <path d="M8 9h8"/>
                                <path d="M8 13h6"/>
                            </svg>
                            <span class="contact-text">LINE</span>
                        </a>
                        <a href="https://www.instagram.com/<?php echo htmlspecialchars($contactInfo['instagram']); ?>/" class="contact-btn contact-primary-btn" target="_blank">
                            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                            </svg>
                            <span class="contact-text">Instagram</span>
                        </a>
                    </div>
                </div>
                <div class="contact-info">
                    <h3>工房情報</h3>
                    <p>
                        住所：〒759-4503<br>
                        <?php echo htmlspecialchars($contactInfo['address']); ?><br><br>
                        現在、工房での販売は行っておりません。<br>
                        主に委託販売という形で販売を行っております。<br><br>
                        メール：<a href="mailto:<?php echo htmlspecialchars($contactInfo['email']); ?>" class="contact-link"><?php echo htmlspecialchars($contactInfo['email']); ?></a><br>
                        <?php if (!empty($contactInfo['phone'])): ?>
                        電話：<a href="tel:<?php echo htmlspecialchars($contactInfo['phone']); ?>" class="contact-link"><?php echo htmlspecialchars($contactInfo['phone']); ?></a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Topic Modal -->
    <div id="topic-modal" class="topic-modal">
        <div class="topic-modal-content">
            <div class="topic-modal-header">
                <h2 id="topic-modal-title">トピック</h2>
                <button class="topic-close" onclick="closeTopic()">&times;</button>
            </div>
            <div class="topic-modal-body" id="topic-modal-body">
                <!-- トピックコンテンツがここに読み込まれる -->
            </div>
        </div>
    </div>

    <!-- Fixed Instagram Icon -->
    <div class="fixed-instagram">
        <a href="https://www.instagram.com/<?php echo htmlspecialchars($contactInfo['instagram']); ?>/" target="_blank" class="instagram-float-btn">
            <div class="instagram-content">
                <svg class="instagram-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke="currentColor" stroke-width="2"/>
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" stroke="currentColor" stroke-width="2"/>
                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span class="instagram-text instagram-text-pc">最新情報はInstagramで</span>
                <span class="instagram-text instagram-text-mobile">最新情報</span>
            </div>
        </a>
    </div>

    <!-- Footer -->
    <footer id="footer" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="assets/images/logo3.png" alt="焼き菓子.りすたっち" class="footer-logo">
                </div>
                <div class="footer-links">
                    <a href="#about">りすたっちについて</a>
                    <a href="#products">焼き菓子</a>
                    <a href="#shops">販売店</a>
                    <a href="#news">トピック</a>
                    <a href="#contact">お問い合わせ</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 焼き菓子.りすたっち All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>