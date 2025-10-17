class ChatApp {
    constructor() {
        this.messagesContainer = document.getElementById('messagesContainer');
        this.messageForm = document.getElementById('messageForm');
        this.messageInput = document.getElementById('messageInput');
        this.onlineUsers = document.getElementById('onlineUsers');
        this.currentServer = this.getCurrentServer();
        this.isSending = false;
        
        this.init();
        this.setupFileUpload();
    }
    
    init() {
        this.loadMessages();
        this.setupEventListeners();
        this.startAutoRefresh();
    }
    
    getCurrentServer() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('server') || 'main';
    }
    
    setupEventListeners() {
        this.messageForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // 输入框键盘事件
        this.messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
    }
    
    async sendMessage() {
        if (this.isSending) return;
        
        const message = this.messageInput.value.trim();
        const fileInput = document.getElementById('fileUpload');
        const file = fileInput.files[0];
        // 消息和文件不能同时为空
        if (!message && !file) return;
        
        this.isSending = true;
        
        try {
            const formData = new FormData();
            if (message) formData.append('message', message);
            if (file) formData.append('file', file);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.messageInput.value = '';
            fileInput.value = '';
            document.getElementById('filePreview').innerHTML = '';
                this.loadMessages(); // 立即刷新消息
            } else {
                console.error('发送失败:', result.error);
                alert('发送消息失败: ' + result.error);
            }
        } catch (error) {
            console.error('发送消息失败:', error);
            alert('发送消息失败，请检查网络连接');
        } finally {
            this.isSending = false;
        }
    }
    
    async loadMessages() {
        try {
            const response = await fetch(`?action=get_messages&server=${this.currentServer}&t=${Date.now()}`);
            if (!response.ok) {
                throw new Error('网络响应不正常');
            }
            
            const text = await response.text();
            
            if (text.trim()) {
                const lines = text.trim().split('\n');
                const messages = lines.map(line => {
                    try {
                        return JSON.parse(line);
                    } catch (e) {
                        console.error('解析消息失败:', e, '原始数据:', line);
                        return null;
                    }
                }).filter(msg => msg !== null);
                
                this.displayMessages(messages);
            } else {
                this.displayNoMessages();
            }
        } catch (error) {
            console.error('加载消息失败:', error);
            this.messagesContainer.innerHTML = '<div class="error-message">加载消息失败，请刷新页面</div>';
        }
    }
    
displayMessages(messages) {
    if (messages.length === 0) {
        this.displayNoMessages();
        return;
    }
    
    // 获取当前用户名（从PHP会话）
    const currentUser = '<?php echo $_SESSION["user"]; ?>';
    const fragment = document.createDocumentFragment();
    
    messages.forEach(message => {
        const messageDiv = document.createElement('div');
        
        // 根据消息类型设置不同的样式类
        let messageClass = 'message';
        if (message.username === currentUser) {
            messageClass += ' own';
        }
        if (message.username === 'AI助手') {
            messageClass += ' ai-response';
        }
        if (message.username === '系统提示') {
            messageClass += ' system-message';
        }
        
        messageDiv.className = messageClass;
        messageDiv.dataset.messageId = message.id;
        
        let usernameDisplay = this.escapeHtml(message.username);
        if (message.username === 'AI助手' && message.ai_model) {
            usernameDisplay += ` <small>(${this.escapeHtml(message.ai_model)})</small>`;
        }
        
        messageDiv.innerHTML = `
            <div class="message-header">
                <span class="message-username">${usernameDisplay}</span>
                <span class="message-time">${this.formatTime(message.timestamp)}</span>
            </div>
            <div class="message-content">${this.escapeHtml(message.message)}</div>
        `;
        
        fragment.appendChild(messageDiv);
    });
    
    this.messagesContainer.innerHTML = '';
    this.messagesContainer.appendChild(fragment);
    this.scrollToBottom();
}
    
    displayNoMessages() {
        this.messagesContainer.innerHTML = `
            <div class="no-messages">
                <p>还没有消息，开始第一个对话吧！</p>
            </div>
        `;
    }
    
    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp * 1000);
        const now = new Date();
        const diff = now - date;
        
        // 如果是今天的消息，显示时间
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString('zh-CN', {
                hour: '2-digit',
                minute: '2-digit'
            });
        } else {
            // 如果是昨天的消息
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            if (date.toDateString() === yesterday.toDateString()) {
                return '昨天 ' + date.toLocaleTimeString('zh-CN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } else {
                // 更早的消息显示日期
                return date.toLocaleDateString('zh-CN') + ' ' + date.toLocaleTimeString('zh-CN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    startAutoRefresh() {
        // 每3秒刷新一次消息
        setInterval(() => {
            this.loadMessages();
        }, 3000);
    }
    
    setupFileUpload() {
        const fileInput = document.getElementById('fileUpload');
        const filePreview = document.getElementById('filePreview');
        if (fileInput && filePreview) {
            fileInput.addEventListener('change', () => {
                const file = fileInput.files[0];
                if (file) {
                    filePreview.innerHTML = `已选择文件: ${file.name}`;
                } else {
                    filePreview.innerHTML = '';
                }
            });
        }
    }
}

// 页面加载完成后初始化聊天应用
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('messagesContainer')) {
        new ChatApp();
    }
});

// 添加一些工具函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}