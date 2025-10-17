#!/bin/bash
echo "正在启动PHP聊天应用..."

# 检查是否安装了PHP
if ! command -v php &> /dev/null; then
    echo "正在安装PHP..."
    sudo apt-get update
    sudo apt-get install -y php
fi

# 设置文件权限
chmod 666 user.json messages.jsonl server_config.json 2>/dev/null || true

# 创建初始文件（如果不存在）
[ -f user.json ] || echo "[]" > user.json
[ -f messages.jsonl ] || touch messages.jsonl
[ -f server_config.json ] || cat > server_config.json << 'EOF'
{
    "servers": [
        {
            "id": "server1",
            "name": "主聊天室",
            "url": "chat.php",
            "description": "主要聊天服务器"
        },
        {
            "id": "server2", 
            "name": "游戏讨论",
            "url": "chat.php?server=game",
            "description": "游戏爱好者的聚集地"
        },
        {
            "id": "server3",
            "name": "技术交流",
            "url": "chat.php?server=tech",
            "description": "编程和技术讨论"
        },
        {
            "id": "ai_chat",
            "name": "AI智能助手",
            "url": "chat.php?server=ai",
            "description": "与AI助手对话 (使用Qwen2.5模型)"
        }
    ]
}
EOF

# 启动PHP内置服务器
echo "启动PHP服务器在端口 $PORT"
php -S 0.0.0.0:$PORT