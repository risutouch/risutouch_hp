<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_login();

$monthParam = (string) request_get('month', current_year_month());
$yearMonth = normalize_year_month($monthParam);
$settings = load_settings();
$invoice = load_invoice($yearMonth);
$isSupplier = is_supplier();

$invoiceData = is_array($invoice) ? $invoice : [];
$invoiceStatus = $invoiceData['status'] ?? null;
$invoiceVisible = !empty($invoiceData) && ($invoiceStatus === 'published' || $isSupplier);

if (!$invoiceVisible) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>アクセス拒否</title></head><body><p>この請求書は閲覧できません。</p></body></html>';
    exit;
}

$items = is_array($invoiceData['items'] ?? null) ? $invoiceData['items'] : [];
if (empty($items)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>請求書なし</title></head><body><p>請求書が見つかりません。</p></body></html>';
    exit;
}

$monthDate = DateTime::createFromFormat('Y-m-d', $yearMonth . '-01') ?: new DateTime('first day of this month');
$supplierInfo = $settings['supplier'] ?? [];
$receiverInfo = $settings['receiver'] ?? [];

$invoiceNotesGlobal = trim((string) ($settings['invoice_notes'] ?? ''));
$invoiceNotesLocal = trim((string) ($invoiceData['notes'] ?? ''));
$invoiceNotesCombined = trim(implode("\n", array_filter([$invoiceNotesGlobal, $invoiceNotesLocal], static fn ($v) => $v !== '')));

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
$dueInfo = compute_invoice_due_info($yearMonth, $invoiceData['due_date'] ?? null, $settings);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>請求書 - <?= escape($monthDate->format('Y年n月')) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: #ffffff;
            padding: 20px;
        }
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
        }
        .print-actions {
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px dashed #d9deea;
        }
        @media print {
            .print-actions {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="print-actions">
            <button type="button" class="primary" onclick="window.print()">印刷する</button>
            <button type="button" class="secondary" onclick="window.close()" style="margin-left: 0.5rem;">閉じる</button>
        </div>

        <div class="invoice-print-area">
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
        </div>
    </div>

    <script>
        // ページ読み込み後、すぐに印刷ダイアログを表示するオプション（コメントアウト）
        // window.addEventListener('load', function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // });
    </script>
</body>
</html>
