<?php
require_once '../conexion.php';
$pdo = conexion();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

$sql = "SELECT * FROM productos WHERE 1";
$params = [];

if ($cat_id) {
    $sql .= " AND type = ?";
    $params[] = $cat_id;
}
if ($q !== '') {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$sql .= " ORDER BY nombre LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header('Content-Type: application/json');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>