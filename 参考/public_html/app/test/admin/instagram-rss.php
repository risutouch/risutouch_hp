<?php
// Instagram RSS 自動取得システム
class InstagramRSSManager {
    private $config_file;
    private $posts_file;
    private $rss_cache_file;
    
    public function __construct() {
        $this->config_file = '../assets/data/instagram_rss_config.json';
        $this->posts_file = '../assets/data/instagram_posts.json';
        $this->rss_cache_file = '../assets/data/instagram_rss_cache.json';
        $this->initFiles();
    }
    
    private function initFiles() {
        // 設定ファイル初期化
        if (!file_exists($this->config_file)) {
            $initial_config = [
                'username' => '',
                'last_updated' => '',
                'auto_fetch' => true,
                'fetch_interval' => 3600 // 1時間ごと
            ];
            file_put_contents($this->config_file, json_encode($initial_config, JSON_PRETTY_PRINT));
        }
        
        // 投稿ファイル初期化
        if (!file_exists($this->posts_file)) {
            $initial_posts = [
                'posts' => [],
                'last_fetch' => '',
                'source' => 'rss'
            ];
            file_put_contents($this->posts_file, json_encode($initial_posts, JSON_PRETTY_PRINT));
        }
        
        // RSSキャッシュファイル初期化
        if (!file_exists($this->rss_cache_file)) {
            $initial_cache = [
                'last_fetch' => '',
                'last_etag' => '',
                'items' => []
            ];
            file_put_contents($this->rss_cache_file, json_encode($initial_cache, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Instagramユーザー名を設定
     */
    public function setUsername($username) {
        $config = json_decode(file_get_contents($this->config_file), true);
        $config['username'] = $username;
        $config['last_updated'] = date('Y-m-d H:i:s');
        
        return file_put_contents($this->config_file, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * 設定を取得
     */
    public function getConfig() {
        return json_decode(file_get_contents($this->config_file), true);
    }
    
    /**
     * Instagram RSSを取得
     */
    public function fetchRSS() {
        $config = $this->getConfig();
        
        if (empty($config['username'])) {
            return ['error' => 'Instagramユーザー名が設定されていません'];
        }
        
        // 複数のRSSソースを試行
        $rss_urls = [
            "https://www.instagram.com/{$config['username']}/feed/", // Instagram公式RSS（存在しない場合あり）
            "https://bibliogram.art/u/{$config['username']}/rss.xml", // Bibliogram RSS
            "https://picuki.com/profile/{$config['username']}.rss", // Picuki RSS  
            "https://rsshub.app/instagram/user/{$config['username']}", // RSSHub
            "https://api.rss2json.com/v1/api.json?rss_url=https://www.instagram.com/{$config['username']}/feed/"
        ];
        
        foreach ($rss_urls as $rss_url) {
            $result = $this->fetchFromURL($rss_url);
            if ($result['success']) {
                return $this->processRSSData($result['data']);
            }
        }
        
        return ['error' => 'すべてのRSSソースでデータ取得に失敗しました'];
    }
    
    /**
     * URLからRSSデータを取得
     */
    private function fetchFromURL($url) {
        // cURLまたはfile_get_contentsでRSS取得
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; Instagram RSS Fetcher)',
                'ignore_errors' => true
            ]
        ]);
        
        $rss_content = @file_get_contents($url, false, $context);
        
        if ($rss_content === false) {
            return ['success' => false, 'error' => 'RSS取得に失敗'];
        }
        
        // XMLをパース
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rss_content);
        
        if ($xml === false) {
            return ['success' => false, 'error' => 'XML解析に失敗'];
        }
        
        return ['success' => true, 'data' => $xml];
    }
    
    /**
     * RSSデータを処理してInstagram投稿形式に変換
     */
    private function processRSSData($xml) {
        $posts = [];
        
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $post = $this->convertRSSItemToPost($item);
                if ($post) {
                    $posts[] = $post;
                }
            }
        }
        
        if (empty($posts)) {
            return ['error' => 'RSS投稿データが見つかりません'];
        }
        
        // 投稿を保存
        return $this->savePosts($posts);
    }
    
    /**
     * RSS項目をInstagram投稿形式に変換
     */
    private function convertRSSItemToPost($item) {
        // 基本情報を抽出
        $title = (string)$item->title;
        $description = (string)$item->description;
        $link = (string)$item->link;
        $pub_date = (string)$item->pubDate;
        
        // 画像URLを抽出（description内のimg tagから）
        $image_url = '';
        if (preg_match('/<img[^>]+src="([^"]+)"/', $description, $matches)) {
            $image_url = $matches[1];
        }
        
        // キャプションを抽出（HTMLタグを除去）
        $caption = strip_tags($description);
        $caption = html_entity_decode($caption, ENT_QUOTES, 'UTF-8');
        
        return [
            'id' => 'rss_' . md5($link . $pub_date),
            'caption' => $caption ?: $title,
            'image' => $image_url,
            'url' => $link,
            'date' => date('Y-m-d', strtotime($pub_date)),
            'type' => 'image',
            'source' => 'rss'
        ];
    }
    
    /**
     * 投稿データを保存
     */
    private function savePosts($new_posts) {
        $current_data = json_decode(file_get_contents($this->posts_file), true);
        
        // 重複チェック
        $existing_ids = array_column($current_data['posts'], 'id');
        $added_count = 0;
        
        foreach ($new_posts as $post) {
            if (!in_array($post['id'], $existing_ids)) {
                array_unshift($current_data['posts'], $post); // 新しい投稿を先頭に追加
                $added_count++;
            }
        }
        
        $current_data['last_fetch'] = date('Y-m-d H:i:s');
        
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
    
    /**
     * データクリア
     */
    public function clearData() {
        $config = [
            'username' => '',
            'last_updated' => '',
            'auto_fetch' => true,
            'fetch_interval' => 3600
        ];
        
        $posts = [
            'posts' => [],
            'last_fetch' => '',
            'source' => 'rss'
        ];
        
        $config_result = file_put_contents($this->config_file, json_encode($config, JSON_PRETTY_PRINT));
        $posts_result = file_put_contents($this->posts_file, json_encode($posts, JSON_PRETTY_PRINT));
        
        return $config_result !== false && $posts_result !== false;
    }
    
    /**
     * 自動取得が必要かチェック
     */
    public function shouldAutoFetch() {
        $config = $this->getConfig();
        
        if (!$config['auto_fetch'] || empty($config['username'])) {
            return false;
        }
        
        $last_fetch = strtotime($config['last_updated'] ?? '1970-01-01');
        $current_time = time();
        
        return ($current_time - $last_fetch) > $config['fetch_interval'];
    }
}
?>