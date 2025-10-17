import os

# 创建项目目录结构
project_structure = {
    'index.php': '',
    'login.php': '',
    'register.php': '',
    'chat.php': '',
    'logout.php': '',
    'servers.php': '',
    'styles.css': '',
    'script.js': '',
    'user.json': '[]',
    'messages.jsonl': '',
    'server_config.json': ''
}

# 创建文件和目录
for filename, content in project_structure.items():
    with open(filename, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Created: {filename}')

print("Project structure created successfully!")