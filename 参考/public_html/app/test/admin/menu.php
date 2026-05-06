<?php
// 直接アクセス防止
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}
?>
<!-- Menu Tab -->
<div class="tab-header">
    <h2>メニュー表管理</h2>
</div>

<!-- Current Menu Display -->
<div class="current-menu-section">
    <div class="current-menu-card">
        <div class="current-menu-header">
            <h3>現在のメニュー表</h3>
            <span class="last-updated" id="last-updated"></span>
        </div>
        <div class="current-menu-display" id="current-menu-display">
            <!-- Current menu will be loaded here -->
        </div>
        <div class="current-menu-actions">
            <button class="btn btn-primary" onclick="openUploadModal()">新しいメニューをアップロード</button>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="menu-upload-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>メニュー表をアップロード</h3>
            <button class="modal-close" onclick="closeUploadModal()">&times;</button>
        </div>
        <form id="menu-upload-form" enctype="multipart/form-data" class="modal-form">
            <div class="form-group">
                <label for="menu-file">メニュー画像</label>
                <input type="file" id="menu-file" name="menu_file" accept="image/*" required>
                <small>JPG、PNG、WebP画像ファイルをアップロード可能です</small>
            </div>
            <div class="form-group">
                <div class="image-preview" id="menu-preview"></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">キャンセル</button>
                <button type="submit" class="btn btn-primary">アップロード</button>
            </div>
        </form>
    </div>
</div>

<style>
/* メニュー管理スタイル */
.current-menu-section {
    background: var(--white);
    border-radius: 12px;
    box-shadow: var(--shadow);
    padding: 24px;
}

.current-menu-card {
    text-align: center;
}

.current-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e0e0e0;
}

.current-menu-header h3 {
    margin: 0;
    color: var(--primary-color);
}

.last-updated {
    font-size: 0.9rem;
    color: var(--gray);
}

.current-menu-display {
    margin-bottom: 24px;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed #e0e0e0;
    border-radius: 12px;
    background: #fafafa;
}

.current-menu-display img {
    max-width: 100%;
    max-height: 400px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.no-menu-message {
    color: var(--gray);
    text-align: center;
    padding: 40px;
}

.no-menu-message .icon {
    font-size: 3rem;
    margin-bottom: 16px;
    display: block;
}

.current-menu-actions {
    margin-top: 24px;
}

/* プレビューエリア */
.image-preview {
    margin-top: 16px;
    padding: 16px;
    border: 1px dashed #ccc;
    border-radius: 8px;
    text-align: center;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
}

.image-preview.empty {
    color: var(--gray);
    background: #fafafa;
}

.loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* レスポンシブ */
@media (max-width: 768px) {
    .current-menu-header {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .current-menu-display {
        min-height: 200px;
    }
}
</style>

<script>
// 初期化
document.addEventListener('DOMContentLoaded', function() {
    loadCurrentMenu();
    setupFormHandlers();
});

// 現在のメニューを読み込み
async function loadCurrentMenu() {
    try {
        const response = await fetch('../assets/data/menu.json');
        const data = await response.json();
        
        const display = document.getElementById('current-menu-display');
        const lastUpdated = document.getElementById('last-updated');
        
        if (data.menu && data.menu.image) {
            display.innerHTML = `<img src="../${data.menu.image}" alt="現在のメニュー表">`;
            lastUpdated.textContent = `最終更新: ${data.menu.lastUpdated || '不明'}`;
        } else {
            display.innerHTML = `
                <div class="no-menu-message">
                    <span class="icon">🖼️</span>
                    <h4>メニュー表が設定されていません</h4>
                    <p>下のボタンから新しいメニュー表をアップロードしてください</p>
                </div>
            `;
            lastUpdated.textContent = '';
        }
    } catch (error) {
        console.error('メニュー読み込みエラー:', error);
        const display = document.getElementById('current-menu-display');
        display.innerHTML = `
            <div class="no-menu-message">
                <span class="icon">❌</span>
                <h4>メニューの読み込みに失敗しました</h4>
                <p>${error.message}</p>
            </div>
        `;
    }
}

// アップロードモーダルを開く
function openUploadModal() {
    const modal = document.getElementById('menu-upload-modal');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

// アップロードモーダルを閉じる
function closeUploadModal() {
    const modal = document.getElementById('menu-upload-modal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
        document.getElementById('menu-upload-form').reset();
        document.getElementById('menu-preview').innerHTML = '';
        document.getElementById('menu-preview').classList.add('empty');
    }, 300);
}

// フォームハンドラーを設定
function setupFormHandlers() {
    // ファイル選択時のプレビュー
    document.getElementById('menu-file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('menu-preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="プレビュー">`;
                preview.classList.remove('empty');
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
            preview.classList.add('empty');
        }
    });
    
    // フォーム送信
    document.getElementById('menu-upload-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // アップロード中の表示
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> アップロード中...';
        
        try {
            const response = await fetch('menu-simple-upload.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('メニュー表をアップロードしました', 'success');
                closeUploadModal();
                loadCurrentMenu(); // 表示を更新
            } else {
                showAlert('アップロードに失敗しました: ' + result.error, 'error');
            }
        } catch (error) {
            console.error('アップロードエラー:', error);
            showAlert('アップロードエラー: ' + error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'アップロード';
        }
    });
    
    // モーダル外クリックで閉じる
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('menu-upload-modal');
        if (e.target === modal) {
            closeUploadModal();
        }
    });
}

// アラート表示
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    switch(type) {
        case 'success':
            alertDiv.style.background = '#28a745';
            alertDiv.innerHTML = '✅ ' + message;
            break;
        case 'error':
            alertDiv.style.background = '#dc3545';
            alertDiv.innerHTML = '❌ ' + message;
            break;
        default:
            alertDiv.style.background = '#17a2b8';
            alertDiv.innerHTML = 'ℹ️ ' + message;
    }
    
    document.body.appendChild(alertDiv);
    
    // 3秒後に削除
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}

// グローバル関数として登録
window.openUploadModal = openUploadModal;
window.closeUploadModal = closeUploadModal;
</script>