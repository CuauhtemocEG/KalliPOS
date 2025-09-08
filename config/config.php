<?php
/**
 * Archivo de configuración global del sistema POS
 * Contiene todas las variables de entorno, API keys y configuraciones
 */

if (!defined('LOG_AUTH_ATTEMPTS')) {
    define('LOG_AUTH_ATTEMPTS', true);
}

// Configuración de la base de datos
define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'kallijag_pos');
define('DB_USER', 'kallijag_pos');
define('DB_PASS', '{&<eXA[x$?_q\<N');
define('DB_CHARSET', 'utf8mb4');

// URLs del sistema
define('BASE_URL', 'https://gastos.kallijaguar-inventory.com/');
define('API_BASE_URL', BASE_URL . 'api/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Configuración del sistema
define('APP_NAME', 'Kalli Jaguar POS');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/Mexico_City');

// Configuración de seguridad
define('JWT_SECRET', 'kalli_jaguar_pos_2025_secret_key_super_secure_production_v1_123456789');
define('JWT_SECRET_KEY', JWT_SECRET); // Alias para compatibilidad
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_HOURS', 24);
define('JWT_EXPIRATION_TIME', JWT_EXPIRATION_HOURS * 60 * 60); // Tiempo en segundos
define('JWT_REFRESH_TIME', 7 * 24 * 60 * 60); // 7 días para refresh token
define('SESSION_TIMEOUT_MINUTES', 120);

// Configuración de archivos
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Configuración de desarrollo/producción
define('DEBUG_MODE', true); // Cambiar a false en producción
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR
define('LOG_PATH', __DIR__ . '/../logs/');

// Configuración de cache
define('CACHE_ENABLED', true);
define('CACHE_TTL_SECONDS', 3600); // 1 hora

// Configuración de la aplicación
date_default_timezone_set(APP_TIMEZONE);

// Configuración de errores según el modo
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
}

/**
 * Función helper para obtener configuraciones del entorno
 */
function getEnvConfig($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

/**
 * Función helper para verificar si estamos en modo debug
 */
function isDebugMode() {
    return DEBUG_MODE === true;
}

/**
 * Función helper para obtener la URL base
 */
function getBaseUrl() {
    return BASE_URL;
}

/**
 * Función helper para generar URLs completas
 */
function url($path = '') {
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $path ? $base . '/' . $path : $base . '/';
}

/**
 * Función helper para generar URLs de assets
 */
function asset($path = '') {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Función helper para generar URLs de API
 */
function apiUrl($path = '') {
    return url('api/' . ltrim($path, '/'));
}
?>
