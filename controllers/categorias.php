<?php
require_once '../conexion.php';
$pdo = conexion();
$stmt = $pdo->query("SELECT id, nombre FROM type ORDER BY nombre");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>