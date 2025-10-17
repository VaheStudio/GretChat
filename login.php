<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: servers.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '请填写所有字段';
    } else {
        $users = json_decode(file_get_contents('user.json'), true) ?? [];
        $user_found = false;
        
        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                header('Location: servers.php');
                exit();
            }
        }
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 聊天室</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>登录</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="username" placeholder="用户名" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="密码" required>
                </div>
                <button type="submit" class="btn btn-primary">登录</button>
            </form>
            <p>还没有账号？ <a href="register.php">立即注册</a></p>
        </div>
    </div>
</body>
</html>