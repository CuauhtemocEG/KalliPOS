<!-- Hero Section -->
<div class="text-center mb-12">
  <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-3xl mb-6 shadow-2xl">
    <i class="bi bi-cup-straw text-white text-3xl"></i>
  </div>
  <h1 class="text-4xl md:text-5xl font-bold font-display gradient-text mb-4">
    Vista de Bar
  </h1>
  <p class="text-xl text-gray-400 max-w-2xl mx-auto">
    Gestiona y controla todas las órdenes de bebidas en preparación
  </p>
</div>

<!-- Bar Status Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 text-center">
    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
      <i class="bi bi-clock text-white text-xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-white" id="bebidas-pendientes">0</h3>
    <p class="text-gray-400">Pendientes</p>
  </div>
  
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 text-center">
    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
      <i class="bi bi-hourglass-split text-white text-xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-white" id="bebidas-preparando">0</h3>
    <p class="text-gray-400">En Preparación</p>
  </div>
  
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 text-center">
    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
      <i class="bi bi-check-circle text-white text-xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-white" id="bebidas-listas">0</h3>
    <p class="text-gray-400">Listas</p>
  </div>
  
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6 text-center">
    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl mx-auto mb-4 flex items-center justify-center">
      <i class="bi bi-grid-3x3 text-white text-xl"></i>
    </div>
    <h3 class="text-2xl font-bold text-white" id="mesas-activas-bar">0</h3>
    <p class="text-gray-400">Mesas Activas</p>
  </div>
</div>

<!-- Auto Refresh Control -->
<div class="mb-8">
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center">
          <i class="bi bi-arrow-clockwise text-white"></i>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-white">Actualización Automática</h3>
          <p class="text-gray-400 text-sm">Los datos se actualizan cada 30 segundos</p>
        </div>
      </div>
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 bg-cyan-500 rounded-full animate-pulse"></div>
          <span class="text-cyan-400 text-sm font-medium">En línea</span>
        </div>
        <button onclick="cargarBar()" 
                class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-semibold rounded-xl transition-all duration-200 transform hover:scale-105">
          <i class="bi bi-arrow-clockwise mr-2"></i>
          Actualizar Ahora
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Bar Orders Content -->
<div id="bar-content">
  <!-- Loading State -->
  <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-12 text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-cyan-500/20 to-blue-600/20 rounded-2xl mb-4">
      <i class="bi bi-arrow-clockwise text-cyan-400 text-2xl animate-spin"></i>
    </div>
    <h3 class="text-xl font-semibold text-white mb-2">Cargando Vista de Bar</h3>
    <p class="text-gray-400">Obteniendo órdenes de bebidas...</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let refreshIntervalBar;

function cargarBar() {
  // Show loading state
  document.getElementById('bar-content').innerHTML = `
    <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-12 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-cyan-500/20 to-blue-600/20 rounded-2xl mb-4">
        <i class="bi bi-arrow-clockwise text-cyan-400 text-2xl animate-spin"></i>
      </div>
      <h3 class="text-xl font-semibold text-white mb-2">Actualizando Vista de Bar</h3>
      <p class="text-gray-400">Obteniendo órdenes de bebidas...</p>
    </div>
  `;

  fetch('controllers/bar_ajax.php')
    .then(res => res.json())
    .then(data => {
      // Agrupar por mesa
      let mesas = {};
      let stats = {
        pendientes: 0,
        preparando: 0,
        listas: 0,
        mesasActivas: 0
      };

      data.forEach(item => {
        if (!mesas[item.mesa]) {
          mesas[item.mesa] = [];
          stats.mesasActivas++;
        }
        mesas[item.mesa].push(item);
        
        // Calculate stats
        stats.pendientes += parseInt(item.faltan);
        stats.preparando += parseInt(item.cantidad) - parseInt(item.preparado) - parseInt(item.cancelado) - parseInt(item.faltan);
        stats.listas += parseInt(item.preparado);
      });

      // Update stats
      document.getElementById('bebidas-pendientes').textContent = stats.pendientes;
      document.getElementById('bebidas-preparando').textContent = stats.preparando;
      document.getElementById('bebidas-listas').textContent = stats.listas;
      document.getElementById('mesas-activas-bar').textContent = stats.mesasActivas;

      let html = '';
      
      if (Object.keys(mesas).length === 0) {
        html = `
          <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500/20 to-emerald-600/20 rounded-2xl mb-4">
              <i class="bi bi-check-circle text-green-400 text-2xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-white mb-2">¡Todo al día!</h3>
            <p class="text-gray-400">No hay órdenes de bebidas pendientes</p>
          </div>
        `;
      } else {
        for (const nombreMesa in mesas) {
          html += `
            <div class="mb-8 bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 overflow-hidden shadow-xl">
              <div class="bg-gradient-to-r from-cyan-500/20 to-blue-600/20 p-6 border-b border-dark-600/50">
                <div class="flex items-center space-x-3">
                  <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center">
                    <i class="bi bi-table text-white text-xl"></i>
                  </div>
                  <div>
                    <h3 class="text-xl font-bold text-white">${nombreMesa}</h3>
                    <p class="text-gray-300">Mesa del restaurante</p>
                  </div>
                </div>
              </div>
              
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-dark-600/50">
                    <tr>
                      <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">Bebida</th>
                      <th class="px-4 py-4 text-center text-sm font-semibold text-gray-300">Cantidad</th>
                      <th class="px-4 py-4 text-center text-sm font-semibold text-green-400">Preparado</th>
                      <th class="px-4 py-4 text-center text-sm font-semibold text-red-400">Cancelado</th>
                      <th class="px-4 py-4 text-center text-sm font-semibold text-yellow-400">Faltan</th>
                      <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">Acción</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-dark-600/50">
          `;
          
          mesas[nombreMesa].forEach(item => {
            html += `
              <tr class="hover:bg-dark-600/30 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500/20 to-blue-600/20 rounded-lg flex items-center justify-center">
                      <i class="bi bi-cup-straw text-cyan-400"></i>
                    </div>
                    <div>
                      <p class="text-white font-medium">${item.producto}</p>
                      <p class="text-gray-400 text-sm">Orden: #${item.op_id}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-500/20 text-blue-400 rounded-full text-sm font-bold">
                    ${item.cantidad}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-flex items-center justify-center w-8 h-8 bg-green-500/20 text-green-400 rounded-full text-sm font-bold">
                    ${item.preparado}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-flex items-center justify-center w-8 h-8 bg-red-500/20 text-red-400 rounded-full text-sm font-bold">
                    ${item.cancelado}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-flex items-center justify-center w-8 h-8 bg-yellow-500/20 text-yellow-400 rounded-full text-sm font-bold">
                    ${item.faltan}
                  </span>
                </td>
                <td class="px-6 py-4">
                  ${item.faltan > 0 ? `
                    <form class="marcar-preparado-form-bar flex items-center gap-3 justify-center" data-op="${item.op_id}">
                      <div class="flex items-center space-x-2">
                        <input type="number" 
                               name="marcar" 
                               value="1" 
                               min="1" 
                               max="${item.faltan}" 
                               class="w-16 px-2 py-1 bg-dark-600/50 border border-dark-500/50 rounded-lg text-white text-center focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all duration-200 transform hover:scale-105 text-sm">
                          <i class="bi bi-check-lg mr-1"></i>
                          Preparar
                        </button>
                      </div>
                    </form>
                  ` : `
                    <div class="flex items-center justify-center">
                      <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-semibold">
                        <i class="bi bi-check-circle mr-1"></i>
                        Completado
                      </span>
                    </div>
                  `}
                </td>
              </tr>
            `;
          });
          
          html += `
                  </tbody>
                </table>
              </div>
            </div>
          `;
        }
      }

      document.getElementById('bar-content').innerHTML = html;

      // Agregar event listeners para los formularios
      document.querySelectorAll('.marcar-preparado-form-bar').forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          const op_id = this.getAttribute('data-op');
          const marcar = this.querySelector('input[name="marcar"]').value;
          
          fetch('controllers/marcar_preparado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `op_id=${op_id}&marcar=${marcar}`
          })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'ok') {
              Swal.fire({
                title: '¡Excelente!',
                text: 'Bebida marcada como preparada',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
              });
              cargarBar(); // Recargar datos
            } else {
              Swal.fire('Error', data.msg || 'No se pudo marcar', 'error');
            }
          })
          .catch(err => {
            console.error('Error:', err);
            Swal.fire('Error', 'Error de conexión', 'error');
          });
        });
      });
    })
    .catch(error => {
      console.error('Error:', error);
      document.getElementById('bar-content').innerHTML = `
        <div class="bg-dark-700/30 backdrop-blur-xl rounded-2xl border border-dark-600/50 p-12 text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-500/20 to-pink-600/20 rounded-2xl mb-4">
            <i class="bi bi-exclamation-triangle text-red-400 text-2xl"></i>
          </div>
          <h3 class="text-xl font-semibold text-white mb-2">Error al cargar datos</h3>
          <p class="text-gray-400 mb-4">No se pudieron obtener las órdenes del bar</p>
          <button onclick="cargarBar()" class="px-4 py-2 bg-cyan-500 text-white rounded-xl hover:bg-cyan-600 transition-colors">
            <i class="bi bi-arrow-clockwise mr-2"></i>
            Reintentar
          </button>
        </div>
      `;
    });
}

// Auto refresh every 30 seconds
function startAutoRefreshBar() {
  refreshIntervalBar = setInterval(cargarBar, 30000);
}

function stopAutoRefreshBar() {
  if (refreshIntervalBar) {
    clearInterval(refreshIntervalBar);
  }
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', function() {
  cargarBar();
  startAutoRefreshBar();
});

// Stop refresh when page is hidden
document.addEventListener('visibilitychange', function() {
  if (document.hidden) {
    stopAutoRefreshBar();
  } else {
    startAutoRefreshBar();
  }
});
</script>