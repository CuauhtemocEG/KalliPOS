<?php
// Configurar límites de memoria y tiempo
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 30);

// Limpiar completamente todos los buffers
while (ob_get_level()) {
    ob_end_clean();
}

require_once '../conexion.php';
require_once '../fpdf/fpdf.php';

try {
    $pdo = conexion();
    
    $orden_id = intval($_GET['id'] ?? 0);
    if ($orden_id <= 0) {
        throw new Exception('ID de orden inválido');
    }
    
    $stmt = $pdo->prepare("SELECT o.*, m.nombre AS mesa_nombre FROM ordenes o JOIN mesas m ON m.id=o.mesa_id WHERE o.id=?");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orden) { 
        throw new Exception('Orden no encontrada');
    }
    
    $productos = $pdo->prepare("SELECT p.nombre, op.cantidad, op.preparado, op.cancelado, p.precio FROM orden_productos op JOIN productos p ON op.producto_id = p.id WHERE op.orden_id = ?");
    $productos->execute([$orden_id]);
    $productos = $productos->fetchAll(PDO::FETCH_ASSOC);
    
    $subtotal = 0;
    foreach ($productos as $prod) $subtotal += $prod['precio'] * $prod['cantidad'];
    $descuento = 0; $impuestos = 0; $total = $subtotal - $descuento + $impuestos;
    
    // Crear PDF optimizado con estilo profesional pero eficiente
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // Título centrado con estilo profesional
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(33, 37, 41);
    $pdf->Cell(0, 15, 'DETALLE DE ORDEN', 0, 1, 'C');
    $pdf->Ln(3);
    
    // Línea separadora elegante
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, 35, 195, 35);
    $pdf->Ln(5);
    
    // Información básica con formato profesional
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(52, 58, 64);
    $pdf->Cell(40, 8, 'Codigo:', 0, 0, 'L');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, $orden['codigo'], 0, 1, 'L');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 8, 'Mesa:', 0, 0, 'L');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(60, 8, $orden['mesa_nombre'], 0, 0, 'L');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(25, 8, 'Estado:', 0, 0, 'L');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, ucfirst($orden['estado']), 0, 1, 'L');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(40, 8, 'Fecha:', 0, 0, 'L');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, date('d/m/Y H:i', strtotime($orden['creada_en'])), 0, 1, 'L');
    $pdf->Ln(8);
    
    // Productos - tabla profesional pero simple
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(33, 37, 41);
    $pdf->Cell(0, 10, 'PRODUCTOS SOLICITADOS', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Header de tabla con fondo gris
    $pdf->SetFillColor(248, 249, 250);
    $pdf->SetTextColor(52, 58, 64);
    $pdf->SetFont('Arial', 'B', 10);
    
    $pdf->Cell(65, 10, 'Producto', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'Cant.', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Preparado', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Cancelado', 1, 0, 'C', true);
    $pdf->Cell(25, 10, 'Precio', 1, 0, 'C', true);
    $pdf->Cell(20, 10, 'Subtotal', 1, 1, 'C', true);
    
    // Contenido de la tabla
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(73, 80, 87);
    
    foreach($productos as $prod){
        $preparado_texto = ($prod['preparado'] == 1) ? 'Si' : 'No';
        $cancelado_texto = ($prod['cancelado'] == 1) ? 'Si' : 'No';
        
        $pdf->Cell(65, 8, substr($prod['nombre'], 0, 25), 1, 0, 'L');
        $pdf->Cell(20, 8, $prod['cantidad'], 1, 0, 'C');
        
        // Color verde para preparado
        if ($prod['preparado'] == 1) {
            $pdf->SetTextColor(40, 167, 69);
        }
        $pdf->Cell(25, 8, $preparado_texto, 1, 0, 'C');
        
        // Color rojo para cancelado
        $pdf->SetTextColor(73, 80, 87);
        if ($prod['cancelado'] == 1) {
            $pdf->SetTextColor(220, 53, 69);
        }
        $pdf->Cell(25, 8, $cancelado_texto, 1, 0, 'C');
        
        // Volver al color normal
        $pdf->SetTextColor(73, 80, 87);
        $pdf->Cell(25, 8, '$'.number_format($prod['precio'], 2), 1, 0, 'R');
        $pdf->Cell(20, 8, '$'.number_format($prod['precio']*$prod['cantidad'], 2), 1, 1, 'R');
    }
    
    $pdf->Ln(5);
    
    // Totales con estilo profesional
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(52, 58, 64);
    $pdf->SetFillColor(248, 249, 250);
    
    $pdf->Cell(160, 8, 'Subtotal', 1, 0, 'R', true);
    $pdf->Cell(20, 8, '$'.number_format($subtotal, 2), 1, 1, 'R', true);
    
    $pdf->Cell(160, 8, 'Descuento', 1, 0, 'R', true);
    $pdf->Cell(20, 8, '$'.number_format($descuento, 2), 1, 1, 'R', true);
    
    $pdf->Cell(160, 8, 'Impuestos', 1, 0, 'R', true);
    $pdf->Cell(20, 8, '$'.number_format($impuestos, 2), 1, 1, 'R', true);
    
    // Total destacado
    $pdf->SetFillColor(33, 37, 41);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(160, 10, 'TOTAL', 1, 0, 'R', true);
    $pdf->Cell(20, 10, '$'.number_format($total, 2), 1, 1, 'R', true);
    
    // Footer
    $pdf->Ln(10);
    $pdf->SetTextColor(108, 117, 125);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 5, 'Documento generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Sistema de Punto de Venta', 0, 1, 'C');
    
    // Output del PDF
    $pdf->Output('I', $orden['codigo'].'.pdf');
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
exit();
?>
