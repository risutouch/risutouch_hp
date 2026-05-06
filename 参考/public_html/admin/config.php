<?php
// 環境設定ファイル
// 本番環境では DEVELOPMENT_MODE を false に変更してください

// 開発モード設定
// true: ローカル開発用（メール認証バイパス）
// false: 本番環境用（通常のメール認証）
define('DEVELOPMENT_MODE', false);

// 本番環境用の設定例：
// define('DEVELOPMENT_MODE', false);

// その他の環境別設定
if (DEVELOPMENT_MODE) {
    // 開発環境設定
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    // 本番環境設定
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}
?>