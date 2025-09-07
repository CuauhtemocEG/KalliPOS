<?php
require_once 'auth-check.php';
require_once 'conexion.php';
$pdo = conexion();

// Solo administradores pueden acceder
if (!hasPermission('configuracion', 'ver')) {
    header('Location: index.php?page=error-403');
    exit;
}

// Manejar solicitudes AJAX de actualización
if (isset($_POST['ajax_update']) && $_POST['ajax_update'] == '1') {
    header('Content-Type: application/json');
    
    try {
        // Obtener solicitudes actualizadas
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                p.nombre as producto_nombre,
                m.nombre as mesa_nombre,
                o.codigo as orden_codigo,
                u.nombre_completo as solicitante,
                op.cantidad as cantidad_total,
                op.preparado as cantidad_preparada,
                op.cancelado as cantidad_cancelada,
                COALESCE(c.cantidad_solicitada, 1) as cantidad_solicitada,
                TIMESTAMPDIFF(MINUTE, c.fecha_creacion, NOW()) as minutos_transcurridos,
                TIMESTAMPDIFF(MINUTE, NOW(), c.fecha_expiracion) as minutos_restantes
            FROM codigos_cancelacion c
            JOIN productos p ON c.producto_id = p.id
            JOIN ordenes o ON c.orden_id = o.id
            JOIN mesas m ON o.mesa_id = m.id
            JOIN usuarios u ON c.solicitado_por = u.id
            JOIN orden_productos op ON c.orden_id = op.orden_id AND c.producto_id = op.producto_id
            WHERE c.usado = 0 AND c.fecha_expiracion > NOW()
            ORDER BY c.fecha_creacion DESC
        ");
        $stmt->execute();
        $solicitudes_ajax = $stmt->fetchAll();
        
        // Generar HTML para las solicitudes
        ob_start();
        foreach ($solicitudes_ajax as $solicitud): ?>
            <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-600 overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="text-xl font-bold text-white mb-1">
                                Mesa <?= htmlspecialchars($solicitud['mesa_nombre'] ?? 'N/A') ?>
                            </h4>
                            <p class="text-slate-400">Orden: <?= htmlspecialchars($solicitud['orden_codigo'] ?? 'N/A') ?></p>
                        </div>
                        <div class="text-right">
                            <div class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                PIN: <?= $solicitud['codigo'] ?? 'N/A' ?>
                            </div>
                            <p class="text-slate-400 text-sm mt-1">
                                Hace <?= $solicitud['minutos_transcurridos'] ?> min
                            </p>
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-slate-900 rounded-xl p-4">
                            <h5 class="text-white font-semibold mb-2">Producto a cancelar:</h5>
                            <p class="text-blue-400 font-bold"><?= htmlspecialchars($solicitud['producto_nombre'] ?? 'N/A') ?></p>
                            <div class="mt-2 text-sm text-slate-300">
                                <p>Cantidad solicitada: <span class="text-orange-400 font-semibold"><?= $solicitud['cantidad_solicitada'] ?> unidad(es)</span></p>
                                <?php if ($solicitud['cantidad_preparada'] > 0): ?>
                                <p class="text-yellow-400">⚠️ Tiene <?= $solicitud['cantidad_preparada'] ?> preparado(s)</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="bg-slate-900 rounded-xl p-4">
                            <h5 class="text-white font-semibold mb-2">Solicitado por:</h5>
                            <p class="text-green-400 font-bold"><?= htmlspecialchars($solicitud['solicitante'] ?? 'N/A') ?></p>
                            <div class="mt-2 text-sm text-slate-300">
                                <p>Mesa: <?= htmlspecialchars($solicitud['mesa_nombre'] ?? 'N/A') ?></p>
                                <p>Orden: <?= htmlspecialchars($solicitud['orden_codigo'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($solicitud['razon'])): ?>
                    <div class="mt-4 bg-slate-900 rounded-xl p-4">
                        <h5 class="text-white font-semibold mb-2">Razón de cancelación:</h5>
                        <p class="text-slate-300"><?= htmlspecialchars($solicitud['razon'] ?? '') ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-6 flex justify-between items-center">
                        <div class="flex space-x-2">
                            <?php if ($solicitud['minutos_restantes'] <= 5): ?>
                            <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm">
                                <i class="bi bi-clock-fill mr-1"></i>Expira en <?= $solicitud['minutos_restantes'] ?> min
                            </span>
                            <?php else: ?>
                            <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm">
                                <i class="bi bi-clock mr-1"></i>Válido por <?= $solicitud['minutos_restantes'] ?> min
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <button onclick="usarCodigo('<?= $solicitud['codigo'] ?? '' ?>')" 
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl font-semibold transition-colors">
                            <i class="bi bi-check-lg mr-2"></i>Usar PIN
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach;
        
        $html = ob_get_clean();
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'total_solicitudes' => count($solicitudes_ajax),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Obtener solicitudes de cancelación pendientes para la carga inicial
$solicitudes = [];
$tabla_error = false;

try {
    $solicitudes = $pdo->query("
        SELECT c.*, p.nombre as producto_nombre, m.nombre as mesa_nombre, 
               u.nombre_completo as solicitante, o.codigo as orden_codigo,
               op.cantidad as cantidad_total,
               op.preparado as cantidad_preparada,
               op.cancelado as cantidad_cancelada,
               COALESCE(c.cantidad_solicitada, 1) as cantidad_solicitada,
               TIMESTAMPDIFF(MINUTE, c.fecha_creacion, NOW()) as minutos_transcurridos
        FROM codigos_cancelacion c
        JOIN productos p ON c.producto_id = p.id
        JOIN ordenes o ON c.orden_id = o.id
        JOIN mesas m ON o.mesa_id = m.id
        JOIN usuarios u ON c.solicitado_por = u.id
        JOIN orden_productos op ON c.orden_id = op.orden_id AND c.producto_id = op.producto_id
        WHERE c.usado = 0 AND c.fecha_expiracion > NOW()
        ORDER BY c.fecha_creacion DESC
    ")->fetchAll();
} catch (PDOException $e) {
    // Si la tabla no existe o hay un error, marcar error y crear array vacío
    $tabla_error = true;
    $solicitudes = [];
}
?>

<div class="max-w-7xl mx-auto p-6">
    <div class="bg-gradient-to-r from-red-800 to-red-700 rounded-2xl shadow-2xl p-6 mb-6 border border-red-600">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-red-500 p-3 rounded-xl shadow-lg">
                    <i class="bi bi-shield-exclamation text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Panel de Autorización</h1>
                    <p class="text-red-100 text-sm">Solicitudes de cancelación pendientes</p>
                </div>
            </div>
            <div class="bg-red-600 px-4 py-2 rounded-xl">
                <span class="text-white font-bold"><?= count($solicitudes) ?> pendientes</span>
            </div>
        </div>
    </div>

    <?php if (empty($solicitudes)): ?>
    <div class="bg-slate-800 rounded-2xl shadow-xl p-12 text-center border border-slate-600">
        <?php if ($tabla_error): ?>
        <!-- Mensaje de error de tabla no encontrada -->
        <i class="bi bi-database text-6xl text-yellow-500 mb-4"></i>
        <h3 class="text-2xl font-bold text-white mb-2">Base de datos no configurada</h3>
        <p class="text-slate-400 mb-4">Las tablas necesarias para el sistema de autorizaciones no existen.</p>
        <div class="bg-slate-900 rounded-xl p-4 text-left text-sm">
            <p class="text-green-400 mb-2">📁 Ejecuta el archivo SQL:</p>
            <code class="text-blue-300">database_autorizaciones.sql</code>
            <p class="text-slate-400 mt-2">Este archivo contiene las tablas necesarias para el funcionamiento del sistema.</p>
        </div>
        <?php else: ?>
        <!-- Mensaje de no hay solicitudes -->
        <i class="bi bi-check-circle text-6xl text-green-500 mb-4"></i>
        <h3 class="text-2xl font-bold text-white mb-2">No hay solicitudes pendientes</h3>
        <p class="text-slate-400">Todas las solicitudes de cancelación han sido procesadas</p>
        <?php endif; ?>
    </div>
    <?php else: ?>
    
    <!-- Indicador de estado de actualización -->
    <div class="bg-slate-700 rounded-xl p-3 mb-4 border border-slate-600 flex justify-between items-center">
        <div class="flex items-center text-slate-300">
            <i class="bi bi-arrow-clockwise mr-2"></i>
            <span>Actualización automática cada 30s</span>
        </div>
        <div class="flex items-center text-slate-400 text-sm">
            <span id="ultima-actualizacion">Cargando...</span>
            <div id="indicador-conexion" class="ml-3 w-2 h-2 bg-green-500 rounded-full"></div>
        </div>
    </div>
    
    <!-- Formulario de autorización -->
    <div class="bg-slate-800 rounded-2xl shadow-xl p-6 mb-6 border border-slate-600">
        <h3 class="text-xl font-bold text-white mb-4">
            <i class="bi bi-key mr-2"></i>Autorizar Cancelación
        </h3>
        <form id="form-autorizacion" class="flex space-x-4">
            <div class="flex-1">
                <input type="text" 
                       id="codigo-pin" 
                       placeholder="Ingrese el código PIN de 6 dígitos" 
                       class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all text-center text-lg font-mono"
                       maxlength="6">
            </div>
            <button type="submit" 
                    class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition-colors">
                <i class="bi bi-check-lg mr-2"></i>Autorizar
            </button>
        </form>
    </div>

    <!-- Lista de solicitudes -->
    <div class="grid gap-6">
        <?php foreach ($solicitudes as $solicitud): ?>
        <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-600 overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h4 class="text-xl font-bold text-white mb-1">
                            Mesa <?= htmlspecialchars($solicitud['mesa_nombre'] ?? 'N/A') ?>
                        </h4>
                        <p class="text-slate-400">Orden: <?= htmlspecialchars($solicitud['orden_codigo'] ?? 'N/A') ?></p>
                    </div>
                    <div class="text-right">
                        <div class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                            PIN: <?= $solicitud['codigo'] ?? 'N/A' ?>
                        </div>
                        <p class="text-slate-400 text-sm mt-1">
                            Hace <?= $solicitud['minutos_transcurridos'] ?> min
                        </p>
                    </div>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-900 rounded-xl p-4">
                        <h5 class="text-white font-semibold mb-2">Producto a cancelar:</h5>
                        <p class="text-blue-400 font-bold"><?= htmlspecialchars($solicitud['producto_nombre'] ?? 'N/A') ?></p>
                    </div>
                    
                    <div class="bg-slate-900 rounded-xl p-4">
                        <h5 class="text-white font-semibold mb-2">Solicitado por:</h5>
                        <p class="text-green-400"><?= htmlspecialchars($solicitud['solicitante'] ?? 'N/A') ?></p>
                    </div>
                </div>
                
                <?php if ($solicitud['razon']): ?>
                <div class="mt-4 bg-slate-900 rounded-xl p-4">
                    <h5 class="text-white font-semibold mb-2">Motivo:</h5>
                    <p class="text-slate-300"><?= htmlspecialchars($solicitud['razon'] ?? '') ?></p>
                </div>
                <?php endif; ?>
                
                <div class="mt-4 flex justify-between items-center">
                    <div class="text-slate-400 text-sm">
                        <i class="bi bi-clock mr-1"></i>
                        Expira en <?= 10 - $solicitud['minutos_transcurridos'] ?> minutos
                    </div>
                    <button onclick="usarCodigo('<?= $solicitud['codigo'] ?? '' ?>')" 
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <i class="bi bi-check-lg mr-1"></i>Usar este código
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-autorizacion');
    const inputPin = document.getElementById('codigo-pin');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const pin = inputPin.value.trim();
            if (pin.length !== 6) {
                Swal.fire('Error', 'El PIN debe tener 6 dígitos', 'error');
                return;
            }
            
            autorizarCancelacion(pin);
        });
    }
    
    // Auto-actualizar cada 30 segundos usando AJAX interno
    setInterval(() => {
        actualizarSolicitudes();
    }, 30000);
    
    function actualizarSolicitudes() {
        const indicador = document.getElementById('indicador-conexion');
        const ultimaActualizacion = document.getElementById('ultima-actualizacion');
        
        // Cambiar indicador a amarillo (cargando)
        if (indicador) indicador.className = 'ml-3 w-2 h-2 bg-yellow-500 rounded-full';
        
        // Usar la misma página con parámetro AJAX
        const formData = new FormData();
        formData.append('ajax_update', '1');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar solo el contenido de las solicitudes
                const contenedor = document.querySelector('.grid.gap-6');
                if (contenedor && data.html) {
                    contenedor.innerHTML = data.html;
                }
                
                // Actualizar indicadores
                if (indicador) indicador.className = 'ml-3 w-2 h-2 bg-green-500 rounded-full';
                if (ultimaActualizacion) {
                    const ahora = new Date();
                    ultimaActualizacion.textContent = `Último: ${ahora.toLocaleTimeString()}`;
                }
                
            } else if (data.redirect) {
                // Solo redirigir si hay un problema de sesión
                window.location.href = data.redirect;
            } else {
                // Error en la actualización
                if (indicador) indicador.className = 'ml-3 w-2 h-2 bg-red-500 rounded-full';
            }
        })
        .catch(error => {
            console.error('Error actualizando solicitudes:', error);
            if (indicador) indicador.className = 'ml-3 w-2 h-2 bg-red-500 rounded-full';
        });
    }
    
    // Ejecutar primera actualización después de 5 segundos
    setTimeout(actualizarSolicitudes, 5000);
});

function usarCodigo(codigo) {
    document.getElementById('codigo-pin').value = codigo;
    autorizarCancelacion(codigo);
}

function autorizarCancelacion(pin) {
    Swal.fire({
        title: 'Procesando autorización...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('controllers/newPos/autorizar_cancelacion.php', {
        method: 'POST',
        body: new URLSearchParams({
            codigo_pin: pin
        }),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Acceder correctamente a los datos anidados
            const detalles = data.data?.detalles || {};
            Swal.fire({
                icon: 'success',
                title: 'Cancelación autorizada',
                html: `
                    <div class="text-left">
                        <p><strong>Mesa:</strong> ${detalles.mesa || 'N/A'}</p>
                        <p><strong>Producto:</strong> ${detalles.producto || 'N/A'}</p>
                        <p><strong>Solicitante:</strong> ${detalles.solicitante || 'N/A'}</p>
                        <p><strong>Nuevo total:</strong> $${data.nuevo_total || '0.00'}</p>
                    </div>
                `,
                timer: 3000
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        console.error('Stack trace:', error.stack);
        Swal.fire('Error', 'Ocurrió un error al procesar la autorización', 'error');
    });
}
</script>
