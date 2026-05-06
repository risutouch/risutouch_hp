<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/json_storage.php';
require_once __DIR__ . '/deliveries.php';

function invoice_file_path(string $yearMonth): string
{
    $normalized = normalize_year_month($yearMonth);
    return data_path('invoice-' . $normalized . '.json');
}

function load_invoice(string $yearMonth): ?array
{
    $data = read_json_file(invoice_file_path($yearMonth), null);
    return is_array($data) ? $data : null;
}

function save_invoice(string $yearMonth, array $invoice): void
{
    write_json_file(invoice_file_path($yearMonth), $invoice);
}

function build_invoice(string $yearMonth, array $deliveries, float $commissionRate, array $existing = []): array
{
    $groups = [];
    $deliveryIds = [];
    $grossTotal = 0.0;

    foreach ($deliveries as $delivery) {
        if (($delivery['status'] ?? 'unconfirmed') !== 'confirmed') {
            continue;
        }
        $deliveryIds[] = $delivery['id'];
        foreach ($delivery['items'] ?? [] as $item) {
            $name = (string) ($item['name'] ?? '');
            $unit = (string) ($item['unit'] ?? '');
            $price = isset($item['unit_price']) ? (float) $item['unit_price'] : 0.0;
            $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 0.0;
            if ($name === '' || $quantity === 0.0) {
                continue;
            }
            $key = md5(strtolower($name) . '|' . $unit . '|' . $price);
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'name' => $name,
                    'unit' => $unit,
                    'unit_price' => $price,
                    'quantity' => 0.0,
                    'subtotal' => 0.0,
                ];
            }
            $groups[$key]['quantity'] += $quantity;
            $lineTotal = $quantity * $price;
            $groups[$key]['subtotal'] += $lineTotal;
            $grossTotal += $lineTotal;
        }
    }

    $items = array_values($groups);
    usort($items, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    });

    $commissionAmount = round($grossTotal * $commissionRate);
    $netTotal = $grossTotal - $commissionAmount;

    $invoice = $existing;
    $invoice['id'] = $invoice['id'] ?? ('inv-' . $yearMonth);
    $invoice['period'] = $yearMonth;
    $invoice['deliveries'] = $deliveryIds;
    $invoice['items'] = $items;
    $invoice['gross_total'] = $grossTotal;
    $invoice['commission_rate'] = $commissionRate;
    $invoice['commission_amount'] = $commissionAmount;
    $invoice['net_total'] = $netTotal;
    $invoice['updated_at'] = date('c');
    if (!isset($invoice['created_at'])) {
        $invoice['created_at'] = $invoice['updated_at'];
    }
    $invoice['status'] = 'draft';
    unset($invoice['published_by'], $invoice['published_at'], $invoice['acknowledged_by'], $invoice['acknowledged_at']);

    return $invoice;
}

function compute_invoice_due_info(string $yearMonth, ?string $storedDueDate, array $settings, string $issueDateText = ''): array
{
    $storedDueDate = trim((string) ($storedDueDate ?? ''));
    if ($storedDueDate !== '') {
        $display = $storedDueDate;
        $iso = '';
        $dt = DateTime::createFromFormat('Y-m-d', $storedDueDate);
        if ($dt instanceof DateTime) {
            $iso = $dt->format('Y-m-d');
            $display = $dt->format('Y年n月j日');
        }
        return ['display' => $display, 'iso' => $iso];
    }

    $periodDate = DateTime::createFromFormat('Y-m', $yearMonth);
    if (!$periodDate instanceof DateTime) {
        $periodDate = DateTime::createFromFormat('Y-m-d', $yearMonth . '-01') ?: new DateTime('first day of this month');
    }

    $rule = $settings['invoice_due_rule'] ?? 'month_end';
    $day = (int) ($settings['invoice_due_day'] ?? 31);
    if ($day < 1 || $day > 31) {
        $day = 31;
    }

    switch ($rule) {
        case 'next_month_end':
            $due = (clone $periodDate)->modify('first day of next month')->modify('last day of this month');
            break;
        case 'day_of_month':
            $due = clone $periodDate;
            $maxDay = (int) $due->format('t');
            $due->setDate((int) $due->format('Y'), (int) $due->format('m'), min($day, $maxDay));
            break;
        case 'month_end':
        default:
            $due = (clone $periodDate)->modify('last day of this month');
            break;
    }

    $iso = $due->format('Y-m-d');
    $display = $due->format('Y年n月j日');
    return ['display' => $display, 'iso' => $iso];
}