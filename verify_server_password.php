<?php
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
?>