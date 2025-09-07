<?php
require_once '../conexion.php';
require_once '../src/Auth/JWTAuth.php';

use POS\Auth\JWTAuth;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$login = trim($input['login'] ?? $_POST['login'] ?? '');
$password = $input['password'] ?? $_POST['password'] ?? '';
$remember = isset($input['remember']) ? (bool)$input['remember'] : false;

if (empty($login) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Login y contraseña son requeridos'
    ]);
    exit;
}

try {
    $pdo = conexion();
    $jwtAuth = new JWTAuth($pdo);
    
    $result = $jwtAuth->login($login, $password, $remember);
    
    if ($result['success']) {
        // Establecer cookie con el token
        if ($remember && isset($result['refresh_token'])) {
            setcookie('jwt_refresh_token', $result['refresh_token'], time() + JWT_REFRESH_TIME, '/', '', false, true);
        }
        
        setcookie('jwt_token', $result['access_token'], $remember ? time() + JWT_EXPIRATION_TIME : 0, '/', '', false, true);
        
        // Establecer sesión
        $_SESSION['user_data'] = [
            'user_id' => $result['user']['id'],
            'username' => $result['user']['username'],
            'rol' => $result['user']['rol_nombre'],
            'permisos' => json_decode($result['user']['permisos'], true)
        ];
        $_SESSION['authenticated'] = true;
        
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
    error_log("Error en login: " . $e->getMessage());
}
