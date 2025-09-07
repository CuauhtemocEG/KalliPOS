<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kalli Jaguar POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-container {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">
    <div class="login-container w-full max-w-md p-8 rounded-2xl shadow-2xl">
        <!-- Logo y Título -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                <i class="bi bi-shop text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Kalli Jaguar POS</h1>
            <p class="text-gray-600">Iniciar Sesión</p>
        </div>

        <!-- Mensaje de logout exitoso -->
        <?php if (isset($_GET['message']) && $_GET['message'] === 'logout_success'): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <div class="flex items-center">
                <i class="bi bi-check-circle-fill mr-2"></i>
                <span>Sesión cerrada exitosamente</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulario de Login -->
        <form id="loginForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="bi bi-person-circle mr-2"></i>Usuario o Email
                </label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                    placeholder="Tu usuario o email"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="bi bi-lock-fill mr-2"></i>Contraseña
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-12"
                        placeholder="Tu contraseña"
                    >
                    <button 
                        type="button" 
                        onclick="togglePassword()" 
                        class="absolute right-3 top-3 text-gray-500 hover:text-gray-700"
                    >
                        <i id="passwordToggleIcon" class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                </label>
                <a href="#" class="text-sm text-blue-600 hover:text-blue-800">¿Olvidaste tu contraseña?</a>
            </div>

            <button 
                type="submit" 
                id="loginBtn"
                class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-600 hover:to-purple-700 focus:ring-4 focus:ring-blue-200 transition duration-200 flex items-center justify-center"
            >
                <span id="loginBtnText">
                    <i class="bi bi-box-arrow-in-right mr-2"></i>Iniciar Sesión
                </span>
                <span id="loginBtnLoading" class="hidden">
                    <i class="bi bi-arrow-clockwise animate-spin mr-2"></i>Iniciando...
                </span>
            </button>
        </form>

        <!-- Información de usuarios demo -->
        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Usuarios de prueba:</h3>
            <div class="text-xs text-gray-600 space-y-1">
                <div><strong>Admin:</strong> admin / password</div>
                <div><strong>Mesero:</strong> mesero1 / password</div>
                <div><strong>Cocinero:</strong> cocinero1 / password</div>
            </div>
        </div>
    </div>

    <script>
        // Verificar si ya está logueado
        if (getCookie('jwt_token')) {
            window.location.href = 'index.php';
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginBtnLoading = document.getElementById('loginBtnLoading');
            
            // Mostrar loading
            loginBtnText.classList.add('hidden');
            loginBtnLoading.classList.remove('hidden');
            loginBtn.disabled = true;
            
            try {
                const formData = new FormData(this);
                const data = {
                    login: formData.get('login'),
                    password: formData.get('password'),
                    remember: formData.get('remember') === 'on'
                };
                
                const response = await fetch('auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Bienvenido!',
                        text: `Hola ${result.user.nombre_completo}`,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de inicio de sesión',
                        text: result.message || 'Credenciales inválidas'
                    });
                }
                
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión. Inténtalo de nuevo.'
                });
            } finally {
                // Ocultar loading
                loginBtnText.classList.remove('hidden');
                loginBtnLoading.classList.add('hidden');
                loginBtn.disabled = false;
            }
        });
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }
        
        function getCookie(name) {
            let cookieValue = null;
            if (document.cookie && document.cookie !== '') {
                const cookies = document.cookie.split(';');
                for (let i = 0; i < cookies.length; i++) {
                    const cookie = cookies[i].trim();
                    if (cookie.substring(0, name.length + 1) === (name + '=')) {
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        break;
                    }
                }
            }
            return cookieValue;
        }
    </script>
</body>
</html>
