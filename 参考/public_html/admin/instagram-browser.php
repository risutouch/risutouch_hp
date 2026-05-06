<?php
// ブラウザベースInstagram投稿管理
class BrowserInstagramManager {
    private $config_file;
    private $posts_file;
    
    public function __construct() {
        $this->config_file = '../assets/data/instagram_config.json';
        $this->posts_file = '../assets/data/instagram_posts.json';
        $this->initFiles();
    }
    
    private function initFiles() {
        // 設定ファイル初期化
        if (!file_exists($this->config_file)) {
            $initial_config = [
                'access_token' => '',
                'page_id' => '',
                'instagram_account_id' => '',
                'last_updated' => '',
                'is_configured' => false
            ];
            file_put_contents($this->config_file, json_encode($initial_config, JSON_PRETTY_PRINT));
        }
        
        // 投稿ファイル初期化
        if (!file_exists($this->posts_file)) {
            $initial_posts = [
                'posts' => [],
                'last_fetch' => '',
                'account_info' => []
            ];
            file_put_contents($this->posts_file, json_encode($initial_posts, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * 設定を保存
     */
    public function saveConfig($access_token, $page_id) {
        $config = [
            'access_token' => $access_token,
            'page_id' => $page_id,
            'last_updated' => date('Y-m-d H:i:s'),
            'is_configured' => true
        ];
        
        return file_put_contents($this->config_file, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * 設定を取得
     */
    public function getConfig() {
        if (file_exists($this->config_file)) {
            return json_decode(file_get_contents($this->config_file), true);
        }
        return null;
    }
    
    /**
     * 設定済みかチェック
     */
    public function isConfigured() {
        $config = $this->getConfig();
        return $config && $config['is_configured'] && 
               !empty($config['access_token']) && !empty($config['page_id']);
    }
    
    /**
     * ブラウザから送信された投稿データを保存
     */
    public function savePosts($posts_data) {
        if (!is_array($posts_data)) {
            return ['error' => '無効なデータ形式です'];
        }
        
        $current_data = json_decode(file_get_contents($this->posts_file), true);
        
        // 投稿データを変換
        $processed_posts = [];
        foreach ($posts_data as $post) {
            $processed_posts[] = [
                'id' => $post['id'] ?? 'post_' . time() . '_' . rand(1000, 9999),
                'caption' => $post['caption'] ?? '',
                'image' => $post['media_url'] ?? $post['thumbnail_url'] ?? '',
                'url' => $post['permalink'] ?? '',
                'date' => isset($post['timestamp']) ? date('Y-m-d', strtotime($post['timestamp'])) : date('Y-m-d'),
                'type' => isset($post['media_type']) ? strtolower($post['media_type']) : 'image'
            ];
        }
        
        $current_data['posts'] = $processed_posts;
        $current_data['last_fetch'] = date('Y-m-d H:i:s');
        
        $result = file_put_contents($this->posts_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result !== false) {
            return ['success' => true, 'posts' => $processed_posts];
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
     * アカウント情報を保存
     */
    public function saveAccountInfo($account_info) {
        $current_data = json_decode(file_get_contents($this->posts_file), true);
        $current_data['account_info'] = $account_info;
        
        return file_put_contents($this->posts_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }
    
    /**
     * アカウント情報を取得
     */
    public function getAccountInfo() {
        if (!file_exists($this->posts_file)) {
            return ['error' => 'アカウント情報がありません'];
        }
        
        $data = json_decode(file_get_contents($this->posts_file), true);
        return $data['account_info'] ?? ['username' => 'Unknown', 'media_count' => 0];
    }
    
    /**
     * 設定をクリア
     */
    public function clearData() {
        $config = [
            'access_token' => '',
            'page_id' => '',
            'instagram_account_id' => '',
            'last_updated' => '',
            'is_configured' => false
        ];
        
        $posts = [
            'posts' => [],
            'last_fetch' => '',
            'account_info' => []
        ];
        
        $config_result = file_put_contents($this->config_file, json_encode($config, JSON_PRETTY_PRINT));
        $posts_result = file_put_contents($this->posts_file, json_encode($posts, JSON_PRETTY_PRINT));
        
        return $config_result !== false && $posts_result !== false;
    }
}
?>