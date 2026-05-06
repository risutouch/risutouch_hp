<?php
// 直接アクセス防止
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}
?>
<!-- Shops Tab -->
<div class="tab-header">
    <h2>販売店管理</h2>
    <p class="tab-description">販売店の表示/非表示を管理できます</p>
    <button class="btn btn-primary" onclick="openShopModal()">店舗を追加</button>
</div>

<div class="shops-list">
    <?php foreach ($shops['shops'] as $shop): ?>
        <div class="item-card">
            <div class="item-card-image">
                <?php if ($shop['logo']): ?>
                    <img src="../<?php echo htmlspecialchars($shop['logo']); ?>" alt="<?php echo htmlspecialchars($shop['name']); ?> ロゴ">
                <?php else: ?>
                    🏪
                <?php endif; ?>
            </div>
            <div class="item-card-content">
                <div class="item-card-header">
                    <h3 class="item-card-title"><?php echo htmlspecialchars($shop['name']); ?></h3>
                    <label class="toggle-switch">
                        <input type="checkbox" id="shop-<?php echo $shop['id']; ?>-visible" <?php echo isset($shop['visible']) && $shop['visible'] !== false ? 'checked' : ''; ?> onchange="toggleShopVisible('<?php echo $shop['id']; ?>', this.checked)">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label"><?php echo isset($shop['visible']) && $shop['visible'] !== false ? '表示中' : '非表示'; ?></span>
                    </label>
                </div>
                <p class="item-card-description"><?php echo htmlspecialchars($shop['description']); ?></p>
                <div class="item-card-meta">
                    <span class="item-card-badge"><?php echo htmlspecialchars($shop['address']); ?></span>
                    <span class="item-card-badge"><?php echo htmlspecialchars($shop['hours']); ?></span>
                </div>
                <div class="item-card-actions">
                    <div class="order-controls">
                        <input type="number" 
                               class="order-input" 
                               value="<?php echo array_search($shop, $shops['shops']) + 1; ?>" 
                               min="1" 
                               max="<?php echo count($shops['shops']); ?>"
                               data-shop-id="<?php echo htmlspecialchars($shop['id']); ?>"
                               onchange="updateShopOrder(this)"
                               onblur="updateShopOrder(this)">
                    </div>
                    <button class="btn btn-secondary btn-small" onclick="editShop('<?php echo $shop['id']; ?>')">編集</button>
                    <button class="btn btn-danger btn-small" onclick="deleteShop('<?php echo $shop['id']; ?>')">削除</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Shop Modal -->
<div id="shop-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="shop-modal-title">店舗を追加</h3>
            <button class="modal-close" onclick="closeModal('shop-modal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="modal-form" onsubmit="debugFormSubmit(event)">
            <input type="hidden" id="shop-id" name="id" value="">
            <input type="hidden" id="existing-logo" name="existing_logo" value="">
            <input type="hidden" id="existing-shop-images" name="existing_shop_images" value="">
            
            <div class="form-group">
                <label for="shop-name">店舗名</label>
                <input type="text" id="shop-name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="shop-address">住所</label>
                <input type="text" id="shop-address" name="address" required>
            </div>
            
            
            <div class="form-group">
                <label for="shop-hours">営業時間</label>
                <input type="text" id="shop-hours" name="hours" placeholder="例: 10:00-17:00">
            </div>
            
            <div class="form-group">
                <label for="shop-closed">定休日</label>
                <input type="text" id="shop-closed" name="closed" placeholder="例: 月曜日">
            </div>
            
            <div class="form-group">
                <label for="shop-description">説明</label>
                <textarea id="shop-description" name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="shop-instagram">Instagram</label>
                <input type="text" id="shop-instagram" name="instagram" placeholder="例: @shop_name または https://instagram.com/shop_name">
            </div>
            
            <div class="form-group">
                <label for="shop-logo">ロゴ画像</label>
                <input type="file" id="shop-logo" name="logo" accept="image/*">
                <div class="image-preview" id="shop-logo-preview"></div>
            </div>
            
            
            <div class="form-group">
                <label for="shop-images">店舗画像（複数可）</label>
                <input type="file" id="shop-images" name="shopImages[]" accept="image/*" multiple>
                <div class="image-preview" id="shop-images-preview"></div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('shop-modal')">キャンセル</button>
                <button type="submit" name="save_shop" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
// Shop management functions
let currentEditingShop = null;

function openShopModal(shopId = null) {
    const modal = document.getElementById('shop-modal');
    const title = document.getElementById('shop-modal-title');
    const form = modal.querySelector('form');
    
    currentEditingShop = shopId;
    
    if (shopId) {
        title.textContent = '店舗を編集';
        console.log('Opening shop modal for editing:', shopId);
        // Load shop data
        <?php foreach ($shops['shops'] as $shop): ?>
        if (shopId === '<?php echo $shop['id']; ?>') {
            document.getElementById('shop-id').value = '<?php echo $shop['id']; ?>';
            document.getElementById('shop-name').value = '<?php echo htmlspecialchars($shop['name']); ?>';
            document.getElementById('shop-address').value = '<?php echo htmlspecialchars($shop['address']); ?>';
            document.getElementById('shop-hours').value = '<?php echo htmlspecialchars($shop['hours']); ?>';
            document.getElementById('shop-closed').value = '<?php echo htmlspecialchars($shop['closed']); ?>';
            document.getElementById('shop-description').value = '<?php echo htmlspecialchars($shop['description']); ?>';
            document.getElementById('shop-instagram').value = '<?php echo htmlspecialchars($shop['social']['instagram'] ?? ''); ?>';
            document.getElementById('existing-logo').value = '<?php echo htmlspecialchars($shop['logo'] ?? ''); ?>';
            <?php if (!empty($shop['logo'])): ?>
            document.getElementById('shop-logo-preview').innerHTML = '<img src="../<?php echo htmlspecialchars($shop['logo']); ?>" alt="ロゴ">';
            <?php endif; ?>
            
            const existingShopImages = '<?php echo htmlspecialchars(json_encode($shop['shopImages'] ?? [])); ?>';
            document.getElementById('existing-shop-images').value = existingShopImages;
            console.log('Set existing shop images for <?php echo $shop['id']; ?>:', existingShopImages);
            <?php if (!empty($shop['shopImages']) && is_array($shop['shopImages'])): ?>
            let shopImagesHtml = '';
            <?php foreach ($shop['shopImages'] as $image): ?>
            shopImagesHtml += '<img src="../<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($shop['name']); ?>">';
            <?php endforeach; ?>
            document.getElementById('shop-images-preview').innerHTML = shopImagesHtml;
            <?php endif; ?>
        }
        <?php endforeach; ?>
    } else {
        title.textContent = '店舗を追加';
        form.reset();
        document.getElementById('shop-id').value = '';
        document.getElementById('existing-logo').value = '';
        document.getElementById('shop-logo-preview').innerHTML = '';
        document.getElementById('existing-shop-images').value = '';
        document.getElementById('shop-images-preview').innerHTML = '';
    }
    
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

function editShop(shopId) {
    openShopModal(shopId);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function deleteShop(shopId) {
    if (confirm(`店舗を削除してもよろしいですか？この操作は取り消せません。`)) {
        window.location.href = `?delete=${encodeURIComponent(shopId)}&type=shops`;
    }
}

// Logo preview
document.getElementById('shop-logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('shop-logo-preview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="ロゴ Preview">';
        };
        reader.readAsDataURL(file);
    }
});

// Shop images preview
document.getElementById('shop-images').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const preview = document.getElementById('shop-images-preview');
    preview.innerHTML = '';
    
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Shop Preview';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});

// デバッグ関数
function debugFormSubmit(event) {
    console.log('=== FORM SUBMIT DEBUG ===');
    const formData = new FormData(event.target);
    
    // 全てのフォームデータをコンソールに出力
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    // 隠しフィールドの値を特に確認
    console.log('existing-logo field:', document.getElementById('existing-logo').value);
    console.log('existing-shop-images field:', document.getElementById('existing-shop-images').value);
    
    // フォーム送信を続行
    return true;
}

// 店舗順序更新
function updateShopOrder(input) {
    const shopId = input.getAttribute('data-shop-id');
    const newOrder = parseInt(input.value);
    const maxOrder = parseInt(input.getAttribute('max'));
    
    // 範囲チェック
    if (newOrder < 1 || newOrder > maxOrder) {
        alert('順序番号は1から' + maxOrder + 'の間で入力してください。');
        // 元の値に戻す
        input.value = parseInt(input.getAttribute('data-original-value') || '1');
        return;
    }
    
    // 元の値と同じ場合は何もしない
    const originalValue = parseInt(input.getAttribute('data-original-value') || '1');
    if (newOrder === originalValue) {
        return;
    }
    
    // AJAX送信
    const formData = new FormData();
    formData.append('action', 'update_shop_order');
    formData.append('shop_id', shopId);
    formData.append('new_order', newOrder);
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功時はページをリロードして順序を反映
            window.location.href = '?tab=shops';
        } else {
            alert('順序の更新に失敗しました: ' + (data.error || '不明なエラー'));
            // 元の値に戻す
            input.value = originalValue;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('順序の更新中にエラーが発生しました。');
        // 元の値に戻す
        input.value = originalValue;
    });
}

// ページ読み込み時に元の値を記録
document.addEventListener('DOMContentLoaded', function() {
    const orderInputs = document.querySelectorAll('.order-input');
    orderInputs.forEach(input => {
        input.setAttribute('data-original-value', input.value);
        
        // フォーカス時に元の値を記録
        input.addEventListener('focus', function() {
            this.setAttribute('data-original-value', this.value);
        });
    });
});
</script>