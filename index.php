<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: servers.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>多人聊天室</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>欢迎来到聊天室</h1>
            <div class="button-group">
                <a href="login.php" class="btn btn-primary">登录</a>
                <a href="register.php" class="btn btn-secondary">注册</a>
            </div>
        </div>
    </div>
</body>
</html>