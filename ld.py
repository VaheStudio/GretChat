import os
import json
import shutil

def backup_original_files():
    """备份原始文件"""
    if not os.path.exists('backup'):
        os.makedirs('backup')
    
    files_to_backup = [
        'chat.php', 'ai_chat.php', 'script.js', 'styles.css',
        'servers.php', 'server_config.json', 'mkuser.py'
    ]
    
    for file in files_to_backup:
        if os.path.exists(file):
            shutil.copy2(file, f'backup/{file}')
            print(f"已备份: {file}")

def update_server_config_json():
    """更新服务器配置文件结构，添加密码相关字段"""
    if not os.path.exists('server_config.json'):
        print("server_config.json 不存在，将创建新文件")
        config = {"servers": []}
    else:
        with open('server_config.json', 'r', encoding='utf-8') as f:
            config = json.load(f)
    
    # 为每个服务器添加password和created_by字段
    for server in config.get('servers', []):
        if 'password' not in server:
            server['password'] = ""
        if 'created_by' not in server:
            server['created_by'] = "system"
    
    with open('server_config.json', 'w', encoding='utf-8') as f:
        json.dump(config, f, ensure_ascii=False, indent=2)
    
    print("已更新 server_config.json")

def create_create_server_php():
    """创建创建服务器页面"""
    content = """<?php
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
</html>"""

    with open('create_server.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("已创建 create_server.php")

def create_verify_password_php():
    """创建密码验证处理文件"""
    content = """<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit();
}

$server_url = $_POST['server_url'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($server_url) || empty($password)) {
    echo json_encode(['success' => false, 'error' => '参数不完整']);
    exit();
}

// 解析服务器ID
$url_parts = parse_url($server_url);
parse_str($url_parts['query'] ?? '', $query_params);
$server_id = $query_params['server'] ?? '';

if (empty($server_id)) {
    echo json_encode(['success' => false, 'error' => '无效的服务器']);
    exit();
}

// 验证密码
$server_config = json_decode(file_get_contents('server_config.json'), true);
foreach ($server_config['servers'] as $server) {
    if ($server['id'] === $server_id && !empty($server['password'])) {
        if (password_verify($password, $server['password'])) {
            // 密码验证成功，记录会话
            $_SESSION['server_passwords'][$server_id] = true;
            echo json_encode(['success' => true]);
            exit();
        } else {
            echo json_encode(['success' => false, 'error' => '密码错误']);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'error' => '服务器不存在或不需要密码']);
exit();
?>"""

    with open('verify_server_password.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("已创建 verify_server_password.php")

def update_servers_php():
    """更新服务器选择页面，添加创建服务器入口和密码验证"""
    if not os.path.exists('servers.php'):
        print("servers.php 不存在，无法更新")
        return
    
    with open('servers.php', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 添加创建服务器按钮
    if '<div class="header">' in content and '创建新服务器' not in content:
        content = content.replace(
            '<div class="header">',
            '<div class="header">\n            <div class="actions">\n                <a href="create_server.php" class="btn btn-primary">创建新服务器</a>\n            </div>'
        )
    
    # 修改服务器卡片，添加密码验证
    if 'server-card' in content and 'join-with-password' not in content:
        # 替换服务器链接部分
        old_card = '''<div class="server-card">
                <h3><?php echo htmlspecialchars($server['name']); ?></h3>
                <p><?php echo htmlspecialchars($server['description']); ?></p>
                <a href="<?php echo htmlspecialchars($server['url']); ?>" class="btn btn-primary">
                    加入聊天
                </a>
            </div>'''
        
        new_card = '''<div class="server-card">
                <h3><?php echo htmlspecialchars($server['name']); ?></h3>
                <p><?php echo htmlspecialchars($server['description']); ?></p>
                <?php if (!empty($server['password'])): ?>
                    <button class="btn btn-primary join-with-password" 
                            data-server-id="<?php echo htmlspecialchars($server['id']); ?>"
                            data-server-url="<?php echo htmlspecialchars($server['url']); ?>">
                        加入聊天（需要密码）
                    </button>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($server['url']); ?>" class="btn btn-primary">
                        加入聊天
                    </a>
                <?php endif; ?>
            </div>'''
        
        content = content.replace(old_card, new_card)
    
    # 添加密码验证模态框
    if '</body>' in content and 'passwordModal' not in content:
        modal_html = '''
    <!-- 密码验证模态框 -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <h3>输入服务器密码</h3>
            <input type="password" id="serverPassword" placeholder="请输入密码">
            <input type="hidden" id="targetServerUrl">
            <button id="submitPassword" class="btn btn-primary">确认</button>
            <button id="cancelPassword" class="btn btn-secondary">取消</button>
        </div>
    </div>

    <script>
    // 密码验证相关JS
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('passwordModal');
        const serverPassword = document.getElementById('serverPassword');
        const targetServerUrl = document.getElementById('targetServerUrl');
        const submitPassword = document.getElementById('submitPassword');
        const cancelPassword = document.getElementById('cancelPassword');
        
        // 打开密码模态框
        document.querySelectorAll('.join-with-password').forEach(button => {
            button.addEventListener('click', function() {
                targetServerUrl.value = this.getAttribute('data-server-url');
                modal.style.display = 'block';
                serverPassword.focus();
            });
        });
        
        // 关闭模态框
        cancelPassword.addEventListener('click', function() {
            modal.style.display = 'none';
            serverPassword.value = '';
        });
        
        // 提交密码验证
        submitPassword.addEventListener('click', async function() {
            const password = serverPassword.value;
            const url = targetServerUrl.value;
            
            if (!password) return;
            
            try {
                const response = await fetch('verify_server_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `server_url=${encodeURIComponent(url)}&password=${encodeURIComponent(password)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 密码正确，跳转
                    window.location.href = url;
                } else {
                    alert('密码错误，请重试');
                }
            } catch (error) {
                console.error('验证失败:', error);
            }
        });
    });
    </script>'''
        content = content.replace('</body>', modal_html + '\n</body>')
    
    with open('servers.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("已更新 servers.php")

def update_chat_php():
    """更新聊天页面，添加文件上传功能"""
    # 更新 chat.php
    if os.path.exists('chat.php'):
        with open('chat.php', 'r', encoding='utf-8') as f:
            content = f.read()
        
        # 修改消息输入区域
        old_input = '''<div class="message-input">
                <form id="messageForm">
                    <input type="text" id="messageInput" placeholder="输入消息..." maxlength="500" required autocomplete="off">
                    <button type="submit" class="btn btn-primary">发送</button>
                </form>
            </div>'''
        
        new_input = '''<div class="message-input">
                <form id="messageForm">
                    <div class="input-wrapper">
                        <input type="text" id="messageInput" placeholder="输入消息..." maxlength="500" autocomplete="off">
                        <input type="file" id="fileUpload" style="display: none;" accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt">
                        <label for="fileUpload" class="file-upload-btn">📎</label>
                    </div>
                    <button type="submit" class="btn btn-primary">发送</button>
                </form>
                <div id="filePreview" class="file-preview"></div>
            </div>'''
        
        content = content.replace(old_input, new_input)
        
        # 添加文件上传处理代码
        if '处理文件上传' not in content:
            # 找到消息处理部分并添加文件上传代码
            if '// 处理消息发送' in content:
                file_process_code = '''
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
        if (!empty($message) || !empty($file_url)) {'''
                
                content = content.replace(
                    '// 处理消息发送',
                    '// 处理消息发送\n' + file_process_code
                )
                
                # 更新消息数据
                if "'message' => $message," in content:
                    content = content.replace(
                        "'message' => $message,",
                        "'message' => $message,\n            'file_url' => $file_url,\n            'file_name' => $file ? $file['name'] : '',"
                    )
                
                # 更新空消息检查
                if "if (!empty($message)) {" in content:
                    content = content.replace(
                        "if (!empty($message)) {",
                        "if (!empty($message) || !empty($file_url)) {"
                    )
        
        with open('chat.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print("已更新 chat.php")
    
    # 更新 ai_chat.php
    if os.path.exists('ai_chat.php'):
        with open('ai_chat.php', 'r', encoding='utf-8') as f:
            content = f.read()
        
        # 修改消息输入区域
        old_ai_input = '''<div class="message-input">
                <form id="messageForm">
                    <input type="text" id="messageInput" placeholder="<?php 
                        echo $current_server === 'ai' ? '向AI助手提问...' : '输入消息...';
                    ?>" maxlength="500" required autocomplete="off">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $current_server === 'ai' ? '发送给AI' : '发送'; ?>
                    </button>
                </form>
            </div>'''
        
        new_ai_input = '''<div class="message-input">
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
            </div>'''
        
        content = content.replace(old_ai_input, new_ai_input)
        
        # 添加文件上传处理代码
        if '处理文件上传' not in content:
            if '// 处理消息发送' in content:
                file_process_code = '''
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
        if (!empty($message) || !empty($file_url)) {'''
                
                content = content.replace(
                    '// 处理消息发送',
                    '// 处理消息发送\n' + file_process_code
                )
                
                # 更新消息数据
                if "'message' => $message," in content:
                    content = content.replace(
                        "'message' => $message,",
                        "'message' => $message,\n            'file_url' => $file_url,\n            'file_name' => $file ? $file['name'] : '',"
                    )
        
        with open('ai_chat.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print("已更新 ai_chat.php")

def update_script_js():
    """更新脚本文件，添加文件上传处理"""
    if not os.path.exists('script.js'):
        print("script.js 不存在，无法更新")
        return
    
    with open('script.js', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 添加文件上传初始化
    if 'this.init();' in content and 'this.setupFileUpload();' not in content:
        content = content.replace(
            'this.init();',
            'this.init();\n        this.setupFileUpload();'
        )
    
    # 添加文件上传相关方法
    if 'setupFileUpload' not in content:
        file_methods = '''
    setupFileUpload() {
        const fileUpload = document.getElementById('fileUpload');
        const filePreview = document.getElementById('filePreview');
        
        fileUpload.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                this.showFilePreview(file);
            }
        });
    }

    showFilePreview(file) {
        const filePreview = document.getElementById('filePreview');
        filePreview.innerHTML = `
            <div class="preview-item">
                <span>${file.name} (${this.formatFileSize(file.size)})</span>
                <button class="remove-file">×</button>
            </div>
        `;
        
        // 添加移除文件功能
        document.querySelector('.remove-file').addEventListener('click', () => {
            filePreview.innerHTML = '';
            document.getElementById('fileUpload').value = '';
        });
    }

    formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        else return (bytes / 1048576).toFixed(1) + ' MB';
    }
    
    getFileIcon(fileName) {
        const ext = fileName.split('.').pop().toLowerCase();
        
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) {
            return '<span class="file-icon">🖼️</span>';
        } else if (['pdf'].includes(ext)) {
            return '<span class="file-icon">📄</span>';
        } else if (['doc', 'docx'].includes(ext)) {
            return '<span class="file-icon">📝</span>';
        } else if (['xls', 'xlsx'].includes(ext)) {
            return '<span class="file-icon">📊</span>';
        } else {
            return '<span class="file-icon">📎</span>';
        }
    }'''
        
        # 插入到类中
        if 'class ChatApp {' in content:
            content = content.replace(
                'class ChatApp {',
                'class ChatApp {' + file_methods
            )
    
    # 更新sendMessage方法
    if 'async sendMessage() {' in content and 'fileInput' not in content:
        # 更新消息和文件检查
        content = content.replace(
            'const message = this.messageInput.value.trim();',
            'const message = this.messageInput.value.trim();\n        const fileInput = document.getElementById(\'fileUpload\');\n        const file = fileInput.files[0];'
        )
        
        content = content.replace(
            'if (!message) return;',
            '// 消息和文件不能同时为空\n        if (!message && !file) return;'
        )
        
        # 更新FormData
        content = content.replace(
            'const formData = new FormData();\n            formData.append(\'message\', message);',
            'const formData = new FormData();\n            if (message) formData.append(\'message\', message);\n            if (file) formData.append(\'file\', file);'
        )
        
        # 更新成功处理
        content = content.replace(
            'this.messageInput.value = \'\';',
            'this.messageInput.value = \'\';\n            fileInput.value = \'\';\n            document.getElementById(\'filePreview\').innerHTML = \'\';'
        )
    
    # 更新displayMessages方法以显示文件
    if 'displayMessages(messages)' in content and 'message.file_url' not in content:
        # 找到消息内容处理部分
        content = content.replace(
            'let messageContent = this.escapeHtml(message.message);',
            'let messageContent = this.escapeHtml(message.message);\n\n        // 添加文件显示\n        if (message.file_url) {\n            const fileIcon = this.getFileIcon(message.file_name);\n            messageContent += `<div class="message-file">\n                ${fileIcon}\n                <a href="${this.escapeHtml(message.file_url)}" target="_blank" download="${this.escapeHtml(message.file_name)}">\n                    ${this.escapeHtml(message.file_name)}\n                </a>\n            </div>`;\n        }'
        )
    
    with open('script.js', 'w', encoding='utf-8') as f:
        f.write(content)
    print("已更新 script.js")

def update_styles_css():
    """更新样式表，添加文件上传和模态框样式"""
    if not os.path.exists('styles.css'):
        print("styles.css 不存在，无法更新")
        return
    
    with open('styles.css', 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 添加模态框样式
    if '.modal {' not in content:
        modal_css = '''
/* 模态框样式 */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: var(--bg-secondary);
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
}

.modal-content input {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    background: var(--bg-tertiary);
    border: 1px solid #444;
    border-radius: 5px;
    color: var(--text-primary);
}'''
        content += modal_css
    
    # 添加文件上传样式
    if '.file-upload-btn {' not in content:
        file_css = '''
/* 文件上传样式 */
.input-wrapper {
    display: flex;
    flex: 1;
}

.file-upload-btn {
    padding: 0 10px;
    background: var(--bg-tertiary);
    border: 1px solid #444;
    border-left: none;
    border-radius: 0 5px 5px 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-preview {
    margin-top: 10px;
    padding: 10px;
    background: var(--bg-tertiary);
    border-radius: 5px;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.remove-file {
    background: none;
    border: none;
    color: var(--error);
    cursor: pointer;
    font-size: 18px;
    padding: 0 5px;
}

/* 消息中的文件样式 */
.message-file {
    margin-top: 5px;
    padding: 5px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.file-icon {
    font-size: 18px;
}

.message-file a {
    color: var(--accent-primary);
    text-decoration: none;
}

.message-file a:hover {
    text-decoration: underline;
}'''
        content += file_css
    
    with open('styles.css', 'w', encoding='utf-8') as f:
        f.write(content)
    print("已更新 styles.css")

def create_uploads_directory():
    """创建文件上传目录"""
    if not os.path.exists('uploads'):
        os.makedirs('uploads', 0o755)
        print("已创建 uploads 目录")
    else:
        print("uploads 目录已存在")

def main():
    print("开始执行一键配置脚本...")
    print("1. 备份原始文件...")
    backup_original_files()
    
    print("\n2. 更新服务器配置文件...")
    update_server_config_json()
    
    print("\n3. 创建新文件...")
    create_create_server_php()
    create_verify_password_php()
    create_uploads_directory()
    
    print("\n4. 更新现有文件...")
    update_servers_php()
    update_chat_php()
    update_script_js()
    update_styles_css()
    
    print("\n所有操作已完成！")
    print("注意事项：")
    print("- 原始文件已备份到 backup 目录")
    print("- 请确保 uploads 目录有写入权限")
    print("- 如有问题，请使用备份文件恢复")

if __name__ == "__main__":
    main()