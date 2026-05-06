<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function scrapeInstagramProfile($username) {
    try {
        // Instagram公開プロフィールのURL
        $url = "https://www.instagram.com/{$username}/";
        
        // User-Agentを設定してリクエスト
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]
            ]
        ]);
        
        $html = @file_get_contents($url, false, $context);
        
        if ($html === false) {
            throw new Exception('Instagramページの取得に失敗しました');
        }
        
        // JSON-LDデータを抽出
        $pattern = '/<script type="application\/ld\+json">(.*?)<\/script>/s';
        if (preg_match($pattern, $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData && isset($jsonData['mainEntityOfPage'])) {
                return extractPostsFromJsonLd($jsonData);
            }
        }
        
        // window._sharedDataから抽出を試行
        $pattern = '/window\._sharedData = ({.*?});/s';
        if (preg_match($pattern, $html, $matches)) {
            $sharedData = json_decode($matches[1], true);
            if ($sharedData) {
                return extractPostsFromSharedData($sharedData);
            }
        }
        
        // 代替方法：基本的なメタデータ抽出
        return extractBasicMetadata($html, $username);
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'posts' => []
        ];
    }
}

function extractPostsFromSharedData($sharedData) {
    $posts = [];
    
    try {
        // プロフィールページの投稿を取得
        $media = $sharedData['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] ?? [];
        
        foreach (array_slice($media, 0, 6) as $edge) {
            $node = $edge['node'];
            
            $post = [
                'id' => $node['id'] ?? uniqid(),
                'caption' => $node['edge_media_to_caption']['edges'][0]['node']['text'] ?? '',
                'image' => $node['display_url'] ?? $node['thumbnail_src'] ?? '',
                'url' => "https://www.instagram.com/p/{$node['shortcode']}/",
                'date' => date('Y-m-d', $node['taken_at_timestamp'] ?? time()),
                'likes' => $node['edge_liked_by']['count'] ?? 0
            ];
            
            $posts[] = $post;
        }
        
    } catch (Exception $e) {
        // エラーの場合は空配列を返す
    }
    
    return [
        'success' => true,
        'posts' => $posts
    ];
}

function extractBasicMetadata($html, $username) {
    $posts = [];
    
    // OGメタタグから基本情報を取得
    $pattern = '/<meta property="og:image" content="([^"]+)"/';
    if (preg_match($pattern, $html, $matches)) {
        $image = $matches[1];
        
        $pattern = '/<meta property="og:description" content="([^"]+)"/';
        $description = '';
        if (preg_match($pattern, $html, $matches)) {
            $description = $matches[1];
        }
        
        // 基本的な投稿情報を作成
        $posts[] = [
            'id' => uniqid(),
            'caption' => $description,
            'image' => $image,
            'url' => "https://www.instagram.com/{$username}/",
            'date' => date('Y-m-d'),
            'likes' => 0
        ];
    }
    
    return [
        'success' => true,
        'posts' => $posts,
        'note' => 'Instagramの制限により、限定的な情報のみ取得できました'
    ];
}

// リクエスト処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    
    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'error' => 'ユーザー名が指定されていません'
        ]);
        exit;
    }
    
    // @マークを除去
    $username = ltrim($username, '@');
    
    $result = scrapeInstagramProfile($username);
    echo json_encode($result);
    
} else {
    echo json_encode([
        'success' => false,
        'error' => 'POSTリクエストが必要です'
    ]);
}
?>