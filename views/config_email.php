<?php
/**
 * Configuración de Email para HostGator
 * Configuración específica para el envío de tickets por email
 */

require_once '../auth-check.php';
require_once '../conexion.php';

$pdo = conexion();
$mensaje = '';
$tipo_mensaje = '';

// Procesar actualización de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $configuraciones = [
            'empresa_nombre' => $_POST['empresa_nombre'] ?? '',
            'empresa_direccion' => $_POST['empresa_direccion'] ?? '',
            'empresa_telefono' => $_POST['empresa_telefono'] ?? '',
            'empresa_email' => $_POST['empresa_email'] ?? '',
            'email_remitente' => $_POST['email_remitente'] ?? '',
            'email_smtp_host' => $_POST['email_smtp_host'] ?? 'localhost',
            'email_smtp_port' => $_POST['email_smtp_port'] ?? '25',
            'email_smtp_auth' => $_POST['email_smtp_auth'] ?? '0'
        ];
        
        foreach ($configuraciones as $clave => $valor) {
            $stmt = $pdo->prepare("
                INSERT INTO configuracion (clave, valor) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");
            $stmt->execute([$clave, $valor]);
        }
        
        $mensaje = '¡Configuración actualizada correctamente!';
        $tipo_mensaje = 'success';
        
    } catch (Exception $e) {
        $mensaje = 'Error al actualizar configuración: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener configuración actual
$stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave LIKE 'empresa_%' OR clave LIKE 'email_%'");
$stmt->execute();
$config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .config-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-gear-fill"></i> Configuración Email
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="enviar_ticket_email.php">
                    <i class="bi bi-envelope"></i> Enviar Tickets
                </a>
                <a class="nav-link" href="ordenes.php">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="config-section text-center">
            <i class="bi bi-envelope-gear" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h2 class="mb-3">Configuración de Email para HostGator</h2>
            <p class="mb-0">Configura los ajustes de email para enviar tickets desde tu hosting HostGator</p>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <!-- Configuración de Empresa -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-building text-primary"></i>
                                Información de la Empresa
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="empresa_nombre" class="form-label">Nombre de la Empresa</label>
                                <input type="text" class="form-control" id="empresa_nombre" name="empresa_nombre" 
                                       value="<?php echo htmlspecialchars($config['empresa_nombre'] ?? ''); ?>"
                                       placeholder="Mi Restaurant POS">
                            </div>
                            
                            <div class="mb-3">
                                <label for="empresa_direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="empresa_direccion" name="empresa_direccion" 
                                       value="<?php echo htmlspecialchars($config['empresa_direccion'] ?? ''); ?>"
                                       placeholder="Calle Principal #123, Ciudad">
                            </div>
                            
                            <div class="mb-3">
                                <label for="empresa_telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="empresa_telefono" name="empresa_telefono" 
                                       value="<?php echo htmlspecialchars($config['empresa_telefono'] ?? ''); ?>"
                                       placeholder="+1 (555) 123-4567">
                            </div>
                            
                            <div class="mb-3">
                                <label for="empresa_email" class="form-label">Email Principal del Negocio</label>
                                <input type="email" class="form-control" id="empresa_email" name="empresa_email" 
                                       value="<?php echo htmlspecialchars($config['empresa_email'] ?? ''); ?>"
                                       placeholder="info@mirestaurant.com">
                                <div class="form-text">Este será el email de destino por defecto para los tickets</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Email/SMTP -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-envelope-gear text-success"></i>
                                Configuración de Email (HostGator)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>HostGator:</strong> Generalmente no necesitas cambiar estos valores. 
                                HostGator maneja el email automáticamente desde localhost.
                            </div>
                            
                            <div class="mb-3">
                                <label for="email_remitente" class="form-label">Email Remitente</label>
                                <input type="email" class="form-control" id="email_remitente" name="email_remitente" 
                                       value="<?php echo htmlspecialchars($config['email_remitente'] ?? ''); ?>"
                                       placeholder="noreply@tudominio.com">
                                <div class="form-text">Debe ser un email de tu dominio en HostGator</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email_smtp_host" class="form-label">Servidor SMTP</label>
                                <input type="text" class="form-control" id="email_smtp_host" name="email_smtp_host" 
                                       value="<?php echo htmlspecialchars($config['email_smtp_host'] ?? 'localhost'); ?>">
                                <div class="form-text">Para HostGator usar: localhost</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email_smtp_port" class="form-label">Puerto SMTP</label>
                                        <input type="number" class="form-control" id="email_smtp_port" name="email_smtp_port" 
                                               value="<?php echo htmlspecialchars($config['email_smtp_port'] ?? '25'); ?>">
                                        <div class="form-text">HostGator: 25</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email_smtp_auth" class="form-label">Autenticación</label>
                                        <select class="form-control" id="email_smtp_auth" name="email_smtp_auth">
                                            <option value="0" <?php echo ($config['email_smtp_auth'] ?? '0') === '0' ? 'selected' : ''; ?>>No requerida</option>
                                            <option value="1" <?php echo ($config['email_smtp_auth'] ?? '0') === '1' ? 'selected' : ''; ?>>Requerida</option>
                                        </select>
                                        <div class="form-text">HostGator: No requerida</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">¿Listo para guardar?</h6>
                                    <small class="text-muted">Guarda la configuración y prueba el envío de emails</small>
                                </div>
                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-gradient btn-lg">
                                        <i class="bi bi-check-circle"></i> Guardar Configuración
                                    </button>
                                    <a href="enviar_ticket_email.php" class="btn btn-outline-primary btn-lg">
                                        <i class="bi bi-envelope"></i> Ir a Enviar Tickets
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Sección de Prueba -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-bug text-warning"></i>
                            Prueba de Configuración
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="test-section">
                            <h6>Información del Servidor</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Servidor:</strong><br>
                                    <span class="text-muted"><?php echo $_SERVER['HTTP_HOST'] ?? 'No detectado'; ?></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>PHP Mail:</strong><br>
                                    <span class="badge bg-<?php echo function_exists('mail') ? 'success' : 'danger'; ?>">
                                        <?php echo function_exists('mail') ? 'Disponible' : 'No disponible'; ?>
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <strong>PHPMailer:</strong><br>
                                    <span class="badge bg-<?php echo file_exists('../vendor/phpmailer/phpmailer/src/PHPMailer.php') ? 'success' : 'warning'; ?>">
                                        <?php echo file_exists('../vendor/phpmailer/phpmailer/src/PHPMailer.php') ? 'Instalado' : 'No detectado'; ?>
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Método Envío:</strong><br>
                                    <span class="badge bg-info">
                                        <?php echo file_exists('../vendor/phpmailer/phpmailer/src/PHPMailer.php') ? 'PHPMailer + Fallback' : 'PHP mail()'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <p class="mb-2"><strong>Consejos para HostGator:</strong></p>
                            <ul class="text-muted">
                                <li>El email remitente debe ser de tu dominio (ej: noreply@tudominio.com)</li>
                                <li>HostGator permite envío desde localhost sin autenticación</li>
                                <li>Si los emails no llegan, verifica en la carpeta de spam</li>
                                <li>Para dominios nuevos, puede tomar unas horas en activarse el email</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
