<?php
session_start();

// 允許跨域請求（如果需要的話）
header('Content-Type: application/json');

// 獲取POST數據
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 用戶數據存儲（記憶體中的陣列）
if (!isset($GLOBALS['users'])) {
    $GLOBALS['users'] = [];
}

// 響應函數
function response($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

// 檢查是否已登入
function checkLogin() {
    return isset($_SESSION['user']);
}

// 根據action執行對應操作
switch ($action) {
    case 'register':
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        // 驗證輸入
        if (strlen($username) < 5 || strlen($password) < 5) {
            response(false, [], '帳號和密碼都必須至少5個字元');
        }

        // 檢查帳號是否已存在
        foreach ($GLOBALS['users'] as $user) {
            if ($user['username'] === $username) {
                response(false, [], '帳號已存在');
            }
        }

        // 儲存用戶資料
        $GLOBALS['users'][] = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'registerTime' => date('Y-m-d H:i:s')
        ];

        response(true, [], '註冊成功');
        break;

    case 'login':
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        // 驗證輸入
        if (strlen($username) < 5 || strlen($password) < 5) {
            response(false, [], '帳號和密碼都必須至少5個字元');
        }

        // 檢查帳號密碼
        foreach ($GLOBALS['users'] as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $username;
                response(true, [], '登入成功');
            }
        }

        response(false, [], '帳號或密碼錯誤');
        break;

    case 'logout':
        session_destroy();
        response(true, [], '登出成功');
        break;

    case 'checkAuth':
        response(checkLogin());
        break;

    case 'getUsers':
        if (!checkLogin()) {
            response(false, [], '請先登入');
        }

        // 只返回用戶名和註冊時間
        $userList = array_map(function($user) {
            return [
                'username' => $user['username'],
                'registerTime' => $user['registerTime']
            ];
        }, $GLOBALS['users']);

        response(true, ['users' => $userList]);
        break;

    default:
        response(false, [], '無效的操作');
} 