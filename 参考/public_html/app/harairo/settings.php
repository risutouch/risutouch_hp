
<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_login();

if (!is_supplier()) {
    set_flash('error', '設定画面へのアクセス権限がありません。');
    redirect('index.php');
}

$settings = load_settings();
$users = load_users();
$flash = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(request_post('csrf_token'))) {
        set_flash('error', '不正なリクエストです。');
        redirect('settings.php');
    }

    $supplier = [
        'company_name' => trim((string) request_post('supplier_company_name', '')),
        'address' => trim((string) request_post('supplier_address', '')),
        'phone' => trim((string) request_post('supplier_phone', '')),
        'contact_name' => trim((string) request_post('supplier_contact_name', '')),
        'email' => trim((string) request_post('supplier_email', '')),
        'bank_name' => trim((string) request_post('supplier_bank_name', '')),
        'bank_branch' => trim((string) request_post('supplier_bank_branch', '')),
        'bank_account' => trim((string) request_post('supplier_bank_account', '')),
        'bank_account_type' => trim((string) request_post('supplier_bank_account_type', '')),
        'bank_account_name_kana' => trim((string) request_post('supplier_bank_account_name_kana', '')),
        'postal_code' => trim((string) request_post('supplier_postal_code', '')),
    ];

    $receiver = [
        'company_name' => trim((string) request_post('receiver_company_name', '')),
        'address' => trim((string) request_post('receiver_address', '')),
        'phone' => trim((string) request_post('receiver_phone', '')),
        'contact_name' => trim((string) request_post('receiver_contact_name', '')),
        'email' => trim((string) request_post('receiver_email', '')),
        'postal_code' => trim((string) request_post('receiver_postal_code', '')),
        'honorific' => trim((string) request_post('receiver_honorific', $settings['receiver']['honorific'] ?? 'onchu')),
    ];

    $supplierLabel = trim((string) request_post('label_supplier', $settings['role_labels']['supplier'] ?? '請求元'));
    $receiverLabel = trim((string) request_post('label_receiver', $settings['role_labels']['receiver'] ?? '請求先'));

    $commissionPercent = parse_float(request_post('commission_rate', ($settings['commission_rate'] ?? get_commission_rate()) * 100));
    $invoiceNotes = trim((string) request_post('invoice_notes', ''));

    $invoiceDueRule = (string) request_post('invoice_due_rule', $settings['invoice_due_rule'] ?? 'month_end');
    $invoiceDueDay = (int) request_post('invoice_due_day', $settings['invoice_due_day'] ?? 31);

    $productsInput = $_POST['products'] ?? [];
    $products = [];

    if (is_array($productsInput)) {
        foreach ($productsInput as $productRow) {
            if (!is_array($productRow)) {
                continue;
            }
            $name = trim((string) ($productRow['name'] ?? ''));
            $unit = trim((string) ($productRow['unit'] ?? ''));
            $price = parse_float($productRow['unit_price'] ?? 0);
            $defaultQuantity = (int) ($productRow['default_quantity'] ?? 0);
            $isReturn = !empty($productRow['is_return']);
            $id = trim((string) ($productRow['id'] ?? ''));
            if ($name === '') {
                continue;
            }
            if ($id === '') {
                $id = generate_product_id();
            }
            $products[] = [
                'id' => $id,
                'name' => $name,
                'unit' => $unit,
                'unit_price' => $price,
                'default_quantity' => $defaultQuantity,
                'is_return' => $isReturn,
            ];
        }
    }

    $settings['supplier'] = $supplier;
    $settings['receiver'] = $receiver;
    $settings['commission_rate'] = $commissionPercent / 100;
    $settings['invoice_notes'] = $invoiceNotes;
    $settings['invoice_due_rule'] = in_array($invoiceDueRule, ['month_end', 'next_month_end', 'day_of_month'], true) ? $invoiceDueRule : 'month_end';
    $settings['invoice_due_day'] = ($invoiceDueDay >= 1 && $invoiceDueDay <= 31) ? $invoiceDueDay : 31;
    $settings['role_labels'] = [
        'supplier' => $supplierLabel !== '' ? $supplierLabel : '請求元',
        'receiver' => $receiverLabel !== '' ? $receiverLabel : '請求先',
    ];
    $settings['products'] = $products;

    // デバッグ: 保存前の$productsを確認
    file_put_contents(__DIR__ . '/../data/debug_products.txt', print_r($products, true));

    // ユーザー情報更新
    $users = load_users();
    $usersUpdated = false;

    // 請求元ユーザー
    $supplierLogin = trim((string) request_post('supplier_user_login', ''));
    $supplierPassword = trim((string) request_post('supplier_user_password', ''));
    if ($supplierLogin !== '' && isset($users['supplier'])) {
        $users['supplier']['login'] = $supplierLogin;
        $usersUpdated = true;
    }
    if ($supplierPassword !== '') {
        $users['supplier']['password_hash'] = password_hash($supplierPassword, PASSWORD_DEFAULT);
        $usersUpdated = true;
    }

    // 請求先ユーザー
    $receiverLogin = trim((string) request_post('receiver_user_login', ''));
    $receiverPassword = trim((string) request_post('receiver_user_password', ''));
    if ($receiverLogin !== '' && isset($users['receiver'])) {
        $users['receiver']['login'] = $receiverLogin;
        $usersUpdated = true;
    }
    if ($receiverPassword !== '') {
        $users['receiver']['password_hash'] = password_hash($receiverPassword, PASSWORD_DEFAULT);
        $usersUpdated = true;
    }

    if ($usersUpdated) {
        save_users($users);
    }

    save_settings($settings);
    set_flash('success', '設定を保存しました。');
    redirect('settings.php');
}

$pageTitle = '設定';
include __DIR__ . '/partials/header.php';
?>

<section class="card">
    <h1 style="margin-top: 0; font-size: 1.5rem;">基本設定</h1>

    <?php if ($flash): ?>
        <div style="background: <?= $flash['type'] === 'success' ? '#e6f4d6' : '#ffe3e3' ?>; color: <?= $flash['type'] === 'success' ? '#2f6b05' : '#a40000' ?>; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?= escape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">

        <fieldset>
            <legend>表示ラベル</legend>
            <div class="form-row">
                <label>請求元のラベル
                    <input type="text" name="label_supplier" value="<?= escape($settings['role_labels']['supplier'] ?? '請求元') ?>">
                </label>
                <label>請求先のラベル
                    <input type="text" name="label_receiver" value="<?= escape($settings['role_labels']['receiver'] ?? '請求先') ?>">
                </label>
            </div>
        </fieldset>

        <fieldset>
            <legend><?= escape($settings['role_labels']['supplier'] ?? '請求元') ?>の情報</legend>
            <div class="form-row">
                <label>会社名
                    <input type="text" name="supplier_company_name" value="<?= escape($settings['supplier']['company_name'] ?? '') ?>">
                </label>
                <label>担当者名
                    <input type="text" name="supplier_contact_name" value="<?= escape($settings['supplier']['contact_name'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>郵便番号
                    <input type="text" name="supplier_postal_code" value="<?= escape($settings['supplier']['postal_code'] ?? '') ?>">
                </label>
                <label>電話番号
                    <input type="text" name="supplier_phone" value="<?= escape($settings['supplier']['phone'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>住所
                    <input type="text" name="supplier_address" value="<?= escape($settings['supplier']['address'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>メールアドレス
                    <input type="email" name="supplier_email" value="<?= escape($settings['supplier']['email'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>金融機関名
                    <input type="text" name="supplier_bank_name" value="<?= escape($settings['supplier']['bank_name'] ?? '') ?>">
                </label>
                <label>支店名
                    <input type="text" name="supplier_bank_branch" value="<?= escape($settings['supplier']['bank_branch'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>口座種別
                    <input type="text" name="supplier_bank_account_type" value="<?= escape($settings['supplier']['bank_account_type'] ?? '') ?>">
                </label>
                <label>口座番号
                    <input type="text" name="supplier_bank_account" value="<?= escape($settings['supplier']['bank_account'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>口座名義（カナ）
                    <input type="text" name="supplier_bank_account_name_kana" value="<?= escape($settings['supplier']['bank_account_name_kana'] ?? '') ?>">
                </label>
            </div>
        </fieldset>

        <fieldset>
            <legend><?= escape($settings['role_labels']['receiver'] ?? '請求先') ?>の情報</legend>
            <div class="form-row">
                <label>会社名
                    <input type="text" name="receiver_company_name" value="<?= escape($settings['receiver']['company_name'] ?? '') ?>">
                </label>
                <label>担当者名
                    <input type="text" name="receiver_contact_name" value="<?= escape($settings['receiver']['contact_name'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>郵便番号
                    <input type="text" name="receiver_postal_code" value="<?= escape($settings['receiver']['postal_code'] ?? '') ?>">
                </label>
                <label>電話番号
                    <input type="text" name="receiver_phone" value="<?= escape($settings['receiver']['phone'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>住所
                    <input type="text" name="receiver_address" value="<?= escape($settings['receiver']['address'] ?? '') ?>">
                </label>
            </div>
            <div class="form-row">
                <label>メールアドレス
                    <input type="email" name="receiver_email" value="<?= escape($settings['receiver']['email'] ?? '') ?>">
                </label>
                <label>敬称
                    <select name="receiver_honorific">
                        <?php $honorific = $settings['receiver']['honorific'] ?? 'onchu'; ?>
                        <option value="onchu" <?= $honorific === 'onchu' ? 'selected' : '' ?>>御中</option>
                        <option value="sama" <?= $honorific === 'sama' ? 'selected' : '' ?>>様</option>
                        <option value="none" <?= $honorific === 'none' ? 'selected' : '' ?>>なし</option>
                    </select>
                </label>
            </div>
        </fieldset>

        <fieldset>
            <legend>品目マスタ</legend>
            <div class="table-responsive" data-products-container>
                <table class="table" id="products-table">
                    <thead>
                    <tr>
                        <th style="width: 30%;">品目名</th>
                        <th style="width: 15%;">単位</th>
                        <th style="width: 15%;">単価 (円)</th>
                        <th style="width: 15%;">数量</th>
                        <th style="width: 10%;">返品</th>
                        <th style="width: 15%;">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($settings['products'] as $index => $product): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="products[<?= $index ?>][id]" value="<?= escape($product['id']) ?>">
                                <input type="text" name="products[<?= $index ?>][name]" value="<?= escape($product['name']) ?>" required>
                            </td>
                            <td><input type="text" name="products[<?= $index ?>][unit]" value="<?= escape($product['unit']) ?>"></td>
                            <td><input type="number" step="1" name="products[<?= $index ?>][unit_price]" value="<?= escape((int)($product['unit_price'] ?? 0)) ?>"></td>
                            <td>
                                <select name="products[<?= $index ?>][default_quantity]">
                                    <option value="0">-</option>
                                    <?php for ($q = 1; $q <= 200; $q++): ?>
                                        <option value="<?= $q ?>" <?= (isset($product['default_quantity']) && (int)$product['default_quantity'] === $q) ? 'selected' : '' ?>><?= $q ?></option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="products[<?= $index ?>][is_return]" value="1" <?= !empty($product['is_return']) ? 'checked' : '' ?>>
                            </td>
                            <td style="text-align: center;"><button type="button" class="link-button danger" data-remove-product>&times;</button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($settings['products'])): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="products[0][id]" value="">
                                <input type="text" name="products[0][name]" value="" required>
                            </td>
                            <td><input type="text" name="products[0][unit]" value=""></td>
                            <td><input type="number" step="1" name="products[0][unit_price]" value=""></td>
                            <td>
                                <select name="products[0][default_quantity]">
                                    <option value="0">-</option>
                                    <?php for ($q = 1; $q <= 200; $q++): ?>
                                        <option value="<?= $q ?>"><?= $q ?></option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="products[0][is_return]" value="1">
                            </td>
                            <td style="text-align: center;"><button type="button" class="link-button danger" data-remove-product>&times;</button></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="secondary" data-add-product>+ 品目を追加</button>
            </div>
        </fieldset>

        <fieldset>
            <legend>請求書の設定</legend>
            <div class="form-row">
                <label>手数料率 (%)
                    <input type="number" step="0.1" name="commission_rate" value="<?= escape(number_format(($settings['commission_rate'] ?? get_commission_rate()) * 100, 1, '.', '')) ?>">
                </label>
            </div>
            <div class="form-row">
                <label>共通備考
                    <textarea name="invoice_notes" rows="3"><?= escape($settings['invoice_notes'] ?? '') ?></textarea>
                </label>
            </div>
            <div class="form-row">
                <label>振込期日のルール
                    <select name="invoice_due_rule">
                        <?php $rule = $settings['invoice_due_rule'] ?? 'month_end'; ?>
                        <option value="month_end" <?= $rule === 'month_end' ? 'selected' : '' ?>>請求月の月末</option>
                        <option value="next_month_end" <?= $rule === 'next_month_end' ? 'selected' : '' ?>>翌月末</option>
                        <option value="day_of_month" <?= $rule === 'day_of_month' ? 'selected' : '' ?>>請求月の指定日</option>
                    </select>
                </label>
                <label>指定日 (日付)
                    <input type="number" min="1" max="31" name="invoice_due_day" value="<?= escape((string) ($settings['invoice_due_day'] ?? 31)) ?>">
                </label>
            </div>
        </fieldset>

        <fieldset>
            <legend>ユーザーアカウント設定</legend>
            <div style="margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">請求元ユーザー</h3>
                <div class="form-row">
                    <label>ログインID
                        <input type="text" name="supplier_user_login" value="<?= escape($users['supplier']['login'] ?? '') ?>" placeholder="現在: <?= escape($users['supplier']['login'] ?? 'supplier') ?>">
                    </label>
                    <label>パスワード
                        <input type="password" name="supplier_user_password" placeholder="変更する場合のみ入力">
                    </label>
                </div>
            </div>
            <div>
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1rem;">請求先ユーザー</h3>
                <div class="form-row">
                    <label>ログインID
                        <input type="text" name="receiver_user_login" value="<?= escape($users['receiver']['login'] ?? '') ?>" placeholder="現在: <?= escape($users['receiver']['login'] ?? 'receiver') ?>">
                    </label>
                    <label>パスワード
                        <input type="password" name="receiver_user_password" placeholder="変更する場合のみ入力">
                    </label>
                </div>
            </div>
        </fieldset>

        <div class="actions" style="justify-content: flex-end;">
            <button type="submit" class="primary">保存</button>
        </div>
    </form>
</section>

<template id="product-row-template">
    <tr>
        <td>
            <input type="hidden" name="__NAME__[id]" value="">
            <input type="text" name="__NAME__[name]" required>
        </td>
        <td><input type="text" name="__NAME__[unit]"></td>
        <td><input type="number" step="1" name="__NAME__[unit_price]"></td>
        <td>
            <select name="__NAME__[default_quantity]">
                <option value="0">-</option>
                <?php for ($q = 1; $q <= 200; $q++): ?>
                    <option value="<?= $q ?>"><?= $q ?></option>
                <?php endfor; ?>
            </select>
        </td>
        <td style="text-align: center;">
            <input type="checkbox" name="__NAME__[is_return]" value="1">
        </td>
        <td style="text-align: center;"><button type="button" class="link-button danger" data-remove-product>&times;</button></td>
    </tr>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('products-table');
        const addBtn = document.querySelector('[data-add-product]');
        const templateEl = document.getElementById('product-row-template');

        if (!table || !addBtn || !templateEl) {
            return;
        }

        const tbody = table.querySelector('tbody');
        const templateHtml = templateEl.innerHTML.trim();

        function nextIndex() {
            return tbody.querySelectorAll('tr').length;
        }

        addBtn.addEventListener('click', function () {
            const index = nextIndex();
            const html = templateHtml.replace(/__NAME__/g, `products[${index}]`);
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = html;
            tbody.appendChild(wrapper.firstElementChild);
        });

        tbody.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            if (target.matches('[data-remove-product]')) {
                const row = target.closest('tr');
                if (row && tbody.children.length > 1) {
                    row.remove();
                } else if (row) {
                    row.querySelectorAll('input').forEach(function (input) {
                        input.value = '';
                    });
                }
            }
        });
    });
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
