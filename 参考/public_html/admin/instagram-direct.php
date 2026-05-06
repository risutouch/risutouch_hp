<?php
// Direct Instagram API access using Graph API Explorer token
class DirectInstagramAPI {
    private $access_token;
    private $instagram_account_id;
    
    public function __construct() {
        // Graph API Explorerから取得したアクセストークンを設定
        $this->access_token = 'YOUR_GRAPH_API_TOKEN'; // ← ここにGraph API Explorerのトークンを入力
        $this->instagram_account_id = 'YOUR_INSTAGRAM_ACCOUNT_ID'; // ← ここにInstagramアカウントIDを入力
    }
    
    /**
     * アクセストークンを設定
     */
    public function setAccessToken($token) {
        $this->access_token = $token;
    }
    
    /**
     * InstagramアカウントIDを設定
     */
    public function setInstagramAccountId($account_id) {
        $this->instagram_account_id = $account_id;
    }
    
    /**
     * Instagram投稿を取得
     */
    public function getUserMedia($limit = 25) {
        if (!$this->access_token || !$this->instagram_account_id) {
            return ['error' => 'アクセストークンまたはアカウントIDが設定されていません'];
        }
        
        $url = "https://graph.facebook.com/v18.0/{$this->instagram_account_id}/media";
        $params = [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
            'limit' => $limit,
            'access_token' => $this->access_token
        ];
        
        $full_url = $url . '?' . http_build_query($params);
        
        $response = $this->makeHttpRequest($full_url);
        
        if ($response && isset($response['data'])) {
            // データを管理画面用に変換
            $posts = [];
            foreach ($response['data'] as $item) {
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
        
        return ['error' => 'データの取得に失敗しました', 'response' => $response];
    }
    
    /**
     * アカウント情報を取得
     */
    public function getAccountInfo() {
        if (!$this->access_token || !$this->instagram_account_id) {
            return ['error' => 'アクセストークンまたはアカウントIDが設定されていません'];
        }
        
        $url = "https://graph.facebook.com/v18.0/{$this->instagram_account_id}";
        $params = [
            'fields' => 'id,username,media_count,profile_picture_url',
            'access_token' => $this->access_token
        ];
        
        $full_url = $url . '?' . http_build_query($params);
        
        return $this->makeHttpRequest($full_url);
    }
    
    /**
     * HTTPリクエストを実行
     */
    private function makeHttpRequest($url) {
        // 複数の方法でHTTPリクエストを試行
        $methods = ['curl', 'stream'];
        
        foreach ($methods as $method) {
            $result = null;
            
            switch ($method) {
                case 'curl':
                    if (function_exists('curl_init')) {
                        $result = $this->makeRequestWithCurl($url);
                    }
                    break;
                    
                case 'stream':
                    if (in_array('https', stream_get_wrappers())) {
                        $result = $this->makeRequestWithStream($url);
                    }
                    break;
            }
            
            if ($result !== null) {
                return $result;
            }
        }
        
        return ['error' => 'HTTPリクエストの実行に失敗しました'];
    }
    
    /**
     * cURLでリクエスト
     */
    private function makeRequestWithCurl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $httpCode === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Streamでリクエスト
     */
    private function makeRequestWithStream($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true
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
    
    /**
     * トークンの有効性をチェック
     */
    public function validateToken() {
        $result = $this->getAccountInfo();
        return !isset($result['error']);
    }
}
?>