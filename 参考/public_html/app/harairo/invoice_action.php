<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('invoice.php');
}

$token = request_post('csrf_token');
if (!verify_csrf_token($token)) {
    set_flash('error', '不正なリクエストです。');
    redirect('invoice.php');
}

$action = (string) request_post('action', '');
$monthParam = (string) request_post('month', current_year_month());
$yearMonth = normalize_year_month($monthParam);
$currentUserId = current_user_id();

switch ($action) {
    case 'publish':
        if (!is_supplier()) {
            set_flash('error', '公開できるのは' . role_label('supplier') . 'のみです。');
            break;
        }
        $invoice = load_invoice($yearMonth);
        if (!$invoice) {
            set_flash('error', '先に請求書を作成してください。');
            break;
        }
        if (($invoice['status'] ?? 'draft') !== 'draft') {
            set_flash('error', 'すでに公開済みの請求書です。');
            break;
        }
        if (empty($invoice['items'])) {
            set_flash('error', '確定した納品がないため公開できません。');
            break;
        }
        $invoice['status'] = 'published';
        $invoice['published_by'] = $currentUserId;
        $invoice['published_at'] = date('c');
        $invoice['updated_at'] = $invoice['published_at'];
        save_invoice($yearMonth, $invoice);
        set_flash('success', '請求書を受領先へ公開しました。内容は固定されます。');
        break;

    case 'unpublish':
        if (!is_supplier()) {
            set_flash('error', '非公開に戻せるのは' . role_label('supplier') . 'のみです。');
            break;
        }
        $invoice = load_invoice($yearMonth);
        if (!$invoice) {
            set_flash('error', '対象の請求書が見つかりません。');
            break;
        }
        if (($invoice['status'] ?? '') !== 'published') {
            set_flash('error', '公開中の請求書ではありません。');
            break;
        }
        $invoice['status'] = 'draft';
        unset($invoice['published_by'], $invoice['published_at']);
        $invoice['updated_at'] = date('c');
        save_invoice($yearMonth, $invoice);
        set_flash('success', '請求書を非公開に戻しました。最新の納品情報で再計算されます。');
        break;

    case 'confirm_payment':
        if (!is_supplier()) {
            set_flash('error', '入金確認できるのは' . role_label('supplier') . 'のみです。');
            break;
        }
        $invoice = load_invoice($yearMonth);
        if (!$invoice) {
            set_flash('error', '対象の請求書が見つかりません。');
            break;
        }
        if (($invoice['status'] ?? '') !== 'published') {
            set_flash('error', '公開中の請求書のみ入金確認できます。');
            break;
        }
        if (!empty($invoice['payment_confirmed'])) {
            set_flash('error', 'すでに入金確認済みです。');
            break;
        }
        $invoice['payment_confirmed'] = true;
        $invoice['payment_confirmed_by'] = $currentUserId;
        $invoice['payment_confirmed_at'] = date('c');
        $invoice['updated_at'] = date('c');
        save_invoice($yearMonth, $invoice);
        set_flash('success', '入金を確認しました。');
        break;

    default:
        set_flash('error', '不正な操作です。');
        break;
}

// 入金確認の場合は一覧画面へ、それ以外は請求書詳細へ
if ($action === 'confirm_payment') {
    redirect('invoice_list.php');
} else {
    redirect('invoice.php?month=' . urlencode($yearMonth));
}
