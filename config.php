<?php
/**
 * Archivo de configuración principal - Redirige a la configuración unificada
 * Este archivo mantiene compatibilidad con archivos que requieren config.php en la raíz
 */

// Incluir la configuración principal del directorio config/
require_once __DIR__ . '/config/config.php';

// Mantener estas definiciones para compatibilidad con el sistema actual
if (!defined('APP_URL')) {
    define('APP_URL', BASE_URL);
}

if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', JWT_SECRET);
}

if (!defined('JWT_ALGORITHM')) {
    define('JWT_ALGORITHM', 'HS256');
}

if (!defined('JWT_EXPIRATION_TIME')) {
    define('JWT_EXPIRATION_TIME', JWT_EXPIRATION_HOURS * 60 * 60);
}

if (!defined('JWT_REFRESH_TIME')) {
    define('JWT_REFRESH_TIME', 7 * 24 * 60 * 60); // 7 días
}

if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', SESSION_TIMEOUT_MINUTES * 60);
}

if (!defined('MAX_LOGIN_ATTEMPTS')) {
    define('MAX_LOGIN_ATTEMPTS', 5);
}

if (!defined('LOGIN_LOCKOUT_TIME')) {
    define('LOGIN_LOCKOUT_TIME', 15 * 60);
}

if (!defined('LOG_AUTH_ATTEMPTS')) {
    define('LOG_AUTH_ATTEMPTS', true);
}

if (!defined('LOG_USER_ACTIONS')) {
    define('LOG_USER_ACTIONS', true);
}

if (!defined('PUBLIC_ROUTES')) {
    define('PUBLIC_ROUTES', [
        '/login.php',
        '/auth/login.php',
        '/auth/logout.php',
        '/auth/verify-token.php',
        '/assets/',
        '/vendor/'
    ]);
}

if (!defined('CORS_ALLOWED_ORIGINS')) {
    define('CORS_ALLOWED_ORIGINS', [
        BASE_URL
    ]);
}
?>
