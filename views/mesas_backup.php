<?php
// Nota: auth-check.php y conexion.php ya están incluidos en index.php
// $pdo ya está disponible desde index.php
$mesas = $pdo->query("
    SELECT m.*, 
      (SELECT COUNT(*) FROM ordenes o WHERE o.mesa_id = m.id AND o.estado = 'abierta') as orden_abierta
    FROM mesas m
    ORDER BY m.nombre
")->fetchAll(PDO::FETCH_ASSOC);

// Verificar mensajes de la URL
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'orden_cerrada':
            $total = $_GET['total'] ?? '0.00';
            $success_message = "Orden cerrada exitosamente. Total: $" . htmlspecialchars($total);
            break;
        default:
            $success_message = "Operación completada exitosamente";
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'orden_no_especificada':
            $error_message = "No se especificó la orden a cerrar";
            break;
        case 'mesa_invalida':
            $error_message = "ID de mesa inválido";
            break;
        case 'mesa_no_encontrada':
            $error_message = "La mesa especificada no existe";
            break;
        default:
            $error_message = htmlspecialchars($_GET['error']);
    }
}
?>

<!-- SweetAlert2 para mensajes -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($success_message): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: '<?= addslashes($success_message) ?>',
        timer: 3000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end',
        background: '#1f2937',
        color: '#ffffff',
        iconColor: '#10b981'
    });
});
</script>
<?php endif; ?>

<?php if ($error_message): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= addslashes($error_message) ?>',
        confirmButtonColor: '#ef4444',
        background: '#1f2937',
        color: '#ffffff'
    });
});
</script>
<?php endif; ?>

<!-- Hero Section -->
<div class="text-center mb-12">
  <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-3xl mb-6 shadow-2xl">
    <i class="bi bi-grid-3x3 text-white text-3xl"></i>
  </div>
  <h1 class="text-4xl md:text-5xl font-montserrat-bold font-display gradient-text mb-4">
    Gestión de Mesas
  </h1>
  <p class="text-xl text-gray-400 max-w-2xl mx-auto">
    Administra y controla todas las mesas del restaurante desde un solo lugar
  </p>
</div>

<!-- Statistics Section -->
<div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
  <?php
  $totalMesas = count($mesas);
  $mesasOcupadas = array_sum(array_column($mesas, 'orden_abierta'));
  $mesasLibres = $totalMesas - $mesasOcupadas;
  ?>

  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 text-center">
    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
      <i class="bi bi-grid-3x3 text-white text-xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-white"><?= $totalMesas ?></h3>
    <p class="text-gray-400">Total de Mesas</p>
  </div>

  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 text-center">
    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
      <i class="bi bi-exclamation-triangle text-white text-xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-white"><?= $mesasOcupadas ?></h3>
    <p class="text-gray-400">Mesas Ocupadas</p>
  </div>

  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 text-center">
    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
      <i class="bi bi-check-circle text-white text-xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-white"><?= $mesasLibres ?></h3>
    <p class="text-gray-400">Mesas Disponibles</p>
  </div>
</div>

<br>

<!-- Add New Table Form -->
<div class="mb-12">
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-8 shadow-xl">
    <div class="flex items-center mb-6">
      <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-4">
        <i class="bi bi-plus-lg text-white"></i>
      </div>
      <h3 class="text-xl font-semibold text-white">Crear Nueva Mesa</h3>
    </div>

    <form method="post" action="controllers/crear_mesa.php" class="flex flex-col sm:flex-row gap-4">
      <div class="flex-1">
        <input type="text"
          name="nombre"
          class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
          placeholder="Nombre de la nueva mesa (ej: Mesa 1, Terraza A)"
          required>
      </div>
      <button type="submit"
        class="px-8 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
        <i class="bi bi-plus-circle mr-2"></i>
        Agregar Mesa
      </button>
    </form>
  </div>
</div>

<!-- Tables Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
  <?php foreach ($mesas as $mesa):
    if ($mesa['orden_abierta'] > 0) {
      $estado = 'ocupada';
      $statusColor = 'from-red-500 to-pink-600';
      $borderColor = 'border-red-500/30';
      $bgColor = 'bg-red-500/5';
      $iconColor = 'text-red-400';
      $statusText = 'Ocupada';
      $btnText = 'Ver POS';
      $btnColor = 'from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700';
    } else {
      $estado = 'libre';
      $statusColor = 'from-green-500 to-emerald-600';
      $borderColor = 'border-green-500/30';
      $bgColor = 'bg-green-500/5';
      $iconColor = 'text-green-400';
      $statusText = 'Disponible';
      $btnText = 'Abrir POS';
      $btnColor = 'from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700';
    }
  ?>
    <div class="group card-hover cursor-pointer">
      <div class="bg-dark-700/40 backdrop-blur-xl rounded-2xl border <?= $borderColor ?> p-6 h-full flex flex-col justify-between shadow-xl <?= $bgColor ?>"
        onclick="window.location='index.php?page=mesa&id=<?= $mesa['id'] ?>'">

        <!-- Mesa Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-gradient-to-br <?= $statusColor ?> rounded-xl flex items-center justify-center shadow-lg">
              <i class="bi bi-table text-white text-xl"></i>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-white group-hover:text-blue-400 transition-colors">
                <?= htmlspecialchars($mesa['nombre']) ?>
              </h3>
              <p class="text-sm text-gray-400">Mesa</p>
            </div>
          </div>

          <!-- Status Badge -->
          <div class="flex flex-col items-end">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gradient-to-r <?= $statusColor ?> text-white shadow-lg">
              <div class="w-2 h-2 bg-white rounded-full mr-2 animate-pulse"></div>
              <?= $statusText ?>
            </span>
          </div>
        </div>

        <!-- Mesa Description -->
        <?php if (!empty($mesa['descripcion'])): ?>
          <div class="mb-4">
            <p class="text-gray-300 text-sm">
              <i class="bi bi-info-circle <?= $iconColor ?> mr-2"></i>
              <?= htmlspecialchars($mesa['descripcion']) ?>
            </p>
          </div>
        <?php endif; ?>

        <!-- Mesa Stats -->
        <div class="mb-6">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-400">Estado:</span>
            <span class="<?= $iconColor ?> font-medium"><?= $statusText ?></span>
          </div>
          <?php if ($mesa['orden_abierta'] > 0): ?>
            <div class="flex items-center justify-between text-sm mt-1">
              <span class="text-gray-400">Órdenes activas:</span>
              <span class="text-red-400 font-bold"><?= $mesa['orden_abierta'] ?></span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Action Button -->
        <div class="mt-auto">
          <a href="index.php?page=mesa&id=<?= $mesa['id'] ?>"
            class="block w-full text-center px-4 py-3 bg-gradient-to-r <?= $btnColor ?> text-white font-semibold rounded-xl transition-all duration-200 transform group-hover:scale-105 shadow-lg hover:shadow-xl">
            <i class="bi bi-arrow-right-circle mr-2"></i>
            <?= $btnText ?>
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Add New Table Card -->
  <div class="group card-hover cursor-pointer" onclick="document.querySelector('input[name=nombre]').focus()">
    <div class="bg-dark-700/20 backdrop-blur-xl rounded-2xl border-2 border-dashed border-dark-600/50 p-6 h-full flex flex-col items-center justify-center text-center hover:border-blue-500/50 transition-all duration-200">
      <div class="w-16 h-16 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
        <i class="bi bi-plus-lg text-blue-400 text-2xl"></i>
      </div>
      <p class="text-gray-400 text-sm">Crea una nueva mesa para el restaurante</p>
    </div>
  </div>
</div>

<!-- Vista Previa Interactiva: Layout del Restaurante -->
<div class="mt-16 mb-12">
  <div class="text-center mb-8">
    <h2 class="text-2xl font-bold text-white mb-2 flex items-center justify-center">
      <i class="bi bi-grid-3x3-gap mr-3 text-blue-400"></i>
      Vista Previa: Layout Interactivo del Restaurante
    </h2>
    <p class="text-gray-400">Arrastra y acomoda las mesas como si fuera tu restaurante</p>
    <div class="flex justify-center gap-4 mt-4">
      <button onclick="resetLayout()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors text-sm">
        <i class="bi bi-arrow-clockwise mr-2"></i>Reset Layout
      </button>
      <button onclick="saveLayout()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm">
        <i class="bi bi-save mr-2"></i>Guardar Layout
      </button>
      <button onclick="toggleGridLines()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors text-sm">
        <i class="bi bi-grid mr-2"></i>Toggle Grid
      </button>
    </div>
  </div>

  <!-- Restaurant Floor Container -->
  <div class="bg-dark-700/20 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-8 relative overflow-hidden">
    
    <!-- Grid Background (toggleable) -->
    <div id="gridBackground" class="absolute inset-0 opacity-20 pointer-events-none" style="
      background-image: 
        linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 40px 40px;
      display: none;
    "></div>

    <!-- Restaurant Elements Container -->
    <div id="restaurantFloor" class="relative min-h-96 border-2 border-dashed border-gray-600/30 rounded-xl p-4">
      
      <!-- Resizable Static Elements -->
      <!-- Kitchen Area -->
      <div id="kitchen" class="resizable-element absolute bg-gradient-to-br from-red-600/40 to-red-800/60 rounded-lg border border-red-500/50 flex items-center justify-center cursor-move" 
           style="top: 16px; left: 16px; width: 128px; height: 80px;" data-element-type="kitchen">
        <div class="text-center">
          <i class="bi bi-fire text-red-400 text-2xl mb-1"></i>
          <div class="text-red-200 text-xs font-semibold">COCINA</div>
        </div>
        <!-- Resize handles -->
        <div class="resize-handle resize-se"></div>
        <div class="resize-handle resize-sw"></div>
        <div class="resize-handle resize-ne"></div>
        <div class="resize-handle resize-nw"></div>
      </div>

      <!-- Bar Area -->
      <div id="bar" class="resizable-element absolute bg-gradient-to-b from-amber-600/40 to-amber-800/60 rounded-lg border border-amber-500/50 flex items-center justify-center cursor-move" 
           style="top: 16px; right: 16px; width: 96px; height: 192px;" data-element-type="bar">
        <div class="text-center transform -rotate-90">
          <i class="bi bi-cup-straw text-amber-400 text-xl mb-1"></i>
          <div class="text-amber-200 text-xs font-semibold">BAR</div>
        </div>
        <!-- Resize handles -->
        <div class="resize-handle resize-se"></div>
        <div class="resize-handle resize-sw"></div>
        <div class="resize-handle resize-ne"></div>
        <div class="resize-handle resize-nw"></div>
      </div>

      <!-- Bathroom Area -->
      <div id="bathroom" class="resizable-element absolute bg-gradient-to-br from-blue-600/40 to-blue-800/60 rounded-lg border border-blue-500/50 flex items-center justify-center cursor-move" 
           style="top: 220px; left: 16px; width: 80px; height: 60px;" data-element-type="bathroom">
        <div class="text-center">
          <i class="bi bi-house text-blue-400 text-lg mb-1"></i>
          <div class="text-blue-200 text-xs font-semibold">BAÑO</div>
        </div>
        <!-- Resize handles -->
        <div class="resize-handle resize-se"></div>
        <div class="resize-handle resize-sw"></div>
        <div class="resize-handle resize-ne"></div>
        <div class="resize-handle resize-nw"></div>
      </div>

      <!-- Entrance -->
      <div id="entrance" class="resizable-element absolute bg-gradient-to-t from-green-600/40 to-green-800/60 rounded-t-lg border border-green-500/50 flex items-center justify-center cursor-move" 
           style="bottom: 16px; left: 50%; transform: translateX(-50%); width: 80px; height: 32px;" data-element-type="entrance">
        <div class="text-center">
          <i class="bi bi-door-open text-green-400 text-sm"></i>
          <div class="text-green-200 text-xs font-semibold">ENTRADA</div>
        </div>
        <!-- Resize handles -->
        <div class="resize-handle resize-se"></div>
        <div class="resize-handle resize-sw"></div>
        <div class="resize-handle resize-ne"></div>
        <div class="resize-handle resize-nw"></div>
      </div>

      <!-- Draggable Tables -->
      
      <!-- Square Tables -->
      <div class="draggable-table absolute" data-table-type="square" style="top: 120px; left: 200px;" data-table-id="T-01">
        <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg border-4 border-purple-300 flex items-center justify-center shadow-lg cursor-move group hover:scale-110 transition-transform">
          <span class="text-white font-bold text-xs">T-01</span>
          <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-3 h-4 bg-amber-600 rounded-t group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-3 h-4 bg-amber-600 rounded-b group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 -left-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 -right-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
        </div>
      </div>

      <div class="draggable-table absolute" data-table-type="square" style="top: 120px; left: 350px;" data-table-id="T-02">
        <div class="w-14 h-14 bg-gradient-to-br from-gray-400 to-gray-600 rounded-lg border-4 border-gray-300 flex items-center justify-center shadow-lg cursor-move group hover:scale-110 transition-transform">
          <span class="text-white font-bold text-xs">T-02</span>
          <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-3 h-4 bg-amber-600 rounded-t group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-3 h-4 bg-amber-600 rounded-b group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 -left-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 -right-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
        </div>
      </div>

      <div class="draggable-table absolute" data-table-type="square" style="top: 220px; left: 275px;" data-table-id="T-03">
        <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg border-4 border-blue-300 flex items-center justify-center shadow-lg cursor-move group hover:scale-110 transition-transform">
          <span class="text-white font-bold text-xs">T-03</span>
          <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-3 h-4 bg-amber-600 rounded-t group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-3 h-4 bg-amber-600 rounded-b group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 -left-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 -right-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
        </div>
      </div>

      <!-- Rectangular Tables -->
      <div class="draggable-table absolute" data-table-type="rectangular" style="top: 160px; left: 450px;" data-table-id="R-01">
        <div class="w-20 h-10 bg-gradient-to-br from-red-400 to-red-600 rounded-lg border-4 border-red-300 flex items-center justify-center shadow-lg cursor-move group hover:scale-110 transition-transform">
          <span class="text-white font-bold text-xs">R-01</span>
          <div class="absolute top-1/2 -left-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 -right-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 left-4 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 right-4 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
        </div>
      </div>

      <div class="draggable-table absolute" data-table-type="rectangular" style="top: 300px; left: 400px;" data-table-id="R-02">
        <div class="w-20 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-lg border-4 border-green-300 flex items-center justify-center shadow-lg cursor-move group hover:scale-110 transition-transform">
          <span class="text-white font-bold text-xs">R-02</span>
          <div class="absolute top-1/2 -left-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 -right-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 left-4 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
          <div class="absolute top-1/2 right-4 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
        </div>
      </div>

    </div>

    <!-- Table Palette -->
    <div class="mt-6 p-4 bg-dark-800/40 rounded-xl">
      <h4 class="text-white font-semibold mb-3 flex items-center">
        <i class="bi bi-palette mr-2 text-blue-400"></i>
        Paleta de Mesas - Arrastra para añadir más
      </h4>
      <div class="flex flex-wrap gap-3">
        
        <!-- Square Table Template -->
        <div class="new-table-template cursor-grab active:cursor-grabbing" data-type="square">
          <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg border-2 border-purple-300 flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
            <i class="bi bi-square text-white text-xs"></i>
          </div>
          <span class="block text-xs text-gray-400 mt-1 text-center">Cuadrada</span>
        </div>

        <!-- Rectangular Table Template -->
        <div class="new-table-template cursor-grab active:cursor-grabbing" data-type="rectangular">
          <div class="w-16 h-8 bg-gradient-to-br from-red-400 to-red-600 rounded-lg border-2 border-red-300 flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
            <i class="bi bi-rectangle text-white text-xs"></i>
          </div>
          <span class="block text-xs text-gray-400 mt-1 text-center">Rectangular</span>
        </div>

      </div>
    </div>

    <!-- Layout Info -->
    <div class="mt-4 p-3 bg-blue-900/20 rounded-lg border border-blue-500/30">
      <div class="flex items-center text-blue-200 text-sm">
        <i class="bi bi-info-circle mr-2"></i>
        <span><strong>Instrucciones:</strong> Arrastra las mesas para reorganizar el layout. Usa la paleta inferior para añadir nuevas mesas. Haz doble click en una mesa para eliminarla.</span>
      </div>
    </div>

  </div>
</div>

<!-- JavaScript para Drag & Drop -->
<style>
.resizable-element {
  position: relative;
  box-sizing: border-box;
}

.resize-handle {
  position: absolute;
  width: 10px;
  height: 10px;
  background: #3b82f6;
  border: 2px solid #ffffff;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.2s, transform 0.2s;
  z-index: 20;
  pointer-events: all;
}

.resizable-element:hover .resize-handle,
.resizable-element:focus .resize-handle {
  opacity: 1;
}

.resize-se { 
  bottom: -6px; 
  right: -6px; 
  cursor: se-resize; 
}

.resize-sw { 
  bottom: -6px; 
  left: -6px; 
  cursor: sw-resize; 
}

.resize-ne { 
  top: -6px; 
  right: -6px; 
  cursor: ne-resize; 
}

.resize-nw { 
  top: -6px; 
  left: -6px; 
  cursor: nw-resize; 
}

.resize-handle:hover {
  background: #1d4ed8;
  transform: scale(1.2);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
}

.resize-handle:active {
  background: #1e40af;
  transform: scale(1.1);
}

/* Ensure proper layering */
.resizable-element {
  z-index: 1;
}

.resizable-element.resizing {
  z-index: 999;
}

/* Visual feedback for dragging and collisions */
.draggable-table.dragging,
.resizable-element.dragging {
    opacity: 0.8;
    z-index: 1000;
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.collision-warning {
    border: 2px solid #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.1) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let draggedElement = null;
    let tableCounter = { square: 3, rectangular: 2 };
    let isDragging = false;
    let isResizing = false;
    let dragOffset = { x: 0, y: 0 };
    let resizeData = null;

    const restaurantFloor = document.getElementById('restaurantFloor');

    // Load saved layout on page load
    loadLayout();

    // Function to make an element draggable
    function makeDraggable(element) {
        // Remove existing listeners to prevent duplicates
        element.removeEventListener('mousedown', startDrag);
        element.removeEventListener('touchstart', startDrag);
        
        element.addEventListener('mousedown', startDrag);
        element.addEventListener('touchstart', startDrag, { passive: false });
        
        // Double click to delete (only for tables)
        if (element.classList.contains('draggable-table')) {
            element.removeEventListener('dblclick', deleteTable);
            element.addEventListener('dblclick', deleteTable);
        }
    }

    function deleteTable(e) {
        e.preventDefault();
        e.stopPropagation();
        this.remove();
        saveLayout();
    }

    // Function to make elements resizable
    function makeResizable(element) {
        const handles = element.querySelectorAll('.resize-handle');
        handles.forEach(handle => {
            // Remove existing listeners
            handle.removeEventListener('mousedown', startResize);
            handle.removeEventListener('touchstart', startResize);
            
            handle.addEventListener('mousedown', startResize);
            handle.addEventListener('touchstart', startResize, { passive: false });
        });
    }

    // Initialize existing elements
    document.querySelectorAll('.draggable-table').forEach(makeDraggable);
    document.querySelectorAll('.resizable-element').forEach(element => {
        makeDraggable(element);
        makeResizable(element);
    });

    // Template drag functionality
    document.querySelectorAll('.new-table-template').forEach(template => {
        template.removeEventListener('mousedown', startTemplateDrag);
        template.removeEventListener('touchstart', startTemplateDrag);
        
        template.addEventListener('mousedown', startTemplateDrag);
        template.addEventListener('touchstart', startTemplateDrag, { passive: false });
    });

    function startResize(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (isDragging) return; // Prevent conflicts
        
        isResizing = true;
        const element = e.target.closest('.resizable-element');
        const handle = e.target;
        const floorRect = restaurantFloor.getBoundingClientRect();
        
        // Get current position and size from actual element
        const elementRect = element.getBoundingClientRect();
        const currentWidth = element.offsetWidth;
        const currentHeight = element.offsetHeight;
        const currentLeft = element.offsetLeft;
        const currentTop = element.offsetTop;
        
        resizeData = {
            element: element,
            handleType: getHandleType(handle),
            startX: e.clientX || e.touches[0].clientX,
            startY: e.clientY || e.touches[0].clientY,
            startWidth: currentWidth,
            startHeight: currentHeight,
            startLeft: currentLeft,
            startTop: currentTop,
            floorWidth: restaurantFloor.clientWidth,
            floorHeight: restaurantFloor.clientHeight
        };

        // Add visual feedback
        element.style.outline = '2px solid #3b82f6';
        element.style.opacity = '0.8';
        element.classList.add('resizing');

        document.addEventListener('mousemove', resize);
        document.addEventListener('mouseup', stopResize);
        document.addEventListener('touchmove', resize, { passive: false });
        document.addEventListener('touchend', stopResize);
    }

    function getHandleType(handle) {
        if (handle.classList.contains('resize-se')) return 'se';
        if (handle.classList.contains('resize-sw')) return 'sw';
        if (handle.classList.contains('resize-ne')) return 'ne';
        if (handle.classList.contains('resize-nw')) return 'nw';
        return 'se'; // default
    }

    // Collision detection function
    function checkCollisions(element, newLeft, newTop, newWidth, newHeight) {
        const currentElement = element;
        const allElements = document.querySelectorAll('.resizable-element, .draggable-table');
        
        for (let otherElement of allElements) {
            if (otherElement === currentElement) continue;
            
            const otherRect = {
                left: otherElement.offsetLeft,
                top: otherElement.offsetTop,
                width: otherElement.offsetWidth,
                height: otherElement.offsetHeight
            };
            
            // Check if rectangles overlap
            if (!(newLeft + newWidth <= otherRect.left || 
                  newLeft >= otherRect.left + otherRect.width ||
                  newTop + newHeight <= otherRect.top ||
                  newTop >= otherRect.top + otherRect.height)) {
                return true; // Collision detected
            }
        }
        return false; // No collision
    }

    // Add visual feedback for collisions
    function updateCollisionVisual(element, hasCollision) {
        if (hasCollision) {
            element.classList.add('collision-warning');
        } else {
            element.classList.remove('collision-warning');
        }
    }

    // Find safe position for element
    function findSafePosition(element, preferredLeft, preferredTop, width, height) {
        let safeLeft = preferredLeft;
        let safeTop = preferredTop;
        
        // Try the preferred position first
        if (!checkCollisions(element, safeLeft, safeTop, width, height)) {
            return { left: safeLeft, top: safeTop };
        }
        
        // Try moving right
        for (let offset = 10; offset <= 100; offset += 10) {
            safeLeft = preferredLeft + offset;
            if (safeLeft + width <= restaurantFloor.clientWidth && 
                !checkCollisions(element, safeLeft, safeTop, width, height)) {
                return { left: safeLeft, top: safeTop };
            }
        }
        
        // Try moving down
        safeLeft = preferredLeft;
        for (let offset = 10; offset <= 100; offset += 10) {
            safeTop = preferredTop + offset;
            if (safeTop + height <= restaurantFloor.clientHeight && 
                !checkCollisions(element, safeLeft, safeTop, width, height)) {
                return { left: safeLeft, top: safeTop };
            }
        }
        
        // Try moving left
        for (let offset = 10; offset <= 100; offset += 10) {
            safeLeft = preferredLeft - offset;
            if (safeLeft >= 0 && 
                !checkCollisions(element, safeLeft, safeTop, width, height)) {
                return { left: safeLeft, top: safeTop };
            }
        }
        
        // Try moving up
        safeLeft = preferredLeft;
        for (let offset = 10; offset <= 100; offset += 10) {
            safeTop = preferredTop - offset;
            if (safeTop >= 0 && 
                !checkCollisions(element, safeLeft, safeTop, width, height)) {
                return { left: safeLeft, top: safeTop };
            }
        }
        
        // If no safe position found, return constrained position
        return { 
            left: Math.max(0, Math.min(preferredLeft, restaurantFloor.clientWidth - width)), 
            top: Math.max(0, Math.min(preferredTop, restaurantFloor.clientHeight - height)) 
        };
    }

    function resize(e) {
        if (!isResizing || !resizeData) return;
        e.preventDefault();

        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;
        
        const deltaX = clientX - resizeData.startX;
        const deltaY = clientY - resizeData.startY;
        
        let newWidth = resizeData.startWidth;
        let newHeight = resizeData.startHeight;
        let newLeft = resizeData.startLeft;
        let newTop = resizeData.startTop;

        // Calculate new dimensions based on handle type
        switch(resizeData.handleType) {
            case 'se': // Southeast - bottom right
                newWidth = Math.max(60, resizeData.startWidth + deltaX);
                newHeight = Math.max(40, resizeData.startHeight + deltaY);
                break;
            case 'sw': // Southwest - bottom left
                newWidth = Math.max(60, resizeData.startWidth - deltaX);
                newHeight = Math.max(40, resizeData.startHeight + deltaY);
                if (newWidth >= 60) {
                    newLeft = resizeData.startLeft + (resizeData.startWidth - newWidth);
                }
                break;
            case 'ne': // Northeast - top right
                newWidth = Math.max(60, resizeData.startWidth + deltaX);
                newHeight = Math.max(40, resizeData.startHeight - deltaY);
                if (newHeight >= 40) {
                    newTop = resizeData.startTop + (resizeData.startHeight - newHeight);
                }
                break;
            case 'nw': // Northwest - top left
                newWidth = Math.max(60, resizeData.startWidth - deltaX);
                newHeight = Math.max(40, resizeData.startHeight - deltaY);
                if (newWidth >= 60) {
                    newLeft = resizeData.startLeft + (resizeData.startWidth - newWidth);
                }
                if (newHeight >= 40) {
                    newTop = resizeData.startTop + (resizeData.startHeight - newHeight);
                }
                break;
        }

        // Apply strict boundary constraints
        newLeft = Math.max(0, newLeft);
        newTop = Math.max(0, newTop);
        
        // Ensure element doesn't exceed floor boundaries
        if (newLeft + newWidth > resizeData.floorWidth) {
            newWidth = resizeData.floorWidth - newLeft;
        }
        if (newTop + newHeight > resizeData.floorHeight) {
            newHeight = resizeData.floorHeight - newTop;
        }

        // Final validation
        newWidth = Math.max(60, newWidth);
        newHeight = Math.max(40, newHeight);

        // Check for collisions and adjust if necessary
        if (checkCollisions(resizeData.element, newLeft, newTop, newWidth, newHeight)) {
            // If collision detected, try to find alternative sizes
            const maxSafeWidth = resizeData.floorWidth - newLeft;
            const maxSafeHeight = resizeData.floorHeight - newTop;
            
            // For SE and NE handles, try reducing width first
            if (resizeData.handleType === 'se' || resizeData.handleType === 'ne') {
                for (let testWidth = newWidth - 10; testWidth >= 60; testWidth -= 10) {
                    if (!checkCollisions(resizeData.element, newLeft, newTop, testWidth, newHeight)) {
                        newWidth = testWidth;
                        break;
                    }
                }
                // If still colliding, try reducing height
                if (checkCollisions(resizeData.element, newLeft, newTop, newWidth, newHeight)) {
                    for (let testHeight = newHeight - 10; testHeight >= 40; testHeight -= 10) {
                        if (!checkCollisions(resizeData.element, newLeft, newTop, newWidth, testHeight)) {
                            newHeight = testHeight;
                            break;
                        }
                    }
                }
            }
            
            // For SW and NW handles, adjust position and size
            if (resizeData.handleType === 'sw' || resizeData.handleType === 'nw') {
                const safePos = findSafePosition(resizeData.element, newLeft, newTop, newWidth, newHeight);
                newLeft = safePos.left;
                newTop = safePos.top;
            }
        }

        // Apply changes
        resizeData.element.style.width = newWidth + 'px';
        resizeData.element.style.height = newHeight + 'px';
        resizeData.element.style.left = newLeft + 'px';
        resizeData.element.style.top = newTop + 'px';
    }

    function stopResize() {
        if (resizeData && resizeData.element) {
            // Remove visual feedback
            resizeData.element.style.outline = '';
            resizeData.element.style.opacity = '';
            resizeData.element.classList.remove('resizing');
        }
        
        isResizing = false;
        resizeData = null;
        saveLayout();

        document.removeEventListener('mousemove', resize);
        document.removeEventListener('mouseup', stopResize);
        document.removeEventListener('touchmove', resize);
        document.removeEventListener('touchend', stopResize);
    }

    function startDrag(e) {
        if (isResizing || e.target.classList.contains('resize-handle')) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        draggedElement = e.currentTarget;
        isDragging = true;

        const rect = draggedElement.getBoundingClientRect();
        const floorRect = restaurantFloor.getBoundingClientRect();
        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;

        // Calculate offset relative to the element's position within the floor
        dragOffset.x = clientX - rect.left;
        dragOffset.y = clientY - rect.top;

        // Visual feedback
        draggedElement.style.zIndex = '1000';
        draggedElement.style.transform = 'scale(1.02)';
        draggedElement.style.opacity = '0.9';
        draggedElement.classList.add('dragging');

        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchmove', drag, { passive: false });
        document.addEventListener('touchend', stopDrag);
    }

    function startTemplateDrag(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const template = e.currentTarget;
        const type = template.dataset.type;
        
        // Create new table from template
        const newTable = createNewTable(type);
        restaurantFloor.appendChild(newTable);
        
        draggedElement = newTable;
        isDragging = true;

        const rect = template.getBoundingClientRect();
        const floorRect = restaurantFloor.getBoundingClientRect();
        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;

        // Center the new table on cursor
        dragOffset.x = newTable.offsetWidth / 2;
        dragOffset.y = newTable.offsetHeight / 2;

        const x = Math.max(0, clientX - floorRect.left - dragOffset.x);
        const y = Math.max(0, clientY - floorRect.top - dragOffset.y);

        newTable.style.left = x + 'px';
        newTable.style.top = y + 'px';
        newTable.style.zIndex = '1000';
        newTable.style.transform = 'scale(1.02)';
        newTable.style.opacity = '0.9';

        makeDraggable(newTable);

        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchmove', drag, { passive: false });
        document.addEventListener('touchend', stopDrag);
    }

    function drag(e) {
        if (!isDragging || !draggedElement || isResizing) return;
        e.preventDefault();

        const floorRect = restaurantFloor.getBoundingClientRect();
        const clientX = e.clientX || e.touches[0].clientX;
        const clientY = e.clientY || e.touches[0].clientY;

        // Calculate new position relative to floor
        let x = clientX - floorRect.left - dragOffset.x;
        let y = clientY - floorRect.top - dragOffset.y;

        // Get element actual dimensions (without transform scaling)
        const elementWidth = draggedElement.offsetWidth;
        const elementHeight = draggedElement.offsetHeight;

        // Boundary constraints
        const maxX = restaurantFloor.clientWidth - elementWidth;
        const maxY = restaurantFloor.clientHeight - elementHeight;

        x = Math.max(0, Math.min(x, maxX));
        y = Math.max(0, Math.min(y, maxY));

        // Check for collisions before applying position
        const hasCollision = checkCollisions(draggedElement, x, y, elementWidth, elementHeight);
        
        if (hasCollision) {
            // If collision detected, find safe position
            const safePos = findSafePosition(draggedElement, x, y, elementWidth, elementHeight);
            x = safePos.left;
            y = safePos.top;
        }

        // Update visual feedback
        updateCollisionVisual(draggedElement, hasCollision);

        draggedElement.style.left = x + 'px';
        draggedElement.style.top = y + 'px';
    }

    function stopDrag() {
        if (draggedElement) {
            // Remove visual feedback
            draggedElement.style.zIndex = '';
            draggedElement.style.transform = '';
            draggedElement.style.opacity = '';
            draggedElement.classList.remove('dragging', 'collision-warning');
        }
        
        isDragging = false;
        draggedElement = null;
        saveLayout();

        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', stopDrag);
        document.removeEventListener('touchmove', drag);
        document.removeEventListener('touchend', stopDrag);
    }

    function createNewTable(type) {
        const table = document.createElement('div');
        table.className = 'draggable-table absolute';
        table.dataset.tableType = type;
        
        tableCounter[type]++;
        const tableId = getTableId(type, tableCounter[type]);
        table.dataset.tableId = tableId;

        const colors = {
            square: 'from-purple-400 to-purple-600 border-purple-300',
            rectangular: 'from-red-400 to-red-600 border-red-300'
        };

        const tableHTML = getTableHTML(type, tableId, colors[type]);
        table.innerHTML = tableHTML;

        return table;
    }

    function getTableId(type, counter) {
        const prefixes = {
            square: 'T',
            rectangular: 'R'
        };
        return prefixes[type] + '-' + String(counter).padStart(2, '0');
    }

    function getTableHTML(type, tableId, colorClass) {
        switch(type) {
            case 'square':
                return `
                    <div class="w-14 h-14 bg-gradient-to-br ${colorClass} rounded-lg border-4 flex items-center justify-center shadow-lg cursor-move group hover:scale-110 transition-transform">
                        <span class="text-white font-bold text-xs">${tableId}</span>
                        <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-3 h-4 bg-amber-600 rounded-t group-hover:bg-amber-500 transition-colors"></div>
                        <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-3 h-4 bg-amber-600 rounded-b group-hover:bg-amber-500 transition-colors"></div>
                        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
                        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
                    </div>
                `;
            case 'rectangular':
                return `
                    <div class="w-20 h-10 bg-gradient-to-br ${colorClass} rounded-lg border-4 flex items-center justify-center shadow-lg cursor-move group hover:scale-110 transition-transform">
                        <span class="text-white font-bold text-xs">${tableId}</span>
                        <div class="absolute top-1/2 -left-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
                        <div class="absolute top-1/2 -right-2 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
                        <div class="absolute top-1/2 left-4 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-l group-hover:bg-amber-500 transition-colors"></div>
                        <div class="absolute top-1/2 right-4 transform -translate-y-1/2 w-4 h-3 bg-amber-600 rounded-r group-hover:bg-amber-500 transition-colors"></div>
                    </div>
                `;
        }
    }

    // Save layout to localStorage
    function saveLayout() {
        const layout = {
            tables: [],
            staticElements: {},
            timestamp: Date.now()
        };

        // Save tables
        document.querySelectorAll('.draggable-table').forEach(table => {
            layout.tables.push({
                id: table.dataset.tableId,
                type: table.dataset.tableType,
                x: table.offsetLeft,
                y: table.offsetTop
            });
        });

        // Save static elements (kitchen, bar, bathroom, entrance)
        document.querySelectorAll('.resizable-element').forEach(element => {
            const type = element.dataset.elementType;
            if (type) {
                layout.staticElements[type] = {
                    x: element.offsetLeft,
                    y: element.offsetTop,
                    width: element.offsetWidth,
                    height: element.offsetHeight
                };
            }
        });

        localStorage.setItem('restaurantLayout', JSON.stringify(layout));
        console.log('Layout guardado:', layout);
    }

    // Load layout from localStorage
    function loadLayout() {
        const savedLayout = localStorage.getItem('restaurantLayout');
        if (!savedLayout) return;

        try {
            const layout = JSON.parse(savedLayout);
            
            // Clear existing tables
            document.querySelectorAll('.draggable-table').forEach(table => table.remove());
            
            // Restore tables
            if (layout.tables) {
                layout.tables.forEach(tableData => {
                    const table = createNewTable(tableData.type);
                    table.dataset.tableId = tableData.id;
                    table.style.left = Math.max(0, tableData.x) + 'px';
                    table.style.top = Math.max(0, tableData.y) + 'px';
                    
                    // Update the table content with saved ID
                    const tableElement = table.querySelector('span');
                    if (tableElement) {
                        tableElement.textContent = tableData.id;
                    }
                    
                    restaurantFloor.appendChild(table);
                    makeDraggable(table);
                });
            }

            // Restore static elements
            if (layout.staticElements) {
                Object.keys(layout.staticElements).forEach(elementType => {
                    const element = document.querySelector(`[data-element-type="${elementType}"]`);
                    if (element && layout.staticElements[elementType]) {
                        const data = layout.staticElements[elementType];
                        element.style.left = Math.max(0, data.x) + 'px';
                        element.style.top = Math.max(0, data.y) + 'px';
                        element.style.width = Math.max(60, data.width) + 'px';
                        element.style.height = Math.max(40, data.height) + 'px';
                    }
                });
            }

            console.log('Layout cargado:', layout);
        } catch (e) {
            console.error('Error cargando layout:', e);
            localStorage.removeItem('restaurantLayout');
        }
    }

    // Utility functions
    window.toggleGridLines = function() {
        const grid = document.getElementById('gridBackground');
        grid.style.display = grid.style.display === 'none' ? 'block' : 'none';
    }

    window.resetLayout = function() {
        localStorage.removeItem('restaurantLayout');
        location.reload();
    }

    window.saveLayout = function() {
        saveLayout();
        
        // Show success message
        const tablesCount = document.querySelectorAll('.draggable-table').length;
        Swal.fire({
            icon: 'success',
            title: '¡Layout Guardado!',
            text: `Se guardaron ${tablesCount} mesas y elementos en la configuración`,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            background: '#1f2937',
            color: '#ffffff',
            iconColor: '#10b981'
        });
    }

    // Prevent context menu on drag elements
    document.querySelectorAll('.draggable-table, .resizable-element, .new-table-template').forEach(element => {
        element.addEventListener('contextmenu', e => e.preventDefault());
    });
});
</script>