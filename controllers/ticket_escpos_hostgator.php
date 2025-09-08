<?php
/**
 * Generador de Comandos ESC/POS para HostGator
 * Genera comandos térmicos válidos que se pueden copiar y enviar a impresora
 */

require_once '../conexion.php';
require_once 'imprimir_termica.php';

$orden_id = $_GET['orden_id'] ?? null;
$modo = $_GET['modo'] ?? 'preview'; // preview, download, raw

if (!$orden_id) {
    die('Error: ID de orden no proporcionado');
}

try {
    $pdo = conexion();
    
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
    
    // Crear impresora térmica
    $impresora = new ImpresorTermica();
    
    // ✅ USAR IMAGEN CONFIGURADA
    $impresora->imagenConfigurada();
    
    // Generar ticket completo
    $impresora->texto('KALLI JAGUAR', 'center', true, 'large');
    $impresora->saltoLinea();
    
    $impresora->texto('Mesa: ' . $orden['mesa_nombre'], 'left');
    $impresora->texto('Orden: #' . $orden['codigo'], 'left');
    $impresora->texto('Fecha: ' . date('d/m/Y H:i:s', strtotime($orden['creada_en'])), 'left');
    $impresora->saltoLinea();
    $impresora->linea('=', 32);
    $impresora->saltoLinea();
    
    // Productos
    $impresora->tablaProductos($productos);
    
    // Total
    $impresora->saltoLinea();
    $impresora->texto('TOTAL: $' . number_format($orden['total'], 2), 'right', true, 'large');
    $impresora->saltoLinea();
    
    // Información del pago
    if ($orden['estado'] === 'cerrada' && !empty($orden['metodo_pago'])) {
        $impresora->linea('-', 32);
        $impresora->texto('METODO DE PAGO: ' . strtoupper($orden['metodo_pago']), 'left', true);
        
        if ($orden['metodo_pago'] === 'efectivo' && !empty($orden['dinero_recibido'])) {
            $impresora->texto('Dinero recibido: $' . number_format($orden['dinero_recibido'], 2), 'left');
            
            if (!empty($orden['cambio']) && $orden['cambio'] > 0) {
                $impresora->texto('Cambio: $' . number_format($orden['cambio'], 2), 'left', true);
            } else {
                $impresora->texto('Pago exacto', 'left');
            }
        }
        $impresora->saltoLinea();
    }
    
    $impresora->linea('=', 32);
    $impresora->texto('Gracias por su compra!', 'center');
    $impresora->cortar();
    
    // Obtener comandos ESC/POS
    $comandosESCPOS = $impresora->obtenerComandos();
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Manejar diferentes modos
switch ($modo) {
    case 'download':
        // Descargar archivo binario
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="ticket_orden_' . $orden['codigo'] . '.prn"');
        echo $comandosESCPOS;
        exit;
        
    case 'raw':
        // Mostrar comandos en formato crudo
        header('Content-Type: text/plain; charset=utf-8');
        echo $comandosESCPOS;
        exit;
        
    case 'preview':
    default:
        // Mostrar interfaz de preview
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comandos ESC/POS - Orden <?= htmlspecialchars($orden['codigo']) ?></title>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f8f9fa;
            line-height: 1.5;
        }
        
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
            margin: -20px -20px 0 -20px;
        }
        
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 8px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .commands-preview {
            background: #1e1e1e;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            margin: 20px 0;
            border: 2px solid #444;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .warning-box {
            background: #fff3e0;
            border: 1px solid #ff9800;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .success-box {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .steps {
            counter-reset: step-counter;
        }
        
        .step {
            counter-increment: step-counter;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            border-radius: 0 8px 8px 0;
        }
        
        .step::before {
            content: counter(step-counter);
            background: #007bff;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .copy-btn {
            position: relative;
            overflow: hidden;
        }
        
        .copy-success {
            background: #28a745 !important;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🖨️ Comandos ESC/POS para HostGator</h1>
        <p>Orden #<?= htmlspecialchars($orden['codigo']) ?> - Mesa: <?= htmlspecialchars($orden['mesa_nombre']) ?></p>
        <p><strong>✅ Optimizado para Impresoras Térmicas</strong></p>
    </div>
    
    <div class="content">
        <div class="success-box">
            <h3>🎯 ¡Comandos ESC/POS Generados Exitosamente!</h3>
            <p>Los comandos están listos para usar con tu impresora térmica. Tamaño: <strong><?= number_format(strlen($comandosESCPOS)) ?> bytes</strong></p>
        </div>
        
        <!-- Botones de acción -->
        <div style="text-align: center; margin: 25px 0;">
            <button onclick="copiarComandos()" class="btn btn-primary copy-btn" id="copyBtn">
                📋 Copiar Comandos al Portapapeles
            </button>
            
            <a href="?orden_id=<?= $orden_id ?>&modo=download" class="btn btn-success">
                💾 Descargar Archivo .PRN
            </a>
            
            <button onclick="mostrarComandos()" class="btn btn-warning" id="showBtn">
                👁️ Ver Comandos Crudos
            </button>
            
            <button onclick="window.close()" class="btn btn-secondary">
                ❌ Cerrar
            </button>
        </div>
        
        <!-- Preview de comandos -->
        <div class="commands-preview" id="commandsPreview" style="display: none;">
            <div style="color: #a6e22e; margin-bottom: 10px;">// Comandos ESC/POS - <?= date('Y-m-d H:i:s') ?></div>
            <div style="color: #f92672;">// Orden: <?= htmlspecialchars($orden['codigo']) ?></div>
            <div style="color: #66d9ef;">// Total: $<?= number_format($orden['total'], 2) ?></div>
            <div style="color: #75715e;">// Tamaño: <?= number_format(strlen($comandosESCPOS)) ?> bytes</div>
            <hr style="border-color: #444;">
            <?= htmlspecialchars(substr($comandosESCPOS, 0, 500)) ?>
            <?php if (strlen($comandosESCPOS) > 500): ?>
                <div style="color: #fd971f;">... (<?= number_format(strlen($comandosESCPOS) - 500) ?> bytes adicionales)</div>
            <?php endif; ?>
        </div>
        
        <!-- Instrucciones -->
        <div class="info-box">
            <h3>📋 Métodos para Imprimir desde HostGator:</h3>
            
            <div class="steps">
                <div class="step">
                    <strong>Método 1: Copiar y Pegar (Recomendado)</strong><br>
                    Copia los comandos ESC/POS y úsalos con software como "ESC/POS Print Tool" o "Thermal Printer Tool"
                </div>
                
                <div class="step">
                    <strong>Método 2: Archivo .PRN</strong><br>
                    Descarga el archivo .PRN y envíalo directamente a tu impresora usando: <code>copy archivo.prn \\.\COM1</code> (Windows) o <code>cat archivo.prn > /dev/usb/lp0</code> (Linux)
                </div>
                
                <div class="step">
                    <strong>Método 3: Software Terceros</strong><br>
                    Usa aplicaciones como "RawBT" (Android), "Bluetooth Thermal Printer" (iOS) o "PrintNode" (Web)
                </div>
                
                <div class="step">
                    <strong>Método 4: API de Impresión</strong><br>
                    Integra con servicios como PrintNode, CloudPrint o ZebraPrint para impresión remota
                </div>
            </div>
        </div>
        
        <div class="warning-box">
            <h4>⚠️ Importante para HostGator:</h4>
            <ul>
                <li>HostGator <strong>NO soporta</strong> comandos shell ni acceso USB directo</li>
                <li>Los comandos ESC/POS se generan correctamente pero deben enviarse desde tu computadora local</li>
                <li>Considera usar un software de impresión térmica en tu laptop/PC</li>
                <li>Para automático completo, necesitarías un VPS o servidor dedicado</li>
            </ul>
        </div>
        
        <div class="success-box">
            <h4>✅ Herramientas Recomendadas:</h4>
            <ul>
                <li><strong>Windows:</strong> "ESC/POS Print Tool", "Thermal Receipt Printer"</li>
                <li><strong>Mac:</strong> "Receipt Printer Tool", Terminal con comandos lpr</li>
                <li><strong>Android:</strong> "RawBT ESC/POS", "Bluetooth Thermal Printer"</li>
                <li><strong>Web:</strong> PrintNode, CloudPrint (servicios de pago)</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Comandos ESC/POS en base64 para JavaScript
        const comandosBase64 = '<?= base64_encode($comandosESCPOS) ?>';
        
        function copiarComandos() {
            try {
                // Decodificar comandos
                const comandos = atob(comandosBase64);
                
                // Crear elemento temporal para copiar
                const textArea = document.createElement('textarea');
                textArea.value = comandos;
                document.body.appendChild(textArea);
                textArea.select();
                
                // Copiar al portapapeles
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Feedback visual
                const btn = document.getElementById('copyBtn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '✅ ¡Copiado!';
                btn.classList.add('copy-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('copy-success');
                }, 2000);
                
                // Mostrar instrucciones adicionales
                alert('✅ Comandos ESC/POS copiados al portapapeles!\n\n' +
                      '📋 Ahora puedes:\n' +
                      '1. Abrir un software de impresión térmica\n' +
                      '2. Pegar los comandos (Ctrl+V)\n' +
                      '3. Enviar a tu impresora\n\n' +
                      '💡 Tip: Busca "ESC/POS Print Tool" en Google');
                
            } catch (error) {
                alert('❌ Error al copiar. Usa el botón "Ver Comandos Crudos" y copia manualmente.');
                console.error('Error:', error);
            }
        }
        
        function mostrarComandos() {
            const preview = document.getElementById('commandsPreview');
            const btn = document.getElementById('showBtn');
            
            if (preview.style.display === 'none') {
                preview.style.display = 'block';
                btn.innerHTML = '🙈 Ocultar Comandos';
            } else {
                preview.style.display = 'none';
                btn.innerHTML = '👁️ Ver Comandos Crudos';
            }
        }
        
        // Auto-mostrar comandos si la URL contiene show=1
        if (window.location.search.includes('show=1')) {
            mostrarComandos();
        }
    </script>
</body>
</html>
