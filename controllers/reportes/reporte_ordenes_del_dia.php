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
$condicionFechaMetodos = "";  // Condición especial para la consulta de métodos
$textoPeriodo = "del dia de hoy";

if ($fechaDesde && $fechaHasta) {
    $condicionFecha = "AND DATE(o.creada_en) BETWEEN '$fechaDesde' AND '$fechaHasta'";
    $condicionFechaMetodos = "AND DATE(creada_en) BETWEEN '$fechaDesde' AND '$fechaHasta'";
    $textoPeriodo = "del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
} else {
    $condicionFecha = "AND DATE(o.creada_en) = CURDATE()";
    $condicionFechaMetodos = "AND DATE(creada_en) = CURDATE()";
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

class ReporteOrdenesDelDia extends FPDF {
    private $empresa = "Kalli Jaguar POS";
    private $fecha_reporte;
    private $total_del_dia = 0;
    private $total_ordenes = 0;
    private $periodo_texto;
    private $fecha_desde;
    private $fecha_hasta;
    
    function __construct($textoPeriodo = "del día de hoy", $fechaDesde = null, $fechaHasta = null) {
        parent::__construct();
        $this->periodo_texto = $textoPeriodo;
        $this->fecha_desde = $fechaDesde;
        $this->fecha_hasta = $fechaHasta;
        
        // Configurar fecha del reporte según el filtro
        if ($fechaDesde && $fechaHasta) {
            if ($fechaDesde === $fechaHasta) {
                // Si es el mismo día
                $this->fecha_reporte = date('d/m/Y', strtotime($fechaDesde));
            } else {
                // Si es un rango
                $this->fecha_reporte = date('d/m/Y', strtotime($fechaDesde)) . ' al ' . date('d/m/Y', strtotime($fechaHasta));
            }
        } else {
            // Sin filtro, usar fecha actual
            $this->fecha_reporte = date('d/m/Y');
        }
        
        // Configuración UTF-8
        $this->SetTitle('Reporte de Ordenes del Dia');
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
        $this->Cell(0, 8, limpiarTexto($this->empresa), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 5, limpiarTexto('Sistema de Punto de Venta'), 0, 1, 'C');
        $this->Cell(0, 5, 'Tel: (222) 814-5866 | Email: contacto@kalli.com', 0, 1, 'C');
        
        // Línea decorativa
        $this->SetDrawColor(155, 89, 182);
        $this->SetLineWidth(0.8);
        $this->Line(15, 35, 195, 35);
        
        // Título del reporte
        $this->Ln(8);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, limpiarTexto('Reporte de Ordenes'), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 5, limpiarTexto('Periodo: ' . $this->periodo_texto), 0, 1, 'C');
        
        $this->Ln(5);
    }
    
    // Pie de página
    function Footer() {
        $this->SetY(-25);
        
        // Línea decorativa
        $this->SetDrawColor(155, 89, 182);
        $this->SetLineWidth(0.5);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        
        $this->Ln(3);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(127, 140, 141);
        $this->Cell(0, 4, limpiarTexto('Generado el ' . date('d/m/Y H:i:s') . ' | Kalli Jaguar POS'), 0, 1, 'C');
        $this->Cell(0, 4, limpiarTexto('Pagina ' . $this->PageNo() . ' de {nb}'), 0, 0, 'C');
    }
    
    // Encabezado de tabla
    function TablaHeader() {
        $this->SetFillColor(155, 89, 182);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        
        $this->Cell(40, 10, limpiarTexto('Orden #'), 1, 0, 'C', true);
        $this->Cell(20, 10, limpiarTexto('Mesa'), 1, 0, 'C', true);
        $this->Cell(25, 10, limpiarTexto('Hora'), 1, 0, 'C', true);
        $this->Cell(35, 10, limpiarTexto('Método Pago'), 1, 0, 'C', true);
        $this->Cell(25, 10, limpiarTexto('Estado'), 1, 0, 'C', true);
        $this->Cell(25, 10, limpiarTexto('Monto'), 1, 0, 'C', true);
        $this->Cell(20, 10, limpiarTexto('Items'), 1, 1, 'C', true);
        
        $this->SetTextColor(44, 62, 80);
    }
    
    // Fila de datos
    function TablaFila($orden, $isEven = false) {
        $this->SetFont('Arial', '', 9);
        
        // Color alternado para las filas
        if ($isEven) {
            $this->SetFillColor(248, 249, 250);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        // Color especial para estado
        $estadoColor = $this->GetEstadoColor($orden['estado']);
        
        // Ya no necesitamos convertir caracteres, usamos limpiarTexto()
        $mesa_nombre = limpiarTexto($orden['mesa_nombre']);
        $metodo_pago = limpiarTexto($orden['metodo_pago'] ?? 'N/A');
        $estado = limpiarTexto($orden['estado']);
        
        $this->Cell(40, 8, $orden['codigo'], 1, 0, 'C', true);
        $this->Cell(20, 8, $mesa_nombre, 1, 0, 'C', true);
        $this->Cell(25, 8, date('H:i:s', strtotime($orden['creada_en'])), 1, 0, 'C', true);
        $this->Cell(35, 8, ucfirst($metodo_pago), 1, 0, 'C', true);
        
        // Celda de estado con color especial
        $this->SetFillColor($estadoColor['bg'][0], $estadoColor['bg'][1], $estadoColor['bg'][2]);
        $this->SetTextColor($estadoColor['text'][0], $estadoColor['text'][1], $estadoColor['text'][2]);
        $this->Cell(25, 8, ucfirst($estado), 1, 0, 'C', true);
        
        // Restaurar colores
        if ($isEven) {
            $this->SetFillColor(248, 249, 250);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        $this->SetTextColor(44, 62, 80);
        
        $this->Cell(25, 8, '$' . number_format($orden['total'], 2), 1, 0, 'R', true);
        $this->Cell(20, 8, $orden['total_items'], 1, 1, 'C', true);
    }
    
    // Obtener colores según el estado
    function GetEstadoColor($estado) {
        switch($estado) {
            case 'abierta':
                return ['bg' => [52, 152, 219], 'text' => [255, 255, 255]]; // Azul
            case 'cerrada':
            case 'pagada':
                return ['bg' => [46, 204, 113], 'text' => [255, 255, 255]]; // Verde
            case 'cancelada':
                return ['bg' => [231, 76, 60], 'text' => [255, 255, 255]]; // Rojo
            default:
                return ['bg' => [127, 140, 141], 'text' => [255, 255, 255]]; // Gris
        }
    }
    
    // Resumen por método de pago
    function ResumenMetodosPago($resumen_metodos) {
        $this->Ln(8);
        
        $this->SetFillColor(52, 73, 94);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 10, limpiarTexto('Resumen por métodos de pago'), 1, 1, 'C', true);

        $this->SetFillColor(236, 240, 241);
        $this->SetTextColor(44, 62, 80);
        $this->SetFont('Arial', 'B', 10);
        
        foreach ($resumen_metodos as $metodo) {
            // Ya no necesitamos convertir caracteres, usamos limpiarTexto()
            $metodo_pago_text = limpiarTexto(ucfirst($metodo['metodo_pago']));
            $ordenes_text = limpiarTexto(number_format($metodo['total_ordenes']) . ' órdenes');
            
            $this->Cell(60, 8, $metodo_pago_text . ':', 1, 0, 'L', true);
            $this->Cell(40, 8, $ordenes_text, 1, 0, 'C', true);
            $this->Cell(90, 8, '$' . number_format($metodo['total_monto'], 2), 1, 1, 'R', true);
        }
    }
    
    // Resumen final del día
    function ResumenFinal($total_ordenes, $total_del_dia, $promedio_venta, $periodo_texto) {
        $this->Ln(8);
        
        // Cuadro de resumen principal
        $this->SetFillColor(241, 196, 15);
        $this->SetTextColor(44, 62, 80);
        $this->SetFont('Arial', 'B', 12);
        
        $this->Cell(0, 12, limpiarTexto('Resumen total ' . $periodo_texto), 1, 1, 'C', true);
        
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor(44, 62, 80);
        $this->SetFont('Arial', 'B', 11);
        
        // Fila 1: Total de órdenes
        $this->Cell(95, 10, limpiarTexto('Total de ordenes procesadas:'), 1, 0, 'L', true);
        $this->SetTextColor(52, 152, 219);
        $this->Cell(95, 10, limpiarTexto(number_format($total_ordenes) . ' órdenes'), 1, 1, 'R', true);
        
        // Fila 2: Promedio por venta
        $this->SetTextColor(44, 62, 80);
        $this->Cell(95, 10, limpiarTexto('Promedio por venta:'), 1, 0, 'L', true);
        $this->SetTextColor(155, 89, 182);
        $this->Cell(95, 10, '$' . number_format($promedio_venta, 2), 1, 1, 'R', true);
        
        // Fila 3: Total del período (destacado)
        $this->SetFillColor(46, 204, 113);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(95, 12, limpiarTexto('Total de ventas ' . $periodo_texto . ':'), 1, 0, 'L', true);
        $this->Cell(95, 12, '$' . number_format($total_del_dia, 2), 1, 1, 'R', true);
    }
}

// Obtener datos de órdenes del día
try {
    // Consulta principal de órdenes
    $query = "
        SELECT 
            o.id,
            o.codigo,
            o.total,
            o.estado,
            o.metodo_pago,
            o.creada_en,
            m.nombre as mesa_nombre,
            (SELECT COUNT(*) FROM orden_productos op WHERE op.orden_id = o.id AND op.estado != 'cancelado') as total_items
        FROM ordenes o
        LEFT JOIN mesas m ON o.mesa_id = m.id
        WHERE o.estado='cerrada'
        $condicionFecha
        ORDER BY o.creada_en DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Resumen por métodos de pago
    $query_metodos = "
        SELECT 
            metodo_pago,
            COUNT(*) as total_ordenes,
            SUM(total) as total_monto
        FROM ordenes 
        WHERE estado IN ('cerrada', 'pagada')
        AND metodo_pago IS NOT NULL
        $condicionFechaMetodos
        GROUP BY metodo_pago
        ORDER BY total_monto DESC
    ";
    
    $stmt_metodos = $pdo->prepare($query_metodos);
    $stmt_metodos->execute();
    $resumen_metodos = $stmt_metodos->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular totales
    $total_ordenes = count($ordenes);
    $total_del_dia = 0;
    $ordenes_cerradas = 0;
    
    foreach ($ordenes as $orden) {
        if (in_array($orden['estado'], ['cerrada', 'pagada'])) {
            $total_del_dia += $orden['total'];
            $ordenes_cerradas++;
        }
    }
    
    $promedio_venta = $ordenes_cerradas > 0 ? $total_del_dia / $ordenes_cerradas : 0;
    
    // Crear el PDF
    $pdf = new ReporteOrdenesDelDia($textoPeriodo, $fechaDesde, $fechaHasta);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Verificar si hay datos
    if (empty($ordenes)) {
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(231, 76, 60);
        $texto_sin_datos = $fechaDesde && $fechaHasta ? 
            'No se encontraron órdenes para el período seleccionado.' : 
            'No se encontraron órdenes para el día de hoy.';
        $pdf->Cell(0, 20, limpiarTexto($texto_sin_datos), 0, 1, 'C');
    } else {
        // Encabezado de tabla
        $pdf->TablaHeader();
        
        // Datos de la tabla
        foreach ($ordenes as $index => $orden) {
            $isEven = ($index % 2 == 0);
            
            // Verificar si necesitamos una nueva página ANTES de agregar la fila
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
                $pdf->TablaHeader();
            }
            
            $pdf->TablaFila($orden, $isEven);
        }
        
        // Verificar espacio para los resúmenes
        if ($pdf->GetY() > 200) {
            $pdf->AddPage();
        }
        
        // Resumen por métodos de pago
        if (!empty($resumen_metodos)) {
            $pdf->ResumenMetodosPago($resumen_metodos);
        }
        
        // Resumen final
        $pdf->ResumenFinal($total_ordenes, $total_del_dia, $promedio_venta, $textoPeriodo);
    }
    
    // Configurar headers para visualización en navegador
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="Reporte_Ordenes_Del_Dia_' . date('Y-m-d') . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    $pdf->Output('I', 'Reporte_Ordenes_Del_Dia_' . date('Y-m-d') . '.pdf');
    
} catch (Exception $e) {
    die('Error al generar el reporte: ' . limpiarTexto($e->getMessage()));
}
?>
