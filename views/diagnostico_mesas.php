<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Sistema de Mesas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: white;
            margin: 0;
            padding: 20px;
        }
        
        .diagnostic-section {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .test-container {
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 12px;
            position: relative;
            height: 400px;
            margin: 20px 0;
            overflow: visible;
        }
        
        .test-mesa {
            position: absolute;
            width: 100px;
            height: 80px;
            background: #059669;
            border: 3px solid white;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: move;
        }
        
        .mesa-db {
            background: #dc2626;
        }
        
        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            margin: 5px;
        }
        
        button:hover {
            background: #2563eb;
        }
        
        .log {
            background: #000;
            color: #0f0;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>🔧 Diagnóstico del Sistema de Mesas</h1>
    
    <div class="diagnostic-section">
        <h2>1. Datos de la Base de Datos</h2>
        <div id="db-info">Cargando datos...</div>
        <button onclick="cargarDatosBD()">Recargar Datos BD</button>
    </div>
    
    <div class="diagnostic-section">
        <h2>2. Test de Mesas Estáticas</h2>
        <p>Estas mesas están hardcodeadas en HTML para probar si el problema es de PHP o CSS:</p>
        
        <div class="test-container" id="static-test">
            <!-- Mesa estática 1 -->
            <div class="test-mesa" style="left: 50px; top: 50px;" id="static-1">
                <div>🍽️</div>
                <div>STATIC-1</div>
                <div style="font-size: 10px;">LIBRE</div>
            </div>
            
            <!-- Mesa estática 2 -->
            <div class="test-mesa mesa-db" style="left: 170px; top: 50px;" id="static-2">
                <div>🍽️</div>
                <div>STATIC-2</div>
                <div style="font-size: 10px;">OCUPADA</div>
            </div>
            
            <!-- Mesa estática 3 -->
            <div class="test-mesa" style="left: 290px; top: 50px;" id="static-3">
                <div>🍽️</div>
                <div>STATIC-3</div>
                <div style="font-size: 10px;">LIBRE</div>
            </div>
        </div>
        
        <button onclick="testMesasEstaticas()">Test Mesas Estáticas</button>
    </div>
    
    <div class="diagnostic-section">
        <h2>3. Test de Mesas Dinámicas (PHP)</h2>
        <p>Estas mesas se generan desde PHP usando los datos de la BD:</p>
        
        <div class="test-container" id="dynamic-test">
            <?php
            require_once '../config/config.php';
            
            try {
                $stmt = $pdo->query("
                    SELECT m.*, 
                           COALESCE(SUM(CASE WHEN o.estado != 'completada' AND o.estado != 'cancelada' THEN 1 ELSE 0 END), 0) as orden_abierta
                    FROM mesas m 
                    LEFT JOIN ordenes o ON m.id = o.mesa_id 
                    GROUP BY m.id 
                    ORDER BY m.nombre
                ");
                $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($mesas as $index => $mesa) {
                    $posX = 50 + ($index % 4) * 120;
                    $posY = 180 + floor($index / 4) * 100;
                    $bgColor = $mesa['orden_abierta'] > 0 ? '#dc2626' : '#059669';
                    $estado = $mesa['orden_abierta'] > 0 ? 'OCUPADA' : 'LIBRE';
                    
                    echo "<div class='test-mesa' 
                               id='dynamic-{$mesa['id']}' 
                               data-mesa-id='{$mesa['id']}'
                               style='left: {$posX}px; 
                                      top: {$posY}px; 
                                      background: {$bgColor};'>";
                    echo "<div>🍽️</div>";
                    echo "<div>" . htmlspecialchars($mesa['nombre']) . "</div>";
                    echo "<div style='font-size: 10px;'>{$estado}</div>";
                    echo "</div>";
                }
            } catch (PDOException $e) {
                echo "<div style='color: red; padding: 20px;'>Error BD: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
        
        <button onclick="testMesasDinamicas()">Test Mesas Dinámicas</button>
    </div>
    
    <div class="diagnostic-section">
        <h2>4. Log de Diagnóstico</h2>
        <div class="log" id="diagnostic-log"></div>
        <button onclick="limpiarLog()">Limpiar Log</button>
    </div>

    <script>
        function log(mensaje) {
            const logElement = document.getElementById('diagnostic-log');
            const timestamp = new Date().toLocaleTimeString();
            logElement.textContent += `[${timestamp}] ${mensaje}\n`;
            logElement.scrollTop = logElement.scrollHeight;
            console.log(mensaje);
        }
        
        function limpiarLog() {
            document.getElementById('diagnostic-log').textContent = '';
        }
        
        function cargarDatosBD() {
            log('Cargando datos de la base de datos...');
            
            fetch('verificar_datos.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('db-info').innerHTML = data;
                    log('Datos de BD cargados correctamente');
                })
                .catch(error => {
                    log('Error cargando datos BD: ' + error);
                });
        }
        
        function testMesasEstaticas() {
            log('=== TEST MESAS ESTÁTICAS ===');
            
            const mesas = document.querySelectorAll('#static-test .test-mesa');
            log(`Mesas estáticas encontradas: ${mesas.length}`);
            
            mesas.forEach((mesa, index) => {
                const rect = mesa.getBoundingClientRect();
                const style = window.getComputedStyle(mesa);
                
                log(`Mesa estática ${index + 1}:`);
                log(`  ID: ${mesa.id}`);
                log(`  Visible: ${rect.width > 0 && rect.height > 0}`);
                log(`  Posición: ${mesa.style.left}, ${mesa.style.top}`);
                log(`  Dimensiones: ${rect.width}x${rect.height}`);
                log(`  Display: ${style.display}`);
                log(`  Z-Index: ${style.zIndex}`);
            });
        }
        
        function testMesasDinamicas() {
            log('=== TEST MESAS DINÁMICAS ===');
            
            const mesas = document.querySelectorAll('#dynamic-test .test-mesa');
            log(`Mesas dinámicas encontradas: ${mesas.length}`);
            
            mesas.forEach((mesa, index) => {
                const rect = mesa.getBoundingClientRect();
                const style = window.getComputedStyle(mesa);
                
                log(`Mesa dinámica ${index + 1}:`);
                log(`  ID: ${mesa.id}`);
                log(`  Mesa ID: ${mesa.dataset.mesaId}`);
                log(`  Visible: ${rect.width > 0 && rect.height > 0}`);
                log(`  Posición: ${mesa.style.left}, ${mesa.style.top}`);
                log(`  Dimensiones: ${rect.width}x${rect.height}`);
                log(`  Display: ${style.display}`);
                log(`  Background: ${style.backgroundColor}`);
            });
        }
        
        // Auto-ejecutar tests al cargar
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Sistema de diagnóstico iniciado');
            
            setTimeout(() => {
                cargarDatosBD();
                testMesasEstaticas();
                testMesasDinamicas();
            }, 500);
        });
    </script>
</body>
</html>
