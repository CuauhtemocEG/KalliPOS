<?php
// Nota: auth-check.php y conexion.php ya están incluidos en index.php
// $pdo ya está disponible desde index.php

if (!hasPermission('ordenes', 'crear') && !hasPermission('ordenes', 'ver')) {
    header('Location: index.php?page=error-403');
    exit;
}

$userInfo = getUserInfo();
$esAdministrador = ($userInfo['rol'] === 'administrador');
$mesa_id = intval($_GET['id'] ?? 0);

if ($mesa_id <= 0) {
    header('Location: index.php?page=mesas&error=mesa_invalida');
    exit;
}

$mesa = $pdo->query("SELECT * FROM mesas WHERE id=$mesa_id")->fetch();

// Validar que la mesa exista
if (!$mesa) {
    header('Location: index.php?page=mesas&error=mesa_no_encontrada');
    exit;
}

$orden = $pdo->query("SELECT * FROM ordenes WHERE mesa_id=$mesa_id AND estado='abierta'")->fetch();
$orden_id = $orden ? $orden['id'] : 0;

// Obtener configuración de impresión térmica
include_once 'includes/ConfiguracionSistema.php';
$config = new ConfiguracionSistema($pdo);
$config_impresion = $config->obtenerTodasConfiguraciones();
$impresion_automatica = ($config_impresion['impresion_automatica'] ?? '0') == '1';
$impresora_configurada = !empty($config_impresion['nombre_impresora'] ?? '');
$metodo_impresion = $config_impresion['metodo_impresion'] ?? 'navegador';
?>

<!-- Estilos específicos para mesa.php -->
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .aspect-square {
        aspect-ratio: 1 / 1;
    }

    .product-card:hover {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    /* Estilos para scrollbars personalizados */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #1e293b;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #475569;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    /* Área de scroll para la orden actual */
    .orden-scroll-area {
        max-height: calc(100vh - 450px);
        min-height: 250px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #475569 #1e293b;
        padding-right: 4px;
    }

    /* Área de scroll para el catálogo de productos */
    .catalogo-scroll-area {
        max-height: calc(100vh - 300px);
        min-height: 350px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #475569 #1e293b;
        padding-right: 4px;
    }

    /* Estilos para Firefox */
    .orden-scroll-area {
        scrollbar-width: thin;
        scrollbar-color: #475569 #1e293b;
    }

    .catalogo-scroll-area {
        scrollbar-width: thin;
        scrollbar-color: #475569 #1e293b;
    }

    /* Indicador visual de scroll */
    .scroll-fade-top {
        background: linear-gradient(to bottom, rgba(30, 41, 59, 0.9) 0%, transparent 100%);
        height: 15px;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        pointer-events: none;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 12px 12px 0 0;
    }

    .scroll-fade-bottom {
        background: linear-gradient(to top, rgba(30, 41, 59, 0.9) 0%, transparent 100%);
        height: 15px;
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        pointer-events: none;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 0 0 12px 12px;
    }

    /* Responsive adjustments */
    @media (max-width: 1279px) {
        .orden-scroll-area {
            max-height: 350px;
            min-height: 200px;
        }
        .catalogo-scroll-area {
            max-height: 450px;
            min-height: 300px;
        }
    }

    @media (max-width: 768px) {
        .orden-scroll-area {
            max-height: 300px;
            min-height: 150px;
        }
        .catalogo-scroll-area {
            max-height: 400px;
            min-height: 250px;
        }
    }

    /* Smooth scrolling para toda la página */
    html {
        scroll-behavior: smooth;
    }

    /* Mejor espaciado en dispositivos móviles */
    @media (max-width: 640px) {
        .catalogo-scroll-area .grid {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
    }

    @media (min-width: 641px) and (max-width: 1023px) {
        .catalogo-scroll-area .grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<!-- Contenido de la mesa -->
<div class="w-full px-2 py-4 lg:px-4 lg:py-6 xl:px-6 xl:py-8">

    <!-- Header Mesa -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-700 rounded-2xl shadow-2xl p-6 mb-6 border border-slate-600">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-4">
                <div class="bg-blue-500 p-3 rounded-xl shadow-lg">
                    <i class="bi bi-table text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Mesa <?= htmlspecialchars($mesa['nombre']) ?></h1>
                    <p class="text-slate-300 text-sm">Sistema POS - Kalli Jaguar</p>
                </div>
            </div>
            <a href="index.php?page=mesas" class="inline-flex items-center px-4 py-2 bg-slate-600 hover:bg-slate-500 text-white rounded-xl transition-all duration-300 shadow-lg">
                <i class="bi bi-arrow-left mr-2"></i> Volver a Mesas
            </a>
        </div>
    </div>

    <?php if ($orden): ?>
        <!-- POS con Orden Abierta -->
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6" style="height: calc(100vh - 200px);">
            <!-- Panel Izquierdo (Orden) - 40% del ancho -->
            <div class="xl:col-span-2">
                <div class="bg-slate-800 rounded-2xl shadow-2xl border border-slate-600 p-6 flex flex-col h-full">
                    <h3 class="text-white font-bold text-xl mb-6"><i class="bi bi-receipt mr-2"></i> Orden Actual</h3>
                    
                    <!-- Área de scroll para la lista de productos -->
                    <div class="relative flex-1 mb-6">
                        <div class="scroll-fade-top"></div>
                        <div id="orden-lista" class="orden-scroll-area text-center text-slate-400 px-2">
                            <div class="py-8">
                                <i class="bi bi-arrow-clockwise animate-spin text-3xl mb-3"></i>
                                <p>Cargando orden...</p>
                            </div>
                        </div>
                        <div class="scroll-fade-bottom"></div>
                    </div>
                    
                    <!-- Totales fijos -->
                    <div id="orden-totales" class="bg-slate-900 rounded-xl p-5 mb-6 border border-slate-600 flex-shrink-0">
                        <div class="text-slate-400 text-center py-4">Totales...</div>
                    </div>
                    
                    <!-- Botones de acción fijos -->
                    <div class="space-y-3 flex-shrink-0">
                        <?php if ($esAdministrador): ?>
                        <button id="cancelar_orden" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-semibold transition-colors">
                            <i class="bi bi-x-circle mr-2"></i>Cancelar Orden
                        </button>
                        <?php endif; ?>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <?php if ($esAdministrador): ?>
                            <a href="<?= url('controllers/impresion_ticket.php?orden_id=' . $orden_id) ?>" target="_blank" class="block bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-center font-semibold transition-colors">
                                <i class="bi bi-printer mr-2"></i>Ticket PDF
                            </a>
                            
                            <!-- NUEVO: Generador ESC/POS para HostGator -->
                            <a href="<?= url('controllers/ticket_escpos_hostgator.php?orden_id=' . $orden_id) ?>" target="_blank" class="block bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl text-center font-semibold transition-colors">
                                <i class="bi bi-code-square mr-2"></i>ESC/POS
                                <div class="text-xs opacity-75 mt-1">Para HostGator</div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($metodo_impresion === 'navegador'): ?>
                            <div class="<?= $esAdministrador ? '' : 'col-span-3' ?>">
                                <!-- Sistema Híbrido USB + Navegador -->
                                <button onclick="imprimirTicketLocal(<?= $orden_id ?>)" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-center font-semibold transition-colors">
                                    <i class="bi bi-lightning mr-2"></i>🖨️ Imprimir Ticket
                                    <div class="text-xs opacity-75 mt-1">USB/ESC-POS + Navegador</div>
                                </button>
                            </div>
                        <?php elseif ($impresora_configurada): ?>
                            <div class="<?= $esAdministrador ? '' : 'col-span-3' ?>">
                                <!-- Sistema Híbrido USB + Navegador -->
                                <button onclick="imprimirTicketLocal(<?= $orden_id ?>)" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-center font-semibold transition-colors">
                                    <i class="bi bi-lightning mr-2"></i>🖨️ Imprimir Ticket
                                    <div class="text-xs opacity-75 mt-1">USB/ESC-POS + Navegador</div>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="<?= $esAdministrador ? '' : 'col-span-2' ?>">
                                <!-- Enlace a configuración -->
                                <a href="index.php?page=configuracion&tab=impresoras" class="block bg-slate-600 hover:bg-slate-700 text-white py-3 rounded-xl text-center font-semibold transition-colors">
                                    <i class="bi bi-gear mr-2"></i>Configurar
                                    <div class="text-xs opacity-75 mt-1">Impresora Térmica</div>
                                </a>
                            </div>
                        <?php endif; ?>
                        </div>
                        
                        <?php if (!$impresion_automatica || !$impresora_configurada): ?>
                            <!-- Mostrar enlace a configuración si no está configurada la impresión automática -->
                            <div class="text-center">
                                <a href="index.php?page=configuracion&tab=impresoras" class="text-slate-400 hover:text-white text-sm transition-colors">
                                    <i class="bi bi-gear mr-1"></i>
                                    <?php if (!$impresora_configurada): ?>
                                        Configurar impresora térmica
                                    <?php else: ?>
                                        Activar impresión automática
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Botón de configuración de impresora híbrida -->
                        <div class="text-center mt-2">
                            <button onclick="configurarImpresora()" class="text-blue-400 hover:text-blue-300 text-sm transition-colors">
                                <i class="bi bi-printer mr-1"></i>Configurar Impresora USB
                            </button>
                            <button onclick="imprimirPruebaLocal()" class="text-green-400 hover:text-green-300 text-sm transition-colors ml-4">
                                <i class="bi bi-lightning mr-1"></i>Prueba Impresión
                            </button>
                        </div>
                        <form method="post" action="controllers/cerrar_orden.php" id="cerrar-orden-form">
                            <input type="hidden" name="orden_id" value="<?= $orden['id'] ?>">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-semibold transition-colors">
                                <i class="bi bi-cash-coin mr-2"></i>Cerrar y Pagar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Panel Derecho (Catálogo) - 60% del ancho -->
            <div class="xl:col-span-3" id="divProductos">
                <div class="bg-slate-800 rounded-2xl shadow-2xl border border-slate-600 p-6 flex flex-col h-full">
                    <h3 class="text-white font-bold text-xl mb-6"><i class="bi bi-grid-3x3-gap mr-2"></i> Catálogo de Productos</h3>
                    
                    <!-- Buscador fijo -->
                    <div class="mb-6 flex-shrink-0">
                        <input type="text" id="buscador" class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="🔍 Buscar producto...">
                    </div>
                    
                    <!-- Categorías fijas -->
                    <div id="categorias" class="flex flex-wrap gap-2 mb-6 flex-shrink-0"></div>
                    
                    <!-- Área de scroll para productos -->
                    <div class="relative flex-1">
                        <div class="scroll-fade-top"></div>
                        <div id="productos" class="catalogo-scroll-area grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 px-2"></div>
                        <div class="scroll-fade-bottom"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Estado sin Orden -->
        <div class="bg-slate-800 rounded-2xl shadow-2xl border border-slate-600 p-8 text-center">
            <h3 class="text-white text-xl font-bold mb-2">No hay orden abierta</h3>
            <form method="post" action="controllers/nueva_orden.php">
                <input type="hidden" name="mesa_id" value="<?= $mesa_id ?>">
                <button type="submit" class="bg-green-600 text-white py-3 px-6 rounded-xl">Abrir Nueva Orden</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    const mesaId = <?= $mesa_id ?>;
    const ordenId = <?= $orden_id ?>;
    const esAdministrador = <?= $esAdministrador ? 'true' : 'false' ?>;
    
    // Configuración de URLs
    const BASE_URL = '<?= getBaseUrl() ?>';
    const CONTROLLERS_URL = '<?= url('controllers/') ?>';
    const ASSETS_URL = '<?= ASSETS_URL ?>';
    const API_URL = '<?= API_BASE_URL ?>';

    /** 🔹 Funciones globales para manejo de efectivo y cambio */
    function toggleEfectivoFields() {
        const efectivoSelected = document.querySelector('input[name="metodo_pago"]:checked').value === 'efectivo';
        const efectivoFields = document.getElementById('efectivo-fields');
        
        if (efectivoSelected) {
            efectivoFields.style.display = 'block';
            // Enfocar el campo de dinero recibido
            setTimeout(() => {
                const dineroInput = document.getElementById('dinero-recibido');
                if (dineroInput) {
                    dineroInput.focus();
                }
            }, 100);
        } else {
            efectivoFields.style.display = 'none';
            // Limpiar campos cuando se cambia a tarjeta
            const dineroInput = document.getElementById('dinero-recibido');
            const cambioDisplay = document.getElementById('cambio-display');
            const cambioError = document.getElementById('cambio-error');
            
            if (dineroInput) dineroInput.value = '';
            if (cambioDisplay) cambioDisplay.style.display = 'none';
            if (cambioError) cambioError.style.display = 'none';
        }
    }

    function calcularCambio(total) {
        const dineroRecibido = parseFloat(document.getElementById('dinero-recibido').value) || 0;
        const cambioDisplay = document.getElementById('cambio-display');
        const cambioAmount = document.getElementById('cambio-amount');
        const cambioError = document.getElementById('cambio-error');
        
        if (dineroRecibido <= 0) {
            // No mostrar nada si no hay valor
            if (cambioDisplay) cambioDisplay.style.display = 'none';
            if (cambioError) cambioError.style.display = 'none';
            return;
        }
        
        if (dineroRecibido < total) {
            // Mostrar error si el dinero es insuficiente
            if (cambioDisplay) cambioDisplay.style.display = 'none';
            if (cambioError) {
                cambioError.style.display = 'block';
                cambioError.innerHTML = '<p class="text-sm">Faltan $' + (total - dineroRecibido).toFixed(2) + ' para completar el pago</p>';
            }
        } else {
            // Calcular y mostrar el cambio
            const cambio = dineroRecibido - total;
            if (cambioError) cambioError.style.display = 'none';
            if (cambioDisplay) cambioDisplay.style.display = 'block';
            
            if (cambioAmount) {
                if (cambio === 0) {
                    cambioAmount.textContent = 'Pago exacto';
                    cambioAmount.className = 'text-xl font-bold text-blue-700';
                } else {
                    cambioAmount.textContent = '$' + cambio.toFixed(2);
                    cambioAmount.className = 'text-xl font-bold text-green-700';
                }
            }
        }
    }

    // Verificar mensajes de impresión automática
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('impresion_exitosa') === '1') {
            const mensaje = urlParams.get('mensaje') || 'Ticket impreso correctamente';
            Swal.fire({
                icon: 'success',
                title: '¡Impresión Exitosa!',
                text: mensaje,
                confirmButtonColor: '#16a34a'
            });
            
            // Limpiar URL
            window.history.replaceState({}, document.title, window.location.pathname + '?id=' + mesaId);
        }
        
        if (urlParams.get('impresion_error') === '1') {
            const mensaje = urlParams.get('mensaje') || 'Error al imprimir ticket';
            Swal.fire({
                icon: 'error',
                title: 'Error de Impresión',
                text: mensaje,
                confirmButtonColor: '#dc2626'
            });
            
            // Limpiar URL
            window.history.replaceState({}, document.title, window.location.pathname + '?id=' + mesaId);
        }
    });

    /** 🔹 Cargar Categorías */
    function cargarCategorias() {
        fetch(CONTROLLERS_URL + 'categorias.php')
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                let html = '<button class="category-btn px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all duration-300 transform hover:-translate-y-0.5 shadow-lg text-sm font-medium" data-cat="0">' +
                    '<i class="bi bi-grid-3x3-gap mr-1"></i> Todos' +
                    '</button>';
                data.forEach(function(cat) {
                    html += '<button class="category-btn px-4 py-2 bg-slate-600 hover:bg-slate-500 text-white rounded-lg transition-all duration-300 transform hover:-translate-y-0.5 shadow-lg text-sm font-medium" data-cat="' + cat.id + '">' +
                        '<i class="bi bi-tag mr-1"></i> ' + cat.nombre +
                        '</button>';
                });
                document.getElementById('categorias').innerHTML = html;

                // Aplicar eventos a los botones
                document.querySelectorAll('.category-btn').forEach(function(btn) {
                    btn.onclick = function() {
                        // Remover la clase activa de todos los botones
                        document.querySelectorAll('.category-btn').forEach(function(b) {
                            b.classList.remove('bg-purple-600', 'bg-purple-700');
                            b.classList.add('bg-slate-600');
                            b.classList.remove('ring-2', 'ring-purple-400');
                        });

                        // Agregar la clase activa al botón clickeado
                        this.classList.remove('bg-slate-600');
                        this.classList.add('bg-purple-600', 'hover:bg-purple-700', 'ring-2', 'ring-purple-400');

                        // Cargar productos de la categoría seleccionada
                        cargarProductos(this.getAttribute('data-cat'), document.getElementById('buscador').value);
                    };
                });

                // Activar el primer botón (Todos) por defecto
                const firstBtn = document.querySelector('.category-btn[data-cat="0"]');
                if (firstBtn) {
                    firstBtn.classList.remove('bg-slate-600');
                    firstBtn.classList.add('bg-purple-600', 'hover:bg-purple-700', 'ring-2', 'ring-purple-400');
                }
            });
    }

    /** 🔹 Cargar Productos */
    function cargarProductos(cat_id, q) {
        if (cat_id === undefined) cat_id = 0;
        if (q === undefined) q = '';

        fetch(CONTROLLERS_URL + 'buscar_productos.php?cat_id=' + cat_id + '&q=' + encodeURIComponent(q))
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                let html = '';
                data.forEach(function(prod) {
                    html += '<div class="product-card group bg-gradient-to-br from-slate-700 to-slate-600 rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 cursor-pointer border border-slate-500 hover:border-blue-400" onclick="agregarProductoMesa(' + prod.id + ')">' +
                        '<div class="aspect-square overflow-hidden bg-slate-800">' +
                        '<img src="' + ASSETS_URL + 'img/' + (prod.imagen || 'noimg.png') + '" ' +
                        'alt="' + prod.nombre + '"' +
                        'class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"' +
                        'onerror="this.src=\'assets/img/noimg.png\'">' +
                        '</div>' +
                        '<div class="p-4">' +
                        '<h4 class="text-white font-semibold text-base mb-2 group-hover:text-blue-300 transition-colors line-clamp-2 min-h-[3rem]">' + prod.nombre + '</h4>' +
                        '<div class="flex items-center justify-between">' +
                        '<span class="text-green-400 font-bold text-xl">$' + Number(prod.precio).toFixed(2) + '</span>' +
                        '<div class="bg-blue-600 group-hover:bg-blue-500 text-white p-2 rounded-lg transition-colors">' +
                        '<i class="bi bi-plus-lg text-lg"></i>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                });

                if (!html) {
                    html = '<div class="col-span-full text-center py-16">' +
                        '<div class="bg-slate-600 p-6 rounded-full w-24 h-24 mx-auto mb-6 flex items-center justify-center">' +
                        '<i class="bi bi-search text-white text-3xl"></i>' +
                        '</div>' +
                        '<p class="text-slate-300 text-xl mb-2">No se encontraron productos</p>' +
                        '<p class="text-slate-400 text-sm">Intenta con otros términos de búsqueda</p>' +
                        '<p class="text-slate-500 text-xs mt-4">O selecciona una categoría diferente</p>' +
                        '</div>';
                }

                document.getElementById('productos').innerHTML = html;
                
                // Refrescar indicadores de scroll después de cargar productos
                setTimeout(refreshScrollIndicators, 100);
            });
    }

    /** 🔹 Agregar producto */
    function agregarProductoMesa(producto_id) {
        fetch(CONTROLLERS_URL + 'newPos/agregar_producto_orden.php', {
                method: 'POST',
                body: new URLSearchParams({
                    producto_id: producto_id,
                    cantidad: 1,
                    orden_id: ordenId
                }),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(resp) {
                if (resp.status === "ok") {
                    cargarOrden();
                    Swal.fire({
                        icon: 'success',
                        text: 'Producto agregado',
                        timer: 1200,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', resp.msg || 'No se pudo agregar', 'error');
                }
            });
    }

    /** 🔹 Determinar Estado Visual del Producto */
    function getEstadoProducto(cantidad, preparado, cancelado) {
        const pendientes = cantidad - preparado - cancelado;

        if (cancelado > 0) {
            return {
                cardClass: 'bg-red-900/30 rounded-xl p-4 border border-red-600/50 opacity-75',
                titleClass: 'text-red-300 font-semibold text-sm flex-1 pr-2 line-through',
                badge: '<span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">CANCELADO</span>',
                detalles: null,
                detallesClass: null
            };
        }

        if (preparado === 0 && pendientes > 0) {
            // Todo pendiente - naranja "Preparando"
            return {
                cardClass: 'bg-orange-900/30 rounded-xl p-4 border border-orange-500/50',
                titleClass: 'text-orange-100 font-semibold text-sm flex-1 pr-2',
                badge: '<span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full animate-pulse">PREPARANDO</span>',
                detalles: pendientes + ' unidad(es) en preparación',
                detallesClass: 'text-orange-300'
            };
        }

        if (preparado > 0 && pendientes > 0) {
            // Parcialmente preparado - verde con detalles
            return {
                cardClass: 'bg-gradient-to-r from-green-900/30 to-orange-900/30 rounded-xl p-4 border border-green-500/50',
                titleClass: 'text-green-100 font-semibold text-sm flex-1 pr-2',
                badge: '<span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">PARCIAL</span>',
                detalles: preparado + ' preparado(s), ' + pendientes + ' preparando',
                detallesClass: 'text-green-300'
            };
        }

        if (preparado > 0 && pendientes === 0) {
            // Todo preparado - verde completo
            return {
                cardClass: 'bg-green-900/30 rounded-xl p-4 border border-green-500/50',
                titleClass: 'text-green-100 font-semibold text-sm flex-1 pr-2',
                badge: '<span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full"><i class="bi bi-check-circle mr-1"></i>PREPARADO</span>',
                detalles: preparado + ' unidad(es) listas',
                detallesClass: 'text-green-300'
            };
        }

        // Estado por defecto
        return {
            cardClass: 'bg-slate-900 rounded-xl p-4 border border-slate-600 hover:border-slate-500 transition-colors',
            titleClass: 'text-white font-semibold text-sm flex-1 pr-2',
            badge: '',
            detalles: null,
            detallesClass: null
        };
    }

    /** 🔹 Función para mostrar confirmación de cancelación total */
    function mostrarConfirmacionCancelacion(ordenProductoId, productoNombre, cantidadCancelar, preparado) {
        let contextMessage = '';
        if (preparado > 0) {
            contextMessage = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">' +
                '<p class="text-green-700 text-sm">' +
                '<i class="bi bi-check-circle mr-1"></i>' +
                '<strong>' + preparado + ' unidad(es) ya preparada(s)</strong> - No se pueden cancelar' +
                '</p>' +
                '</div>';
        }

        Swal.fire({
            title: 'Confirmar cancelación total',
            html: '<div class="text-left">' +
                '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                contextMessage +
                '<div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-3">' +
                '<p class="text-red-700 text-sm">' +
                '<i class="bi bi-x-circle mr-1"></i>' +
                '<strong>' + cantidadCancelar + ' unidad(es) se cancelarán</strong>' +
                '</p>' +
                '</div>' +
                '<p class="mb-3 text-sm text-gray-600">Se enviará un PIN al administrador por Email</p>' +
                '<textarea id="razon-cancelacion" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" rows="3" placeholder="Motivo de la cancelación (opcional)"></textarea>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Cancelar ' + cantidadCancelar + ' unidad(es)',
            cancelButtonText: 'No cancelar',
            confirmButtonColor: '#ef4444',
            preConfirm: function() {
                return document.getElementById('razon-cancelacion').value;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                const razon = result.value || ('Cancelación total de ' + cantidadCancelar + ' unidades');
                mostrarModalCancelacion(ordenProductoId, productoNombre, cantidadCancelar, razon);
            }
        });
    }

    /** 🔹 Función para mostrar selector de cantidad parcial */
    function mostrarSelectorCantidad(ordenProductoId, productoNombre, pendientes, preparado) {
        let contextMessage = '';
        if (preparado > 0) {
            contextMessage = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">' +
                '<p class="text-green-700 text-sm">' +
                '<i class="bi bi-check-circle mr-1"></i>' +
                '<strong>' + preparado + ' unidad(es) ya preparada(s)</strong> - No se pueden cancelar' +
                '</p>' +
                '</div>';
        }

        Swal.fire({
            title: 'Cancelación parcial',
            html: '<div class="text-left">' +
                '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                contextMessage +
                '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">' +
                '<p class="text-orange-700 text-sm">' +
                '<i class="bi bi-clock mr-1"></i>' +
                '<strong>' + pendientes + ' unidad(es) disponibles</strong> para cancelar' +
                '</p>' +
                '</div>' +
                '<div class="mb-3">' +
                '<label class="block text-sm font-medium text-gray-700 mb-2">¿Cuántas unidades deseas cancelar?</label>' +
                '<input type="number" id="cantidad-cancelar" min="1" max="' + pendientes + '" value="1" ' +
                'class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">' +
                '</div>' +
                '<p class="mb-3 text-sm text-gray-600">Se enviará un PIN al administrador por Email</p>' +
                '<textarea id="razon-cancelacion" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" rows="3" placeholder="Motivo de la cancelación (opcional)"></textarea>' +
                '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Cancelar unidades seleccionadas',
            cancelButtonText: 'No cancelar',
            confirmButtonColor: '#f59e0b',
            preConfirm: function() {
                const cantidadInput = document.getElementById('cantidad-cancelar');
                const razonInput = document.getElementById('razon-cancelacion');
                const valor = parseInt(cantidadInput.value);

                if (!valor || valor < 1 || valor > pendientes) {
                    Swal.showValidationMessage('Cantidad inválida. Debe ser entre 1 y ' + pendientes);
                    return false;
                }

                return {
                    cantidad: valor,
                    razon: razonInput.value || ('Cancelación parcial de ' + valor + ' unidad(es)')
                };
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                mostrarModalCancelacion(ordenProductoId, productoNombre, result.value.cantidad, result.value.razon);
            }
        });
    }

    function mostrarModalCancelacion(ordenProductoId, productoNombre, cantidad, motivoPredefinido) {
        if (motivoPredefinido === undefined) motivoPredefinido = null;

        if (motivoPredefinido) {
            // Si ya tenemos el motivo, enviar directamente
            enviarSolicitudCancelacion(ordenProductoId, ordenId, cantidad, motivoPredefinido);
            return;
        }

        Swal.fire({
            title: 'Motivo de cancelación',
            html: '<div class="text-left">' +
                '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                '<p class="mb-3">Cantidad a cancelar: <strong>' + cantidad + ' unidad(es)</strong></p>' +
                '<p class="mb-3 text-sm text-gray-600">Se enviará un PIN al administrador por Email y en Autorizaciones</p>' +
                '<textarea id="razon-cancelacion" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" rows="3" placeholder="Motivo de la cancelación (opcional)"></textarea>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Solicitar cancelación',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#f59e0b',
            preConfirm: function() {
                return document.getElementById('razon-cancelacion').value;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                const razon = result.value || 'Sin motivo especificado';
                enviarSolicitudCancelacion(ordenProductoId, ordenId, cantidad, razon);
            }
        });
    }

    function enviarSolicitudCancelacion(ordenProductoId, ordenId, cantidad, razon) {
        Swal.fire({
            title: 'Enviando solicitud...',
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });

        fetch(CONTROLLERS_URL + 'newPos/solicitar_cancelacion.php', {
                method: 'POST',
                body: new URLSearchParams({
                    orden_producto_id: ordenProductoId,
                    orden_id: ordenId,
                    cantidad_cancelar: cantidad,
                    razon: razon
                }),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Solicitud enviada',
                        html: '<div class="text-center">' +
                            '<p class="mb-2">' + data.message + '</p>' +
                            '<p class="text-sm text-gray-600">Cantidad solicitada: ' + cantidad + ' unidad(es)</p>' +
                            '<div class="mt-3 p-2 bg-blue-50 rounded-lg">' +
                            '<p class="text-blue-700 text-xs">' +
                            '<i class="bi bi-envelope mr-1"></i>' +
                            'PIN enviado por email al administrador' +
                            '</p>' +
                            '</div>' +
                            '</div>',
                        timer: 4000
                    });
                    cargarOrden(); // Recargar la orden para mostrar cambios
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Ocurrió un error al enviar la solicitud', 'error');
            });
    }

    /** 🔹 Cargar Orden */
    function cargarOrden() {
        fetch(CONTROLLERS_URL + 'newPos/orden_actual.php?orden_id=' + ordenId)
            .then(function(r) {
                if (!r.ok) {
                    throw new Error('Error HTTP: ' + r.status);
                }
                return r.json();
            })
            .then(function(data) {
                if (!data.items || data.items.length === 0) {
                    document.getElementById('orden-lista').innerHTML = '<div class="text-center py-8 text-slate-400">' +
                        '<i class="bi bi-cart-x text-4xl mb-3"></i>' +
                        '<p>No hay productos en la orden</p>' +
                        '<p class="text-sm mt-2">Selecciona productos del catálogo para comenzar</p>' +
                        '</div>';
                    document.getElementById('orden-totales').innerHTML = '<div class="text-slate-400 text-center py-4">' +
                        '<i class="bi bi-calculator mr-2"></i>' +
                        'Agrega productos para ver el total' +
                        '</div>';
                    return;
                }

                let html = '<div class="space-y-3">';
                data.items.forEach(function(item) {
                    let subtotal = item.precio * item.cantidad;
                    let isCancelado = item.cancelado == 1;
                    let preparado = parseInt(item.preparado || 0);
                    let cantidad = parseInt(item.cantidad || 0);
                    let cancelado = parseInt(item.cancelado || 0);
                    let pendientes = cantidad - preparado - cancelado;

                    // Determinar estado visual del producto
                    let estadoInfo = getEstadoProducto(cantidad, preparado, cancelado);

                    // Clases CSS diferentes según el estado
                    let cardClass = estadoInfo.cardClass;
                    let titleClass = estadoInfo.titleClass;

                    html += '<div class="' + cardClass + '">' +
                        '<div class="flex justify-between items-start mb-2">' +
                        '<h4 class="' + titleClass + '">' + item.nombre + '</h4>' +
                        estadoInfo.badge +
                        '</div>' +
                        '<div class="flex justify-between items-center">' +
                        '<div class="text-slate-300 text-sm">' +
                        '$' + Number(item.precio).toFixed(2) + ' c/u' +
                        '</div>' +
                        '<div class="flex items-center space-x-3">' +
                        '<div class="flex items-center space-x-2">' +
                        '<span class="text-slate-400 text-sm">Cant:</span>';

                    if (isCancelado) {
                        html += '<span class="text-red-300 text-sm">' + item.cantidad + '</span>';
                    } else {
                        html += '<input type="number" min="1" value="' + item.cantidad + '" ' +
                            'class="sale-item-qty bg-slate-800 border border-slate-600 text-white px-2 py-1 rounded w-16 text-center text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-colors" ' +
                            'data-id="' + item.id + '">';
                    }

                    html += '</div>' +
                        '<div class="' + (isCancelado ? 'text-red-400 font-bold line-through' : 'text-green-400 font-bold') + '">' +
                        '$' + subtotal.toFixed(2) +
                        '</div>' +
                        '</div>' +
                        '</div>';

                    if (estadoInfo.detalles) {
                        html += '<div class="mt-2 text-xs ' + estadoInfo.detallesClass + '">' + estadoInfo.detalles + '</div>';
                    }

                    if (!isCancelado) {
                        html += '<div class="flex justify-between items-center mt-3">';

                        if (pendientes > 0) {
                            html += '<button class="sale-item-cancel-request text-orange-400 hover:text-orange-300 px-3 py-1 border border-orange-400 rounded-lg text-xs transition-colors" ' +
                                'data-id="' + item.id + '" ' +
                                'data-nombre="' + item.nombre + '"' +
                                'data-cantidad="' + cantidad + '"' +
                                'data-preparado="' + preparado + '"' +
                                'data-cancelado="' + cancelado + '"' +
                                'title="Solicitar cancelación">' +
                                '<i class="bi bi-exclamation-triangle mr-1"></i>Cancelar' +
                                '</button>';
                        } else {
                            html += '<span class="text-green-400 text-xs">' +
                                '<i class="bi bi-check-circle mr-1"></i>Todo preparado' +
                                '</span>';
                        }

                        // Solo mostrar botón de eliminar a administradores
                        if (esAdministrador) {
                            html += '<button class="sale-item-remove text-red-400 hover:text-red-300 p-1 transition-colors" data-id="' + item.id + '" title="Eliminar producto">' +
                                '<i class="bi bi-trash text-sm"></i>' +
                                '</button>';
                        }
                        
                        html += '</div>';
                    } else {
                        html += '<div class="mt-3 text-center">' +
                            '<span class="text-red-400 text-xs">' +
                            '<i class="bi bi-x-circle mr-1"></i>Producto cancelado por autorización' +
                            '</span>' +
                            '</div>';
                    }

                    html += '</div>';
                });
                html += '</div>';
                document.getElementById('orden-lista').innerHTML = html;

                // Totales con diseño mejorado
                let resumen = '<div class="space-y-2 text-slate-300">' +
                    '<div class="flex justify-between">' +
                    '<span>Subtotal:</span>' +
                    '<span class="text-blue-400 font-semibold">$' + Number(data.subtotal).toFixed(2) + '</span>' +
                    '</div>' +
                    '<div class="flex justify-between">' +
                    '<span>Descuento:</span>' +
                    '<span class="text-green-400 font-semibold">$' + Number(data.descuento || 0).toFixed(2) + '</span>' +
                    '</div>' +
                    '<div class="flex justify-between">' +
                    '<span>Impuestos:</span>' +
                    '<span class="text-yellow-400 font-semibold">$' + Number(data.impuestos || 0).toFixed(2) + '</span>' +
                    '</div>';

                // Mostrar total cancelado si existe
                if (data.total_cancelado && data.total_cancelado > 0) {
                    resumen += '<div class="flex justify-between">' +
                        '<span class="text-red-400">Cancelado:</span>' +
                        '<span class="text-red-400 font-semibold line-through">-$' + Number(data.total_cancelado).toFixed(2) + '</span>' +
                        '</div>';
                }

                resumen += '<hr class="border-slate-600 my-3">' +
                    '<div class="flex justify-between text-lg font-bold">' +
                    '<span class="text-white">Total:</span>' +
                    '<span class="text-green-400">$' + Number(data.total).toFixed(2) + '</span>' +
                    '</div>';

                // Mostrar conteo de productos cancelados
                if (data.productos_cancelados && data.productos_cancelados.length > 0) {
                    resumen += '<div class="mt-3 p-2 bg-red-900/20 rounded-lg border border-red-600/30">' +
                        '<div class="text-red-400 text-xs text-center">' +
                        '<i class="bi bi-exclamation-triangle mr-1"></i>' +
                        data.productos_cancelados.length + ' producto(s) cancelado(s)' +
                        '</div>' +
                        '</div>';
                }

                resumen += '</div>';
                document.getElementById('orden-totales').innerHTML = resumen;
                
                // Refrescar indicadores de scroll después de actualizar la orden
                setTimeout(refreshScrollIndicators, 100);

                // Eventos para cambiar cantidad
                document.querySelectorAll('.sale-item-qty').forEach(function(input) {
                    input.onchange = function() {
                        let val = Math.max(1, parseInt(this.value));
                        this.classList.add('animate-pulse');

                        fetch(CONTROLLERS_URL + 'newPos/actualizar_producto_orden.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                producto_id: this.getAttribute('data-id'),
                                cantidad: val,
                                orden_id: ordenId
                            }),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }).then(function() {
                            input.classList.remove('animate-pulse');
                            cargarOrden();
                        }).catch(function(error) {
                            input.classList.remove('animate-pulse');
                            console.error('Error al actualizar cantidad:', error);
                            Swal.fire('Error', 'No se pudo actualizar la cantidad', 'error');
                        });
                    };
                });

                // Eventos para solicitar cancelación de producto
                document.querySelectorAll('.sale-item-cancel-request').forEach(function(btn) {
                    btn.onclick = function() {
                        const ordenProductoId = this.getAttribute('data-id'); // ID del registro orden_productos
                        const productoNombre = this.getAttribute('data-nombre');
                        const cantidad = parseInt(this.getAttribute('data-cantidad'));
                        const preparado = parseInt(this.getAttribute('data-preparado'));
                        const cancelado = parseInt(this.getAttribute('data-cancelado'));
                        const pendientes = cantidad - preparado - cancelado;

                        // Si no hay productos disponibles para cancelar
                        if (pendientes === 0) {
                            if (preparado > 0) {
                                Swal.fire({
                                    title: 'No se puede cancelar',
                                    html: '<div class="text-left">' +
                                        '<p class="mb-3">Este producto está <strong>completamente preparado</strong>.</p>' +
                                        '<p class="mb-3 text-sm text-green-600"><i class="bi bi-check-circle mr-1"></i>' + preparado + ' unidad(es) ya lista(s)</p>' +
                                        '<p class="text-sm text-gray-600">Contacta al administrador si necesitas cancelar productos preparados.</p>' +
                                        '</div>',
                                    icon: 'info',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#10b981'
                                });
                            } else {
                                Swal.fire({
                                    title: 'No se puede cancelar',
                                    html: '<div class="text-left">' +
                                        '<p class="mb-3">Este producto ya ha sido <strong>completamente cancelado</strong>.</p>' +
                                        '<p class="mb-3 text-sm text-red-600"><i class="bi bi-x-circle mr-1"></i>' + cancelado + ' unidad(es) ya cancelada(s)</p>' +
                                        '</div>',
                                    icon: 'info',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                            return;
                        }

                        // Si solo hay 1 unidad disponible para cancelar, cancelar directamente
                        if (pendientes === 1) {
                            let contextMessage = '';
                            if (preparado > 0) {
                                contextMessage = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">' +
                                    '<p class="text-green-700 text-sm">' +
                                    '<i class="bi bi-check-circle mr-1"></i>' +
                                    '<strong>' + preparado + ' unidad(es) ya preparada(s)</strong> - No se pueden cancelar' +
                                    '</p>' +
                                    '</div>';
                            }

                            Swal.fire({
                                title: 'Cancelar producto',
                                html: '<div class="text-left">' +
                                    '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                                    contextMessage +
                                    '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">' +
                                    '<p class="text-orange-700 text-sm">' +
                                    '<i class="bi bi-clock mr-1"></i>' +
                                    '<strong>1 unidad en preparación</strong> - Se cancelará' +
                                    '</p>' +
                                    '</div>' +
                                    '<p class="mb-3 text-sm text-gray-600">Se enviará un PIN al administrador por Email</p>' +
                                    '<textarea id="razon-cancelacion" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500" rows="3" placeholder="Motivo de la cancelación (opcional)"></textarea>' +
                                    '</div>',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Cancelar 1 unidad',
                                cancelButtonText: 'No cancelar',
                                confirmButtonColor: '#f59e0b',
                                preConfirm: function() {
                                    return document.getElementById('razon-cancelacion').value;
                                }
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    const razon = result.value || 'Cancelación de producto en preparación';
                                    mostrarModalCancelacion(ordenProductoId, productoNombre, 1, razon);
                                }
                            });
                            return;
                        }

                        // Si hay múltiples unidades disponibles, preguntar si cancelar todas o algunas
                        let contextMessage = '';
                        if (preparado > 0) {
                            contextMessage = '<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">' +
                                '<p class="text-green-700 text-sm">' +
                                '<i class="bi bi-check-circle mr-1"></i>' +
                                '<strong>' + preparado + ' unidad(es) ya preparada(s)</strong> - No se pueden cancelar' +
                                '</p>' +
                                '</div>';
                        }

                        Swal.fire({
                            title: 'Tipo de cancelación',
                            html: '<div class="text-left">' +
                                '<p class="mb-3">Producto: <strong>' + productoNombre + '</strong></p>' +
                                contextMessage +
                                '<div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">' +
                                '<p class="text-orange-700 text-sm">' +
                                '<i class="bi bi-clock mr-1"></i>' +
                                '<strong>' + pendientes + ' unidad(es) en preparación</strong> - Disponibles para cancelar' +
                                '</p>' +
                                '</div>' +
                                '<p class="mb-4 text-sm text-gray-600">¿Qué tipo de cancelación deseas realizar?</p>' +
                                '<div class="space-y-3">' +
                                '<button type="button" class="cancelacion-opcion w-full text-left p-3 border-2 border-orange-200 rounded-lg hover:border-orange-400 hover:bg-orange-50 transition-colors" data-tipo="total">' +
                                '<div class="flex items-center">' +
                                '<i class="bi bi-x-circle-fill text-orange-500 text-xl mr-3"></i>' +
                                '<div>' +
                                '<div class="font-semibold text-gray-900">Cancelación Total</div>' +
                                '<div class="text-sm text-gray-600">Cancelar todas las ' + pendientes + ' unidades disponibles</div>' +
                                '</div>' +
                                '</div>' +
                                '</button>' +
                                '<button type="button" class="cancelacion-opcion w-full text-left p-3 border-2 border-blue-200 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors" data-tipo="parcial">' +
                                '<div class="flex items-center">' +
                                '<i class="bi bi-dash-circle-fill text-blue-500 text-xl mr-3"></i>' +
                                '<div>' +
                                '<div class="font-semibold text-gray-900">Cancelación Parcial</div>' +
                                '<div class="text-sm text-gray-600">Elegir cuántas unidades cancelar (1 a ' + pendientes + ')</div>' +
                                '</div>' +
                                '</div>' +
                                '</button>' +
                                '</div>' +
                                '</div>',
                            icon: 'question',
                            showCancelButton: true,
                            showConfirmButton: false,
                            cancelButtonText: 'Cancelar operación',
                            didOpen: function() {
                                // Agregar eventos a los botones de opción
                                document.querySelectorAll('.cancelacion-opcion').forEach(function(opcionBtn) {
                                    opcionBtn.addEventListener('click', function() {
                                        const tipo = this.getAttribute('data-tipo');
                                        Swal.close();

                                        if (tipo === 'total') {
                                            // Cancelación total - confirmar directamente
                                            mostrarConfirmacionCancelacion(ordenProductoId, productoNombre, pendientes, preparado);
                                        } else {
                                            // Cancelación parcial - preguntar cantidad
                                            mostrarSelectorCantidad(ordenProductoId, productoNombre, pendientes, preparado);
                                        }
                                    });
                                });
                            }
                        });
                    };
                });

                // Eventos para eliminar producto (solo administradores)
                if (esAdministrador) {
                    document.querySelectorAll('.sale-item-remove').forEach(function(btn) {
                        btn.onclick = function() {
                            const button = this;
                            const productoId = this.getAttribute('data-id');

                            Swal.fire({
                                title: '¿Eliminar producto?',
                                text: 'Se quitará este producto de la orden',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Sí, eliminar',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#ef4444'
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    button.classList.add('animate-pulse');

                                    fetch(CONTROLLERS_URL + 'newPos/actualizar_producto_orden.php', {
                                        method: 'POST',
                                        body: new URLSearchParams({
                                            producto_id: productoId,
                                            cantidad: 0,
                                            orden_id: ordenId
                                        }),
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    }).then(function(response) {
                                        return response.json();
                                    }).then(function(resp) {
                                        if (resp.status === 'ok' || resp.success) {
                                            cargarOrden();
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Producto eliminado',
                                                text: 'El producto se eliminó de la orden',
                                                timer: 1500,
                                                showConfirmButton: false,
                                                toast: true,
                                                position: 'top-end'
                                            });
                                        } else {
                                            button.classList.remove('animate-pulse');
                                            Swal.fire('Error', resp.msg || 'No se pudo eliminar el producto', 'error');
                                        }
                                    }).catch(function(error) {
                                        button.classList.remove('animate-pulse');
                                        console.error('Error al eliminar producto:', error);
                                        Swal.fire('Error', 'No se pudo eliminar el producto', 'error');
                                    });
                                }
                            });
                        };
                    });
                }
            })
            .catch(function(error) {
                console.error('Error al cargar orden:', error);
                document.getElementById('orden-lista').innerHTML = '<div class="text-center py-8 text-red-400">' +
                    '<i class="bi bi-exclamation-triangle text-4xl mb-3"></i>' +
                    '<p>Error al cargar la orden</p>' +
                    '</div>';
            });
    }

    /** 🔹 Inicialización */
    document.addEventListener('DOMContentLoaded', function() {
        cargarCategorias();
        cargarProductos();
        cargarOrden();
        setInterval(cargarOrden, 5000);

        // Configurar indicadores de scroll
        setupScrollIndicators();
        
        // Configurar observer para cambios de contenido
        setupContentObserver();

        const buscador = document.getElementById('buscador');
        if (buscador) {
            buscador.addEventListener('input', function(e) {
                // Activar visualmente el botón "Todos" cuando se busca
                document.querySelectorAll('.category-btn').forEach(function(btn) {
                    btn.classList.remove('bg-purple-600', 'bg-purple-700', 'ring-2', 'ring-purple-400');
                    btn.classList.add('bg-slate-600');
                });

                const todosBtn = document.querySelector('.category-btn[data-cat="0"]');
                if (todosBtn) {
                    todosBtn.classList.remove('bg-slate-600');
                    todosBtn.classList.add('bg-purple-600', 'hover:bg-purple-700', 'ring-2', 'ring-purple-400');
                }

                cargarProductos(0, e.target.value);
            });
        }

        const btnCancelar = document.getElementById('cancelar_orden');
        if (btnCancelar && esAdministrador) {
            btnCancelar.onclick = function() {
                Swal.fire({
                    title: '¿Cancelar orden?',
                    text: 'Esta acción eliminará todos los productos de la orden y no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar orden',
                    cancelButtonText: 'No, mantener orden',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280'
                }).then(function(res) {
                    if (res.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Cancelando orden...',
                            allowOutsideClick: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });

                        fetch(CONTROLLERS_URL + 'newPos/cancelar_orden.php', {
                                method: 'POST',
                                body: new URLSearchParams({
                                    orden_id: ordenId
                                }),
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                if (data.status === 'ok') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Orden cancelada',
                                        text: 'La orden ha sido cancelada exitosamente',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(function() {
                                        // Redireccionar a la vista de mesas
                                        window.location.href = 'index.php?page=mesas';
                                    });
                                } else {
                                    Swal.fire('Error', data.msg || 'No se pudo cancelar la orden', 'error');
                                }
                            })
                            .catch(function(error) {
                                console.error('Error:', error);
                                Swal.fire('Error', 'Ocurrió un error al cancelar la orden', 'error');
                            });
                    }
                });
            };
        }

        // Manejar el formulario de cerrar orden
        const cerrarOrdenForm = document.getElementById('cerrar-orden-form');
        if (cerrarOrdenForm) {
            cerrarOrdenForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Verificar si hay productos sin preparar antes de permitir cerrar
                fetch(CONTROLLERS_URL + 'newPos/orden_actual.php?orden_id=' + ordenId)
                    .then(function(r) {
                        if (!r.ok) {
                            throw new Error('Error HTTP: ' + r.status + ' ' + r.statusText);
                        }
                        return r.json();
                    })
                    .then(function(data) {
                        // Contar productos sin preparar
                        let productosSinPreparar = 0;
                        let detallesPendientes = [];
                        
                        if (data.items && data.items.length > 0) {
                            data.items.forEach(function(item) {
                                const preparado = parseInt(item.preparado || 0);
                                const cantidad = parseInt(item.cantidad || 0);
                                const cancelado = parseInt(item.cancelado || 0);
                                const pendientes = cantidad - preparado - cancelado;
                                
                                if (pendientes > 0) {
                                    productosSinPreparar += pendientes;
                                    detallesPendientes.push({
                                        nombre: item.nombre,
                                        pendientes: pendientes
                                    });
                                }
                            });
                        }

                        // Si hay productos sin preparar, mostrar error
                        if (productosSinPreparar > 0) {
                            let listaProductos = '<ul class="text-left mt-3 space-y-1">';
                            detallesPendientes.forEach(function(item) {
                                listaProductos += '<li class="text-orange-700">• <strong>' + item.nombre + '</strong>: ' + item.pendientes + ' unidad(es) pendiente(s)</li>';
                            });
                            listaProductos += '</ul>';

                            Swal.fire({
                                title: '❌ No se puede cerrar la orden',
                                html: '<div class="text-center">' +
                                    '<div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">' +
                                    '<p class="text-orange-800 font-semibold mb-2">' +
                                    '<i class="bi bi-exclamation-triangle mr-2"></i>' +
                                    'Hay ' + productosSinPreparar + ' producto(s) sin preparar' +
                                    '</p>' +
                                    listaProductos +
                                    '</div>' +
                                    '<p class="text-sm text-gray-600 mb-3">Para cerrar la orden debes:</p>' +
                                    '<div class="text-left space-y-2 text-sm text-gray-700">' +
                                    '<div class="flex items-center">' +
                                    '<i class="bi bi-check-circle text-green-500 mr-2"></i>' +
                                    'Completar la preparación de todos los productos' +
                                    '</div>' +
                                    '<div class="flex items-center">' +
                                    '<i class="bi bi-x-circle text-orange-500 mr-2"></i>' +
                                    'O cancelar los productos que no se van a preparar' +
                                    '</div>' +
                                    '</div>' +
                                    '</div>',
                                icon: 'warning',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#f59e0b',
                                customClass: {
                                    popup: 'text-left'
                                }
                            });
                            return;
                        }

                        // Si todos los productos están preparados, continuar con el cierre normal
                        procederConCierreOrden(data);
                    })
                    .catch(function(error) {
                        console.error('Error al verificar productos:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo verificar el estado de los productos. Error: ' + error.message,
                            confirmButtonColor: '#ef4444'
                        });
                    });
            });
        }

        function procederConCierreOrden(data) {
            // Obtener el total actual - buscar en toda la sección de totales
            const totalElements = document.querySelectorAll('#orden-totales .text-green-400');
            let totalText = '$0.00';
            let total = 0;

            // El total es el último elemento con clase text-green-400 (el total final)
            if (totalElements.length > 0) {
                totalText = totalElements[totalElements.length - 1].textContent.trim();
                // Extraer el número del texto (remover $ y convertir a float)
                total = parseFloat(totalText.replace('$', '').replace(',', '')) || 0;
            }

            Swal.fire({
                title: '✅ ¿Cerrar y pagar orden?',
                html: '<div class="text-center">' +
                    '<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">' +
                    '<p class="text-green-700 font-semibold mb-2">' +
                    '<i class="bi bi-check-circle mr-2"></i>' +
                    'Todos los productos están preparados' +
                    '</p>' +
                    '</div>' +
                    '<p class="mb-4">Esta acción cerrará la orden y marcará la mesa como disponible</p>' +
                    '<div class="bg-gray-100 p-4 rounded-lg mb-4">' +
                    '<p class="text-lg font-bold text-green-600">Total a pagar: ' + totalText + '</p>' +
                    '</div>' +
                    '<div class="mb-4">' +
                    '<label class="block text-sm font-medium text-gray-700 mb-2">Método de pago:</label>' +
                    '<div class="grid grid-cols-2 gap-3 mb-4">' +
                    '<label class="flex items-center justify-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">' +
                    '<input type="radio" name="metodo_pago" value="efectivo" checked class="mr-2" onchange="toggleEfectivoFields()">' +
                    '<span class="flex items-center">' +
                    '<i class="bi bi-cash text-green-600 mr-2"></i>' +
                    'Efectivo' +
                    '</span>' +
                    '</label>' +
                    '<label class="flex items-center justify-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">' +
                    '<input type="radio" name="metodo_pago" value="debito" class="mr-2" onchange="toggleEfectivoFields()">' +
                    '<span class="flex items-center">' +
                    '<i class="bi bi-credit-card-2-front text-blue-600 mr-2"></i>' +
                    'Débito' +
                    '</span>' +
                    '</label>' +
                    '<label class="flex items-center justify-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">' +
                    '<input type="radio" name="metodo_pago" value="credito" class="mr-2" onchange="toggleEfectivoFields()">' +
                    '<span class="flex items-center">' +
                    '<i class="bi bi-credit-card text-purple-600 mr-2"></i>' +
                    'Crédito' +
                    '</span>' +
                    '</label>' +
                    '<label class="flex items-center justify-center p-3 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">' +
                    '<input type="radio" name="metodo_pago" value="transferencia" class="mr-2" onchange="toggleEfectivoFields()">' +
                    '<span class="flex items-center">' +
                    '<i class="bi bi-bank text-orange-600 mr-2"></i>' +
                    'Transferencia' +
                    '</span>' +
                    '</label>' +
                    '</div>' +
                    '<!-- Campos para efectivo -->' +
                    '<div id="efectivo-fields" class="bg-blue-50 border border-blue-200 rounded-lg p-4">' +
                    '<div class="mb-3">' +
                    '<label class="block text-sm font-medium text-gray-700 mb-1">Dinero recibido:</label>' +
                    '<input type="number" id="dinero-recibido" class="w-full px-3 py-2 border border-gray-300 rounded-md text-center text-lg font-semibold" ' +
                    'placeholder="$0.00" step="0.01" min="' + total + '" oninput="calcularCambio(' + total + ')">' +
                    '</div>' +
                    '<div id="cambio-display" class="text-center p-2 bg-green-100 rounded-md" style="display: none;">' +
                    '<p class="text-sm text-gray-600">Cambio a entregar:</p>' +
                    '<p class="text-xl font-bold text-green-700" id="cambio-amount">$0.00</p>' +
                    '</div>' +
                    '<div id="cambio-error" class="text-center p-2 bg-red-100 rounded-md text-red-700" style="display: none;">' +
                    '<p class="text-sm">El dinero recibido debe ser mayor o igual al total</p>' +
                    '</div>' +
                    '</div>' +
                    '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar orden',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280'
            }).then(function(result) {
                if (result.isConfirmed) {
                    // Obtener el método de pago seleccionado
                    const metodoPago = document.querySelector('input[name="metodo_pago"]:checked').value;

                    // Validar efectivo si es necesario
                    if (metodoPago === 'efectivo') {
                        const dineroRecibido = parseFloat(document.getElementById('dinero-recibido').value) || 0;
                        
                        if (dineroRecibido < total) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Dinero insuficiente',
                                text: 'El dinero recibido debe ser mayor o igual al total de la cuenta',
                                confirmButtonColor: '#ef4444'
                            });
                            return;
                        }
                    }

                    // Agregar el método de pago al formulario
                    const form = document.getElementById('cerrar-orden-form');
                    const metodoPagoInput = document.createElement('input');
                    metodoPagoInput.type = 'hidden';
                    metodoPagoInput.name = 'metodo_pago';
                    metodoPagoInput.value = metodoPago;
                    form.appendChild(metodoPagoInput);

                    // Si es efectivo, agregar información del dinero recibido y cambio
                    if (metodoPago === 'efectivo') {
                        const dineroRecibido = parseFloat(document.getElementById('dinero-recibido').value);
                        const cambio = dineroRecibido - total;

                        const dineroRecibidoInput = document.createElement('input');
                        dineroRecibidoInput.type = 'hidden';
                        dineroRecibidoInput.name = 'dinero_recibido';
                        dineroRecibidoInput.value = dineroRecibido.toFixed(2);
                        form.appendChild(dineroRecibidoInput);

                        const cambioInput = document.createElement('input');
                        cambioInput.type = 'hidden';
                        cambioInput.name = 'cambio';
                        cambioInput.value = cambio.toFixed(2);
                        form.appendChild(cambioInput);
                    }

                    let loadingText = 'Procesando pago por ' + metodoPago;
                    if (metodoPago === 'efectivo') {
                        const dineroRecibido = parseFloat(document.getElementById('dinero-recibido').value);
                        const cambio = dineroRecibido - total;
                        if (cambio > 0) {
                            loadingText += '. Cambio: $' + cambio.toFixed(2);
                        } else {
                            loadingText += ' (pago exacto)';
                        }
                    }

                    // Mostrar loading
                    Swal.fire({
                        title: 'Cerrando orden...',
                        text: loadingText + ' y liberando mesa',
                        allowOutsideClick: false,
                        didOpen: function() {
                            Swal.showLoading();
                        }
                    });

                    // Enviar el formulario
                    form.submit();
                }
            });
        }
    });

    /** 🔹 Configurar indicadores de scroll */
    function setupScrollIndicators() {
        console.log('🔹 Configurando indicadores de scroll...');
        
        // Configurar indicadores para la orden
        const ordenScroll = document.querySelector('.orden-scroll-area');
        
        if (ordenScroll) {
            const ordenContainer = ordenScroll.closest('.relative');
            if (ordenContainer) {
                // Crear indicadores si no existen
                let fadeTop = ordenContainer.querySelector('.scroll-fade-top');
                let fadeBottom = ordenContainer.querySelector('.scroll-fade-bottom');
                
                if (!fadeTop) {
                    fadeTop = document.createElement('div');
                    fadeTop.className = 'scroll-fade-top';
                    ordenContainer.appendChild(fadeTop);
                }
                
                if (!fadeBottom) {
                    fadeBottom = document.createElement('div');
                    fadeBottom.className = 'scroll-fade-bottom';
                    ordenContainer.appendChild(fadeBottom);
                }
                
                ordenScroll.addEventListener('scroll', function() {
                    updateScrollIndicators(this, fadeTop, fadeBottom);
                });
                
                // Verificar inicial después de que el DOM esté listo
                setTimeout(() => updateScrollIndicators(ordenScroll, fadeTop, fadeBottom), 200);
            }
        }

        // Configurar indicadores para el catálogo
        const catalogoScroll = document.querySelector('.catalogo-scroll-area');
        
        if (catalogoScroll) {
            const catalogoContainer = catalogoScroll.closest('.relative');
            if (catalogoContainer) {
                // Crear indicadores si no existen
                let fadeTop = catalogoContainer.querySelector('.scroll-fade-top');
                let fadeBottom = catalogoContainer.querySelector('.scroll-fade-bottom');
                
                if (!fadeTop) {
                    fadeTop = document.createElement('div');
                    fadeTop.className = 'scroll-fade-top';
                    catalogoContainer.appendChild(fadeTop);
                }
                
                if (!fadeBottom) {
                    fadeBottom = document.createElement('div');
                    fadeBottom.className = 'scroll-fade-bottom';
                    catalogoContainer.appendChild(fadeBottom);
                }
                
                catalogoScroll.addEventListener('scroll', function() {
                    updateScrollIndicators(this, fadeTop, fadeBottom);
                });
                
                // Verificar inicial después de que el DOM esté listo
                setTimeout(() => updateScrollIndicators(catalogoScroll, fadeTop, fadeBottom), 200);
            }
        }
    }

    /** 🔹 Actualizar indicadores de scroll */
    function updateScrollIndicators(scrollElement, fadeTop, fadeBottom) {
        if (!scrollElement || !fadeTop || !fadeBottom) return;
        
        const { scrollTop, scrollHeight, clientHeight } = scrollElement;
        const threshold = 5; // Umbral mínimo para mostrar indicadores
        
        // Calcular si hay contenido que scrollear
        const hasScrollableContent = scrollHeight > clientHeight;
        
        if (!hasScrollableContent) {
            // Si no hay contenido para scroll, ocultar ambos indicadores
            fadeTop.style.opacity = '0';
            fadeBottom.style.opacity = '0';
            return;
        }
        
        // Mostrar fade superior si no estamos en el top
        if (scrollTop > threshold) {
            fadeTop.style.opacity = '1';
        } else {
            fadeTop.style.opacity = '0';
        }
        
        // Mostrar fade inferior si no estamos en el bottom
        const isAtBottom = scrollTop >= scrollHeight - clientHeight - threshold;
        if (!isAtBottom) {
            fadeBottom.style.opacity = '1';
        } else {
            fadeBottom.style.opacity = '0';
        }
        
        // Debug para desarrollo (comentar en producción)
        // console.log(`Scroll: ${scrollTop}, Height: ${scrollHeight}, Client: ${clientHeight}, AtBottom: ${isAtBottom}`);
    }

    /** 🔹 Scroll suave para elementos */
    function scrollToProduct(productElement) {
        if (productElement && productElement.scrollIntoView) {
            productElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }

    /** 🔹 Recalcular indicadores cuando cambia el contenido */
    function refreshScrollIndicators() {
        const ordenScroll = document.querySelector('.orden-scroll-area');
        const catalogoScroll = document.querySelector('.catalogo-scroll-area');
        
        if (ordenScroll) {
            const container = ordenScroll.closest('.relative');
            if (container) {
                const fadeTop = container.querySelector('.scroll-fade-top');
                const fadeBottom = container.querySelector('.scroll-fade-bottom');
                if (fadeTop && fadeBottom) {
                    updateScrollIndicators(ordenScroll, fadeTop, fadeBottom);
                }
            }
        }
        
        if (catalogoScroll) {
            const container = catalogoScroll.closest('.relative');
            if (container) {
                const fadeTop = container.querySelector('.scroll-fade-top');
                const fadeBottom = container.querySelector('.scroll-fade-bottom');
                if (fadeTop && fadeBottom) {
                    updateScrollIndicators(catalogoScroll, fadeTop, fadeBottom);
                }
            }
        }
    }

    // Observer para detectar cambios en el contenido y actualizar indicadores
    function setupContentObserver() {
        const ordenArea = document.querySelector('.orden-scroll-area');
        const catalogoArea = document.querySelector('.catalogo-scroll-area');
        
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                let shouldRefresh = false;
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        shouldRefresh = true;
                    }
                });
                
                if (shouldRefresh) {
                    // Dar tiempo para que el DOM se actualice
                    setTimeout(refreshScrollIndicators, 100);
                }
            });
            
            if (ordenArea) {
                observer.observe(ordenArea, { 
                    childList: true, 
                    subtree: true, 
                    characterData: true 
                });
            }
            
            if (catalogoArea) {
                observer.observe(catalogoArea, { 
                    childList: true, 
                    subtree: true, 
                    characterData: true 
                });
            }
        }
    }
</script>

<!-- Incluir sistema de impresión térmica -->
<script src="<?= url('js/impresion-hibrida.js') ?>"></script>
<script>
    // Hacer disponible la configuración de impresora para JavaScript
    window.configImpresoraNombre = '<?= $config_impresion['nombre_impresora'] ?? '' ?>';
    
    // Log de depuración para verificar carga de archivos
    console.log('🔧 Sistema POS - Mesa.php cargado');
    console.log('🖨️ Configuración impresora:', window.configImpresoraNombre);
    
    // Verificar que la función de impresión térmica esté disponible
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof imprimirTicketTermico === 'undefined') {
            console.warn('⚠️ Función imprimirTicketTermico no encontrada, creando respaldo');
            
            // Función de respaldo para impresión térmica
            window.imprimirTicketTermico = function(ordenId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sistema de Impresión No Disponible',
                    html: `
                        <div class="text-left">
                            <p>El sistema de impresión térmica no está disponible.</p>
                            <div class="bg-blue-50 p-3 rounded border border-blue-200 mt-3">
                                <h4 class="font-semibold text-blue-800 mb-2">💡 Alternativas:</h4>
                                <ol class="text-sm text-blue-700 space-y-1">
                                    <li>1. Usa el método "Navegador" en configuración</li>
                                    <li>2. O descarga el ticket en PDF</li>
                                </ol>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: '🖨️ Usar Navegador',
                    cancelButtonText: '📄 Ticket PDF',
                    confirmButtonColor: '#10B981',
                    cancelButtonColor: '#3B82F6'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirigir a configuración para cambiar método
                        window.location.href = 'index.php?page=configuracion&tab=impresoras';
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Abrir PDF
                        window.open(`<?= url('controllers/impresion_ticket.php') ?>?orden_id=${ordenId}`, '_blank');
                    }
                });
            };
        } else {
            console.log('✅ Función imprimirTicketTermico disponible');
        }
    });
    
    // 🖨️ Función para imprimir ticket desde navegador (MEJORADA PARA HOSTGATOR)
    function imprimirTicketNavegador(ordenId) {
        // Validar que existe la orden
        if (!ordenId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró el ID de la orden'
            });
            return;
        }
        
        // Mostrar loading
        Swal.fire({
            title: 'Preparando ticket optimizado...',
            text: 'Generando ticket para HostGator',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // 🚀 NUEVA URL OPTIMIZADA para HostGator
        const urlTicketOptimizado = `<?= url('controllers/ticket_navegador_optimizado.php') ?>?orden_id=${ordenId}`;
        
        // Abrir ventana del ticket con configuración optimizada
        const ventanaTicket = window.open(
            urlTicketOptimizado, 
            '_blank', 
            'width=420,height=800,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no'
        );
        
        // Verificar si se abrió la ventana
        if (ventanaTicket) {
            Swal.close();
            
            // 🎯 INSTRUCCIONES ESPECÍFICAS PARA HOSTGATOR
            Swal.fire({
                icon: 'success',
                title: '🎫 ¡Ticket Optimizado para HostGator!',
                html: `
                    <div class="text-left">
                        <div class="bg-blue-50 p-3 rounded border border-blue-200 mb-3">
                            <h4 class="font-semibold text-blue-800 mb-2">🌐 Sistema HostGator Activo</h4>
                            <p class="text-blue-700 text-sm">Ticket optimizado para hosting compartido</p>
                        </div>
                        
                        <div class="bg-green-50 p-3 rounded border border-green-200">
                            <h4 class="font-semibold text-green-800 mb-2">📋 Pasos para imprimir:</h4>
                            <ol class="text-sm text-green-700 space-y-1 text-left">
                                <li><strong>1. 🖨️ Haz clic en "Imprimir Ticket"</strong> en la nueva ventana</li>
                                <li><strong>2. 📄 Configura el papel:</strong>
                                    <ul class="ml-4 mt-1 space-y-1">
                                        <li>• <strong>Impresora térmica:</strong> 80mm x continuo</li>
                                        <li>• <strong>Impresora normal:</strong> Tamaño carta vertical</li>
                                    </ul>
                                </li>
                                <li><strong>3. ⚙️ Ajusta márgenes:</strong> Mínimos (0mm)</li>
                                <li><strong>4. ✅ ¡Presiona Imprimir!</strong></li>
                            </ol>
                        </div>
                        
                        <div class="bg-yellow-50 p-2 rounded border border-yellow-200 mt-3">
                            <p class="text-yellow-800 text-xs">
                                💡 <strong>Optimización HostGator:</strong> El ticket se genera completamente en tu navegador, sin depender del servidor.
                            </p>
                        </div>
                        
                        <div class="bg-red-50 p-2 rounded border border-red-200 mt-2">
                            <p class="text-red-800 text-xs">
                                🚫 <strong>Limitación:</strong> HostGator no permite impresión USB directa. Solo funciona vía navegador.
                            </p>
                        </div>
                    </div>
                `,
                confirmButtonText: '✅ Entendido',
                width: '600px',
                showCancelButton: true,
                cancelButtonText: '🔧 Ver Configuración',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    // Mostrar configuración adicional
                    mostrarConfiguracionHostGator();
                }
            });
        } else {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Ventana Bloqueada por Navegador',
                html: `
                    <div class="text-left">
                        <p class="mb-3">El navegador bloqueó la ventana emergente.</p>
                        <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                            <h4 class="font-semibold text-yellow-800 mb-2">🔧 Soluciones para HostGator:</h4>
                            <ol class="text-sm text-yellow-700 space-y-2">
                                <li><strong>1. Permitir ventanas emergentes:</strong>
                                    <ul class="ml-4 mt-1">
                                        <li>• Busca el ícono 🚫 en la barra de direcciones</li>
                                        <li>• Haz clic y selecciona "Permitir ventanas emergentes"</li>
                                    </ul>
                                </li>
                                <li><strong>2. Alternativa sin popup:</strong>
                                    <ul class="ml-4 mt-1">
                                        <li>• Usa "Ticket PDF" desde el menú de la orden</li>
                                        <li>• O abre en nueva pestaña (Ctrl+Click)</li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
                    </div>
                `,
                confirmButtonText: '🔄 Volver a Intentar',
                showCancelButton: true,
                cancelButtonText: '📄 Usar Ticket PDF',
                confirmButtonColor: '#3B82F6',
                cancelButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    imprimirTicketNavegador(ordenId);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Abrir en nueva pestaña como alternativa
                    window.open(urlTicketOptimizado, '_blank');
                }
            });
        }
    }
    
    // 🔧 Función para mostrar configuración específica de HostGator
    function mostrarConfiguracionHostGator() {
        Swal.fire({
            title: '⚙️ Configuración para HostGator',
            html: `
                <div class="text-left">
                    <div class="bg-blue-50 p-3 rounded border border-blue-200 mb-3">
                        <h4 class="font-semibold text-blue-800 mb-2">🌐 Limitaciones de HostGator:</h4>
                        <ul class="text-blue-700 text-sm space-y-1">
                            <li>• ❌ No soporta comandos USB (lpr, shell_exec)</li>
                            <li>• ❌ No puede acceder a impresoras locales</li>
                            <li>• ✅ Solo funciona impresión vía navegador</li>
                        </ul>
                    </div>
                    
                    <div class="bg-green-50 p-3 rounded border border-green-200 mb-3">
                        <h4 class="font-semibold text-green-800 mb-2">✅ Soluciones Funcionales:</h4>
                        <ol class="text-green-700 text-sm space-y-1">
                            <li><strong>1. Método "Navegador":</strong> Ya configurado ✅</li>
                            <li><strong>2. Tickets PDF:</strong> Descarga y imprime</li>
                            <li><strong>3. Email automático:</strong> Envía tickets por correo</li>
                            <li><strong>4. WhatsApp Business:</strong> Envía tickets a clientes</li>
                        </ol>
                    </div>
                    
                    <div class="bg-yellow-50 p-3 rounded border border-yellow-200">
                        <h4 class="font-semibold text-yellow-800 mb-2">💡 Recomendaciones:</h4>
                        <ul class="text-yellow-700 text-sm space-y-1">
                            <li>• Mantén el método "Navegador" activado</li>
                            <li>• Configura una impresora térmica USB en tu laptop</li>
                            <li>• Usa Chrome o Firefox para mejor compatibilidad</li>
                            <li>• Considera un servidor local para futuro</li>
                        </ul>
                    </div>
                </div>
            `,
            confirmButtonText: '✅ Entendido',
            width: '600px'
        });
    }
</script>
</div>