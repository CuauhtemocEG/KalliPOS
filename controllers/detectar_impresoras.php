<?php
header('Content-Type: application/json');

try {
    $impresoras = [];
    $os = strtolower(PHP_OS);
    
    if (strpos($os, 'darwin') !== false) {
        // macOS - Detectar impresoras reales
        
        // Método 1: lpstat configuradas
        $output = shell_exec('lpstat -v 2>/dev/null');
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
        
        // Método 2: USB directo (solo si no duplica)
        $usbOutput = shell_exec('system_profiler SPUSBDataType 2>/dev/null');
        if ($usbOutput) {
            if (preg_match_all('/([^:\n]+Printer[^:\n]*):.*?Manufacturer: ([^\n]+)/is', $usbOutput, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $nombreUSB = trim($match[1]);
                    $fabricante = trim($match[2]);
                    
                    if (!empty($nombreUSB) && !empty($fabricante)) {
                        // Verificar si ya existe por lpstat
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
    
    // Solo agregar defaults si NO hay impresoras reales
    if (empty($impresoras)) {
        $impresoras = [
            ['nombre' => 'ThermalPrinter80mm', 'tipo' => 'Térmica Genérica', 'estado' => 'Manual', 'puerto' => 'USB/LAN'],
            ['nombre' => 'EPSON-TM-T20', 'tipo' => 'Térmica EPSON', 'estado' => 'Manual', 'puerto' => 'USB/LAN'],
            ['nombre' => 'Star-TSP143', 'tipo' => 'Térmica Star', 'estado' => 'Manual', 'puerto' => 'USB/LAN']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'impresoras' => $impresoras,
        'sistema' => $os,
        'mensaje' => count($impresoras) . ' impresoras detectadas'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'impresoras' => []
    ]);
}
?>
