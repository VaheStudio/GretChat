#!/usr/bin/env python3
import os
import subprocess
import sys

def setup_php_environment():
    """设置PHP环境"""
    print("设置PHP聊天应用环境...")
    
    # 检查PHP是否安装
    try:
        subprocess.run(["php", "-v"], check=True, capture_output=True)
        print("✓ PHP 已安装")
    except (subprocess.CalledProcessError, FileNotFoundError):
        print("✗ PHP 未安装，尝试安装...")
        # 在Render环境中，PHP通常已预装
        sys.exit(1)
    
    # 设置文件权限
    files_to_chmod = ['user.json', 'messages.jsonl', 'server_config.json']
    for file in files_to_chmod:
        if os.path.exists(file):
            os.chmod(file, 0o666)
            print(f"✓ 设置 {file} 权限")
    
    print("环境设置完成！")

if __name__ == "__main__":
    setup_php_environment()