<?php
/**
 * Ticket Optimizado para Impresión por Navegador
 * Diseñado específicamente para HostGator y hostings compartidos
 */

require_once '../conexion.php';

$orden_id = $_GET['orden_id'] ?? null;
if (!$orden_id) {
    die('Error: ID de orden no proporcionado');
}

try {
    $pdo = conexion();
    
    // Obtener datos de la orden
    $stmt = $pdo->prepare("SELECT o.*, m.nombre as mesa_nombre FROM ordenes o JOIN mesas m ON o.mesa_id = m.id WHERE o.id = ?");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orden) {
        throw new Exception('Orden no encontrada');
    }
    
    // Obtener productos
    $stmt = $pdo->prepare("
        SELECT op.*, p.nombre, p.precio 
        FROM orden_productos op 
        JOIN productos p ON op.producto_id = p.id 
        WHERE op.orden_id = ? AND op.preparado = 1 AND op.cancelado = 0
    ");
    $stmt->execute([$orden_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener configuración de la empresa
    $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('empresa_nombre', 'empresa_direccion', 'empresa_telefono', 'logo_activado', 'logo_imagen')");
    $stmt->execute();
    $configuracion = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Formatear fecha
$fecha_formateada = date('d/m/Y H:i', strtotime($orden['creada_en']));

// Calcular total de productos mostrados
$total_productos = 0;
foreach ($productos as $producto) {
    $total_productos += $producto['precio'] * $producto['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - Orden <?= htmlspecialchars($orden['codigo']) ?></title>
    
    <!-- CSS optimizado para impresión térmica -->
    <style>
        /* Estilos para pantalla */
        @media screen {
            body {
                font-family: 'Courier New', monospace;
                max-width: 400px;
                margin: 20px auto;
                padding: 20px;
                background: #f5f5f5;
                border: 2px solid #ddd;
                border-radius: 10px;
                line-height: 1.3;
            }
            
            .no-print {
                display: block;
                margin: 20px 0;
                text-align: center;
            }
            
            .btn {
                background: #007bff;
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                margin: 5px;
                transition: background 0.3s;
            }
            
            .btn:hover { background: #0056b3; }
            .btn-success { background: #28a745; }
            .btn-secondary { background: #6c757d; }
            
            .preview-header {
                background: #007bff;
                color: white;
                padding: 10px;
                text-align: center;
                margin: -20px -20px 20px -20px;
                border-radius: 8px 8px 0 0;
            }
        }
        
        /* Estilos específicos para impresión */
        @media print {
            body {
                font-family: 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.2;
                margin: 0;
                padding: 5px;
                width: 300px; /* Ancho típico de impresora térmica */
                background: white;
            }
            
            .no-print { display: none !important; }
            
            .ticket-header {
                text-align: center;
                margin-bottom: 10px;
            }
            
            .ticket-content {
                font-size: 11px;
            }
            
            .separator {
                border-top: 1px dashed #000;
                margin: 8px 0;
            }
            
            .total-section {
                font-weight: bold;
                font-size: 13px;
                text-align: right;
                margin-top: 10px;
            }
            
            .producto-line {
                display: flex;
                justify-content: space-between;
                margin-bottom: 2px;
            }
            
            .empresa-logo {
                max-width: 150px;
                max-height: 80px;
                margin: 0 auto 10px auto;
                display: block;
            }
            
            .footer-info {
                text-align: center;
                font-size: 10px;
                margin-top: 15px;
            }
            
            /* Forzar salto de página después del ticket */
            .ticket-container {
                page-break-after: always;
            }
        }
        
        /* Estilos generales */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        
        .separator {
            border-top: 1px dashed #333;
            margin: 10px 0;
        }
        
        .producto-tabla {
            width: 100%;
            margin: 10px 0;
        }
        
        .producto-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }
        
        .producto-nombre {
            flex: 1;
            padding-right: 10px;
        }
        
        .producto-cant {
            width: 30px;
            text-align: center;
        }
        
        .producto-precio {
            width: 70px;
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Header solo visible en pantalla -->
    <div class="preview-header no-print">
        <h3>🎫 Vista Previa del Ticket</h3>
        <p>Orden #<?= htmlspecialchars($orden['codigo']) ?></p>
    </div>
    
    <!-- Botones de acción (solo en pantalla) -->
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-success">
            🖨️ Imprimir Ticket
        </button>
        <button onclick="imprimirDirecto()" class="btn">
            ⚡ Impresión Rápida
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            ❌ Cerrar
        </button>
    </div>
    
    <!-- Contenido del ticket -->
    <div class="ticket-container">
        <!-- Logo de la empresa (si está configurado) -->
        <?php if (($configuracion['logo_activado'] ?? '1') == '1' && !empty($configuracion['logo_imagen'])): ?>
            <div class="text-center mb-2">
                <img src="../assets/img/<?= htmlspecialchars($configuracion['logo_imagen']) ?>" 
                     alt="Logo" class="empresa-logo">
            </div>
        <?php endif; ?>
        
        <!-- Header del ticket -->
        <div class="ticket-header text-center mb-3">
            <div class="font-bold" style="font-size: 14px;">
                <?= htmlspecialchars($configuracion['empresa_nombre'] ?? 'RESTAURANT POS') ?>
            </div>
            <?php if (!empty($configuracion['empresa_direccion'])): ?>
                <div style="font-size: 10px;">
                    <?= htmlspecialchars($configuracion['empresa_direccion']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($configuracion['empresa_telefono'])): ?>
                <div style="font-size: 10px;">
                    Tel: <?= htmlspecialchars($configuracion['empresa_telefono']) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="separator"></div>
        
        <!-- Información de la orden -->
        <div class="ticket-content">
            <div class="mb-2">
                <strong>Mesa:</strong> <?= htmlspecialchars($orden['mesa_nombre']) ?><br>
                <strong>Orden:</strong> #<?= htmlspecialchars($orden['codigo']) ?><br>
                <strong>Fecha:</strong> <?= $fecha_formateada ?><br>
                <?php if ($orden['estado'] === 'cerrada'): ?>
                    <strong>Estado:</strong> PAGADA ✅
                <?php endif; ?>
            </div>
            
            <div class="separator"></div>
            
            <!-- Productos -->
            <div class="mb-3">
                <div class="font-bold mb-2">PRODUCTOS:</div>
                
                <?php foreach ($productos as $producto): ?>
                    <div class="producto-row">
                        <div class="producto-nombre">
                            <?= htmlspecialchars(substr($producto['nombre'], 0, 25)) ?>
                        </div>
                        <div class="producto-cant">
                            <?= $producto['cantidad'] ?>
                        </div>
                        <div class="producto-precio">
                            $<?= number_format($producto['precio'] * $producto['cantidad'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="separator"></div>
            
            <!-- Total -->
            <div class="total-section">
                <div style="font-size: 16px;">
                    TOTAL: $<?= number_format($orden['total'], 2) ?>
                </div>
            </div>
            
            <?php if ($orden['estado'] === 'cerrada' && !empty($orden['metodo_pago'])): ?>
                <div class="separator"></div>
                <div class="mb-2">
                    <strong>MÉTODO DE PAGO:</strong> <?= strtoupper($orden['metodo_pago']) ?><br>
                    
                    <?php if ($orden['metodo_pago'] === 'efectivo' && !empty($orden['dinero_recibido'])): ?>
                        <strong>Recibido:</strong> $<?= number_format($orden['dinero_recibido'], 2) ?><br>
                        <?php if (!empty($orden['cambio']) && $orden['cambio'] > 0): ?>
                            <strong>Cambio:</strong> $<?= number_format($orden['cambio'], 2) ?>
                        <?php else: ?>
                            <strong>Pago exacto</strong>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="separator"></div>
            
            <!-- Footer -->
            <div class="footer-info text-center">
                <div>¡Gracias por su preferencia!</div>
                <div style="font-size: 9px; margin-top: 5px;">
                    Ticket generado: <?= date('d/m/Y H:i:s') ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Instrucciones para imprimir (solo en pantalla) -->
    <div class="no-print" style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px; border: 1px solid #b8daff;">
        <h4 style="color: #004085; margin-bottom: 10px;">📋 Instrucciones de Impresión:</h4>
        <ol style="color: #004085; font-size: 14px;">
            <li><strong>Presiona "🖨️ Imprimir Ticket"</strong> arriba</li>
            <li><strong>Selecciona tu impresora térmica</strong> en el diálogo</li>
            <li><strong>Configura el papel:</strong> Selecciona tamaño personalizado (80mm x continuo)</li>
            <li><strong>Ajusta márgenes:</strong> Mínimos (0mm)</li>
            <li><strong>¡Presiona Imprimir!</strong></li>
        </ol>
        
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 3px; margin-top: 10px;">
            <strong>💡 Consejo:</strong> Si no tienes impresora térmica, selecciona cualquier impresora y ajusta el tamaño de papel a A4 vertical.
        </div>
    </div>
    
    <script>
        // Función de impresión directa
        function imprimirDirecto() {
            // Remover elementos no imprimibles temporalmente
            const noprint = document.querySelectorAll('.no-print');
            noprint.forEach(el => el.style.display = 'none');
            
            // Imprimir
            window.print();
            
            // Restaurar elementos
            setTimeout(() => {
                noprint.forEach(el => el.style.display = 'block');
            }, 1000);
        }
        
        // Auto-focus para impresión rápida
        document.addEventListener('keydown', function(e) {
            // Ctrl+P o Cmd+P
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                imprimirDirecto();
            }
            
            // Escape para cerrar
            if (e.key === 'Escape') {
                window.close();
            }
        });
        
        // Mostrar mensaje si la ventana no se puede cerrar
        window.addEventListener('beforeunload', function(e) {
            // No mostrar confirmación para cierre normal
            return undefined;
        });
        
        // Detectar si es impresora térmica
        function detectarImpresoraTermica() {
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('print');
                mediaQuery.addListener(function(mq) {
                    if (mq.matches) {
                        console.log('Iniciando impresión...');
                        // Aquí podrías agregar lógica específica para impresoras térmicas
                    }
                });
            }
        }
        
        detectarImpresoraTermica();
    </script>
</body>
</html>
