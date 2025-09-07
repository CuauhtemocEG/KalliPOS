<?php
// Definir la ruta base
define('BASE_PATH', dirname(dirname(__DIR__)) . '/');

// Solo incluir la conexión, que ya maneja la configuración
require_once BASE_PATH . 'conexion.php';
require_once BASE_PATH . 'fpdf/fpdf.php';

// Crear la conexión a la base de datos
$pdo = conexion();

// Obtener filtros de fecha desde parámetros GET
$fechaDesde = $_GET['fecha_desde'] ?? null;
$fechaHasta = $_GET['fecha_hasta'] ?? null;

// Construir condición de fecha
$condicionFecha = "";
$textoPeriodo = "del día de hoy";

if ($fechaDesde && $fechaHasta) {
    $condicionFecha = "AND DATE(o.creada_en) BETWEEN '$fechaDesde' AND '$fechaHasta'";
    $textoPeriodo = "del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
} else {
    $condicionFecha = "AND DATE(o.creada_en) = CURDATE()";
}

// Función para limpiar texto y convertir a ASCII seguro
function limpiarTexto($texto) {
    // Primero reemplazar caracteres acentuados ANTES de filtrar
    $texto = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'ü', 'Ü', 'ç', 'Ç'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N', 'u', 'U', 'c', 'C'],
        $texto
    );
    
    // Mantener caracteres básicos, números, espacios y puntuación común
    // Agregamos {} para que {nb} no se elimine
    $texto = preg_replace('/[^a-zA-Z0-9\s\.,\-:$(){}\/#|@]/', '', $texto);
    
    return $texto;
}

class ReporteProductosVendidos extends FPDF {
    private $empresa = "Kalli Jaguar POS";
    private $fecha_reporte;
    private $total_ventas = 0;
    private $total_productos = 0;
    private $periodo_texto;
    
    function __construct($textoPeriodo = "del día de hoy") {
        parent::__construct();
        $this->fecha_reporte = date('d/m/Y');
        $this->periodo_texto = $textoPeriodo;
        // Configuración UTF-8
        $this->SetTitle('Reporte de Productos Vendidos');
        $this->SetAuthor('Kalli Jaguar POS');
    }
    
    // Encabezado
    function Header() {
        // Logo
        $logoPath = BASE_PATH . 'assets/img/logoorange.jpg';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 15, 10, 40);
        }
        
        // Información de la empresa
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, $this->empresa, 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 5, 'Sistema de Punto de Venta', 0, 1, 'C');
        $this->Cell(0, 5, 'Tel: (222) 814-5866 | Email: contacto@kalli.com', 0, 1, 'C');
        
        // Línea decorativa
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.8);
        $this->Line(15, 35, 195, 35);
        
        // Título del reporte
        $this->Ln(8);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, 'Reporte de Productos Vendidos', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 5, 'Periodo: ' . $this->periodo_texto, 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    // Pie de página
    function Footer() {
        $this->SetY(-25);
        
        // Línea decorativa
        $this->SetDrawColor(52, 152, 219);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        
        $this->Ln(3);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 4, 'Generado el ' . date('d/m/Y H:i:s') . ' | Kalli Jaguar POS', 0, 1, 'C');
        $this->Cell(0, 4, 'Pagina ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }
    
    // Encabezado de tabla
    function TablaHeader() {
        $this->SetFillColor(52, 152, 219);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        
        $this->Cell(15, 10, '#', 1, 0, 'C', true);
        $this->Cell(70, 10, 'Producto', 1, 0, 'L', true);
        $this->Cell(25, 10, 'Cantidad', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Precio Unit.', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Subtotal', 1, 0, 'C', true);
        $this->Cell(30, 10, 'Tipo', 1, 1, 'C', true);
        
        $this->SetTextColor(44, 62, 80);
    }
    
    // Fila de datos
    function TablaFila($num, $producto, $cantidad, $precio, $subtotal, $tipo, $isEven = false) {
        $this->SetFont('Arial', '', 9);
        
        // Color alternado para las filas
        if ($isEven) {
            $this->SetFillColor(248, 249, 250);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        // Limpiar texto para compatibilidad con PDF
        $producto = limpiarTexto($producto);
        $tipo = limpiarTexto($tipo);
        
        $this->Cell(15, 8, $num, 1, 0, 'C', true);
        $this->Cell(70, 8, substr($producto, 0, 32), 1, 0, 'L', true);
        $this->Cell(25, 8, number_format($cantidad), 1, 0, 'C', true);
        $this->Cell(25, 8, '$' . number_format($precio, 2), 1, 0, 'R', true);
        $this->Cell(25, 8, '$' . number_format($subtotal, 2), 1, 0, 'R', true);
        $this->Cell(30, 8, substr($tipo, 0, 15), 1, 1, 'C', true);
    }
    
    // Resumen final
    function ResumenFinal($total_productos, $total_ventas, $periodo_texto) {
        $this->Ln(5);
        
        // Cuadro de resumen
        $this->SetFillColor(46, 204, 113);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        
        $this->Cell(0, 10, 'Resumen ' . $periodo_texto, 1, 1, 'C', true);
        
        $this->SetFillColor(236, 240, 241);
        $this->SetTextColor(44, 62, 80);
        $this->SetFont('Arial', 'B', 10);
        
        $this->Cell(95, 8, 'Total de productos vendidos:', 1, 0, 'L', true);
        $this->Cell(95, 8, number_format($total_productos) . ' unidades', 1, 1, 'R', true);
        
        $this->Cell(95, 8, 'Total de ventas ' . $periodo_texto . ':', 1, 0, 'L', true);
        $this->SetTextColor(46, 204, 113);
        $this->Cell(95, 8, '$' . number_format($total_ventas, 2), 1, 1, 'R', true);
    }
}

// Obtener datos de productos vendidos hoy
try {
    $query = "
        SELECT 
            p.nombre,
            p.precio,
            t.nombre as tipo,
            SUM(op.cantidad) as total_cantidad,
            SUM(op.cantidad * p.precio) as total_subtotal
        FROM orden_productos op
        INNER JOIN productos p ON op.producto_id = p.id
        INNER JOIN type t ON p.type = t.id
        INNER JOIN ordenes o ON op.orden_id = o.id
        WHERE o.estado = 'cerrada'
        AND op.preparado > 0
        AND op.cancelado = 0
        $condicionFecha
        GROUP BY p.id, p.nombre, p.precio, t.nombre
        ORDER BY total_cantidad DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear el PDF
    $pdf = new ReporteProductosVendidos($textoPeriodo);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Verificar si hay datos
    if (empty($productos)) {
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(231, 76, 60);
        $texto_sin_datos = $fechaDesde && $fechaHasta ? 
            'No se encontraron productos vendidos para el período seleccionado.' : 
            'No se encontraron productos vendidos para el día de hoy.';
        $pdf->Cell(0, 20, $texto_sin_datos, 0, 1, 'C');
    } else {
        // Encabezado de tabla
        $pdf->TablaHeader();
        
        $total_productos = 0;
        $total_ventas = 0;
        $contador = 1;
        
        // Datos de la tabla
        foreach ($productos as $index => $producto) {
            $isEven = ($index % 2 == 0);
            
            $pdf->TablaFila(
                $contador,
                $producto['nombre'],
                $producto['total_cantidad'],
                $producto['precio'],
                $producto['total_subtotal'],
                $producto['tipo'],
                $isEven
            );
            
            $total_productos += $producto['total_cantidad'];
            $total_ventas += $producto['total_subtotal'];
            $contador++;
            
            // Verificar si necesitamos una nueva página
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                $pdf->TablaHeader();
            }
        }
        
        // Resumen final
        $pdf->ResumenFinal($total_productos, $total_ventas, $textoPeriodo);
    }
    
    // Configurar headers para visualización en navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Reporte_Productos_Vendidos_' . date('Y-m-d') . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    $pdf->Output('I', 'Reporte_Productos_Vendidos_' . date('Y-m-d') . '.pdf');
    
} catch (Exception $e) {
    die('Error al generar el reporte: ' . $e->getMessage());
}
?>
