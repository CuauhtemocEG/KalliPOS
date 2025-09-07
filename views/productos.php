<?php
// Este archivo es incluido desde index.php, por lo que las rutas son relativas al directorio raíz
// $pdo y $userInfo ya están disponibles desde index.php

// Obtén todos los tipos y crea un array asociativo id => nombre
$tipos = $pdo->query("SELECT id, nombre FROM type")->fetchAll(PDO::FETCH_KEY_PAIR);

$editando = false;
$producto = null;

if (isset($_GET['editar'])) {
  $editando = true;
  $pid = intval($_GET['editar']);
  $prodedit = $pdo->prepare("SELECT * FROM productos WHERE id=?");
  $prodedit->execute([$pid]);
  $producto = $prodedit->fetch();
}

$productos = $pdo->query("SELECT * FROM productos")->fetchAll();
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- CSS Personalizado para el Modal Tailwind -->
<style>
  .modal-overlay { background-color: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px); }
  .modal-enter { opacity: 0; transform: scale(0.95); }
  .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity 0.3s ease-out, transform 0.3s ease-out; }
  .modal-exit { opacity: 1; transform: scale(1); }
  .modal-exit-active { opacity: 0; transform: scale(0.95); transition: opacity 0.2s ease-in, transform 0.2s ease-in; }
  
  /* Animación de spin para el loading */
  .spin { animation: spin 1s linear infinite; }
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  
  /* Estilos para los dropdowns */
  .dropdown-menu {
    animation: fadeInDown 0.2s ease-out;
  }
  
  @keyframes fadeInDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  /* Estilos para los botones de Excel */
  .excel-btn-group { display: flex; gap: 0.5rem; }
  .excel-btn { min-width: 120px; }
  
  /* Responsive adjustments */
  @media (max-width: 640px) {
    .excel-btn span { display: none; }
    .excel-btn { min-width: auto; padding: 0.75rem; }
  }
</style>

<!-- Hero Section -->
<div class="text-center mb-8">
  <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl mb-4 shadow-lg">
    <i class="bi bi-bag text-white text-2xl"></i>
  </div>
  <h1 class="text-3xl md:text-4xl font-montserrat-bold font-display gradient-text mb-3">Catálogo de Productos</h1>
  <p class="text-lg text-gray-400 max-w-xl mx-auto">Gestiona el inventario completo del restaurante</p>
</div>

<!-- Action Bar -->
<div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-8">
  <div class="flex items-center space-x-4">
    <div class="relative">
      <input 
        type="text" 
        id="searchInput" 
        placeholder="Buscar productos..." 
        class="w-72 pl-12 pr-4 py-3 bg-dark-700/50 border border-dark-600/50 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent"
      >
      <i class="bi bi-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
    </div>
  </div>
  
  <div class="flex flex-col sm:flex-row items-center gap-3">
    <!-- Botones de Excel -->
    <div class="flex items-center gap-2">
      <!-- Dropdown para descargar plantilla -->
      <div class="relative group">
        <button 
          id="plantillaDropdownBtn"
          class="flex items-center space-x-2 px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105"
          title="Descargar plantilla para editar productos"
        >
          <i class="bi bi-file-earmark-excel"></i>
          <span class="hidden sm:inline">Descargar Plantilla</span>
          <i class="bi bi-chevron-down text-sm"></i>
        </button>
        
        <!-- Dropdown Menu -->
        <div id="plantillaDropdown" class="hidden absolute top-full mt-2 left-0 bg-dark-800 border border-dark-600 rounded-xl shadow-2xl z-50 min-w-[200px]">
          <div class="py-2">
            <button 
              onclick="descargarPlantilla('xls')" 
              class="w-full text-left px-4 py-3 text-white hover:bg-dark-700 transition-colors duration-200 flex items-center space-x-2"
            >
              <i class="bi bi-file-earmark-excel text-green-400"></i>
              <div>
                <div class="font-medium">Formato Excel (.xls)</div>
                <div class="text-xs text-gray-400">Compatible con Microsoft Excel</div>
              </div>
            </button>
            <button 
              onclick="descargarPlantilla('csv')" 
              class="w-full text-left px-4 py-3 text-white hover:bg-dark-700 transition-colors duration-200 flex items-center space-x-2"
            >
              <i class="bi bi-file-earmark-text text-blue-400"></i>
              <div>
                <div class="font-medium">Formato CSV (.csv)</div>
                <div class="text-xs text-gray-400">Compatible con cualquier editor</div>
              </div>
            </button>
          </div>
        </div>
      </div>
      
      <button 
        onclick="openImportModal()" 
        class="flex items-center space-x-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105"
        title="Cargar archivo Excel con productos"
      >
        <i class="bi bi-upload"></i>
        <span class="hidden sm:inline">Cargar Excel</span>
      </button>
    </div>
    
    <!-- Separador visual -->
    <div class="hidden sm:block w-px h-8 bg-gray-600"></div>
    
    <!-- Botón nuevo producto -->
    <button 
      onclick="openModal()" 
      class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105"
    >
      <i class="bi bi-plus-lg"></i>
      <span>Nuevo Producto</span>
    </button>
  </div>
</div>

<!-- Productos Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="productosGrid">
  <!-- Tarjeta para Añadir Nuevo Producto -->
  <div 
    onclick="openModal()" 
    class="group bg-gradient-to-br from-purple-600/20 to-pink-600/20 border border-purple-500/30 rounded-2xl p-6 cursor-pointer hover:shadow-2xl transition-all duration-300 transform hover:scale-105 hover:border-purple-400/50"
  >
    <div class="flex flex-col items-center justify-center h-full min-h-[200px] text-center">
      <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
        <i class="bi bi-plus-lg text-white text-2xl"></i>
      </div>
      <h3 class="text-xl font-montserrat-semibold text-white mb-2">Añadir Producto</h3>
      <p class="text-gray-400 text-sm">Click para crear un nuevo producto</p>
    </div>
  </div>

  <!-- Productos Existentes -->
  <?php foreach ($productos as $prod): ?>
    <div class="bg-dark-800/50 border border-dark-700/50 rounded-2xl overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:scale-105 group">
      <!-- Imagen del Producto -->
      <div class="relative h-48 bg-gradient-to-br from-gray-700 to-gray-800 overflow-hidden">
        <?php if (!empty($prod['imagen'])): ?>
          <img src="assets/img/<?= htmlspecialchars($prod['imagen']) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
        <?php else: ?>
          <div class="w-full h-full flex items-center justify-center">
            <i class="bi bi-image text-4xl text-gray-500"></i>
          </div>
        <?php endif; ?>
        
        <!-- Overlay con acciones -->
        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center space-x-3">
          <button 
            onclick="editProduct(<?= $prod['id'] ?>)" 
            class="p-3 bg-blue-600 hover:bg-blue-700 text-white rounded-full transition-colors duration-200"
            title="Editar producto"
          >
            <i class="bi bi-pencil"></i>
          </button>
          <button 
            onclick="deleteProduct(<?= $prod['id'] ?>)" 
            class="p-3 bg-red-600 hover:bg-red-700 text-white rounded-full transition-colors duration-200"
            title="Eliminar producto"
          >
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>

      <!-- Información del Producto -->
      <div class="p-6">
        <h3 class="text-xl font-montserrat-semibold text-white mb-2 truncate"><?= htmlspecialchars($prod['nombre']) ?></h3>
        
        <div class="space-y-2 mb-4">
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-400">Precio:</span>
            <span class="text-lg font-bold text-green-400">$<?= number_format($prod['precio'], 2) ?></span>
          </div>
          
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-400">Categoría:</span>
            <span class="text-sm text-purple-300">
              <?= isset($tipos[$prod['type']]) ? htmlspecialchars($tipos[$prod['type']]) : 'Sin categoría' ?>
            </span>
          </div>
        
        </div>

        <?php if (!empty($prod['descripcion'])): ?>
          <p class="text-sm text-gray-400 line-clamp-2"><?= htmlspecialchars($prod['descripcion']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Modal para Agregar/Editar Producto (Tailwind CSS Puro) -->
<div id="productModal" class="hidden fixed inset-0 z-50">
  <!-- Backdrop -->
  <div class="modal-overlay absolute inset-0" onclick="closeModal()"></div>
  
  <!-- Modal Container -->
  <div class="fixed inset-0 flex items-center justify-center p-4 max-h-screen overflow-y-auto">
    <div class="modal-enter bg-dark-800 rounded-2xl shadow-2xl border border-dark-700/50 w-full max-w-2xl my-8">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-6 border-b border-dark-700/50">
        <h2 class="text-2xl font-montserrat-bold text-white">
          <i class="bi bi-bag mr-2 text-purple-400"></i>
          <span id="modalTitle"><?= $editando ? 'Editar Producto' : 'Nuevo Producto' ?></span>
        </h2>
        <button 
          onclick="closeModal()" 
          class="p-2 hover:bg-dark-700 rounded-lg transition-colors duration-200 text-gray-400 hover:text-white"
        >
          <i class="bi bi-x-lg text-xl"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <form id="productForm" method="POST" action="controllers/productos_crud.php" enctype="multipart/form-data" class="p-6 space-y-6">
        <input type="hidden" name="action" value="<?= $editando ? 'edit' : 'add' ?>">
        <?php if ($editando): ?>
          <input type="hidden" name="id" value="<?= $producto['id'] ?>">
        <?php endif; ?>

        <!-- Nombre del Producto -->
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">
            <i class="bi bi-tag mr-1"></i> Nombre del Producto *
          </label>
          <input 
            type="text" 
            name="nombre" 
            value="<?= $editando ? htmlspecialchars($producto['nombre']) : '' ?>"
            class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent" 
            placeholder="Ej: Pizza Margarita"
            required
          >
        </div>

        <!-- Descripción -->
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">
            <i class="bi bi-card-text mr-1"></i> Descripción
          </label>
          <textarea 
            name="descripcion" 
            rows="3"
            class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent resize-none" 
            placeholder="Describe brevemente el producto..."
          ><?= $editando ? htmlspecialchars($producto['descripcion']) : '' ?></textarea>
        </div>

        <!-- Precio y Stock -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">
              <i class="bi bi-currency-dollar mr-1"></i> Precio *
            </label>
            <input 
              type="number" 
              name="precio" 
              value="<?= $editando ? $producto['precio'] : '' ?>"
              step="0.01" 
              min="0"
              class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent" 
              placeholder="0.00"
              required
            >
          </div>
        </div>

        <!-- Categoría -->
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">
            <i class="bi bi-grid mr-1"></i> Categoría *
          </label>
          <select name="type" class="w-full px-4 py-3 bg-dark-600/50 border border-dark-500/50 rounded-xl text-white" required>
            <option value="">Seleccionar categoría</option>
            <?php foreach ($tipos as $idTipo => $nombreTipo): ?>
              <option value="<?= $idTipo ?>" <?= ($editando && $producto['type'] == $idTipo) ? 'selected' : '' ?>>
                <?= htmlspecialchars($nombreTipo) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Imagen del Producto -->
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">
            <i class="bi bi-image mr-1"></i> Imagen del Producto
          </label>
          
          <!-- Área de Drop Zone -->
          <div 
            id="dropZone" 
            class="relative border-2 border-dashed border-dark-500/50 rounded-xl p-6 text-center hover:border-purple-500/50 transition-colors duration-300 cursor-pointer bg-dark-600/20"
            onclick="document.getElementById('imagen').click()"
          >
            <input type="file" id="imagen" name="imagen" accept="image/*" class="hidden" onchange="handleFileSelect(event)">
            
            <div id="dropContent" class="space-y-3">
              <i class="bi bi-cloud-upload text-4xl text-gray-500"></i>
              <div class="text-gray-400">
                <p class="text-lg font-medium">Arrastra una imagen aquí</p>
                <p class="text-sm">o haz click para seleccionar</p>
              </div>
              <p class="text-xs text-gray-500">PNG, JPG hasta 5MB</p>
            </div>
            
            <!-- Preview de la imagen -->
            <div id="imagePreview" class="hidden">
              <img id="previewImage" class="max-w-full h-32 object-cover mx-auto rounded-lg">
              <p id="fileName" class="text-sm text-gray-400 mt-2"></p>
              <button type="button" onclick="removeImage()" class="text-red-400 hover:text-red-300 text-sm mt-2">
                <i class="bi bi-trash mr-1"></i> Remover imagen
              </button>
            </div>
          </div>
          
          <?php if ($editando && !empty($producto['imagen'])): ?>
            <div class="mt-3 text-sm text-gray-400">
              <i class="bi bi-info-circle mr-1"></i>
              Imagen actual: <?= htmlspecialchars($producto['imagen']) ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Botones del Modal -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-dark-700/50">
          <button 
            type="button" 
            onclick="closeModal()" 
            class="px-6 py-3 bg-dark-600 hover:bg-dark-500 text-gray-300 rounded-xl font-medium transition-colors duration-200"
          >
            Cancelar
          </button>
          <button 
            type="submit" 
            class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-200 shadow-lg hover:shadow-xl"
          >
            <i class="bi bi-check-lg mr-2"></i>
            <?= $editando ? 'Actualizar' : 'Crear' ?> Producto
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para Importar Productos desde Excel -->
<div id="importModal" class="hidden fixed inset-0 z-50">
  <!-- Backdrop -->
  <div class="modal-overlay absolute inset-0" onclick="closeImportModal()"></div>
  
  <!-- Modal Container -->
  <div class="fixed inset-0 flex items-center justify-center p-4">
    <div class="modal-enter bg-dark-800 rounded-2xl shadow-2xl border border-dark-700/50 w-full max-w-3xl">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-6 border-b border-dark-700/50">
        <h2 class="text-2xl font-montserrat-bold text-white">
          <i class="bi bi-upload mr-2 text-blue-400"></i>
          <span>Importar Productos desde Excel</span>
        </h2>
        <button 
          onclick="closeImportModal()" 
          class="p-2 hover:bg-dark-700 rounded-lg transition-colors duration-200 text-gray-400 hover:text-white"
        >
          <i class="bi bi-x-lg text-xl"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-6">
        <!-- Instrucciones -->
        <div class="bg-blue-600/10 border border-blue-500/30 rounded-xl p-4">
          <h3 class="text-lg font-semibold text-blue-300 mb-3">
            <i class="bi bi-info-circle mr-2"></i>Instrucciones de Uso
          </h3>
          <div class="text-sm text-blue-200 space-y-2">
            <p><strong>1.</strong> Descarga primero la plantilla Excel haciendo clic en "Descargar Plantilla"</p>
            <p><strong>2.</strong> Edita la plantilla con tus productos (puedes usar Excel, LibreOffice, Google Sheets)</p>
            <p><strong>3.</strong> Para productos nuevos: deja el ID como "NUEVO" o vacío</p>
            <p><strong>4.</strong> Para actualizar existentes: usa el ID del producto</p>
            <p><strong>5.</strong> Guarda el archivo y súbelo aquí</p>
          </div>
        </div>
        
        <!-- Área de carga -->
        <form id="importForm" enctype="multipart/form-data">
          <div 
            id="excelDropZone" 
            class="relative border-2 border-dashed border-dark-500/50 rounded-xl p-8 text-center hover:border-blue-500/50 transition-colors duration-300 cursor-pointer bg-dark-600/20"
            onclick="document.getElementById('archivoExcel').click()"
          >
            <input type="file" id="archivoExcel" name="archivo_excel" accept=".xls,.xlsx,.csv" class="hidden" onchange="handleExcelFileSelect(event)">
            
            <div id="excelDropContent" class="space-y-4">
              <i class="bi bi-file-earmark-excel text-5xl text-blue-400"></i>
              <div class="text-gray-300">
                <p class="text-xl font-medium">Arrastra tu archivo Excel aquí</p>
                <p class="text-sm text-gray-400 mt-1">o haz click para seleccionar</p>
              </div>
              <p class="text-xs text-gray-500">Formatos soportados: XLS, XLSX, CSV (máximo 5MB)</p>
            </div>
            
            <!-- Preview del archivo -->
            <div id="excelFilePreview" class="hidden">
              <i class="bi bi-file-earmark-excel text-4xl text-green-400"></i>
              <p id="excelFileName" class="text-lg text-green-300 mt-2"></p>
              <p class="text-sm text-gray-400">Archivo listo para procesar</p>
              <button type="button" onclick="removeExcelFile()" class="text-red-400 hover:text-red-300 text-sm mt-2">
                <i class="bi bi-trash mr-1"></i> Cambiar archivo
              </button>
            </div>
          </div>
        </form>
        
        <!-- Resultados del procesamiento -->
        <div id="importResults" class="hidden">
          <div class="bg-dark-700/50 rounded-xl p-4">
            <h4 class="text-lg font-semibold text-white mb-3">Resultados del Procesamiento</h4>
            <div id="importSummary" class="space-y-2"></div>
            <div id="importErrors" class="mt-4"></div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="flex justify-between items-center p-6 border-t border-dark-700/50">
        <!-- Dropdown para descargar plantilla dentro del modal -->
        <div class="relative">
          <button 
            id="modalPlantillaDropdownBtn"
            class="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200"
          >
            <i class="bi bi-download"></i>
            <span>Descargar Plantilla</span>
            <i class="bi bi-chevron-down text-sm"></i>
          </button>
          
          <!-- Dropdown Menu dentro del modal -->
          <div id="modalPlantillaDropdown" class="hidden absolute bottom-full mb-2 left-0 bg-dark-700 border border-dark-600 rounded-xl shadow-2xl z-50 min-w-[220px]">
            <div class="py-2">
              <button 
                onclick="descargarPlantilla('xls')" 
                class="w-full text-left px-4 py-3 text-white hover:bg-dark-600 transition-colors duration-200 flex items-center space-x-2"
              >
                <i class="bi bi-file-earmark-excel text-green-400"></i>
                <div>
                  <div class="font-medium text-sm">Excel (.xls)</div>
                  <div class="text-xs text-gray-400">Microsoft Excel</div>
                </div>
              </button>
              <button 
                onclick="descargarPlantilla('csv')" 
                class="w-full text-left px-4 py-3 text-white hover:bg-dark-600 transition-colors duration-200 flex items-center space-x-2"
              >
                <i class="bi bi-file-earmark-text text-blue-400"></i>
                <div>
                  <div class="font-medium text-sm">CSV (.csv)</div>
                  <div class="text-xs text-gray-400">Cualquier editor</div>
                </div>
              </button>
            </div>
          </div>
        </div>
        
        <div class="flex space-x-3">
          <button 
            type="button" 
            onclick="closeImportModal()" 
            class="px-6 py-3 bg-dark-600 hover:bg-dark-500 text-gray-300 rounded-xl font-medium transition-colors duration-200"
          >
            Cancelar
          </button>
          <button 
            id="processExcelBtn"
            onclick="procesarArchivoExcel()" 
            disabled
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white rounded-xl font-medium transition-all duration-200 shadow-lg hover:shadow-xl"
          >
            <i class="bi bi-upload mr-2"></i>
            <span id="processExcelText">Procesar Archivo</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Variables globales
let isModalOpen = false;
let isImportModalOpen = false;

// ============= FUNCIONES EXISTENTES =============

// Función para abrir el modal
function openModal(editMode = false, productData = null) {
  const modal = document.getElementById('productModal');
  const modalTitle = document.getElementById('modalTitle');
  const form = document.getElementById('productForm');
  
  if (editMode && productData) {
    modalTitle.textContent = 'Editar Producto';
    // Rellenar formulario con datos del producto
    populateForm(productData);
  } else {
    modalTitle.textContent = 'Nuevo Producto';
    form.reset();
    resetImagePreview();
  }
  
  modal.classList.remove('hidden');
  modal.querySelector('.modal-enter').classList.add('modal-enter-active');
  isModalOpen = true;
  
  // Prevenir scroll del body
  document.body.style.overflow = 'hidden';
}

// Función para cerrar el modal
function closeModal() {
  if (!isModalOpen) return;
  
  const modal = document.getElementById('productModal');
  const modalContent = modal.querySelector('.modal-enter-active');
  
  modalContent.classList.remove('modal-enter-active');
  modalContent.classList.add('modal-exit-active');
  
  setTimeout(() => {
    modal.classList.add('hidden');
    modalContent.classList.remove('modal-exit-active');
    modalContent.classList.add('modal-enter');
    isModalOpen = false;
    
    // Restaurar scroll del body
    document.body.style.overflow = '';
  }, 200);
}

// Función para editar producto
function editProduct(productId) {
  // Aquí harías una petición AJAX para obtener los datos del producto
  window.location.href = `?page=productos&editar=${productId}`;
}

// Función para eliminar producto
function deleteProduct(productId) {
  Swal.fire({
    title: '¿Estás seguro?',
    text: "Esta acción no se puede deshacer",
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
      // Crear un formulario para enviar la petición de eliminación
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'controllers/productos_crud.php';
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'action';
      actionInput.value = 'delete';
      
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'id';
      idInput.value = productId;
      
      form.appendChild(actionInput);
      form.appendChild(idInput);
      
      document.body.appendChild(form);
      form.submit();
    }
  });
}

// Manejo de archivos
function handleFileSelect(event) {
  const files = event.target.files;
  if (files.length > 0) {
    const file = files[0];
    
    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
      Swal.fire({
        title: 'Error',
        text: 'Por favor selecciona un archivo de imagen válido',
        icon: 'error',
        background: '#1f2937',
        color: '#ffffff'
      });
      return;
    }
    
    // Validar tamaño (5MB máximo)
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({
        title: 'Error',
        text: 'El archivo es demasiado grande. Máximo 5MB permitido',
        icon: 'error',
        background: '#1f2937',
        color: '#ffffff'
      });
      return;
    }
    
    // Mostrar preview
    showImagePreview(file);
  }
}

function showImagePreview(file) {
  const dropContent = document.getElementById('dropContent');
  const imagePreview = document.getElementById('imagePreview');
  const previewImage = document.getElementById('previewImage');
  const fileName = document.getElementById('fileName');
  
  const reader = new FileReader();
  reader.onload = function(e) {
    previewImage.src = e.target.result;
    fileName.textContent = file.name;
    
    dropContent.classList.add('hidden');
    imagePreview.classList.remove('hidden');
  };
  reader.readAsDataURL(file);
}

function removeImage() {
  const dropContent = document.getElementById('dropContent');
  const imagePreview = document.getElementById('imagePreview');
  const imageInput = document.getElementById('imagen');
  
  imageInput.value = '';
  dropContent.classList.remove('hidden');
  imagePreview.classList.add('hidden');
}

function resetImagePreview() {
  removeImage();
}

// Drag and Drop functionality
const dropZone = document.getElementById('dropZone');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
  dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
  dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
  dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
  dropZone.classList.add('border-purple-500/70', 'bg-purple-500/10');
}

function unhighlight(e) {
  dropZone.classList.remove('border-purple-500/70', 'bg-purple-500/10');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
  const dt = e.dataTransfer;
  const files = dt.files;
  
  if (files.length > 0) {
    document.getElementById('imagen').files = files;
    handleFileSelect({ target: { files: files } });
  }
}

// Búsqueda en tiempo real
document.getElementById('searchInput').addEventListener('input', function(e) {
  const searchTerm = e.target.value.toLowerCase();
  const productCards = document.querySelectorAll('#productosGrid > div:not(:first-child)');
  
  productCards.forEach(card => {
    const productName = card.querySelector('h3')?.textContent.toLowerCase() || '';
    const productDescription = card.querySelector('p')?.textContent.toLowerCase() || '';
    
    if (productName.includes(searchTerm) || productDescription.includes(searchTerm)) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
});

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    if (isModalOpen) closeModal();
    if (isImportModalOpen) closeImportModal();
  }
});

// ============= FUNCIONES PARA EXCEL =============

// Función para descargar plantilla Excel (con formato)
function descargarPlantilla(formato = 'xls') {
  if (formato === 'csv') {
    window.open('controllers/exportar_plantilla_productos_csv.php', '_blank');
  } else {
    window.open('controllers/exportar_plantilla_productos_xls.php', '_blank');
  }
  
  // Cerrar ambos dropdowns si están abiertos
  const dropdown = document.getElementById('plantillaDropdown');
  const modalDropdown = document.getElementById('modalPlantillaDropdown');
  if (dropdown) dropdown.classList.add('hidden');
  if (modalDropdown) modalDropdown.classList.add('hidden');
}

// Manejo del dropdown de plantillas
document.addEventListener('DOMContentLoaded', function() {
  const dropdownBtn = document.getElementById('plantillaDropdownBtn');
  const dropdown = document.getElementById('plantillaDropdown');
  const modalDropdownBtn = document.getElementById('modalPlantillaDropdownBtn');
  const modalDropdown = document.getElementById('modalPlantillaDropdown');
  
  // Dropdown principal
  if (dropdownBtn && dropdown) {
    dropdownBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdown.classList.toggle('hidden');
      // Cerrar el otro dropdown si está abierto
      if (modalDropdown) modalDropdown.classList.add('hidden');
    });
  }
  
  // Dropdown del modal
  if (modalDropdownBtn && modalDropdown) {
    modalDropdownBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      modalDropdown.classList.toggle('hidden');
      // Cerrar el otro dropdown si está abierto
      if (dropdown) dropdown.classList.add('hidden');
    });
  }
  
  // Cerrar dropdowns al hacer clic fuera
  document.addEventListener('click', function(e) {
    if (dropdown && dropdownBtn && !dropdownBtn.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
    if (modalDropdown && modalDropdownBtn && !modalDropdownBtn.contains(e.target) && !modalDropdown.contains(e.target)) {
      modalDropdown.classList.add('hidden');
    }
  });
  
  // Cerrar dropdowns con Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      if (dropdown) dropdown.classList.add('hidden');
      if (modalDropdown) modalDropdown.classList.add('hidden');
    }
  });
});

// Función para abrir modal de importación
function openImportModal() {
  const modal = document.getElementById('importModal');
  modal.classList.remove('hidden');
  modal.querySelector('.modal-enter').classList.add('modal-enter-active');
  isImportModalOpen = true;
  
  // Resetear formulario
  resetImportForm();
  
  // Prevenir scroll del body
  document.body.style.overflow = 'hidden';
}

// Función para cerrar modal de importación
function closeImportModal() {
  if (!isImportModalOpen) return;
  
  const modal = document.getElementById('importModal');
  const modalContent = modal.querySelector('.modal-enter-active');
  
  modalContent.classList.remove('modal-enter-active');
  modalContent.classList.add('modal-exit-active');
  
  setTimeout(() => {
    modal.classList.add('hidden');
    modalContent.classList.remove('modal-exit-active');
    modalContent.classList.add('modal-enter');
    isImportModalOpen = false;
    
    // Restaurar scroll del body
    document.body.style.overflow = '';
  }, 200);
}

// Función para resetear formulario de importación
function resetImportForm() {
  document.getElementById('archivoExcel').value = '';
  document.getElementById('excelDropContent').classList.remove('hidden');
  document.getElementById('excelFilePreview').classList.add('hidden');
  document.getElementById('importResults').classList.add('hidden');
  document.getElementById('processExcelBtn').disabled = true;
}

// Manejo de selección de archivo Excel
function handleExcelFileSelect(event) {
  const files = event.target.files;
  if (files.length > 0) {
    const file = files[0];
    
    // Validar tipo de archivo
    const extension = file.name.split('.').pop().toLowerCase();
    if (!['xls', 'xlsx', 'csv'].includes(extension)) {
      Swal.fire({
        title: 'Error',
        text: 'Por favor selecciona un archivo Excel válido (.xls, .xlsx o .csv)',
        icon: 'error',
        background: '#1f2937',
        color: '#ffffff'
      });
      return;
    }
    
    // Validar tamaño (5MB máximo)
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({
        title: 'Error',
        text: 'El archivo es demasiado grande. Máximo 5MB permitido',
        icon: 'error',
        background: '#1f2937',
        color: '#ffffff'
      });
      return;
    }
    
    // Mostrar preview
    showExcelFilePreview(file);
  }
}

// Mostrar preview del archivo Excel
function showExcelFilePreview(file) {
  const dropContent = document.getElementById('excelDropContent');
  const filePreview = document.getElementById('excelFilePreview');
  const fileName = document.getElementById('excelFileName');
  
  fileName.textContent = file.name;
  dropContent.classList.add('hidden');
  filePreview.classList.remove('hidden');
  
  // Habilitar botón de procesar
  document.getElementById('processExcelBtn').disabled = false;
}

// Remover archivo Excel seleccionado
function removeExcelFile() {
  document.getElementById('archivoExcel').value = '';
  document.getElementById('excelDropContent').classList.remove('hidden');
  document.getElementById('excelFilePreview').classList.add('hidden');
  document.getElementById('processExcelBtn').disabled = true;
}

// Procesar archivo Excel
async function procesarArchivoExcel() {
  const fileInput = document.getElementById('archivoExcel');
  const processBtn = document.getElementById('processExcelBtn');
  const processText = document.getElementById('processExcelText');
  
  if (!fileInput.files[0]) {
    Swal.fire({
      title: 'Error',
      text: 'Por favor selecciona un archivo primero',
      icon: 'error',
      background: '#1f2937',
      color: '#ffffff'
    });
    return;
  }
  
  // Cambiar estado del botón
  processBtn.disabled = true;
  processText.textContent = 'Procesando...';
  processBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin mr-2"></i><span>Procesando...</span>';
  
  try {
    const formData = new FormData();
    formData.append('archivo_excel', fileInput.files[0]);
    
    const response = await fetch('controllers/importar_productos_excel.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Mostrar resultados exitosos
      showImportResults(result);
      
      // Mostrar notificación de éxito
      Swal.fire({
        title: '¡Éxito!',
        text: result.message,
        icon: result.warning ? 'warning' : 'success',
        background: '#1f2937',
        color: '#ffffff'
      }).then(() => {
        // Recargar página para mostrar cambios
        window.location.reload();
      });
      
    } else {
      throw new Error(result.message);
    }
    
  } catch (error) {
    console.error('Error:', error);
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error al procesar el archivo',
      icon: 'error',
      background: '#1f2937',
      color: '#ffffff'
    });
  } finally {
    // Restaurar estado del botón
    processBtn.disabled = false;
    processText.textContent = 'Procesar Archivo';
    processBtn.innerHTML = '<i class="bi bi-upload mr-2"></i><span>Procesar Archivo</span>';
  }
}

// Mostrar resultados de la importación
function showImportResults(result) {
  const resultsDiv = document.getElementById('importResults');
  const summaryDiv = document.getElementById('importSummary');
  const errorsDiv = document.getElementById('importErrors');
  
  // Limpiar contenido anterior
  summaryDiv.innerHTML = '';
  errorsDiv.innerHTML = '';
  
  // Mostrar resumen
  summaryDiv.innerHTML = `
    <div class="grid grid-cols-2 gap-4">
      <div class="bg-green-600/20 border border-green-500/30 rounded-lg p-3">
        <p class="text-green-300 font-semibold">Productos Creados</p>
        <p class="text-2xl font-bold text-green-400">${result.creados}</p>
      </div>
      <div class="bg-blue-600/20 border border-blue-500/30 rounded-lg p-3">
        <p class="text-blue-300 font-semibold">Productos Actualizados</p>
        <p class="text-2xl font-bold text-blue-400">${result.actualizados}</p>
      </div>
    </div>
  `;
  
  // Mostrar errores si los hay
  if (result.errores && result.errores.length > 0) {
    errorsDiv.innerHTML = `
      <div class="bg-red-600/20 border border-red-500/30 rounded-lg p-4">
        <p class="text-red-300 font-semibold mb-2">Errores encontrados (${result.errores.length}):</p>
        <div class="text-sm text-red-200 space-y-1 max-h-32 overflow-y-auto">
          ${result.errores.map(error => `<p>• ${error}</p>`).join('')}
        </div>
      </div>
    `;
  }
  
  resultsDiv.classList.remove('hidden');
}

// Drag and Drop para Excel
const excelDropZone = document.getElementById('excelDropZone');

if (excelDropZone) {
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    excelDropZone.addEventListener(eventName, preventDefaults, false);
  });

  ['dragenter', 'dragover'].forEach(eventName => {
    excelDropZone.addEventListener(eventName, highlightExcelDrop, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    excelDropZone.addEventListener(eventName, unhighlightExcelDrop, false);
  });

  excelDropZone.addEventListener('drop', handleExcelDrop, false);
}

function highlightExcelDrop(e) {
  excelDropZone.classList.add('border-blue-500/70', 'bg-blue-500/10');
}

function unhighlightExcelDrop(e) {
  excelDropZone.classList.remove('border-blue-500/70', 'bg-blue-500/10');
}

function handleExcelDrop(e) {
  const dt = e.dataTransfer;
  const files = dt.files;
  
  if (files.length > 0) {
    document.getElementById('archivoExcel').files = files;
    handleExcelFileSelect({ target: { files: files } });
  }
}

// ============= FUNCIONES EXISTENTES (CONTINUACIÓN) =============

// Cerrar modal con Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && isModalOpen) {
    closeModal();
  }
});

// Abrir modal si estamos en modo edición
<?php if ($editando): ?>
  document.addEventListener('DOMContentLoaded', function() {
    openModal(true);
  });
<?php endif; ?>

// Manejo del formulario
document.getElementById('productForm').addEventListener('submit', function(e) {
  // Aquí puedes agregar validaciones adicionales si es necesario
  const submitButton = this.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.innerHTML = '<i class="bi bi-arrow-clockwise spin mr-2"></i> Procesando...';
});
</script>
