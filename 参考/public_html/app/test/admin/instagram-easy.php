<?php
// 簡単Instagram API - Graph API Explorerトークン直接入力方式
class EasyInstagramAPI {
    private $config_file;
    
    public function __construct() {
        $this->config_file = '../assets/data/instagram_config.json';
        $this->initConfig();
    }
    
    private function initConfig() {
        if (!file_exists($this->config_file)) {
            $initial_config = [
                'access_token' => '',
                'account_id' => '',
                'last_updated' => '',
                'is_configured' => false
            ];
            file_put_contents($this->config_file, json_encode($initial_config, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * 設定を保存
     */
    public function saveConfig($access_token, $account_id) {
        $config = [
            'access_token' => $access_token,
            'account_id' => $account_id,
            'last_updated' => date('Y-m-d H:i:s'),
            'is_configured' => true
        ];
        
        $result = file_put_contents($this->config_file, json_encode($config, JSON_PRETTY_PRINT));
        return $result !== false;
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
               !empty($config['access_token']) && !empty($config['account_id']);
    }
    
    /**
     * Instagram投稿を取得
     */
    public function getPosts($limit = 25) {
        $config = $this->getConfig();
        if (!$config || !$config['is_configured']) {
            return ['error' => '設定が完了していません'];
        }
        
        // まずFacebookページからInstagramアカウントIDを取得
        $page_url = "https://graph.facebook.com/v18.0/{$config['account_id']}";
        $page_params = [
            'fields' => 'instagram_business_account',
            'access_token' => $config['access_token']
        ];
        
        $page_full_url = $page_url . '?' . http_build_query($page_params);
        $page_response = $this->makeHttpRequest($page_full_url);
        
        if (!$page_response || !isset($page_response['instagram_business_account'])) {
            return [
                'error' => 'Instagramビジネスアカウントが見つかりません', 
                'debug' => [
                    'page_response' => $page_response,
                    'url' => $page_full_url
                ]
            ];
        }
        
        $instagram_account_id = $page_response['instagram_business_account']['id'];
        
        // Instagramメディアを取得
        $media_url = "https://graph.facebook.com/v18.0/{$instagram_account_id}/media";
        $media_params = [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
            'limit' => $limit,
            'access_token' => $config['access_token']
        ];
        
        $media_full_url = $media_url . '?' . http_build_query($media_params);
        $media_response = $this->makeHttpRequest($media_full_url);
        
        if ($media_response && isset($media_response['data'])) {
            $posts = [];
            foreach ($media_response['data'] as $item) {
                if (in_array($item['media_type'], ['IMAGE', 'VIDEO', 'CAROUSEL_ALBUM'])) {
                    $posts[] = [
                        'id' => $item['id'],
                        'caption' => $item['caption'] ?? '',
                        'image' => $item['media_url'] ?? $item['thumbnail_url'] ?? '',
                        'url' => $item['permalink'],
                        'date' => date('Y-m-d', strtotime($item['timestamp'])),
                        'type' => strtolower($item['media_type'])
                    ];
                }
            }
            return ['success' => true, 'posts' => $posts];
        }
        
        return [
            'error' => 'Instagram投稿の取得に失敗しました', 
            'debug' => [
                'instagram_account_id' => $instagram_account_id,
                'media_response' => $media_response,
                'media_url' => $media_full_url
            ]
        ];
    }
    
    /**
     * アカウント情報を取得
     */
    public function getProfile() {
        $config = $this->getConfig();
        if (!$config || !$config['is_configured']) {
            return ['error' => '設定が完了していません'];
        }
        
        $url = "https://graph.facebook.com/v18.0/{$config['account_id']}";
        $params = [
            'fields' => 'id,username,media_count',
            'access_token' => $config['access_token']
        ];
        
        $full_url = $url . '?' . http_build_query($params);
        return $this->makeHttpRequest($full_url);
    }
    
    /**
     * 設定をクリア
     */
    public function clearConfig() {
        $config = [
            'access_token' => '',
            'account_id' => '',
            'last_updated' => '',
            'is_configured' => false
        ];
        return file_put_contents($this->config_file, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * HTTPリクエストを実行
     */
    private function makeHttpRequest($url) {
        // cURLを優先して使用
        if (function_exists('curl_init')) {
            return $this->makeRequestWithCurl($url);
        }
        
        // file_get_contentsをフォールバック
        if (in_array('https', stream_get_wrappers())) {
            return $this->makeRequestWithStream($url);
        }
        
        return ['error' => 'HTTPリクエストの実行環境が整っていません'];
    }
    
    private function makeRequestWithCurl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Instagram Easy API/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $httpCode === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    private function makeRequestWithStream($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true,
                'user_agent' => 'Instagram Easy API/1.0'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response && isset($http_response_header)) {
            $status_line = $http_response_header[0];
            if (strpos($status_line, '200') !== false) {
                return json_decode($response, true);
            }
        }
        
        return null;
    }
}
?>