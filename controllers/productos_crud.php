<?php
header('Content-Type: application/json');
require_once '../conexion.php';

$pdo = conexion();

function guardarImagen($archivo) {
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $nombreTmp = $archivo['tmp_name'];
        $nombreOriginal = basename($archivo['name']);
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $permitidas)) return null;
        $nuevoNombre = uniqid().".".$ext;
        move_uploaded_file($nombreTmp, "../assets/img/" . $nuevoNombre);
        return $nuevoNombre;
    }
    return null;
}

if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $type = trim($_POST['type'] ?? '');

    if ($nombre === '' || $precio <= 0 || $categoria === '' || $type === '' || !isset($_FILES['imagen'])) {
        echo json_encode(['status'=>'error','msg'=>'Todos los campos obligatorios']);
        exit;
    }

    $imagen = guardarImagen($_FILES['imagen']);
    if (!$imagen) {
        echo json_encode(['status'=>'error','msg'=>'Imagen inválida o no subida']);
        exit;
    }

    $ok = $pdo->prepare("INSERT INTO productos (nombre, precio, descripcion, categoria, imagen, type) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$nombre, $precio, $descripcion, $categoria, $imagen, $type]);

    echo json_encode([
        'status' => $ok ? 'ok' : 'error',
        'msg' => $ok ? 'Producto agregado.' : 'No se pudo agregar'
    ]);
    exit;
}

if (isset($_POST['editar'])) {
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $type = trim($_POST['type'] ?? '');

    if (!$id || $nombre === '' || $precio <= 0 || $categoria === '' || $type === '') {
        echo json_encode(['status'=>'error','msg'=>'Todos los campos obligatorios']);
        exit;
    }

    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = guardarImagen($_FILES['imagen']);
    }

    if ($imagen) {
        $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id=?");
        $stmt->execute([$id]);
        $imgOld = $stmt->fetchColumn();
        if ($imgOld && file_exists("../assets/img/$imgOld")) {
            unlink("../assets/img/$imgOld");
        }
        $sql = "UPDATE productos SET nombre=?, precio=?, descripcion=?, categoria=?, imagen=?, type=? WHERE id=?";
        $params = [$nombre, $precio, $descripcion, $categoria, $imagen, $type, $id];
    } else {
        $sql = "UPDATE productos SET nombre=?, precio=?, descripcion=?, categoria=?, type=? WHERE id=?";
        $params = [$nombre, $precio, $descripcion, $categoria, $type, $id];
    }
    $ok = $pdo->prepare($sql)->execute($params);

    echo json_encode([
        'status' => $ok ? 'ok' : 'error',
        'msg' => $ok ? 'Producto actualizado.' : 'No se pudo actualizar'
    ]);
    exit;
}

if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $pdo->prepare("SELECT imagen FROM productos WHERE id=?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../assets/img/$img")) {
        unlink("../assets/img/$img");
    }
    $ok = $pdo->prepare("DELETE FROM productos WHERE id=?")->execute([$id]);
    echo json_encode([
        'status' => $ok ? 'ok' : 'error',
        'msg' => $ok ? 'Producto eliminado.' : 'No se pudo eliminar'
    ]);
    exit;
}

echo json_encode(['status'=>'error','msg'=>'Petición inválida']);