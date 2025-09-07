<?php
// Cerrar sesión - Compatible con sistema actual
session_start();

header('Content-Type: application/json');

try {
    // Destruir todas las variables de sesión
    $_SESSION = array();

    // Si se desea destruir la sesión completamente, también hay que borrar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalmente, destruir la sesión
    session_destroy();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Sesión cerrada exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cerrar sesión'
    ]);
    error_log("Error en logout: " . $e->getMessage());
}
