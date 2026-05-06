<?php
// 基本的なテスト版
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Backup Test Started...<br>";

// 直接アクセス防止の確認
if (!defined('ADMIN_ACCESS')) {
    echo "ADMIN_ACCESS not defined<br>";
}

if (!isset($_SESSION)) {
    echo "Session not started<br>";
    session_start();
}

// バックアップディレクトリのテスト
$backupDir = '../assets/backups';
echo "Checking backup directory: $backupDir<br>";

if (!file_exists($backupDir)) {
    echo "Creating backup directory...<br>";
    if (mkdir($backupDir, 0755, true)) {
        echo "Directory created successfully<br>";
    } else {
        echo "Failed to create directory<br>";
    }
} else {
    echo "Directory already exists<br>";
}

// ZipArchive クラスの確認
if (class_exists('ZipArchive')) {
    echo "ZipArchive class is available<br>";
} else {
    echo "ZipArchive class is NOT available<br>";
}

// 利用可能なバックアップファイルの確認
echo "Looking for existing backup files...<br>";
$files = glob($backupDir . '/backup_*.zip');
if ($files === false) {
    echo "glob() function failed<br>";
} else {
    echo "Found " . count($files) . " backup files<br>";
    foreach ($files as $file) {
        echo " - " . basename($file) . "<br>";
    }
}

echo "Test completed successfully";
?>