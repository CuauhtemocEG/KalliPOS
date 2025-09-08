<?php
/**
 * Sistema de Envío de Tickets por Email para HostGator
 * Alternativa cuando la impresión local no está disponible
 */

// Verificar si PHPMailer está disponible e incluir
if (file_exists('../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../conexion.php';
require_once '../includes/EmailSender.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $pdo = conexion();
        
        if (!isset($input['orden_id'])) {
            throw new Exception('ID de orden requerido');
        }
        
        $orden_id = $input['orden_id'];
        $email_destino = $input['email'] ?? null;
        
        // Obtener datos de la orden
        $stmt = $pdo->prepare("SELECT o.*, m.nombre as mesa_nombre FROM ordenes o JOIN mesas m ON o.mesa_id = m.id WHERE o.id = ?");
        $stmt->execute([$orden_id]);
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orden) {
            throw new Exception('Orden no encontrada');
        }
        
        // Obtener productos
        $stmt = $pdo->prepare("
            SELECT op.*, p.nombre, p.precio 
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            WHERE op.orden_id = ? AND op.preparado = 1 AND op.cancelado = 0
        ");
        $stmt->execute([$orden_id]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener configuración de email
        $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave LIKE 'email_%' OR clave LIKE 'empresa_%'");
        $stmt->execute();
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Generar HTML del ticket para email
        $ticketHTML = generarTicketHTML($orden, $productos, $config);
        
        // Configurar EmailSender
        $emailSender = new EmailSender($pdo);
        
        // Email del negocio por defecto si no se especifica
        if (!$email_destino) {
            $email_destino = $config['empresa_email'] ?? 'admin@' . $_SERVER['HTTP_HOST'];
        }
        
        // Enviar email usando método directo
        $resultado = enviarEmailDirecto(
            $email_destino,
            "Ticket de Orden #{$orden['codigo']}",
            $ticketHTML
        );
        
        if ($resultado['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Ticket enviado por email correctamente',
                'email_enviado' => $email_destino
            ]);
        } else {
            throw new Exception('Error al enviar email: ' . $resultado['error']);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Generar HTML del ticket para email
 */
function generarTicketHTML($orden, $productos, $config) {
    $fecha_formateada = date('d/m/Y H:i', strtotime($orden['creada_en']));
    $empresa_nombre = $config['empresa_nombre'] ?? 'Restaurant POS';
    
    $html = '
    <div style="font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; border: 2px solid #ddd; padding: 20px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #333;">' . htmlspecialchars($empresa_nombre) . '</h2>';
    
    if (!empty($config['empresa_direccion'])) {
        $html .= '<p style="margin: 5px 0; font-size: 12px;">' . htmlspecialchars($config['empresa_direccion']) . '</p>';
    }
    
    if (!empty($config['empresa_telefono'])) {
        $html .= '<p style="margin: 5px 0; font-size: 12px;">Tel: ' . htmlspecialchars($config['empresa_telefono']) . '</p>';
    }
    
    $html .= '
        </div>
        
        <hr style="border: 1px dashed #333;">
        
        <div style="margin: 15px 0;">
            <strong>Mesa:</strong> ' . htmlspecialchars($orden['mesa_nombre']) . '<br>
            <strong>Orden:</strong> #' . htmlspecialchars($orden['codigo']) . '<br>
            <strong>Fecha:</strong> ' . $fecha_formateada . '<br>';
    
    if ($orden['estado'] === 'cerrada') {
        $html .= '<strong>Estado:</strong> <span style="color: green;">PAGADA ✅</span>';
    }
    
    $html .= '
        </div>
        
        <hr style="border: 1px dashed #333;">
        
        <div style="margin: 15px 0;">
            <h3 style="margin-bottom: 10px;">PRODUCTOS:</h3>';
    
    foreach ($productos as $producto) {
        $html .= '
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; border-bottom: 1px dotted #ccc; padding-bottom: 3px;">
                <span>' . htmlspecialchars($producto['nombre']) . '</span>
                <span>' . $producto['cantidad'] . ' x $' . number_format($producto['precio'], 2) . '</span>
                <strong>$' . number_format($producto['precio'] * $producto['cantidad'], 2) . '</strong>
            </div>';
    }
    
    $html .= '
        </div>
        
        <hr style="border: 1px dashed #333;">
        
        <div style="text-align: right; font-size: 18px; font-weight: bold; margin: 15px 0;">
            TOTAL: $' . number_format($orden['total'], 2) . '
        </div>';
    
    if ($orden['estado'] === 'cerrada' && !empty($orden['metodo_pago'])) {
        $html .= '
        <hr style="border: 1px dashed #333;">
        <div style="margin: 15px 0;">
            <strong>MÉTODO DE PAGO:</strong> ' . strtoupper($orden['metodo_pago']) . '<br>';
        
        if ($orden['metodo_pago'] === 'efectivo' && !empty($orden['dinero_recibido'])) {
            $html .= '<strong>Recibido:</strong> $' . number_format($orden['dinero_recibido'], 2) . '<br>';
            
            if (!empty($orden['cambio']) && $orden['cambio'] > 0) {
                $html .= '<strong>Cambio:</strong> $' . number_format($orden['cambio'], 2);
            } else {
                $html .= '<strong>Pago exacto</strong>';
            }
        }
        
        $html .= '</div>';
    }
    
    $html .= '
        <hr style="border: 1px dashed #333;">
        
        <div style="text-align: center; margin-top: 20px;">
            <p style="margin: 5px 0;"><strong>¡Gracias por su preferencia!</strong></p>
            <p style="font-size: 10px; color: #666;">
                Ticket generado: ' . date('d/m/Y H:i:s') . '<br>
                Sistema POS - Enviado desde HostGator
            </p>
        </div>
    </div>';
    
    return $html;
}

/**
 * Enviar email directamente usando PHPMailer configurado para HostGator
 */
function enviarEmailDirecto($destinatario, $asunto, $cuerpoHTML) {
    try {
        // Verificar si PHPMailer está disponible
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Fallback usando mail() de PHP
            return enviarEmailSimple($destinatario, $asunto, $cuerpoHTML);
        }
        
        // Configuración básica para HostGator
        $mail = new PHPMailer(true);
        
        // Configuración del servidor SMTP de HostGator
        $mail->isSMTP();
        $mail->Host = 'localhost'; // HostGator usa localhost para SMTP
        $mail->SMTPAuth = false;   // HostGator no requiere autenticación desde el hosting
        $mail->Port = 25;          // Puerto estándar de HostGator
        
        // Configurar remitente y destinatario
        $remitente = 'noreply@' . $_SERVER['HTTP_HOST'];
        $mail->setFrom($remitente, 'Sistema POS');
        $mail->addAddress($destinatario);
        
        // Configurar contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $cuerpoHTML;
        $mail->CharSet = 'UTF-8';
        
        // Enviar
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email enviado correctamente con PHPMailer'
        ];
        
    } catch (Exception $e) {
        // Fallback en caso de error con PHPMailer
        return enviarEmailSimple($destinatario, $asunto, $cuerpoHTML);
    }
}

/**
 * Función fallback usando mail() de PHP
 */
function enviarEmailSimple($destinatario, $asunto, $cuerpoHTML) {
    try {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Sistema POS <noreply@' . $_SERVER['HTTP_HOST'] . '>',
            'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $success = mail($destinatario, $asunto, $cuerpoHTML, implode("\r\n", $headers));
        
        if ($success) {
            return [
                'success' => true,
                'message' => 'Email enviado correctamente con mail()'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error al enviar con mail()'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Error en envío simple: ' . $e->getMessage()
        ];
    }
}
?>
