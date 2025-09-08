<?php
/**
 * Prueba rápida para verificar la estructura de la base de datos
 */

// Solo mostrar información si es una solicitud de prueba
if (!isset($_GET['test'])) {
    die('Agrega ?test=1 a la URL para ejecutar la prueba');
}

require_once '../conexion.php';

try {
    $pdo = conexion();
    
    echo "<h2>🔍 Prueba de Estructura de Base de Datos</h2>";
    
    // Verificar columnas de tabla mesas
    echo "<h3>📋 Columnas de tabla 'mesas':</h3>";
    $stmt = $pdo->query("DESCRIBE mesas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    // Verificar columnas de tabla orden_productos
    echo "<h3>📋 Columnas de tabla 'orden_productos':</h3>";
    $stmt = $pdo->query("DESCRIBE orden_productos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    // Probar consulta corregida
    echo "<h3>🧪 Prueba de consulta corregida:</h3>";
    $stmt = $pdo->prepare("
        SELECT o.*, m.nombre as mesa_nombre
        FROM ordenes o 
        JOIN mesas m ON o.mesa_id = m.id 
        LIMIT 1
    ");
    $stmt->execute();
    $orden = $stmt->fetch();
    
    if ($orden) {
        echo "<p>✅ <strong>Consulta exitosa!</strong> Orden encontrada: #{$orden['id']} - Mesa: {$orden['mesa_nombre']}</p>";
    } else {
        echo "<p>⚠️ No se encontraron órdenes para probar</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>✅ Las consultas funcionan correctamente.</strong></p>";
    echo "<p><a href='ticket_local.php?tipo=prueba'>🧪 Probar ticket de ejemplo</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
