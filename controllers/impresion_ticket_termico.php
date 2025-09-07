<?php
require_once '../conexion.php';
require_once 'imprimir_termica.php';

$pdo = conexion();

// Obtener ID de orden
$orden_id = $_GET['orden_id'] ?? 0;

if (!$orden_id) {
    die('ID de orden requerido');
}

try {
    // Obtener configuración de impresora térmica
    $stmt = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = ?");
    $stmt->execute(['nombre_impresora']);
    $nombreImpresora = $stmt->fetchColumn();
    
    // Obtener datos de la orden
    $stmt = $pdo->prepare("
        SELECT o.*, m.nombre AS mesa_nombre 
        FROM ordenes o 
        JOIN mesas m ON o.mesa_id = m.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch();
    
    if (!$orden) {
        die('Orden no encontrada');
    }
    
    // Obtener productos de la orden (SOLO los preparados y cantidad > 0)
    $stmt = $pdo->prepare("
        SELECT p.nombre, op.preparado AS cantidad, p.precio,
               (op.preparado * p.precio) as subtotal
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        WHERE op.orden_id = ? AND op.preparado > 0
        ORDER BY p.nombre
    ");
    $stmt->execute([$orden_id]);
    $productos = $stmt->fetchAll();
    
    // Crear impresora térmica
    $impresora = new ImpresorTermica();
    
    // FUNCIONES COPIADAS EXACTAMENTE DEL TEST_GIGANTE.PHP QUE FUNCIONA
    function procesarLogoBlackGigante() {
        $rutaOriginal = '../assets/img/LogoBlack.png';
        
        if (!file_exists($rutaOriginal)) {
            // Intentar ruta alternativa
            $rutaOriginal = dirname(__DIR__) . '/assets/img/LogoBlack.png';
            if (!file_exists($rutaOriginal)) {
                return false;
            }
        }
        
        // Analizar imagen original
        $info = getimagesize($rutaOriginal);
        
        // Cargar imagen original
        $imagenOriginal = imagecreatefrompng($rutaOriginal);
        if (!$imagenOriginal) {
            return false;
        }
        
        $anchoOriginal = $info[0];
        $altoOriginal = $info[1];
        
        // TAMAÑO GIGANTE: 3x más que la versión mejorada
        // Versión anterior: 120x60
        // Nueva versión: 360x180 (3x más grande!)
        $anchoFinal = 360;
        $altoFinal = intval(($altoOriginal * $anchoFinal) / $anchoOriginal);
        
        // Limitar altura pero muy generoso
        if ($altoFinal > 180) {
            $altoFinal = 180;
            $anchoFinal = intval(($anchoOriginal * $altoFinal) / $altoOriginal);
        }
        
        // Crear imagen redimensionada
        $imagenGigante = imagecreatetruecolor($anchoFinal, $altoFinal);
        $blanco = imagecolorallocate($imagenGigante, 255, 255, 255);
        imagefill($imagenGigante, 0, 0, $blanco);
        
        // Redimensionar con máxima calidad
        $resultado = imagecopyresampled(
            $imagenGigante, $imagenOriginal,
            0, 0, 0, 0,
            $anchoFinal, $altoFinal, $anchoOriginal, $altoOriginal
        );
        
        if (!$resultado) {
            return false;
        }
        
        // Guardar imagen gigante
        $rutaGigante = dirname(__DIR__) . '/assets/img/LogoBlack_gigante.png';
        imagepng($imagenGigante, $rutaGigante);
        
        // Análisis de umbrales para imagen gigante (igual al test)
        $umbrales = [
            'muy_conservador' => 40,
            'conservador' => 80,
            'medio' => 120,
            'agresivo' => 160,
            'muy_agresivo' => 200
        ];
        
        $pixelesTotales = $anchoFinal * $altoFinal;
        $mejorUmbral = 80;
        $mejorBalance = 0;
        
        foreach ($umbrales as $nombre => $umbral) {
            $pixelesNegros = 0;
            
            // Muestreo para imagen gigante (cada 4 píxeles para velocidad)
            for ($y = 0; $y < $altoFinal; $y += 2) {
                for ($x = 0; $x < $anchoFinal; $x += 2) {
                    $color = imagecolorat($imagenGigante, $x, $y);
                    $r = ($color >> 16) & 0xFF;
                    $g = ($color >> 8) & 0xFF;
                    $b = $color & 0xFF;
                    $gris = intval(0.299 * $r + 0.587 * $g + 0.114 * $b);
                    
                    if ($gris < $umbral) {
                        $pixelesNegros += 4; // Compensar el muestreo
                    }
                }
            }
            
            $porcentaje = round(($pixelesNegros/$pixelesTotales)*100, 1);
            
            // Para imagen gigante, aceptamos rangos más amplios
            if ($porcentaje >= 10 && $porcentaje <= 50 && $porcentaje > $mejorBalance) {
                $mejorBalance = $porcentaje;
                $mejorUmbral = $umbral;
            }
        }
        
        // Limpiar memoria
        imagedestroy($imagenOriginal);
        imagedestroy($imagenGigante);
        
        return [
            'ruta' => $rutaGigante,
            'ancho' => $anchoFinal,
            'alto' => $altoFinal,
            'umbral_recomendado' => $mejorUmbral,
            'pixeles_totales' => $pixelesTotales
        ];
    }
    
    function crearTicketGiganteParaPOS($config) {
        $imagen = imagecreatefrompng($config['ruta']);
        if (!$imagen) {
            return false;
        }
        
        $ancho = $config['ancho'];
        $alto = $config['alto'];
        $umbral = $config['umbral_recomendado'];
        
        // Comando GS v 0 para imagen gigante (EXACTO AL TEST)
        $comandoImagen = "\x1D\x76\x30";
        $comandoImagen .= chr(0);
        
        $anchoBytes = ceil($ancho / 8);
        $comandoImagen .= chr($anchoBytes & 0xFF);
        $comandoImagen .= chr(($anchoBytes >> 8) & 0xFF);
        $comandoImagen .= chr($alto & 0xFF);
        $comandoImagen .= chr(($alto >> 8) & 0xFF);
        
        // Convertir imagen gigante (EXACTO AL TEST)
        $datosImagen = '';
        $pixelesConvertidos = 0;
        
        for ($y = 0; $y < $alto; $y++) {
            for ($x = 0; $x < $anchoBytes * 8; $x += 8) {
                $byte = 0;
                
                for ($bit = 0; $bit < 8; $bit++) {
                    $px = $x + $bit;
                    
                    if ($px < $ancho) {
                        $color = imagecolorat($imagen, $px, $y);
                        
                        $r = ($color >> 16) & 0xFF;
                        $g = ($color >> 8) & 0xFF;
                        $b = $color & 0xFF;
                        $gris = intval(0.299 * $r + 0.587 * $g + 0.114 * $b);
                        
                        if ($gris < $umbral) {
                            $byte |= (1 << (7 - $bit));
                            $pixelesConvertidos++;
                        }
                    }
                }
                
                $datosImagen .= chr($byte);
            }
        }
        
        $comandoImagen .= $datosImagen;
        
        imagedestroy($imagen);
        
        return $comandoImagen;
    }
    
    // IMPLEMENTAR LA IMAGEN GIGANTE EXACTA
    $config = procesarLogoBlackGigante();
    if ($config) {
        $comandoImagenGigante = crearTicketGiganteParaPOS($config);
        if ($comandoImagenGigante) {
            $impresora->agregarComando($comandoImagenGigante);
            $impresora->saltoLinea();
        }
    }
    
    $impresora->texto('KALLI JAGUAR', 'center', true, 'large');
    $impresora->saltoLinea();
    $impresora->texto('Mesa: ' . $orden['mesa_nombre'], 'center');
    $impresora->texto('Orden #: ' . ($orden['codigo'] ?? $orden['id']), 'center');
    $impresora->texto('Fecha: ' . date('d/m/Y H:i', strtotime($orden['creada_en'] ?? $orden['fecha_creacion'] ?? 'now')), 'center');
    $impresora->saltoLinea();
    $impresora->linea('=', 32);
    $impresora->saltoLinea();
    
    // Encabezado de tabla
    $impresora->texto("PRODUCTO         CANT  PRECIO", 'left', true);
    $impresora->linea('-', 32);
    
    // Productos
    $total = 0;
    foreach ($productos as $producto) {
        $nombre = substr($producto['nombre'], 0, 16);
        $cantidad = str_pad($producto['cantidad'], 4, ' ', STR_PAD_LEFT);
        $precio = str_pad('$' . number_format($producto['precio'], 2), 8, ' ', STR_PAD_LEFT);
        
        $linea = str_pad($nombre, 16) . $cantidad . $precio;
        $impresora->texto($linea, 'left');
        
        $total += $producto['subtotal'];
    }
    
    $impresora->linea('-', 32);
    $impresora->saltoLinea();
    
    // Total
    $impresora->texto('TOTAL: $' . number_format($total, 2), 'right', true, 'large');
    $impresora->saltoLinea();
    $impresora->linea('=', 32);
    
    // Código de orden si existe
    if (!empty($orden['codigo'])) {
        $impresora->saltoLinea();
        $impresora->texto('Codigo de Orden:', 'center');
        $impresora->texto($orden['codigo'], 'center', true);
        $impresora->saltoLinea();
    }
    
    $impresora->texto('Gracias por su visita!', 'center');
    $impresora->cortar();
    
    // Verificar si se debe imprimir o solo mostrar comandos
    if ($nombreImpresora && isset($_GET['imprimir']) && $_GET['imprimir'] == '1') {
        // Imprimir directamente
        $resultado = $impresora->imprimir($nombreImpresora);
        
        // Mostrar resultado
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Resultado de Impresión</title>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial; padding: 20px; }
                .success { color: green; }
                .error { color: red; }
                .commands { background: #f5f5f5; padding: 10px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <h2>Ticket Térmico - Orden #" . ($orden['codigo'] ?? $orden['id']) . "</h2>";
        
        if ($resultado === null || trim($resultado) === '') {
            echo "<p class='success'>✅ Ticket enviado correctamente a la impresora: <strong>$nombreImpresora</strong></p>";
        } else {
            echo "<p class='error'>⚠️ Resultado de impresión: " . htmlspecialchars($resultado) . "</p>";
        }
        
        echo "<p><a href='javascript:window.close()'>Cerrar ventana</a></p>
        </body>
        </html>";
        
    } else {
        // Mostrar comandos ESC/POS para depuración o descarga
        $comandos = $impresora->obtenerComandos();
        
        if (isset($_GET['debug'])) {
            // Modo debug - mostrar comandos
            header('Content-Type: text/html; charset=utf-8');
            echo "<!DOCTYPE html>
            <html>
            <head>
                <title>Debug Comandos ESC/POS</title>
                <meta charset='utf-8'>
                <style>
                    body { font-family: monospace; padding: 20px; }
                    .commands { background: #f0f0f0; padding: 15px; border: 1px solid #ccc; }
                </style>
            </head>
            <body>
                <h2>Comandos ESC/POS Generados</h2>
                <div class='commands'>";
            
            // Mostrar comandos en hexadecimal
            $hex = bin2hex($comandos);
            $chunks = str_split($hex, 32);
            foreach ($chunks as $chunk) {
                echo htmlspecialchars($chunk) . "<br>";
            }
            
            echo "</div>
                <p><a href='?orden_id=$orden_id'>Volver</a></p>
            </body>
            </html>";
        } else {
            // Enviar comandos como descarga binaria
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="ticket_orden_' . $orden_id . '.prn"');
            header('Content-Length: ' . strlen($comandos));
            echo $comandos;
        }
    }
    
} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <meta charset='utf-8'>
        <style>body { font-family: Arial; padding: 20px; }</style>
    </head>
    <body>
        <h2>Error al generar ticket</h2>
        <p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>
        <p><a href='javascript:window.close()'>Cerrar</a></p>
    </body>
    </html>";
}
?>
