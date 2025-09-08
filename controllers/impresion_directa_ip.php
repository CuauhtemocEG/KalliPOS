<?php
/**
 * Sistema de Impresión Directa por IP para HostGator
 * Envía comandos ESC/POS directamente a impresora térmica por TCP/IP
 */

require_once '../conexion.php';
require_once 'imprimir_termica.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['tipo'])) {
            throw new Exception('Tipo de operación no especificado');
        }
        
        switch ($input['tipo']) {
            case 'imprimir_directo':
                $resultado = imprimirDirectoIP($input);
                echo json_encode($resultado);
                break;
                
            case 'probar_impresora':
                $resultado = probarImpresoraIP($input);
                echo json_encode($resultado);
                break;
                
            case 'configurar_ip':
                $resultado = configurarImpresoraIP($input);
                echo json_encode($resultado);
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
 * Imprimir ticket directamente por IP
 */
function imprimirDirectoIP($datos) {
    try {
        // Obtener configuración de IP
        $pdo = conexion();
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'impresora_ip'");
        $stmt->execute();
        $ip_config = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'impresora_puerto'");
        $stmt->execute();
        $puerto_config = $stmt->fetch();
        
        $ip = $datos['ip'] ?? ($ip_config['valor'] ?? '');
        $puerto = $datos['puerto'] ?? ($puerto_config['valor'] ?? 9100);
        $orden_id = $datos['orden_id'] ?? '';
        
        if (empty($ip)) {
            throw new Exception('IP de impresora no configurada. Configure primero la IP de su impresora térmica.');
        }
        
        if (empty($orden_id)) {
            throw new Exception('ID de orden requerido');
        }
        
        // Generar comandos ESC/POS
        $comandos = generarComandosTicketCompleto($orden_id);
        
        if (empty($comandos)) {
            throw new Exception('Error al generar comandos ESC/POS');
        }
        
        // Enviar a impresora por socket TCP
        $resultado = enviarAImpresoraIP($ip, $puerto, $comandos);
        
        if ($resultado['success']) {
            return [
                'success' => true,
                'message' => 'Ticket impreso correctamente en impresora térmica',
                'ip' => $ip,
                'puerto' => $puerto,
                'bytes_enviados' => strlen($comandos),
                'tiempo_ms' => $resultado['tiempo_ms'] ?? 0
            ];
        } else {
            throw new Exception($resultado['error']);
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'sugerencia' => 'Verifique que la impresora esté encendida y conectada a la red'
        ];
    }
}

/**
 * Enviar datos a impresora por socket TCP/IP
 */
function enviarAImpresoraIP($ip, $puerto, $datos) {
    $tiempo_inicio = microtime(true);
    
    try {
        // Validar IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new Exception("IP no válida: $ip");
        }
        
        // Crear socket
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            throw new Exception('No se pudo crear socket: ' . socket_strerror(socket_last_error()));
        }
        
        // Configurar timeouts
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);
        
        // Conectar a impresora
        $conectado = socket_connect($socket, $ip, $puerto);
        if (!$conectado) {
            $error = socket_strerror(socket_last_error($socket));
            socket_close($socket);
            throw new Exception("No se pudo conectar a $ip:$puerto - $error");
        }
        
        // Enviar comandos ESC/POS
        $bytes_enviados = socket_write($socket, $datos, strlen($datos));
        if ($bytes_enviados === false) {
            $error = socket_strerror(socket_last_error($socket));
            socket_close($socket);
            throw new Exception("Error al enviar datos: $error");
        }
        
        // Esperar confirmación (opcional)
        $respuesta = @socket_read($socket, 1024, PHP_NORMAL_READ);
        
        // Cerrar conexión
        socket_close($socket);
        
        $tiempo_total = round((microtime(true) - $tiempo_inicio) * 1000, 2);
        
        return [
            'success' => true,
            'bytes_enviados' => $bytes_enviados,
            'tiempo_ms' => $tiempo_total,
            'respuesta' => $respuesta ?: null
        ];
        
    } catch (Exception $e) {
        if (isset($socket)) {
            @socket_close($socket);
        }
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'tiempo_ms' => round((microtime(true) - $tiempo_inicio) * 1000, 2)
        ];
    }
}

/**
 * Generar comandos ESC/POS completos para ticket
 */
function generarComandosTicketCompleto($orden_id) {
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
        
        // Generar ticket completo
        $impresora->imagenConfigurada();
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
        $impresora->texto('Impreso desde HostGator', 'center');
        $impresora->cortar();
        
        return $impresora->obtenerComandos();
        
    } catch (Exception $e) {
        error_log("Error generando comandos: " . $e->getMessage());
        return '';
    }
}

/**
 * Probar conexión con impresora
 */
function probarImpresoraIP($datos) {
    try {
        $ip = $datos['ip'] ?? '';
        $puerto = $datos['puerto'] ?? 9100;
        
        if (empty($ip)) {
            throw new Exception('IP requerida para prueba');
        }
        
        // Crear comandos de prueba
        $impresora = new ImpresorTermica();
        $impresora->texto('=== PRUEBA DE CONEXION ===', 'center', true);
        $impresora->saltoLinea();
        $impresora->texto('IP: ' . $ip, 'left');
        $impresora->texto('Puerto: ' . $puerto, 'left');
        $impresora->texto('Fecha: ' . date('Y-m-d H:i:s'), 'left');
        $impresora->saltoLinea();
        $impresora->texto('Si ve este mensaje, la', 'center');
        $impresora->texto('conexion funciona correctamente', 'center');
        $impresora->saltoLinea();
        $impresora->linea('=', 32);
        $impresora->texto('Prueba desde HostGator', 'center');
        $impresora->cortar();
        
        $comandos_prueba = $impresora->obtenerComandos();
        
        // Enviar prueba
        $resultado = enviarAImpresoraIP($ip, $puerto, $comandos_prueba);
        
        if ($resultado['success']) {
            return [
                'success' => true,
                'message' => 'Prueba exitosa - Impresora responde correctamente',
                'detalles' => [
                    'ip' => $ip,
                    'puerto' => $puerto,
                    'bytes_enviados' => $resultado['bytes_enviados'],
                    'tiempo_ms' => $resultado['tiempo_ms']
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => $resultado['error'],
                'tiempo_ms' => $resultado['tiempo_ms']
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Configurar IP de impresora
 */
function configurarImpresoraIP($datos) {
    try {
        $pdo = conexion();
        
        $ip = $datos['ip'] ?? '';
        $puerto = $datos['puerto'] ?? 9100;
        $habilitada = $datos['habilitada'] ?? false;
        
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new Exception('IP no válida');
        }
        
        // Guardar configuración
        $configs = [
            'impresora_ip' => $ip,
            'impresora_puerto' => $puerto,
            'impresora_ip_habilitada' => $habilitada ? '1' : '0'
        ];
        
        foreach ($configs as $clave => $valor) {
            $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            $stmt->execute([$clave, $valor]);
        }
        
        return [
            'success' => true,
            'message' => 'Configuración guardada correctamente',
            'config' => $configs
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Página de configuración simple si se accede por GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pdo = conexion();
    
    // Obtener configuración actual
    $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave LIKE 'impresora_%'");
    $stmt->execute();
    $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $ip_actual = $config['impresora_ip'] ?? '';
    $puerto_actual = $config['impresora_puerto'] ?? 9100;
    $habilitada = ($config['impresora_ip_habilitada'] ?? '0') == '1';
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Configurar Impresora IP - HostGator</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            .btn:hover { background: #0056b3; }
            .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
            .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
            .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
            .status { padding: 10px; margin: 10px 0; border-radius: 4px; background: #f8f9fa; }
        </style>
    </head>
    <body>
        <h1>🖨️ Configurar Impresora Térmica por IP</h1>
        <p>Configure su impresora térmica para impresión directa desde HostGator</p>
        
        <div class="form-group">
            <label>IP de la Impresora:</label>
            <input type="text" id="ip" value="<?= htmlspecialchars($ip_actual) ?>" placeholder="192.168.1.100">
        </div>
        
        <div class="form-group">
            <label>Puerto (normalmente 9100):</label>
            <input type="number" id="puerto" value="<?= $puerto_actual ?>" min="1" max="65535">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" id="habilitada" <?= $habilitada ? 'checked' : '' ?>>
                Habilitar impresión por IP
            </label>
        </div>
        
        <button onclick="guardarConfig()" class="btn">💾 Guardar Configuración</button>
        <button onclick="probarConexion()" class="btn" style="background: #28a745;">🔍 Probar Conexión</button>
        <button onclick="imprimirPrueba()" class="btn" style="background: #ffc107; color: black;">🖨️ Imprimir Prueba</button>
        
        <div id="resultado"></div>
        
        <div class="status">
            <h3>📋 Instrucciones:</h3>
            <ol>
                <li><strong>Conecte su impresora térmica a la red</strong> (Ethernet o WiFi)</li>
                <li><strong>Obtenga la IP de la impresora</strong> (imprimiendo página de configuración)</li>
                <li><strong>Configure port forwarding</strong> en su router (puerto 9100 → IP de impresora)</li>
                <li><strong>Use su IP pública</strong> en el campo IP (ejemplo: su.ip.publica.aqui)</li>
                <li><strong>Pruebe la conexión</strong> antes de usar en producción</li>
            </ol>
            
            <p><strong>⚠️ Importante:</strong> Para que funcione desde HostGator, su impresora debe ser accesible desde Internet. Configure port forwarding en su router dirigiendo el puerto 9100 a la IP local de su impresora.</p>
        </div>
        
        <script>
            async function guardarConfig() {
                const ip = document.getElementById('ip').value;
                const puerto = document.getElementById('puerto').value;
                const habilitada = document.getElementById('habilitada').checked;
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            tipo: 'configurar_ip',
                            ip: ip,
                            puerto: parseInt(puerto),
                            habilitada: habilitada
                        })
                    });
                    
                    const resultado = await response.json();
                    mostrarResultado(resultado);
                } catch (error) {
                    mostrarResultado({success: false, error: error.message});
                }
            }
            
            async function probarConexion() {
                const ip = document.getElementById('ip').value;
                const puerto = document.getElementById('puerto').value;
                
                if (!ip) {
                    alert('Por favor ingrese la IP de la impresora');
                    return;
                }
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            tipo: 'probar_impresora',
                            ip: ip,
                            puerto: parseInt(puerto)
                        })
                    });
                    
                    const resultado = await response.json();
                    mostrarResultado(resultado);
                } catch (error) {
                    mostrarResultado({success: false, error: error.message});
                }
            }
            
            async function imprimirPrueba() {
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            tipo: 'imprimir_directo',
                            orden_id: 'test'
                        })
                    });
                    
                    const resultado = await response.json();
                    mostrarResultado(resultado);
                } catch (error) {
                    mostrarResultado({success: false, error: error.message});
                }
            }
            
            function mostrarResultado(resultado) {
                const div = document.getElementById('resultado');
                const clase = resultado.success ? 'alert-success' : 'alert-danger';
                div.innerHTML = `<div class="alert ${clase}">${resultado.message || resultado.error}</div>`;
            }
        </script>
    </body>
    </html>
    <?php
}
?>
