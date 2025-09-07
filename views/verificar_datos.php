<?php
require_once '../config/config.php';

echo "<h2>Verificación de Datos - Sistema POS</h2>";

try {
    // Verificar mesas
    $stmt = $pdo->query("
        SELECT m.*, 
               COALESCE(SUM(CASE WHEN o.estado != 'completada' AND o.estado != 'cancelada' THEN 1 ELSE 0 END), 0) as orden_abierta
        FROM mesas m 
        LEFT JOIN ordenes o ON m.id = o.mesa_id 
        GROUP BY m.id 
        ORDER BY m.nombre
    ");
    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Mesas encontradas: " . count($mesas) . "</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Ubicación</th><th>Capacidad</th><th>Órdenes Abiertas</th></tr>";
    
    foreach ($mesas as $mesa) {
        echo "<tr>";
        echo "<td>" . $mesa['id'] . "</td>";
        echo "<td>" . $mesa['nombre'] . "</td>";
        echo "<td>" . $mesa['ubicacion'] . "</td>";
        echo "<td>" . $mesa['capacidad'] . "</td>";
        echo "<td>" . $mesa['orden_abierta'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar layouts
    $stmt = $pdo->query("SELECT * FROM mesa_layouts ORDER BY mesa_id");
    $layouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Layouts encontrados: " . count($layouts) . "</h3>";
    if (count($layouts) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Mesa ID</th><th>Pos X</th><th>Pos Y</th><th>Width</th><th>Height</th><th>Rotation</th></tr>";
        
        foreach ($layouts as $layout) {
            echo "<tr>";
            echo "<td>" . $layout['mesa_id'] . "</td>";
            echo "<td>" . $layout['pos_x'] . "</td>";
            echo "<td>" . $layout['pos_y'] . "</td>";
            echo "<td>" . $layout['width'] . "</td>";
            echo "<td>" . $layout['height'] . "</td>";
            echo "<td>" . $layout['rotation'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Información de PHP
    echo "<h3>Información del Sistema</h3>";
    echo "<p>PHP Version: " . PHP_VERSION . "</p>";
    echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error de base de datos: " . $e->getMessage() . "</p>";
}
?>
