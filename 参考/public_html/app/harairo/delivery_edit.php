<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_login();

if (!is_supplier()) {
    set_flash('error', '納品の登録・編集は' . role_label('supplier') . 'のみが行えます。');
    redirect('index.php');
}

$productOptions = product_options();
$productOptionsById = [];
foreach ($productOptions as $option) {
    if (!empty($option['id'])) {
        $productOptionsById[$option['id']] = $option;
    }
}

$monthParam = (string) request_get('month', current_year_month());
$yearMonth = normalize_year_month($monthParam);

$invoiceLockCache = [];
$isInvoiceLocked = static function (string $month) use (&$invoiceLockCache) {
    $key = normalize_year_month($month);
    if (!array_key_exists($key, $invoiceLockCache)) {
        $invoiceLockCache[$key] = load_invoice($key);
    }
    $invoice = $invoiceLockCache[$key];
    return is_array($invoice) && (($invoice['status'] ?? '') === 'published');
};

$invoiceLockedMessage = '請求書が公開中のため、この月の納品は登録・変更できません。非公開に戻してから操作してください。';

$deliveryId = request_get('id');
$currentUserId = current_user_id();

if (!$deliveryId && $isInvoiceLocked($yearMonth)) {
    set_flash('error', $invoiceLockedMessage);
    redirect('index.php?month=' . urlencode($yearMonth));
}

$existingDelivery = null;
$sourceMonth = $yearMonth;

if ($deliveryId) {
    $records = load_deliveries($yearMonth);
    $existingDelivery = find_delivery($records, $deliveryId);
    if (!$existingDelivery) {
        set_flash('error', '指定の納品データが見つかりません。');
        redirect('index.php?month=' . urlencode($yearMonth));
    }
    $sourceMonth = substr($existingDelivery['delivery_date'], 0, 7);

    if ($isInvoiceLocked($sourceMonth)) {
        set_flash('error', $invoiceLockedMessage);
        redirect('index.php?month=' . urlencode($sourceMonth));
    }
}

$errors = [];
$formData = [
    'delivery_date' => $existingDelivery['delivery_date'] ?? date('Y-m-d'),
    'notes' => $existingDelivery['notes'] ?? '',
    'items' => [],
];

if ($existingDelivery) {
    foreach ($existingDelivery['items'] as $item) {
        $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 0;
        $isReturn = $quantity < 0;
        $quantityAbs = abs((int) $quantity);

        $unitPrice = isset($item['unit_price']) ? (string) $item['unit_price'] : '';
        if ($unitPrice !== '' && is_numeric($unitPrice)) {
            $unitPrice = (string) (int) $unitPrice;
        }
        $formData['items'][] = [
            'name' => (string) ($item['name'] ?? ''),
            'quantity' => $quantityAbs,
            'unit' => (string) ($item['unit'] ?? ''),
            'unit_price' => $unitPrice,
            'is_return' => $isReturn,
        ];
    }
}

if (empty($formData['items'])) {
    $formData['items'][] = ['name' => '', 'quantity' => '', 'unit' => '', 'unit_price' => '', 'is_return' => false];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetMonth = null;
    if (!verify_csrf_token(request_post('csrf_token'))) {
        $errors[] = '不正なリクエストです。ページを再読み込みしてやり直してください。';
    }

    $formData['delivery_date'] = trim((string) request_post('delivery_date', $formData['delivery_date']));
    $formData['notes'] = trim((string) request_post('notes', ''));
    $itemsInput = $_POST['items'] ?? [];
    $parsedItems = [];

    if (is_array($itemsInput)) {
        foreach ($itemsInput as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            $quantityRaw = trim((string) ($item['quantity'] ?? ''));
            $unit = trim((string) ($item['unit'] ?? ''));
            $unitPriceRaw = trim((string) ($item['unit_price'] ?? ''));
            $isReturn = !empty($item['is_return']);

            if ($name === '' && $quantityRaw === '' && $unit === '' && $unitPriceRaw === '') {
                continue;
            }
            if ($name === '') {
                $errors[] = '品目名を入力してください。';
            }
            $quantityIsNumber = $quantityRaw !== '' && is_numeric(str_replace([',', ' '], '', $quantityRaw));
            if (!$quantityIsNumber) {
                $errors[] = '数量は数値で入力してください。';
            }
            $unitPriceIsNumber = $unitPriceRaw === '' || is_numeric(str_replace([',', ' '], '', $unitPriceRaw));
            if ($unitPriceRaw === '' || !$unitPriceIsNumber) {
                $errors[] = '単価は数値で入力してください。';
            }

            $parsedItems[] = [
                'name' => $name,
                'quantity_raw' => $quantityRaw,
                'unit' => $unit,
                'unit_price_raw' => $unitPriceRaw,
                'is_return' => $isReturn,
            ];
        }
    }

    if (empty($parsedItems)) {
        $errors[] = '明細は1件以上入力してください。';
    }

    if ($formData['delivery_date'] === '' || !DateTime::createFromFormat('Y-m-d', $formData['delivery_date'])) {
        $errors[] = '納品日を正しく入力してください（例: 2024-02-15）。';
    }

    $formData['items'] = [];
    foreach ($parsedItems as $item) {
        $formData['items'][] = [
            'name' => $item['name'],
            'quantity' => $item['quantity_raw'],
            'unit' => $item['unit'],
            'unit_price' => $item['unit_price_raw'],
            'is_return' => $item['is_return'],
        ];
    }

    if (empty($errors)) {
        $timestamp = date('c');
        $targetMonth = substr($formData['delivery_date'], 0, 7);
        $itemsToStore = [];
        $grossTotal = 0.0;
        foreach ($parsedItems as $item) {
            $qty = parse_float($item['quantity_raw']);
            if ($item['is_return']) {
                $qty = -$qty;
            }
            $price = parse_float($item['unit_price_raw']);
            $subtotal = $qty * $price;
            $grossTotal += $subtotal;
            $itemsToStore[] = [
                'name' => $item['name'],
                'quantity' => $qty,
                'unit' => $item['unit'],
                'unit_price' => $price,
                'subtotal' => $subtotal,
            ];
        }

        if ($deliveryId && $existingDelivery) {
            $updated = $existingDelivery;
            $updated['delivery_date'] = $formData['delivery_date'];
            $updated['notes'] = $formData['notes'];
            $updated['items'] = $itemsToStore;
            $updated['gross_total'] = $grossTotal;
            $updated['updated_at'] = $timestamp;
            $updated['last_modified_by'] = $currentUserId;
            $updated['status'] = 'unconfirmed';
            $updated['confirmed_by'] = null;
            $updated['confirmed_at'] = null;

            $found = false;
            update_deliveries($sourceMonth, function (array $records) use ($deliveryId, $targetMonth, $sourceMonth, &$found, $updated) {
                $next = [];
                foreach ($records as $record) {
                    if (($record['id'] ?? null) === $deliveryId) {
                        $found = true;
                        if ($targetMonth === $sourceMonth) {
                            $next[] = $updated;
                        }
                        continue;
                    }
                    $next[] = $record;
                }
                return $next;
            });

            if (!$found) {
                set_flash('error', '更新対象の納品が見つかりませんでした。');
                redirect('index.php?month=' . urlencode($yearMonth));
            }

            if ($targetMonth !== $sourceMonth) {
                update_deliveries($targetMonth, function (array $records) use ($updated) {
                    $records[] = $updated;
                    return $records;
                });
            }

            set_flash('success', '納品を更新しました。');
            redirect('index.php?month=' . urlencode($targetMonth));
        }

        $newDelivery = [
            'id' => generate_delivery_id(),
            'delivery_date' => $formData['delivery_date'],
            'notes' => $formData['notes'],
            'items' => $itemsToStore,
            'gross_total' => $grossTotal,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'created_by' => $currentUserId,
            'last_modified_by' => $currentUserId,
            'status' => 'unconfirmed',
            'confirmed_by' => null,
            'confirmed_at' => null,
        ];

        update_deliveries($targetMonth, function (array $records) use ($newDelivery) {
            $records[] = $newDelivery;
            return $records;
        });

        set_flash('success', '納品を登録しました。');
        redirect('index.php?month=' . urlencode($targetMonth));
    }
}

$productsJson = htmlspecialchars(json_encode($productOptions, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

$pageTitle = $deliveryId ? '納品の編集' : '納品の登録';
include __DIR__ . '/partials/header.php';
?>

<div class="card">
    <h1>
        <?= $deliveryId ? '納品の編集' : '納品の登録' ?>
    </h1>

    <?php if ($errors): ?>
        <div class="flash flash-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="form-row">
            <label>
                納品日
                <input type="date" name="delivery_date" value="<?= escape($formData['delivery_date']) ?>" required>
            </label>
            <label>
                メモ
                <textarea name="notes" rows="2" placeholder="先方へのメモなど"><?= escape($formData['notes']) ?></textarea>
            </label>
        </div>

        <div class="items-table" data-items-container>
            <input type="hidden" id="product-catalog" value='<?= $productsJson ?>'>
            <table class="table" id="items-table">
                <thead>
                <tr>
                    <th>品目</th>
                    <th>数量</th>
                    <th>単位</th>
                    <th>単価 (円)</th>
                    <th>返品</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
<?php foreach ($formData["items"] as $index => $item): ?>
    <?php
        $selectedName = isset($item["name"]) ? $item["name"] : "";
        $selectedUnit = isset($item["unit"]) ? $item["unit"] : "";
        $selectedPrice = isset($item["unit_price"]) ? $item["unit_price"] : "";
    ?>
    <tr>
        <td data-label="品目">
            <input type="text" name="items[<?= $index ?>][name]" value="<?= escape($selectedName) ?>" placeholder="品目名を入力" required list="product-list-<?= $index ?>">
            <datalist id="product-list-<?= $index ?>">
                <?php foreach ($productOptions as $product): ?>
                    <option value="<?= escape($product["name"]) ?>">
                <?php endforeach; ?>
            </datalist>
        </td>
        <td data-label="数量">
            <select name="items[<?= $index ?>][quantity]" required>
                <option value="">選択</option>
                <?php for ($q = 0; $q <= 200; $q++): ?>
                    <option value="<?= $q ?>" <?= (isset($item["quantity"]) && (int)$item["quantity"] === $q) ? "selected" : "" ?>><?= $q ?></option>
                <?php endfor; ?>
            </select>
        </td>
        <td data-label="単位"><input type="text" name="items[<?= $index ?>][unit]" value="<?= escape(isset($item["unit"]) ? $item["unit"] : "") ?>"></td>
        <td data-label="単価"><input type="number" step="1" name="items[<?= $index ?>][unit_price]" value="<?= escape(isset($item["unit_price"]) ? $item["unit_price"] : "") ?>" required></td>
        <td data-label="返品">
            <input type="checkbox" name="items[<?= $index ?>][is_return]" value="1" <?= !empty($item["is_return"]) ? "checked" : "" ?>>
        </td>
        <td data-label="操作"><button type="button" class="link-button danger" data-remove-row>&times;</button></td>
    </tr>
<?php endforeach; ?>
</tbody>
            </table>
            <button type="button" class="secondary" data-add-item>＋ 明細を追加</button>
        </div>

        <div class="actions align-right">
            <a href="index.php?month=<?= escape($yearMonth) ?>" class="secondary-link">一覧に戻る</a>
            <button type="submit" class="primary">保存</button>
        </div>
    </form>
</div>

<template id="item-row-template">
    <tr>
        <td data-label="品目">
            <input type="text" name="__NAME__[name]" placeholder="品目名を入力" required list="product-list-new">
            <datalist id="product-list-new">
                <?php foreach ($productOptions as $product): ?>
                    <option value="<?= escape($product['name']) ?>">
                <?php endforeach; ?>
            </datalist>
        </td>
        <td data-label="数量">
            <select name="__NAME__[quantity]" required>
                <option value="">選択</option>
                <?php for ($q = 0; $q <= 200; $q++): ?>
                    <option value="<?= $q ?>"><?= $q ?></option>
                <?php endfor; ?>
            </select>
        </td>
        <td data-label="単位"><input type="text" name="__NAME__[unit]"></td>
        <td data-label="単価"><input type="number" step="1" name="__NAME__[unit_price]" required></td>
        <td data-label="返品">
            <input type="checkbox" name="__NAME__[is_return]" value="1">
        </td>
        <td data-label="操作"><button type="button" class="link-button danger" data-remove-row>&times;</button></td>
    </tr>
</template>

<?php include __DIR__ . '/partials/footer.php'; ?>













