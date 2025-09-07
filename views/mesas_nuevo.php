<?php
session_start();
require_once '../auth/check-session.php';
require_once '../config/config.php';

// Obtener las mesas de la base de datos
try {
    $stmt = $pdo->query("
        SELECT m.*, 
               COALESCE(SUM(CASE WHEN o.estado != 'completada' AND o.estado != 'cancelada' THEN 1 ELSE 0 END), 0) as orden_abierta
        FROM mesas m 
        LEFT JOIN ordenes o ON m.id = o.mesa_id 
        GROUP BY m.id 
        ORDER BY m.nombre
    ");
    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error obteniendo mesas: " . $e->getMessage());
    $mesas = [];
}

// Obtener layouts guardados
try {
    $stmt = $pdo->query("SELECT * FROM mesa_layouts ORDER BY mesa_id");
    $layouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $layoutMap = [];
    foreach ($layouts as $layout) {
        $layoutMap[$layout['mesa_id']] = $layout;
    }
} catch (PDOException $e) {
    error_log("Error obteniendo layouts: " . $e->getMessage());
    $layoutMap = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diseñador de Layout - POS Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/mesas.css">
    <style>
        .mesa-container {
            background: #1e293b;
            min-height: 600px;
            position: relative;
            overflow: visible;
            border: 2px solid #334155;
            border-radius: 12px;
        }
        
        .mesa-element {
            position: absolute;
            cursor: move;
            user-select: none;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            transition: transform 0.2s ease;
            z-index: 100;
        }
        
        .mesa-element:hover {
            transform: scale(1.05);
        }
        
        .mesa-libre {
            background: linear-gradient(135deg, #059669, #047857);
            border: 3px solid #10b981;
        }
        
        .mesa-ocupada {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border: 3px solid #ef4444;
        }
        
        .mesa-nombre {
            font-size: 16px;
            margin-bottom: 4px;
        }
        
        .mesa-estado {
            font-size: 10px;
            opacity: 0.9;
            margin-bottom: 6px;
        }
        
        .mesa-btn {
            background: rgba(255,255,255,0.25);
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .mesa-btn:hover {
            background: rgba(255,255,255,0.4);
        }
        
        .debug-info {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            z-index: 9999;
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-4">
                <div class="bg-purple-600 p-3 rounded-lg">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Diseñador de Layout</h1>
                    <p class="text-gray-400">Organiza el layout visual de tu restaurante</p>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button id="btn-grid" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition-colors">
                    📏 Grid
                </button>
                <button id="btn-guardar" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition-colors">
                    💾 Guardar
                </button>
                <button id="btn-reset" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors">
                    🔄 Reset
                </button>
                <button id="btn-debug" class="bg-yellow-600 hover:bg-yellow-700 px-4 py-2 rounded-lg transition-colors">
                    🐛 Debug
                </button>
            </div>
        </div>

        <!-- Debug Info -->
        <div id="debug-info" class="debug-info" style="display: none;">
            <div>Mesas en BD: <?= count($mesas) ?></div>
            <div>Mesas en DOM: <span id="debug-dom-count">0</span></div>
            <div>Mesas visibles: <span id="debug-visible-count">0</span></div>
            <div>Sistema: <span id="debug-status">Inicializando...</span></div>
        </div>

        <!-- Área de diseño -->
        <div id="design-area" class="mesa-container relative" style="height: 600px;">
            <!-- Elementos estáticos -->
            <div id="cocina" class="mesa-element bg-red-600" 
                 style="left: 50px; top: 50px; width: 100px; height: 350px;">
                <span class="text-2xl mb-2">🔥</span>
                <span class="mesa-nombre">COCINA</span>
            </div>
            
            <div id="bar" class="mesa-element bg-purple-600" 
                 style="left: 170px; top: 50px; width: 100px; height: 350px;">
                <span class="text-2xl mb-2">🍺</span>
                <span class="mesa-nombre">BAR</span>
            </div>
            
            <div id="bano" class="mesa-element bg-gray-600" 
                 style="left: 50px; top: 420px; width: 80px; height: 60px;">
                <span class="text-xl mb-1">🚻</span>
                <span class="mesa-nombre" style="font-size: 12px;">BAÑO</span>
            </div>

            <!-- Mesas dinámicas de la base de datos -->
            <?php foreach ($mesas as $index => $mesa): ?>
                <?php
                // Obtener posición guardada o usar posición por defecto
                $layout = $layoutMap[$mesa['id']] ?? null;
                $posX = $layout ? $layout['pos_x'] : (350 + ($index % 4) * 140);
                $posY = $layout ? $layout['pos_y'] : (150 + floor($index / 4) * 130);
                $width = $layout ? $layout['width'] : 120;
                $height = $layout ? $layout['height'] : 100;
                $rotation = $layout ? $layout['rotation'] : 0;
                
                $estaOcupada = $mesa['orden_abierta'] > 0;
                $claseEstado = $estaOcupada ? 'mesa-ocupada' : 'mesa-libre';
                $textoEstado = $estaOcupada ? 'OCUPADA' : 'LIBRE';
                $textoBtnPOS = $estaOcupada ? 'Ver POS' : 'Abrir POS';
                ?>
                
                <div id="mesa-<?= $mesa['id'] ?>" 
                     class="mesa-element <?= $claseEstado ?>"
                     data-mesa-id="<?= $mesa['id'] ?>"
                     data-mesa-nombre="<?= htmlspecialchars($mesa['nombre']) ?>"
                     style="left: <?= $posX ?>px; 
                            top: <?= $posY ?>px; 
                            width: <?= $width ?>px; 
                            height: <?= $height ?>px;
                            transform: rotate(<?= $rotation ?>deg);">
                    
                    <!-- Icono de mesa -->
                    <div class="text-2xl mb-1">🍽️</div>
                    
                    <!-- Nombre de la mesa -->
                    <div class="mesa-nombre"><?= htmlspecialchars($mesa['nombre']) ?></div>
                    
                    <!-- Estado -->
                    <div class="mesa-estado"><?= $textoEstado ?></div>
                    
                    <!-- Botón POS -->
                    <button class="mesa-btn" 
                            onclick="abrirMesa(<?= $mesa['id'] ?>); event.stopPropagation();">
                        <?= $textoBtnPOS ?>
                    </button>
                    
                    <?php if ($estaOcupada): ?>
                    <!-- Indicador de orden activa -->
                    <div style="position: absolute; top: -8px; right: -8px; 
                                width: 20px; height: 20px; 
                                background: #fbbf24; border: 2px solid white; 
                                border-radius: 50%; display: flex; 
                                align-items: center; justify-content: center; 
                                font-size: 12px; color: #000; font-weight: bold;">!</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paleta de elementos -->
        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-4">Añadir Elementos</h3>
            <div class="grid grid-cols-4 gap-4">
                <button class="bg-blue-600 hover:bg-blue-700 p-4 rounded-lg text-center transition-colors"
                        onclick="agregarMesa('cuadrada')">
                    <div class="text-2xl mb-2">⬜</div>
                    <div class="text-sm">Mesa Cuadrada</div>
                </button>
                
                <button class="bg-green-600 hover:bg-green-700 p-4 rounded-lg text-center transition-colors"
                        onclick="agregarMesa('rectangular')">
                    <div class="text-2xl mb-2">▬</div>
                    <div class="text-sm">Mesa Rectangular</div>
                </button>
                
                <button class="bg-orange-600 hover:bg-orange-700 p-4 rounded-lg text-center transition-colors"
                        onclick="agregarMesa('redonda')">
                    <div class="text-2xl mb-2">🟠</div>
                    <div class="text-sm">Mesa Redonda</div>
                </button>
                
                <button class="bg-purple-600 hover:bg-purple-700 p-4 rounded-lg text-center transition-colors"
                        onclick="agregarMesa('larga')">
                    <div class="text-2xl mb-2">▬</div>
                    <div class="text-sm">Mesa Larga</div>
                </button>
            </div>
        </div>
    </div>

    <script>
        // === CONFIGURACIÓN DEL SISTEMA ===
        let isDragging = false;
        let isResizing = false;
        let currentElement = null;
        let currentHandle = null;
        let startX, startY, startLeft, startTop, startWidth, startHeight;
        let gridSize = 20;
        let showGrid = false;
        
        // === INICIALIZACIÓN ===
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Inicializando sistema de mesas...');
            initializeSystem();
        });
        
        function initializeSystem() {
            setupEventListeners();
            setupDragAndDrop();
            addResizeHandles();
            updateDebugInfo();
            verificarMesas();
        }
        
        function verificarMesas() {
            console.log('🔍 Verificando mesas...');
            const mesas = document.querySelectorAll('[data-mesa-id]');
            console.log(`Total mesas: ${mesas.length}`);
            
            mesas.forEach((mesa, index) => {
                const rect = mesa.getBoundingClientRect();
                const style = window.getComputedStyle(mesa);
                
                console.log(`Mesa ${index + 1}:`, {
                    id: mesa.id,
                    nombre: mesa.dataset.mesaNombre,
                    visible: rect.width > 0 && rect.height > 0,
                    position: `${mesa.style.left}, ${mesa.style.top}`,
                    dimensions: `${rect.width}x${rect.height}`
                });
            });
            
            updateDebugInfo();
        }
        
        // === EVENT LISTENERS ===
        function setupEventListeners() {
            document.getElementById('btn-debug').addEventListener('click', toggleDebug);
            document.getElementById('btn-guardar').addEventListener('click', guardarLayout);
            document.getElementById('btn-reset').addEventListener('click', resetLayout);
            document.getElementById('btn-grid').addEventListener('click', toggleGrid);
        }
        
        function setupDragAndDrop() {
            const elementos = document.querySelectorAll('.mesa-element');
            elementos.forEach(elemento => {
                elemento.addEventListener('mousedown', startDrag);
                elemento.addEventListener('contextmenu', showContextMenu);
                elemento.addEventListener('dblclick', rotateMesa);
            });
            
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', stopDragResize);
        }
        
        // === RESIZE HANDLES ===
        function addResizeHandles() {
            const mesas = document.querySelectorAll('[data-mesa-id]');
            mesas.forEach(mesa => {
                if (!mesa.querySelector('.resize-handle')) {
                    addResizeHandlesToElement(mesa);
                }
            });
        }
        
        function addResizeHandlesToElement(element) {
            const handles = ['nw', 'ne', 'sw', 'se'];
            
            handles.forEach(direction => {
                const handle = document.createElement('div');
                handle.className = `resize-handle resize-${direction}`;
                handle.style.cssText = `
                    position: absolute;
                    width: 8px;
                    height: 8px;
                    background: #3b82f6;
                    border: 1px solid white;
                    cursor: ${direction}-resize;
                    z-index: 1001;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                `;
                
                // Posicionamiento de handles
                switch(direction) {
                    case 'nw': handle.style.top = '-4px'; handle.style.left = '-4px'; break;
                    case 'ne': handle.style.top = '-4px'; handle.style.right = '-4px'; break;
                    case 'sw': handle.style.bottom = '-4px'; handle.style.left = '-4px'; break;
                    case 'se': handle.style.bottom = '-4px'; handle.style.right = '-4px'; break;
                }
                
                handle.addEventListener('mousedown', (e) => startResize(e, direction));
                element.appendChild(handle);
            });
            
            // Mostrar handles al hover
            element.addEventListener('mouseenter', () => {
                element.querySelectorAll('.resize-handle').forEach(h => h.style.opacity = '1');
            });
            
            element.addEventListener('mouseleave', () => {
                if (!isResizing) {
                    element.querySelectorAll('.resize-handle').forEach(h => h.style.opacity = '0');
                }
            });
        }
        
        // === DRAG & DROP ===
        function startDrag(e) {
            if (e.target.classList.contains('resize-handle') || e.target.tagName === 'BUTTON') return;
            
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
            
            console.log(`Iniciando arrastre de ${currentElement.id}`);
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
            
            console.log(`Iniciando redimensión ${direction} de ${currentElement.id}`);
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
            const container = document.getElementById('design-area');
            const containerRect = container.getBoundingClientRect();
            const elementRect = currentElement.getBoundingClientRect();
            
            newLeft = Math.max(0, Math.min(newLeft, containerRect.width - elementRect.width));
            newTop = Math.max(0, Math.min(newTop, containerRect.height - elementRect.height));
            
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
                case 'se': // Southeast
                    newWidth = startWidth + deltaX;
                    newHeight = startHeight + deltaY;
                    break;
                case 'sw': // Southwest
                    newWidth = startWidth - deltaX;
                    newHeight = startHeight + deltaY;
                    newLeft = startLeft + deltaX;
                    break;
                case 'ne': // Northeast
                    newWidth = startWidth + deltaX;
                    newHeight = startHeight - deltaY;
                    newTop = startTop + deltaY;
                    break;
                case 'nw': // Northwest
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
                console.log(`Arrastre finalizado: ${currentElement.id}`);
                isDragging = false;
                
                if (currentElement) {
                    currentElement.style.zIndex = '100';
                    currentElement.style.cursor = 'move';
                    guardarPosicionMesa(currentElement);
                }
            }
            
            if (isResizing) {
                console.log(`Redimensión finalizada: ${currentElement.id}`);
                isResizing = false;
                
                if (currentElement) {
                    currentElement.style.zIndex = '100';
                    currentElement.querySelectorAll('.resize-handle').forEach(h => h.style.opacity = '0');
                    guardarPosicionMesa(currentElement);
                }
            }
            
            currentElement = null;
            currentHandle = null;
        }
        
        // === FUNCIONES DE MESA ===
        function abrirMesa(mesaId) {
            console.log(`Abriendo mesa ${mesaId}`);
            
            // Verificar si la mesa tiene una orden abierta
            const mesaElement = document.querySelector(`[data-mesa-id="${mesaId}"]`);
            const tieneOrdenAbierta = mesaElement.classList.contains('mesa-ocupada');
            
            if (tieneOrdenAbierta) {
                // Abrir POS en modo ver/editar orden existente
                window.open(`mesa.php?mesa_id=${mesaId}&modo=ver`, '_blank');
            } else {
                // Abrir POS en modo nueva orden
                window.open(`mesa.php?mesa_id=${mesaId}&modo=nuevo`, '_blank');
            }
        }
        
        function rotateMesa(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const mesa = e.currentTarget;
            const currentRotation = parseFloat(mesa.style.transform.replace('rotate(', '').replace('deg)', '')) || 0;
            const newRotation = (currentRotation + 90) % 360;
            
            mesa.style.transform = `rotate(${newRotation}deg)`;
            
            console.log(`Mesa ${mesa.id} rotada a ${newRotation}°`);
            guardarPosicionMesa(mesa);
        }
        
        function showContextMenu(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const mesa = e.currentTarget;
            const mesaId = mesa.dataset.mesaId;
            const mesaNombre = mesa.dataset.mesaNombre;
            
            // Crear menú contextual
            const menu = document.createElement('div');
            menu.className = 'context-menu';
            menu.style.cssText = `
                position: fixed;
                left: ${e.clientX}px;
                top: ${e.clientY}px;
                background: white;
                border: 1px solid #ccc;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 2000;
                min-width: 150px;
                overflow: hidden;
            `;
            
            const opciones = [
                { texto: '🔄 Rotar 90°', accion: () => rotateMesa({ currentTarget: mesa, preventDefault: () => {}, stopPropagation: () => {} }) },
                { texto: '📏 Redimensionar', accion: () => alert('Usa los puntos azules en las esquinas para redimensionar') },
                { texto: '🍽️ Abrir POS', accion: () => abrirMesa(mesaId) },
                { texto: '🗑️ Eliminar Mesa', accion: () => eliminarMesa(mesaId, mesaNombre) }
            ];
            
            opciones.forEach(opcion => {
                const item = document.createElement('div');
                item.textContent = opcion.texto;
                item.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    color: #333;
                    border-bottom: 1px solid #eee;
                `;
                item.addEventListener('mouseenter', () => item.style.background = '#f5f5f5');
                item.addEventListener('mouseleave', () => item.style.background = 'white');
                item.addEventListener('click', () => {
                    opcion.accion();
                    document.body.removeChild(menu);
                });
                menu.appendChild(item);
            });
            
            document.body.appendChild(menu);
            
            // Cerrar menú al hacer clic fuera
            setTimeout(() => {
                document.addEventListener('click', function closeMenu(e) {
                    if (menu && document.body.contains(menu)) {
                        document.body.removeChild(menu);
                    }
                    document.removeEventListener('click', closeMenu);
                }, 100);
            });
        }
        
        function eliminarMesa(mesaId, mesaNombre) {
            if (confirm(`¿Está seguro de que desea eliminar la mesa "${mesaNombre}"?`)) {
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
                        const mesaElement = document.getElementById(`mesa-${mesaId}`);
                        if (mesaElement) {
                            mesaElement.remove();
                        }
                        console.log(`Mesa ${mesaNombre} eliminada correctamente`);
                        updateDebugInfo();
                    } else {
                        alert('Error al eliminar la mesa: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al eliminar la mesa');
                });
            }
        }
        
        function guardarPosicionMesa(element) {
            const mesaId = element.dataset.mesaId;
            if (!mesaId) return; // Solo guardar mesas de BD, no elementos estáticos
            
            const left = parseInt(element.style.left) || 0;
            const top = parseInt(element.style.top) || 0;
            const width = element.offsetWidth;
            const height = element.offsetHeight;
            const rotation = parseFloat(element.style.transform.replace('rotate(', '').replace('deg)', '')) || 0;
            
            console.log(`Guardando posición mesa ${mesaId}: x:${left}, y:${top}, w:${width}, h:${height}, r:${rotation}`);
            
            fetch('../controllers/actualizar_layout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mesa_id=${mesaId}&pos_x=${left}&pos_y=${top}&width=${width}&height=${height}&rotation=${rotation}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(`✅ Posición guardada para mesa ${mesaId}`);
                } else {
                    console.error(`❌ Error guardando posición: ${data.error}`);
                }
            })
            .catch(error => {
                console.error('Error de red:', error);
            });
        }
        
        function agregarMesa(tipo) {
            const nombre = prompt(`Nombre para la nueva mesa ${tipo}:`);
            if (!nombre) return;
            
            const capacidad = prompt('Capacidad de la mesa (número de personas):');
            if (!capacidad || isNaN(capacidad)) return;
            
            fetch('../controllers/crear_mesa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=crear&nombre=${encodeURIComponent(nombre)}&capacidad=${capacidad}&tipo=${tipo}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Recargar para mostrar la nueva mesa
                } else {
                    alert('Error al crear la mesa: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al crear la mesa');
            });
        }
        
        // === FUNCIONES DE DEBUG ===
        function toggleDebug() {
            const debugInfo = document.getElementById('debug-info');
            debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
            updateDebugInfo();
        }
        
        function updateDebugInfo() {
            const mesas = document.querySelectorAll('[data-mesa-id]');
            let visibleCount = 0;
            
            mesas.forEach(mesa => {
                const rect = mesa.getBoundingClientRect();
                if (rect.width > 0 && rect.height > 0) {
                    visibleCount++;
                }
            });
            
            document.getElementById('debug-dom-count').textContent = mesas.length;
            document.getElementById('debug-visible-count').textContent = visibleCount;
            document.getElementById('debug-status').textContent = 'Sistema OK - Funcional';
        }
        
        // === OTRAS FUNCIONES ===
        function guardarLayout() {
            console.log('Guardando layout completo...');
            
            const mesas = document.querySelectorAll('[data-mesa-id]');
            const layouts = [];
            
            mesas.forEach(mesa => {
                layouts.push({
                    mesa_id: mesa.dataset.mesaId,
                    pos_x: parseInt(mesa.style.left) || 0,
                    pos_y: parseInt(mesa.style.top) || 0,
                    width: mesa.offsetWidth,
                    height: mesa.offsetHeight,
                    rotation: parseFloat(mesa.style.transform.replace('rotate(', '').replace('deg)', '')) || 0
                });
            });
            
            fetch('../controllers/actualizar_layout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'guardar_todo', layouts: layouts })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Layout guardado correctamente');
                } else {
                    alert('❌ Error al guardar layout: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al guardar layout');
            });
        }
        
        function resetLayout() {
            if (confirm('¿Está seguro de que desea resetear todas las posiciones al layout por defecto?')) {
                location.reload();
            }
        }
        
        function toggleGrid() {
            showGrid = !showGrid;
            const gridBtn = document.getElementById('btn-grid');
            gridBtn.textContent = showGrid ? '📏 Grid ON' : '📏 Grid OFF';
            gridBtn.style.background = showGrid ? '#059669' : '#3b82f6';
            console.log('Grid:', showGrid ? 'Activado' : 'Desactivado');
        }
        
        // Debug global
        window.debugSistema = verificarMesas;
    </script>
</body>
</html>
