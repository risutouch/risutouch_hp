<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_login();

$isSupplier = is_supplier();
$isReceiver = is_receiver();

// 全ての請求書ファイルを取得
$dataDir = data_path('');
$invoiceFiles = glob($dataDir . '/invoice-*.json');
$invoices = [];

foreach ($invoiceFiles as $file) {
    $data = read_json_file($file, null);
    if (!is_array($data)) {
        continue;
    }

    $status = $data['status'] ?? 'draft';

    // receiverの場合は公開済みのみ表示
    if ($isReceiver && !$isSupplier && $status !== 'published') {
        continue;
    }

    // 入金確認済みかどうか
    $paymentConfirmed = $data['payment_confirmed'] ?? false;

    $invoices[] = [
        'period' => $data['period'] ?? '',
        'net_total' => $data['net_total'] ?? 0.0,
        'status' => $status,
        'payment_confirmed' => $paymentConfirmed,
        'published_at' => $data['published_at'] ?? null,
        'payment_confirmed_at' => $data['payment_confirmed_at'] ?? null,
    ];
}

// 期間で降順ソート（新しい順）
usort($invoices, function ($a, $b) {
    return strcmp($b['period'], $a['period']);
});

$pageTitle = '請求書一覧';
include __DIR__ . '/partials/header.php';
?>

<section class="card">
    <div class="section-header">
        <h1 class="section-title">請求書一覧</h1>
        <div class="actions compact-actions">
            <button type="button" class="secondary-link compact" data-share-page title="シェア">&#x1F517;</button>
        </div>
    </div>
</section>

<section class="card">
    <?php if (empty($invoices)): ?>
        <p class="info-text">請求書はまだありません。</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th style="width: 15%;">対象月</th>
                    <th style="width: 20%;">支払金額</th>
                    <th style="width: 20%;">ステータス</th>
                    <?php if ($isSupplier): ?>
                        <th style="width: 20%;">入金状況</th>
                    <?php endif; ?>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <?php
                    $period = $invoice['period'];
                    $status = $invoice['status'];
                    $paymentConfirmed = $invoice['payment_confirmed'];

                    // ステータス表示
                    if ($status === 'published') {
                        $statusLabel = '公開済み';
                        $statusClass = 'confirmed';
                    } else {
                        $statusLabel = '下書き';
                        $statusClass = 'unconfirmed';
                    }

                    // 入金状況表示
                    if ($paymentConfirmed) {
                        $paymentLabel = '入金確認済み';
                        $paymentClass = 'confirmed';
                    } else {
                        $paymentLabel = '未確認';
                        $paymentClass = 'unconfirmed';
                    }

                    $periodDate = DateTime::createFromFormat('Y-m', $period);
                    $periodDisplay = $periodDate ? $periodDate->format('Y年n月') : $period;
                    ?>
                    <tr>
                        <td><strong><?= escape($periodDisplay) ?></strong></td>
                        <td><?= format_currency($invoice['net_total']) ?> 円</td>
                        <td>
                            <span class="badge <?= $statusClass ?>">
                                <?= escape($statusLabel) ?>
                            </span>
                        </td>
                        <?php if ($isSupplier): ?>
                            <td>
                                <span class="badge <?= $paymentClass ?>">
                                    <?= escape($paymentLabel) ?>
                                </span>
                                <?php if ($paymentConfirmed && !empty($invoice['payment_confirmed_at'])): ?>
                                    <div class="meta" style="margin-top: 0.25rem;">
                                        <?= escape(date('Y-m-d', strtotime($invoice['payment_confirmed_at']))) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td>
                            <div class="actions-group">
                                <a class="secondary-link compact" href="invoice.php?month=<?= escape($period) ?>">表示</a>
                                <?php if ($isSupplier && $status === 'published' && !$paymentConfirmed): ?>
                                    <form method="post" action="invoice_action.php" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="confirm_payment">
                                        <input type="hidden" name="month" value="<?= escape($period) ?>">
                                        <button type="submit" class="primary compact">入金確認</button>
                                    </form>
                                <?php endif; ?>
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
            const url = window.location.href;
            const title = document.title;

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
