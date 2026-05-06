<?php
// Instagram API with Facebook Login 設定
class InstagramAPI {
    private $app_id;
    private $app_secret;
    private $redirect_uri;
    private $access_token;
    private $page_id;
    private $instagram_business_account;
    
    public function __construct() {
        // Facebook Developer Appから取得した値を設定
        $this->app_id = '1775710736521310';  // Facebook App ID
        $this->app_secret = 'a74a42f4f00131929cf1930095b40e20';  // App Secret
        $this->redirect_uri = 'http://localhost:8080/admin/instagram-callback.php';
        
        // セッションからアクセストークンを取得
        session_start();
        $this->access_token = $_SESSION['facebook_access_token'] ?? null;
        $this->page_id = $_SESSION['facebook_page_id'] ?? null;
        $this->instagram_business_account = $_SESSION['instagram_business_account'] ?? null;
    }
    
    /**
     * Facebook Login認証URLを生成
     */
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->app_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'pages_show_list,pages_read_engagement,instagram_basic,instagram_manage_insights',
            'response_type' => 'code',
            'state' => 'instagram_auth_' . time()
        ];
        
        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }
    
    /**
     * 認証コードからアクセストークンを取得
     */
    public function getAccessToken($code) {
        // Step 1: Facebook アクセストークンを取得
        $url = 'https://graph.facebook.com/v18.0/oauth/access_token';
        
        $data = [
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'redirect_uri' => $this->redirect_uri,
            'code' => $code
        ];
        
        $response = $this->makeRequest($url, $data, 'POST');
        
        if (!$response || !isset($response['access_token'])) {
            return false;
        }
        
        $access_token = $response['access_token'];
        
        // Step 2: ユーザーのFacebookページを取得
        $pages_url = "https://graph.facebook.com/v18.0/me/accounts?access_token={$access_token}";
        $pages_response = $this->makeRequest($pages_url);
        
        if (!$pages_response || !isset($pages_response['data'])) {
            return ['error' => 'Facebookページが見つかりません'];
        }
        
        // 最初のページを使用（複数ページがある場合は選択UIが必要）
        $page = $pages_response['data'][0] ?? null;
        if (!$page) {
            return ['error' => 'アクセス可能なFacebookページがありません'];
        }
        
        $page_access_token = $page['access_token'];
        $page_id = $page['id'];
        
        // Step 3: ページに紐づくInstagramアカウントを取得
        $ig_url = "https://graph.facebook.com/v18.0/{$page_id}?fields=instagram_business_account&access_token={$page_access_token}";
        $ig_response = $this->makeRequest($ig_url);
        
        if (!$ig_response || !isset($ig_response['instagram_business_account'])) {
            return ['error' => 'Instagramビジネスアカウントが紐づいていません'];
        }
        
        // セッションに保存
        $_SESSION['facebook_access_token'] = $access_token;
        $_SESSION['page_access_token'] = $page_access_token;
        $_SESSION['facebook_page_id'] = $page_id;
        $_SESSION['instagram_business_account'] = $ig_response['instagram_business_account']['id'];
        
        $this->access_token = $page_access_token;
        $this->page_id = $page_id;
        $this->instagram_business_account = $ig_response['instagram_business_account']['id'];
        
        return [
            'access_token' => $page_access_token,
            'page_id' => $page_id,
            'instagram_account' => $ig_response['instagram_business_account']['id']
        ];
    }
    
    /**
     * Instagramアカウント情報を取得
     */
    public function getUserProfile() {
        if (!$this->access_token || !$this->instagram_business_account) {
            return false;
        }
        
        $url = "https://graph.facebook.com/v18.0/{$this->instagram_business_account}?fields=id,username,media_count,profile_picture_url&access_token={$this->access_token}";
        return $this->makeRequest($url);
    }
    
    /**
     * Instagramメディア一覧を取得
     */
    public function getUserMedia($limit = 25) {
        if (!$this->access_token || !$this->instagram_business_account) {
            return false;
        }
        
        $url = "https://graph.facebook.com/v18.0/{$this->instagram_business_account}/media?fields=id,caption,media_type,media_url,thumbnail_url,permalink,timestamp&limit={$limit}&access_token={$this->access_token}";
        return $this->makeRequest($url);
    }
    
    /**
     * 特定のメディア詳細を取得
     */
    public function getMediaDetails($media_id) {
        if (!$this->access_token) {
            return false;
        }
        
        $url = "https://graph.instagram.com/{$media_id}?fields=id,caption,media_type,media_url,thumbnail_url,permalink,timestamp&access_token={$this->access_token}";
        return $this->makeRequest($url);
    }
    
    /**
     * HTTPリクエストを実行
     */
    private function makeRequest($url, $data = null, $method = 'GET') {
        // 複数の方法でHTTPリクエストを試行
        $methods = ['curl', 'stream'];
        
        foreach ($methods as $requestMethod) {
            $result = null;
            
            switch ($requestMethod) {
                case 'curl':
                    if (function_exists('curl_init')) {
                        $result = $this->makeRequestWithCurl($url, $data, $method);
                    }
                    break;
                    
                case 'stream':
                    if (in_array('https', stream_get_wrappers())) {
                        $result = $this->makeRequestWithFileGetContents($url, $data, $method);
                    }
                    break;
            }
            
            if ($result !== false && $result !== null) {
                return $result;
            }
        }
        
        // すべての方法が失敗した場合
        error_log('All HTTP request methods failed for URL: ' . $url);
        return false;
    }
    
    /**
     * cURLを使用したHTTPリクエスト
     */
    private function makeRequestWithCurl($url, $data = null, $method = 'GET') {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * file_get_contents()を使用したHTTPリクエスト
     */
    private function makeRequestWithFileGetContents($url, $data = null, $method = 'GET') {
        // HTTPSラッパーが利用可能かチェック
        if (!in_array('https', stream_get_wrappers())) {
            // HTTPSラッパーが無効な場合、エラーメッセージを返す
            error_log('HTTPS wrapper is not enabled in PHP configuration');
            return false;
        }
        
        $context_options = [
            'http' => [
                'method' => $method,
                'header' => 'Content-Type: application/x-www-form-urlencoded\r\n',
                'ignore_errors' => true,
                'timeout' => 30
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        
        if ($method === 'POST' && $data) {
            $context_options['http']['content'] = http_build_query($data);
        }
        
        $context = stream_context_create($context_options);
        
        // エラー出力を抑制
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false && isset($http_response_header)) {
            // レスポンスヘッダーからステータスコードを取得
            $status_line = $http_response_header[0];
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $status_line, $matches);
            $httpCode = isset($matches[1]) ? (int)$matches[1] : 0;
            
            if ($httpCode === 200) {
                return json_decode($response, true);
            }
        }
        
        return false;
    }
    
    /**
     * アクセストークンの有効性をチェック
     */
    public function isTokenValid() {
        return $this->access_token && $this->instagram_business_account && $this->getUserProfile() !== false;
    }
    
    /**
     * ログアウト（トークンを削除）
     */
    public function logout() {
        unset($_SESSION['facebook_access_token']);
        unset($_SESSION['page_access_token']);
        unset($_SESSION['facebook_page_id']);
        unset($_SESSION['instagram_business_account']);
        $this->access_token = null;
        $this->page_id = null;
        $this->instagram_business_account = null;
    }
}
?>