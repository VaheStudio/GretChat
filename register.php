<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: servers.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = '请填写所有字段';
    } elseif ($password !== $confirm_password) {
        $error = '密码不匹配';
    } elseif (strlen($username) < 3) {
        $error = '用户名至少3个字符';
    } elseif (strlen($password) < 6) {
        $error = '密码至少6个字符';
    } else {
        $users = json_decode(file_get_contents('user.json'), true) ?? [];
        
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $error = '用户名已存在';
                break;
            }
        }
        
        if (!$error) {
            $new_user = [
                'id' => uniqid(),
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $users[] = $new_user;
            file_put_contents('user.json', json_encode($users, JSON_PRETTY_PRINT));
            $success = '注册成功！请登录';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 聊天室</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h1>注册</h1>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="username" placeholder="用户名" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="密码" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="确认密码" required>
                </div>
                <button type="submit" class="btn btn-primary">注册</button>
            </form>
            <p>已有账号？ <a href="login.php">立即登录</a></p>
        </div>
    </div>
</body>
</html>