<?php
// backend/routes/api.php

error_reporting(E_ALL);
ini_set('display_errors', 0);          // Never leak HTML errors into JSON responses
ini_set('log_errors', 1);              // Still log them server-side

// Catch fatal errors and return JSON instead of an HTML crash page
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $err['message'],
            'file'    => basename($err['file']),
            'line'    => $err['line'],
        ]);
    }
});

set_exception_handler(function (Throwable $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ]);
    exit;
});

session_start();



require_once __DIR__ . '/../controllers/taskController.php';
require_once __DIR__ . '/../config/db.php';           // ✅ already correct
require_once __DIR__ . '/../controllers/authController.php'; // ✅
// CORS headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://localhost:8000',
    'http://127.0.0.1:8000',
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost:5173'
];
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// Helpers
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function requestBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void {
    if (!currentUser()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

// Controllers
$db = (new Database())->connect();
$authController = new AuthController($db);
$taskController  = new TaskController($db);

// Route handling
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$payload = requestBody();

switch ($action) {

    case 'register':
        if ($method !== 'POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
        jsonResponse($authController->register($payload));
        break;

    case 'login':
        if ($method !== 'POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
        jsonResponse($authController->login($payload));
        break;

    case 'logout':
        jsonResponse($authController->logout());
        break;

    case 'me':
        $user = currentUser();
        jsonResponse(['success'=>!!$user, 'user'=>$user], 200);
        break;

    // Products (admin only)
    case 'products':
        requireLogin();
        if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
            jsonResponse(['success'=>false,'message'=>'Forbidden'],403);
        }
        if ($method === 'GET') jsonResponse($taskController->getProducts());
        if ($method === 'POST') {
            if (isset($payload['id']) && (int)$payload['id'] > 0) {
                jsonResponse($taskController->updateProduct($payload));
            }
            jsonResponse($taskController->createProduct($payload));
        }
        jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
        break;

    // Sales
    case 'sales':
        requireLogin();
        if ($method === 'GET') jsonResponse($taskController->getSales());
        if ($method === 'POST') jsonResponse($taskController->createSale($payload));
        jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
        break;



    default:
        jsonResponse([
            'success'=>false,
            'message'=>'Unknown action',
            'available_actions'=>['register','login','logout','me','products','sales']
        ], 400);
        break;
}