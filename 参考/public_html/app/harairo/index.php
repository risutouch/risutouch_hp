<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_login();

$users = load_users();
$settings = load_settings();
$roleLabelSupplier = $settings['role_labels']['supplier'] ?? role_label('supplier');
$roleLabelReceiver = $settings['role_labels']['receiver'] ?? role_label('receiver');

$monthParam = (string) request_get('month', '');
// 月パラメータがない場合は今月にリダイレクト
if ($monthParam === '') {
    redirect('index.php?month=' . urlencode(current_year_month()));
}
$yearMonth = normalize_year_month($monthParam);
$deliveries = load_deliveries($yearMonth);
$flash = get_flash();

$invoice = load_invoice($yearMonth);
$isInvoicePublished = is_array($invoice) && (($invoice['status'] ?? '') === 'published');

$monthDate = DateTime::createFromFormat('Y-m-d', $yearMonth . '-01') ?: new DateTime('first day of this month');
$prevMonth = (clone $monthDate)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $monthDate)->modify('+1 month')->format('Y-m');

$displayDeliveries = [];
$monthlyGross = 0.0;
$unconfirmedCount = 0;
$confirmedCount = 0;
$isSupplier = is_supplier();
$isReceiver = is_receiver();

foreach ($deliveries as $delivery) {
    $items = $delivery['items'] ?? [];
    $total = 0.0;
    foreach ($items as $item) {
        $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0.0;
        $price = isset($item['unit_price']) ? (float) $item['unit_price'] : 0.0;
        $total += $qty * $price;
    }
    $delivery['computed_total'] = $total;
    $status = $delivery['status'] ?? 'unconfirmed';
    if ($status === 'confirmed') {
        $confirmedCount++;
    } else {
        $unconfirmedCount++;
    }
    $monthlyGross += $total;
    $delivery['confirmable'] = $status !== 'confirmed' && $isReceiver;
    $displayDeliveries[] = $delivery;
}

$pageTitle = '納品一覧';
$ogTitle = $monthDate->format('Y年n月') . 'の納品一覧';
$ogDescription = $monthDate->format('Y年n月') . 'の納品一覧';
include __DIR__ . '/partials/header.php';
?>

<section class="card">
    <div class="section-header">
        <div>
            <h1 class="section-title"><?= escape($monthDate->format('Y年n月')) ?>の納品</h1>
            <div class="section-meta">
                合計金額: <?= format_currency($monthlyGross) ?> 円 ／ 確認済み <?= $confirmedCount ?> 件 ／ 未確認 <?= $unconfirmedCount ?> 件
                <?php if ($isInvoicePublished): ?>
                    <span style="margin-left: 1rem; padding: 0.25rem 0.5rem; background: #f0f0f0; border-radius: 4px; font-weight: bold; color: #666;">請求書公開済み</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="actions" style="flex-wrap: wrap;">
            <button type="button" class="secondary-link compact" data-share-page title="シェア">&#x1F517;</button>
            <a href="index.php?month=<?= escape($prevMonth) ?>" class="secondary-link compact">&laquo; 前月</a>
            <a href="index.php?month=<?= escape(current_year_month()) ?>" class="secondary-link compact">今月</a>
            <a href="index.php?month=<?= escape($nextMonth) ?>" class="secondary-link compact">翌月 &raquo;</a>
            <?php if ($isSupplier && !$isInvoicePublished): ?>
                <a href="delivery_edit.php?month=<?= escape($yearMonth) ?>" class="primary-link compact">＋ 納品を登録</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($flash): ?>
    <div class="card flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>">
        <?= escape($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<?php if ($isReceiver && !$isSupplier): ?>
    <div class="card info-card">
        <p style="margin: 0; color: #555; font-size: 0.95rem;">
            納品内容を確認し、問題なければ「確認」ボタンを押してください。修正が必要な場合は <?= escape($roleLabelSupplier) ?> へご連絡ください。
        </p>
    </div>
<?php endif; ?>

<section class="card">
    <?php if (empty($displayDeliveries)): ?>
        <p class="info-text">この月の納品はまだ登録されていません。</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table deliveries-table">
                <thead>
                <tr>
                    <th style="width: 12%;">納品日</th>
                    <th>品目</th>
                    <th style="width: 15%;">金額</th>
                    <th style="width: 20%;">ステータス</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($displayDeliveries as $delivery): ?>
                    <?php $status = $delivery['status'] ?? 'unconfirmed'; ?>
                    <tr>
                        <td><strong><?= escape($delivery['delivery_date'] ?? '-') ?></strong></td>
                        <td>
                            <ul class="items-list">
                                <?php foreach ($delivery['items'] ?? [] as $item): ?>
                                    <?php
                                    $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
                                    $isReturn = $qty < 0;
                                    $displayQty = $isReturn ? abs($qty) : $qty;
                                    ?>
                                    <li>
                                        <?= escape($item['name'] ?? '') ?>
                                        <?php if (isset($item['unit_price'])): ?>
                                            <span class="meta">@ <?= format_currency((float) ($item['unit_price'] ?? 0)) ?> 円</span>
                                        <?php endif; ?>
                                        <?php if (isset($item['quantity'])): ?>
                                            <span class="meta"><?= $isReturn ? '-' : '' ?><?= escape((string) $displayQty) ?><?= escape($item['unit'] ? ' ' . $item['unit'] : '') ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (!empty($delivery['notes'])): ?>
                                <div class="meta">メモ: <?= nl2br(escape($delivery['notes'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= format_currency((float) $delivery['computed_total']) ?> 円</td>
                        <td class="status-cell">
                            <span class="badge <?= $status === 'confirmed' ? 'confirmed' : 'unconfirmed' ?>">
                                <?= $status === 'confirmed' ? '確認済み' : '未確認' ?>
                            </span>
                        </td>
                    </tr>
                    <tr class="delivery-actions-row">
                        <td colspan="4">
                            <div class="actions-row">
                                <?php
                                $updateLabel = '';
                                if (!empty($delivery['updated_at'])) {
                                    $updateLabel = '更新: ' . date('Y-m-d H:i', strtotime($delivery['updated_at']));
                                } elseif (!empty($delivery['created_at'])) {
                                    $updateLabel = '登録: ' . date('Y-m-d H:i', strtotime($delivery['created_at']));
                                }
                                ?>
                                <?php if ($updateLabel !== ''): ?>
                                    <div class="meta"><?= escape($updateLabel) ?></div>
                                <?php endif; ?>
                                <div class="actions-group">
                                    <?php if ($delivery['confirmable']): ?>
                                        <form method="post" action="delivery_action.php" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <input type="hidden" name="delivery_id" value="<?= escape($delivery['id']) ?>">
                                            <input type="hidden" name="month" value="<?= escape($yearMonth) ?>">
                                            <button type="submit" class="primary compact">確認</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($isSupplier && !$isInvoicePublished): ?>
                                        <a class="secondary-link compact" href="delivery_edit.php?id=<?= escape($delivery['id']) ?>&month=<?= escape($yearMonth) ?>">編集</a>
                                        <form method="post" action="delivery_action.php" class="inline-form" onsubmit="return confirm('この納品を削除しますか？');">
                                            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="delivery_id" value="<?= escape($delivery['id']) ?>">
                                            <input type="hidden" name="month" value="<?= escape($yearMonth) ?>">
                                            <button type="submit" class="secondary danger compact">削除</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shareButton = document.querySelector('[data-share-page]');
        if (!shareButton) {
            return;
        }
        shareButton.addEventListener('click', function () {
            // 現在の月パラメータを取得
            const urlParams = new URLSearchParams(window.location.search);
            const month = urlParams.get('month') || '<?= escape($yearMonth) ?>';

            // 月パラメータを含むURLを生成
            const baseUrl = window.location.origin + window.location.pathname;
            const url = baseUrl + '?month=' + encodeURIComponent(month);
            const title = '<?= escape($monthDate->format('Y年n月')) ?>の納品一覧';

            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).catch(function(err) {
                    console.log('シェアエラー:', err);
                });
            } else {
                navigator.clipboard.writeText(url).then(function() {
                    alert('URLをクリップボードにコピーしました');
                }).catch(function(err) {
                    prompt('URLをコピーしてください:', url);
                });
            }
        });
    });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>

