<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/json_storage.php';

function settings_file_path(): string
{
    return data_path('settings.json');
}

function default_settings(): array
{
    return [
        'supplier' => [
            'company_name' => '',
            'address' => '',
            'phone' => '',
            'contact_name' => '',
            'email' => '',
            'bank_name' => '',
            'bank_branch' => '',
            'bank_account' => '',
            'bank_account_type' => '',
            'bank_account_name_kana' => '',
            'postal_code' => '',
        ],
        'receiver' => [
            'company_name' => '',
            'address' => '',
            'phone' => '',
            'contact_name' => '',
            'email' => '',
            'postal_code' => '',
        ],
        'commission_rate' => default_commission_rate(),
        'invoice_notes' => '',
        'invoice_due_rule' => 'month_end',
        'invoice_due_day' => 31,
        'role_labels' => [
            'supplier' => '請求元',
            'receiver' => '請求先',
        ],
        'products' => [],
    ];
}

function load_settings(): array
{
    $raw = read_json_file(settings_file_path(), null);
    if (!is_array($raw)) {
        return default_settings();
    }

    $defaults = default_settings();
    $settings = array_replace_recursive($defaults, $raw);

    if (isset($settings['ftp'])) {
        unset($settings['ftp']);
    }

    $settings['commission_rate'] = clamp_rate($settings['commission_rate']);

    if (!is_array($settings['products'])) {
        $settings['products'] = [];
    }
    $settings['products'] = array_values(array_map('normalize_product', $settings['products']));

    $settings['invoice_due_rule'] = in_array($settings['invoice_due_rule'], ['month_end', 'next_month_end', 'day_of_month'], true)
        ? $settings['invoice_due_rule']
        : 'month_end';
    $settings['invoice_due_day'] = (int) $settings['invoice_due_day'];
    if ($settings['invoice_due_day'] < 1 || $settings['invoice_due_day'] > 31) {
        $settings['invoice_due_day'] = 31;
    }

    if (!isset($settings['role_labels']['supplier']) || trim((string) $settings['role_labels']['supplier']) === '') {
        $settings['role_labels']['supplier'] = '請求元';
    }
    if (!isset($settings['role_labels']['receiver']) || trim((string) $settings['role_labels']['receiver']) === '') {
        $settings['role_labels']['receiver'] = '請求先';
    }

    return $settings;
}

function normalize_product($product): array
{
    if (!is_array($product)) {
        return [
            'id' => generate_product_id(),
            'name' => '',
            'unit' => '',
            'unit_price' => 0.0,
        ];
    }

    $id = isset($product['id']) && is_string($product['id']) && $product['id'] !== ''
        ? $product['id']
        : generate_product_id();
    $name = trim((string) ($product['name'] ?? ''));
    $unit = trim((string) ($product['unit'] ?? ''));
    $unitPrice = parse_float($product['unit_price'] ?? 0);
    $defaultQuantity = (int) ($product['default_quantity'] ?? 0);
    $isReturn = !empty($product['is_return']);

    $normalized = [
        'id' => $id,
        'name' => $name,
        'unit' => $unit,
        'unit_price' => $unitPrice,
    ];

    if ($defaultQuantity > 0) {
        $normalized['default_quantity'] = $defaultQuantity;
    }
    if ($isReturn) {
        $normalized['is_return'] = $isReturn;
    }

    return $normalized;
}

function save_settings(array $settings): void
{
    $defaults = default_settings();
    $normalized = array_replace_recursive($defaults, $settings);

    $normalized['commission_rate'] = clamp_rate($normalized['commission_rate'] ?? default_commission_rate());

    $products = $normalized['products'] ?? [];
    if (!is_array($products)) {
        $products = [];
    }

    $unique = [];
    foreach ($products as $product) {
        $normalizedProduct = normalize_product($product);
        if ($normalizedProduct['name'] === '') {
            continue;
        }
        $unique[$normalizedProduct['id']] = $normalizedProduct;
    }
    $normalized['products'] = array_values($unique);

    $rule = $normalized['invoice_due_rule'] ?? 'month_end';
    $normalized['invoice_due_rule'] = in_array($rule, ['month_end', 'next_month_end', 'day_of_month'], true) ? $rule : 'month_end';

    $day = (int) ($normalized['invoice_due_day'] ?? 31);
    if ($day < 1 || $day > 31) {
        $day = 31;
    }
    $normalized['invoice_due_day'] = $day;

    $supplierLabel = trim((string) ($normalized['role_labels']['supplier'] ?? ''));
    $receiverLabel = trim((string) ($normalized['role_labels']['receiver'] ?? ''));
    $normalized['role_labels']['supplier'] = $supplierLabel !== '' ? $supplierLabel : '請求元';
    $normalized['role_labels']['receiver'] = $receiverLabel !== '' ? $receiverLabel : '請求先';

    if (isset($normalized['ftp'])) {
        unset($normalized['ftp']);
    }

    write_json_file(settings_file_path(), $normalized);
}

function get_commission_rate(): float
{
    $settings = load_settings();
    return $settings['commission_rate'];
}

function clamp_rate($rate): float
{
    $value = parse_float($rate);
    if ($value > 1) {
        $value = $value > 100 ? $value / 100 : $value;
    }
    return max(0.0, min(1.0, $value));
}

function generate_product_id(): string
{
    return 'prod-' . bin2hex(random_bytes(3));
}

function product_options(): array
{
    $settings = load_settings();
    return $settings['products'];
}

function role_label(string $role): string
{
    $settings = load_settings();
    $labels = $settings['role_labels'] ?? [];
    if ($role === 'receiver') {
        return $labels['receiver'] ?? '請求先';
    }
    return $labels['supplier'] ?? '請求元';
}
