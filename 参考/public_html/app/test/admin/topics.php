<?php
// 直接アクセス防止
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    die('Access Denied');
}

session_start();

// CSRF トークン生成
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

$csrf_token = generateCSRFToken();

// トピック設定を読み込み
$topicsConfigFile = '../assets/data/topics_config.json';
$topicsConfig = [];
if (file_exists($topicsConfigFile)) {
    $topicsConfig = json_decode(file_get_contents($topicsConfigFile), true) ?: [];
}

// デフォルト設定
$defaultTopics = [
    'sweets-quiz' => true,
    'seasonal-calendar' => true
];

// 各トピックの公開状態を取得
function getTopicPublishedState($topicId, $topicsConfig, $defaultTopics) {
    if (isset($topicsConfig[$topicId]['published'])) {
        return $topicsConfig[$topicId]['published'];
    }
    return $defaultTopics[$topicId] ?? true;
}

// 各トピックのサムネイル画像パスを取得
function getTopicThumbnail($topicId, $topicsConfig) {
    if (isset($topicsConfig[$topicId]['thumbnail'])) {
        return '../' . $topicsConfig[$topicId]['thumbnail'];
    }
    // デフォルトの画像パス
    return "../assets/images/entertainment/{$topicId}.jpg";
}

// 各トピックのタイトルを取得
function getTopicTitle($topicId, $topicsConfig) {
    if (isset($topicsConfig[$topicId]['title'])) {
        return $topicsConfig[$topicId]['title'];
    }
    // デフォルトのタイトル
    $defaultTitles = [
        'sweets-quiz' => 'あなたにピッタリのお菓子診断',
        'seasonal-calendar' => '季節の素材暦'
    ];
    return $defaultTitles[$topicId] ?? $topicId;
}

// 各トピックの説明を取得
function getTopicDescription($topicId, $topicsConfig) {
    if (isset($topicsConfig[$topicId]['description'])) {
        return $topicsConfig[$topicId]['description'];
    }
    // デフォルトの説明
    $defaultDescriptions = [
        'sweets-quiz' => '簡単な質問に答えて、あなたの好みに合う焼き菓子を見つけよう！2択の質問形式で、40種類以上の商品から最適な一品をおすすめします。',
        'seasonal-calendar' => '四季を通じた旬の素材と、それを使った焼き菓子の魅力をご紹介。春夏秋冬それぞれの代表的な素材の豆知識と、りすたっちの商品をご案内します。'
    ];
    return $defaultDescriptions[$topicId] ?? '';
}
?>

<!-- Topics Tab -->
<div class="tab-header">
    <h2>トピック管理</h2>
    <p class="tab-description">現在サイトで公開中のトピックコンテンツの管理</p>
    <button class="btn btn-primary" onclick="addNewTopic()">トピックを追加</button>
</div>

<div class="topics-list">
    <div class="item-card">
        <div class="item-card-image">
            <img id="sweets-quiz-thumbnail" src="<?php echo htmlspecialchars(getTopicThumbnail('sweets-quiz', $topicsConfig)); ?>" alt="お菓子診断" onerror="this.src='../assets/images/logo.png'">
        </div>
        <div class="item-card-content">
            <h3 class="item-card-title"><?php echo htmlspecialchars(getTopicTitle('sweets-quiz', $topicsConfig)); ?></h3>
            <p class="item-card-description"><?php echo htmlspecialchars(getTopicDescription('sweets-quiz', $topicsConfig)); ?></p>
            <div class="item-card-meta">
                <?php $sweetsQuizPublished = getTopicPublishedState('sweets-quiz', $topicsConfig, $defaultTopics); ?>
                <label class="toggle-switch">
                    <input type="checkbox" id="sweets-quiz-published" <?php echo $sweetsQuizPublished ? 'checked' : ''; ?> onchange="toggleTopicPublished('sweets-quiz', this.checked)">
                    <span class="toggle-slider"></span>
                    <span class="toggle-label"><?php echo $sweetsQuizPublished ? '公開中' : '非表示'; ?></span>
                </label>
                <span class="item-card-badge">診断システム</span>
            </div>
            <div class="item-card-actions">
                <div class="order-controls">
                    <input type="number" 
                           class="order-input" 
                           value="<?php echo isset($topicsConfig['sweets-quiz']['order']) ? $topicsConfig['sweets-quiz']['order'] : 1; ?>" 
                           min="1" 
                           max="2"
                           data-topic-id="sweets-quiz"
                           onchange="updateTopicOrder(this)"
                           onblur="updateTopicOrder(this)">
                </div>
                <button class="btn btn-secondary btn-small" data-topic-id="sweets-quiz" onclick="editTopic('sweets-quiz')">編集</button>
                <button class="btn btn-primary btn-small" data-topic-id="sweets-quiz" data-topic-title="<?php echo htmlspecialchars(getTopicTitle('sweets-quiz', $topicsConfig)); ?>" onclick="previewTopic('sweets-quiz', '<?php echo htmlspecialchars(getTopicTitle('sweets-quiz', $topicsConfig)); ?>')">プレビュー</button>
            </div>
        </div>
    </div>
    
    <div class="item-card">
        <div class="item-card-image">
            <img id="seasonal-calendar-thumbnail" src="<?php echo htmlspecialchars(getTopicThumbnail('seasonal-calendar', $topicsConfig)); ?>" alt="季節の素材暦" onerror="this.src='../assets/images/logo.png'">
        </div>
        <div class="item-card-content">
            <h3 class="item-card-title"><?php echo htmlspecialchars(getTopicTitle('seasonal-calendar', $topicsConfig)); ?></h3>
            <p class="item-card-description"><?php echo htmlspecialchars(getTopicDescription('seasonal-calendar', $topicsConfig)); ?></p>
            <div class="item-card-meta">
                <?php $seasonalCalendarPublished = getTopicPublishedState('seasonal-calendar', $topicsConfig, $defaultTopics); ?>
                <label class="toggle-switch">
                    <input type="checkbox" id="seasonal-calendar-published" <?php echo $seasonalCalendarPublished ? 'checked' : ''; ?> onchange="toggleTopicPublished('seasonal-calendar', this.checked)">
                    <span class="toggle-slider"></span>
                    <span class="toggle-label"><?php echo $seasonalCalendarPublished ? '公開中' : '非表示'; ?></span>
                </label>
                <span class="item-card-badge">情報コンテンツ</span>
            </div>
            <div class="item-card-actions">
                <div class="order-controls">
                    <input type="number" 
                           class="order-input" 
                           value="<?php echo isset($topicsConfig['seasonal-calendar']['order']) ? $topicsConfig['seasonal-calendar']['order'] : 2; ?>" 
                           min="1" 
                           max="2"
                           data-topic-id="seasonal-calendar"
                           onchange="updateTopicOrder(this)"
                           onblur="updateTopicOrder(this)">
                </div>
                <button class="btn btn-secondary btn-small" data-topic-id="seasonal-calendar" onclick="editTopic('seasonal-calendar')">編集</button>
                <button class="btn btn-primary btn-small" data-topic-id="seasonal-calendar" data-topic-title="<?php echo htmlspecialchars(getTopicTitle('seasonal-calendar', $topicsConfig)); ?>" onclick="previewTopic('seasonal-calendar', '<?php echo htmlspecialchars(getTopicTitle('seasonal-calendar', $topicsConfig)); ?>')">プレビュー</button>
            </div>
        </div>
    </div>
</div>

<!-- Topic Editor -->
<div id="topic-editor" class="modal" style="display: none;">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="editor-title">トピック編集</h3>
            <button class="modal-close" onclick="closeEditor()">&times;</button>
        </div>
        
        <div class="modal-form">
            <div class="form-group">
                <label for="topic-title">タイトル:</label>
                <input type="text" id="topic-title" placeholder="トピックのタイトル">
            </div>
            
            <div class="form-group">
                <label for="topic-description">説明:</label>
                <textarea id="topic-description" rows="3" placeholder="トピックの説明文"></textarea>
            </div>
            
            <div class="form-group">
                <label for="topic-thumbnail">サムネイル画像:</label>
                <input type="file" id="topic-thumbnail" accept="image/*" onchange="previewThumbnailInEditor()">
                <div id="thumbnail-preview-editor" class="image-preview" style="display: none;">
                    <img id="preview-thumbnail-editor" src="" alt="サムネイルプレビュー" style="max-width: 200px; max-height: 150px;">
                </div>
            </div>
            
            <div class="form-group">
                <label for="topic-file">ファイル:</label>
                <input type="text" id="topic-file" readonly>
            </div>
            
            <div class="form-group">
                <label for="topic-content">内容:</label>
                <textarea id="topic-content" rows="20" placeholder="HTMLコンテンツを入力してください..." style="font-family: 'Courier New', monospace; font-size: 0.9rem;"></textarea>
            </div>
            
            <div class="form-actions">
                <button onclick="saveTopic()" class="btn btn-primary">保存</button>
                <button onclick="closeEditor()" class="btn btn-secondary">キャンセル</button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="preview-modal" class="modal" style="display: none;">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="preview-title">プレビュー</h3>
            <button class="modal-close" onclick="closePreview()">&times;</button>
        </div>
        <div id="preview-body" class="modal-body">
            <!-- プレビューコンテンツがここに表示される -->
        </div>
    </div>
</div>


<script>
let currentEditingTopic = null;
const csrfToken = '<?php echo $csrf_token; ?>';

// トピック追加機能
function addNewTopic() {
    currentEditingTopic = null;
    
    // モーダルのタイトルを設定
    document.getElementById('editor-title').textContent = 'トピックを追加';
    
    // フォームをクリア
    document.getElementById('topic-title').value = '';
    document.getElementById('topic-description').value = '';
    document.getElementById('topic-content').value = '';
    document.getElementById('topic-file').value = '';
    document.getElementById('topic-thumbnail').value = '';
    document.getElementById('thumbnail-preview-editor').style.display = 'none';
    
    // モーダルを表示
    const modal = document.getElementById('topic-editor');
    modal.style.display = 'flex';
    setTimeout(() => modal.classList.add('active'), 10);
}

// トピック編集機能
function editTopic(topicId) {
    currentEditingTopic = topicId;
    
    // モーダルのタイトルを設定
    document.getElementById('editor-title').textContent = `${getTopicTitle(topicId)} - 編集`;
    
    // フォームに現在の値を設定
    document.getElementById('topic-title').value = getTopicTitle(topicId);
    document.getElementById('topic-description').value = getTopicDescription(topicId);
    document.getElementById('topic-file').value = `../assets/topics/${topicId}.html`;
    
    // 現在のサムネイル画像を表示
    const currentThumbnail = document.getElementById(`${topicId}-thumbnail`);
    if (currentThumbnail && currentThumbnail.src && !currentThumbnail.src.includes('logo.png')) {
        const previewImg = document.getElementById('preview-thumbnail-editor');
        const previewContainer = document.getElementById('thumbnail-preview-editor');
        previewImg.src = currentThumbnail.src;
        previewContainer.style.display = 'block';
    }
    
    // HTMLコンテンツを読み込み
    loadTopicContent(topicId);
    
    // モーダルを表示
    const modal = document.getElementById('topic-editor');
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
}

async function loadTopicContent(topicId) {
    try {
        const response = await fetch(`../assets/topics/${topicId}.html`);
        if (response.ok) {
            const content = await response.text();
            document.getElementById('topic-content').value = content;
        }
    } catch (error) {
        console.error('コンテンツの読み込みに失敗しました:', error);
    }
}

function previewThumbnailInEditor() {
    const fileInput = document.getElementById('topic-thumbnail');
    const previewImg = document.getElementById('preview-thumbnail-editor');
    const previewContainer = document.getElementById('thumbnail-preview-editor');
    
    if (fileInput.files && fileInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(fileInput.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
}

function closeEditor() {
    const modal = document.getElementById('topic-editor');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
    
    currentEditingTopic = null;
    // フォームをリセット
    document.getElementById('topic-title').value = '';
    document.getElementById('topic-description').value = '';
    document.getElementById('topic-content').value = '';
    document.getElementById('topic-thumbnail').value = '';
    document.getElementById('thumbnail-preview-editor').style.display = 'none';
}

async function saveTopic() {
    if (!currentEditingTopic) {
        alert('編集中のトピックが選択されていません');
        return;
    }
    
    const title = document.getElementById('topic-title').value;
    const description = document.getElementById('topic-description').value;
    const content = document.getElementById('topic-content').value;
    const thumbnailFile = document.getElementById('topic-thumbnail').files[0];
    
    if (!title || !description || !content) {
        alert('タイトル、説明、内容は必須項目です');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_topic');
    formData.append('topic_id', currentEditingTopic);
    formData.append('title', title);
    formData.append('description', description);
    formData.append('content', content);
    formData.append('csrf_token', csrfToken);
    
    if (thumbnailFile) {
        formData.append('thumbnail', thumbnailFile);
    }
    
    try {
        const response = await fetch('topics-handler.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('トピックを保存しました');
            
            // ページ上の表示を更新
            updateTopicDisplay(currentEditingTopic, title, description);
            
            // サムネイル画像も更新
            if (result.thumbnail_path) {
                const thumbnailImg = document.getElementById(`${currentEditingTopic}-thumbnail`);
                if (thumbnailImg) {
                    // パスが'assets/'で始まる場合、相対パスとして'../'を追加
                    const imagePath = result.thumbnail_path.startsWith('assets/') 
                        ? '../' + result.thumbnail_path 
                        : result.thumbnail_path;
                    thumbnailImg.src = imagePath + '?t=' + Date.now();
                }
            }
            
            closeEditor();
        } else {
            alert('保存に失敗しました: ' + result.error);
        }
        
    } catch (error) {
        alert('保存に失敗しました: ' + error.message);
    }
}

function updateTopicDisplay(topicId, title, description) {
    // タイトルと説明を更新
    const titleElement = document.querySelector(`[data-topic-id="${topicId}"]`).closest('.item-card').querySelector('.item-card-title');
    const descriptionElement = document.querySelector(`[data-topic-id="${topicId}"]`).closest('.item-card').querySelector('.item-card-description');
    
    if (titleElement) titleElement.textContent = title;
    if (descriptionElement) descriptionElement.textContent = description;
    
    // プレビューボタンのdata属性も更新
    const previewButton = document.querySelector(`[onclick*="previewTopic('${topicId}'"]`);
    if (previewButton) {
        previewButton.setAttribute('onclick', `previewTopic('${topicId}', '${title}')`);
        previewButton.setAttribute('data-topic-title', title);
    }
}

// 公開/非公開切り替え
async function toggleTopicPublished(topicId, isPublished) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_published');
        formData.append('topic_id', topicId);
        formData.append('published', isPublished ? '1' : '0');
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch('topics-handler.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const label = document.querySelector(`#${topicId}-published`).parentNode.querySelector('.toggle-label');
            label.textContent = isPublished ? '公開中' : '非公開';
        } else {
            alert('設定の変更に失敗しました: ' + result.error);
            // チェックボックスを元に戻す
            document.getElementById(`${topicId}-published`).checked = !isPublished;
        }
        
    } catch (error) {
        alert('設定の変更に失敗しました: ' + error.message);
        // チェックボックスを元に戻す
        document.getElementById(`${topicId}-published`).checked = !isPublished;
    }
}


async function previewTopic(topicId, title) {
    try {
        const response = await fetch(`../assets/topics/${topicId}.html`);
        if (!response.ok) {
            throw new Error(`ファイルの読み込みに失敗しました (${response.status})`);
        }
        const content = await response.text();
        
        document.getElementById('preview-title').textContent = title;
        document.getElementById('preview-body').innerHTML = content;
        const previewModal = document.getElementById('preview-modal');
        previewModal.style.display = 'flex';
        previewModal.classList.add('active');
        
    } catch (error) {
        console.error('プレビューエラー:', error);
        alert('プレビューの読み込みに失敗しました: ' + error.message);
    }
}

function closePreview() {
    const previewModal = document.getElementById('preview-modal');
    previewModal.classList.remove('active');
    setTimeout(() => {
        previewModal.style.display = 'none';
    }, 300);
}

function getTopicTitle(topicId) {
    // DOMから現在のタイトルを取得
    const titleElement = document.querySelector(`[data-topic-id="${topicId}"]`)?.closest('.item-card')?.querySelector('.item-card-title');
    if (titleElement) {
        return titleElement.textContent;
    }
    
    // フォールバック用のデフォルトタイトル
    const titles = {
        'sweets-quiz': 'あなたにピッタリのお菓子診断',
        'seasonal-calendar': '季節の素材暦'
    };
    return titles[topicId] || topicId;
}

function getTopicDescription(topicId) {
    // DOMから現在の説明を取得
    const descriptionElement = document.querySelector(`[data-topic-id="${topicId}"]`)?.closest('.item-card')?.querySelector('.item-card-description');
    if (descriptionElement) {
        return descriptionElement.textContent;
    }
    
    // フォールバック用のデフォルト説明
    const descriptions = {
        'sweets-quiz': '簡単な質問に答えて、あなたの好みに合う焼き菓子を見つけよう！2択の質問形式で、40種類以上の商品から最適な一品をおすすめします。',
        'seasonal-calendar': '四季を通じた旬の素材と、それを使った焼き菓子の魅力をご紹介。春夏秋冬それぞれの代表的な素材の豆知識と、りすたっちの商品をご案内します。'
    };
    return descriptions[topicId] || '';
}



// モーダル外クリックで閉じる
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

// ページ読み込み完了時の確認
document.addEventListener('DOMContentLoaded', function() {
    // 基本機能の初期化処理
});

// トピック順序更新
async function updateTopicOrder(inputElement) {
    const topicId = inputElement.dataset.topicId;
    const newOrder = parseInt(inputElement.value);
    
    if (!topicId || isNaN(newOrder)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_order');
        formData.append('topic_id', topicId);
        formData.append('order', newOrder);
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch('topics-handler.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // ページを更新して順序を反映
            location.reload();
        } else {
            alert('順序の更新に失敗しました: ' + result.error);
            // 入力値を元に戻す
            inputElement.value = inputElement.defaultValue;
        }
        
    } catch (error) {
        alert('順序の更新に失敗しました: ' + error.message);
        // 入力値を元に戻す
        inputElement.value = inputElement.defaultValue;
    }
}

// グローバル関数として明示的に定義
window.addNewTopic = addNewTopic;
window.editTopic = editTopic;
window.previewTopic = previewTopic;
window.toggleTopicPublished = toggleTopicPublished;
window.updateTopicOrder = updateTopicOrder;
</script>

<style>
/* ===== TOPICS LIST FOR ADMIN ===== */
.topics-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;
}

/* ボタンが確実にクリック可能になるように */
.item-card-actions button {
    pointer-events: auto;
    z-index: 10;
    position: relative;
}

/* 順序変更コントロール */
.order-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-right: 12px;
}

.order-input {
    width: 60px;
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    font-size: 0.9rem;
}

.order-input:focus {
    outline: none;
    border-color: var(--primary-color, #4a3c2a);
}

/* トグルスイッチ */
.toggle-switch {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.toggle-switch input[type="checkbox"] {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: relative;
    width: 44px;
    height: 24px;
    background-color: #ccc;
    border-radius: 24px;
    transition: 0.3s;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: 0.3s;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: var(--success-color, #28a745);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.toggle-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--gray-dark, #343a40);
}

/* モーダル共通スタイル */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.active {
    opacity: 1;
}

.modal .modal-content {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-hover);
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.7);
    transition: transform 0.3s ease;
}

.modal.active .modal-content {
    transform: scale(1);
}

@media (max-width: 768px) {
    .topics-list {
        grid-template-columns: 1fr;
    }
    
    .modal .modal-content {
        width: 95%;
        margin: 10px;
    }
}
</style>