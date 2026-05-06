<?php
// 直接アクセス防止
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}
?>
<!-- Products Tab -->
<div class="tab-header">
    <h2>焼き菓子管理</h2>
    <p class="tab-description">商品の表示/非表示を管理できます</p>
    <button class="btn btn-primary" onclick="openProductModal()">商品を追加</button>
</div>

<div class="products-list">
    <?php foreach ($products['products'] as $product): ?>
        <div class="item-card">
            <div class="item-card-image">
                <?php if (!empty($product['images'])): ?>
                    <img src="../<?php echo htmlspecialchars($product['images'][0]); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    📷
                <?php endif; ?>
            </div>
            <div class="item-card-content">
                <div class="item-card-header">
                    <h3 class="item-card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <label class="toggle-switch">
                        <input type="checkbox" id="product-<?php echo htmlspecialchars($product['name']); ?>-visible" <?php echo isset($product['visible']) && $product['visible'] !== false ? 'checked' : ''; ?> onchange="toggleProductVisible('<?php echo htmlspecialchars($product['name']); ?>', this.checked)">
                        <span class="toggle-slider"></span>
                        <span class="toggle-label"><?php echo isset($product['visible']) && $product['visible'] !== false ? '表示中' : '非表示'; ?></span>
                    </label>
                </div>
                <p class="item-card-description"><?php echo htmlspecialchars($product['description']); ?></p>
                <div class="item-card-meta">
                    <?php if ($product['seasonal']): ?>
                        <span class="item-card-badge badge-seasonal">季節限定</span>
                    <?php endif; ?>
                </div>
                <div class="item-card-actions">
                    <div class="order-controls">
                        <input type="number" 
                               class="order-input" 
                               value="<?php echo array_search($product, $products['products']) + 1; ?>" 
                               min="1" 
                               max="<?php echo count($products['products']); ?>"
                               data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                               onchange="updateProductOrder(this)"
                               onblur="updateProductOrder(this)">
                    </div>
                    <button class="btn btn-secondary btn-small" onclick="editProduct('<?php echo htmlspecialchars($product['name']); ?>')">編集</button>
                    <button class="btn btn-danger btn-small" onclick="deleteProduct('<?php echo htmlspecialchars($product['name']); ?>')">削除</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Product Modal -->
<div id="product-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="product-modal-title">商品を追加</h3>
            <button class="modal-close" onclick="closeModal('product-modal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="modal-form">
            <input type="hidden" id="edit-mode" name="edit_mode" value="">
            <input type="hidden" id="original-name" name="original_name" value="">
            <input type="hidden" id="existing-images" name="existing_images" value="">
            
            <div class="form-group">
                <label for="product-name">商品名</label>
                <input type="text" id="product-name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="product-description">説明</label>
                <textarea id="product-description" name="description" required></textarea>
            </div>
            
            
            
            <div class="form-group">
                <label for="product-images">商品画像（複数可）</label>
                <input type="file" id="product-images" name="images[]" accept="image/*" multiple>
                <div class="image-preview" id="product-images-preview"></div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="product-seasonal" name="seasonal">
                    季節限定商品
                </label>
            </div>
            
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('product-modal')">キャンセル</button>
                <button type="submit" name="save_product" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
// Product management functions
let currentEditingProduct = null;

function openProductModal(productId = null) {
    const modal = document.getElementById('product-modal');
    const title = document.getElementById('product-modal-title');
    const form = modal.querySelector('form');
    
    currentEditingProduct = productId;
    
    if (productId) {
        title.textContent = '商品を編集';
        document.getElementById('edit-mode').value = '1';
        document.getElementById('original-name').value = productId;
        
        // Load product data
        <?php foreach ($products['products'] as $product): ?>
        if (productId === '<?php echo htmlspecialchars($product['name']); ?>') {
            document.getElementById('product-name').value = '<?php echo htmlspecialchars($product['name']); ?>';
            document.getElementById('product-description').value = '<?php echo htmlspecialchars($product['description']); ?>';
            document.getElementById('product-seasonal').checked = <?php echo $product['seasonal'] ? 'true' : 'false'; ?>;
            document.getElementById('existing-images').value = '<?php echo htmlspecialchars(json_encode($product['images'] ?? [])); ?>';
            <?php if (!empty($product['images']) && is_array($product['images'])): ?>
            let imagesHtml = '';
            <?php foreach ($product['images'] as $image): ?>
            imagesHtml += '<img src="../<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">';
            <?php endforeach; ?>
            document.getElementById('product-images-preview').innerHTML = imagesHtml;
            <?php endif; ?>
        }
        <?php endforeach; ?>
    } else {
        title.textContent = '商品を追加';
        form.reset();
        document.getElementById('edit-mode').value = '';
        document.getElementById('original-name').value = '';
        document.getElementById('existing-images').value = '';
        document.getElementById('product-images-preview').innerHTML = '';
    }
    
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

function editProduct(productId) {
    openProductModal(productId);
}

// Images preview
document.getElementById('product-images').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const preview = document.getElementById('product-images-preview');
    preview.innerHTML = '';
    
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = 'Preview';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
});

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function deleteProduct(productName) {
    if (confirm(`商品「${productName}」を削除してもよろしいですか？この操作は取り消せません。`)) {
        window.location.href = `?delete=${encodeURIComponent(productName)}&type=products`;
    }
}

// 商品順序更新
function updateProductOrder(input) {
    const productName = input.getAttribute('data-product-name');
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
    formData.append('action', 'update_product_order');
    formData.append('product_name', productName);
    formData.append('new_order', newOrder);
    
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功時はページをリロードして順序を反映
            window.location.href = '?tab=products';
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