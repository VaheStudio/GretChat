<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$server_config = json_decode(file_get_contents('server_config.json'), true);

// 初始化密码验证会话
if (!isset($_SESSION['server_passwords'])) {
    $_SESSION['server_passwords'] = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>选择服务器 - 聊天室</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 20px;
        border-radius: 8px;
        width: 300px;
        text-align: center;
    }
    .modal-content input {
        width: 100%;
        padding: 8px;
        margin: 10px 0;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .modal-content button {
        margin: 5px;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="actions">
                <a href="create_server.php" class="btn btn-primary">创建新服务器</a>
            </div>
            <h1>选择聊天服务器</h1>
            <div class="user-info">
                欢迎, <?php echo htmlspecialchars($_SESSION['user']); ?> 
                <a href="logout.php" class="btn btn-secondary">退出</a>
            </div>
        </div>
        
        <div class="servers-grid">
            <?php foreach ($server_config['servers'] as $server): 
                $has_password = !empty($server['password']);
                $is_verified = isset($_SESSION['server_passwords'][$server['id']]);
            ?>
                <div class="server-card">
                    <h3><?php echo htmlspecialchars($server['name']); ?></h3>
                    <p><?php echo htmlspecialchars($server['description']); ?></p>
                    <?php if ($has_password && !$is_verified): ?>
                        <!-- 需要密码验证的服务器 -->
                        <button class="btn btn-primary join-with-password" 
                                data-server-url="<?php echo htmlspecialchars($server['url']); ?>"
                                data-server-id="<?php echo htmlspecialchars($server['id']); ?>">
                            加入聊天（需要密码）
                        </button>
                    <?php else: ?>
                        <!-- 无需密码或已验证的服务器 -->
                        <a href="<?php echo htmlspecialchars($server['url']); ?>" class="btn btn-primary">
                            加入聊天
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 密码验证模态框 -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <h3>输入服务器密码</h3>
            <input type="password" id="serverPassword" placeholder="请输入密码">
            <input type="hidden" id="targetServerUrl">
            <input type="hidden" id="targetServerId">
            <button id="submitPassword" class="btn btn-primary">确认</button>
            <button id="cancelPassword" class="btn btn-secondary">取消</button>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('passwordModal');
        const serverPassword = document.getElementById('serverPassword');
        const targetServerUrl = document.getElementById('targetServerUrl');
        const targetServerId = document.getElementById('targetServerId');
        const submitPassword = document.getElementById('submitPassword');
        const cancelPassword = document.getElementById('cancelPassword');
        
        // 打开密码模态框
        document.querySelectorAll('.join-with-password').forEach(button => {
            button.addEventListener('click', function() {
                targetServerUrl.value = this.getAttribute('data-server-url');
                targetServerId.value = this.getAttribute('data-server-id');
                modal.style.display = 'block';
                serverPassword.focus();
            });
        });
        
        // 关闭模态框
        cancelPassword.addEventListener('click', function() {
            modal.style.display = 'none';
            serverPassword.value = '';
        });
        
        // 点击模态框外部关闭
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
                serverPassword.value = '';
            }
        });
        
        // 回车键提交
        serverPassword.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                submitPassword.click();
            }
        });
        
        // 提交密码验证
        submitPassword.addEventListener('click', async function() {
            const password = serverPassword.value;
            const url = targetServerUrl.value;
            const serverId = targetServerId.value;
            
            if (!password) {
                alert('请输入密码');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('server_url', url);
                formData.append('password', password);
                
                const response = await fetch('verify_server_password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 密码正确，跳转
                    window.location.href = url;
                } else {
                    alert(result.error || '密码错误，请重试');
                    serverPassword.value = '';
                    serverPassword.focus();
                }
            } catch (error) {
                console.error('验证失败:', error);
                alert('验证失败，请重试');
            }
        });
    });
    </script>
</body>
</html>