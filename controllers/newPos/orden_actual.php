<?php
require_once '../../conexion.php';
$pdo = conexion();

$orden_id = intval($_GET['orden_id'] ?? 0);
if (!$orden_id) {
    echo json_encode(['items'=>[], 'subtotal'=>0, 'descuento'=>0, 'impuestos'=>0, 'total'=>0]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        op.id,
        op.producto_id, 
        p.nombre, 
        op.cantidad,
        COALESCE(op.preparado, 0) as preparado,
        COALESCE(op.cancelado, 0) as cancelado,
        p.precio,
        (op.cantidad * p.precio) as subtotal_item
    FROM orden_productos op
    JOIN productos p ON op.producto_id = p.id
    WHERE op.orden_id = ? AND op.estado != 'eliminado'
    ORDER BY op.id
");
$stmt->execute([$orden_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
$subtotal = 0;
$total_cancelado = 0;

foreach ($productos as $producto) {
    $cantidad = intval($producto['cantidad']);
    $preparado = intval($producto['preparado']);
    $cancelado = intval($producto['cancelado']);
    $precio = floatval($producto['precio']);
    
    // Calcular totales
    $subtotal_producto = $cantidad * $precio;
    $cancelado_monto = $cancelado * $precio;
    
    if ($cancelado > 0) {
        $total_cancelado += $cancelado_monto;
    }
    
    // Solo agregar al subtotal los productos no cancelados
    if ($cantidad > $cancelado) {
        $subtotal += ($cantidad - $cancelado) * $precio;
    }
    
    $items[] = [
        'id' => $producto['id'],
        'producto_id' => $producto['producto_id'],
        'nombre' => $producto['nombre'],
        'cantidad' => $cantidad,
        'preparado' => $preparado,
        'cancelado' => $cancelado,
        'precio' => $precio,
        'subtotal' => $subtotal_producto
    ];
}

$descuento = 0;
$impuestos = 0;
$total = $subtotal - $descuento + $impuestos;

echo json_encode([
    'items' => $items,
    'subtotal' => $subtotal,
    'descuento' => $descuento,
    'impuestos' => $impuestos,
    'total' => $total,
    'total_cancelado' => $total_cancelado,
    'productos_cancelados' => array_filter($items, function($item) { 
        return intval($item['cancelado']) > 0; 
    })
]);
?>