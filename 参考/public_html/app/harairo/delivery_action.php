<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$token = request_post('csrf_token');
if (!verify_csrf_token($token)) {
    set_flash('error', '不正なリクエストです。');
    redirect('index.php');
}

$action = (string) request_post('action', '');
$deliveryId = (string) request_post('delivery_id', '');
$monthParam = (string) request_post('month', current_year_month());
$yearMonth = normalize_year_month($monthParam);
$currentUserId = current_user_id();

if ($deliveryId === '') {
    set_flash('error', '対象の納品IDが指定されていません。');
    redirect('index.php?month=' . urlencode($yearMonth));
}

switch ($action) {
    case 'confirm':
        if (!is_receiver()) {
            set_flash('error', '確認操作は' . role_label('receiver') . 'のみ行えます。');
            break;
        }
        $updated = false;
        $message = '対象の納品が見つかりません。';
        update_deliveries($yearMonth, function (array $records) use ($deliveryId, $currentUserId, &$updated, &$message) {
            foreach ($records as &$record) {
                if (($record['id'] ?? null) !== $deliveryId) {
                    continue;
                }
                if (($record['status'] ?? 'unconfirmed') === 'confirmed') {
                    $message = 'すでに確認済みの納品です。';
                    return $records;
                }
                $record['status'] = 'confirmed';
                $record['confirmed_by'] = $currentUserId;
                $record['confirmed_at'] = date('c');
                $updated = true;
                return $records;
            }
            return $records;
        });
        if ($updated) {
            set_flash('success', '納品を確認済みにしました。');
        } else {
            set_flash('error', $message);
        }
        break;

    case 'delete':
        if (!is_supplier()) {
            set_flash('error', '削除操作は' . role_label('supplier') . 'のみ行えます。');
            break;
        }
        $deleted = false;
        update_deliveries($yearMonth, function (array $records) use ($deliveryId, &$deleted) {
            $filtered = [];
            foreach ($records as $record) {
                if (($record['id'] ?? null) === $deliveryId) {
                    $deleted = true;
                    continue;
                }
                $filtered[] = $record;
            }
            return $filtered;
        });
        if ($deleted) {
            set_flash('success', '納品を削除しました。');
        } else {
            set_flash('error', '削除対象の納品が見つかりませんでした。');
        }
        break;

    default:
        set_flash('error', '不明な操作です。');
        break;
}

redirect('index.php?month=' . urlencode($yearMonth));
