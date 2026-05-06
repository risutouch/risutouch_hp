<?php

declare(strict_types=1);

function read_json_file(string $path, $default = [])
{
    if (!file_exists($path)) {
        return $default;
    }
    $json = file_get_contents($path);
    if ($json === false || $json === '') {
        return $default;
    }
    $data = json_decode($json, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        return $default;
    }
    return $data;
}

function write_json_file(string $path, $data): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
    }
    $result = file_put_contents($path, $json, LOCK_EX);
    if ($result === false) {
        throw new RuntimeException('Failed to write JSON file: ' . $path);
    }
}

function update_json_file(string $path, callable $updater, $default = [])
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $fp = fopen($path, 'c+');
    if ($fp === false) {
        throw new RuntimeException('Unable to open file: ' . $path);
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new RuntimeException('Unable to acquire lock: ' . $path);
    }

    $contents = stream_get_contents($fp);
    $data = null;
    if ($contents !== false && $contents !== '') {
        $data = json_decode($contents, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $data = $default;
        }
    }
    if (!is_array($data)) {
        $data = $default;
    }

    $newData = $updater($data);
    if ($newData !== null) {
        $data = $newData;
    }

    ftruncate($fp, 0);
    rewind($fp);

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        flock($fp, LOCK_UN);
        fclose($fp);
        throw new RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
    }

    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $data;
}

