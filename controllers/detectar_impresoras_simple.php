<?php
// Configurar para JSON limpio
error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_level()) ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

// Función simple y segura
function detectarImpresorasSimple() {
    $resultado = [
        'success' => true,
        'impresoras' => [],
        'debug' => [],
        'mensaje' => 'Detección simple ejecutada'
    ];
    
    try {
        // Información básica del sistema
        $resultado['debug']['php_version'] = PHP_VERSION;
        $resultado['debug']['os'] = PHP_OS;
        $resultado['debug']['sapi'] = php_sapi_name();
        
        // Verificar funciones disponibles
        $funciones = ['shell_exec', 'exec', 'system'];
        $funcionesDisponibles = [];
        
        foreach ($funciones as $func) {
            if (function_exists($func)) {
                $funcionesDisponibles[] = $func;
            }
        }
        
        $resultado['debug']['funciones_disponibles'] = $funcionesDisponibles;
        $resultado['debug']['disabled_functions'] = ini_get('disable_functions');
        
        // Intentar detección básica solo si shell_exec está disponible
        if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
            $resultado['debug']['shell_exec'] = 'disponible';
            
            // Probar comando simple
            $test = @shell_exec('echo "test" 2>/dev/null');
            if ($test !== null) {
                $resultado['debug']['shell_exec_test'] = 'exitoso';
                
                // Intentar lpstat (CUPS)
                $lpstat = @shell_exec('lpstat -v 2>/dev/null');
                if ($lpstat && strlen(trim($lpstat)) > 0) {
                    $resultado['debug']['lpstat_output'] = substr(trim($lpstat), 0, 200);
                    
                    // Parsear salida de lpstat
                    $lines = explode("\n", $lpstat);
                    foreach ($lines as $line) {
                        if (preg_match('/device for (.+?): (.+)/', $line, $matches)) {
                            $resultado['impresoras'][] = [
                                'nombre' => trim($matches[1]),
                                'tipo' => 'CUPS',
                                'estado' => 'Detectada',
                                'puerto' => trim($matches[2])
                            ];
                        }
                    }
                }
                
                // Intentar lsusb (Linux USB)
                $lsusb = @shell_exec('lsusb 2>/dev/null');
                if ($lsusb && strlen(trim($lsusb)) > 0) {
                    $resultado['debug']['lsusb_found'] = true;
                    
                    $lines = explode("\n", $lsusb);
                    foreach ($lines as $line) {
                        if (stripos($line, 'printer') !== false) {
                            $resultado['impresoras'][] = [
                                'nombre' => 'USB Printer: ' . trim($line),
                                'tipo' => 'USB',
                                'estado' => 'USB Detectada',
                                'puerto' => 'USB'
                            ];
                        }
                    }
                }
                
            } else {
                $resultado['debug']['shell_exec_test'] = 'falló';
            }
        } else {
            $resultado['debug']['shell_exec'] = 'no_disponible';
        }
        
        // Si no se encontraron impresoras, agregar opciones manuales
        if (empty($resultado['impresoras'])) {
            $resultado['impresoras'] = [
                [
                    'nombre' => 'EPSON TM-T88V',
                    'tipo' => 'Térmica ESC/POS',
                    'estado' => 'Manual',
                    'puerto' => 'USB/Red'
                ],
                [
                    'nombre' => 'Star TSP143',
                    'tipo' => 'Térmica',
                    'estado' => 'Manual',
                    'puerto' => 'USB/Red'
                ],
                [
                    'nombre' => 'Generic Thermal',
                    'tipo' => 'Térmica Genérica',
                    'estado' => 'Manual',
                    'puerto' => 'USB/Red/WiFi'
                ]
            ];
            
            $resultado['mensaje'] = 'No se detectaron impresoras automáticamente. Mostrando opciones manuales.';
        } else {
            $resultado['mensaje'] = count($resultado['impresoras']) . ' impresoras detectadas automáticamente.';
        }
        
    } catch (Exception $e) {
        $resultado['success'] = false;
        $resultado['mensaje'] = 'Error en detección: ' . $e->getMessage();
        $resultado['debug']['error'] = $e->getMessage();
    }
    
    return $resultado;
}

// Ejecutar detección
try {
    $resultado = detectarImpresorasSimple();
    echo json_encode($resultado, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error crítico: ' . $e->getMessage(),
        'impresoras' => [],
        'debug' => ['error_critico' => $e->getMessage()]
    ]);
}
?>
