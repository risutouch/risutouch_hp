<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/json_storage.php';

function deliveries_file_path(string $yearMonth): string
{
    $normalized = normalize_year_month($yearMonth);
    return data_path('deliveries-' . $normalized . '.json');
}

function load_deliveries(string $yearMonth): array
{
    $path = deliveries_file_path($yearMonth);
    $data = read_json_file($path, []);
    if (!is_array($data)) {
        return [];
    }
    foreach ($data as &$record) {
        if (!isset($record['items']) || !is_array($record['items'])) {
            $record['items'] = [];
        }
        $sum = 0.0;
        foreach ($record['items'] as &$item) {
            $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0.0;
            $price = isset($item['unit_price']) ? (float) $item['unit_price'] : 0.0;
            $lineTotal = $qty * $price;
            if (!isset($item['subtotal'])) {
                $item['subtotal'] = $lineTotal;
            }
            $sum += (float) $item['subtotal'];
        }
        unset($item);
        if (!isset($record['gross_total'])) {
            $record['gross_total'] = $sum;
        }
        if (!isset($record['status'])) {
            $record['status'] = 'unconfirmed';
        }
    }
    unset($record);

    usort($data, function ($a, $b) {
        $dateA = $a['delivery_date'] ?? '';
        $dateB = $b['delivery_date'] ?? '';
        if ($dateA === $dateB) {
            return strcmp($b['id'] ?? '', $a['id'] ?? '');
        }
        return strcmp($dateB, $dateA);
    });
    return $data;
}

function save_deliveries(string $yearMonth, array $records): void
{
    $path = deliveries_file_path($yearMonth);
    write_json_file($path, array_values($records));
}

function update_deliveries(string $yearMonth, callable $updater): array
{
    $path = deliveries_file_path($yearMonth);
    return update_json_file($path, function ($data) use ($updater) {
        if (!is_array($data)) {
            $data = [];
        }
        return $updater($data) ?? $data;
    }, []);
}

function find_delivery(array $deliveries, string $deliveryId): ?array
{
    foreach ($deliveries as $delivery) {
        if (($delivery['id'] ?? null) === $deliveryId) {
            return $delivery;
        }
    }
    return null;
}

function generate_delivery_id(): string
{
    return 'del-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2));
}

function calculate_delivery_totals(array $items): array
{
    $total = 0.0;
    foreach ($items as $item) {
        $qty = isset($item['quantity']) ? (float) $item['quantity'] : 0.0;
        $price = isset($item['unit_price']) ? (float) $item['unit_price'] : 0.0;
        $total += $qty * $price;
    }
    return [
        'gross_total' => $total,
    ];
}

