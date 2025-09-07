<?php
require_once '../conexion.php';
$pdo = conexion();
$detalles = $pdo->query(
    "SELECT m.nombre AS mesa, m.id AS mesa_id, op.id AS op_id, p.nombre AS producto, op.cantidad, op.preparado, op.cancelado, (op.cantidad-op.preparado-op.cancelado) AS faltan
     FROM orden_productos op
     JOIN ordenes o ON op.orden_id = o.id
     JOIN mesas m ON o.mesa_id = m.id
     JOIN productos p ON op.producto_id = p.id
     WHERE p.categoria = 'comidas' AND (op.cantidad-op.preparado-op.cancelado) > 0
     AND o.estado='abierta'
     ORDER BY m.nombre"
)->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($detalles);
?>