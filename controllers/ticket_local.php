<?php
/**
 * Sistema de Impresión Local para Laptop Base
 * Este archivo genera tickets que se imprimen directamente desde el navegador
 * Formato idéntico al ticket térmico
 */

/**
 * Convertir número a texto en español (idéntico a imprimir_termica.php)
 */
function numeroATextoHelper($numero) {
    $unidades = array('', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
                     'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 
                     'dieciocho', 'diecinueve', 'veinte');
    
    $decenas = array('', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa');
    $centenas = array('', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos');
    
    if ($numero == 0) return 'cero pesos';
    
    $entero = floor($numero);
    $decimales = round(($numero - $entero) * 100);
    
    $texto = '';
    
    // Procesar miles
    if ($entero >= 1000) {
        $miles = floor($entero / 1000);
        if ($miles == 1) {
            $texto .= 'mil ';
        } else {
            $texto .= numeroATextoHelper($miles) . ' mil ';
        }
        $entero = $entero % 1000;
    }
    
    // Procesar centenas
    if ($entero >= 100) {
        $cen = floor($entero / 100);
        if ($entero == 100) {
            $texto .= 'cien ';
        } else {
            $texto .= $centenas[$cen] . ' ';
        }
        $entero = $entero % 100;
    }
    
    // Procesar decenas y unidades
    if ($entero >= 21) {
        $dec = floor($entero / 10);
        $uni = $entero % 10;
        $texto .= $decenas[$dec];
        if ($uni > 0) {
            $texto .= ' y ' . $unidades[$uni];
        }
    } else if ($entero > 0) {
        $texto .= $unidades[$entero];
    }
    
    $texto .= ' pesos';
    
    if ($decimales > 0) {
        $texto .= ' con ' . $decimales . '/100';
    }
    
    return trim($texto);
}

/**
 * Formatear método de pago (idéntico a imprimir_termica.php)
 */
function formatearMetodoPagoHelper($metodo) {
    $metodos = [
        'efectivo' => 'EFECTIVO',
        'tarjeta' => 'TARJETA',
        'transferencia' => 'TRANSFERENCIA',
        'cheque' => 'CHEQUE'
    ];
    
    return $metodos[$metodo] ?? strtoupper($metodo);
}

// Datos de ejemplo para ticket de prueba
$datosPrueba = [
    'id' => 'PRUEBA-001',
    'fecha_creacion' => date('Y-m-d H:i:s'),
    'mesa_nombre' => 'Configuración',
    'productos' => [
        (object)[
            'nombre' => 'Producto de Prueba 1', 
            'cantidad' => 2, 
            'precio' => 25.00
        ],
        (object)[
            'nombre' => 'Producto de Prueba 2', 
            'cantidad' => 1, 
            'precio' => 15.50
        ],
        (object)[
            'nombre' => 'Producto de Prueba 3', 
            'cantidad' => 3, 
            'precio' => 8.75
        ]
    ],
    'subtotal' => 76.00,
    'total' => 88.16,
    'metodo_pago' => 'efectivo',
    'dinero_recibido' => 100.00,
    'cambio' => 11.84,
    'estado' => 'cerrada'
];

// Verificar si es una prueba o una orden real
$esPrueba = isset($_GET['tipo']) && $_GET['tipo'] === 'prueba';
$orden_id = $esPrueba ? null : ($_GET['orden_id'] ?? null);

if (!$esPrueba && !$orden_id) {
    die('Error: ID de orden no proporcionado');
}

// Datos de configuración por defecto
$sucursal_nombre = 'RESTAURANT POS';

// Si es prueba, usar datos de ejemplo
if ($esPrueba) {
    $orden = (object)$datosPrueba;
    $productos = $datosPrueba['productos'];
} else {
    // Incluir conexión para orden real
    require_once '../conexion.php';

    try {
        $pdo = conexion();
        
        // Obtener datos de la orden (formato idéntico a imprimir_termica.php)
        $stmt = $pdo->prepare("SELECT * FROM ordenes o JOIN mesas m WHERE o.mesa_id=mesa_id AND o.id = ?");
        $stmt->execute([$orden_id]);
        $orden = $stmt->fetch();
        
        if (!$orden) {
            die('Error: Orden no encontrada');
        }
        
        // Obtener productos de la orden (solo productos preparados, no cancelados)
        $stmt = $pdo->prepare("
            SELECT op.*, p.nombre, p.precio 
            FROM orden_productos op 
            JOIN productos p ON op.producto_id = p.id 
            WHERE op.orden_id = ? AND op.preparado = 1
        ");
        $stmt->execute([$orden_id]);
        $productos = $stmt->fetchAll();

        // Obtener datos de la sucursal (idéntico a imprimir_termica.php)
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'empresa_nombre'");
        $stmt->execute();
        $sucursal = $stmt->fetch();
        
        if ($sucursal) {
            $sucursal_nombre = $sucursal['valor'];
        }
        
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
            .ticket { width: 58mm; font-size: 10px; }
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
            font-size: 12px;
        }
        
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .large { font-size: 16px; }
        .separator { 
            border-top: 1px dashed #000; 
            margin: 8px 0; 
            width: 100%;
        }
        
        /* Tabla de productos - formato idéntico a ticket térmico */
        .product-table {
            width: 100%;
            font-family: 'Courier New', monospace;
        }
        
        .table-row {
            display: flex;
            width: 100%;
            margin: 1px 0;
        }
        
        .table-row.header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            margin-bottom: 3px;
        }
        
        .table-row.comment {
            font-size: 10px;
            color: #666;
        }
        
        /* Columnas con ancho fijo para alineación (45 caracteres total como en ticket térmico) */
        .col-qty {
            width: 8%;
            text-align: center;
        }
        
        .col-desc {
            width: 50%;
            text-align: left;
            padding-left: 2px;
            /* Truncar texto largo */
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        
        .col-price {
            width: 20%;
            text-align: right;
            padding-right: 2px;
        }
        
        .col-total {
            width: 22%;
            text-align: right;
        }
        
        /* Ajustes responsivos para impresión térmica */
        @media print {
            .product-table {
                font-size: 9px;
            }
            
            .col-desc {
                /* Para tickets térmicos, permitir wrap en descripción larga */
                white-space: normal;
                word-wrap: break-word;
            }
        }
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

    <!-- Contenido del ticket - Formato idéntico a imprimir_termica.php -->
    <div class="ticket">
        <!-- Encabezado con información de sucursal -->
        <div class="center bold">
            <?= htmlspecialchars($sucursal_nombre) ?>
        </div>
        
        <div class="separator"></div>
        
        <div>
            Sucursal: <?= htmlspecialchars($sucursal_nombre) ?><br>
            Mesa: <?= htmlspecialchars($esPrueba ? $orden->mesa_nombre : $orden['nombre']) ?><br>
            Orden: #<?= htmlspecialchars($esPrueba ? $orden->id : $orden['codigo']) ?><br>
            Fecha: <?= date('d/m/Y H:i:s', strtotime($esPrueba ? $orden->fecha_creacion : $orden['creada_en'])) ?>
        </div>
        
        <div class="separator"></div>
        
        <!-- Tabla de productos (formato idéntico a tablaProductos) -->
        <?php 
        $total_orden = 0;
        
        // Encabezado de la tabla
        ?>
        <div class="product-table">
            <div class="table-row header">
                <span class="col-qty">CANT</span>
                <span class="col-desc">DESCRIPCION</span>
                <span class="col-price">PRECIO</span>
                <span class="col-total">TOTAL</span>
            </div>
            
            <?php foreach ($productos as $producto): 
                // Acceso a propiedades para objetos y arrays
                $nombre = is_object($producto) ? $producto->nombre : $producto['nombre'];
                $cantidad = is_object($producto) ? $producto->cantidad : $producto['cantidad'];
                $precio = is_object($producto) ? $producto->precio : ($producto['precio_unitario'] ?? $producto['precio']);
                
                $total_producto = $cantidad * $precio;
                $total_orden += $total_producto;
            ?>
                <div class="table-row">
                    <span class="col-qty"><?= $cantidad ?></span>
                    <span class="col-desc"><?= htmlspecialchars($nombre) ?></span>
                    <span class="col-price">$<?= number_format($precio, 2) ?></span>
                    <span class="col-total">$<?= number_format($total_producto, 2) ?></span>
                </div>
                <?php 
                $comentarios = is_object($producto) ? ($producto->comentarios ?? null) : ($producto['comentarios'] ?? null);
                if (!empty($comentarios)): 
                ?>
                    <div class="table-row comment">
                        <span class="col-qty"></span>
                        <span class="col-desc" style="font-style: italic; color: #666;">* <?= htmlspecialchars($comentarios) ?></span>
                        <span class="col-price"></span>
                        <span class="col-total"></span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="separator"></div>
        
        <!-- Total (formato idéntico) -->
        <div class="right bold large">
            TOTAL: $<?= number_format($esPrueba ? $orden->total : $total_orden, 2) ?>
        </div>
        
        <div class="separator"></div>
        
        <!-- Total en texto (idéntico a imprimir_termica.php) -->
        <?php 
        $totalTexto = numeroATextoHelper($esPrueba ? $orden->total : $total_orden);
        ?>
        <div class="center">
            <?= ucfirst($totalTexto) ?>
        </div>
        
        <!-- Información del pago (si está cerrada o es prueba) -->
        <?php if (($esPrueba && $orden->estado === 'cerrada') || (!$esPrueba && $orden['estado'] === 'cerrada')): ?>
            <div class="separator"></div>
            
            <div class="bold">
                METODO DE PAGO: <?= formatearMetodoPagoHelper($esPrueba ? $orden->metodo_pago : ($orden['metodo_pago'] ?? 'efectivo')) ?>
            </div>
            
            <?php 
            $metodo_pago = $esPrueba ? $orden->metodo_pago : ($orden['metodo_pago'] ?? 'efectivo');
            $dinero_recibido = $esPrueba ? $orden->dinero_recibido : ($orden['dinero_recibido'] ?? null);
            $cambio = $esPrueba ? $orden->cambio : ($orden['cambio'] ?? null);
            
            if (($metodo_pago === 'efectivo' || !isset($metodo_pago)) && isset($dinero_recibido) && $dinero_recibido !== null): 
            ?>
                <div>
                    Dinero recibido: $<?= number_format($dinero_recibido, 2) ?>
                </div>
                
                <?php if (isset($cambio) && $cambio !== null && $cambio > 0): ?>
                    <div class="bold">
                        Cambio: $<?= number_format($cambio, 2) ?>
                    </div>
                <?php else: ?>
                    <div>
                        Pago exacto
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="separator"></div>
        
        <div class="center">
            ¡Gracias por su preferencia!
        </div>
        
        <div class="center" style="margin-top: 15px;">
            <?php if ($esPrueba): ?>
                <small>🧪 Sistema POS - Ticket de Prueba</small>
            <?php else: ?>
                <small>Sistema POS - Orden #<?= $orden['id'] ?></small>
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
