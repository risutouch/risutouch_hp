// Common JavaScript functions for PHP admin panel

// ページ読み込み時の処理
document.addEventListener('DOMContentLoaded', function() {
    // F5リフレッシュ対策：リロードされたページかどうかをチェック
    if (performance.navigation.type === performance.navigation.TYPE_RELOAD) {
        // リロードの場合は成功メッセージを即座に削除
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            alert.style.display = 'none';
        });
    } else {
        // 通常のページロードの場合は4秒後に自動非表示
        const alerts = document.querySelectorAll('.alert-success, .alert-error');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }, 4000);
        });
    }
});

// Modal functions
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

// 商品の表示/非表示切り替え
async function toggleProductVisible(productName, isVisible) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_product_visible');
        formData.append('product_name', productName);
        formData.append('visible', isVisible ? '1' : '0');
        
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const label = document.querySelector(`#product-${CSS.escape(productName)}-visible`).parentNode.querySelector('.toggle-label');
            label.textContent = isVisible ? '表示中' : '非表示';
            console.log(`商品 ${productName} を${isVisible ? '表示' : '非表示'}にしました`);
        } else {
            throw new Error('サーバーエラー');
        }
        
    } catch (error) {
        alert('設定の変更に失敗しました: ' + error.message);
        // チェックボックスを元に戻す
        document.querySelector(`#product-${CSS.escape(productName)}-visible`).checked = !isVisible;
    }
}

// 店舗の表示/非表示切り替え
async function toggleShopVisible(shopId, isVisible) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_shop_visible');
        formData.append('shop_id', shopId);
        formData.append('visible', isVisible ? '1' : '0');
        
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const label = document.querySelector(`#shop-${shopId}-visible`).parentNode.querySelector('.toggle-label');
            label.textContent = isVisible ? '表示中' : '非表示';
            console.log(`店舗 ${shopId} を${isVisible ? '表示' : '非表示'}にしました`);
        } else {
            throw new Error('サーバーエラー');
        }
        
    } catch (error) {
        alert('設定の変更に失敗しました: ' + error.message);
        // チェックボックスを元に戻す
        document.getElementById(`shop-${shopId}-visible`).checked = !isVisible;
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal.active');
        if (activeModal) {
            closeModal(activeModal.id);
        }
    }
});

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Add form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('必須項目を入力してください');
            }
        });
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// File upload preview functions
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    }
}

function previewImages(input, previewId) {
    const preview = document.getElementById(previewId);
    const files = Array.from(input.files);
    
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
}

// Loading button function
function setLoadingButton(button, loading = true) {
    if (loading) {
        button.dataset.originalText = button.textContent;
        button.innerHTML = '<span class="loading"></span> 処理中...';
        button.disabled = true;
    } else {
        button.textContent = button.dataset.originalText;
        button.disabled = false;
    }
}

// Confirmation dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Auto-save functionality (for future use)
function autoSave(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    localStorage.setItem(`autosave_${formId}`, JSON.stringify(data));
}

function loadAutoSave(formId) {
    const savedData = localStorage.getItem(`autosave_${formId}`);
    if (!savedData) return;
    
    const data = JSON.parse(savedData);
    const form = document.getElementById(formId);
    
    for (let [key, value] of Object.entries(data)) {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) {
            if (field.type === 'checkbox') {
                field.checked = value === 'on';
            } else {
                field.value = value;
            }
        }
    }
}

// Clear auto-save data
function clearAutoSave(formId) {
    localStorage.removeItem(`autosave_${formId}`);
}

// Tab navigation enhancement
function enhanceTabNavigation() {
    const navLinks = document.querySelectorAll('.nav-btn');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            window.location.href = href;
        });
    });
}

// Initialize enhancements
document.addEventListener('DOMContentLoaded', function() {
    enhanceTabNavigation();
    
    // Initialize tooltips (if needed)
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });
});

// Utility functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Export functions for global use
window.closeModal = closeModal;
window.previewImage = previewImage;
window.previewImages = previewImages;
window.setLoadingButton = setLoadingButton;
window.confirmAction = confirmAction;
window.autoSave = autoSave;
window.loadAutoSave = loadAutoSave;
window.clearAutoSave = clearAutoSave;
window.formatFileSize = formatFileSize;
window.formatDate = formatDate;