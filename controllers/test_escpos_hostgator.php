<?php
/**
 * Archivo de prueba para comandos ESC/POS en HostGator
 * Acceso directo: /controllers/test_escpos_hostgator.php
 */

require_once '../conexion.php';
require_once 'imprimir_termica.php';

try {
    echo "<h1>🧪 Prueba de Comandos ESC/POS para HostGator</h1>";
    echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<hr>";
    
    // Crear impresora de prueba
    $impresora = new ImpresorTermica();
    
    // Logo de prueba
    echo "<h3>1. Probando logo configurado...</h3>";
    $logoResult = $impresora->imagenConfigurada();
    echo $logoResult ? "✅ Logo cargado correctamente<br>" : "❌ Error al cargar logo<br>";
    
    // Ticket de prueba
    echo "<h3>2. Generando ticket de prueba...</h3>";
    $impresora->texto('KALLI JAGUAR', 'center', true, 'large');
    $impresora->saltoLinea();
    $impresora->texto('=== PRUEBA HOSTGATOR ===', 'center', true);
    $impresora->saltoLinea();
    $impresora->texto('Fecha: ' . date('d/m/Y H:i:s'), 'left');
    $impresora->texto('Servidor: HostGator', 'left');
    $impresora->texto('Sistema: KalliPOS', 'left');
    $impresora->saltoLinea();
    $impresora->linea('=', 32);
    $impresora->saltoLinea();
    
    // Productos de prueba
    $productos_prueba = [
        ['nombre' => 'Producto de Prueba 1', 'cantidad' => 2, 'precio' => 15.50],
        ['nombre' => 'Producto de Prueba 2', 'cantidad' => 1, 'precio' => 25.00],
        ['nombre' => 'Producto de Prueba 3', 'cantidad' => 3, 'precio' => 8.75]
    ];
    
    $impresora->tablaProductos($productos_prueba);
    
    $total_prueba = 2*15.50 + 1*25.00 + 3*8.75;
    $impresora->saltoLinea();
    $impresora->texto('TOTAL: $' . number_format($total_prueba, 2), 'right', true, 'large');
    $impresora->saltoLinea();
    
    $impresora->linea('=', 32);
    $impresora->texto('Prueba exitosa en HostGator', 'center');
    $impresora->cortar();
    
    // Obtener comandos
    $comandos = $impresora->obtenerComandos();
    $tamaño = strlen($comandos);
    
    echo "✅ Ticket generado correctamente<br>";
    echo "<strong>Tamaño:</strong> " . number_format($tamaño) . " bytes<br>";
    echo "<strong>Comandos ESC/POS:</strong> " . ($tamaño > 0 ? "Válidos ✅" : "Error ❌") . "<br>";
    
    echo "<hr>";
    
    // Botones de acción
    echo '<div style="margin: 20px 0;">';
    echo '<a href="ticket_escpos_hostgator.php?orden_id=test&modo=preview" target="_blank" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">📋 Ver Interface Completa</a> ';
    echo '<a href="?download=1" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">💾 Descargar .PRN</a> ';
    echo '<a href="?raw=1" target="_blank" style="background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">🔍 Ver Comandos Crudos</a>';
    echo '</div>';
    
    // Manejo de descargas
    if (isset($_GET['download'])) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="test_escpos_hostgator.prn"');
        echo $comandos;
        exit;
    }
    
    if (isset($_GET['raw'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "// Comandos ESC/POS de prueba para HostGator\n";
        echo "// Generado: " . date('Y-m-d H:i:s') . "\n";
        echo "// Tamaño: " . number_format($tamaño) . " bytes\n\n";
        echo $comandos;
        exit;
    }
    
    // Información del sistema
    echo "<h3>3. Información del Sistema:</h3>";
    echo "<ul>";
    echo "<li><strong>PHP:</strong> " . PHP_VERSION . "</li>";
    echo "<li><strong>Servidor:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "</li>";
    echo "<li><strong>Host:</strong> " . $_SERVER['HTTP_HOST'] . "</li>";
    echo "<li><strong>Shell exec:</strong> " . (function_exists('shell_exec') ? "Disponible ⚠️" : "Bloqueado ✅ (Normal en HostGator)") . "</li>";
    echo "<li><strong>GD Library:</strong> " . (extension_loaded('gd') ? "Disponible ✅" : "No disponible ❌") . "</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>4. Limitaciones de HostGator:</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeaa7;'>";
    echo "<ul>";
    echo "<li>❌ No se puede usar <code>lpr</code> o comandos shell</li>";
    echo "<li>❌ No hay acceso directo a impresoras USB</li>";
    echo "<li>✅ Los comandos ESC/POS se generan correctamente</li>";
    echo "<li>✅ Funciona con métodos indirectos (copiar/pegar, archivos .PRN)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>5. Soluciones Funcionales:</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; border: 1px solid #4caf50;'>";
    echo "<ol>";
    echo "<li><strong>Copiar comandos:</strong> Usar software de impresión térmica en tu laptop</li>";
    echo "<li><strong>Archivo .PRN:</strong> Descargar y enviar directamente a impresora</li>";
    echo "<li><strong>Navegador:</strong> Usar el método de impresión por navegador web</li>";
    echo "<li><strong>APIs externas:</strong> PrintNode, CloudPrint, etc.</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error de Prueba</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    
    echo "<h3>Diagnóstico:</h3>";
    echo "<ul>";
    echo "<li>Verificar que existe el archivo ../conexion.php</li>";
    echo "<li>Verificar que existe el archivo imprimir_termica.php</li>";
    echo "<li>Verificar configuración de base de datos</li>";
    echo "<li>Verificar permisos de archivos</li>";
    echo "</ul>";
}
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    max-width: 800px; 
    margin: 20px auto; 
    padding: 20px; 
    line-height: 1.6; 
}
h1 { color: #007bff; }
h3 { color: #333; margin-top: 25px; }
code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
hr { margin: 20px 0; border: 1px solid #ddd; }
</style>
