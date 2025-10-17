<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $server_name = trim($_POST['server_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($server_name)) {
        $error = '请输入服务器名称';
    } else {
        $server_config = json_decode(file_get_contents('server_config.json'), true);
        
        // 生成唯一ID
        $server_id = 'server_' . uniqid();
        
        // 新服务器配置
        $new_server = [
            'id' => $server_id,
            'name' => $server_name,
            'url' => "chat.php?server=" . $server_id,
            'description' => $description,
            'password' => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '',
            'created_by' => $_SESSION['user_id']
        ];
        
        $server_config['servers'][] = $new_server;
        file_put_contents('server_config.json', json_encode($server_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $success = '服务器创建成功！';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>创建服务器 - 聊天室</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>创建新服务器</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="server_name" placeholder="服务器名称" required>
                </div>
                <div class="form-group">
                    <textarea name="description" placeholder="服务器描述" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="服务器密码（可选）">
                </div>
                <button type="submit" class="btn btn-primary">创建服务器</button>
                <a href="servers.php" class="btn btn-secondary">取消</a>
            </form>
        </div>
    </div>
</body>
</html>