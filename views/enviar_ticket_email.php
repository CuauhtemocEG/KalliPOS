<?php
/**
 * Interfaz para enviar tickets por email
 * Alternativa para HostGator cuando no se puede imprimir directamente
 */

require_once '../auth-check.php';
require_once '../conexion.php';

$pdo = conexion();

// Obtener órdenes cerradas para poder enviar por email
$stmt = $pdo->prepare("
    SELECT o.id, o.codigo, o.total, o.creada_en, o.estado, m.nombre as mesa_nombre 
    FROM ordenes o 
    JOIN mesas m ON o.mesa_id = m.id 
    WHERE o.estado = 'cerrada' 
    ORDER BY o.creada_en DESC 
    LIMIT 50
");
$stmt->execute();
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Tickets por Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .ticket-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
        }
        .orden-card {
            transition: all 0.3s ease;
        }
        .orden-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-envelope"></i> Envío por Email
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="config_email.php">
                            <i class="bi bi-gear"></i> Configurar Email
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../views/ordenes.php">
                            <i class="bi bi-arrow-left"></i> Volver a Órdenes
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            <i class="bi bi-envelope-paper text-primary"></i>
                            Envío de Tickets por Email
                        </h4>
                        <p class="card-text text-muted">
                            Alternativa para imprimir tickets cuando estás en HostGator. 
                            Selecciona una orden cerrada y envía el ticket por email.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de envío -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-send"></i> Enviar Ticket
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="formEnviarEmail">
                            <div class="mb-3">
                                <label for="orden_select" class="form-label">Seleccionar Orden</label>
                                <select class="form-select" id="orden_select" name="orden_id" required>
                                    <option value="">-- Seleccionar orden --</option>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <option value="<?php echo $orden['id']; ?>" 
                                                data-codigo="<?php echo htmlspecialchars($orden['codigo']); ?>"
                                                data-mesa="<?php echo htmlspecialchars($orden['mesa_nombre']); ?>"
                                                data-total="<?php echo number_format($orden['total'], 2); ?>"
                                                data-fecha="<?php echo date('d/m/Y H:i', strtotime($orden['creada_en'])); ?>">
                                            Orden #<?php echo $orden['codigo']; ?> - 
                                            Mesa <?php echo $orden['mesa_nombre']; ?> - 
                                            $<?php echo number_format($orden['total'], 2); ?> -
                                            <?php echo date('d/m/Y H:i', strtotime($orden['creada_en'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="email_destino" class="form-label">Email de destino</label>
                                <input type="email" class="form-control" id="email_destino" name="email" 
                                       placeholder="cliente@ejemplo.com o dejar vacío para email del negocio">
                                <div class="form-text">
                                    Si no especificas un email, se enviará al email del negocio configurado
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Enviar Ticket por Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-eye"></i> Vista Previa
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="orden-preview">
                            <p class="text-muted text-center">
                                <i class="bi bi-arrow-left"></i>
                                Selecciona una orden para ver la vista previa
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de envíos -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history"></i> Últimos Envíos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="historial-envios">
                            <p class="text-muted text-center">No hay envíos registrados aún</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de resultado -->
    <div class="modal fade" id="modalResultado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Resultado del Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalCuerpo">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let historialEnvios = [];

        // Preview de orden seleccionada
        document.getElementById('orden_select').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const preview = document.getElementById('orden-preview');
            
            if (this.value) {
                const codigo = selected.dataset.codigo;
                const mesa = selected.dataset.mesa;
                const total = selected.dataset.total;
                const fecha = selected.dataset.fecha;
                
                preview.innerHTML = `
                    <div class="ticket-preview">
                        <div class="text-center mb-3">
                            <h6 class="mb-1">Ticket de Orden</h6>
                            <small class="text-muted">Vista previa del email</small>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6"><strong>Mesa:</strong></div>
                            <div class="col-6">${mesa}</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Orden:</strong></div>
                            <div class="col-6">#${codigo}</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Fecha:</strong></div>
                            <div class="col-6">${fecha}</div>
                        </div>
                        <div class="row">
                            <div class="col-6"><strong>Total:</strong></div>
                            <div class="col-6"><strong>$${total}</strong></div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <small class="text-success">
                                <i class="bi bi-check-circle"></i> 
                                Orden cerrada - Lista para envío
                            </small>
                        </div>
                    </div>
                `;
            } else {
                preview.innerHTML = `
                    <p class="text-muted text-center">
                        <i class="bi bi-arrow-left"></i>
                        Selecciona una orden para ver la vista previa
                    </p>
                `;
            }
        });

        // Envío de email
        document.getElementById('formEnviarEmail').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const ordenSelect = document.getElementById('orden_select');
            const emailInput = document.getElementById('email_destino');
            
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
            button.disabled = true;
            
            // Preparar datos para envío
            const datos = {
                orden_id: formData.get('orden_id'),
                email: formData.get('email') || null
            };
            
            fetch('../controllers/enviar_ticket_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datos)
            })
            .then(response => response.json())
            .then(data => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (data.success) {
                    // Agregar al historial
                    const ordenSeleccionada = ordenSelect.options[ordenSelect.selectedIndex];
                    historialEnvios.unshift({
                        codigo: ordenSeleccionada.dataset.codigo,
                        mesa: ordenSeleccionada.dataset.mesa,
                        email: data.email_enviado,
                        fecha: new Date().toLocaleString('es-ES')
                    });
                    
                    actualizarHistorial();
                    
                    // Mostrar resultado exitoso
                    mostrarModal('success', '¡Éxito!', `
                        <div class="text-center">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Ticket enviado correctamente</h5>
                            <p class="text-muted">El ticket ha sido enviado a: <strong>${data.email_enviado}</strong></p>
                        </div>
                    `);
                    
                    // Limpiar formulario
                    this.reset();
                    document.getElementById('orden-preview').innerHTML = `
                        <p class="text-muted text-center">
                            <i class="bi bi-arrow-left"></i>
                            Selecciona una orden para ver la vista previa
                        </p>
                    `;
                    
                } else {
                    mostrarModal('error', 'Error', `
                        <div class="text-center">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">Error al enviar</h5>
                            <p class="text-muted">${data.error}</p>
                        </div>
                    `);
                }
            })
            .catch(error => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                mostrarModal('error', 'Error de Conexión', `
                    <div class="text-center">
                        <i class="bi bi-wifi-off text-warning" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Error de conexión</h5>
                        <p class="text-muted">No se pudo conectar con el servidor. Intenta nuevamente.</p>
                    </div>
                `);
                console.error('Error:', error);
            });
        });

        function mostrarModal(tipo, titulo, cuerpo) {
            document.getElementById('modalTitulo').textContent = titulo;
            document.getElementById('modalCuerpo').innerHTML = cuerpo;
            
            const modal = new bootstrap.Modal(document.getElementById('modalResultado'));
            modal.show();
        }

        function actualizarHistorial() {
            const historialDiv = document.getElementById('historial-envios');
            
            if (historialEnvios.length === 0) {
                historialDiv.innerHTML = '<p class="text-muted text-center">No hay envíos registrados aún</p>';
                return;
            }
            
            let html = '<div class="row">';
            historialEnvios.slice(0, 6).forEach((envio, index) => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card border-success orden-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-1">
                                            <i class="bi bi-envelope-check text-success"></i>
                                            Orden #${envio.codigo}
                                        </h6>
                                        <p class="card-text mb-1">
                                            <small><strong>Mesa:</strong> ${envio.mesa}</small><br>
                                            <small><strong>Email:</strong> ${envio.email}</small><br>
                                            <small class="text-muted">${envio.fecha}</small>
                                        </p>
                                    </div>
                                    <span class="badge bg-success status-badge">Enviado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            historialDiv.innerHTML = html;
        }
    </script>
</body>
</html>
