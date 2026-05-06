<?php
// 手動Instagram投稿管理システム
class ManualInstagramManager {
    private $posts_file;
    
    public function __construct() {
        $this->posts_file = '../assets/data/instagram_posts.json';
        $this->initFile();
    }
    
    private function initFile() {
        if (!file_exists($this->posts_file)) {
            $initial_posts = [
                'posts' => [],
                'last_updated' => '',
                'username' => ''
            ];
            file_put_contents($this->posts_file, json_encode($initial_posts, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * 手動で投稿を追加
     */
    public function addPost($post_data) {
        $current_data = json_decode(file_get_contents($this->posts_file), true);
        
        $post = [
            'id' => 'manual_' . time() . '_' . rand(1000, 9999),
            'caption' => $post_data['caption'] ?? '',
            'image' => $post_data['image'] ?? '',
            'url' => $post_data['url'] ?? '',
            'date' => $post_data['date'] ?? date('Y-m-d'),
            'type' => 'image',
            'source' => 'manual'
        ];
        
        // 新しい投稿を先頭に追加
        array_unshift($current_data['posts'], $post);
        $current_data['last_updated'] = date('Y-m-d H:i:s');
        
        $result = file_put_contents($this->posts_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result !== false) {
            return ['success' => true, 'post' => $post];
        } else {
            return ['error' => '投稿の保存に失敗しました'];
        }
    }
    
    /**
     * 一括投稿追加（JSON形式）
     */
    public function addMultiplePosts($posts_json) {
        try {
            $posts_data = json_decode($posts_json, true);
            
            if (!is_array($posts_data)) {
                return ['error' => '無効なJSON形式です'];
            }
            
            $current_data = json_decode(file_get_contents($this->posts_file), true);
            $added_count = 0;
            
            foreach ($posts_data as $post_data) {
                $post = [
                    'id' => 'manual_' . time() . '_' . rand(1000, 9999),
                    'caption' => $post_data['caption'] ?? $post_data['text'] ?? '',
                    'image' => $post_data['image'] ?? $post_data['photo'] ?? '',
                    'url' => $post_data['url'] ?? $post_data['link'] ?? '',
                    'date' => isset($post_data['date']) ? date('Y-m-d', strtotime($post_data['date'])) : date('Y-m-d'),
                    'type' => 'image',
                    'source' => 'manual'
                ];
                
                array_unshift($current_data['posts'], $post);
                $added_count++;
            }
            
            $current_data['last_updated'] = date('Y-m-d H:i:s');
            
            $result = file_put_contents($this->posts_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            if ($result !== false) {
                return [
                    'success' => true, 
                    'added_count' => $added_count,
                    'total_count' => count($current_data['posts'])
                ];
            } else {
                return ['error' => '投稿の保存に失敗しました'];
            }
            
        } catch (Exception $e) {
            return ['error' => 'JSON解析エラー: ' . $e->getMessage()];
        }
    }
    
    /**
     * 投稿を取得
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
     * 投稿を削除
     */
    public function deletePost($post_id) {
        $current_data = json_decode(file_get_contents($this->posts_file), true);
        
        $posts = $current_data['posts'];
        $found = false;
        
        foreach ($posts as $index => $post) {
            if ($post['id'] === $post_id) {
                unset($posts[$index]);
                $found = true;
                break;
            }
        }
        
        if ($found) {
            $current_data['posts'] = array_values($posts); // インデックスを再構築
            $current_data['last_updated'] = date('Y-m-d H:i:s');
            
            $result = file_put_contents($this->posts_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return ['success' => $result !== false];
        } else {
            return ['error' => '投稿が見つかりません'];
        }
    }
    
    /**
     * ユーザー名を設定
     */
    public function setUsername($username) {
        $current_data = json_decode(file_get_contents($this->posts_file), true);
        $current_data['username'] = $username;
        $current_data['last_updated'] = date('Y-m-d H:i:s');
        
        return file_put_contents($this->posts_file, json_encode($current_data, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * アカウント情報を取得
     */
    public function getAccountInfo() {
        $data = json_decode(file_get_contents($this->posts_file), true);
        return [
            'username' => $data['username'] ?? 'unknown',
            'media_count' => count($data['posts'] ?? []),
            'last_updated' => $data['last_updated'] ?? ''
        ];
    }
    
    /**
     * データクリア
     */
    public function clearData() {
        $initial_posts = [
            'posts' => [],
            'last_updated' => '',
            'username' => ''
        ];
        
        return file_put_contents($this->posts_file, json_encode($initial_posts, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * サンプルデータを生成
     */
    public function generateSampleData() {
        $sample_posts = [
            [
                'caption' => '新商品のどんぐりケーキが完成しました！自然の恵みをたっぷりと使った、やさしい甘さのケーキです。 #どんぐりケーキ #自然派スイーツ #新商品',
                'image' => '../assets/images/products/donguri-cake.jpg',
                'url' => 'https://instagram.com/p/sample1',
                'date' => date('Y-m-d', strtotime('-2 days'))
            ],
            [
                'caption' => 'はちみつクッキーの焼き上がり！甘い香りが工房いっぱいに広がっています。今日も一つ一つ丁寧に手作りしました。 #はちみつクッキー #手作りスイーツ #焼きたて',
                'image' => '../assets/images/products/honey-cookie.jpg',
                'url' => 'https://instagram.com/p/sample2',
                'date' => date('Y-m-d', strtotime('-1 day'))
            ],
            [
                'caption' => 'はちみつフロランタンが完成！カリッとした食感とはちみつの上品な甘さが絶妙です。午後のティータイムにいかがですか？ #はちみつフロランタン #ティータイム #上品な甘さ',
                'image' => '../assets/images/products/honey-florentine.jpg',
                'url' => 'https://instagram.com/p/sample3',
                'date' => date('Y-m-d')
            ]
        ];
        
        return $this->addMultiplePosts(json_encode($sample_posts));
    }
}
?>