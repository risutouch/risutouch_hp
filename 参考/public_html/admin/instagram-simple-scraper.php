<?php
// 簡易Instagram投稿取得（公開データのみ）
class SimpleInstagramScraper {
    private $posts_file;
    
    public function __construct() {
        $this->posts_file = '../assets/data/instagram_posts.json';
        $this->initFile();
    }
    
    private function initFile() {
        if (!file_exists($this->posts_file)) {
            $initial_posts = [
                'posts' => [],
                'last_fetch' => '',
                'username' => ''
            ];
            file_put_contents($this->posts_file, json_encode($initial_posts, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Instagram公開投稿を取得（簡易版）
     */
    public function fetchPosts($username) {
        // Instagram公開ページからメタデータを取得
        $url = "https://www.instagram.com/{$username}/";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'ignore_errors' => true
            ]
        ]);
        
        $html = @file_get_contents($url, false, $context);
        
        if ($html === false) {
            return ['error' => 'Instagramページにアクセスできません'];
        }
        
        // JSON-LDデータを抽出
        $posts = $this->extractPostsFromHTML($html, $username);
        
        if (empty($posts)) {
            return ['error' => '投稿データが見つかりません'];
        }
        
        return $this->savePosts($posts, $username);
    }
    
    /**
     * HTMLからInstagram投稿データを抽出
     */
    private function extractPostsFromHTML($html, $username) {
        $posts = [];
        
        // window._sharedData からデータを抽出
        if (preg_match('/window\._sharedData\s*=\s*({.+?});/', $html, $matches)) {
            $shared_data = json_decode($matches[1], true);
            
            if (isset($shared_data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'])) {
                $edges = $shared_data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];
                
                foreach ($edges as $edge) {
                    $node = $edge['node'];
                    
                    $posts[] = [
                        'id' => 'ig_' . $node['id'],
                        'caption' => isset($node['edge_media_to_caption']['edges'][0]['node']['text']) 
                                   ? $node['edge_media_to_caption']['edges'][0]['node']['text'] : '',
                        'image' => $node['display_url'],
                        'url' => "https://www.instagram.com/p/{$node['shortcode']}/",
                        'date' => date('Y-m-d', $node['taken_at_timestamp']),
                        'type' => $node['is_video'] ? 'video' : 'image'
                    ];
                }
            }
        }
        
        // もしsharedDataで取得できない場合、メタタグから取得を試行
        if (empty($posts)) {
            $posts = $this->extractFromMetaTags($html, $username);
        }
        
        return $posts;
    }
    
    /**
     * メタタグから投稿情報を抽出（フォールバック）
     */
    private function extractFromMetaTags($html, $username) {
        $posts = [];
        
        // og:image メタタグから画像を抽出
        if (preg_match_all('/<meta property="og:image" content="([^"]+)"/', $html, $matches)) {
            foreach ($matches[1] as $i => $image_url) {
                if (strpos($image_url, 'instagram') !== false) {
                    $posts[] = [
                        'id' => 'meta_' . $username . '_' . $i,
                        'caption' => "Instagram投稿 #" . ($i + 1),
                        'image' => $image_url,
                        'url' => "https://www.instagram.com/{$username}/",
                        'date' => date('Y-m-d'),
                        'type' => 'image'
                    ];
                }
            }
        }
        
        return array_slice($posts, 0, 5); // 最大5件
    }
    
    /**
     * 投稿データを保存
     */
    private function savePosts($new_posts, $username) {
        $current_data = json_decode(file_get_contents($this->posts_file), true);
        
        // 重複チェック
        $existing_ids = array_column($current_data['posts'], 'id');
        $added_count = 0;
        
        foreach ($new_posts as $post) {
            if (!in_array($post['id'], $existing_ids)) {
                array_unshift($current_data['posts'], $post);
                $added_count++;
            }
        }
        
        $current_data['last_fetch'] = date('Y-m-d H:i:s');
        $current_data['username'] = $username;
        
        $result = file_put_contents($this->posts_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result !== false) {
            return [
                'success' => true,
                'posts' => $current_data['posts'],
                'added_count' => $added_count,
                'total_count' => count($current_data['posts'])
            ];
        } else {
            return ['error' => '投稿データの保存に失敗しました'];
        }
    }
    
    /**
     * 保存された投稿を取得
     */
    public function getPosts($limit = 25) {
        if (!file_exists($this->posts_file)) {
            return ['error' => '投稿データがありません'];
        }
        
        $data = json_decode(file_get_contents($this->posts_file), true);
        $posts = $data['posts'] ?? [];
        
        return ['success' => true, 'posts' => array_slice($posts, 0, $limit)];
    }
}
?>