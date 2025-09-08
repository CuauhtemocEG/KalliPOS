<?php
/**
 * Sistema de Impresión Local USB con ESC/POS
 * Este archivo maneja impresión directa a impresoras térmicas USB
 * Usa comandos nativos del sistema y ESC/POS
 */

// Incluir las funciones de ayuda del sistema térmico
require_once '../conexion.php';

/**
 * Detectar si estamos en entorno local o remoto
 */
function esEntornoLocal() {
    // Detectar si estamos en localhost/127.0.0.1 o en un entorno de desarrollo
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return in_array($host, ['localhost', '127.0.0.1']) || 
           strpos($host, 'localhost:') === 0 ||
           strpos($host, '192.168.') === 0 ||
           strpos($host, '10.0.') === 0;
}

/**
 * Detectar impresoras térmicas disponibles
 */
function detectarImpresorasTermicas() {
    $impresoras = [];
    
    // Ejecutar lpstat para obtener impresoras
    $output = shell_exec('lpstat -p 2>/dev/null');
    
    if ($output) {
        $lineas = explode("\n", $output);
        foreach ($lineas as $linea) {
            if (preg_match('/impresora (.+?) está/', $linea, $matches)) {
                $nombre = $matches[1];
                
                // Filtrar impresoras que probablemente sean térmicas
                if (stripos($nombre, 'termica') !== false || 
                    stripos($nombre, 'thermal') !== false ||
                    stripos($nombre, 'receipt') !== false ||
                    stripos($nombre, 'pos') !== false ||
                    stripos($nombre, 'gprinter') !== false ||
                    stripos($nombre, 'xprinter') !== false ||
                    stripos($nombre, 'epson') !== false) {
                    
                    $impresoras[] = [
                        'nombre' => $nombre,
                        'activa' => strpos($linea, 'inactiva') === false
                    ];
                }
            }
        }
    }
    
    return $impresoras;
}

/**
 * Convertir número a texto en español (reutilizado)
 */
function numeroATextoHelper($numero) {
    $unidades = array('', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
                     'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 
                     'dieciocho', 'diecinueve', 'veinte');
    
    $decenas = array('', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa');
    $centenas = array('', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos');
    
    if ($numero == 0) return 'cero pesos';
    
    $entero = floor($numero);
    $decimales = round(($numero - $entero) * 100);
    
    $texto = '';
    
    if ($entero >= 1000) {
        $miles = floor($entero / 1000);
        if ($miles == 1) {
            $texto .= 'mil ';
        } else {
            $texto .= numeroATextoHelper($miles) . ' mil ';
        }
        $entero = $entero % 1000;
    }
    
    if ($entero >= 100) {
        $cen = floor($entero / 100);
        if ($entero == 100) {
            $texto .= 'cien ';
        } else {
            $texto .= $centenas[$cen] . ' ';
        }
        $entero = $entero % 100;
    }
    
    if ($entero >= 21) {
        $dec = floor($entero / 10);
        $uni = $entero % 10;
        $texto .= $decenas[$dec];
        if ($uni > 0) {
            $texto .= ' y ' . $unidades[$uni];
        }
    } else if ($entero > 0) {
        $texto .= $unidades[$entero];
    }
    
    $texto .= ' pesos';
    
    if ($decimales > 0) {
        $texto .= ' con ' . $decimales . '/100';
    }
    
    return trim($texto);
}

/**
 * Formatear método de pago
 */
function formatearMetodoPagoHelper($metodo) {
    $metodos = [
        'efectivo' => 'EFECTIVO',
        'tarjeta' => 'TARJETA', 
        'transferencia' => 'TRANSFERENCIA',
        'cheque' => 'CHEQUE'
    ];
    
    return $metodos[$metodo] ?? strtoupper($metodo);
}

/**
 * Generar contenido ESC/POS para ticket
 */
function generarTicketESCPOS($orden, $productos, $sucursal_nombre) {
    $esc = "\x1B";
    $gs = "\x1D";
    
    $ticket = '';
    
    // Inicializar impresora
    $ticket .= $esc . "@"; // Reset
    
    // Centrar y título
    $ticket .= $esc . "a\x01"; // Centrar
    $ticket .= $esc . "!\x18"; // Texto grande y negrita
    $ticket .= $sucursal_nombre . "\n";
    $ticket .= $esc . "!\x00"; // Resetear formato
    
    // Línea separadora
    $ticket .= $esc . "a\x00"; // Alinear izquierda
    $ticket .= str_repeat("=", 45) . "\n";
    
    // Información de la orden
    $ticket .= "Sucursal: " . $sucursal_nombre . "\n";
    $ticket .= "Mesa: " . $orden['nombre'] . "\n";
    $ticket .= "Orden: #" . $orden['codigo'] . "\n";
    $ticket .= "Fecha: " . date('d/m/Y H:i:s', strtotime($orden['creada_en'])) . "\n";
    
    $ticket .= str_repeat("=", 45) . "\n";
    
    // Encabezado de productos
    $ticket .= sprintf("%-4s %-20s %8s %10s\n", "CANT", "DESCRIPCION", "PRECIO", "TOTAL");
    $ticket .= str_repeat("-", 45) . "\n";
    
    // Productos
    $total_orden = 0;
    foreach ($productos as $producto) {
        $nombre = $producto['nombre'];
        $cantidad = $producto['cantidad'];
        $precio = $producto['precio'];
        $total_producto = $cantidad * $precio;
        $total_orden += $total_producto;
        
        // Truncar nombre si es muy largo
        if (strlen($nombre) > 20) {
            $nombre = substr($nombre, 0, 17) . "...";
        }
        
        $ticket .= sprintf("%-4d %-20s %8.2f %10.2f\n", 
                          $cantidad, 
                          $nombre, 
                          $precio, 
                          $total_producto);
        
        // Comentarios si existen
        if (!empty($producto['comentarios'])) {
            $ticket .= "     * " . $producto['comentarios'] . "\n";
        }
    }
    
    $ticket .= str_repeat("=", 45) . "\n";
    
    // Total
    $ticket .= $esc . "a\x02"; // Alinear derecha
    $ticket .= $esc . "!\x18"; // Texto grande y negrita
    $ticket .= "TOTAL: $" . number_format($total_orden, 2) . "\n";
    $ticket .= $esc . "!\x00"; // Resetear formato
    
    // Total en texto
    $ticket .= $esc . "a\x01"; // Centrar
    $totalTexto = numeroATextoHelper($total_orden);
    $ticket .= ucfirst($totalTexto) . "\n";
    
    // Información de pago si está cerrada
    if ($orden['estado'] === 'cerrada') {
        $ticket .= $esc . "a\x00"; // Alinear izquierda
        $ticket .= str_repeat("-", 45) . "\n";
        $ticket .= $esc . "!\x08"; // Negrita
        $ticket .= "METODO DE PAGO: " . formatearMetodoPagoHelper($orden['metodo_pago'] ?? 'efectivo') . "\n";
        $ticket .= $esc . "!\x00"; // Resetear formato
        
        if (($orden['metodo_pago'] === 'efectivo' || !isset($orden['metodo_pago'])) && 
            isset($orden['dinero_recibido']) && $orden['dinero_recibido'] !== null) {
            
            $ticket .= "Dinero recibido: $" . number_format($orden['dinero_recibido'], 2) . "\n";
            
            if (isset($orden['cambio']) && $orden['cambio'] !== null && $orden['cambio'] > 0) {
                $ticket .= $esc . "!\x08"; // Negrita
                $ticket .= "Cambio: $" . number_format($orden['cambio'], 2) . "\n";
                $ticket .= $esc . "!\x00"; // Resetear formato
            } else {
                $ticket .= "Pago exacto\n";
            }
        }
    }
    
    $ticket .= str_repeat("=", 45) . "\n";
    
    // Mensaje final
    $ticket .= $esc . "a\x01"; // Centrar
    $ticket .= "¡Gracias por su preferencia!\n";
    
    // Cortar papel
    $ticket .= $gs . "V\x42\x00"; // Corte parcial
    
    return $ticket;
}

/**
 * Imprimir ticket directamente a impresora USB
 */
function imprimirTicketUSB($contenidoESCPOS, $nombreImpresora = null) {
    // Si no se especifica impresora, usar la primera térmica disponible
    if (!$nombreImpresora) {
        $impresoras = detectarImpresorasTermicas();
        if (empty($impresoras)) {
            throw new Exception('No se encontraron impresoras térmicas configuradas');
        }
        $nombreImpresora = $impresoras[0]['nombre'];
    }
    
    // Crear archivo temporal con el contenido ESC/POS
    $archivoTemporal = tempnam(sys_get_temp_dir(), 'ticket_escpos_');
    
    if (file_put_contents($archivoTemporal, $contenidoESCPOS) === false) {
        throw new Exception('No se pudo crear el archivo temporal');
    }
    
    try {
        // Enviar directamente a la impresora usando lp
        $comando = sprintf('lp -d "%s" -o raw "%s" 2>&1', 
                          escapeshellarg($nombreImpresora), 
                          escapeshellarg($archivoTemporal));
        
        $output = shell_exec($comando);
        
        if ($output && strpos($output, 'request id') === false) {
            throw new Exception('Error al enviar a impresora: ' . $output);
        }
        
        return [
            'success' => true,
            'mensaje' => 'Ticket enviado a impresora ' . $nombreImpresora,
            'output' => $output
        ];
        
    } finally {
        // Limpiar archivo temporal
        unlink($archivoTemporal);
    }
}

// Procesar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['tipo'])) {
            throw new Exception('Tipo de impresión no especificado');
        }
        
        // Verificar si estamos en entorno local
        if (!esEntornoLocal()) {
            throw new Exception('Impresión USB solo disponible en entorno local');
        }
        
        switch ($input['tipo']) {
            case 'detectar_impresoras':
                echo json_encode([
                    'success' => true,
                    'impresoras' => detectarImpresorasTermicas(),
                    'entorno_local' => true
                ]);
                break;
                
            case 'prueba':
                // Ticket de prueba
                $datosOrden = [
                    'nombre' => 'Mesa de Prueba',
                    'codigo' => 'PRUEBA-001',
                    'creada_en' => date('Y-m-d H:i:s'),
                    'estado' => 'cerrada',
                    'metodo_pago' => 'efectivo',
                    'dinero_recibido' => 100.00,
                    'cambio' => 11.84
                ];
                
                $productosEjemplo = [
                    ['nombre' => 'Producto de Prueba 1', 'cantidad' => 2, 'precio' => 25.00, 'comentarios' => ''],
                    ['nombre' => 'Producto de Prueba 2', 'cantidad' => 1, 'precio' => 15.50, 'comentarios' => 'Sin cebolla'],
                    ['nombre' => 'Producto de Prueba 3', 'cantidad' => 3, 'precio' => 8.75, 'comentarios' => '']
                ];
                
                $contenidoESCPOS = generarTicketESCPOS($datosOrden, $productosEjemplo, 'RESTAURANT POS - PRUEBA');
                $resultado = imprimirTicketUSB($contenidoESCPOS, $input['impresora'] ?? null);
                
                echo json_encode($resultado);
                break;
                
            case 'ticket':
                if (!isset($input['orden_id'])) {
                    throw new Exception('ID de orden no especificado');
                }
                
                $pdo = conexion();
                
                // Obtener datos de la orden
                $stmt = $pdo->prepare("SELECT * FROM ordenes o JOIN mesas m ON o.mesa_id = m.id WHERE o.id = ?");
                $stmt->execute([$input['orden_id']]);
                $orden = $stmt->fetch();
                
                if (!$orden) {
                    throw new Exception('Orden no encontrada');
                }
                
                // Obtener productos
                $stmt = $pdo->prepare("
                    SELECT op.*, p.nombre, p.precio 
                    FROM orden_productos op 
                    JOIN productos p ON op.producto_id = p.id 
                    WHERE op.orden_id = ? AND op.preparado = 1
                ");
                $stmt->execute([$input['orden_id']]);
                $productos = $stmt->fetchAll();
                
                // Obtener nombre de sucursal
                $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'empresa_nombre'");
                $stmt->execute();
                $sucursal = $stmt->fetch();
                $sucursal_nombre = $sucursal ? $sucursal['valor'] : 'RESTAURANT POS';
                
                $contenidoESCPOS = generarTicketESCPOS($orden, $productos, $sucursal_nombre);
                $resultado = imprimirTicketUSB($contenidoESCPOS, $input['impresora'] ?? null);
                
                echo json_encode($resultado);
                break;
                
            default:
                throw new Exception('Tipo de impresión no válido');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Si se accede por GET, mostrar interfaz de prueba
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión USB ESC/POS - KalliPOS</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .btn { background: #007bff; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; margin: 5px; font-size: 14px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .printer-list { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .printer-item { padding: 10px; margin: 5px 0; background: white; border-radius: 3px; border-left: 4px solid #007bff; }
        .printer-item.active { border-left-color: #28a745; }
        .loading { display: none; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖨️ Impresión USB ESC/POS</h1>
        <p>Sistema de impresión directa para impresoras térmicas conectadas por USB</p>
        
        <div id="status" class="alert alert-info">
            <strong>ℹ️ Información:</strong> Detectando entorno y impresoras disponibles...
        </div>
        
        <div id="impresoras-container" style="display: none;">
            <h3>🖨️ Impresoras Térmicas Detectadas</h3>
            <div id="impresoras-list" class="printer-list">
                <!-- Las impresoras se cargarán aquí -->
            </div>
        </div>
        
        <div class="control-buttons">
            <button class="btn btn-warning" onclick="detectarImpresoras()">🔍 Detectar Impresoras</button>
            <button class="btn btn-success" onclick="pruebaImpresion()">🧪 Imprimir Prueba</button>
            <button class="btn" onclick="window.location.href='../views/mesas.php'">🏠 Volver al POS</button>
        </div>
        
        <div class="loading" id="loading">
            <p>⏳ Procesando impresión...</p>
        </div>
        
        <div class="alert alert-info" style="margin-top: 30px;">
            <strong>💡 Cómo funciona:</strong><br>
            • Este sistema envía comandos ESC/POS directamente a tu impresora térmica<br>
            • Funciona solo en entorno local (tu laptop)<br>
            • Compatible con impresoras Gprinter, Xprinter, Epson y similares<br>
            • El formato es idéntico al sistema térmico original
        </div>
    </div>

    <script>
        let impresoraSeleccionada = null;
        
        // Detectar impresoras al cargar la página
        window.addEventListener('DOMContentLoaded', detectarImpresoras);
        
        function detectarImpresoras() {
            const statusDiv = document.getElementById('status');
            const impresorasContainer = document.getElementById('impresoras-container');
            const impresorasList = document.getElementById('impresoras-list');
            
            statusDiv.innerHTML = '<strong>🔍 Detectando impresoras...</strong>';
            statusDiv.className = 'alert alert-info';
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tipo: 'detectar_impresoras' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.impresoras.length > 0) {
                        statusDiv.innerHTML = `<strong>✅ Entorno local detectado!</strong> Se encontraron ${data.impresoras.length} impresora(s) térmica(s).`;
                        statusDiv.className = 'alert alert-success';
                        
                        // Mostrar impresoras
                        impresorasList.innerHTML = '';
                        data.impresoras.forEach((impresora, index) => {
                            const div = document.createElement('div');
                            div.className = 'printer-item' + (impresora.activa ? ' active' : '');
                            div.innerHTML = `
                                <strong>${impresora.nombre}</strong>
                                <span style="float: right; color: ${impresora.activa ? '#28a745' : '#dc3545'};">
                                    ${impresora.activa ? '✅ Activa' : '❌ Inactiva'}
                                </span>
                                <br><small>Clic para seleccionar</small>
                            `;
                            div.style.cursor = 'pointer';
                            div.onclick = () => seleccionarImpresora(impresora.nombre, div);
                            impresorasList.appendChild(div);
                            
                            // Seleccionar la primera activa por defecto
                            if (index === 0 && impresora.activa && !impresoraSeleccionada) {
                                seleccionarImpresora(impresora.nombre, div);
                            }
                        });
                        
                        impresorasContainer.style.display = 'block';
                    } else {
                        statusDiv.innerHTML = '<strong>⚠️ No se encontraron impresoras térmicas.</strong> Verifica que tu impresora esté conectada y configurada en el sistema.';
                        statusDiv.className = 'alert alert-warning';
                    }
                } else {
                    statusDiv.innerHTML = `<strong>❌ Error:</strong> ${data.error}`;
                    statusDiv.className = 'alert alert-danger';
                }
            })
            .catch(error => {
                statusDiv.innerHTML = `<strong>❌ Error de conexión:</strong> ${error.message}`;
                statusDiv.className = 'alert alert-danger';
            });
        }
        
        function seleccionarImpresora(nombre, elemento) {
            // Remover selección anterior
            document.querySelectorAll('.printer-item').forEach(item => {
                item.style.borderLeftColor = '#007bff';
                item.style.backgroundColor = 'white';
            });
            
            // Seleccionar nueva impresora
            elemento.style.borderLeftColor = '#28a745';
            elemento.style.backgroundColor = '#f8f9fa';
            impresoraSeleccionada = nombre;
        }
        
        function pruebaImpresion() {
            if (!impresoraSeleccionada) {
                alert('Por favor selecciona una impresora');
                return;
            }
            
            const loadingDiv = document.getElementById('loading');
            const statusDiv = document.getElementById('status');
            
            loadingDiv.style.display = 'block';
            statusDiv.innerHTML = '<strong>🖨️ Enviando ticket de prueba...</strong>';
            statusDiv.className = 'alert alert-info';
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    tipo: 'prueba',
                    impresora: impresoraSeleccionada
                })
            })
            .then(response => response.json())
            .then(data => {
                loadingDiv.style.display = 'none';
                
                if (data.success) {
                    statusDiv.innerHTML = `<strong>✅ ¡Ticket enviado correctamente!</strong> ${data.mensaje}`;
                    statusDiv.className = 'alert alert-success';
                } else {
                    statusDiv.innerHTML = `<strong>❌ Error al imprimir:</strong> ${data.error}`;
                    statusDiv.className = 'alert alert-danger';
                }
            })
            .catch(error => {
                loadingDiv.style.display = 'none';
                statusDiv.innerHTML = `<strong>❌ Error de conexión:</strong> ${error.message}`;
                statusDiv.className = 'alert alert-danger';
            });
        }
    </script>
</body>
</html>
