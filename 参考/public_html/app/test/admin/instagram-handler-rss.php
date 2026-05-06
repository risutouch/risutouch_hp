<?php
require_once 'instagram-rss.php';

header('Content-Type: application/json');

$instagram = new InstagramRSSManager();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'set_username':
        // Instagramユーザー名を設定
        $username = $_POST['username'] ?? '';
        
        if (empty($username)) {
            echo json_encode([
                'success' => false,
                'error' => 'Instagramユーザー名を入力してください'
            ]);
            break;
        }
        
        // @マークを除去
        $username = ltrim($username, '@');
        
        $result = $instagram->setUsername($username);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'ユーザー名を設定しました' : '設定の保存に失敗しました'
        ]);
        break;
        
    case 'fetch_rss':
        // RSSを取得して投稿を更新
        $result = $instagram->fetchRSS();
        
        if (isset($result['error'])) {
            // RSSで失敗した場合、スクレイピングを試行
            require_once 'instagram-simple-scraper.php';
            $scraper = new SimpleInstagramScraper();
            $config = $instagram->getConfig();
            
            if (!empty($config['username'])) {
                $scraper_result = $scraper->fetchPosts($config['username']);
                
                if (isset($scraper_result['error'])) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'RSS取得もスクレイピングも失敗しました: ' . $scraper_result['error']
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => "スクレイピングで{$scraper_result['added_count']}件の新しい投稿を追加しました（合計{$scraper_result['total_count']}件）",
                        'added_count' => $scraper_result['added_count'],
                        'total_count' => $scraper_result['total_count'],
                        'method' => 'scraping'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'message' => "RSS経由で{$result['added_count']}件の新しい投稿を追加しました（合計{$result['total_count']}件）",
                'added_count' => $result['added_count'],
                'total_count' => $result['total_count'],
                'method' => 'rss'
            ]);
        }
        break;
        
    case 'status':
        // 設定状態確認
        $config = $instagram->getConfig();
        echo json_encode([
            'success' => true,
            'authenticated' => !empty($config['username']),
            'username' => $config['username'] ?? '',
            'last_updated' => $config['last_updated'] ?? ''
        ]);
        break;
        
    case 'profile':
        // プロフィール情報（ダミー）
        $config = $instagram->getConfig();
        $posts_data = $instagram->getPosts(1); // 投稿数カウント用
        
        if (!empty($config['username'])) {
            echo json_encode([
                'success' => true,
                'profile' => [
                    'username' => $config['username'],
                    'media_count' => isset($posts_data['posts']) ? count($posts_data['posts']) : 0
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'ユーザー名が設定されていません'
            ]);
        }
        break;
        
    case 'media':
        // 保存された投稿を取得
        $limit = $_GET['limit'] ?? 25;
        $result = $instagram->getPosts($limit);
        echo json_encode($result);
        break;
        
    case 'auto_check':
        // 自動取得チェック（cron用）
        if ($instagram->shouldAutoFetch()) {
            $result = $instagram->fetchRSS();
            echo json_encode($result);
        } else {
            echo json_encode([
                'success' => true,
                'message' => '自動取得は不要です'
            ]);
        }
        break;
        
    case 'logout':
        // データクリア
        $result = $instagram->clearData();
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'データをクリアしました' : 'データのクリアに失敗しました'
        ]);
        break;
        
    case 'test_rss':
        // RSSテスト（デバッグ用）
        $config = $instagram->getConfig();
        
        if (empty($config['username'])) {
            echo json_encode([
                'success' => false,
                'error' => 'ユーザー名が設定されていません'
            ]);
            break;
        }
        
        $rss_url = "https://rsshub.app/instagram/user/{$config['username']}";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; RSS Test)',
                'ignore_errors' => true
            ]
        ]);
        
        $rss_content = @file_get_contents($rss_url, false, $context);
        
        if ($rss_content === false) {
            echo json_encode([
                'success' => false,
                'error' => 'RSS URLにアクセスできません',
                'url' => $rss_url
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'RSS URLにアクセス成功',
                'url' => $rss_url,
                'content_length' => strlen($rss_content),
                'content_preview' => substr($rss_content, 0, 500)
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'error' => '無効なアクションです'
        ]);
        break;
}
?>