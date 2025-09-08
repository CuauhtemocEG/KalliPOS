<?php
/**
 * Sistema de Impresión Local para Laptop Base
 * Este archivo genera tickets que se imprimen directamente desde el navegador
 */

// Datos de ejemplo para ticket de prueba
$datosPrueba = [
    'id' => 'PRUEBA-001',
    'fecha_creacion' => date('Y-m-d H:i:s'),
    'mesa_nombre' => 'Configuración',
    'productos' => [
        (object)[
            'nombre' => 'Producto de Prueba 1', 
            'cantidad' => 2, 
            'precio' => 25.00,
            'categoria_nombre' => 'Bebidas'
        ],
        (object)[
            'nombre' => 'Producto de Prueba 2', 
            'cantidad' => 1, 
            'precio' => 15.50,
            'categoria_nombre' => 'Comida'
        ],
        (object)[
            'nombre' => 'Producto de Prueba 3', 
            'cantidad' => 3, 
            'precio' => 8.75,
            'categoria_nombre' => 'Postres'
        ]
    ],
    'subtotal' => 76.00,
    'total' => 88.16
];

// Verificar si es una prueba o una orden real
$esPrueba = isset($_GET['tipo']) && $_GET['tipo'] === 'prueba';
$orden_id = $esPrueba ? null : ($_GET['orden_id'] ?? null);

if (!$esPrueba && !$orden_id) {
    die('Error: ID de orden no proporcionado');
}

// Si es prueba, usar datos de ejemplo
if ($esPrueba) {
    $orden = (object)$datosPrueba;
    $productos = $datosPrueba['productos'];
} else {
    // Incluir conexión para orden real
    require_once '../conexion.php';

    try {
        $pdo = conexion();
        
        // Obtener datos de la orden
        $stmt = $pdo->prepare("
            SELECT o.*, m.nombre as mesa_nombre
            FROM ordenes o 
            JOIN mesas m ON o.mesa_id = m.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orden_id]);
        $orden = $stmt->fetch();
        
        if (!$orden) {
            die('Error: Orden no encontrada');
        }
        
        // Obtener productos de la orden
        $stmt = $pdo->prepare("
            SELECT op.*, p.nombre, p.precio, p.categoria_id,
                   c.nombre as categoria_nombre
            FROM orden_productos op
            JOIN productos p ON op.producto_id = p.id
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE op.orden_id = ?
            ORDER BY c.nombre, p.nombre
        ");
        $stmt->execute([$orden_id]);
        $productos = $stmt->fetchAll();
        
    } catch (Exception $e) {
        die('Error de base de datos: ' . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Orden #<?= $orden['id'] ?></title>
    <style>
        /* Estilos para impresión */
        @media print {
            body { margin: 0; font-family: 'Courier New', monospace; }
            .no-print { display: none; }
            .ticket { width: 58mm; font-size: 12px; }
        }
        
        /* Estilos para pantalla */
        @media screen {
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .ticket { 
                width: 300px; 
                margin: 0 auto; 
                background: white; 
                padding: 20px; 
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .btn {
                background: #007bff;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                margin: 5px;
                font-size: 14px;
            }
            .btn:hover { background: #0056b3; }
            .btn-success { background: #28a745; }
            .btn-success:hover { background: #1e7e34; }
        }
        
        .ticket {
            font-family: 'Courier New', monospace;
            line-height: 1.2;
        }
        
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .separator { border-top: 1px dashed #000; margin: 10px 0; }
        .item-line { display: flex; justify-content: space-between; margin: 2px 0; }
        .total-line { font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    <!-- Botones de control (no se imprimen) -->
    <div class="no-print center" style="margin-bottom: 20px;">
        <?php if ($esPrueba): ?>
            <h2>🧪 Ticket de Prueba - Configuración</h2>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;">
                <strong>✅ ¡Configuración Exitosa!</strong><br>
                Este es un ticket de ejemplo para probar la impresión desde tu navegador.
            </div>
        <?php else: ?>
            <h2>🎫 Ticket de Orden #<?= $orden->id ?></h2>
        <?php endif; ?>
        
        <button class="btn" onclick="window.print()">🖨️ Imprimir Ticket</button>
        <button class="btn btn-success" onclick="imprimirYCerrar()">🖨️ Imprimir y Cerrar</button>
        <button class="btn" onclick="window.close()">❌ Cerrar</button>
        
        <div style="margin: 20px 0; padding: 15px; background: #e9ecef; border-radius: 5px;">
            <strong>💡 Instrucciones:</strong><br>
            1. Haz clic en "Imprimir Ticket"<br>
            2. Selecciona tu impresora en el diálogo<br>
            3. Para impresoras térmicas, ajusta el tamaño a 58mm o "Receipt"<br>
            4. El ticket se imprimirá directamente desde tu laptop
        </div>
    </div>

    <!-- Contenido del ticket -->
    <div class="ticket">
        <div class="center bold">
            <?= htmlspecialchars(APP_NAME ?? 'RESTAURANT POS') ?>
        </div>
        <div class="center">
            Tel: (555) 123-4567<br>
            RFC: ABCD123456789
        </div>
        
        <div class="separator"></div>
        
        <div class="center bold">
            <?php if ($esPrueba): ?>
                🧪 TICKET DE PRUEBA
            <?php else: ?>
                ORDEN #<?= str_pad($orden->id, 6, '0', STR_PAD_LEFT) ?>
            <?php endif; ?>
        </div>
        
        <div class="separator"></div>
        
        <div>
            <strong>Mesa:</strong> <?= htmlspecialchars($orden->mesa_nombre) ?><br>
            <strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($orden->fecha_creacion)) ?><br>
            <?php if (!$esPrueba): ?>
                <strong>Estado:</strong> <?= strtoupper($orden->estado) ?>
            <?php else: ?>
                <strong>Tipo:</strong> CONFIGURACIÓN DE PRUEBA
            <?php endif; ?>
        </div>
        
        <div class="separator"></div>
        
        <!-- Productos -->
        <?php 
        $subtotal = 0;
        $categoria_actual = '';
        
        foreach ($productos as $producto): 
            // Acceso a propiedades para objetos y arrays
            $cat_nombre = is_object($producto) ? $producto->categoria_nombre : $producto['categoria_nombre'];
            $nombre = is_object($producto) ? $producto->nombre : $producto['nombre'];
            $cantidad = is_object($producto) ? $producto->cantidad : $producto['cantidad'];
            $precio = is_object($producto) ? $producto->precio : ($producto['precio_unitario'] ?? $producto['precio']);
            
            // Mostrar categoría si cambió
            if ($categoria_actual !== $cat_nombre) {
                $categoria_actual = $cat_nombre;
                if ($categoria_actual) {
                    echo "<div class='bold'>--- " . htmlspecialchars($categoria_actual) . " ---</div>";
                }
            }
            
            $total_producto = $cantidad * $precio;
            $subtotal += $total_producto;
        ?>
            <div class="item-line">
                <span><?= $cantidad ?>x <?= htmlspecialchars($nombre) ?></span>
                <span>$<?= number_format($total_producto, 2) ?></span>
            </div>
            <?php 
            $comentarios = is_object($producto) ? ($producto->comentarios ?? null) : ($producto['comentarios'] ?? null);
            if (!empty($comentarios)): 
            ?>
                <div style="font-size: 10px; margin-left: 10px; color: #666;">
                    * <?= htmlspecialchars($comentarios) ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <div class="separator"></div>
        
        <!-- Totales -->
        <?php if ($esPrueba): ?>
            <!-- Usar totales predefinidos para la prueba -->
            <div class="item-line">
                <span>SUBTOTAL:</span>
                <span>$<?= number_format($orden->subtotal, 2) ?></span>
            </div>
            
            <div class="item-line">
                <span>IVA (16%):</span>
                <span>$<?= number_format($orden->total - $orden->subtotal, 2) ?></span>
            </div>
            
            <div class="separator"></div>
            
            <div class="item-line total-line">
                <span>TOTAL:</span>
                <span>$<?= number_format($orden->total, 2) ?></span>
            </div>
        <?php else: ?>
            <!-- Calcular totales para orden real -->
            <div class="item-line">
                <span>SUBTOTAL:</span>
                <span>$<?= number_format($subtotal, 2) ?></span>
            </div>
            
            <?php 
            $iva = $subtotal * 0.16; // 16% IVA
            $total = $subtotal + $iva;
            ?>
            
            <div class="item-line">
                <span>IVA (16%):</span>
                <span>$<?= number_format($iva, 2) ?></span>
            </div>
            
            <div class="separator"></div>
            
            <div class="item-line total-line">
                <span>TOTAL:</span>
                <span>$<?= number_format($total, 2) ?></span>
            </div>
        <?php endif; ?>
        
        <div class="separator"></div>
        
        <div class="center">
            ¡Gracias por su preferencia!<br>
            <small>Ticket generado: <?= date('d/m/Y H:i:s') ?></small>
        </div>
        
        <div class="center" style="margin-top: 10px;">
            <?php if ($esPrueba): ?>
                <small>🧪 Sistema POS - Ticket de Prueba</small>
            <?php else: ?>
                <small>Sistema POS - Orden #<?= $orden->id ?></small>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function imprimirYCerrar() {
            window.print();
            // Cerrar después de un breve delay para que termine la impresión
            setTimeout(() => {
                window.close();
            }, 1000);
        }
        
        // Auto-detectar si es una impresora térmica y ajustar estilos
        window.addEventListener('beforeprint', function() {
            // Detectar impresoras térmicas comunes
            const mediaQuery = window.matchMedia('print');
            if (mediaQuery.matches) {
                // Ajustar estilos para impresión térmica
                document.querySelector('.ticket').style.width = '58mm';
                document.querySelector('.ticket').style.fontSize = '11px';
            }
        });
        
        // Función para detectar tipo de impresora
        function configurarImpresion() {
            const config = {
                'Térmica 58mm': { width: '58mm', fontSize: '10px' },
                'Térmica 80mm': { width: '80mm', fontSize: '12px' },
                'Láser/Inkjet': { width: '210mm', fontSize: '14px' }
            };
            
            const tipo = prompt('Selecciona tipo de impresora:\n1. Térmica 58mm\n2. Térmica 80mm\n3. Láser/Inkjet\n\nEscribe el número:', '1');
            
            let selectedConfig;
            switch(tipo) {
                case '1': selectedConfig = config['Térmica 58mm']; break;
                case '2': selectedConfig = config['Térmica 80mm']; break;
                case '3': selectedConfig = config['Láser/Inkjet']; break;
                default: selectedConfig = config['Térmica 58mm'];
            }
            
            const ticket = document.querySelector('.ticket');
            ticket.style.width = selectedConfig.width;
            ticket.style.fontSize = selectedConfig.fontSize;
            
            // Aplicar configuración para impresión
            const style = document.createElement('style');
            style.textContent = `
                @media print {
                    .ticket { 
                        width: ${selectedConfig.width} !important; 
                        font-size: ${selectedConfig.fontSize} !important; 
                    }
                }
            `;
            document.head.appendChild(style);
            
            alert('Configuración aplicada. Ahora puedes imprimir.');
        }
        
        // Agregar botón de configuración
        document.addEventListener('DOMContentLoaded', function() {
            const controls = document.querySelector('.no-print');
            const configBtn = document.createElement('button');
            configBtn.className = 'btn';
            configBtn.textContent = '⚙️ Configurar Impresora';
            configBtn.onclick = configurarImpresion;
            controls.appendChild(configBtn);
        });
    </script>
</body>
</html>
