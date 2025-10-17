<?php
class OllamaAI {
    private $api_url;
    private $model;
    
    public function __construct($api_url = 'http://localhost:11434', $model = 'qwen2.5:0.5b') {
        $this->api_url = rtrim($api_url, '/');
        $this->model = $model;
    }
    
    /**
     * 发送消息到AI模型
     */
    public function chat($message, $context = []) {
        // 构建对话历史
        $messages = [];
        
        // 添加上下文消息
        foreach ($context as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
        
        // 添加当前用户消息
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
                'top_p' => 0.9,
                'max_tokens' => 500
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_url . '/api/chat',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Ollama API请求失败: " . $error);
        }
        
        if ($http_code !== 200) {
            throw new Exception("Ollama API返回错误: HTTP " . $http_code);
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['message']['content'])) {
            return [
                'success' => true,
                'response' => $data['message']['content'],
                'model' => $data['model'],
                'usage' => $data.get('usage', [])
            ];
        } else {
            throw new Exception("AI响应格式错误");
        }
    }
    
    /**
     * 检查Ollama服务是否可用
     */
    public function checkHealth() {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_url . '/api/tags',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    }
    
    /**
     * 获取可用的模型列表
     */
    public function getAvailableModels() {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_url . '/api/tags',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['models'] ?? [];
    }
}
?>