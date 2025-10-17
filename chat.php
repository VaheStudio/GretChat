<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$current_server = $_GET['server'] ?? 'main';
$username = $_SESSION['user'];
$user_id = $_SESSION['user_id'];

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
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
        
        // 确保文件可写
        if (file_put_contents('messages.jsonl', json_encode($message_data) . "\n", FILE_APPEND | LOCK_EX) === false) {
            error_log("无法写入消息文件");
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
    <title>聊天室(DeepLora提供维护) - <?php echo htmlspecialchars($current_server === 'main' ? '主聊天室' : $current_server); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h3>在线用户</h3>
                <span class="online-count"><?php echo count($online_users); ?> 人在线</span>
            </div>
            <div class="online-users" id="onlineUsers">
                <?php foreach ($online_users as $user): ?>
                    <div class="user-item">
                        <span class="user-status"></span>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="sidebar-actions">
                <a href="servers.php" class="btn btn-secondary">返回服务器</a>
                <a href="logout.php" class="btn btn-secondary">退出</a>
            </div>
        </div>
        
        <div class="chat-main">
            <div class="chat-header">
                <h2>聊天室(DeepLora提供维护) - <?php echo htmlspecialchars($current_server === 'main' ? '主聊天室' : ucfirst($current_server)); ?></h2>
                <div class="user-welcome">
                    欢迎, <strong><?php echo htmlspecialchars($username); ?></strong>
                </div>
            </div>
            
            <div class="messages-container" id="messagesContainer">
                <!-- 消息将通过JavaScript动态加载 -->
                <div class="loading-message">说两句吧...</div>
            </div>
            
            <div class="message-input">
                <form id="messageForm">
                    <div class="input-wrapper">
                        <input type="text" id="messageInput" placeholder="输入消息..." maxlength="500" autocomplete="off">
                        <input type="file" id="fileUpload" style="display: none;" accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <label for="fileUpload" class="file-upload-btn">📎</label>
                    </div>
                    <button type="submit" class="btn btn-primary">发送</button>
                </form>
                <div id="filePreview" class="file-preview"></div>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>