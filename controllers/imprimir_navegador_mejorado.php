<?php
/**
 * Sistema de Impresión por Navegador Mejorado para HostGator
 * Genera tickets optimizados para impresión desde navegador web
 */

require_once '../conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $pdo = conexion();
        
        if (!isset($input['tipo'])) {
            throw new Exception('Tipo de impresión no especificado');
        }
        
        switch ($input['tipo']) {
            case 'generar_ticket':
                $orden_id = $input['orden_id'] ?? null;
                if (!$orden_id) {
                    throw new Exception('ID de orden requerido');
                }
                
                // Generar URL del ticket optimizada
                $ticket_url = generarUrlTicket($orden_id);
                
                echo json_encode([
                    'success' => true,
                    'ticket_url' => $ticket_url,
                    'message' => 'Ticket listo para imprimir'
                ]);
                break;
                
            case 'verificar_compatibilidad':
                // Verificar si el navegador soporta las funciones necesarias
                $compatibilidad = verificarCompatibilidadNavegador();
                
                echo json_encode([
                    'success' => true,
                    'compatibilidad' => $compatibilidad
                ]);
                break;
                
            default:
                throw new Exception('Tipo no válido');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Generar URL optimizada para ticket
 */
function generarUrlTicket($orden_id) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
    
    return $base_url . '/ticket_navegador_optimizado.php?orden_id=' . urlencode($orden_id);
}

/**
 * Verificar compatibilidad del navegador
 */
function verificarCompatibilidadNavegador() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    return [
        'window_print' => true, // Todos los navegadores modernos lo soportan
        'popup_blocker' => strpos($user_agent, 'Chrome') !== false || 
                          strpos($user_agent, 'Firefox') !== false ||
                          strpos($user_agent, 'Safari') !== false,
        'css_media_print' => true, // CSS @media print
        'user_agent' => $user_agent
    ];
}
?>
