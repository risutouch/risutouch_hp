<?php
// 簡易Instagram投稿管理システム
class SimpleInstagramManager {
    private $posts_file;
    
    public function __construct() {
        $this->posts_file = '../assets/data/instagram_posts.json';
        $this->initializeFile();
    }
    
    private function initializeFile() {
        if (!file_exists($this->posts_file)) {
            $initial_data = [
                'posts' => [],
                'last_updated' => date('Y-m-d H:i:s'),
                'account_info' => [
                    'username' => '',
                    'connected' => false
                ]
            ];
            file_put_contents($this->posts_file, json_encode($initial_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    
    /**
     * サンプル投稿データを生成（実際のInstagram APIの代替）
     */
    public function generateSamplePosts() {
        $sample_posts = [
            [
                'id' => 'sample_' . time() . '_1',
                'caption' => '新商品のどんぐりケーキが完成しました！自然の恵みをたっぷりと使った、やさしい甘さのケーキです。#どんぐりケーキ #自然派スイーツ #新商品',
                'image' => '../assets/images/products/donguri-cake.jpg',
                'url' => 'https://instagram.com/p/sample1',
                'date' => date('Y-m-d', strtotime('-2 days')),
                'type' => 'image',
                'likes' => rand(50, 200),
                'comments' => rand(5, 30)
            ],
            [
                'id' => 'sample_' . time() . '_2',
                'caption' => 'はちみつクッキーの焼き上がり！甘い香りが工房いっぱいに広がっています。今日も一つ一つ丁寧に手作りしました。#はちみつクッキー #手作りスイーツ #焼きたて',
                'image' => '../assets/images/products/honey-cookie.jpg',
                'url' => 'https://instagram.com/p/sample2',
                'date' => date('Y-m-d', strtotime('-1 day')),
                'type' => 'image',
                'likes' => rand(50, 200),
                'comments' => rand(5, 30)
            ],
            [
                'id' => 'sample_' . time() . '_3',
                'caption' => 'はちみつフロランタンが完成！カリッとした食感とはちみつの上品な甘さが絶妙です。午後のティータイムにいかがですか？#はちみつフロランタン #ティータイム #上品な甘さ',
                'image' => '../assets/images/products/honey-florentine.jpg',
                'url' => 'https://instagram.com/p/sample3',
                'date' => date('Y-m-d'),
                'type' => 'image',
                'likes' => rand(50, 200),
                'comments' => rand(5, 30)
            ]
        ];
        
        return $sample_posts;
    }
    
    /**
     * 投稿データを取得
     */
    public function getPosts($limit = 25) {
        $data = json_decode(file_get_contents($this->posts_file), true);
        $posts = $data['posts'] ?? [];
        
        // サンプルデータがない場合は生成
        if (empty($posts)) {
            $posts = $this->generateSamplePosts();
            $data['posts'] = $posts;
            $data['last_updated'] = date('Y-m-d H:i:s');
            file_put_contents($this->posts_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        return array_slice($posts, 0, $limit);
    }
    
    /**
     * アカウント情報を取得
     */
    public function getAccountInfo() {
        $data = json_decode(file_get_contents($this->posts_file), true);
        return $data['account_info'] ?? [
            'username' => 'sample_bakery',
            'connected' => true,
            'media_count' => count($data['posts'] ?? [])
        ];
    }
    
    /**
     * 投稿を更新（新しい投稿を追加）
     */
    public function updatePosts() {
        $new_posts = $this->generateSamplePosts();
        $data = json_decode(file_get_contents($this->posts_file), true);
        
        // 既存の投稿と重複しないようにIDをチェック
        $existing_ids = array_column($data['posts'] ?? [], 'id');
        foreach ($new_posts as $post) {
            if (!in_array($post['id'], $existing_ids)) {
                $data['posts'][] = $post;
            }
        }
        
        // 日付順にソート（新しい順）
        usort($data['posts'], function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        $data['last_updated'] = date('Y-m-d H:i:s');
        $data['account_info']['connected'] = true;
        $data['account_info']['username'] = 'sample_bakery';
        $data['account_info']['media_count'] = count($data['posts']);
        
        file_put_contents($this->posts_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return true;
    }
    
    /**
     * 認証状態をチェック
     */
    public function isConnected() {
        $data = json_decode(file_get_contents($this->posts_file), true);
        return $data['account_info']['connected'] ?? false;
    }
    
    /**
     * 接続をリセット
     */
    public function disconnect() {
        $data = json_decode(file_get_contents($this->posts_file), true);
        $data['account_info']['connected'] = false;
        $data['posts'] = [];
        file_put_contents($this->posts_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }
}
?>