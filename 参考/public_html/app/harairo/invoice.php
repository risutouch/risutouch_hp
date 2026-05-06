<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_login();

$monthParam = (string) request_get('month', '');
// 月パラメータがない場合は今月にリダイレクト
if ($monthParam === '') {
    redirect('invoice.php?month=' . urlencode(current_year_month()));
}
$yearMonth = normalize_year_month($monthParam);
$deliveries = load_deliveries($yearMonth);
$settings = load_settings();
$flash = get_flash();
$isSupplier = is_supplier();

// 備考更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isSupplier) {
    if (verify_csrf_token(request_post('csrf_token'))) {
        $action = request_post('action');
        if ($action === 'update_notes') {
            $invoice = load_invoice($yearMonth);
            if ($invoice && ($invoice['status'] ?? 'draft') === 'draft') {
                $invoice['notes'] = trim((string) request_post('notes', ''));
                $invoice['updated_at'] = date('c');
                save_invoice($yearMonth, $invoice);
                set_flash('success', '備考を更新しました。');
            }
        }
    }
    redirect('invoice.php?month=' . urlencode($yearMonth));
}

$invoice = load_invoice($yearMonth);

if ($isSupplier) {
    $needsRebuild = !$invoice || (($invoice['status'] ?? 'draft') === 'draft');
    if ($needsRebuild) {
        $existingInvoice = is_array($invoice) ? $invoice : [];
        $invoice = build_invoice($yearMonth, $deliveries, get_commission_rate(), $existingInvoice);

        // 確定納品がある場合のみ保存
        if (!empty($invoice['items'])) {
            $invoice['generated_by'] = current_user_id();
            $invoice['generated_at'] = $invoice['updated_at'];
            $invoice['status'] = $invoice['status'] ?? 'draft';
            save_invoice($yearMonth, $invoice);
        }
    }
}

$invoiceData = is_array($invoice) ? $invoice : [];
$hasInvoice = $invoiceData !== [];

$monthDate = DateTime::createFromFormat('Y-m-d', $yearMonth . '-01') ?: new DateTime('first day of this month');
$prevMonth = (clone $monthDate)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $monthDate)->modify('+1 month')->format('Y-m');

$totalDeliveries = count($deliveries);
$confirmedDeliveries = 0;
foreach ($deliveries as $delivery) {
    if (($delivery['status'] ?? 'unconfirmed') === 'confirmed') {
        $confirmedDeliveries++;
    }
}

$roleLabelSupplier = $settings['role_labels']['supplier'] ?? role_label('supplier');
$roleLabelReceiver = $settings['role_labels']['receiver'] ?? role_label('receiver');

$statusLabels = [
    'draft' => sprintf('作成中 - %sのみ閲覧可能', $roleLabelSupplier),
    'published' => sprintf('公開済み - %sが閲覧可能', $roleLabelReceiver),
];

$invoiceStatus = $invoiceData['status'] ?? null;
$invoiceVisible = $hasInvoice && ($invoiceStatus === 'published' || $isSupplier);
$canPublish = $isSupplier && $invoiceStatus === 'draft';
$canUnpublish = $isSupplier && $invoiceStatus === 'published';

$supplierInfo = $settings['supplier'] ?? [];
$receiverInfo = $settings['receiver'] ?? [];

$invoiceNotesGlobal = trim((string) ($settings['invoice_notes'] ?? ''));
$invoiceNotesLocal = trim((string) ($invoiceData['notes'] ?? ''));
$invoiceNotesCombined = trim(implode("
", array_filter([$invoiceNotesGlobal, $invoiceNotesLocal], static fn ($v) => $v !== '')));

$items = is_array($invoiceData['items'] ?? null) ? $invoiceData['items'] : [];
$grossTotal = (float) ($invoiceData['gross_total'] ?? 0);
$commissionRate = (float) ($invoiceData['commission_rate'] ?? get_commission_rate());
$commissionAmount = (float) ($invoiceData['commission_amount'] ?? ($grossTotal * $commissionRate));
$netTotal = (float) ($invoiceData['net_total'] ?? ($grossTotal - $commissionAmount));
$commissionPercentLabel = rtrim(rtrim(number_format($commissionRate * 100, 1, '.', ''), '0'), '.');

$invoiceId = $invoiceData['id'] ?? ('INV-' . str_replace('-', '', $yearMonth));
$issuedAt = $invoiceData['published_at']
    ?? $invoiceData['generated_at']
    ?? $invoiceData['updated_at']
    ?? $invoiceData['created_at']
    ?? null;
$issuedAtLabel = $issuedAt ? date('Y-m-d', strtotime($issuedAt)) : date('Y-m-d');
$dueInfo = $hasInvoice ? compute_invoice_due_info($yearMonth, $invoiceData['due_date'] ?? null, $settings) : null;
$canPrint = $invoiceVisible && !empty($items);

$pageTitle = '請求書';
$ogTitle = $monthDate->format('Y年n月') . 'の請求書';
$ogDescription = $monthDate->format('Y年n月') . 'の請求書';
include __DIR__ . '/partials/header.php';
?>

<section class="card">
    <div class="section-header">
        <div>
            <h1 class="section-title"><?= escape($monthDate->format('Y年n月')) ?>の請求書</h1>
            <div class="section-meta">
                納品件数: <?= $totalDeliveries ?> 件 / 確認済み <?= $confirmedDeliveries ?> 件
            </div>
        </div>
        <div class="actions compact-actions" style="flex-wrap: wrap;">
            <button type="button" class="secondary-link compact" data-share-page title="シェア">&#x1F517;</button>
            <a href="invoice_list.php" class="secondary-link compact">請求書一覧</a>
            <a href="invoice.php?month=<?= escape($prevMonth) ?>" class="secondary-link compact">&laquo; 前月</a>
            <a href="invoice.php?month=<?= escape(current_year_month()) ?>" class="secondary-link compact">今月</a>
            <a href="invoice.php?month=<?= escape($nextMonth) ?>" class="secondary-link compact">翌月 &raquo;</a>
        </div>
    </div>
</section>

<?php if ($flash): ?>
    <div class="card flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>">
        <?= escape($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<section class="card invoice-card">
    <?php if ($isSupplier && $confirmedDeliveries < $totalDeliveries): ?>
        <div class="alert alert-warning">
            確認待ちの納品が <?= $totalDeliveries - $confirmedDeliveries ?> 件あります。確認後に請求書を確定すると安心です。
        </div>
    <?php endif; ?>

    <?php if (!$hasInvoice): ?>
        <p class="info-text">
            <?php if ($isSupplier): ?>
                今月はまだ請求書を作成できる確定納品がありません。
            <?php else: ?>
                請求書は現在準備中です。<?= escape($roleLabelSupplier) ?>の作業完了をお待ちください。
            <?php endif; ?>
        </p>
    <?php else: ?>
        <div class="invoice-status-bar">
            <div>
                <div class="section-meta">ステータス</div>
                <div class="status-label">
                    <?= escape($statusLabels[$invoiceStatus] ?? strtoupper((string) $invoiceStatus)) ?>
                </div>
                <div class="meta">更新日時: <?= escape(date('Y-m-d H:i', strtotime($invoiceData['updated_at'] ?? 'now'))) ?></div>
                <?php if ($invoiceStatus === 'published'): ?>
                    <div class="meta" style="color: #a94442;">公開中の請求書は再計算されません。</div>
                <?php endif; ?>
            </div>
            <div class="actions align-right" style="gap: 0.5rem; flex-wrap: wrap;">
                <?php if ($canPrint): ?>
                    <button type="button" class="secondary-link compact" data-print-invoice>請求書を印刷</button>
                <?php endif; ?>
                <?php if ($canPublish && !empty($items)): ?>
                    <form method="post" action="invoice_action.php" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                        <input type="hidden" name="action" value="publish">
                        <input type="hidden" name="month" value="<?= escape($yearMonth) ?>">
                        <button type="submit" class="primary compact">受領先へ公開</button>
                    </form>
                <?php elseif ($canUnpublish): ?>
                    <form method="post" action="invoice_action.php" class="inline-form" onsubmit="return confirm('請求書を非公開に戻します。受領先は閲覧できなくなり、内容は最新の納品情報で再計算されます。よろしいですか？');">
                        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                        <input type="hidden" name="action" value="unpublish">
                        <input type="hidden" name="month" value="<?= escape($yearMonth) ?>">
                        <button type="submit" class="secondary compact danger">非公開に戻す</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$invoiceVisible): ?>
            <p class="info-text">請求書はまだ公開されていません。<?= escape($roleLabelSupplier) ?>の公開をお待ちください。</p>
        <?php elseif (empty($items)): ?>
            <p class="info-text">今月は請求対象の確定納品がありません。</p>
        <?php else: ?>
            <div class="invoice-print-area" id="invoice-print-area">
                <div class="invoice-print-header">
                    <div>
                        <h2>請求書</h2>
                        <div>請求書番号: <?= escape($invoiceId) ?></div>
                    </div>
                    <div class="invoice-print-meta">
                        <div><span class="label">請求日</span><?= escape($issuedAtLabel) ?></div>
                        <?php if ($dueInfo): ?>
                            <div><span class="label">振込期日</span><?= escape($dueInfo['display']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="invoice-subject">
                    件名: <?= escape($monthDate->format('Y年n月')) ?>分 ご請求
                </div>

                <div class="invoice-entities">
                    <div class="entity-box">
                        <div>
                            <?= escape($receiverInfo['company_name'] ?? '') ?>
                            <?php
                            $honorific = $receiverInfo['honorific'] ?? 'onchu';
                            if ($honorific === 'onchu') {
                                echo ' 御中';
                            } elseif ($honorific === 'sama') {
                                echo ' 様';
                            }
                            ?>
                        </div>
                        <?php if (!empty($receiverInfo['postal_code'])): ?>
                            <div>〒<?= escape($receiverInfo['postal_code']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($receiverInfo['address'])): ?>
                            <div><?= nl2br(escape($receiverInfo['address'])) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($receiverInfo['contact_name'])): ?>
                            <div>担当: <?= escape($receiverInfo['contact_name']) ?> 様</div>
                        <?php endif; ?>
                        <?php if (!empty($receiverInfo['phone'])): ?>
                            <div>TEL: <span style="unicode-bidi: bidi-override; direction: ltr;"><?= escape($receiverInfo['phone']) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($receiverInfo['email'])): ?>
                            <div>Email: <?= escape($receiverInfo['email']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="entity-box entity-box--right">
                        <div><?= escape($supplierInfo['company_name'] ?? '') ?></div>
                        <?php if (!empty($supplierInfo['postal_code'])): ?>
                            <div>〒<?= escape($supplierInfo['postal_code']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($supplierInfo['address'])): ?>
                            <div><?= nl2br(escape($supplierInfo['address'])) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($supplierInfo['contact_name'])): ?>
                            <div>担当: <?= escape($supplierInfo['contact_name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($supplierInfo['phone'])): ?>
                            <div>TEL: <span style="unicode-bidi: bidi-override; direction: ltr;"><?= escape($supplierInfo['phone']) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($supplierInfo['email'])): ?>
                            <div>Email: <?= escape($supplierInfo['email']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($supplierInfo['bank_name']) || !empty($supplierInfo['bank_account'])): ?>
                            <div class="bank-info">
                                <div>振込先</div>
                                <div><?= escape(trim(($supplierInfo['bank_name'] ?? '') . ' ' . ($supplierInfo['bank_branch'] ?? ''))) ?></div>
                                <div><span style="unicode-bidi: bidi-override; direction: ltr;"><?= escape(trim(($supplierInfo['bank_account_type'] ?? '') . ' ' . ($supplierInfo['bank_account'] ?? ''))) ?></span></div>
                                <?php if (!empty($supplierInfo['bank_account_name_kana'])): ?>
                                    <div><?= escape($supplierInfo['bank_account_name_kana']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="invoice-total-amount">
                    <span>請求金額</span>
                    <strong><?= format_currency($netTotal) ?> 円</strong>
                </div>

                <table class="table invoice-table">
                    <thead>
                    <tr>
                        <th>品目</th>
                        <th>数量</th>
                        <th>単位</th>
                        <th>単価 (円)</th>
                        <th>金額 (円)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0;
                            $isReturn = $qty < 0;
                            $displayQty = $isReturn ? abs($qty) : $qty;
                            $unitLabel = isset($item['unit']) ? (string) $item['unit'] : '';
                            $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;
                            $rowSubtotal = isset($item['subtotal']) ? (float) $item['subtotal'] : $unitPrice * $qty;
                        ?>
                        <tr>
                            <td><?= escape($item['name']) ?></td>
                            <td class="number-cell">
                                <?php if ($displayQty !== 0.0): ?>
                                    <?= $isReturn ? '-' : '' ?><?= escape(rtrim(rtrim(number_format($displayQty, 2, '.', ''), '0'), '.')) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= escape($unitLabel) ?></td>
                            <td class="number-cell"><?= format_currency($unitPrice) ?></td>
                            <td class="number-cell"><?= format_currency($rowSubtotal) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="invoice-summary-rows">
                    <div class="summary-row"><span>小計</span><strong><?= format_currency($grossTotal) ?> 円</strong></div>
                    <div class="summary-row"><span>手数料 (<?= escape($commissionPercentLabel) ?>%)</span><strong>-<?= format_currency($commissionAmount) ?> 円</strong></div>
                    <div class="summary-row summary-row--emphasis"><span>請求金額</span><strong><?= format_currency($netTotal) ?> 円</strong></div>
                </div>

                <?php if ($invoiceNotesCombined !== ''): ?>
                    <div class="invoice-notes">
                        <div class="notes-title">備考</div>
                        <div><?= nl2br(escape($invoiceNotesCombined)) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($canPublish): ?>
                    <div class="card" style="margin-top: 1.5rem;">
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_notes">
                            <div class="form-row">
                                <label>追加備考
                                    <textarea name="notes" rows="3"><?= escape($invoiceNotesLocal) ?></textarea>
                                </label>
                            </div>
                            <div class="actions" style="justify-content: flex-end;">
                                <button type="submit" class="secondary">保存</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const printButton = document.querySelector('[data-print-invoice]');
        if (printButton) {
            printButton.addEventListener('click', function () {
                window.print();
            });
        }

        const shareButton = document.querySelector('[data-share-page]');
        if (shareButton) {
            shareButton.addEventListener('click', function () {
                // 現在の月パラメータを取得
                const urlParams = new URLSearchParams(window.location.search);
                const month = urlParams.get('month') || '<?= escape($yearMonth) ?>';

                // 月パラメータを含むURLを生成
                const baseUrl = window.location.origin + window.location.pathname;
                const url = baseUrl + '?month=' + encodeURIComponent(month);
                const title = '<?= escape($monthDate->format('Y年n月')) ?>の請求書';

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
        }
    });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
