<?php
/**
 * Archivo de configuración global del sistema POS
 * Contiene todas las variables de entorno, API keys y configuraciones
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'kallijag_pos_stage');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// URLs del sistema
define('BASE_URL', 'https://gastos.kallijaguar-inventory.com/');
define('API_BASE_URL', BASE_URL . 'api/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Configuración de Twilio para SMS
define('TWILIO_ACCOUNT_SID', ''); // Tu Account SID de Twilio
define('TWILIO_AUTH_TOKEN', ''); // Tu Auth Token de Twilio
define('TWILIO_PHONE_NUMBER', ''); // Tu número de Twilio (formato: +1234567890)

// Configuración de SendGrid para emails de respaldo
define('SENDGRID_API_KEY', ''); // Tu API Key de SendGrid
define('SENDGRID_FROM_EMAIL', ''); // Email verificado en SendGrid
define('SENDGRID_FROM_NAME', 'Sistema POS'); // Nombre del remitente

// Configuración del sistema
define('APP_NAME', 'Restaurant POS');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/Mexico_City');

// Configuración de seguridad
define('JWT_SECRET', 'tu_clave_secreta_jwt_muy_segura_cambiar_en_produccion');
define('JWT_EXPIRATION_HOURS', 24);
define('SESSION_TIMEOUT_MINUTES', 120);

// Configuración de archivos
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Configuración de SMS/Notificaciones
define('SMS_DEFAULT_EXPIRATION_MINUTES', 5);
define('SMS_MAX_LENGTH', 160);
define('NOTIFICATION_RETRY_ATTEMPTS', 3);

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
