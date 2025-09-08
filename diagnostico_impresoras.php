<?php
/**
 * Diagnóstico de Detección de Impresoras
 * Archivo para diagnosticar problemas con la detección de impresoras
 */

header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Impresoras - KalliPOS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #fafafa; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .code { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 3px; font-family: monospace; white-space: pre-wrap; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        h3 { color: #555; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Detección de Impresoras</h1>
        <p>Este diagnóstico te ayudará a identificar por qué no se detectan las impresoras en tu sistema.</p>

        <?php
        // === INFORMACIÓN DEL SISTEMA ===
        echo "<div class='section'>";
        echo "<h2>📊 Información del Sistema</h2>";
        
        $info = [
            'Sistema Operativo' => PHP_OS,
            'Versión PHP' => PHP_VERSION,
            'Servidor Web' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
            'SAPI' => php_sapi_name(),
            'Usuario del proceso' => get_current_user(),
            'Directorio actual' => getcwd(),
        ];
        
        echo "<table>";
        foreach ($info as $key => $value) {
            echo "<tr><th>$key</th><td>$value</td></tr>";
        }
        echo "</table>";
        echo "</div>";

        // === VERIFICACIÓN DE FUNCIONES ===
        echo "<div class='section'>";
        echo "<h2>🛠️ Verificación de Funciones PHP</h2>";
        
        $funciones = ['shell_exec', 'exec', 'system', 'passthru', 'proc_open', 'file_get_contents', 'fsockopen'];
        $funcionesDeshabilitadas = array_map('trim', explode(',', ini_get('disable_functions')));
        
        echo "<table>";
        echo "<tr><th>Función</th><th>Estado</th><th>Disponible</th></tr>";
        
        foreach ($funciones as $funcion) {
            $existe = function_exists($funcion);
            $deshabilitada = in_array($funcion, $funcionesDeshabilitadas);
            
            $estado = $existe && !$deshabilitada ? 
                "<span style='color: green;'>✅ Disponible</span>" : 
                "<span style='color: red;'>❌ No disponible</span>";
            
            $motivo = '';
            if (!$existe) $motivo = 'No existe';
            if ($deshabilitada) $motivo = 'Deshabilitada';
            
            echo "<tr><td>$funcion</td><td>$estado</td><td>$motivo</td></tr>";
        }
        echo "</table>";
        echo "</div>";

        // === PRUEBA DE COMANDOS DEL SISTEMA ===
        echo "<div class='section'>";
        echo "<h2>💻 Prueba de Comandos del Sistema</h2>";
        
        if (function_exists('shell_exec') && !in_array('shell_exec', $funcionesDeshabilitadas)) {
            $comandos = [
                'lpstat -v' => 'Listar impresoras CUPS',
                'lsusb' => 'Listar dispositivos USB (Linux)',
                'system_profiler SPUSBDataType' => 'Información USB (macOS)',
                'wmic printer get name' => 'Listar impresoras (Windows)',
                'which lpstat' => 'Verificar lpstat disponible',
                'which lsusb' => 'Verificar lsusb disponible',
                'whoami' => 'Usuario actual',
                'pwd' => 'Directorio actual'
            ];
            
            echo "<h3>Ejecutando comandos de prueba:</h3>";
            
            foreach ($comandos as $comando => $descripcion) {
                echo "<div style='margin: 10px 0;'>";
                echo "<strong>$comando</strong> - $descripcion<br>";
                
                $output = @shell_exec("$comando 2>&1");
                
                if ($output !== null && trim($output) !== '') {
                    echo "<div class='code success'>" . htmlspecialchars(trim($output)) . "</div>";
                } else {
                    echo "<div class='code error'>Sin salida o comando no disponible</div>";
                }
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<strong>⚠️ shell_exec() no está disponible</strong><br>";
            echo "La función shell_exec está deshabilitada o no existe. Esto es común en hosting compartido por seguridad.";
            echo "</div>";
        }
        echo "</div>";

        // === VERIFICACIÓN DE ARCHIVOS DE SISTEMA ===
        echo "<div class='section'>";
        echo "<h2>📁 Verificación de Archivos de Sistema</h2>";
        
        $archivos = [
            '/etc/cups/printers.conf' => 'Configuración CUPS',
            '/etc/cups/' => 'Directorio CUPS',
            '/usr/share/cups/model/' => 'Modelos de impresoras',
            '/proc/bus/usb/devices' => 'Dispositivos USB (Linux)',
            '/sys/bus/usb/devices/' => 'Dispositivos USB (sysfs)',
            '/dev/usb/' => 'Dispositivos USB (dev)',
        ];
        
        echo "<table>";
        echo "<tr><th>Archivo/Directorio</th><th>Existe</th><th>Legible</th><th>Tipo</th></tr>";
        
        foreach ($archivos as $archivo => $descripcion) {
            $existe = file_exists($archivo);
            $legible = $existe && is_readable($archivo);
            $tipo = '';
            
            if ($existe) {
                if (is_dir($archivo)) $tipo = 'Directorio';
                elseif (is_file($archivo)) $tipo = 'Archivo';
                else $tipo = 'Otro';
            }
            
            $estadoExiste = $existe ? "✅" : "❌";
            $estadoLegible = $legible ? "✅" : "❌";
            
            echo "<tr>";
            echo "<td>$archivo<br><small>$descripcion</small></td>";
            echo "<td>$estadoExiste</td>";
            echo "<td>$estadoLegible</td>";
            echo "<td>$tipo</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";

        // === DETECTAR IMPRESORAS DE RED ===
        echo "<div class='section'>";
        echo "<h2>🌐 Detección de Impresoras de Red</h2>";
        
        echo "<h3>Información de Red:</h3>";
        $redInfo = [
            'SERVER_ADDR' => $_SERVER['SERVER_ADDR'] ?? 'No disponible',
            'LOCAL_ADDR' => $_SERVER['LOCAL_ADDR'] ?? 'No disponible',
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'No disponible',
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'No disponible'
        ];
        
        echo "<table>";
        foreach ($redInfo as $key => $value) {
            echo "<tr><th>$key</th><td>$value</td></tr>";
        }
        echo "</table>";
        
        // Intentar detectar impresoras de red
        echo "<h3>Buscando impresoras en red...</h3>";
        $ipLocal = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '127.0.0.1';
        
        if (preg_match('/(\d+\.\d+\.\d+)\./', $ipLocal, $matches)) {
            $baseIP = $matches[1];
            $ipsTest = [
                $baseIP . '.100',
                $baseIP . '.101',
                $baseIP . '.102',
                $baseIP . '.200',
                $baseIP . '.250'
            ];
            
            echo "<p>Probando IPs comunes para impresoras en el rango $baseIP.x:</p>";
            
            foreach ($ipsTest as $ip) {
                echo "<div style='margin: 5px 0;'>";
                echo "Probando $ip:9100... ";
                
                $connection = @fsockopen($ip, 9100, $errno, $errstr, 2);
                if ($connection) {
                    fclose($connection);
                    echo "<span style='color: green;'>✅ Puerto 9100 abierto (posible impresora)</span>";
                } else {
                    echo "<span style='color: gray;'>❌ Sin respuesta</span>";
                }
                echo "</div>";
            }
        } else {
            echo "<div class='warning'>No se pudo determinar el rango de IP local</div>";
        }
        echo "</div>";

        // === LLAMAR AL DETECTOR ORIGINAL ===
        echo "<div class='section'>";
        echo "<h2>🔍 Resultado del Detector de Impresoras</h2>";
        
        echo "<button class='btn' onclick='detectarImpresoras()'>🔄 Ejecutar Detección</button>";
        echo "<div id='resultado-detector' style='margin-top: 10px;'></div>";
        echo "</div>";

        // === RECOMENDACIONES ===
        echo "<div class='section'>";
        echo "<h2>💡 Recomendaciones</h2>";
        
        $recomendaciones = [
            "Si shell_exec está deshabilitado, contacta a tu proveedor de hosting para habilitarlo (aunque es poco probable por seguridad)",
            "Considera usar detección manual de impresoras especificando IP y puerto directamente",
            "Para hosting compartido, usa impresoras de red con IP fija (puerto 9100)",
            "Verifica que tu impresora esté conectada a la misma red que el servidor",
            "Prueba impresoras ESC/POS que son compatibles con la mayoría de sistemas POS",
            "Considera usar servicios en la nube para impresión si el hosting es muy restrictivo"
        ];
        
        echo "<ul>";
        foreach ($recomendaciones as $rec) {
            echo "<li>$rec</li>";
        }
        echo "</ul>";
        echo "</div>";
        ?>

        <div class="section">
            <h2>🚀 Configuración Manual de Impresora</h2>
            <p>Si la detección automática no funciona, puedes configurar tu impresora manualmente:</p>
            
            <form style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <h3>Configuración Manual:</h3>
                <label>Nombre de la impresora:</label><br>
                <input type="text" placeholder="Mi Impresora Térmica" style="width: 300px; padding: 5px; margin: 5px 0;"><br>
                
                <label>Tipo:</label><br>
                <select style="width: 300px; padding: 5px; margin: 5px 0;">
                    <option>Térmica ESC/POS</option>
                    <option>Impresora Láser</option>
                    <option>Impresora de Etiquetas</option>
                </select><br>
                
                <label>Conexión:</label><br>
                <select style="width: 300px; padding: 5px; margin: 5px 0;">
                    <option>USB</option>
                    <option>Red/WiFi (IP)</option>
                    <option>Bluetooth</option>
                </select><br>
                
                <label>IP (si es de red):</label><br>
                <input type="text" placeholder="192.168.1.100" style="width: 300px; padding: 5px; margin: 5px 0;"><br>
                
                <button type="button" class="btn" onclick="alert('Función de guardado pendiente de implementar')">💾 Guardar Configuración</button>
            </form>
        </div>
    </div>

    <script>
        async function detectarImpresoras() {
            const resultado = document.getElementById('resultado-detector');
            resultado.innerHTML = '<div style="color: blue;">🔄 Detectando impresoras...</div>';
            
            try {
                const response = await fetch('detectar_impresoras.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                let html = '<div class="code">';
                html += '<strong>Resultado JSON:</strong><br>';
                html += JSON.stringify(data, null, 2);
                html += '</div>';
                
                if (data.success && data.impresoras.length > 0) {
                    html += '<h3>Impresoras encontradas:</h3>';
                    html += '<table>';
                    html += '<tr><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Puerto</th></tr>';
                    
                    data.impresoras.forEach(imp => {
                        html += `<tr>`;
                        html += `<td>${imp.nombre}</td>`;
                        html += `<td>${imp.tipo}</td>`;
                        html += `<td>${imp.estado}</td>`;
                        html += `<td>${imp.puerto}</td>`;
                        html += `</tr>`;
                    });
                    
                    html += '</table>';
                } else {
                    html += '<div class="warning">No se encontraron impresoras automáticamente.</div>';
                }
                
                resultado.innerHTML = html;
                
            } catch (error) {
                resultado.innerHTML = `<div class="error">Error: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
