<?php
// Verificar si $pdo existe, si no, incluir conexión
if (!isset($pdo)) {
    require_once '../conexion.php';
    $pdo = conexion();
}

$mesas = $pdo->query("
    SELECT m.*, 
      (SELECT COUNT(*) FROM ordenes o WHERE o.mesa_id = m.id AND o.estado = 'abierta') as orden_abierta
    FROM mesas m
    ORDER BY m.nombre
")->fetchAll(PDO::FETCH_ASSOC);

// Cargar posiciones de layout si existen
$layout_positions = [];
try {
    $layout_query = $pdo->query("
        SELECT mesa_id, posicion_x, posicion_y, ancho, alto, rotacion, tipo_visual 
        FROM mesa_layouts 
        WHERE mesa_id IS NOT NULL
    ");
    while ($row = $layout_query->fetch(PDO::FETCH_ASSOC)) {
        $layout_positions[$row['mesa_id']] = [
            'posicion_x' => $row['posicion_x'],
            'posicion_y' => $row['posicion_y'],
            'ancho' => $row['ancho'],
            'alto' => $row['alto'],
            'rotacion' => $row['rotacion'],
            'tipo_visual' => $row['tipo_visual']
        ];
    }
} catch (Exception $e) {
    // Si hay error, continuamos sin layout positions
    $layout_positions = [];
}

// Verificar mensajes de la URL
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'orden_cerrada':
            $total = $_GET['total'] ?? '0.00';
            $success_message = "Orden cerrada exitosamente. Total: $" . htmlspecialchars($total);
            break;
        case 'mesa_creada':
            $mesa_nombre = $_GET['mesa_nombre'] ?? '';
            $success_message = "Mesa '" . htmlspecialchars($mesa_nombre) . "' creada exitosamente";
            break;
        case 'mesa_eliminada':
            $success_message = "Mesa eliminada exitosamente";
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
        case 'mesa_con_orden':
            $error_message = "No se puede eliminar una mesa con órdenes abiertas";
            break;
        case 'error_eliminar':
            $error_message = "Error al eliminar la mesa";
            break;
        case 'nombre_vacio':
            $error_message = "El nombre de la mesa no puede estar vacío";
            break;
        case 'error_crear':
            $error_message = "Error al crear la mesa";
            break;
        case 'datos_invalidos':
            $error_message = "Datos inválidos proporcionados";
            break;
        case 'mesa_existe':
            $error_message = "Ya existe una mesa con ese nombre";
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

    <form id="crearMesaForm" class="flex flex-col sm:flex-row gap-4">
      <div class="flex-1">
        <input type="text"
          name="nombre"
          id="nombreMesa"
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
  <div class="group card-hover cursor-pointer" onclick="document.getElementById('nombreMesa').focus()">
    <div class="bg-dark-700/20 backdrop-blur-xl rounded-2xl border-2 border-dashed border-dark-600/50 p-6 h-full flex flex-col items-center justify-center text-center hover:border-blue-500/50 transition-all duration-200">
      <div class="w-16 h-16 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
        <i class="bi bi-plus-lg text-blue-400 text-2xl"></i>
      </div>
      <h3 class="text-lg font-semibold text-white mb-2">Agregar Mesa</h3>
      <p class="text-gray-400 text-sm">Crea una nueva mesa para el restaurante</p>
    </div>
  </div>
</div>

<!-- Layout Designer Section -->
<div class="mt-12">
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-8 shadow-xl">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center">
        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl flex items-center justify-center mr-4">
          <i class="bi bi-grid-3x3-gap text-white"></i>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-white">Diseñador de Layout</h3>
          <p class="text-gray-400 text-sm">Organiza el layout visual de tu restaurante</p>
        </div>
      </div>
      <div class="flex gap-2">
        <button id="toggleGrid" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition-colors">
          <i class="bi bi-grid"></i> Grid
        </button>
        <button id="saveLayout" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition-colors">
          <i class="bi bi-save"></i> Guardar
        </button>
        <button id="resetLayout" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition-colors">
          <i class="bi bi-arrow-clockwise"></i> Reset
        </button>
      </div>
    </div>

    <!-- Restaurant Floor -->
    <div id="restaurantFloor" class="restaurant-floor bg-gray-800 border-2 border-dashed border-gray-600 rounded-xl position-relative" 
         style="height: 800px; width: 100%; overflow: visible; margin-top: 10px;">
      
      <!-- // DEBUG: Info de mesas -->
      <div style="position: absolute; top: 5px; left: 5px; background: rgba(0,0,0,0.8); color: white; padding: 5px; font-size: 12px; z-index: 10000;">
        DEBUG: <?= count($mesas) ?> mesas cargadas de la BD | <?= count($layout_positions) ?> layouts cargados
        <button onclick="debugMesas()" style="margin-left: 10px; padding: 2px 5px; background: #007acc; color: white; border: none; border-radius: 3px;">
          Debug JS
        </button>
        <button onclick="mostrarLayouts()" style="margin-left: 5px; padding: 2px 5px; background: #d97706; color: white; border: none; border-radius: 3px;">
          Ver Layouts
        </button>
        <button onclick="testConexion()" style="margin-left: 5px; padding: 2px 5px; background: #059669; color: white; border: none; border-radius: 3px;">
          Test Conexión
        </button>
      </div>
      
      <!-- === MESAS DE LA BASE DE DATOS === -->
      <?php foreach ($mesas as $index => $mesa): ?>
        <?php
        // Sistema de posicionamiento con layout guardado
        $layout = $layout_positions[$mesa['id']] ?? null;
        $mesaX = $layout ? $layout['posicion_x'] : (300 + ($index % 3) * 150);
        $mesaY = $layout ? $layout['posicion_y'] : (250 + floor($index / 3) * 120);
        $mesaWidth = $layout ? $layout['ancho'] : 120;
        $mesaHeight = $layout ? $layout['alto'] : 100;
        $mesaRotation = $layout ? $layout['rotacion'] : 0;
        
        $mesaColor = $mesa['orden_abierta'] > 0 ? '#dc2626' : '#16a34a';
        $mesaEstado = $mesa['orden_abierta'] > 0 ? 'OCUPADA' : 'LIBRE';
        $mesaBtnText = $mesa['orden_abierta'] > 0 ? 'Ver POS' : 'Abrir POS';
        $mesaClaseEstado = $mesa['orden_abierta'] > 0 ? 'mesa-ocupada' : 'mesa-libre';
        ?>
        
        <!-- Mesa ID: <?= $mesa['id'] ?> - <?= $mesa['nombre'] ?> -->
        <div class="mesa-element layout-element <?= $mesaClaseEstado ?>" 
             id="mesa-<?= $mesa['id'] ?>"
             data-mesa-id="<?= $mesa['id'] ?>"
             data-mesa-nombre="<?= htmlspecialchars($mesa['nombre']) ?>"
             data-orden-abierta="<?= $mesa['orden_abierta'] ?>"
             data-rotation="<?= $mesaRotation ?>"
             style="position: absolute;
                    left: <?= $mesaX ?>px;
                    top: <?= $mesaY ?>px;
                    width: <?= $mesaWidth ?>px;
                    height: <?= $mesaHeight ?>px;
                    background: <?= $mesaColor ?>;
                    border: 3px solid #ffffff;
                    border-radius: 12px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-family: Arial, sans-serif;
                    font-weight: bold;
                    cursor: move;
                    z-index: 500;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
                    transform: rotate(<?= $mesaRotation ?>deg);
                    transition: transform 0.2s ease, box-shadow 0.2s ease;">
          
          <!-- Botón de eliminar -->
          <button class="delete-button" title="Eliminar mesa">×</button>
          
          <!-- Botón de rotación -->
          <div class="rotate-handle" title="Rotar mesa">↻</div>
          
          <!-- Handles de redimensión -->
          <div class="resize-handle nw"></div>
          <div class="resize-handle ne"></div>
          <div class="resize-handle sw"></div>
          <div class="resize-handle se"></div>
          
          <!-- Contenido de la mesa -->
          <div class="mesa-content" style="pointer-events: none;">
            <div style="font-size: 18px; margin-bottom: 4px;">🍽️</div>
            <div style="font-size: 14px; margin-bottom: 4px;"><?= htmlspecialchars($mesa['nombre']) ?></div>
            <div style="font-size: 10px; margin-bottom: 6px; opacity: 0.9;"><?= $mesaEstado ?></div>
            
            <!-- Botón POS -->
            <button class="mesa-action-btn bg-white/20 hover:bg-white/30 text-white px-2 py-1 rounded text-xs"
                    onclick="event.stopPropagation(); abrirMesa(<?= $mesa['id'] ?>, '<?= htmlspecialchars($mesa['nombre']) ?>');"
                    style="pointer-events: auto;">
              <?= $mesaBtnText ?>
            </button>
          </div>
          
          <?php if ($mesa['orden_abierta'] > 0): ?>
          <!-- Indicador de orden activa -->
          <div style="position: absolute;
                      top: -5px;
                      right: -5px;
                      width: 20px;
                      height: 20px;
                      background: #fbbf24;
                      border: 2px solid white;
                      border-radius: 50%;
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      font-size: 10px;
                      color: #000;
                      font-weight: bold;
                      z-index: 10;">!</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Instrucciones -->
    <div class="mt-6 p-4 bg-blue-600/10 border border-blue-500/30 rounded-lg">
      <div class="flex items-center text-blue-200 text-sm">
        <i class="bi bi-info-circle mr-2"></i>
        <span><strong>Instrucciones:</strong> 
          <br>• <strong>Mover:</strong> Arrastra cualquier mesa por el área de trabajo
          <br>• <strong>Redimensionar:</strong> Arrastra los círculos azules en las esquinas
          <br>• <strong>Rotar:</strong> Haz click en el botón naranja (90° por click)
          <br>• <strong>Usar mesa:</strong> Click en el botón "Abrir POS" o "Ver POS"
          <br>• <strong>Eliminar:</strong> Haz click en el botón rojo (×) en la esquina de la mesa
        </span>
      </div>
    </div>
  </div>
</div>

<!-- CSS para el Layout Designer -->
<style>
/* Grid system */
.restaurant-floor {
  background-image: 
    linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
  background-size: 20px 20px;
  user-select: none;
  position: relative;
  border: 3px solid #374151 !important;
  box-shadow: inset 0 0 0 2px #1f2937;
}

.restaurant-floor::before {
  content: '';
  position: absolute;
  top: 5px;
  left: 5px;
  right: 5px;
  bottom: 5px;
  border: 1px dashed rgba(59, 130, 246, 0.3);
  border-radius: 8px;
  pointer-events: none;
  z-index: 1;
}

.restaurant-floor.show-grid {
  background-image: 
    linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px);
  background-size: 20px 20px;
}

.restaurant-floor.show-grid::before {
  border-color: rgba(59, 130, 246, 0.5);
}

/* Layout elements */
.layout-element {
  position: absolute;
  box-sizing: border-box;
  user-select: none;
  transition: all 0.2s ease;
}

/* Forzar visibilidad de mesas */
.mesa-element {
  display: flex !important;
  opacity: 1 !important;
  visibility: visible !important;
  pointer-events: auto !important;
}

.layout-element:hover {
  transform: scale(1.02);
  z-index: 10;
}

.layout-element.dragging {
  z-index: 1000;
  opacity: 0.8;
  transform: scale(1.05);
  box-shadow: 0 8px 25px rgba(0,0,0,0.3) !important;
}

.layout-element.collision {
  border-color: #ef4444 !important;
  background-color: rgba(239, 68, 68, 0.1) !important;
}

.layout-element.resizing {
  box-shadow: 0 0 0 2px #3b82f6, 0 8px 25px rgba(0,0,0,0.3) !important;
  z-index: 1000;
}

.layout-element.resizing .resize-handle {
  opacity: 1 !important;
  background: #1d4ed8;
}

/* Resize handles para mesas */
.mesa-element {
  position: relative;
}

/* Botón de eliminar */
.delete-button {
  position: absolute;
  top: -8px;
  left: -8px;
  width: 20px;
  height: 20px;
  background: #ef4444;
  color: white;
  border: 2px solid white;
  border-radius: 50%;
  font-size: 12px;
  font-weight: bold;
  cursor: pointer;
  opacity: 0;
  transition: all 0.2s ease;
  z-index: 30 !important;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.mesa-element:hover .delete-button {
  opacity: 1;
}

.delete-button:hover {
  background: #dc2626;
  transform: scale(1.1);
}

.resize-handle {
  position: absolute;
  width: 16px;
  height: 16px;
  background: #3b82f6;
  border: 2px solid white;
  border-radius: 50%;
  opacity: 0;
  transition: all 0.2s ease;
  z-index: 25 !important;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  pointer-events: auto !important;
  cursor: pointer;
}

.mesa-element:hover .resize-handle,
.mesa-element:hover .resize-handle {
  opacity: 1;
}

.resize-handle:hover {
  background: #1d4ed8;
  transform: scale(1.4);
  opacity: 1 !important;
  box-shadow: 0 3px 6px rgba(0,0,0,0.3);
}

.resize-handle.nw { 
  top: -8px; 
  left: -8px; 
  cursor: nw-resize !important; 
}

.resize-handle.ne { 
  top: -8px; 
  right: -8px; 
  cursor: ne-resize !important; 
}

.resize-handle.sw { 
  bottom: -8px; 
  left: -8px; 
  cursor: sw-resize !important; 
}

.resize-handle.se { 
  bottom: -8px; 
  right: -8px; 
  cursor: se-resize !important; 
}

/* Templates */
.template-element {
  user-select: none;
}

.template-element:active {
  transform: scale(0.95);
}

/* Utilities */
.select-none {
  user-select: none;
}

/* Estilos para mesas con sillas */
.table-with-chairs {
  position: relative;
  width: 100%;
  height: 100%;
}

.table-surface {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}

.chair {
  position: absolute;
  background: rgba(139, 69, 19, 0.8); /* Color marrón para las sillas */
  border: 1px solid rgba(101, 49, 15, 0.9);
  z-index: 1;
}

/* Sillas para mesa cuadrada */
.square-table .table-surface {
  width: 50px;
  height: 50px;
  background: inherit;
  border-radius: 6px;
  border: 2px solid rgba(255,255,255,0.3);
}

.square-table .chair {
  width: 12px;
  height: 16px;
  border-radius: 2px;
}

.square-table .chair.top {
  top: -18px;
  left: 50%;
  transform: translateX(-50%);
}

.square-table .chair.right {
  right: -14px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 12px;
}

.square-table .chair.bottom {
  bottom: -18px;
  left: 50%;
  transform: translateX(-50%);
}

.square-table .chair.left {
  left: -14px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 12px;
}

/* Sillas para mesa rectangular */
.rectangular-table .table-surface {
  width: 80px;
  height: 50px;
  background: inherit;
  border-radius: 6px;
  border: 2px solid rgba(255,255,255,0.3);
}

.rectangular-table .chair {
  width: 12px;
  height: 16px;
  border-radius: 2px;
}

.rectangular-table .chair.top-left {
  top: -18px;
  left: 20px;
}

.rectangular-table .chair.top-right {
  top: -18px;
  right: 20px;
}

.rectangular-table .chair.right {
  right: -14px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 12px;
}

.rectangular-table .chair.bottom-right {
  bottom: -18px;
  right: 20px;
}

.rectangular-table .chair.bottom-left {
  bottom: -18px;
  left: 20px;
}

.rectangular-table .chair.left {
  left: -14px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 12px;
}

/* Botón de rotación */
.rotate-handle {
  position: absolute;
  top: -12px;
  right: -12px;
  width: 24px;
  height: 24px;
  background: #f59e0b;
  border: 2px solid white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  transition: all 0.2s ease;
  z-index: 30;
  font-size: 10px;
  color: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.layout-element:hover .rotate-handle {
  opacity: 1;
}

.rotate-handle:hover {
  background: #d97706;
  transform: scale(1.2) rotate(90deg);
  box-shadow: 0 3px 6px rgba(0,0,0,0.3);
}

.rotate-handle:active {
  transform: scale(1.1) rotate(180deg);
}

/* Indicador de ángulo */
.layout-element::before {
  content: attr(data-rotation) "°";
  position: absolute;
  top: -25px;
  right: -5px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  opacity: 0;
  transition: opacity 0.2s ease;
  z-index: 35;
  pointer-events: none;
}

.layout-element:hover::before {
  opacity: 1;
}

/* Efectos de rotación */
.layout-element {
  transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  transform-origin: center center;
}

.layout-element.rotating {
  transform-origin: center center;
}

/* Estilos para mesas con orden activa */
.mesa-element.con-orden {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.3), 0 4px 15px rgba(239, 68, 68, 0.2) !important;
  animation: ordenPulse 2s infinite;
}

.mesa-element.con-orden::before {
  content: '';
  position: absolute;
  inset: -3px;
  background: linear-gradient(45deg, transparent, rgba(239, 68, 68, 0.3), transparent);
  border-radius: inherit;
  z-index: -1;
  animation: ordenRing 3s linear infinite;
}

@keyframes ordenPulse {
  0%, 100% {
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.3), 0 4px 15px rgba(239, 68, 68, 0.2);
  }
  50% {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.4), 0 6px 20px rgba(239, 68, 68, 0.3);
  }
}

@keyframes ordenRing {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Estilos para botones de acción integrados */
.mesa-action-btn {
  font-size: 10px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(4px);
  pointer-events: auto;
}

.mesa-action-btn:hover {
  border-color: rgba(255, 255, 255, 0.4);
}

/* Ajustes para contenido de mesa */
.mesa-content {
  pointer-events: none;
}

.mesa-content > * {
  pointer-events: none;
}

/* Handles visibles en hover */
.mesa-element:hover .resize-handle,
.mesa-element:hover .rotate-handle,
.mesa-element:hover .delete-button {
  opacity: 1 !important;
}

.resize-handle:hover {
  background: #1d4ed8 !important;
  transform: scale(1.2);
}

.rotate-handle:hover {
  background: #f97316 !important;
  transform: scale(1.2);
}

.delete-button:hover {
  background: #dc2626 !important;
  transform: scale(1.1);
}
</style>

<!-- JavaScript para el Layout Designer -->
<script>
// Configuración de rutas
const BASE_URL = window.location.origin + '/POS/';
const CONTROLLER_URL = BASE_URL + 'controllers/guardar_layout_temp.php'; // Temporal - funciona sin auth

document.addEventListener('DOMContentLoaded', function() {
    // === VARIABLES GLOBALES ===
    let isDragging = false;
    let isResizing = false;
    let currentElement = null;
    let currentHandle = null;
    let startX, startY, startLeft, startTop, startWidth, startHeight;
    let gridSize = 20;
    let showGrid = false;
    let mesasFromDB = <?= json_encode($mesas) ?>;

    const restaurantFloor = document.getElementById('restaurantFloor');

    // === INICIALIZACIÓN ===
    initializeSystem();

    function initializeSystem() {
        console.log('🚀 Inicializando sistema de mesas...');
        setupEventListeners();
        setupDragAndDrop();
        addResizeHandlesToAllMesas();
        setupContextMenus();
        setupRotationHandlers();
        setupFormHandlers();
        verificarMesas();
    }

    function verificarMesas() {
        console.log('🔍 Verificando mesas...');
        const mesas = document.querySelectorAll('[data-mesa-id]');
        console.log(`Total mesas: ${mesas.length}`);
        
        mesas.forEach((mesa, index) => {
            const rect = mesa.getBoundingClientRect();
            console.log(`Mesa ${index + 1}: ${mesa.dataset.mesaNombre} - Visible: ${rect.width > 0 && rect.height > 0}`);
        });
    }

    // === EVENT LISTENERS PRINCIPALES ===
    function setupEventListeners() {
        // Botones principales
        document.getElementById('toggleGrid').addEventListener('click', toggleGrid);
        document.getElementById('saveLayout').addEventListener('click', guardarLayoutCompleto);
        document.getElementById('resetLayout').addEventListener('click', resetLayout);
        
        // Eventos globales de mouse
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', stopDragResize);
    }

    function setupDragAndDrop() {
        const mesas = document.querySelectorAll('.mesa-element');
        mesas.forEach(mesa => {
            mesa.addEventListener('mousedown', startDrag);
        });
    }

    function setupFormHandlers() {
        const form = document.getElementById('crearMesaForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const nombreInput = document.getElementById('nombreMesa');
                const nombre = nombreInput.value.trim();
                
                if (!nombre) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Nombre requerido',
                        text: 'Por favor ingresa un nombre para la mesa',
                        background: '#1f2937',
                        color: '#ffffff'
                    });
                    return;
                }
                
                // Mostrar loading
                Swal.fire({
                    title: 'Creando mesa...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    background: '#1f2937',
                    color: '#ffffff',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Crear mesa
                fetch('controllers/crear_mesa.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `nombre=${encodeURIComponent(nombre)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        nombreInput.value = '';
                        
                        Swal.fire({
                            icon: 'success',
                            title: '¡Mesa creada!',
                            text: `Mesa "${nombre}" creada correctamente`,
                            timer: 2000,
                            showConfirmButton: false,
                            background: '#1f2937',
                            color: '#ffffff'
                        }).then(() => {
                            // Recargar la página para mostrar la nueva mesa
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Error al crear la mesa',
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor',
                        background: '#1f2937',
                        color: '#ffffff'
                    });
                });
            });
        }
    }

    function setupContextMenus() {
        const mesas = document.querySelectorAll('.mesa-element');
        mesas.forEach(mesa => {
            mesa.addEventListener('contextmenu', showContextMenu);
        });
    }

    function setupRotationHandlers() {
        const mesas = document.querySelectorAll('.mesa-element');
        mesas.forEach(mesa => {
            mesa.addEventListener('dblclick', rotateMesa);
        });
    }

    // === RESIZE HANDLES ===
    function addResizeHandlesToAllMesas() {
        const mesas = document.querySelectorAll('.mesa-element');
        mesas.forEach(mesa => {
            addResizeHandlesToElement(mesa);
        });
    }

    function addResizeHandlesToElement(element) {
        // Remover handles existentes
        element.querySelectorAll('.resize-handle').forEach(handle => handle.remove());
        
        const handles = ['nw', 'ne', 'sw', 'se'];
        handles.forEach(direction => {
            const handle = document.createElement('div');
            handle.className = `resize-handle ${direction}`;
            handle.addEventListener('mousedown', (e) => startResize(e, direction));
            element.appendChild(handle);
        });

        // Agregar botón de eliminar si no existe
        if (!element.querySelector('.delete-button')) {
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-button';
            deleteBtn.innerHTML = '×';
            deleteBtn.title = 'Eliminar mesa';
            deleteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                eliminarMesa(element);
            });
            element.appendChild(deleteBtn);
        }

        // Agregar botón de rotación si no existe
        if (!element.querySelector('.rotate-handle')) {
            const rotateBtn = document.createElement('div');
            rotateBtn.className = 'rotate-handle';
            rotateBtn.innerHTML = '↻';
            rotateBtn.title = 'Rotar mesa';
            rotateBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                rotateMesa({ currentTarget: element, preventDefault: () => {}, stopPropagation: () => {} });
            });
            element.appendChild(rotateBtn);
        }
    }

    // === DRAG & DROP ===
    function startDrag(e) {
        if (e.target.classList.contains('resize-handle') || 
            e.target.classList.contains('rotate-handle') ||
            e.target.classList.contains('delete-button') ||
            e.target.classList.contains('mesa-action-btn')) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        isDragging = true;
        currentElement = e.currentTarget;

        startX = e.clientX;
        startY = e.clientY;
        startLeft = parseInt(currentElement.style.left) || 0;
        startTop = parseInt(currentElement.style.top) || 0;

        currentElement.style.zIndex = '1000';
        currentElement.style.cursor = 'grabbing';

        console.log(`Iniciando arrastre de ${currentElement.dataset.mesaNombre}`);
    }

    function startResize(e, direction) {
        e.preventDefault();
        e.stopPropagation();

        isResizing = true;
        currentElement = e.target.parentElement;
        currentHandle = direction;

        startX = e.clientX;
        startY = e.clientY;
        startLeft = parseInt(currentElement.style.left) || 0;
        startTop = parseInt(currentElement.style.top) || 0;
        startWidth = currentElement.offsetWidth;
        startHeight = currentElement.offsetHeight;

        currentElement.style.zIndex = '1000';

        console.log(`Iniciando redimensión ${direction} de ${currentElement.dataset.mesaNombre}`);
    }

    function handleMouseMove(e) {
        if (isDragging) {
            drag(e);
        } else if (isResizing) {
            resize(e);
        }
    }

    function drag(e) {
        if (!isDragging || !currentElement) return;

        const deltaX = e.clientX - startX;
        const deltaY = e.clientY - startY;

        let newLeft = startLeft + deltaX;
        let newTop = startTop + deltaY;

        // Snap to grid si está habilitado
        if (showGrid) {
            newLeft = Math.round(newLeft / gridSize) * gridSize;
            newTop = Math.round(newTop / gridSize) * gridSize;
        }

        // Limitar al contenedor
        const container = restaurantFloor;
        const containerRect = container.getBoundingClientRect();
        const elementRect = currentElement.getBoundingClientRect();

        newLeft = Math.max(5, Math.min(newLeft, containerRect.width - elementRect.width - 5));
        newTop = Math.max(5, Math.min(newTop, containerRect.height - elementRect.height - 5));

        currentElement.style.left = newLeft + 'px';
        currentElement.style.top = newTop + 'px';
    }

    function resize(e) {
        if (!isResizing || !currentElement) return;

        const deltaX = e.clientX - startX;
        const deltaY = e.clientY - startY;

        let newWidth = startWidth;
        let newHeight = startHeight;
        let newLeft = startLeft;
        let newTop = startTop;

        switch(currentHandle) {
            case 'se':
                newWidth = startWidth + deltaX;
                newHeight = startHeight + deltaY;
                break;
            case 'sw':
                newWidth = startWidth - deltaX;
                newHeight = startHeight + deltaY;
                newLeft = startLeft + deltaX;
                break;
            case 'ne':
                newWidth = startWidth + deltaX;
                newHeight = startHeight - deltaY;
                newTop = startTop + deltaY;
                break;
            case 'nw':
                newWidth = startWidth - deltaX;
                newHeight = startHeight - deltaY;
                newLeft = startLeft + deltaX;
                newTop = startTop + deltaY;
                break;
        }

        // Límites mínimos
        newWidth = Math.max(60, newWidth);
        newHeight = Math.max(40, newHeight);

        // Aplicar cambios
        currentElement.style.width = newWidth + 'px';
        currentElement.style.height = newHeight + 'px';
        currentElement.style.left = newLeft + 'px';
        currentElement.style.top = newTop + 'px';
    }

    function stopDragResize() {
        if (isDragging) {
            console.log(`Arrastre finalizado: ${currentElement?.dataset.mesaNombre}`);
            isDragging = false;
            
            if (currentElement) {
                currentElement.style.zIndex = '500';
                currentElement.style.cursor = 'move';
                guardarPosicionMesa(currentElement);
            }
        }

        if (isResizing) {
            console.log(`Redimensión finalizada: ${currentElement?.dataset.mesaNombre}`);
            isResizing = false;
            
            if (currentElement) {
                currentElement.style.zIndex = '500';
                guardarPosicionMesa(currentElement);
            }
        }

        currentElement = null;
        currentHandle = null;
    }

    // === FUNCIONES DE MESA ===
    function abrirMesa(mesaId, mesaNombre) {
        console.log(`Abriendo mesa ${mesaNombre} (ID: ${mesaId})`);
        
        // Abrir directamente el POS para esta mesa en la misma ventana
        window.location.href = `index.php?page=mesa&id=${mesaId}`;
    }

    function rotateMesa(e) {
        e.preventDefault();
        e.stopPropagation();

        const mesa = e.currentTarget;
        const currentRotation = parseFloat(mesa.dataset.rotation) || 0;
        const newRotation = (currentRotation + 90) % 360;

        mesa.dataset.rotation = newRotation;
        mesa.style.transform = `rotate(${newRotation}deg)`;

        console.log(`Mesa ${mesa.dataset.mesaNombre} rotada a ${newRotation}°`);
        guardarPosicionMesa(mesa);
    }

    function showContextMenu(e) {
        e.preventDefault();
        e.stopPropagation();

        const mesa = e.currentTarget;
        const mesaId = mesa.dataset.mesaId;
        const mesaNombre = mesa.dataset.mesaNombre;

        // Crear menú contextual usando SweetAlert2
        Swal.fire({
            title: `Mesa: ${mesaNombre}`,
            html: `
                <div class="flex flex-col gap-2">
                    <button onclick="rotateMesa({currentTarget: document.querySelector('[data-mesa-id=\\"${mesaId}\\"]'), preventDefault: () => {}, stopPropagation: () => {}}); Swal.close();" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        🔄 Rotar 90°
                    </button>
                    <button onclick="abrirMesa(${mesaId}, '${mesaNombre}'); Swal.close();" 
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        🍽️ Abrir POS
                    </button>
                    <button onclick="eliminarMesa(document.querySelector('[data-mesa-id=\\"${mesaId}\\"]')); Swal.close();" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                        🗑️ Eliminar Mesa
                    </button>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Cerrar',
            background: '#1f2937',
            color: '#ffffff'
        });
    }

    function eliminarMesa(mesaElement) {
        const mesaId = mesaElement.dataset.mesaId;
        const mesaNombre = mesaElement.dataset.mesaNombre;

        Swal.fire({
            title: '¿Eliminar Mesa?',
            text: `¿Está seguro de que desea eliminar "${mesaNombre}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            background: '#1f2937',
            color: '#ffffff'
        }).then((result) => {
            if (result.isConfirmed) {
                // Hacer petición AJAX para eliminar
                fetch('../controllers/crear_mesa.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=eliminar&mesa_id=${mesaId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mesaElement.remove();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminada!',
                            text: `Mesa "${mesaNombre}" eliminada correctamente`,
                            timer: 2000,
                            showConfirmButton: false,
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Error al eliminar la mesa',
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión al eliminar la mesa',
                        background: '#1f2937',
                        color: '#ffffff'
                    });
                });
            }
        });
    }

    function guardarPosicionMesa(element) {
        const mesaId = element.dataset.mesaId;
        if (!mesaId) return;

        const left = parseInt(element.style.left) || 0;
        const top = parseInt(element.style.top) || 0;
        const width = element.offsetWidth;
        const height = element.offsetHeight;
        const rotation = parseFloat(element.dataset.rotation) || 0;

        console.log(`💾 Guardando posición mesa ${mesaId}: x:${left}, y:${top}, w:${width}, h:${height}, r:${rotation}`);
        console.log(`📡 URL: ${CONTROLLER_URL}`);

        fetch(CONTROLLER_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `mesa_id=${mesaId}&pos_x=${left}&pos_y=${top}&width=${width}&height=${height}&rotation=${rotation}&tipo_visual=rectangular`
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            console.log('📡 Response ok:', response.ok);
            console.log('📡 Response headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
            }
            
            // Primero obtener el texto para ver qué respuesta recibimos
            return response.text();
        })
        .then(text => {
            console.log('📄 Response text:', text);
            
            // Intentar parsear como JSON
            try {
                const data = JSON.parse(text);
                console.log('📊 Parsed JSON:', data);
                
                if (data.success) {
                    console.log(`✅ Posición guardada para mesa ${mesaId}:`, data.data);
                    
                    // Mostrar confirmación visual
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: `Posición de mesa guardada`,
                        timer: 1000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                        background: '#1f2937',
                        color: '#ffffff',
                        iconColor: '#10b981'
                    });
                } else {
                    console.error(`❌ Error guardando posición: ${data.error}`);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: `Error al guardar: ${data.error}`,
                        background: '#1f2937',
                        color: '#ffffff'
                    });
                }
            } catch (parseError) {
                console.error('❌ Error parseando JSON:', parseError);
                console.error('❌ Texto recibido:', text);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de formato',
                    text: 'El servidor devolvió una respuesta inválida',
                    background: '#1f2937',
                    color: '#ffffff'
                });
            }
        })
        .catch(error => {
            console.error('🚨 Error completo:', error);
            console.error('🚨 Error message:', error.message);
            console.error('🚨 Error stack:', error.stack);
            
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: `No se pudo conectar con el servidor: ${error.message}`,
                background: '#1f2937',
                color: '#ffffff'
            });
        });
    }

    // === FUNCIONES DE CONTROL ===
    function toggleGrid() {
        showGrid = !showGrid;
        const gridBtn = document.getElementById('toggleGrid');
        restaurantFloor.classList.toggle('show-grid', showGrid);
        gridBtn.textContent = showGrid ? '📏 Grid ON' : '📏 Grid OFF';
        gridBtn.style.background = showGrid ? '#059669' : '#3b82f6';
        console.log('Grid:', showGrid ? 'Activado' : 'Desactivado');
    }

    function guardarLayoutCompleto() {
        console.log('💾 Guardando layout completo...');
        
        const mesas = document.querySelectorAll('[data-mesa-id]');
        const layouts = [];
        
        mesas.forEach(mesa => {
            layouts.push({
                mesa_id: mesa.dataset.mesaId,
                pos_x: parseInt(mesa.style.left) || 0,
                pos_y: parseInt(mesa.style.top) || 0,
                width: mesa.offsetWidth,
                height: mesa.offsetHeight,
                rotation: parseFloat(mesa.dataset.rotation) || 0,
                tipo_visual: 'rectangular'
            });
        });
        
        console.log('📋 Datos a guardar:', layouts);
        console.log(`📡 URL: ${CONTROLLER_URL}`);
        
        fetch(CONTROLLER_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ layouts: layouts })
        })
        .then(response => {
            console.log('📡 Batch Response status:', response.status);
            console.log('📡 Batch Response ok:', response.ok);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('✅ Layout completo guardado');
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Layout guardado!',
                    text: `Se guardaron ${layouts.length} mesas correctamente`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    background: '#1f2937',
                    color: '#ffffff'
                });
            } else {
                console.error('❌ Error:', data.error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar layout: ' + (data.error || 'Error desconocido'),
                    background: '#1f2937',
                    color: '#ffffff'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión al guardar layout',
                background: '#1f2937',
                color: '#ffffff'
            });
        });
    }

    function resetLayout() {
        Swal.fire({
            title: '¿Resetear Layout?',
            text: '¿Está seguro de que desea resetear todas las posiciones al layout por defecto?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, resetear',
            cancelButtonText: 'Cancelar',
            background: '#1f2937',
            color: '#ffffff'
        }).then((result) => {
            if (result.isConfirmed) {
                location.reload();
            }
        });
    }

    // === FUNCIONES GLOBALES ===
    window.abrirMesa = abrirMesa;
    window.rotateMesa = rotateMesa;
    window.eliminarMesa = eliminarMesa;
    
    // === FUNCIONES DE DEBUG ===
    window.debugMesas = function() {
        const mesas = document.querySelectorAll('[data-mesa-id]');
        console.log('=== DEBUG MESAS ===');
        console.log(`Total mesas en DOM: ${mesas.length}`);
        
        mesas.forEach((mesa, index) => {
            const rect = mesa.getBoundingClientRect();
            console.log(`Mesa ${index + 1} (ID: ${mesa.dataset.mesaId}):`, {
                nombre: mesa.dataset.mesaNombre,
                posicion: {
                    left: mesa.style.left,
                    top: mesa.style.top,
                    width: mesa.style.width + '/' + mesa.offsetWidth,
                    height: mesa.style.height + '/' + mesa.offsetHeight
                },
                rotacion: mesa.dataset.rotation,
                visible: rect.width > 0 && rect.height > 0,
                rect: rect
            });
        });
        
        // Mostrar datos de BD
        console.log('Layouts cargados de BD:', <?= json_encode($layout_positions) ?>);
    };
    
    window.mostrarLayouts = function() {
        console.log('=== LAYOUTS GUARDADOS EN BD ===');
        console.log(<?= json_encode($layout_positions) ?>);
        
        Swal.fire({
            title: 'Layouts en BD',
            html: '<pre style="text-align: left; font-size: 12px;">' + JSON.stringify(<?= json_encode($layout_positions) ?>, null, 2) + '</pre>',
            width: 600,
            background: '#1f2937',
            color: '#ffffff'
        });
    };
    
    window.testConexion = function() {
        console.log('🧪 Probando conexión...');
        console.log('🌐 URL a probar:', CONTROLLER_URL);
        console.log('🌐 Base URL:', BASE_URL);
        console.log('🌐 Current URL:', window.location.href);
        
        fetch(CONTROLLER_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'mesa_id=999&pos_x=0&pos_y=0&width=120&height=80&rotation=0&tipo_visual=test'
        })
        .then(response => {
            console.log('✅ Respuesta recibida:', response.status, response.statusText);
            return response.text();
        })
        .then(text => {
            console.log('📄 Respuesta texto:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('📊 Respuesta JSON:', data);
                
                Swal.fire({
                    icon: data.success ? 'success' : 'info',
                    title: 'Test de Conexión',
                    text: data.message || data.error || 'Conexión establecida',
                    background: '#1f2937',
                    color: '#ffffff'
                });
            } catch (e) {
                console.log('⚠️ No es JSON válido');
                Swal.fire({
                    icon: 'warning',
                    title: 'Respuesta del servidor',
                    text: 'Servidor responde pero no envía JSON válido',
                    background: '#1f2937',
                    color: '#ffffff'
                });
            }
        })
        .catch(error => {
            console.error('❌ Error de conexión:', error);
            
            Swal.fire({
                icon: 'error',
                title: 'Error de Conexión',
                text: `No se puede conectar: ${error.message}`,
                background: '#1f2937',
                color: '#ffffff'
            });
        });
    };
});
</script>
