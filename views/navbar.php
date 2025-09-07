<?php
// Asegurar que $userInfo esté disponible
if (!isset($userInfo) || !$userInfo) {
  if (function_exists('getUserInfo')) {
    $userInfo = getUserInfo();
  }
  
  if (!$userInfo) {
    $userInfo = [
      'username' => 'Usuario',
      'rol' => 'Sin rol',
      'permisos' => []
    ];
  }
}
?>

<!-- Professional Fixed Navbar -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-dark-900/98 backdrop-blur-lg border-b border-dark-700/30 shadow-xl">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <!-- Logo Section -->
      <div class="flex items-center space-x-3">
        <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
          <i class="bi bi-lightning-charge text-white text-lg"></i>
        </div>
        <div>
          <h1 class="text-lg font-montserrat-bold text-white">Kalli Jaguar POS</h1>
        </div>
      </div>

      <!-- Desktop Navigation -->
      <div class="hidden md:flex items-center space-x-1">

        <!-- 🍽️ Mesas -->
        <?php if (hasPermission('mesas', 'ver')): ?>
          <a href="index.php?page=mesas"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-grid-3x3 text-blue-400 mr-2"></i>
            Mesas
          </a>
        <?php endif; ?>

        <!-- 📋 Ordenes -->
        <?php if (hasPermission('ordenes', 'ver')): ?>
          <a href="index.php?page=ordenes"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-receipt text-green-400 mr-2"></i>
            Órdenes
          </a>
        <?php endif; ?>

        <!-- 🛍️ Catálogo -->
        <?php if (hasPermission('productos', 'ver')): ?>
          <a href="index.php?page=productos"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-bag text-purple-400 mr-2"></i>
            Catálogo
          </a>
        <?php endif; ?>

        <!-- 🔥 Cocina -->
        <?php if (hasPermission('cocina', 'ver')): ?>
          <a href="index.php?page=cocina"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-fire text-orange-400 mr-2"></i>
            Cocina
          </a>
        <?php endif; ?>

        <!-- 🍹 Bar -->
        <?php if (hasPermission('bar', 'ver')): ?>
          <a href="index.php?page=bar"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-cup-straw text-cyan-400 mr-2"></i>
            Bar
          </a>
        <?php endif; ?>

        <!-- 📊 Reportes -->
        <?php if (hasPermission('reportes', 'ver')): ?>
          <a href="index.php?page=reportes"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-graph-up text-pink-400 mr-2"></i>
            Reportes
          </a>
        <?php endif; ?>

        <!-- 🔐 Autorizaciones -->
        <?php if (hasPermission('configuracion', 'ver')): ?>
          <a href="index.php?page=autorizaciones"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-shield-exclamation text-red-400 mr-2"></i>
            Autorizaciones
          </a>
        <?php endif; ?>

      </div>

      <!-- User Menu & Mobile Button -->
      <div class="flex items-center space-x-3">

        <!-- Fullscreen Button -->
        <button onclick="toggleFullScreen()" type="button"
          class="hidden sm:flex items-center justify-center p-2 w-9 h-9 text-gray-400 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200 border border-dark-600/30"
          title="Pantalla completa">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M8 3H5a2 2 0 0 0-2 2v3m0 8v3a2 2 0 0 0 2 2h3m8-16h3a2 2 0 0 1 2 2v3m0 8v3a2 2 0 0 1-2 2h-3">
            </path>
          </svg>
        </button>

        <!-- User Dropdown -->
        <div class="relative">
          <button id="user-menu-button"
            class="flex items-center space-x-2 px-3 py-2 bg-dark-700/50 hover:bg-dark-600/50 rounded-lg transition-all duration-200 border border-dark-600/30">
            <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-purple-600 rounded-md flex items-center justify-center">
              <i class="bi bi-person text-white text-sm"></i>
            </div>
            <div class="hidden sm:block text-left">
              <p class="text-sm font-montserrat-medium text-white leading-tight"><?= htmlspecialchars($userInfo['username']) ?></p>
              <p class="text-xs text-gray-400 leading-tight"><?= htmlspecialchars($userInfo['rol']) ?></p>
            </div>
            <i class="bi bi-chevron-down text-gray-400 text-xs"></i>
          </button>

          <!-- Dropdown Menu -->
          <div id="user-dropdown"
            class="absolute right-0 mt-2 w-48 bg-dark-800/95 backdrop-blur-xl rounded-xl shadow-2xl border border-dark-700/50 py-2 opacity-0 invisible transform scale-95 transition-all duration-200 origin-top-right">
            <a href="index.php?page=configuracion"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-dark-700/50 hover:text-white transition-colors">
              <i class="bi bi-gear mr-3 text-gray-400 w-4"></i>
              Configuración
            </a>
            <a href="index.php?page=usuarios"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-dark-700/50 hover:text-white transition-colors">
              <i class="bi bi-people mr-3 text-gray-400 w-4"></i>
              Usuarios
            </a>
            <hr class="my-2 border-dark-700/50">
            <a href="#" onclick="logout(); return false;"
              class="flex items-center px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors">
              <i class="bi bi-box-arrow-right mr-3 w-4"></i>
              Cerrar Sesión
            </a>
          </div>
        </div>

        <!-- Mobile Menu Button -->
        <button id="mobile-menu-button"
          class="md:hidden p-2 text-gray-400 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
          <i class="bi bi-list text-xl"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Navigation Menu -->
  <div id="mobile-menu"
    class="md:hidden bg-dark-800/98 backdrop-blur-lg border-t border-dark-700/30 hidden">
    <div class="px-4 py-3 space-y-1">

      <?php if (hasPermission('mesas', 'ver')): ?>
        <a href="index.php?page=mesas"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-grid-3x3 text-blue-400 mr-3 w-5"></i>
          Mesas
        </a>
      <?php endif; ?>

      <?php if (hasPermission('ordenes', 'ver')): ?>
        <a href="index.php?page=ordenes"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-receipt text-green-400 mr-3 w-5"></i>
          Órdenes
        </a>
      <?php endif; ?>

      <?php if (hasPermission('productos', 'ver')): ?>
        <a href="index.php?page=productos"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-bag text-purple-400 mr-3 w-5"></i>
          Catálogo
        </a>
      <?php endif; ?>

      <?php if (hasPermission('cocina', 'ver')): ?>
        <a href="index.php?page=cocina"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-fire text-orange-400 mr-3 w-5"></i>
          Cocina
        </a>
      <?php endif; ?>

      <?php if (hasPermission('bar', 'ver')): ?>
        <a href="index.php?page=bar"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-cup-straw text-cyan-400 mr-3 w-5"></i>
          Bar
        </a>
      <?php endif; ?>

      <?php if (hasPermission('reportes', 'ver')): ?>
        <a href="index.php?page=reportes"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-graph-up text-pink-400 mr-3 w-5"></i>
          Reportes
        </a>
      <?php endif; ?>

      <?php if (hasPermission('configuracion', 'ver')): ?>
        <a href="index.php?page=autorizaciones"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-shield-exclamation text-red-400 mr-3 w-5"></i>
          Autorizaciones
        </a>
      <?php endif; ?>

    </div>
  </div>

</nav>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
      mobileMenuButton.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
      });
    }

    // User dropdown toggle
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');

    if (userMenuButton && userDropdown) {
      userMenuButton.addEventListener('click', function(e) {
        e.stopPropagation();

        if (userDropdown.classList.contains('opacity-0')) {
          userDropdown.classList.remove('opacity-0', 'invisible', 'scale-95');
          userDropdown.classList.add('opacity-100', 'visible', 'scale-100');
        } else {
          userDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
          userDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
        }
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function() {
        userDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
        userDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
      });
    }
  });

  document.addEventListener('DOMContentLoaded', function() {
    // Navigation toggle for mobile
    const toggle = document.getElementById('navbar-toggle');
    const menu = document.getElementById('navbar-menu');

    if (toggle && menu) {
      toggle.addEventListener('click', function() {
        const expanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !expanded);
        menu.classList.toggle('hidden');
        menu.classList.toggle('block');
      });
    }

    // User dropdown toggle
    const userButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');

    if (userButton && userDropdown) {
      userButton.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function() {
        userDropdown.classList.add('hidden');
      });

      userDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }
  });

  function toggleFullScreen() {
    if (
      !document.fullscreenElement &&
      !document.mozFullScreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement
    ) {
      var d = document.documentElement;
      if (d.requestFullscreen) d.requestFullscreen();
      else if (d.mozRequestFullScreen) d.mozRequestFullScreen();
      else if (d.webkitRequestFullscreen) d.webkitRequestFullscreen();
      else if (d.msRequestFullscreen) d.msRequestFullscreen();
    } else {
      if (document.exitFullscreen) document.exitFullscreen();
      else if (document.mozCancelFullScreen) document.mozCancelFullScreen();
      else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
      else if (document.msExitFullscreen) document.msExitFullscreen();
    }
  }

  async function logout() {
    try {
      const response = await fetch('auth/logout.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();

      if (result.success) {
        window.location.href = 'login.php?message=logout_success';
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: result.message || 'Error al cerrar sesión'
        });
      }
    } catch (error) {
      console.error('Error:', error);
      window.location.href = 'login.php';
    }
  }

  function changePassword() {
    // Implementar modal de cambio de contraseña
    alert('Funcionalidad de cambio de contraseña próximamente...');
  }
</script>