<?php
header('Content-Type: application/json');

try {
    $impresoras = [];
    $os = strtolower(PHP_OS);
    $isWebServer = !empty($_SERVER['HTTP_HOST']); // Detectar si es servidor web
    $comandosDisponibles = [];
    
    // Verificar qué comandos están disponibles
    $comandosTestear = ['lpstat', 'lpr', 'system_profiler', 'lsusb', 'dmesg'];
    foreach ($comandosTestear as $comando) {
        if (function_exists('shell_exec')) {
            $test = @shell_exec("which $comando 2>/dev/null");
            if (!empty($test)) {
                $comandosDisponibles[] = $comando;
            }
        }
    }
    
    // Detectar si shell_exec está habilitado
    $shellExecEnabled = function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))));
    
    if ($shellExecEnabled) {
        if (strpos($os, 'darwin') !== false) {
            // macOS - Detectar impresoras reales
            detectarImpresorasMacOS($impresoras);
        } elseif (strpos($os, 'linux') !== false) {
            // Linux - Detectar impresoras
            detectarImpresorasLinux($impresoras);
        } elseif (strpos($os, 'win') !== false) {
            // Windows - Detectar impresoras
            detectarImpresorasWindows($impresoras);
        }
    }
    
    // Intentar detectar por archivos de sistema disponibles (método alternativo)
    if (empty($impresoras)) {
        detectarImpresorasPorArchivos($impresoras);
    }
    
    // Detectar impresoras de red/IP comunes
    detectarImpresorasRed($impresoras);
    
    // Solo agregar defaults si NO hay impresoras reales
    if (empty($impresoras)) {
        $impresoras = [
            ['nombre' => 'ThermalPrinter80mm', 'tipo' => 'Térmica Genérica', 'estado' => 'Manual', 'puerto' => 'USB/LAN'],
            ['nombre' => 'EPSON-TM-T20', 'tipo' => 'Térmica EPSON', 'estado' => 'Manual', 'puerto' => 'USB/LAN'],
            ['nombre' => 'Star-TSP143', 'tipo' => 'Térmica Star', 'estado' => 'Manual', 'puerto' => 'USB/LAN'],
            ['nombre' => 'Generic-ESC-POS', 'tipo' => 'Térmica ESC/POS', 'estado' => 'Manual', 'puerto' => 'USB/LAN'],
            ['nombre' => 'Brother-QL-720NW', 'tipo' => 'Etiquetas Brother', 'estado' => 'Manual', 'puerto' => 'WiFi/LAN']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'impresoras' => $impresoras,
        'sistema' => $os,
        'webserver' => $isWebServer,
        'shell_exec_enabled' => $shellExecEnabled,
        'comandos_disponibles' => $comandosDisponibles,
        'mensaje' => count($impresoras) . ' impresoras detectadas',
        'debug' => [
            'os' => PHP_OS,
            'disabled_functions' => ini_get('disable_functions'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'impresoras' => [],
        'debug' => [
            'error_details' => $e->getTraceAsString()
        ]
    ]);
}

// === FUNCIONES DE DETECCIÓN ===

function detectarImpresorasMacOS(&$impresoras) {
    // Método 1: lpstat configuradas
    $output = @shell_exec('lpstat -v 2>/dev/null');
    if ($output) {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/(?:device for|dispositivo para) (.+?): (.+)/', $line, $matches)) {
                $nombre = trim($matches[1]);
                $dispositivo = trim($matches[2]);
                
                if (!empty($nombre)) {
                    $puerto = 'Sistema';
                    if (stripos($dispositivo, 'usb') !== false) {
                        $puerto = 'USB';
                    } elseif (stripos($dispositivo, 'dnssd') !== false) {
                        $puerto = 'WiFi';
                    }
                    
                    $tipo = 'Impresora';
                    if (stripos($nombre . ' ' . $dispositivo, 'gprinter') !== false ||
                        stripos($nombre . ' ' . $dispositivo, 'thermal') !== false ||
                        stripos($nombre . ' ' . $dispositivo, 'receipt') !== false) {
                        $tipo = 'Térmica';
                    }
                    
                    $impresoras[] = [
                        'nombre' => $nombre,
                        'tipo' => $tipo,
                        'estado' => 'Detectada',
                        'puerto' => $puerto
                    ];
                }
            }
        }
    }

    // Método 2: USB directo
    $usbOutput = @shell_exec('system_profiler SPUSBDataType 2>/dev/null');
    if ($usbOutput) {
        if (preg_match_all('/([^:\n]+Printer[^:\n]*):.*?Manufacturer: ([^\n]+)/is', $usbOutput, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $nombreUSB = trim($match[1]);
                $fabricante = trim($match[2]);
                
                if (!empty($nombreUSB) && !empty($fabricante)) {
                    // Verificar si ya existe
                    $existe = false;
                    foreach ($impresoras as $imp) {
                        if (stripos($imp['nombre'], $nombreUSB) !== false || 
                            stripos($nombreUSB, $imp['nombre']) !== false) {
                            $existe = true;
                            break;
                        }
                    }
                    
                    if (!$existe) {
                        $impresoras[] = [
                            'nombre' => $fabricante . ' ' . $nombreUSB,
                            'tipo' => 'USB Térmica',
                            'estado' => 'USB Conectada',
                            'puerto' => 'USB'
                        ];
                    }
                }
            }
        }
    }
}

function detectarImpresorasLinux(&$impresoras) {
    // Método 1: lpstat (CUPS)
    $output = @shell_exec('lpstat -v 2>/dev/null');
    if ($output) {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/device for (.+?): (.+)/', $line, $matches)) {
                $nombre = trim($matches[1]);
                $dispositivo = trim($matches[2]);
                
                if (!empty($nombre)) {
                    $puerto = 'Sistema';
                    if (stripos($dispositivo, 'usb') !== false) {
                        $puerto = 'USB';
                    } elseif (stripos($dispositivo, 'socket') !== false || stripos($dispositivo, 'ipp') !== false) {
                        $puerto = 'Red';
                    }
                    
                    $impresoras[] = [
                        'nombre' => $nombre,
                        'tipo' => 'Impresora Linux',
                        'estado' => 'CUPS Detectada',
                        'puerto' => $puerto
                    ];
                }
            }
        }
    }
    
    // Método 2: lsusb para dispositivos USB
    $usbOutput = @shell_exec('lsusb 2>/dev/null');
    if ($usbOutput) {
        $lines = explode("\n", $usbOutput);
        foreach ($lines as $line) {
            if (stripos($line, 'printer') !== false || stripos($line, 'epson') !== false || 
                stripos($line, 'canon') !== false || stripos($line, 'brother') !== false ||
                stripos($line, 'star') !== false || stripos($line, 'thermal') !== false) {
                
                if (preg_match('/ID ([0-9a-f]{4}):([0-9a-f]{4}) (.+)/', $line, $matches)) {
                    $vendor = $matches[1];
                    $product = $matches[2];
                    $name = trim($matches[3]);
                    
                    $impresoras[] = [
                        'nombre' => $name,
                        'tipo' => 'USB Printer',
                        'estado' => 'USB Detectada',
                        'puerto' => 'USB',
                        'vendor_id' => $vendor,
                        'product_id' => $product
                    ];
                }
            }
        }
    }
}

function detectarImpresorasWindows(&$impresoras) {
    // Windows - usar wmic
    $output = @shell_exec('wmic printer get name,drivername,portname 2>nul');
    if ($output) {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !stripos($line, 'name') && !stripos($line, 'drivername')) {
                $parts = preg_split('/\s+/', $line, 3);
                if (count($parts) >= 2) {
                    $impresoras[] = [
                        'nombre' => $parts[0],
                        'tipo' => 'Windows Printer',
                        'estado' => 'Windows Detectada',
                        'puerto' => $parts[1] ?? 'Unknown'
                    ];
                }
            }
        }
    }
}

function detectarImpresorasPorArchivos(&$impresoras) {
    // Detectar por archivos de configuración disponibles
    $archivos = [
        '/etc/cups/printers.conf',
        '/usr/share/cups/model/',
        '/proc/bus/usb/devices'
    ];
    
    foreach ($archivos as $archivo) {
        if (file_exists($archivo) && is_readable($archivo)) {
            if ($archivo === '/etc/cups/printers.conf') {
                $contenido = @file_get_contents($archivo);
                if ($contenido && preg_match_all('/<Printer (.+?)>/', $contenido, $matches)) {
                    foreach ($matches[1] as $nombre) {
                        $impresoras[] = [
                            'nombre' => trim($nombre),
                            'tipo' => 'CUPS Config',
                            'estado' => 'Configurada',
                            'puerto' => 'Sistema'
                        ];
                    }
                }
            }
        }
    }
}

function detectarImpresorasRed(&$impresoras) {
    // Detectar impresoras de red comunes por puertos conocidos
    $ipLocal = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '127.0.0.1';
    
    // Extraer rango de red local
    if (preg_match('/(\d+\.\d+\.\d+)\./', $ipLocal, $matches)) {
        $baseIP = $matches[1];
        
        // IPs comunes para impresoras
        $ipsComunes = [
            $baseIP . '.100',
            $baseIP . '.101',
            $baseIP . '.102',
            $baseIP . '.200',
            $baseIP . '.250'
        ];
        
        foreach ($ipsComunes as $ip) {
            // Verificar puerto 9100 (puerto estándar de impresoras de red)
            $connection = @fsockopen($ip, 9100, $errno, $errstr, 1);
            if ($connection) {
                fclose($connection);
                $impresoras[] = [
                    'nombre' => 'Network Printer ' . $ip,
                    'tipo' => 'Red/IP',
                    'estado' => 'Red Detectada',
                    'puerto' => $ip . ':9100'
                ];
            }
        }
    }
}
?>
