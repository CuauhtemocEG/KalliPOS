<?php
/**
 * Archivo para probar el sistema de envío de emails
 */

require_once '../conexion.php';
require_once '../includes/ConfiguracionSistema.php';
require_once '../includes/EmailSender.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$email_destino = trim($_POST['email'] ?? '');

if (empty($email_destino) || !filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email válido requerido']);
    exit;
}

try {
    $pdo = conexion();
    $emailSender = new EmailSender($pdo);
    
    $resultado = $emailSender->enviarEmailPrueba($email_destino);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
