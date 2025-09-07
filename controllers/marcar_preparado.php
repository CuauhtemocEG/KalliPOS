<?php
require_once '../auth-check.php'; // Para obtener getUserInfo()
require_once '../conexion.php';

$pdo = conexion();

// Obtener información del usuario actual
$userInfo = getUserInfo();
$usuario_id = $userInfo['id'] ?? 1; // Usar ID 1 como fallback si no hay usuario

$op_id = $_POST['op_id'];
$marcar = isset($_POST['marcar']) ? intval($_POST['marcar']) : 1;

// Consulta la cantidad y preparado/cancelado actuales
$stmt = $pdo->prepare("SELECT cantidad, preparado, cancelado FROM orden_productos WHERE id=?");
$stmt->execute([$op_id]);
$row = $stmt->fetch();

if ($row) {
    $pendientes = $row['cantidad'] - $row['preparado'] - $row['cancelado'];
    $a_preparar = min($pendientes, max(1, $marcar));
    $nuevo_preparado = $row['preparado'] + $a_preparar;
    
    // Actualizar con el usuario que marcó como preparado
    $pdo->prepare("UPDATE orden_productos SET preparado=?, preparado_por_usuario_id=? WHERE id=?")
        ->execute([$nuevo_preparado, $usuario_id, $op_id]);
        
    echo json_encode(["status"=>"ok", "msg"=>"Se marcaron $a_preparar como preparados"]);
} else {
    echo json_encode(["status"=>"error", "msg"=>"No se encontró el producto"]);
}
exit;
?>