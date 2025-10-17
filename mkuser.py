#!/usr/bin/env python3
import json
import os

def create_initial_files():
    # 创建空的用户文件
    with open('user.json', 'w', encoding='utf-8') as f:
        json.dump([], f, ensure_ascii=False, indent=2)
    
    # 创建空的消息文件
    open('messages.jsonl', 'w').close()
    
    # 创建服务器配置文件
    server_config = {
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
            }
        ]
    }
    
    with open('server_config.json', 'w', encoding='utf-8') as f:
        json.dump(server_config, f, ensure_ascii=False, indent=2)
    
    print("初始文件创建完成！")

if __name__ == "__main__":
    create_initial_files()