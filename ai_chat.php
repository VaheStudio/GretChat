<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once 'ollama_ai.php';

$current_server = $_GET['server'] ?? 'main';
$username = $_SESSION['user'];
$user_id = $_SESSION['user_id'];

// 初始化AI助手（如果是AI服务器）
$ai_assistant = null;
if ($current_server === 'ai') {
    $ai_assistant = new OllamaAI('http://localhost:11434', 'qwen2.5:0.5b');
    $ai_available = $ai_assistant->checkHealth();
}

// 处理获取消息的请求
if (isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    $messages = [];
    if (file_exists('messages.jsonl')) {
        $lines = file('messages.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            // 只获取当前服务器的消息
            foreach ($lines as $line) {
                $message = json_decode($line, true);
                if ($message && $message['server'] === $current_server) {
                    $messages[] = $message;
                }
            }
        }
    }
    
    // 输出JSONL格式的消息
    foreach ($messages as $message) {
        echo json_encode($message) . "\n";
    }
    exit();
}

// 处理消息发送

        // 处理文件上传
        $file = $_FILES['file'] ?? null;
        $file_url = '';
        
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            // 创建上传目录
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // 生成唯一文件名
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $file_url = $targetPath;
            } else {
                error_log("文件上传失败");
            }
        }
        
        // 消息或文件至少有一个
        if (!empty($message) || !empty($file_url)) {
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $message_data = [
            'id' => uniqid(),
            'user_id' => $user_id,
            'username' => $username,
            'message' => $message,
            'file_url' => $file_url,
            'file_name' => $file ? $file['name'] : '',
            'server' => $current_server,
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s')
        ];
        
        // 保存用户消息
        if (file_put_contents('messages.jsonl', json_encode($message_data) . "\n", FILE_APPEND | LOCK_EX) === false) {
            error_log("无法写入消息文件");
        }
        
        // 如果是AI服务器，获取AI回复
        if ($current_server === 'ai' && $ai_assistant && $ai_available) {
            try {
                // 获取最近的对话上下文（最后10条消息）
                $context = [];
                $lines = file('messages.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines) {
                    $recent_messages = array_slice($lines, -10); // 获取最后10条消息
                    foreach ($recent_messages as $line) {
                        $msg = json_decode($line, true);
                        if ($msg && $msg['server'] === 'ai') {
                            $role = ($msg['username'] === 'AI助手') ? 'assistant' : 'user';
                            $context[] = [
                                'role' => $role,
                                'content' => $msg['message']
                            ];
                        }
                    }
                }
                
                // 获取AI回复
                $ai_response = $ai_assistant->chat($message, $context);
                
                // 保存AI回复
                $ai_message_data = [
                    'id' => uniqid(),
                    'user_id' => 'ai_assistant',
                    'username' => 'AI助手',
                    'message' => $ai_response['response'],
                    'server' => $current_server,
                    'timestamp' => time(),
                    'date' => date('Y-m-d H:i:s'),
                    'ai_model' => $ai_response['model']
                ];
                
                file_put_contents('messages.jsonl', json_encode($ai_message_data) . "\n", FILE_APPEND | LOCK_EX);
                
            } catch (Exception $e) {
                error_log("AI回复失败: " . $e->getMessage());
                // 保存错误消息
                $error_message_data = [
                    'id' => uniqid(),
                    'user_id' => 'ai_assistant',
                    'username' => '系统提示',
                    'message' => 'AI助手暂时无法回复: ' . $e->getMessage(),
                    'server' => $current_server,
                    'timestamp' => time(),
                    'date' => date('Y-m-d H:i:s')
                ];
                file_put_contents('messages.jsonl', json_encode($error_message_data) . "\n", FILE_APPEND | LOCK_EX);
            }
        }
        
        // 返回成功响应
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => '消息不能为空']);
        exit();
    }
}

// 获取在线用户
function getOnlineUsers($current_server) {
    $online_users = [];
    
    if (file_exists('messages.jsonl')) {
        $lines = file('messages.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            // 简单在线检测：最近2分钟内有活动的用户
            $two_minutes_ago = time() - 120;
            foreach ($lines as $line) {
                $message = json_decode($line, true);
                if ($message && $message['server'] === $current_server && $message['timestamp'] > $two_minutes_ago) {
                    $online_users[$message['username']] = [
                        'username' => $message['username'],
                        'last_active' => $message['timestamp']
                    ];
                }
            }
        }
    }
    
    return $online_users;
}

$online_users = getOnlineUsers($current_server);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天室 - <?php 
        $server_names = [
            'main' => '主聊天室',
            'game' => '游戏讨论',
            'tech' => '技术交流', 
            'ai' => 'AI智能助手'
        ];
        echo htmlspecialchars($server_names[$current_server] ?? $current_server); 
    ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h3><?php echo $current_server === 'ai' ? 'AI助手状态' : '在线用户'; ?></h3>
                <?php if ($current_server === 'ai'): ?>
                    <span class="ai-status <?php echo $ai_available ? 'online' : 'offline'; ?>">
                        <?php echo $ai_available ? '在线' : '离线'; ?>
                    </span>
                <?php else: ?>
                    <span class="online-count"><?php echo count($online_users); ?> 人在线</span>
                <?php endif; ?>
            </div>
            
            <?php if ($current_server === 'ai'): ?>
                <div class="ai-info">
                    <div class="ai-model">模型: qwen2.5:0.5b</div>
                    <div class="ai-description">
                        <p>这是一个基于Qwen2.5模型的AI助手，可以回答各种问题并进行对话。</p>
                        <?php if (!$ai_available): ?>
                            <div class="ai-warning">
                                <strong>注意:</strong> Ollama服务未启动，AI功能不可用。
                                <br>请确保Ollama正在运行在 localhost:11434
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="online-users" id="onlineUsers">
                    <?php foreach ($online_users as $user): ?>
                        <div class="user-item">
                            <span class="user-status"></span>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="sidebar-actions">
                <a href="servers.php" class="btn btn-secondary">返回服务器</a>
                <a href="logout.php" class="btn btn-secondary">退出</a>
            </div>
        </div>
        
        <div class="chat-main">
            <div class="chat-header">
                <h2>聊天室 - <?php echo htmlspecialchars($server_names[$current_server] ?? ucfirst($current_server)); ?></h2>
                <div class="user-welcome">
                    欢迎, <strong><?php echo htmlspecialchars($username); ?></strong>
                    <?php if ($current_server === 'ai'): ?>
                        <span class="ai-badge">AI助手模式</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="messages-container" id="messagesContainer">
                <div class="loading-message">加载消息中...</div>
            </div>
            
            <div class="message-input">
                <form id="messageForm">
                    <div class="input-wrapper">
                        <input type="text" id="messageInput" placeholder="<?php 
                            echo $current_server === 'ai' ? '向AI助手提问...' : '输入消息...';
                        ?>" maxlength="500" autocomplete="off">
                        <input type="file" id="fileUpload" style="display: none;" accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <label for="fileUpload" class="file-upload-btn">📎</label>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $current_server === 'ai' ? '发送给AI' : '发送'; ?>
                    </button>
                </form>
                <div id="filePreview" class="file-preview"></div>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>