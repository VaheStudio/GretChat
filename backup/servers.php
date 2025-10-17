<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$server_config = json_decode(file_get_contents('server_config.json'), true);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>选择服务器 - 聊天室</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>选择聊天服务器</h1>
            <div class="user-info">
                欢迎, <?php echo htmlspecialchars($_SESSION['user']); ?> 
                <a href="logout.php" class="btn btn-secondary">退出</a>
            </div>
        </div>
        
        <div class="servers-grid">
            <?php foreach ($server_config['servers'] as $server): ?>
                <div class="server-card">
                    <h3><?php echo htmlspecialchars($server['name']); ?></h3>
                    <p><?php echo htmlspecialchars($server['description']); ?></p>
                    <a href="<?php echo htmlspecialchars($server['url']); ?>" class="btn btn-primary">
                        加入聊天
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>