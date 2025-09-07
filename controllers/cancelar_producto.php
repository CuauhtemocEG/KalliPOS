<?php
require_once '../conexion.php';
$pdo = conexion();
$op_id = $_POST['op_id'];
$marcar = isset($_POST['marcar']) ? intval($_POST['marcar']) : 1;

// Consulta la cantidad y cancelado/preparado actuales
$stmt = $pdo->prepare("SELECT cantidad, preparado, cancelado FROM orden_productos WHERE id=?");
$stmt->execute([$op_id]);
$row = $stmt->fetch();

if ($row) {
    $pendientes = $row['cantidad'] - $row['preparado'] - $row['cancelado'];
    $a_cancelar = min($pendientes, max(1, $marcar));
    $nuevo_cancelado = $row['cancelado'] + $a_cancelar;
    $pdo->prepare("UPDATE orden_productos SET cancelado=? WHERE id=?")
        ->execute([$nuevo_cancelado, $op_id]);
    echo json_encode(["status"=>"ok", "msg"=>"Se cancelaron $a_cancelar unidades"]);
} else {
    echo json_encode(["status"=>"error", "msg"=>"No se encontró el producto"]);
}
exit;
?>