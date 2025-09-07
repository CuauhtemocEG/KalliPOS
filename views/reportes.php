<?php
// Este archivo es incluido desde index.php, por lo que las rutas son relativas al directorio raíz
// $pdo y $userInfo ya están disponibles desde index.php

// Manejar filtros de fecha
$fechaDesde = $_GET['fecha_desde'] ?? null;
$fechaHasta = $_GET['fecha_hasta'] ?? null;

// Construir condiciones de fecha
$condicionFecha = "";
$condicionFechaHoy = "DATE(creada_en) = CURDATE()";
$condicionFechaSemana = "creada_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$condicionFechaMes = "creada_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

// Texto descriptivo del período
$textoPeriodo = "Hoy";
if ($fechaDesde && $fechaHasta) {
  $condicionFecha = "DATE(creada_en) BETWEEN '$fechaDesde' AND '$fechaHasta'";
  // Si hay filtro personalizado, usar solo ese filtro para todas las consultas
  $condicionFechaHoy = $condicionFecha;
  $condicionFechaSemana = $condicionFecha;
  $condicionFechaMes = $condicionFecha;
  $textoPeriodo = "del " . date('d/m/Y', strtotime($fechaDesde)) . " al " . date('d/m/Y', strtotime($fechaHasta));
}

// Estadísticas básicas con filtros aplicados
$totalOrdenes = $pdo->query("SELECT COUNT(*) FROM ordenes WHERE estado = 'cerrada'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn();
$ventasHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada'")->fetchColumn() ?? 0;
$ventasSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada'")->fetchColumn() ?? 0;
$ventasMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada'")->fetchColumn() ?? 0;
$ordenesActivas = $pdo->query("SELECT COUNT(*) FROM ordenes WHERE estado = 'abierta'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn();

// Totales por método de pago - Respetan filtro de fecha personalizado
$ventasEfectivo = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'efectivo'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0;
$ventasDebito = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'debito'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0;
$ventasCredito = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'credito'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0;
$ventasTransferencia = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'transferencia'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0;
$ventasTarjeta = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE estado = 'cerrada' AND metodo_pago = 'tarjeta'" . ($condicionFecha ? " AND $condicionFecha" : ""))->fetchColumn() ?? 0; // Para compatibilidad

// Totales por método de pago - HOY
$ventasEfectivoHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'efectivo'")->fetchColumn() ?? 0;
$ventasDebitoHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'debito'")->fetchColumn() ?? 0;
$ventasCreditoHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'credito'")->fetchColumn() ?? 0;
$ventasTransferenciaHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'transferencia'")->fetchColumn() ?? 0;
$ventasTarjetaHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaHoy AND estado = 'cerrada' AND metodo_pago = 'tarjeta'")->fetchColumn() ?? 0;

// Totales por método de pago - SEMANA
$ventasEfectivoSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'efectivo'")->fetchColumn() ?? 0;
$ventasDebitoSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'debito'")->fetchColumn() ?? 0;
$ventasCreditoSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'credito'")->fetchColumn() ?? 0;
$ventasTransferenciaSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'transferencia'")->fetchColumn() ?? 0;
$ventasTarjetaSemana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaSemana AND estado = 'cerrada' AND metodo_pago = 'tarjeta'")->fetchColumn() ?? 0;

// Totales por método de pago - MES
$ventasEfectivoMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'efectivo'")->fetchColumn() ?? 0;
$ventasDebitoMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'debito'")->fetchColumn() ?? 0;
$ventasCreditoMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'credito'")->fetchColumn() ?? 0;
$ventasTransferenciaMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'transferencia'")->fetchColumn() ?? 0;
$ventasTarjetaMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ordenes WHERE $condicionFechaMes AND estado = 'cerrada' AND metodo_pago = 'tarjeta'")->fetchColumn() ?? 0;

// Estadísticas detalladas por método de pago
$estadisticasMetodoPago = $pdo->query("
    SELECT 
        metodo_pago,
        COUNT(*) as total_ordenes,
        COALESCE(SUM(total), 0) as total_ventas,
        COALESCE(AVG(total), 0) as promedio_venta,
        MIN(total) as venta_minima,
        MAX(total) as venta_maxima
    FROM ordenes 
    WHERE estado = 'cerrada' " . ($condicionFecha ? " AND $condicionFecha" : "") . "
    GROUP BY metodo_pago
    ORDER BY total_ventas DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Ventas por método de pago por día (últimos 7 días)
$ventasDiariasMetodoPago = $pdo->query("
    SELECT 
        DATE(creada_en) as fecha,
        metodo_pago,
        COUNT(*) as ordenes,
        COALESCE(SUM(total), 0) as total
    FROM ordenes 
    WHERE estado = 'cerrada' 
    AND creada_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(creada_en), metodo_pago
    ORDER BY fecha DESC, metodo_pago
")->fetchAll(PDO::FETCH_ASSOC);

// Productos más vendidos - Solo productos preparados y no cancelados
try {
  // Intentar con campo cancelado y preparado
  $productosVendidos = $pdo->query("
        SELECT p.nombre, SUM(op.cantidad) as total_vendido, SUM(op.cantidad * p.precio) as total_ingresos
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        JOIN ordenes o ON op.orden_id = o.id 
        WHERE o.estado = 'cerrada' AND op.preparado = 1 AND (op.cancelado = 0 OR op.cancelado IS NULL)" .
    ($condicionFecha ? " AND $condicionFecha" : "") . "
        GROUP BY p.id, p.nombre 
        ORDER BY total_vendido DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // Si falla con preparado, intentar solo con cancelado
  try {
    $productosVendidos = $pdo->query("
            SELECT p.nombre, SUM(op.cantidad) as total_vendido, SUM(op.cantidad * p.precio) as total_ingresos
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            JOIN ordenes o ON op.orden_id = o.id 
            WHERE o.estado = 'cerrada' AND (op.cancelado = 0 OR op.cancelado IS NULL)" .
      ($condicionFecha ? " AND $condicionFecha" : "") . "
            GROUP BY p.id, p.nombre 
            ORDER BY total_vendido DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    // Si falla completamente, usar sin campos adicionales
    $productosVendidos = $pdo->query("
            SELECT p.nombre, SUM(op.cantidad) as total_vendido, SUM(op.cantidad * p.precio) as total_ingresos
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            JOIN ordenes o ON op.orden_id = o.id 
            WHERE o.estado = 'cerrada'" .
      ($condicionFecha ? " AND $condicionFecha" : "") . "
            GROUP BY p.id, p.nombre 
            ORDER BY total_vendido DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
  }
}

// Ventas por mesa (en lugar de por usuario ya que no existe usuario_id)
$ventasPorMesa = $pdo->query("
    SELECT 
        m.nombre as mesa,
        COUNT(o.id) as ordenes_cerradas,
        COALESCE(SUM(o.total), 0) as total_ventas
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    WHERE o.estado = 'cerrada'" .
  ($condicionFecha ? " AND $condicionFecha" : " AND o.creada_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)") . "
    GROUP BY o.mesa_id, m.nombre
    ORDER BY total_ventas DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Órdenes activas (abiertas) - datos detallados
$ordenesActivasDetalle = $pdo->query("
    SELECT 
        o.id,
        o.codigo,
        o.mesa_id,
        m.nombre as mesa,
        o.total,
        o.metodo_pago,
        o.creada_en,
        COUNT(op.id) as productos_count,
        TIMESTAMPDIFF(MINUTE, o.creada_en, NOW()) as minutos_abierta
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    LEFT JOIN orden_productos op ON o.id = op.orden_id
    WHERE o.estado = 'abierta'
    GROUP BY o.id, o.codigo, o.mesa_id, m.nombre, o.total, o.metodo_pago, o.creada_en
    ORDER BY o.creada_en ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Órdenes cerradas recientes - con paginación
$paginaOrdenes = isset($_GET['pagina_ordenes']) ? (int)$_GET['pagina_ordenes'] : 1;
$ordenesPorPagina = 10;
$offsetOrdenes = ($paginaOrdenes - 1) * $ordenesPorPagina;

// Contar total de órdenes cerradas para paginación
$totalOrdenesQuery = "
    SELECT COUNT(DISTINCT o.id) as total
    FROM ordenes o
    WHERE o.estado = 'cerrada'" .
  ($condicionFecha ? " AND $condicionFecha" : " AND o.creada_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");

$totalOrdenesCerradas = $pdo->query($totalOrdenesQuery)->fetchColumn();
$totalPaginasOrdenes = ceil($totalOrdenesCerradas / $ordenesPorPagina);

// Órdenes cerradas recientes (con paginación) - datos detallados
$ordenesCerradasDetalle = $pdo->query("
    SELECT 
        o.id,
        o.codigo,
        o.mesa_id,
        m.nombre as mesa,
        o.total,
        o.metodo_pago,
        o.creada_en,
        o.cerrada_en,
        CASE 
            WHEN o.cerrada_en IS NOT NULL THEN
                TIMESTAMPDIFF(MINUTE, o.creada_en, o.cerrada_en)
            ELSE 
                NULL
        END as tiempo_total_minutos,
        COUNT(op.id) as productos_count
    FROM ordenes o
    JOIN mesas m ON o.mesa_id = m.id
    LEFT JOIN orden_productos op ON o.id = op.orden_id
    WHERE o.estado = 'cerrada'" .
  ($condicionFecha ? " AND $condicionFecha" : " AND o.creada_en >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") . "
    GROUP BY o.id, o.codigo, o.mesa_id, m.nombre, o.total, o.metodo_pago, o.creada_en, o.cerrada_en
    ORDER BY o.cerrada_en DESC
    LIMIT $ordenesPorPagina OFFSET $offsetOrdenes
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Hero Section -->
<div class="text-center mb-8">
  <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl mb-4 shadow-lg">
    <i class="bi bi-graph-up text-white text-2xl"></i>
  </div>
  <h1 class="text-3xl md:text-4xl font-montserrat-bold font-display gradient-text mb-3">Reportes y Estadísticas</h1>
  <p class="text-lg text-gray-400 max-w-xl mx-auto">Análisis completo del rendimiento del restaurante</p>
</div>
<!-- Action Bar -->
<div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
  <!-- Selector de Rango de Fechas -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
    <div class="flex items-center space-x-4">
      <div class="text-sm text-gray-400">
        <i class="bi bi-calendar mr-1"></i>
        Filtrar por fecha:
      </div>
    </div>

    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
      <div class="flex items-center space-x-2">
        <label class="text-sm text-gray-300">Desde:</label>
        <input type="date" id="fecha-desde"
          class="bg-dark-700 border border-dark-600 text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          value="<?= date('Y-m-d') ?>">
      </div>

      <div class="flex items-center space-x-2">
        <label class="text-sm text-gray-300">Hasta:</label>
        <input type="date" id="fecha-hasta"
          class="bg-dark-700 border border-dark-600 text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          value="<?= date('Y-m-d') ?>">
      </div>

      <button onclick="filtrarPorFecha()"
        class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
        <i class="bi bi-funnel"></i>
        <span>Filtrar</span>
      </button>

      <button onclick="limpiarFiltros()"
        class="flex items-center space-x-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-all duration-300">
        <i class="bi bi-arrow-clockwise"></i>
        <span>Limpiar</span>
      </button>
    </div>
  </div>

  <?php
  $userInfo = getUserInfo();
  $esAdministrador = $userInfo['rol'] === 'administrador';
  if ($esAdministrador || hasPermission('reportes', 'ver')):
  ?>
    <div class="flex items-center gap-3">
      <button onclick="toggleReportesPDF()" class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
        <i class="bi bi-file-earmark-pdf"></i>
        <span>Reportes PDF</span>
        <i id="pdf-toggle-icon" class="bi bi-chevron-down transition-transform duration-300"></i>
      </button>
    </div>
  <?php endif; ?>
</div>

<!-- Tarjetas de estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
  <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-2xl shadow-xl border border-blue-400/20">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-blue-100 text-sm font-medium">Órdenes Totales</p>
        <p class="text-3xl font-montserrat-bold mt-2"><?= number_format($totalOrdenes) ?></p>
      </div>
      <i class="bi bi-receipt text-4xl text-blue-200/60"></i>
    </div>
  </div>

  <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white p-6 rounded-2xl shadow-xl border border-orange-400/20">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-orange-100 text-sm font-medium">Órdenes Activas</p>
        <p class="text-3xl font-montserrat-bold mt-2"><?= number_format($ordenesActivas) ?></p>
      </div>
      <i class="bi bi-clock text-4xl text-orange-200/60"></i>
    </div>
  </div>

  <div class="bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-2xl shadow-xl border border-green-400/20">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-green-100 text-sm font-medium">Ventas Hoy</p>
        <p class="text-3xl font-montserrat-bold mt-2">$<?= number_format($ventasHoy, 2) ?></p>
      </div>
      <i class="bi bi-currency-dollar text-4xl text-green-200/60"></i>
    </div>
  </div>

  <div class="bg-gradient-to-br from-yellow-500 to-orange-500 text-white p-6 rounded-2xl shadow-xl border border-yellow-400/20">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-yellow-100 text-sm font-medium">Ventas Semana</p>
        <p class="text-3xl font-montserrat-bold mt-2">$<?= number_format($ventasSemana, 2) ?></p>
      </div>
      <i class="bi bi-calendar-week text-4xl text-yellow-200/60"></i>
    </div>
  </div>

  <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-2xl shadow-xl border border-purple-400/20">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-purple-100 text-sm font-medium">Ventas Mes</p>
        <p class="text-3xl font-montserrat-bold mt-2">$<?= number_format($ventasMes, 2) ?></p>
      </div>
      <i class="bi bi-calendar-month text-4xl text-purple-200/60"></i>
    </div>
  </div>
</div>

<!-- Sección de Reportes PDF -->
<?php
$userInfo = getUserInfo();
$esAdministrador = $userInfo['rol'] === 'administrador';
if ($esAdministrador || hasPermission('reportes', 'ver')):
?>
  <div id="reportes-pdf-section" class="mb-8 overflow-hidden transition-all duration-500 ease-in-out" style="max-height: 0; opacity: 0;">
    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-8 border border-slate-700/50 shadow-xl">
      <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl mb-4 shadow-lg">
          <i class="bi bi-file-earmark-pdf text-white text-2xl"></i>
        </div>
        <h2 class="text-2xl font-montserrat-bold text-white mb-2">Reportes Ejecutivos PDF</h2>
        <p class="text-gray-400">Genere reportes profesionales con el logo de Kalli para presentaciones empresariales</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Reporte de Productos Vendidos -->
        <div class="bg-gradient-to-br from-blue-600/20 to-purple-600/20 rounded-xl p-6 border border-blue-500/30">
          <div class="flex items-start space-x-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
              <i class="bi bi-box-seam text-white text-xl"></i>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-white mb-2">Productos Vendidos del Día</h3>
              <p class="text-gray-400 text-sm mb-4">Reporte detallado de todos los productos vendidos, cantidades, subtotales por categoría y resumen total del día.</p>
              <button onclick="generarReporteProductos()" class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="bi bi-download mr-2"></i>
                Generar PDF
              </button>
            </div>
          </div>
        </div>

        <!-- Reporte de Órdenes del Día -->
        <div class="bg-gradient-to-br from-green-600/20 to-emerald-600/20 rounded-xl p-6 border border-green-500/30">
          <div class="flex items-start space-x-4">
            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0">
              <i class="bi bi-receipt text-white text-xl"></i>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-white mb-2">Órdenes del Día</h3>
              <p class="text-gray-400 text-sm mb-4">Desglose completo de todas las órdenes, métodos de pago, estados y total de ventas del día.</p>
              <button onclick="generarReporteOrdenes()" class="w-full px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="bi bi-download mr-2"></i>
                Generar PDF
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 p-4 bg-gradient-to-r from-amber-500/10 to-orange-500/10 rounded-lg border border-amber-500/20">
        <div class="flex items-center space-x-3">
          <i class="bi bi-info-circle text-amber-400"></i>
          <div class="text-sm text-amber-200">
            <strong>Nota:</strong> Los reportes incluyen el logo de Kalli y están diseñados para presentaciones profesionales. Todos los datos corresponden al día actual.
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Sección de Métodos de Pago -->
<div class="mb-8">
  <h2 class="text-2xl font-montserrat-bold text-white mb-6 flex items-center">
    <i class="bi bi-credit-card-2-front text-green-400 mr-3"></i>
    Análisis por Método de Pago <?= $textoPeriodo ?>
  </h2>

  <!-- Tarjetas de totales por método de pago -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Efectivo Total -->
    <div class="bg-gradient-to-br from-green-600 to-green-700 text-white p-6 rounded-2xl shadow-xl border border-green-500/20">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-green-100 text-sm font-medium">Efectivo</p>
          <p class="text-2xl font-montserrat-bold mt-2">$<?= number_format($ventasEfectivo, 2) ?></p>
          <p class="text-green-200 text-xs mt-1">
            <?php $totalTodos = $ventasEfectivo + $ventasDebito + $ventasCredito + $ventasTransferencia + $ventasTarjeta; ?>
            <?= $totalTodos > 0 ? number_format(($ventasEfectivo / $totalTodos) * 100, 1) : 0 ?>% del total
          </p>
        </div>
        <i class="bi bi-cash-coin text-4xl text-green-200/60"></i>
      </div>
    </div>

    <!-- Débito -->
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 text-white p-6 rounded-2xl shadow-xl border border-blue-500/20">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-blue-100 text-sm font-medium">Débito</p>
          <p class="text-2xl font-montserrat-bold mt-2">$<?= number_format($ventasDebito, 2) ?></p>
          <p class="text-blue-200 text-xs mt-1">
            <?= $totalTodos > 0 ? number_format(($ventasDebito / $totalTodos) * 100, 1) : 0 ?>% del total
          </p>
        </div>
        <i class="bi bi-credit-card text-4xl text-blue-200/60"></i>
      </div>
    </div>

    <!-- Crédito -->
    <div class="bg-gradient-to-br from-purple-600 to-purple-700 text-white p-6 rounded-2xl shadow-xl border border-purple-500/20">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-purple-100 text-sm font-medium">Crédito</p>
          <p class="text-2xl font-montserrat-bold mt-2">$<?= number_format($ventasCredito, 2) ?></p>
          <p class="text-purple-200 text-xs mt-1">
            <?= $totalTodos > 0 ? number_format(($ventasCredito / $totalTodos) * 100, 1) : 0 ?>% del total
          </p>
        </div>
        <i class="bi bi-credit-card-fill text-4xl text-purple-200/60"></i>
      </div>
    </div>

    <!-- Transferencia -->
    <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 text-white p-6 rounded-2xl shadow-xl border border-indigo-500/20">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-indigo-100 text-sm font-medium">Transferencia</p>
          <p class="text-2xl font-montserrat-bold mt-2">$<?= number_format($ventasTransferencia, 2) ?></p>
          <p class="text-indigo-200 text-xs mt-1">
            <?= $totalTodos > 0 ? number_format(($ventasTransferencia / $totalTodos) * 100, 1) : 0 ?>% del total
          </p>
        </div>
        <i class="bi bi-bank text-4xl text-indigo-200/60"></i>
      </div>
    </div>

    <!-- Tarjeta (Legacy) - Solo mostrar si hay datos -->
    <?php if ($ventasTarjeta > 0): ?>
      <div class="bg-gradient-to-br from-slate-600 to-slate-700 text-white p-6 rounded-2xl shadow-xl border border-slate-500/20">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-slate-100 text-sm font-medium">Tarjeta (Legacy)</p>
            <p class="text-2xl font-montserrat-bold mt-2">$<?= number_format($ventasTarjeta, 2) ?></p>
            <p class="text-slate-200 text-xs mt-1">
              <?= $totalTodos > 0 ? number_format(($ventasTarjeta / $totalTodos) * 100, 1) : 0 ?> % del total
            </p>
          </div>
          <i class="bi bi-credit-card-2-front text-4xl text-slate-200/60"></i>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tabla detallada de métodos de pago -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Estadísticas por método de pago -->
    <div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden">
      <div class="p-6 border-b border-dark-700/50">
        <h3 class="text-xl font-montserrat-semibold text-white flex items-center">
          <i class="bi bi-bar-chart text-green-400 mr-2"></i>
          Estadísticas por Método
        </h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-dark-700/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Método</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Órdenes</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Total</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Promedio</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-dark-700/50">
            <?php if (empty($estadisticasMetodoPago)): ?>
              <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                  <i class="bi bi-inbox text-3xl mb-2"></i>
                  <p>No hay datos de ventas disponibles</p>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($estadisticasMetodoPago as $metodo): ?>
                <tr class="hover:bg-dark-700/30 transition-colors">
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <?php if ($metodo['metodo_pago'] == 'efectivo'): ?>
                        <i class="bi bi-cash-coin text-green-400 mr-2"></i>
                        <span class="text-white font-medium">Efectivo</span>
                      <?php elseif ($metodo['metodo_pago'] == 'debito'): ?>
                        <i class="bi bi-credit-card text-blue-400 mr-2"></i>
                        <span class="text-white font-medium">Débito</span>
                      <?php elseif ($metodo['metodo_pago'] == 'credito'): ?>
                        <i class="bi bi-credit-card-fill text-purple-400 mr-2"></i>
                        <span class="text-white font-medium">Crédito</span>
                      <?php elseif ($metodo['metodo_pago'] == 'transferencia'): ?>
                        <i class="bi bi-bank text-indigo-400 mr-2"></i>
                        <span class="text-white font-medium">Transferencia</span>
                      <?php elseif ($metodo['metodo_pago'] == 'tarjeta'): ?>
                        <i class="bi bi-credit-card-2-front text-slate-400 mr-2"></i>
                        <span class="text-white font-medium">Tarjeta (Legacy)</span>
                      <?php else: ?>
                        <i class="bi bi-question-circle text-gray-400 mr-2"></i>
                        <span class="text-white font-medium"><?= ucfirst($metodo['metodo_pago']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span class="text-white font-medium"><?= number_format($metodo['total_ordenes']) ?></span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <span class="text-white font-bold">$<?= number_format($metodo['total_ventas'], 2) ?></span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <span class="text-gray-300">$<?= number_format($metodo['promedio_venta'], 2) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Comparativa por períodos -->
    <div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden">
      <div class="p-6 border-b border-dark-700/50">
        <h3 class="text-xl font-montserrat-semibold text-white flex items-center">
          <i class="bi bi-calendar-range text-blue-400 mr-2"></i>
          Comparativa por Períodos
        </h3>
      </div>

      <div class="p-6 space-y-6">
        <!-- Hoy -->
        <div>
          <h4 class="text-white font-medium mb-3 flex items-center">
            <i class="bi bi-calendar-day text-yellow-400 mr-2"></i>
            Hoy
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-cash-coin text-green-400 mr-2 text-sm"></i>
                Efectivo
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasEfectivoHoy, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card text-blue-400 mr-2 text-sm"></i>
                Débito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasDebitoHoy, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card-fill text-purple-400 mr-2 text-sm"></i>
                Crédito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasCreditoHoy, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-bank text-indigo-400 mr-2 text-sm"></i>
                Transferencia
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasTransferenciaHoy, 2) ?></span>
            </div>
            <?php if ($ventasTarjetaHoy > 0): ?>
              <div class="flex justify-between items-center">
                <span class="text-gray-300 flex items-center">
                  <i class="bi bi-credit-card-2-front text-slate-400 mr-2 text-sm"></i>
                  Tarjeta (Legacy)
                </span>
                <span class="text-white font-bold">$<?= number_format($ventasTarjetaHoy, 2) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Semana -->
        <div>
          <h4 class="text-white font-medium mb-3 flex items-center">
            <i class="bi bi-calendar-week text-orange-400 mr-2"></i>
            Esta Semana
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-cash-coin text-green-400 mr-2 text-sm"></i>
                Efectivo
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasEfectivoSemana, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card text-blue-400 mr-2 text-sm"></i>
                Débito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasDebitoSemana, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card-fill text-purple-400 mr-2 text-sm"></i>
                Crédito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasCreditoSemana, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-bank text-indigo-400 mr-2 text-sm"></i>
                Transferencia
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasTransferenciaSemana, 2) ?></span>
            </div>
            <?php if ($ventasTarjetaSemana > 0): ?>
              <div class="flex justify-between items-center">
                <span class="text-gray-300 flex items-center">
                  <i class="bi bi-credit-card-2-front text-slate-400 mr-2 text-sm"></i>
                  Tarjeta (Legacy)
                </span>
                <span class="text-white font-bold">$<?= number_format($ventasTarjetaSemana, 2) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Mes -->
        <div>
          <h4 class="text-white font-medium mb-3 flex items-center">
            <i class="bi bi-calendar-month text-purple-400 mr-2"></i>
            Este Mes
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-cash-coin text-green-400 mr-2 text-sm"></i>
                Efectivo
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasEfectivoMes, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card text-blue-400 mr-2 text-sm"></i>
                Débito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasDebitoMes, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-credit-card-fill text-purple-400 mr-2 text-sm"></i>
                Crédito
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasCreditoMes, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-300 flex items-center">
                <i class="bi bi-bank text-indigo-400 mr-2 text-sm"></i>
                Transferencia
              </span>
              <span class="text-white font-bold">$<?= number_format($ventasTransferenciaMes, 2) ?></span>
            </div>
            <?php if ($ventasTarjetaMes > 0): ?>
              <div class="flex justify-between items-center">
                <span class="text-gray-300 flex items-center">
                  <i class="bi bi-credit-card-2-front text-slate-400 mr-2 text-sm"></i>
                  Tarjeta (Legacy)
                </span>
                <span class="text-white font-bold">$<?= number_format($ventasTarjetaMes, 2) ?></span>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráfico de barras visual para métodos de pago -->
  <div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl p-6">
    <h3 class="text-xl font-montserrat-semibold text-white mb-6 flex items-center">
      <i class="bi bi-graph-up text-purple-400 mr-2"></i>
      Distribución Visual por Método de Pago <?= $textoPeriodo ?>
    </h3>

    <div class="space-y-4">
      <?php
      $totalGeneral = $ventasEfectivo + $ventasDebito + $ventasCredito + $ventasTransferencia + $ventasTarjeta;
      $porcentajeEfectivo = $totalGeneral > 0 ? ($ventasEfectivo / $totalGeneral) * 100 : 0;
      $porcentajeDebito = $totalGeneral > 0 ? ($ventasDebito / $totalGeneral) * 100 : 0;
      $porcentajeCredito = $totalGeneral > 0 ? ($ventasCredito / $totalGeneral) * 100 : 0;
      $porcentajeTransferencia = $totalGeneral > 0 ? ($ventasTransferencia / $totalGeneral) * 100 : 0;
      $porcentajeTarjeta = $totalGeneral > 0 ? ($ventasTarjeta / $totalGeneral) * 100 : 0;
      ?>

      <!-- Barra Efectivo -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-white font-medium flex items-center">
            <i class="bi bi-cash-coin text-green-400 mr-2"></i>
            Efectivo
          </span>
          <span class="text-gray-300">
            $<?= number_format($ventasEfectivo, 2) ?> (<?= number_format($porcentajeEfectivo, 1) ?>%)
          </span>
        </div>
        <div class="w-full bg-dark-700 rounded-full h-3">
          <div class="bg-gradient-to-r from-green-500 to-green-600 h-3 rounded-full transition-all duration-300"
            style="width: <?= $porcentajeEfectivo ?>%"></div>
        </div>
      </div>

      <!-- Barra Débito -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-white font-medium flex items-center">
            <i class="bi bi-credit-card text-blue-400 mr-2"></i>
            Débito
          </span>
          <span class="text-gray-300">
            $<?= number_format($ventasDebito, 2) ?> (<?= number_format($porcentajeDebito, 1) ?>%)
          </span>
        </div>
        <div class="w-full bg-dark-700 rounded-full h-3">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-300"
            style="width: <?= $porcentajeDebito ?>%"></div>
        </div>
      </div>

      <!-- Barra Crédito -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-white font-medium flex items-center">
            <i class="bi bi-credit-card-fill text-purple-400 mr-2"></i>
            Crédito
          </span>
          <span class="text-gray-300">
            $<?= number_format($ventasCredito, 2) ?> (<?= number_format($porcentajeCredito, 1) ?>%)
          </span>
        </div>
        <div class="w-full bg-dark-700 rounded-full h-3">
          <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-3 rounded-full transition-all duration-300"
            style="width: <?= $porcentajeCredito ?>%"></div>
        </div>
      </div>

      <!-- Barra Transferencia -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-white font-medium flex items-center">
            <i class="bi bi-bank text-indigo-400 mr-2"></i>
            Transferencia
          </span>
          <span class="text-gray-300">
            $<?= number_format($ventasTransferencia, 2) ?> (<?= number_format($porcentajeTransferencia, 1) ?>%)
          </span>
        </div>
        <div class="w-full bg-dark-700 rounded-full h-3">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-3 rounded-full transition-all duration-300"
            style="width: <?= $porcentajeTransferencia ?>%"></div>
        </div>
      </div>

      <!-- Barra Tarjeta (Legacy) - Solo si hay datos -->
      <?php if ($ventasTarjeta > 0): ?>
        <div>
          <div class="flex justify-between items-center mb-2">
            <span class="text-white font-medium flex items-center">
              <i class="bi bi-credit-card-2-front text-slate-400 mr-2"></i>
              Tarjeta (Legacy)
            </span>
            <span class="text-gray-300">
              $<?= number_format($ventasTarjeta, 2) ?> (<?= number_format($porcentajeTarjeta, 1) ?>%)
            </span>
          </div>
          <div class="w-full bg-dark-700 rounded-full h-3">
            <div class="bg-gradient-to-r from-slate-500 to-slate-600 h-3 rounded-full transition-all duration-300"
              style="width: <?= $porcentajeTarjeta ?>%"></div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-6 p-4 bg-dark-700/50 rounded-xl">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-center sm:text-left">
          <p class="text-gray-400 text-sm">Total General</p>
          <p class="text-2xl font-montserrat-bold text-white">$<?= number_format($totalGeneral, 2) ?></p>
        </div>
        <div class="flex flex-wrap gap-4 justify-center">
          <div class="text-center">
            <p class="text-green-400 font-medium text-sm">Efectivo</p>
            <p class="text-lg font-bold text-white"><?= number_format($porcentajeEfectivo, 1) ?>%</p>
          </div>
          <div class="text-center">
            <p class="text-blue-400 font-medium text-sm">Débito</p>
            <p class="text-lg font-bold text-white"><?= number_format($porcentajeDebito, 1) ?>%</p>
          </div>
          <div class="text-center">
            <p class="text-purple-400 font-medium text-sm">Crédito</p>
            <p class="text-lg font-bold text-white"><?= number_format($porcentajeCredito, 1) ?>%</p>
          </div>
          <div class="text-center">
            <p class="text-indigo-400 font-medium text-sm">Transferencia</p>
            <p class="text-lg font-bold text-white"><?= number_format($porcentajeTransferencia, 1) ?>%</p>
          </div>
          <?php if ($ventasTarjeta > 0): ?>
            <div class="text-center">
              <p class="text-slate-400 font-medium text-sm">Legacy</p>
              <p class="text-lg font-bold text-white"><?= number_format($porcentajeTarjeta, 1) ?>%</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Productos más vendidos -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
  <div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-dark-700/50">
      <h3 class="text-xl font-montserrat-semibold text-white flex items-center">
        <i class="bi bi-trophy text-yellow-400 mr-2"></i>
        Productos Más Vendidos <?= $textoPeriodo ?>
      </h3>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-dark-700/50">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">#</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Producto</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Vendidos</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Ingresos</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-dark-700/50">
          <?php if (empty($productosVendidos)): ?>
            <tr>
              <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                <div class="flex flex-col items-center">
                  <i class="bi bi-inbox text-4xl mb-2"></i>
                  <p>No hay datos de productos vendidos</p>
                  <p class="text-sm">Las ventas aparecerán aquí cuando se cierren órdenes</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($productosVendidos as $index => $producto): ?>
              <tr class="hover:bg-dark-700/30 transition-colors duration-200">
                <td class="px-6 py-4 text-sm font-medium text-gray-300">
                  <span class="inline-flex items-center justify-center w-6 h-6 bg-yellow-500/20 text-yellow-400 rounded-full text-xs font-bold">
                    <?= $index + 1 ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-sm font-medium text-white">
                  <?= htmlspecialchars($producto['nombre']) ?>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
                    <?= number_format($producto['total_vendido']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-sm font-bold text-green-400">
                  $<?= number_format($producto['total_ingresos'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ventas por mesa -->
  <div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-dark-700/50">
      <h3 class="text-xl font-montserrat-semibold text-white flex items-center">
        <i class="bi bi-grid-3x3 text-blue-400 mr-2"></i>
        Ventas por Mesa <?= $textoPeriodo ?>
      </h3>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-dark-700/50">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Mesa</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Órdenes</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Total Ventas</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-dark-700/50">
          <?php if (empty($ventasPorMesa)): ?>
            <tr>
              <td colspan="3" class="px-6 py-8 text-center text-gray-400">
                <div class="flex flex-col items-center">
                  <i class="bi bi-table text-4xl mb-2"></i>
                  <p>No hay datos de ventas por mesa</p>
                  <p class="text-sm">Las ventas aparecerán aquí cuando se cierren órdenes</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($ventasPorMesa as $venta): ?>
              <tr class="hover:bg-dark-700/30 transition-colors duration-200">
                <td class="px-6 py-4 text-sm font-medium text-white">
                  <span class="inline-flex items-center">
                    <i class="bi bi-table text-blue-400 mr-2"></i>
                    <?= htmlspecialchars($venta['mesa']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400">
                    <?= number_format($venta['ordenes_cerradas']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-sm font-bold text-green-400">
                  $<?= number_format($venta['total_ventas'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Nueva sección: Órdenes Activas -->
<div class="mt-8">
  <div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-dark-700/50">
      <div class="flex justify-between items-center">
        <h3 class="text-xl font-montserrat-semibold text-white flex items-center">
          <i class="bi bi-clock text-orange-400 mr-2"></i>
          Órdenes Activas
        </h3>
        <div class="bg-orange-600 text-white px-3 py-1 rounded-full text-sm font-bold">
          <?= count($ordenesActivasDetalle) ?> abiertas
        </div>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-dark-700/50">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Código</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Mesa</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Productos</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Tiempo Abierta</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Método Pago</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Total Actual</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-dark-700/50">
          <?php if (empty($ordenesActivasDetalle)): ?>
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                <div class="flex flex-col items-center">
                  <i class="bi bi-check-circle text-4xl mb-2 text-green-500"></i>
                  <p>No hay órdenes activas</p>
                  <p class="text-sm">¡Todas las órdenes están cerradas!</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($ordenesActivasDetalle as $orden): ?>
              <tr class="hover:bg-dark-700/30 transition-colors duration-200">
                <td class="px-6 py-4 text-sm font-medium text-white">
                  <?= htmlspecialchars($orden['codigo']) ?>
                </td>
                <td class="px-6 py-4 text-sm text-white">
                  <span class="inline-flex items-center">
                    <i class="bi bi-table text-blue-400 mr-2"></i>
                    <?= htmlspecialchars($orden['mesa']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
                    <?= $orden['productos_count'] ?> items
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <?php
                  $horas = floor($orden['minutos_abierta'] / 60);
                  $minutos = $orden['minutos_abierta'] % 60;
                  $tiempo_color = $orden['minutos_abierta'] > 60 ? 'text-red-400' : ($orden['minutos_abierta'] > 30 ? 'text-yellow-400' : 'text-green-400');
                  ?>
                  <span class="<?= $tiempo_color ?> font-medium">
                    <?= $horas > 0 ? "{$horas}h " : "" ?><?= $minutos ?>min
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm">
                  <?php
                  $metodo = $orden['metodo_pago'] ?? 'efectivo';
                  $metodo_icon = $metodo == 'tarjeta' ? 'bi-credit-card' : 'bi-cash';
                  $metodo_color = $metodo == 'tarjeta' ? 'text-blue-400' : 'text-green-400';
                  ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 <?= $metodo_color ?>">
                    <i class="<?= $metodo_icon ?> mr-1"></i>
                    <?= ucfirst($metodo) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-sm font-bold text-green-400">
                  $<?= number_format($orden['total'], 2) ?>
                </td>
                <td class="px-6 py-4 text-center">
                  <a href="index.php?page=mesa&id=<?= $orden['id'] ?>"
                    class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition-colors">
                    <i class="bi bi-eye mr-1"></i>Ver
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Nueva sección: Órdenes Cerradas Recientes -->
<div class="mt-8">
  <div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden">
    <!-- Encabezado de la sección -->
    <div class="p-6 border-b border-dark-700/50">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h3 class="text-xl font-montserrat-semibold text-white flex items-center">
            <i class="bi bi-check-circle text-green-400 mr-2"></i>
            Órdenes Cerradas Recientes
          </h3>
          <p id="ordenes-info" class="text-gray-400 text-sm mt-1">
            <?= $totalOrdenesCerradas ?> órdenes encontradas • Página <?= $paginaOrdenes ?> de <?= $totalPaginasOrdenes ?>
          </p>
        </div>
        <div id="ordenes-periodo" class="bg-green-600 text-white px-3 py-1 rounded-full text-sm font-bold">
          <?= $textoPeriodo ?>
        </div>
      </div>
    </div>

    <!-- Loading indicator -->
    <div id="ordenes-loading" class="hidden p-8 text-center">
      <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-indigo-500 transition ease-in-out duration-150 cursor-not-allowed">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Cargando órdenes...
      </div>
    </div>

    <!-- Tabla de órdenes -->
    <div id="ordenes-container" class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-dark-700/50">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Código</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Mesa</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Productos</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider" title="Tiempo entre creación y cierre de la orden">
              <i class="bi bi-clock mr-1"></i>
              Tiempo Total
            </th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Método Pago</th>
            <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Hora</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Total</th>
          </tr>
        </thead>
        <tbody id="ordenes-tbody" class="divide-y divide-dark-700/50">
          <?php if (empty($ordenesCerradasDetalle)): ?>
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                <div class="flex flex-col items-center">
                  <i class="bi bi-inbox text-4xl mb-2"></i>
                  <p>No hay órdenes cerradas <?= strtolower($textoPeriodo) ?></p>
                  <p class="text-sm">Las órdenes cerradas aparecerán aquí</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($ordenesCerradasDetalle as $orden): ?>
              <tr class="hover:bg-dark-700/30 transition-colors duration-200">
                <td class="px-6 py-4 text-sm font-medium text-white">
                  <?= htmlspecialchars($orden['codigo']) ?>
                </td>
                <td class="px-6 py-4 text-sm text-white">
                  <span class="inline-flex items-center">
                    <i class="bi bi-table text-blue-400 mr-2"></i>
                    <?= htmlspecialchars($orden['mesa']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400">
                    <?= $orden['productos_count'] ?> items
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <?php if ($orden['tiempo_total_minutos'] !== null && $orden['tiempo_total_minutos'] >= 0): ?>
                    <?php
                    $horas_total = floor($orden['tiempo_total_minutos'] / 60);
                    $minutos_total = $orden['tiempo_total_minutos'] % 60;
                    ?>
                    <span class="text-blue-400" title="Tiempo entre creación y cierre de la orden">
                      <?= $horas_total > 0 ? "{$horas_total}h " : "" ?><?= $minutos_total ?>min
                    </span>
                  <?php else: ?>
                    <span class="text-gray-500">--</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-center text-sm">
                  <?php
                  $metodo = $orden['metodo_pago'] ?? 'efectivo';
                  $metodos_config = [
                    'efectivo' => ['icon' => 'bi-cash', 'color' => 'text-green-400'],
                    'debito' => ['icon' => 'bi-credit-card', 'color' => 'text-blue-400'],
                    'credito' => ['icon' => 'bi-credit-card', 'color' => 'text-purple-400'],
                    'transferencia' => ['icon' => 'bi-bank', 'color' => 'text-indigo-400'],
                    'tarjeta' => ['icon' => 'bi-credit-card', 'color' => 'text-blue-400'] // Para compatibilidad
                  ];
                  $metodo_config = $metodos_config[$metodo] ?? $metodos_config['efectivo'];
                  ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 <?= $metodo_config['color'] ?>">
                    <i class="<?= $metodo_config['icon'] ?> mr-1"></i>
                    <?= ucfirst($metodo) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                  <?= date('H:i', strtotime($orden['creada_en'])) ?>
                </td>
                <td class="px-6 py-4 text-right text-sm font-bold text-green-400">
                  $<?= number_format($orden['total'], 2) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Controles de paginación -->
    <div id="ordenes-paginacion" class="p-6 border-t border-dark-700/50" style="<?= $totalPaginasOrdenes <= 1 ? 'display: none;' : '' ?>">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <!-- Información de paginación -->
        <div id="ordenes-paginacion-info" class="text-sm text-gray-400">
          Mostrando <?= (($paginaOrdenes - 1) * $ordenesPorPagina) + 1 ?> -
          <?= min($paginaOrdenes * $ordenesPorPagina, $totalOrdenesCerradas) ?>
          de <?= $totalOrdenesCerradas ?> órdenes
        </div>

        <!-- Controles de navegación -->
        <nav id="ordenes-controles-paginacion" class="flex items-center space-x-1">
          <!-- Se generará dinámicamente con JavaScript -->
        </nav>
      </div>
    </div>
  </div>
</div>


<script>
  // Función para mostrar/ocultar sección de reportes PDF
  function toggleReportesPDF() {
    const section = document.getElementById('reportes-pdf-section');
    const icon = document.getElementById('pdf-toggle-icon');

    if (section.style.maxHeight === '0px' || section.style.maxHeight === '') {
      // Mostrar
      section.style.maxHeight = '800px';
      section.style.opacity = '1';
      icon.style.transform = 'rotate(180deg)';
    } else {
      // Ocultar
      section.style.maxHeight = '0px';
      section.style.opacity = '0';
      icon.style.transform = 'rotate(0deg)';
    }
  }

  // Funciones para generar reportes PDF
  <?php
  $userInfo = getUserInfo();
  $esAdministrador = $userInfo['rol'] === 'administrador';
  if ($esAdministrador || hasPermission('reportes', 'ver')):
  ?>

    function generarReporteProductos() {
      // Mostrar loading
      const btn = event.target.closest('button');
      const originalContent = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-hourglass-split animate-spin mr-2"></i>Generando...';
      btn.disabled = true;

      try {
        // Obtener parámetros de fecha de la URL actual
        const urlParams = new URLSearchParams(window.location.search);
        const fechaDesde = urlParams.get('fecha_desde');
        const fechaHasta = urlParams.get('fecha_hasta');

        // Construir URL con parámetros de fecha
        let reportUrl = 'controllers/reportes/reporte_productos_vendidos.php';
        if (fechaDesde && fechaHasta) {
          reportUrl += `?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}`;
        }

        // Abrir reporte en nueva ventana
        window.open(reportUrl, '_blank');

        // Mostrar mensaje de éxito
        setTimeout(() => {
          btn.innerHTML = '<i class="bi bi-check-circle mr-2"></i>Generado!';
          btn.classList.add('bg-green-600');

          setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.classList.remove('bg-green-600');
            btn.disabled = false;
          }, 2000);
        }, 1000);

      } catch (error) {
        console.error('Error al generar reporte:', error);
        btn.innerHTML = '<i class="bi bi-exclamation-triangle mr-2"></i>Error';
        btn.classList.add('bg-red-600');

        setTimeout(() => {
          btn.innerHTML = originalContent;
          btn.classList.remove('bg-red-600');
          btn.disabled = false;
        }, 3000);
      }
    }

    function generarReporteOrdenes() {
      // Mostrar loading
      const btn = event.target.closest('button');
      const originalContent = btn.innerHTML;
      btn.innerHTML = '<i class="bi bi-hourglass-split animate-spin mr-2"></i>Generando...';
      btn.disabled = true;

      try {
        // Obtener parámetros de fecha de la URL actual
        const urlParams = new URLSearchParams(window.location.search);
        const fechaDesde = urlParams.get('fecha_desde');
        const fechaHasta = urlParams.get('fecha_hasta');

        // Construir URL con parámetros de fecha
        let reportUrl = 'controllers/reportes/reporte_ordenes_del_dia.php';
        if (fechaDesde && fechaHasta) {
          reportUrl += `?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}`;
        }

        // Abrir reporte en nueva ventana
        window.open(reportUrl, '_blank');

        // Mostrar mensaje de éxito
        setTimeout(() => {
          btn.innerHTML = '<i class="bi bi-check-circle mr-2"></i>Generado!';
          btn.classList.add('bg-green-600');

          setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.classList.remove('bg-green-600');
            btn.disabled = false;
          }, 2000);
        }, 1000);

      } catch (error) {
        console.error('Error al generar reporte:', error);
        btn.innerHTML = '<i class="bi bi-exclamation-triangle mr-2"></i>Error';
        btn.classList.add('bg-red-600');

        setTimeout(() => {
          btn.innerHTML = originalContent;
          btn.classList.remove('bg-red-600');
          btn.disabled = false;
        }, 3000);
      }
    }

    // Función de exportar original (para compatibilidad)
    function exportarReporte() {
      // Mostrar la sección de reportes si está oculta
      const section = document.getElementById('reportes-pdf-section');
      if (section.style.maxHeight === '0px' || section.style.maxHeight === '') {
        toggleReportesPDF();
      }
    }

  <?php endif; ?>

  // Funciones para filtrar por fecha
  function filtrarPorFecha() {
    const fechaDesde = document.getElementById('fecha-desde').value;
    const fechaHasta = document.getElementById('fecha-hasta').value;

    if (!fechaDesde || !fechaHasta) {
      alert('Por favor selecciona ambas fechas');
      return;
    }

    if (fechaDesde > fechaHasta) {
      alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
      return;
    }

    // Construir URL con parámetros de fecha
    const params = new URLSearchParams();
    params.append('fecha_desde', fechaDesde);
    params.append('fecha_hasta', fechaHasta);

    // Recargar la página con los filtros aplicados
    window.location.href = 'index.php?page=reportes&' + params.toString();
  }

  function limpiarFiltros() {
    // Volver a la página sin filtros
    window.location.href = 'index.php?page=reportes';
  }

  // Función para establecer fechas desde URL y cargar datos
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const fechaDesde = urlParams.get('fecha_desde');
    const fechaHasta = urlParams.get('fecha_hasta');
    const paginaOrdenes = urlParams.get('pagina_ordenes') || 1;

    // Establecer valores en los campos de fecha
    if (fechaDesde) {
      const fechaDesdeInput = document.getElementById('fecha-desde');
      if (fechaDesdeInput) {
        fechaDesdeInput.value = fechaDesde;
      }
    }
    if (fechaHasta) {
      const fechaHastaInput = document.getElementById('fecha-hasta');
      if (fechaHastaInput) {
        fechaHastaInput.value = fechaHasta;
      }
    }

    // Si hay filtros de fecha aplicados, cargar las órdenes vía AJAX
    if (fechaDesde && fechaHasta) {
      // Dar tiempo a que se cargue completamente la página
      setTimeout(() => {
        cargarOrdenesAjax(parseInt(paginaOrdenes));
      }, 100);
    }
  });

  // Función para cambiar página de órdenes manteniendo filtros
  function cambiarPaginaOrdenes(pagina) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('pagina_ordenes', pagina);
    window.location.href = 'index.php?' + urlParams.toString();
  }

  // Variables globales para paginación de órdenes
  let paginaActualOrdenes = <?= $paginaOrdenes ?>;
  let totalPaginasOrdenes = <?= $totalPaginasOrdenes ?>;

  // Función para cargar órdenes via AJAX
  function cargarOrdenesAjax(pagina = 1) {
    // Mostrar loading
    document.getElementById('ordenes-loading').classList.remove('hidden');
    document.getElementById('ordenes-container').style.opacity = '0.5';

    // Obtener filtros de fecha actuales
    const urlParams = new URLSearchParams(window.location.search);
    const fechaDesde = urlParams.get('fecha_desde');
    const fechaHasta = urlParams.get('fecha_hasta');

    // Construir URL de la petición
    let ajaxUrl = 'controllers/ajax_ordenes_cerradas.php?pagina=' + pagina;
    if (fechaDesde && fechaHasta) {
      ajaxUrl += '&fecha_desde=' + fechaDesde + '&fecha_hasta=' + fechaHasta;
    }

    fetch(ajaxUrl)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Actualizar contenido de la tabla
          actualizarTablaOrdenes(data.ordenes, data.periodo);

          // Actualizar información de paginación
          actualizarInfoPaginacion(data.paginacion);

          // Actualizar controles de paginación
          actualizarControlesPaginacion(data.paginacion);

          // Actualizar variables globales
          paginaActualOrdenes = data.paginacion.pagina_actual;
          totalPaginasOrdenes = data.paginacion.total_paginas;

        } else {
          console.error('Error al cargar órdenes:', data.error);
          mostrarError('Error al cargar las órdenes');
        }
      })
      .catch(error => {
        console.error('Error de conexión:', error);
        mostrarError('Error de conexión al servidor');
      })
      .finally(() => {
        // Ocultar loading
        document.getElementById('ordenes-loading').classList.add('hidden');
        document.getElementById('ordenes-container').style.opacity = '1';
      });
  }

  // Función para actualizar la tabla de órdenes
  function actualizarTablaOrdenes(ordenes, periodo) {
    const tbody = document.getElementById('ordenes-tbody');

    if (ordenes.length === 0) {
      tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                    <div class="flex flex-col items-center">
                        <i class="bi bi-inbox text-4xl mb-2"></i>
                        <p>No hay órdenes cerradas ${periodo.toLowerCase()}</p>
                        <p class="text-sm">Las órdenes cerradas aparecerán aquí</p>
                    </div>
                </td>
            </tr>
        `;
      return;
    }

    let html = '';
    ordenes.forEach(orden => {
      // Formatear el tiempo total (entre creación y cierre)
      let tiempoTexto = '';
      if (orden.tiempo_total_minutos !== null && orden.tiempo_total_minutos >= 0) {
        const horasTotal = Math.floor(orden.tiempo_total_minutos / 60);
        const minutosTotal = orden.tiempo_total_minutos % 60;
        
        if (horasTotal > 0) {
          tiempoTexto = `${horasTotal}h ${minutosTotal}min`;
        } else {
          tiempoTexto = `${minutosTotal}min`;
        }
      } else {
        tiempoTexto = '<span class="text-gray-500">--</span>';
      }

      const metodo = orden.metodo_pago || 'efectivo';
      const metodosConfig = {
        'efectivo': {
          icon: 'bi-cash',
          color: 'text-green-400'
        },
        'debito': {
          icon: 'bi-credit-card',
          color: 'text-blue-400'
        },
        'credito': {
          icon: 'bi-credit-card',
          color: 'text-purple-400'
        },
        'transferencia': {
          icon: 'bi-bank',
          color: 'text-indigo-400'
        },
        'tarjeta': {
          icon: 'bi-credit-card',
          color: 'text-blue-400'
        }
      };
      const metodoConfig = metodosConfig[metodo] || metodosConfig['efectivo'];

      html += `
            <tr class="hover:bg-dark-700/30 transition-colors duration-200">
                <td class="px-6 py-4 text-sm font-medium text-white">
                    ${orden.codigo}
                </td>
                <td class="px-6 py-4 text-sm text-white">
                    <span class="inline-flex items-center">
                        <i class="bi bi-table text-blue-400 mr-2"></i>
                        ${orden.mesa}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-400">
                        ${orden.productos_count} items
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                    <span class="text-blue-400" title="Tiempo entre creación y cierre de la orden">
                        ${tiempoTexto}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 ${metodoConfig.color}">
                        <i class="${metodoConfig.icon} mr-1"></i>
                        ${metodo.charAt(0).toUpperCase() + metodo.slice(1)}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-300">
                    ${new Date(orden.creada_en).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}
                </td>
                <td class="px-6 py-4 text-right text-sm font-bold text-green-400">
                    $${parseFloat(orden.total).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
  }

  // Función para actualizar información de paginación
  function actualizarInfoPaginacion(paginacion) {
    const infoElement = document.getElementById('ordenes-info');
    const periodoElement = document.getElementById('ordenes-periodo');
    const paginacionInfoElement = document.getElementById('ordenes-paginacion-info');

    // Verificar si hay filtros activos
    const urlParams = new URLSearchParams(window.location.search);
    const fechaDesde = urlParams.get('fecha_desde');
    const fechaHasta = urlParams.get('fecha_hasta');

    let textoFiltro = '';
    if (fechaDesde && fechaHasta) {
      textoFiltro = ` (filtrado del ${fechaDesde.split('-').reverse().join('/')} al ${fechaHasta.split('-').reverse().join('/')})`;
    }

    infoElement.innerHTML = `
        <span>${paginacion.total_ordenes} órdenes encontradas${textoFiltro}</span>
        <span class="mx-2">•</span>
        <span>Página ${paginacion.pagina_actual} de ${paginacion.total_paginas}</span>
        ${fechaDesde && fechaHasta ? '<span class="ml-2 px-2 py-1 bg-blue-600 text-xs rounded-full"><i class="bi bi-funnel"></i> Filtrado</span>' : ''}
    `;

    paginacionInfoElement.textContent = `Mostrando ${paginacion.desde} - ${paginacion.hasta} de ${paginacion.total_ordenes} órdenes`;

    // Mostrar/ocultar paginación
    const paginacionDiv = document.getElementById('ordenes-paginacion');
    if (paginacion.total_paginas <= 1) {
      paginacionDiv.style.display = 'none';
    } else {
      paginacionDiv.style.display = 'block';
    }
  }

  // Función para actualizar controles de paginación
  function actualizarControlesPaginacion(paginacion) {
    const controlesElement = document.getElementById('ordenes-controles-paginacion');

    let html = '';

    // Botón anterior
    if (paginacion.pagina_actual > 1) {
      html += `
            <button onclick="cargarOrdenesAjax(${paginacion.pagina_actual - 1})" 
                    class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                <i class="bi bi-chevron-left"></i>
            </button>
        `;
    } else {
      html += `
            <span class="px-3 py-2 rounded-lg bg-dark-800/50 text-gray-500 cursor-not-allowed">
                <i class="bi bi-chevron-left"></i>
            </span>
        `;
    }

    // Números de página
    const inicio = Math.max(1, paginacion.pagina_actual - 2);
    const fin = Math.min(paginacion.total_paginas, paginacion.pagina_actual + 2);

    // Primera página si es necesario
    if (inicio > 1) {
      html += `
            <button onclick="cargarOrdenesAjax(1)" 
                    class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                1
            </button>
        `;
      if (inicio > 2) {
        html += '<span class="px-2 text-gray-500">...</span>';
      }
    }

    // Páginas del rango
    for (let i = inicio; i <= fin; i++) {
      if (i === paginacion.pagina_actual) {
        html += `
                <span class="px-3 py-2 rounded-lg bg-green-600 text-white font-bold">
                    ${i}
                </span>
            `;
      } else {
        html += `
                <button onclick="cargarOrdenesAjax(${i})" 
                        class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                    ${i}
                </button>
            `;
      }
    }

    // Última página si es necesario
    if (fin < paginacion.total_paginas) {
      if (fin < paginacion.total_paginas - 1) {
        html += '<span class="px-2 text-gray-500">...</span>';
      }
      html += `
            <button onclick="cargarOrdenesAjax(${paginacion.total_paginas})" 
                    class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                ${paginacion.total_paginas}
            </button>
        `;
    }

    // Botón siguiente
    if (paginacion.pagina_actual < paginacion.total_paginas) {
      html += `
            <button onclick="cargarOrdenesAjax(${paginacion.pagina_actual + 1})" 
                    class="px-3 py-2 rounded-lg bg-dark-700/50 text-gray-300 hover:bg-dark-600/50 hover:text-white transition-colors duration-200">
                <i class="bi bi-chevron-right"></i>
            </button>
        `;
    } else {
      html += `
            <span class="px-3 py-2 rounded-lg bg-dark-800/50 text-gray-500 cursor-not-allowed">
                <i class="bi bi-chevron-right"></i>
            </span>
        `;
    }

    controlesElement.innerHTML = html;
  }

  // Función para mostrar errores
  function mostrarError(mensaje) {
    const tbody = document.getElementById('ordenes-tbody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-8 text-center text-red-400">
                <div class="flex flex-col items-center">
                    <i class="bi bi-exclamation-triangle text-4xl mb-2"></i>
                    <p>${mensaje}</p>
                    <button onclick="cargarOrdenesAjax(paginaActualOrdenes)" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Reintentar
                    </button>
                </div>
            </td>
        </tr>
    `;
  }

  // Modificar la función de filtrar por fecha para recargar solo órdenes via AJAX
  function filtrarPorFecha() {
    const fechaDesde = document.getElementById('fecha-desde').value;
    const fechaHasta = document.getElementById('fecha-hasta').value;

    if (!fechaDesde || !fechaHasta) {
      alert('Por favor selecciona ambas fechas');
      return;
    }

    if (fechaDesde > fechaHasta) {
      alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
      return;
    }

    // Actualizar URL sin recargar toda la página
    const params = new URLSearchParams(window.location.search);
    params.set('fecha_desde', fechaDesde);
    params.set('fecha_hasta', fechaHasta);
    params.delete('pagina_ordenes'); // Resetear a página 1

    // Actualizar URL en el navegador
    const newUrl = 'index.php?page=reportes&' + params.toString();
    window.history.pushState({}, '', newUrl);

    // Solo recargar las órdenes via AJAX (página 1)
    cargarOrdenesAjax(1);

    // Actualizar el badge de período en la sección de órdenes
    const textoPeriodo = `del ${fechaDesde.split('-').reverse().join('/')} al ${fechaHasta.split('-').reverse().join('/')}`;
    document.getElementById('ordenes-periodo').textContent = textoPeriodo;

    // Para el resto de las secciones, necesitamos recargar la página completa
    // Esto lo haremos solo si el usuario lo solicita específicamente
    mostrarOpcionRecargaCompleta();
  }

  // Función para mostrar opción de recarga completa
  function mostrarOpcionRecargaCompleta() {
    // Crear un toast/notificación para preguntar si quiere actualizar todo
    const existingToast = document.getElementById('filter-toast');
    if (existingToast) {
      existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.id = 'filter-toast';
    toast.className = 'fixed top-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50 max-w-sm';
    toast.innerHTML = `
        <div class="flex items-start gap-3">
            <i class="bi bi-info-circle text-xl mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold mb-2">Filtro aplicado</p>
                <p class="text-sm text-blue-100 mb-3">Las órdenes se han actualizado. ¿Deseas actualizar también el resto de reportes?</p>
                <div class="flex gap-2">
                    <button onclick="recargarTodosLosReportes()" class="px-3 py-1 bg-white text-blue-600 rounded text-sm font-semibold hover:bg-blue-50 transition-colors">
                        Sí, actualizar todo
                    </button>
                    <button onclick="cerrarToast()" class="px-3 py-1 bg-blue-700 text-white rounded text-sm hover:bg-blue-800 transition-colors">
                        Solo órdenes
                    </button>
                </div>
            </div>
            <button onclick="cerrarToast()" class="text-blue-200 hover:text-white">
                <i class="bi bi-x text-lg"></i>
            </button>
        </div>
    `;

    document.body.appendChild(toast);

    // Auto-cerrar después de 10 segundos
    setTimeout(() => {
      if (document.getElementById('filter-toast')) {
        cerrarToast();
      }
    }, 10000);
  }

  // Función para recargar todos los reportes
  function recargarTodosLosReportes() {
    cerrarToast();
    window.location.reload();
  }

  // Función para limpiar filtros de fecha
  function limpiarFiltros() {
    // Limpiar campos de fecha
    document.getElementById('fecha-desde').value = '';
    document.getElementById('fecha-hasta').value = '';

    // Limpiar parámetros de la URL
    const params = new URLSearchParams(window.location.search);
    params.delete('fecha_desde');
    params.delete('fecha_hasta');
    params.delete('pagina_ordenes');

    // Actualizar URL
    const newUrl = 'index.php?page=reportes&' + params.toString();
    window.history.pushState({}, '', newUrl);

    // Recargar órdenes (página 1)
    cargarOrdenesAjax(1);

    // Actualizar texto del período
    document.getElementById('ordenes-periodo').textContent = 'del día de hoy';

    // Mostrar mensaje de confirmación
    mostrarMensajeExito('Filtros eliminados. Mostrando órdenes del día de hoy.');
  }

  // Función para mostrar mensaje de éxito
  function mostrarMensajeExito(mensaje) {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-green-600 text-white p-3 rounded-lg shadow-lg z-50 transition-all duration-300';
    toast.innerHTML = `
        <div class="flex items-center gap-2">
            <i class="bi bi-check-circle"></i>
            <span>${mensaje}</span>
        </div>
    `;

    document.body.appendChild(toast);

    // Auto-remover después de 3 segundos
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Función para cerrar el toast
  function cerrarToast() {
    const toast = document.getElementById('filter-toast');
    if (toast) {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      setTimeout(() => toast.remove(), 300);
    }
  }
</script>