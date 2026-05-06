// ===== GLOBAL VARIABLES =====
let currentProducts = [];
let currentShops = [];
let currentNews = [];
let isLoggedIn = false;
let editingProductId = null;
let editingShopId = null;

// ===== AUTHENTICATION =====
const ADMIN_PASSWORD = 'risutouch2024'; // 本番では環境変数に設定

// ===== DOM ELEMENTS =====
const loginScreen = document.getElementById('login-screen');
const adminPanel = document.getElementById('admin-panel');
const loginForm = document.getElementById('login-form');
const logoutBtn = document.getElementById('logout-btn');

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Check if already logged in
    if (localStorage.getItem('admin-logged-in') === 'true') {
        showAdminPanel();
    }
    
    // Initialize event listeners
    initializeEventListeners();
});

// ===== EVENT LISTENERS =====
function initializeEventListeners() {
    // Login form
    loginForm.addEventListener('submit', handleLogin);
    
    // Logout button
    logoutBtn.addEventListener('click', handleLogout);
    
    // Navigation tabs
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            switchTab(e.target.dataset.tab);
        });
    });
    
    // Modal close buttons
    document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });
    
    // Modal background click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeAllModals();
            }
        });
    });
    
    // Add buttons
    document.getElementById('add-product-btn').addEventListener('click', () => {
        openProductModal();
    });
    
    document.getElementById('add-shop-btn').addEventListener('click', () => {
        openShopModal();
    });
    
    document.getElementById('fetch-instagram-btn').addEventListener('click', () => {
        openInstagramModal();
    });
    
    // Form submissions
    document.getElementById('product-form').addEventListener('submit', handleProductSubmit);
    document.getElementById('shop-form').addEventListener('submit', handleShopSubmit);
    document.getElementById('fetch-posts-btn').addEventListener('click', fetchInstagramPosts);
    
    // File input previews
    document.getElementById('product-image').addEventListener('change', handleProductImagePreview);
    document.getElementById('shop-images').addEventListener('change', handleShopImagesPreview);
}

// ===== AUTHENTICATION FUNCTIONS =====
function handleLogin(e) {
    e.preventDefault();
    const password = document.getElementById('password').value;
    
    if (password === ADMIN_PASSWORD) {
        localStorage.setItem('admin-logged-in', 'true');
        showAdminPanel();
    } else {
        showAlert('パスワードが間違っています', 'error');
    }
}

function handleLogout() {
    localStorage.removeItem('admin-logged-in');
    showLoginScreen();
}

function showLoginScreen() {
    loginScreen.classList.remove('hidden');
    adminPanel.classList.add('hidden');
    document.getElementById('password').value = '';
    isLoggedIn = false;
}

function showAdminPanel() {
    loginScreen.classList.add('hidden');
    adminPanel.classList.remove('hidden');
    isLoggedIn = true;
    
    // Load initial data
    loadProducts();
    loadShops();
    loadNews();
}

// ===== TAB SWITCHING =====
function switchTab(tabName) {
    // Update nav buttons
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${tabName}-tab`).classList.add('active');
}

// ===== MODAL FUNCTIONS =====
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('active');
    modal.style.display = 'flex';
}

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    });
    
    // Reset forms
    document.querySelectorAll('form').forEach(form => {
        if (form.id !== 'login-form') {
            form.reset();
        }
    });
    
    // Clear previews
    document.getElementById('product-image-preview').innerHTML = '';
    document.getElementById('shop-images-preview').innerHTML = '';
    
    // Reset editing states
    editingProductId = null;
    editingShopId = null;
}

// ===== PRODUCT MANAGEMENT =====
async function loadProducts() {
    try {
        const response = await fetch('../assets/data/products.json');
        currentProducts = await response.json();
        displayProducts();
    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('商品データの読み込みに失敗しました', 'error');
    }
}

function displayProducts() {
    const productsList = document.getElementById('products-list');
    productsList.innerHTML = '';
    
    currentProducts.products.forEach(product => {
        const productCard = createProductCard(product);
        productsList.appendChild(productCard);
    });
}

function createProductCard(product) {
    const card = document.createElement('div');
    card.className = `item-card ${product.featured ? 'product-featured' : ''}`;
    card.innerHTML = `
        <div class="item-card-image">
            ${product.image ? `<img src="../${product.image}" alt="${product.name}">` : '📷'}
        </div>
        <div class="item-card-content">
            <div class="product-card-category">${product.category || '焼き菓子'}</div>
            <h3 class="item-card-title">${product.name}</h3>
            <p class="item-card-description">${product.description}</p>
            <div class="item-card-meta">
                ${product.seasonal ? '<span class="item-card-badge badge-seasonal">⭐季節限定</span>' : ''}
            </div>
            <div class="item-card-actions">
                <button class="btn btn-secondary btn-small" onclick="editProduct('${product.id}')">編集</button>
                <button class="btn btn-danger btn-small" onclick="deleteProduct('${product.id}')">削除</button>
            </div>
        </div>
    `;
    return card;
}

function openProductModal(productId = null) {
    const modal = document.getElementById('product-modal');
    const title = document.getElementById('product-modal-title');
    const form = document.getElementById('product-form');
    
    if (productId) {
        editingProductId = productId;
        title.textContent = '商品を編集';
        fillProductForm(productId);
    } else {
        editingProductId = null;
        title.textContent = '商品を追加';
        form.reset();
    }
    
    openModal('product-modal');
}

function fillProductForm(productId) {
    const product = currentProducts.products.find(p => p.id === productId);
    if (!product) return;
    
    document.getElementById('product-name').value = product.name;
    document.getElementById('product-description').value = product.description;
    document.getElementById('product-price').value = product.price;
    document.getElementById('product-category').value = product.category;
    document.getElementById('product-seasonal').checked = product.seasonal;
    document.getElementById('product-available').checked = product.available;
    
    // Show image preview if exists
    if (product.image) {
        const preview = document.getElementById('product-image-preview');
        preview.innerHTML = `<img src="../${product.image}" alt="${product.name}">`;
    }
}

function editProduct(productId) {
    openProductModal(productId);
}

function deleteProduct(productId) {
    if (confirm('この商品を削除してもよろしいですか？')) {
        currentProducts.products = currentProducts.products.filter(p => p.id !== productId);
        saveProducts();
        displayProducts();
        showAlert('商品を削除しました', 'success');
    }
}

async function handleProductSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const productData = {
        id: editingProductId || generateId(),
        name: formData.get('name'),
        description: formData.get('description'),
        price: formData.get('price'),
        category: formData.get('category'),
        seasonal: formData.get('seasonal') === 'on',
        available: formData.get('available') === 'on',
        image: '', // Will be set if image is uploaded
        ingredients: [],
        allergens: []
    };
    
    // Handle image upload
    const imageFile = formData.get('image');
    if (imageFile && imageFile.size > 0) {
        productData.image = await handleImageUpload(imageFile, 'products');
    } else if (editingProductId) {
        // Keep existing image if no new image uploaded
        const existingProduct = currentProducts.products.find(p => p.id === editingProductId);
        if (existingProduct) {
            productData.image = existingProduct.image;
        }
    }
    
    if (editingProductId) {
        // Update existing product
        const index = currentProducts.products.findIndex(p => p.id === editingProductId);
        currentProducts.products[index] = productData;
    } else {
        // Add new product
        currentProducts.products.push(productData);
    }
    
    saveProducts();
    displayProducts();
    closeAllModals();
    showAlert(editingProductId ? '商品を更新しました' : '商品を追加しました', 'success');
}

function handleProductImagePreview(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('product-image-preview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    }
}

// ===== SHOP MANAGEMENT =====
async function loadShops() {
    try {
        const response = await fetch('../assets/data/shops.json');
        currentShops = await response.json();
        displayShops();
    } catch (error) {
        console.error('Error loading shops:', error);
        showAlert('店舗データの読み込みに失敗しました', 'error');
    }
}

function displayShops() {
    const shopsList = document.getElementById('shops-list');
    shopsList.innerHTML = '';
    
    currentShops.shops.forEach(shop => {
        const shopCard = createShopCard(shop);
        shopsList.appendChild(shopCard);
    });
}

function createShopCard(shop) {
    const card = document.createElement('div');
    card.className = 'item-card';
    card.innerHTML = `
        <div class="item-card-image">
            ${shop.images && shop.images.length > 0 ? 
                `<img src="../${shop.images[0]}" alt="${shop.name}">` : 
                shop.logo || '🏪'}
        </div>
        <div class="item-card-content">
            <h3 class="item-card-title">${shop.name}</h3>
            <p class="item-card-description">${shop.description}</p>
            <div class="shop-card-info">
                ${shop.hours ? `<div class="shop-card-hours">${shop.hours}</div>` : ''}
                ${shop.phone ? `<div class="shop-card-phone">${shop.phone}</div>` : ''}
            </div>
            <div class="item-card-meta">
                <span class="shop-card-location">${shop.address}</span>
            </div>
            <div class="item-card-actions">
                <button class="btn btn-secondary btn-small" onclick="editShop('${shop.id}')">編集</button>
                <button class="btn btn-danger btn-small" onclick="deleteShop('${shop.id}')">削除</button>
            </div>
        </div>
    `;
    return card;
}

function openShopModal(shopId = null) {
    const modal = document.getElementById('shop-modal');
    const title = document.getElementById('shop-modal-title');
    const form = document.getElementById('shop-form');
    
    if (shopId) {
        editingShopId = shopId;
        title.textContent = '店舗を編集';
        fillShopForm(shopId);
    } else {
        editingShopId = null;
        title.textContent = '店舗を追加';
        form.reset();
    }
    
    openModal('shop-modal');
}

function fillShopForm(shopId) {
    const shop = currentShops.shops.find(s => s.id === shopId);
    if (!shop) return;
    
    document.getElementById('shop-name').value = shop.name;
    document.getElementById('shop-address').value = shop.address;
    document.getElementById('shop-phone').value = shop.phone;
    document.getElementById('shop-hours').value = shop.hours;
    document.getElementById('shop-closed').value = shop.closed;
    document.getElementById('shop-description').value = shop.description;
    document.getElementById('shop-logo').value = shop.logo;
    document.getElementById('shop-website').value = shop.website;
    
    // Show image previews if exist
    if (shop.images && shop.images.length > 0) {
        const preview = document.getElementById('shop-images-preview');
        preview.innerHTML = shop.images.map(img => 
            `<img src="../${img}" alt="${shop.name}">`
        ).join('');
    }
}

function editShop(shopId) {
    openShopModal(shopId);
}

function deleteShop(shopId) {
    if (confirm('この店舗を削除してもよろしいですか？')) {
        currentShops.shops = currentShops.shops.filter(s => s.id !== shopId);
        saveShops();
        displayShops();
        showAlert('店舗を削除しました', 'success');
    }
}

async function handleShopSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const shopData = {
        id: editingShopId || generateId(),
        name: formData.get('name'),
        address: formData.get('address'),
        phone: formData.get('phone'),
        hours: formData.get('hours'),
        closed: formData.get('closed'),
        description: formData.get('description'),
        logo: formData.get('logo'),
        website: formData.get('website'),
        images: [],
        social: {}
    };
    
    // Handle image uploads
    const imageFiles = formData.getAll('images');
    if (imageFiles.length > 0 && imageFiles[0].size > 0) {
        for (const file of imageFiles) {
            if (file.size > 0) {
                const imagePath = await handleImageUpload(file, 'shops');
                shopData.images.push(imagePath);
            }
        }
    } else if (editingShopId) {
        // Keep existing images if no new images uploaded
        const existingShop = currentShops.shops.find(s => s.id === editingShopId);
        if (existingShop) {
            shopData.images = existingShop.images;
        }
    }
    
    if (editingShopId) {
        // Update existing shop
        const index = currentShops.shops.findIndex(s => s.id === editingShopId);
        currentShops.shops[index] = shopData;
    } else {
        // Add new shop
        currentShops.shops.push(shopData);
    }
    
    saveShops();
    displayShops();
    closeAllModals();
    showAlert(editingShopId ? '店舗を更新しました' : '店舗を追加しました', 'success');
}

function handleShopImagesPreview(e) {
    const files = Array.from(e.target.files);
    const preview = document.getElementById('shop-images-preview');
    preview.innerHTML = '';
    
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Preview';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

// ===== NEWS MANAGEMENT =====
function loadNews() {
    // For now, we'll use localStorage to store news
    const savedNews = localStorage.getItem('risutouch-news');
    if (savedNews) {
        currentNews = JSON.parse(savedNews);
    } else {
        currentNews = [];
    }
    displayNews();
}

function displayNews() {
    const newsList = document.getElementById('news-list');
    newsList.innerHTML = '';
    
    if (currentNews.length === 0) {
        newsList.innerHTML = '<p>お知らせはまだありません。Instagram投稿を取得してください。</p>';
        return;
    }
    
    currentNews.forEach(news => {
        const newsCard = createNewsCard(news);
        newsList.appendChild(newsCard);
    });
}

function createNewsCard(news) {
    const card = document.createElement('div');
    card.className = 'item-card';
    card.innerHTML = `
        <div class="item-card-image">
            ${news.image ? `<img src="${news.image}" alt="${news.title}">` : '📢'}
        </div>
        <div class="item-card-content">
            <h3 class="item-card-title">${news.title}</h3>
            <p class="item-card-description">${news.description}</p>
            <div class="item-card-meta">
                <span class="item-card-badge">${news.date}</span>
                <span class="item-card-badge ${news.published ? 'badge-available' : 'badge-unavailable'}">
                    ${news.published ? '公開中' : '非公開'}
                </span>
            </div>
            <div class="item-card-actions">
                <button class="btn btn-secondary btn-small" onclick="toggleNewsPublish('${news.id}')">
                    ${news.published ? '非公開' : '公開'}
                </button>
                <button class="btn btn-danger btn-small" onclick="deleteNews('${news.id}')">削除</button>
            </div>
        </div>
    `;
    return card;
}

function toggleNewsPublish(newsId) {
    const news = currentNews.find(n => n.id === newsId);
    if (news) {
        news.published = !news.published;
        saveNews();
        displayNews();
        showAlert(`お知らせを${news.published ? '公開' : '非公開'}にしました`, 'success');
    }
}

function deleteNews(newsId) {
    if (confirm('このお知らせを削除してもよろしいですか？')) {
        currentNews = currentNews.filter(n => n.id !== newsId);
        saveNews();
        displayNews();
        showAlert('お知らせを削除しました', 'success');
    }
}

// ===== INSTAGRAM INTEGRATION =====
function openInstagramModal() {
    openModal('instagram-modal');
}

async function fetchInstagramPosts() {
    const url = document.getElementById('instagram-url').value;
    const btn = document.getElementById('fetch-posts-btn');
    const postsContainer = document.getElementById('instagram-posts');
    
    if (!url) {
        showAlert('Instagram URLを入力してください', 'error');
        return;
    }
    
    btn.innerHTML = '<span class="loading"></span> 取得中...';
    btn.disabled = true;
    
    try {
        // Instagramユーザー名を抽出
        let username = '';
        const urlMatch = url.match(/instagram\.com\/([^\/\?]+)/);
        if (urlMatch) {
            username = urlMatch[1];
        } else {
            // URLでない場合はユーザー名として扱う
            username = url.replace('@', '');
        }
        
        if (!username) {
            throw new Error('有効なInstagram URLまたはユーザー名を入力してください');
        }
        
        // PHPスクレイピングサービスにリクエスト
        const response = await fetch('instagram-scraper.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username: username })
        });
        
        if (!response.ok) {
            throw new Error('サーバーエラー: ' + response.status);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || '投稿の取得に失敗しました');
        }
        
        // 投稿を表示
        displayInstagramPosts(data.posts, data.note);
        
        if (data.posts.length > 0) {
            showAlert(`${data.posts.length}件の投稿を取得しました`, 'success');
        } else {
            showAlert('投稿が見つかりませんでした。手動で追加してください。', 'warning');
            showInstagramManualInput();
        }
        
    } catch (error) {
        console.error('Error fetching Instagram posts:', error);
        showAlert(error.message, 'error');
        // エラー時は手動入力を表示
        showInstagramManualInput();
    } finally {
        btn.innerHTML = '投稿を取得';
        btn.disabled = false;
    }
}

function displayInstagramPosts(posts, note = '') {
    const postsContainer = document.getElementById('instagram-posts');
    
    let html = '';
    
    if (note) {
        html += `<div class="instagram-note">${note}</div>`;
    }
    
    if (posts.length === 0) {
        html += '<div class="no-posts">投稿が見つかりませんでした。</div>';
    } else {
        html += '<div class="instagram-posts-grid">';
        posts.forEach(post => {
            html += createInstagramPostHTML(post);
        });
        html += '</div>';
    }
    
    // 手動追加ボタン
    html += '<div class="manual-add-section"><button onclick="showInstagramManualInput()" class="btn btn-secondary">手動で投稿を追加</button></div>';
    
    postsContainer.innerHTML = html;
}

function createInstagramPostHTML(post) {
    return `
        <div class="instagram-post" onclick="selectInstagramPost(this, ${JSON.stringify(post).replace(/"/g, '&quot;')})">
            <img src="${post.image}" alt="Instagram post" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPumBuui/sOOCqOODqeODvDwvdGV4dD48L3N2Zz4='">
            <div class="instagram-post-caption">${post.caption.substring(0, 100)}${post.caption.length > 100 ? '...' : ''}</div>
            <div class="instagram-post-date">${post.date}</div>
            ${post.likes ? `<div class="instagram-post-likes">♥ ${post.likes}</div>` : ''}
        </div>
    `;
}

function selectInstagramPost(element, post) {
    // 選択状態をトグル
    element.classList.toggle('selected');
    
    if (element.classList.contains('selected')) {
        addNewsFromInstagram(post);
        showAlert('投稿をお知らせに追加しました', 'success');
        // 1秒後に選択状態をリセット
        setTimeout(() => {
            element.classList.remove('selected');
        }, 1000);
    }
}

function showInstagramManualInput() {
    const postsContainer = document.getElementById('instagram-posts');
    const currentContent = postsContainer.innerHTML;
    
    // 既存のコンテンツの下に追加
    const manualInputHTML = `
        <div class="instagram-manual-input">
            <h4>Instagram投稿を手動で追加</h4>
            <p class="help-text">
                自動取得がうまくいかない場合は、手動で投稿情報を入力できます。<br>
                <strong>手順：</strong> Instagramで投稿を開き、画像を右クリックして「画像アドレスをコピー」し、キャプションもコピーして下記に貼り付けてください。
            </p>
            <div class="manual-post-form">
                <div class="form-group">
                    <label>投稿画像 URL</label>
                    <input type="url" id="manual-image-url" placeholder="Instagramの画像 URLを貼り付け">
                    <small>ヒント: 画像を右クリック→「画像アドレスをコピー」</small>
                </div>
                <div class="form-group">
                    <label>キャプション</label>
                    <textarea id="manual-caption" placeholder="Instagramのキャプションを貼り付け" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>投稿 URL（任意）</label>
                    <input type="url" id="manual-post-url" placeholder="Instagram投稿の URL">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="addManualInstagramPost()" class="btn btn-primary">投稿を追加</button>
                    <button type="button" onclick="hideManualInput()" class="btn btn-secondary">キャンセル</button>
                </div>
            </div>
        </div>
    `;
    
    // 既に手動入力フォームがある場合は追加しない
    if (!currentContent.includes('instagram-manual-input')) {
        postsContainer.innerHTML = currentContent + manualInputHTML;
    }
}

function hideManualInput() {
    const manualInput = document.querySelector('.instagram-manual-input');
    if (manualInput) {
        manualInput.remove();
    }
}

function addManualInstagramPost() {
    const imageUrl = document.getElementById('manual-image-url').value;
    const caption = document.getElementById('manual-caption').value;
    const postUrl = document.getElementById('manual-post-url').value;
    
    if (!imageUrl || !caption) {
        showAlert('画像 URLとキャプションは必須です', 'error');
        return;
    }
    
    const post = {
        id: 'manual_' + Date.now(),
        image: imageUrl,
        caption: caption,
        date: new Date().toISOString().split('T')[0],
        url: postUrl || '#'
    };
    
    addNewsFromInstagram(post);
    showAlert('投稿をお知らせに追加しました', 'success');
    
    // Clear form
    document.getElementById('manual-image-url').value = '';
    document.getElementById('manual-caption').value = '';
    document.getElementById('manual-post-url').value = '';
    
    closeModal('instagram-modal');
    loadNews();
}

function createInstagramPostElement(post) {
    const element = document.createElement('div');
    element.className = 'instagram-post';
    element.innerHTML = `
        <img src="${post.image}" alt="Instagram post">
        <div class="instagram-post-caption">${post.caption}</div>
        <div class="instagram-post-date">${post.date}</div>
    `;
    
    element.addEventListener('click', () => {
        addNewsFromInstagram(post);
        showAlert('投稿をお知らせに追加しました', 'success');
        closeModal('instagram-modal');
        loadNews();
    });
    
    return element;
}

function addNewsFromInstagram(post) {
    const news = {
        id: generateId(),
        title: post.caption.substring(0, 50) + '...',
        description: post.caption,
        image: post.image,
        date: post.date,
        published: false,
        source: 'instagram',
        sourceUrl: post.url
    };
    
    currentNews.unshift(news);
    saveNews();
    displayNews();
    showAlert('お知らせを追加しました', 'success');
}

// ===== UTILITY FUNCTIONS =====
function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

async function handleImageUpload(file, folder) {
    // In a real implementation, you'd upload to a server
    // For now, we'll create a mock path
    const fileName = `${Date.now()}_${file.name}`;
    const imagePath = `assets/images/${folder}/${fileName}`;
    
    // In reality, you'd upload the file to the server here
    // For demo purposes, we'll just return the path
    return imagePath;
}

function saveProducts() {
    // In a real implementation, you'd save to a server
    // For demo purposes, we'll use localStorage
    localStorage.setItem('risutouch-products', JSON.stringify(currentProducts));
}

function saveShops() {
    // In a real implementation, you'd save to a server
    localStorage.setItem('risutouch-shops', JSON.stringify(currentShops));
}

function saveNews() {
    localStorage.setItem('risutouch-news', JSON.stringify(currentNews));
}

function showAlert(message, type = 'success') {
    const alertElement = document.createElement('div');
    alertElement.className = `alert alert-${type}`;
    alertElement.textContent = message;
    
    document.body.appendChild(alertElement);
    
    setTimeout(() => {
        alertElement.remove();
    }, 5000);
}

// ===== GLOBAL FUNCTIONS (for onclick handlers) =====
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
window.editShop = editShop;
window.deleteShop = deleteShop;
window.toggleNewsPublish = toggleNewsPublish;
window.deleteNews = deleteNews;