<?php
session_start();

// 設置錯誤報告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 允許跨域請求
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 記錄所有請求
error_log("=== 新請求開始 ===");
error_log("請求方法: " . $_SERVER['REQUEST_METHOD']);
error_log("原始輸入: " . file_get_contents('php://input'));

// 獲取POST數據
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

error_log("解析後的輸入: " . print_r($input, true));
error_log("Action: " . $action);

// 用戶數據存儲（記憶體中的陣列）
if (!isset($GLOBALS['users'])) {
    $GLOBALS['users'] = [];
}

error_log("當前用戶數據: " . print_r($GLOBALS['users'], true));

// 響應函數
function response($success, $data = [], $message = '') {
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'debug' => [
            'session' => $_SESSION,
            'users' => $GLOBALS['users'],
            'request' => json_decode(file_get_contents('php://input'), true),
            'server' => $_SERVER
        ]
    ];
    
    error_log("響應內容: " . print_r($response, true));
    echo json_encode($response);
    exit;
}

// 檢查是否已登入
function checkLogin() {
    error_log("檢查登入狀態: " . print_r($_SESSION, true));
    return isset($_SESSION['user']);
}

// 根據action執行對應操作
switch ($action) {
    case 'register':
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        error_log("註冊嘗試 - 用戶名: " . $username);

        // 驗證輸入
        if (strlen($username) < 5 || strlen($password) < 5) {
            error_log("註冊失敗 - 帳號或密碼長度不足");
            response(false, [], '帳號和密碼都必須至少5個字元');
        }

        // 檢查帳號是否已存在
        foreach ($GLOBALS['users'] as $user) {
            if ($user['username'] === $username) {
                error_log("註冊失敗 - 帳號已存在");
                response(false, [], '帳號已存在');
            }
        }

        // 儲存用戶資料
        $GLOBALS['users'][] = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'registerTime' => date('Y-m-d H:i:s')
        ];

        error_log("註冊成功 - 用戶名: " . $username);
        error_log("當前用戶列表: " . print_r($GLOBALS['users'], true));

        response(true, [], '註冊成功');
        break;

    case 'login':
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        error_log("登入嘗試 - 用戶名: " . $username);
        error_log("當前用戶列表: " . print_r($GLOBALS['users'], true));

        // 驗證輸入
        if (strlen($username) < 5 || strlen($password) < 5) {
            error_log("登入失敗 - 帳號或密碼長度不足");
            response(false, [], '帳號和密碼都必須至少5個字元');
        }

        // 檢查帳號密碼
        foreach ($GLOBALS['users'] as $user) {
            error_log("檢查用戶: " . $user['username']);
            if ($user['username'] === $username) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $username;
                    error_log("登入成功 - 用戶名: " . $username);
                    response(true, [], '登入成功');
                } else {
                    error_log("登入失敗 - 密碼錯誤");
                    response(false, [], '密碼錯誤');
                }
            }
        }

        error_log("登入失敗 - 找不到用戶");
        response(false, [], '帳號不存在');
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

        $userList = array_map(function($user) {
            return [
                'username' => $user['username'],
                'registerTime' => $user['registerTime']
            ];
        }, $GLOBALS['users']);

        response(true, ['users' => $userList]);
        break;

    default:
        error_log("無效的操作: " . $action);
        response(false, [], '無效的操作');
} 
