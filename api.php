<?php
session_start();

// 允許跨域請求（如果需要的話）
header('Content-Type: application/json');

// 記錄請求
error_log("Request received: " . file_get_contents('php://input'));

// 獲取POST數據
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 記錄action
error_log("Action: " . $action);

// 用戶數據存儲（記憶體中的陣列）
if (!isset($GLOBALS['users'])) {
    $GLOBALS['users'] = [];
}

// 響應函數
function response($success, $data = [], $message = '') {
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'debug' => [
            'registered_users' => $GLOBALS['users'],
            'session' => $_SESSION,
            'request' => json_decode(file_get_contents('php://input'), true)
        ]
    ];
    
    // 記錄響應
    error_log("Response: " . json_encode($response));
    
    echo json_encode($response);
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

        error_log("Attempting to register user: " . $username);

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

        error_log("User registered successfully: " . $username);
        error_log("Current users: " . json_encode($GLOBALS['users']));

        response(true, [], '註冊成功');
        break;

    case 'login':
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        error_log("Attempting to login user: " . $username);
        error_log("Current users in system: " . json_encode($GLOBALS['users']));

        // 驗證輸入
        if (strlen($username) < 5 || strlen($password) < 5) {
            response(false, [], '帳號和密碼都必須至少5個字元');
        }

        // 檢查帳號密碼
        foreach ($GLOBALS['users'] as $user) {
            error_log("Checking against user: " . $user['username']);
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $username;
                error_log("Login successful for user: " . $username);
                response(true, [], '登入成功');
            }
        }

        error_log("Login failed for user: " . $username);
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
