<?php
// Este archivo es incluido desde index.php, por lo que las rutas son relativas al directorio raíz
// $pdo y $userInfo ya están disponibles desde index.php

$orden_id = intval($_GET['id'] ?? 0);
$orden = $pdo->prepare("
    SELECT o.*, m.nombre AS mesa_nombre
    FROM ordenes o
    JOIN mesas m ON m.id = o.mesa_id
    WHERE o.id = ?
");
$orden->execute([$orden_id]);
$orden = $orden->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
  echo "<div class='bg-red-500/10 border border-red-500/20 text-red-400 px-6 py-4 rounded-xl mb-6'>
          <i class='bi bi-exclamation-triangle mr-2'></i>
          Orden no encontrada
        </div>";
  exit;
}

// Productos
$productos = $pdo->prepare("
    SELECT p.nombre, op.cantidad, op.preparado, COALESCE(op.cancelado, 0) as cancelado, p.precio
    FROM orden_productos op
    JOIN productos p ON op.producto_id = p.id
    WHERE op.orden_id = ?
    ORDER BY p.nombre
");
$productos->execute([$orden_id]);
$productos = $productos->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
$total_cancelado = 0;
$productos_activos = 0;
$productos_cancelados = 0;

foreach ($productos as $prod) {
    if ($prod['cancelado'] == 0) {
        // Producto activo
        $subtotal += $prod['precio'] * $prod['cantidad'];
        $productos_activos += $prod['cantidad'];
    } else {
        // Producto cancelado
        $total_cancelado += $prod['precio'] * $prod['cantidad'];
        $productos_cancelados += $prod['cantidad'];
    }
}

$descuento = 0;
$impuestos = 0;
$total = $subtotal - $descuento + $impuestos;

// Actualizar el total en la base de datos si es diferente al calculado
if (isset($orden['total']) && abs($orden['total'] - $total) > 0.01) {
    $update_total = $pdo->prepare("UPDATE ordenes SET total = ? WHERE id = ?");
    $update_total->execute([$total, $orden_id]);
    
    // Actualizar el array orden para mostrar el total correcto
    $orden['total'] = $total;
}
?>

<!-- Hero Section -->
<div class="text-center mb-8">
  <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl mb-4 shadow-lg">
    <i class="bi bi-receipt text-white text-2xl"></i>
  </div>
  <h1 class="text-3xl md:text-4xl font-montserrat-bold font-display gradient-text mb-3">Detalle de Orden</h1>
  <p class="text-lg text-gray-400">Información completa de la orden <?= htmlspecialchars($orden['codigo']) ?></p>
</div>

<!-- Action Bar -->
<div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-8">
  <a href="index.php?page=ordenes" 
     class="flex items-center space-x-2 px-6 py-3 bg-dark-600 hover:bg-dark-500 text-gray-300 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
    <i class="bi bi-arrow-left"></i>
    <span>Volver al Listado</span>
  </a>
  
  <a href="controllers/exportar_order_pdf.php?id=<?= $orden['id'] ?>" 
     target="_blank"
     class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
    <i class="bi bi-file-pdf"></i>
    <span>Exportar PDF</span>
  </a>
</div>

<!-- Order Information Card -->
<div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl p-6 mb-8">
  <h2 class="text-xl font-montserrat-semibold text-white mb-6 flex items-center">
    <i class="bi bi-info-circle mr-2 text-blue-400"></i>
    Información de la Orden
  </h2>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Código</label>
      <p class="text-lg font-semibold text-white"><?= htmlspecialchars($orden['codigo']) ?></p>
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Mesa</label>
      <p class="text-lg font-semibold text-white"><?= htmlspecialchars($orden['mesa_nombre']) ?></p>
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Estado</label>
      <div>
        <?php
        $estado = $orden['estado'];
        $badgeClass = match($estado) {
          'pagada' => 'bg-green-500/20 text-green-400 border-green-500/30',
          'cerrada' => 'bg-green-500/20 text-green-400 border-green-500/30',
          'cancelada' => 'bg-red-500/20 text-red-400 border-red-500/30',
          'abierta' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
          default => 'bg-blue-500/20 text-blue-400 border-blue-500/30'
        };
        ?>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border <?= $badgeClass ?>">
          <i class="bi bi-circle-fill mr-2 text-xs"></i>
          <?= ucfirst($estado) ?>
        </span>
      </div>
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Total</label>
      <p class="text-lg font-semibold text-green-400">$<?= number_format($total, 2) ?></p>
      <?php if ($productos_cancelados > 0): ?>
        <p class="text-xs text-red-400">
          <i class="bi bi-exclamation-triangle mr-1"></i>
          <?= $productos_cancelados ?> producto(s) cancelado(s)
        </p>
      <?php endif; ?>
    </div>
    
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-400">Fecha de Creación</label>
      <p class="text-lg font-semibold text-white"><?= date('d/m/Y H:i', strtotime($orden['creada_en'])) ?></p>
    </div>
  </div>
</div>

<!-- Products Table -->
<div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden mb-8">
  <div class="p-6 border-b border-dark-700/50">
    <h2 class="text-xl font-montserrat-semibold text-white flex items-center">
      <i class="bi bi-bag mr-2 text-purple-400"></i>
      Productos de la Orden
    </h2>
  </div>
  
  <div class="overflow-x-auto">
    <table class="w-full">
      <thead class="bg-dark-700/50">
        <tr>
          <th class="px-6 py-4 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Producto</th>
          <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Cantidad</th>
          <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Preparado</th>
          <th class="px-6 py-4 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Cancelado</th>
          <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Precio Unit.</th>
          <th class="px-6 py-4 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Subtotal</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-dark-700/50">
        <?php foreach ($productos as $prod): ?>
          <?php 
          $isCancelado = $prod['cancelado'] == 1;
          $rowClass = $isCancelado ? 'hover:bg-red-900/10 transition-colors duration-200 opacity-75' : 'hover:bg-dark-700/30 transition-colors duration-200';
          $textClass = $isCancelado ? 'text-red-300 line-through' : 'text-white';
          ?>
          <tr class="<?= $rowClass ?>">
            <td class="px-6 py-4">
              <div class="text-sm font-medium <?= $textClass ?> flex items-center">
                <?= htmlspecialchars($prod['nombre']) ?>
                <?php if ($isCancelado): ?>
                  <span class="ml-2 text-xs bg-red-600 text-white px-2 py-1 rounded-full">CANCELADO</span>
                <?php endif; ?>
              </div>
            </td>
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center justify-center w-8 h-8 <?= $isCancelado ? 'bg-red-500/20 text-red-400' : 'bg-blue-500/20 text-blue-400' ?> rounded-full text-sm font-semibold">
                <?= $prod['cantidad'] ?>
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <?php if ($prod['preparado']): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                  <i class="bi bi-check-circle mr-1"></i>
                  Sí
                </span>
              <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 text-gray-400">
                  <i class="bi bi-clock mr-1"></i>
                  No
                </span>
              <?php endif; ?>
            </td>
            <td class="px-6 py-4 text-center">
              <?php if ($prod['cancelado']): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/20 text-red-400">
                  <i class="bi bi-x-circle mr-1"></i>
                  Sí
                </span>
              <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                  <i class="bi bi-check-circle mr-1"></i>
                  No
                </span>
              <?php endif; ?>
            </td>
            <td class="px-6 py-4 text-right text-sm font-medium <?= $isCancelado ? 'text-red-400' : 'text-gray-300' ?>">
              $<?= number_format($prod['precio'], 2) ?>
            </td>
            <td class="px-6 py-4 text-right text-sm font-bold <?= $isCancelado ? 'text-red-400 line-through' : 'text-white' ?>">
              $<?= number_format($prod['precio'] * $prod['cantidad'], 2) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Order Summary -->
<div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl p-6">
  <h2 class="text-xl font-montserrat-semibold text-white mb-6 flex items-center">
    <i class="bi bi-calculator mr-2 text-green-400"></i>
    Resumen de la Orden
  </h2>
  
  <div class="space-y-4">
    <div class="flex justify-between items-center py-2">
      <span class="text-gray-400">Subtotal (Productos Activos):</span>
      <span class="text-lg font-semibold text-white">$<?= number_format($subtotal, 2) ?></span>
    </div>
    
    <?php if ($total_cancelado > 0): ?>
    <div class="flex justify-between items-center py-2">
      <span class="text-red-400">Total Cancelado:</span>
      <span class="text-lg font-semibold text-red-400 line-through">-$<?= number_format($total_cancelado, 2) ?></span>
    </div>
    <?php endif; ?>
    
    <div class="flex justify-between items-center py-2">
      <span class="text-gray-400">Descuento:</span>
      <span class="text-lg font-semibold text-white">$<?= number_format($descuento, 2) ?></span>
    </div>
    
    <div class="flex justify-between items-center py-2">
      <span class="text-gray-400">Impuestos:</span>
      <span class="text-lg font-semibold text-white">$<?= number_format($impuestos, 2) ?></span>
    </div>
    
    <?php if ($productos_cancelados > 0): ?>
    <div class="bg-red-900/20 border border-red-600/30 rounded-lg p-3 mb-4">
      <div class="flex items-center justify-between text-sm">
        <span class="text-red-400 flex items-center">
          <i class="bi bi-exclamation-triangle mr-2"></i>
          Productos Cancelados:
        </span>
        <span class="text-red-400 font-semibold"><?= $productos_cancelados ?> unidad(es)</span>
      </div>
    </div>
    <?php endif; ?>
    
    <div class="border-t border-dark-700/50 pt-4">
      <div class="flex justify-between items-center">
        <span class="text-xl font-bold text-white">Total:</span>
        <span class="text-2xl font-bold bg-gradient-to-r from-green-400 to-emerald-400 bg-clip-text text-transparent">
          $<?= number_format($total, 2) ?>
        </span>
      </div>
      <div class="text-xs text-gray-500 mt-1 text-right">
        (<?= $productos_activos ?> producto(s) activo(s)<?= $productos_cancelados > 0 ? ', ' . $productos_cancelados . ' cancelado(s)' : '' ?>)
      </div>
    </div>
  </div>
</div>