<?php
require_once '../conexion.php';

$pdo = conexion();

/**
 * Clase para generar comandos ESC/POS para impresoras térmicas
 */
class ImpresorTermica {
    
    // Comandos ESC/POS básicos
    const ESC = "\x1B";
    const GS = "\x1D";
    const NUL = "\x00";
    const LF = "\x0A";
    const CR = "\x0D";
    const INIT = "\x1B\x40";           // Inicializar impresora
    const RESET = "\x1B\x40";          // Reset
    const FEED_LINE = "\x0A";          // Salto de línea
    const CUT_PAPER = "\x1D\x56\x00";  // Cortar papel
    const PARTIAL_CUT = "\x1D\x56\x01"; // Corte parcial
    
    // Alineación de texto
    const ALIGN_LEFT = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT = "\x1B\x61\x02";
    
    // Estilos de texto
    const TEXT_NORMAL = "\x1B\x21\x00";
    const TEXT_BOLD = "\x1B\x45\x01";
    const TEXT_BOLD_OFF = "\x1B\x45\x00";
    const TEXT_UNDERLINE = "\x1B\x2D\x01";
    const TEXT_UNDERLINE_OFF = "\x1B\x2D\x00";
    
    // Tamaños de texto
    const TEXT_SIZE_NORMAL = "\x1B\x21\x00";
    const TEXT_SIZE_WIDE = "\x1B\x21\x20";
    const TEXT_SIZE_TALL = "\x1B\x21\x10";
    const TEXT_SIZE_LARGE = "\x1B\x21\x30";
    
    private $contenido = "";
    
    public function __construct() {
        $this->contenido = self::INIT;
    }
    
    /**
     * Agregar texto con formato
     */
    public function texto($texto, $alineacion = 'left', $bold = false, $size = 'normal') {
        // Aplicar alineación
        switch ($alineacion) {
            case 'center':
                $this->contenido .= self::ALIGN_CENTER;
                break;
            case 'right':
                $this->contenido .= self::ALIGN_RIGHT;
                break;
            default:
                $this->contenido .= self::ALIGN_LEFT;
        }
        
        // Aplicar tamaño
        switch ($size) {
            case 'large':
                $this->contenido .= self::TEXT_SIZE_LARGE;
                break;
            case 'wide':
                $this->contenido .= self::TEXT_SIZE_WIDE;
                break;
            case 'tall':
                $this->contenido .= self::TEXT_SIZE_TALL;
                break;
            default:
                $this->contenido .= self::TEXT_SIZE_NORMAL;
        }
        
        // Aplicar negrita
        if ($bold) {
            $this->contenido .= self::TEXT_BOLD;
        }
        
        // Limpiar texto
        $texto = $this->limpiarTexto($texto);
        $this->contenido .= $texto;
        
        // Resetear formato
        if ($bold) {
            $this->contenido .= self::TEXT_BOLD_OFF;
        }
        
        $this->contenido .= self::LF;
    }
    
    /**
     * Agregar línea separadora
     */
    public function linea($caracter = '-', $longitud = 32) {
        $this->contenido .= self::ALIGN_LEFT;
        $this->contenido .= str_repeat($caracter, $longitud) . self::LF;
    }
    
    /**
     * Agregar imagen en formato ESC/POS
     */
    public function imagenESCPOS($rutaImagen, $anchoDeseado = 200) {
        if (!file_exists($rutaImagen)) {
            return false;
        }
        
        // Cargar imagen
        $imagen = $this->cargarImagen($rutaImagen);
        if (!$imagen) {
            return false;
        }
        
        // Obtener dimensiones originales
        list($anchoOriginal, $altoOriginal) = getimagesize($rutaImagen);
        
        // Calcular nuevo tamaño (mucho más pequeño para logo)
        $anchoFinal = min($anchoDeseado, 120); // Reducido a 120 píxeles máximo
        $altoFinal = intval(($altoOriginal * $anchoFinal) / $anchoOriginal);
        
        // Limitar altura mucho más (máximo 40 píxeles)
        if ($altoFinal > 20) {
            $altoFinal = 20;
            $anchoFinal = intval(($anchoOriginal * $altoFinal) / $altoOriginal);
        }
        
        // Redimensionar imagen
        $imagenRedimensionada = imagecreatetruecolor($anchoFinal, $altoFinal);
        $blanco = imagecolorallocate($imagenRedimensionada, 255, 255, 255);
        imagefill($imagenRedimensionada, 0, 0, $blanco);
        
        imagecopyresampled(
            $imagenRedimensionada, $imagen,
            0, 0, 0, 0,
            $anchoFinal, $altoFinal, $anchoOriginal, $altoOriginal
        );
        
        // Convertir a formato ESC/POS
        $comandoImagen = $this->convertirImagenESCPOS($imagenRedimensionada, $anchoFinal, $altoFinal);
        
        if ($comandoImagen) {
            $this->contenido .= self::ALIGN_CENTER;
            $this->contenido .= $comandoImagen;
        }
        
        // Limpiar memoria
        imagedestroy($imagen);
        imagedestroy($imagenRedimensionada);
        
        return true;
    }
    
    /**
     * 🔥 IMAGEN GIGANTE - Método que SÍ FUNCIONA del test_gigante.php
     * Agregar imagen extra grande optimizada para impresión
     */
    public function imagenGigante($rutaImagen = null) {
        if ($rutaImagen === null) {
            $rutaImagen = '../assets/img/LogoBlack.png';
        }
        
        if (!file_exists($rutaImagen)) {
            return false;
        }
        
        // Usar cargarImagen() que soporta PNG, JPG, GIF
        $imagenOriginal = $this->cargarImagen($rutaImagen);
        if (!$imagenOriginal) {
            return false;
        }
        
        // Obtener dimensiones
        $info = getimagesize($rutaImagen);
        $anchoOriginal = $info[0];
        $altoOriginal = $info[1];
        
        // EXACTO del test_gigante.php - Tamaño GIGANTE
        $anchoFinal = 360;
        $altoFinal = intval(($altoOriginal * $anchoFinal) / $anchoOriginal);
        
        if ($altoFinal > 180) {
            $altoFinal = 180;
            $anchoFinal = intval(($anchoOriginal * $altoFinal) / $altoOriginal);
        }
        
        // Crear imagen redimensionada con fondo blanco
        $imagenGigante = imagecreatetruecolor($anchoFinal, $altoFinal);
        $blanco = imagecolorallocate($imagenGigante, 255, 255, 255);
        imagefill($imagenGigante, 0, 0, $blanco);
        
        imagecopyresampled(
            $imagenGigante, $imagenOriginal,
            0, 0, 0, 0,
            $anchoFinal, $altoFinal, $anchoOriginal, $altoOriginal
        );
        
        // Convertir a comandos ESC/POS usando GS v 0 (EXACTO del test)
        $comandoGigante = $this->crearComandoImagenGigante($imagenGigante, $anchoFinal, $altoFinal);
        
        if ($comandoGigante) {
            $this->contenido .= self::ALIGN_CENTER;
            $this->contenido .= $comandoGigante;
            $this->contenido .= self::LF;
        }
        
        // Limpiar memoria
        imagedestroy($imagenOriginal);
        imagedestroy($imagenGigante);
        
        return true;
    }
    
    /**
     * 🚀 IMAGEN GIGANTE OPTIMIZADA - Para imágenes grandes con manejo de memoria
     */
    public function imagenGiganteOptimizada($rutaImagen = null) {
        // 🔧 OPTIMIZACIÓN: Aumentar límite de memoria temporalmente
        $memoriaOriginal = ini_get('memory_limit');
        ini_set('memory_limit', '256M');
        
        try {
            if ($rutaImagen === null) {
                $rutaImagen = '../assets/img/LogoBlack.png';
            }
            
            if (!file_exists($rutaImagen)) {
                return false;
            }
            
            // Verificar dimensiones primero sin cargar la imagen
            $info = getimagesize($rutaImagen);
            if (!$info) {
                return false;
            }
            
            $anchoOriginal = $info[0];
            $altoOriginal = $info[1];
            
            // Si la imagen es muy grande, pre-redimensionar
            $maxDimension = 2000; // Máximo 2000px en cualquier dirección
            $needsPreResize = ($anchoOriginal > $maxDimension || $altoOriginal > $maxDimension);
            
            if ($needsPreResize) {
                // Pre-redimensionar para reducir el uso de memoria
                $scaleFactor = min($maxDimension / $anchoOriginal, $maxDimension / $altoOriginal);
                $preAnchoFinal = intval($anchoOriginal * $scaleFactor);
                $preAltoFinal = intval($altoOriginal * $scaleFactor);
                
                // Cargar imagen original
                $imagenOriginal = $this->cargarImagen($rutaImagen);
                if (!$imagenOriginal) {
                    return false;
                }
                
                // Crear imagen pre-redimensionada
                $imagenPreRedim = imagecreatetruecolor($preAnchoFinal, $preAltoFinal);
                $blanco = imagecolorallocate($imagenPreRedim, 255, 255, 255);
                imagefill($imagenPreRedim, 0, 0, $blanco);
                
                imagecopyresampled(
                    $imagenPreRedim, $imagenOriginal,
                    0, 0, 0, 0,
                    $preAnchoFinal, $preAltoFinal, $anchoOriginal, $altoOriginal
                );
                
                // Liberar memoria de la imagen original INMEDIATAMENTE
                imagedestroy($imagenOriginal);
                
                // Ahora usar la imagen pre-redimensionada como "original"
                $imagenOriginal = $imagenPreRedim;
                $anchoOriginal = $preAnchoFinal;
                $altoOriginal = $preAltoFinal;
            } else {
                // Cargar imagen normalmente si no es muy grande
                $imagenOriginal = $this->cargarImagen($rutaImagen);
                if (!$imagenOriginal) {
                    return false;
                }
            }
            
            // EXACTO del test_gigante.php - Tamaño GIGANTE
            $anchoFinal = 360;
            $altoFinal = intval(($altoOriginal * $anchoFinal) / $anchoOriginal);
            
            if ($altoFinal > 180) {
                $altoFinal = 180;
                $anchoFinal = intval(($anchoOriginal * $altoFinal) / $altoOriginal);
            }
            
            // Crear imagen redimensionada con fondo blanco
            $imagenGigante = imagecreatetruecolor($anchoFinal, $altoFinal);
            $blanco = imagecolorallocate($imagenGigante, 255, 255, 255);
            imagefill($imagenGigante, 0, 0, $blanco);
            
            imagecopyresampled(
                $imagenGigante, $imagenOriginal,
                0, 0, 0, 0,
                $anchoFinal, $altoFinal, $anchoOriginal, $altoOriginal
            );
            
            // Liberar memoria de la imagen original INMEDIATAMENTE
            imagedestroy($imagenOriginal);
            
            // Convertir a comandos ESC/POS usando GS v 0 (EXACTO del test)
            $comandoGigante = $this->crearComandoImagenGigante($imagenGigante, $anchoFinal, $altoFinal);
            
            if ($comandoGigante) {
                $this->contenido .= self::ALIGN_CENTER;
                $this->contenido .= $comandoGigante;
                $this->contenido .= self::LF;
            }
            
            // Limpiar memoria
            imagedestroy($imagenGigante);
            
            return true;
            
        } finally {
            // 🔧 SIEMPRE restaurar límite de memoria original
            ini_set('memory_limit', $memoriaOriginal);
        }
    }
    
    /**
     * 🎯 IMAGEN CON CONFIGURACIÓN - Usa la configuración del sistema
     * Aplica el logo configurado en el sistema
     */
    public function imagenConfigurada() {
        global $pdo;
        
        try {
            error_log("ImpresorTermica::imagenConfigurada() - INICIANDO");
            
            // Obtener configuración del logo
            $stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ('logo_activado', 'logo_imagen', 'logo_tamaño')");
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $logoActivado = ($configs['logo_activado'] ?? '1') == '1';
            $logoImagen = $configs['logo_imagen'] ?? 'LogoBlack.png';
            $logoTamaño = $configs['logo_tamaño'] ?? 'grande';
            
            error_log("ImpresorTermica::imagenConfigurada() - Config: activado=$logoActivado, imagen=$logoImagen, tamaño=$logoTamaño");
            
            if (!$logoActivado) {
                error_log("ImpresorTermica::imagenConfigurada() - Logo desactivado");
                return false; // Logo desactivado
            }
            
            // 🔧 MEJORAR DETECCIÓN DE RUTAS: Probar múltiples rutas posibles
            $rutasPosibles = [
                "../assets/img/$logoImagen",           // Desde controllers/
                "assets/img/$logoImagen",              // Desde raíz POS/
                __DIR__ . "/../assets/img/$logoImagen" // Ruta absoluta
            ];
            
            $rutaImagen = null;
            foreach ($rutasPosibles as $ruta) {
                if (file_exists($ruta)) {
                    $rutaImagen = $ruta;
                    break;
                }
            }
            
            if (!$rutaImagen) {
                error_log("ImpresorTermica::imagenConfigurada() - Imagen no encontrada: $logoImagen");
                error_log("ImpresorTermica::imagenConfigurada() - Rutas probadas: " . implode(', ', $rutasPosibles));
                error_log("ImpresorTermica::imagenConfigurada() - __DIR__: " . __DIR__);
                error_log("ImpresorTermica::imagenConfigurada() - getcwd(): " . getcwd());
                return false; // Imagen no existe en ninguna ruta
            }
            
            error_log("ImpresorTermica::imagenConfigurada() - Imagen encontrada en: $rutaImagen");
            
            // 🔧 OPTIMIZACIÓN: Verificar tamaño de archivo antes de cargar
            $tamañoArchivo = filesize($rutaImagen);
            $limiteMB = 10; // Límite de 10MB
            if ($tamañoArchivo > ($limiteMB * 1024 * 1024)) {
                error_log("ImpresorTermica::imagenConfigurada() - Imagen demasiado grande: " . ($tamañoArchivo / 1024 / 1024) . "MB");
                return false;
            }
            
            // 🔧 OPTIMIZACIÓN: Verificar dimensiones sin cargar imagen completa
            $info = getimagesize($rutaImagen);
            if (!$info) {
                error_log("ImpresorTermica::imagenConfigurada() - No se pudo obtener info de imagen");
                return false;
            }
            
            error_log("ImpresorTermica::imagenConfigurada() - Dimensiones: {$info[0]}x{$info[1]}px");
            
            // Aplicar según el tamaño configurado
            switch ($logoTamaño) {
                case 'pequeño':
                    error_log("ImpresorTermica::imagenConfigurada() - Usando imagenESCPOS pequeño");
                    return $this->imagenESCPOS($rutaImagen, 120);
                case 'mediano':
                    error_log("ImpresorTermica::imagenConfigurada() - Usando imagenESCPOS mediano");
                    return $this->imagenESCPOS($rutaImagen, 240);
                case 'grande':
                default:
                    error_log("ImpresorTermica::imagenConfigurada() - Usando imagenGiganteOptimizada");
                    $resultado = $this->imagenGiganteOptimizada($rutaImagen);
                    error_log("ImpresorTermica::imagenConfigurada() - imagenGiganteOptimizada resultado: " . ($resultado ? 'SUCCESS' : 'FAILED'));
                    return $resultado;
            }
            
        } catch (Exception $e) {
            error_log("ImpresorTermica::imagenConfigurada() - EXCEPCIÓN: " . $e->getMessage());
            error_log("ImpresorTermica::imagenConfigurada() - Stack trace: " . $e->getTraceAsString());
            // En caso de error, usar logo por defecto si existe
            $rutaDefault = '../assets/img/LogoBlack.png';
            if (file_exists($rutaDefault)) {
                error_log("ImpresorTermica::imagenConfigurada() - Usando logo por defecto");
                return $this->imagenESCPOS($rutaDefault, 120); // Usar tamaño pequeño por seguridad
            }
            return false;
        }
    }
    
    /**
     * 🔥 CREAR COMANDO IMAGEN GIGANTE - EXACTO del test_gigante.php
     * Usa GS v 0 como en el test que funciona
     */
    private function crearComandoImagenGigante($imagen, $ancho, $alto) {
        $umbral = 80; // EXACTO del test
        
        // Comando GS v 0 EXACTO
        $comando = "\x1D\x76\x30" . chr(0);
        
        // Ancho en bytes
        $anchoBytes = ceil($ancho / 8);
        $comando .= chr($anchoBytes & 0xFF);
        $comando .= chr(($anchoBytes >> 8) & 0xFF);
        
        // Alto
        $comando .= chr($alto & 0xFF);
        $comando .= chr(($alto >> 8) & 0xFF);
        
        // Convertir imagen a datos bitmap EXACTO del test
        for ($y = 0; $y < $alto; $y++) {
            for ($x = 0; $x < $anchoBytes * 8; $x += 8) {
                $byte = 0;
                
                for ($bit = 0; $bit < 8; $bit++) {
                    $px = $x + $bit;
                    if ($px < $ancho) {
                        $color = imagecolorat($imagen, $px, $y);
                        $r = ($color >> 16) & 0xFF;
                        $g = ($color >> 8) & 0xFF;
                        $b = $color & 0xFF;
                        $gris = intval(0.299 * $r + 0.587 * $g + 0.114 * $b);
                        
                        if ($gris < $umbral) {
                            $byte |= (1 << (7 - $bit));
                        }
                    }
                }
                
                $comando .= chr($byte);
            }
        }
        
        return $comando;
    }
    
    /**
     * Cargar imagen desde archivo
     */
    private function cargarImagen($rutaImagen) {
        $info = getimagesize($rutaImagen);
        if (!$info) return false;
        
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($rutaImagen);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($rutaImagen);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($rutaImagen);
            default:
                return false;
        }
    }
    
    /**
     * 🧪 MÉTODO DE PRUEBA - Cargar imagen públicamente para tests
     */
    public function probarCargarImagen($rutaImagen) {
        return $this->cargarImagen($rutaImagen);
    }
    
    /**
     * Convertir imagen a comandos ESC/POS bitmap
     */
    private function convertirImagenESCPOS($imagen, $ancho, $alto) {
        // Ajustar ancho a múltiplo de 8
        $anchoBytes = ceil($ancho / 8);
        $resultado = '';
        
        // Procesar imagen línea por línea
        for ($y = 0; $y < $alto; $y++) {
            // Comando ESC * para imagen de densidad normal
            $comando = self::ESC . '*' . chr(0); // Modo 0 = 8-dot single density
            
            // Ancho en bytes (little endian)
            $comando .= chr($anchoBytes & 0xFF);
            $comando .= chr(($anchoBytes >> 8) & 0xFF);
            
            // Datos de la línea
            $datosLinea = '';
            for ($byteX = 0; $byteX < $anchoBytes; $byteX++) {
                $byte = 0;
                
                // Procesar 8 píxeles por byte
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $byteX * 8 + $bit;
                    
                    if ($x < $ancho) {
                        $color = imagecolorat($imagen, $x, $y);
                        
                        // Convertir a escala de grises
                        $r = ($color >> 16) & 0xFF;
                        $g = ($color >> 8) & 0xFF;
                        $b = $color & 0xFF;
                        $gris = intval(0.299 * $r + 0.587 * $g + 0.114 * $b);
                        
                        // Si es oscuro (menos de 128), marcar el bit
                        if ($gris < 128) {
                            $byte |= (1 << (7 - $bit));
                        }
                    }
                }
                
                $datosLinea .= chr($byte);
            }
            
            $resultado .= $comando . $datosLinea . self::LF;
        }
        
        return $resultado;
    }
    
    /**
     * Agregar salto de línea
     */
    public function saltoLinea($cantidad = 1) {
        for ($i = 0; $i < $cantidad; $i++) {
            $this->contenido .= self::LF;
        }
    }
    
    /**
     * Agregar tabla de productos
     */
    public function tablaProductos($productos) {
        // Encabezado
        $this->texto("PRODUCTO                      CANT  PRECIO", 'left', true);
        $this->linea('-', 45);
        
        foreach ($productos as $producto) {
            $nombre = substr($this->limpiarTexto($producto['nombre']), 0, 28);
            $cantidad = str_pad($producto['cantidad'], 4, ' ', STR_PAD_LEFT);
            $precio = str_pad('$' . number_format($producto['precio'], 2), 10, ' ', STR_PAD_LEFT);
            
            $linea = str_pad($nombre, 28) . $cantidad . $precio;
            $this->contenido .= self::ALIGN_LEFT . $linea . self::LF;
        }
        
        $this->linea('-', 45);
    }
    
    /**
     * Cortar papel
     */
    public function cortar($parcial = false) {
        $this->saltoLinea(3);
        if ($parcial) {
            $this->contenido .= self::PARTIAL_CUT;
        } else {
            $this->contenido .= self::CUT_PAPER;
        }
    }
    
    /**
     * Convertir número a texto en español para tickets
     */
    private function numeroATexto($numero) {
        $pesos = intval($numero);
        $centavos = intval(($numero - $pesos) * 100);
        
        $unidades = ["", "UNO", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE"];
        $decenas = ["", "", "VEINTE", "TREINTA", "CUARENTA", "CINCUENTA", "SESENTA", "SETENTA", "OCHENTA", "NOVENTA"];
        $centenas = ["", "CIENTO", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS", 
                    "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS"];
        
        // Función auxiliar para convertir números menores a 1000
        $convertir999 = function($num) use ($unidades, $decenas, $centenas) {
            if ($num == 0) return "";
            
            $resultado = "";
            
            // Centenas
            if ($num >= 100) {
                $c = intval($num / 100);
                if ($num == 100) {
                    $resultado = "CIEN";
                } else {
                    $resultado = $centenas[$c];
                }
                $num %= 100;
                if ($num > 0) $resultado .= " ";
            }
            
            // Decenas especiales (10-19, 20-29)
            if ($num >= 10 && $num <= 29) {
                $especiales = [
                    10 => "DIEZ", 11 => "ONCE", 12 => "DOCE", 13 => "TRECE", 14 => "CATORCE", 15 => "QUINCE",
                    16 => "DIECISEIS", 17 => "DIECISIETE", 18 => "DIECIOCHO", 19 => "DIECINUEVE",
                    20 => "VEINTE", 21 => "VEINTIUNO", 22 => "VEINTIDOS", 23 => "VEINTITRES", 24 => "VEINTICUATRO",
                    25 => "VEINTICINCO", 26 => "VEINTISEIS", 27 => "VEINTISIETE", 28 => "VEINTIOCHO", 29 => "VEINTINUEVE"
                ];
                $resultado .= $especiales[$num];
            } elseif ($num >= 30) {
                // Decenas normales (30-99)
                $d = intval($num / 10);
                $u = $num % 10;
                $resultado .= $decenas[$d];
                if ($u > 0) {
                    $resultado .= " Y " . $unidades[$u];
                }
            } elseif ($num > 0) {
                // Solo unidades (1-9)
                $resultado .= $unidades[$num];
            }
            
            return $resultado;
        };
        
        // Convertir pesos
        $textoPesos = "";
        
        if ($pesos == 0) {
            $textoPesos = "CERO";
        } elseif ($pesos < 1000) {
            $textoPesos = $convertir999($pesos);
        } elseif ($pesos < 1000000) {
            // Miles
            $miles = intval($pesos / 1000);
            $resto = $pesos % 1000;
            
            if ($miles == 1) {
                $textoPesos = "MIL";
            } else {
                $textoPesos = $convertir999($miles) . " MIL";
            }
            
            if ($resto > 0) {
                $textoPesos .= " " . $convertir999($resto);
            }
        }
        
        // Formatear resultado final
        if ($pesos == 1) {
            $resultado = "UN PESO";
        } else {
            $resultado = $textoPesos . " PESOS";
        }
        
        // Agregar centavos
        if ($centavos > 0) {
            if ($centavos == 1) {
                $resultado .= " CON UN CENTAVO";
            } else {
                $textoCentavos = $convertir999($centavos);
                $resultado .= " CON " . $textoCentavos . " CENTAVOS";
            }
        }
        
        return $resultado . " 00/100 M.N.";
    }

    /**
     * Dividir texto largo en líneas apropiadas para tickets térmicos
     */
    private function dividirTextoParaTicket($texto, $anchoMaximo = 32) {
        // Si el texto es corto, devolverlo como está
        if (strlen($texto) <= $anchoMaximo) {
            return [$texto];
        }
        
        $palabras = explode(' ', $texto);
        $lineas = [];
        $lineaActual = '';
        
        foreach ($palabras as $palabra) {
            $pruebaLinea = $lineaActual . ($lineaActual ? ' ' : '') . $palabra;
            
            if (strlen($pruebaLinea) <= $anchoMaximo) {
                $lineaActual = $pruebaLinea;
            } else {
                // Si la línea actual no está vacía, agregarla
                if ($lineaActual) {
                    $lineas[] = $lineaActual;
                }
                $lineaActual = $palabra;
            }
        }
        
        // Agregar la última línea
        if ($lineaActual) {
            $lineas[] = $lineaActual;
        }
        
        return $lineas;
    }

    /**
     * Formatear nombre del método de pago para impresión
     */
    private function formatearMetodoPago($metodo) {
        $metodos = [
            'efectivo' => 'EFECTIVO',
            'debito' => 'TARJETA DE DÉBITO',
            'credito' => 'TARJETA DE CRÉDITO',
            'transferencia' => 'TRANSFERENCIA BANCARIA'
        ];
        
        return $metodos[$metodo] ?? strtoupper($metodo);
    }

    /**
     * Limpiar texto para impresora térmica
     */
    private function limpiarTexto($texto) {
        // Convertir caracteres especiales
        $caracteres = array(
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U',
            'ç' => 'c', 'Ç' => 'C'
        );
        
        $texto = strtr($texto, $caracteres);
        
        // Mantener solo caracteres ASCII imprimibles
        $texto = preg_replace('/[^\x20-\x7E]/', '', $texto);
        
        return $texto;
    }
    
    /**
     * Obtener contenido para enviar a impresora
     */
    public function obtenerComandos() {
        return $this->contenido;
    }
    
    /**
     * Agregar comando personalizado ESC/POS
     */
    public function agregarComando($comando) {
        $this->contenido .= $comando;
    }
    
    /**
     * Enviar a impresora (macOS/Linux)
     */
    public function imprimir($nombreImpresora) {
        // Verificar si estamos en un entorno donde lpr está disponible
        $lprDisponible = shell_exec('which lpr 2>/dev/null');
        
        if (empty($lprDisponible)) {
            return [
                'success' => false,
                'error' => 'COMANDO_NO_DISPONIBLE',
                'message' => 'El comando lpr no está disponible en este servidor. Usa el método "Navegador" para imprimir desde tu laptop.',
                'suggestion' => 'Cambia a método "Navegador" en la configuración de impresoras.'
            ];
        }
        
        // Crear archivo temporal
        $archivoTemp = tempnam(sys_get_temp_dir(), 'ticket_');
        file_put_contents($archivoTemp, $this->contenido);
        
        // Enviar a impresora usando lpr
        $comando = "lpr -P " . escapeshellarg($nombreImpresora) . " " . escapeshellarg($archivoTemp);
        $resultado = shell_exec($comando . " 2>&1");
        
        // Limpiar archivo temporal
        unlink($archivoTemp);
        
        // Verificar si hubo errores
        if (!empty($resultado) && (
            strpos($resultado, 'command not found') !== false ||
            strpos($resultado, 'No such file or directory') !== false ||
            strpos($resultado, 'cannot find') !== false
        )) {
            return [
                'success' => false,
                'error' => 'COMANDO_FALLIDO',
                'message' => 'Error de impresión: ' . trim($resultado),
                'suggestion' => 'Verifica que la impresora esté conectada o usa el método "Navegador".'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Ticket enviado a impresora correctamente',
            'output' => $resultado
        ];
    }
}

/**
 * Funciones auxiliares para uso fuera de la clase
 */

/**
 * Convertir número a texto para tickets
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
 * Dividir texto largo en líneas para ticket térmico
 */
function dividirTextoParaTicketHelper($texto, $anchoMaximo = 32) {
    $palabras = explode(' ', $texto);
    $lineas = array();
    $lineaActual = '';
    
    foreach ($palabras as $palabra) {
        if (strlen($lineaActual . ' ' . $palabra) <= $anchoMaximo) {
            $lineaActual .= ($lineaActual ? ' ' : '') . $palabra;
        } else {
            if ($lineaActual) {
                $lineas[] = $lineaActual;
            }
            $lineaActual = $palabra;
        }
    }
    
    if ($lineaActual) {
        $lineas[] = $lineaActual;
    }
    
    return $lineas;
}

/**
 * Formatear método de pago para ticket
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

// Si se llama directamente, procesar impresión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['tipo'])) {
            throw new Exception('Tipo de impresión no especificado');
        }
        
        $impresora = new ImpresorTermica();
        
        switch ($input['tipo']) {
            case 'prueba':
                // Generar ticket de prueba
                // ✅ USAR IMAGEN CONFIGURADA
                $impresora->imagenConfigurada();
                $impresora->texto('KALLI JAGUAR', 'center', true, 'large');
                $impresora->saltoLinea();
                
                $impresora->texto('PRUEBA IMPRESORA TERMICA', 'center', true, 'large');
                $impresora->saltoLinea();
                $impresora->texto('Fecha: ' . date('d/m/Y H:i:s'), 'left');
                $impresora->texto('Sistema POS - Test', 'left');
                $impresora->saltoLinea();
                $impresora->linea('=', 32);
                $impresora->saltoLinea();
                $impresora->texto('MENSAJE DE PRUEBA', 'center', true);
                $impresora->saltoLinea();
                $impresora->texto('Si puede leer este mensaje,', 'center');
                $impresora->texto('la impresora termica esta', 'center');
                $impresora->texto('configurada correctamente.', 'center');
                $impresora->saltoLinea();
                $impresora->linea('=', 32);
                $impresora->saltoLinea();
                $impresora->texto('PRUEBA DE CARACTERES', 'center', true);
                $impresora->saltoLinea();
                $impresora->texto('Español: aeiou n', 'left');
                $impresora->texto('Numeros: 1234567890', 'left');
                $impresora->texto('Simbolos: $ @ # % & *', 'left');
                $impresora->saltoLinea();
                $impresora->linea('=', 32);
                $impresora->texto('Fin del test', 'center');
                $impresora->cortar();
                
                // Si se especifica impresora, imprimir
                if (isset($input['impresora'])) {
                    $resultado = $impresora->imprimir($input['impresora']);
                    
                    // Verificar si el resultado es un array (nueva versión) o string (versión antigua)
                    if (is_array($resultado)) {
                        if ($resultado['success']) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Ticket de prueba enviado a impresora',
                                'resultado' => $resultado['output'] ?? ''
                            ]);
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => $resultado['message'],
                                'error' => $resultado['error'],
                                'suggestion' => $resultado['suggestion'] ?? null
                            ]);
                        }
                    } else {
                        // Compatibilidad con versión antigua (string)
                        echo json_encode([
                            'success' => true,
                            'message' => 'Ticket de prueba enviado a impresora',
                            'resultado' => $resultado
                        ]);
                    }
                } else {
                    // Solo devolver comandos
                    echo json_encode([
                        'success' => true,
                        'comandos' => base64_encode($impresora->obtenerComandos()),
                        'message' => 'Comandos ESC/POS generados'
                    ]);
                }
                break;
                
            case 'prueba_imagen':
                // Prueba específica solo para la imagen
                $impresora->texto('=== PRUEBA DE IMAGEN GIGANTE ===', 'center', true);
                $impresora->saltoLinea();
                $impresora->texto('Tamaño: 360 píxeles de ancho', 'center');
                $impresora->saltoLinea();
                $impresora->linea('-', 32);
                $impresora->saltoLinea();
                
                // ✅ IMAGEN GIGANTE que SÍ funciona
                $impresora->imagenGigante('../assets/img/LogoBlack.png');
                $impresora->texto('Kalli Jaguar', 'center', true, 'large');
                $impresora->saltoLinea();
                
                $impresora->linea('-', 32);
                $impresora->texto('Fin de prueba imagen', 'center');
                $impresora->cortar();
                
                // Si se especifica impresora, imprimir
                if (isset($input['impresora'])) {
                    $resultado = $impresora->imprimir($input['impresora']);
                    
                    // Verificar si el resultado es un array (nueva versión) o string (versión antigua)
                    if (is_array($resultado)) {
                        if ($resultado['success']) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Prueba de imagen enviada a impresora',
                                'resultado' => $resultado['output'] ?? ''
                            ]);
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => $resultado['message'],
                                'error' => $resultado['error'],
                                'suggestion' => $resultado['suggestion'] ?? null
                            ]);
                        }
                    } else {
                        // Compatibilidad con versión antigua (string)
                        echo json_encode([
                            'success' => true,
                            'message' => 'Prueba de imagen enviada a impresora',
                            'resultado' => $resultado
                        ]);
                    }
                } else {
                    // Solo devolver comandos
                    echo json_encode([
                        'success' => true,
                        'comandos' => base64_encode($impresora->obtenerComandos()),
                        'message' => 'Comandos ESC/POS generados para prueba de imagen'
                    ]);
                }
                break;
                
            case 'prueba_logo':
                // Prueba específica para el logo configurado
                $logoImagen = $input['logo_imagen'] ?? 'LogoBlack.png';
                $logoTamaño = $input['logo_tamaño'] ?? 'grande';
                $logoActivado = $input['logo_activado'] ?? true;
                
                $impresora->texto('=== PRUEBA DE LOGO CONFIGURADO ===', 'center', true);
                $impresora->saltoLinea();
                $impresora->texto("Imagen: $logoImagen", 'center');
                $impresora->texto("Tamaño: $logoTamaño", 'center');
                $impresora->saltoLinea();
                $impresora->linea('-', 32);
                $impresora->saltoLinea();
                
                // Usar imagen con tamaño configurado
                if ($logoActivado && $logoImagen) {
                    $rutaImagen = "../assets/img/$logoImagen";
                    
                    // Ajustar método según el tamaño - USAR VERSIONES OPTIMIZADAS
                    switch ($logoTamaño) {
                        case 'pequeño':
                            $impresora->imagenESCPOS($rutaImagen, 120);
                            break;
                        case 'mediano':
                            $impresora->imagenESCPOS($rutaImagen, 240);
                            break;
                        case 'grande':
                        default:
                            $impresora->imagenGiganteOptimizada($rutaImagen);
                            break;
                    }
                } else {
                    $impresora->texto('[Logo desactivado]', 'center');
                }
                
                $impresora->texto('Kalli Jaguar', 'center', true, 'large');
                $impresora->saltoLinea();
                $impresora->linea('-', 32);
                $impresora->texto('Configuración aplicada correctamente', 'center');
                $impresora->texto('Fecha: ' . date('d/m/Y H:i:s'), 'center');
                $impresora->cortar();
                
                // Si se especifica impresora, imprimir
                if (isset($input['impresora'])) {
                    $resultado = $impresora->imprimir($input['impresora']);
                    
                    // Verificar si el resultado es un array (nueva versión) o string (versión antigua)
                    if (is_array($resultado)) {
                        if ($resultado['success']) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Prueba de logo enviada a impresora',
                                'resultado' => $resultado['output'] ?? ''
                            ]);
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => $resultado['message'],
                                'error' => $resultado['error'],
                                'suggestion' => $resultado['suggestion'] ?? null
                            ]);
                        }
                    } else {
                        // Compatibilidad con versión antigua (string)
                        echo json_encode([
                            'success' => true,
                            'message' => 'Prueba de logo enviada a impresora',
                            'resultado' => $resultado
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => true,
                        'comandos' => base64_encode($impresora->obtenerComandos()),
                        'message' => 'Comandos ESC/POS generados para prueba de logo'
                    ]);
                }
                break;
                
            case 'ticket':
                // Generar ticket de orden
                if (!isset($input['orden_id'])) {
                    throw new Exception('ID de orden no especificado');
                }
                
                // Obtener datos de la orden
                $stmt = $pdo->prepare("SELECT * FROM ordenes o JOIN mesas m WHERE o.mesa_id=mesa_id AND o.id = ?");
                $stmt->execute([$input['orden_id']]);
                $orden = $stmt->fetch();
                
                if (!$orden) {
                    throw new Exception('Orden no encontrada');
                }
                
                // Obtener productos de la orden (solo productos preparados, no cancelados)
                $stmt = $pdo->prepare("
                    SELECT op.*, p.nombre, p.precio 
                    FROM orden_productos op 
                    JOIN productos p ON op.producto_id = p.id 
                    WHERE op.orden_id = ? AND op.preparado = 1
                ");
                $stmt->execute([$input['orden_id']]);
                $productos = $stmt->fetchAll();

                // Obtener datos de la sucursal
                $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'empresa_nombre'");
                $stmt->execute();
                $sucursal = $stmt->fetch();

                if (!$sucursal) {
                    throw new Exception('Sucursal no encontrada');
                }
                
                // Generar ticket
                // ✅ USAR IMAGEN CONFIGURADA
                $impresora->imagenConfigurada();

                $impresora->texto('Sucursal: ' . $sucursal['valor'], 'left');
                $impresora->texto('Mesa: ' . $orden['nombre'], 'left');
                $impresora->texto('Orden: #' . $orden['codigo'], 'left');
                $impresora->texto('Fecha: ' . date('d/m/Y H:i:s', strtotime($orden['creada_en'])), 'left');
                $impresora->saltoLinea();
                $impresora->linea('=', 45);
                $impresora->saltoLinea();
                
                // Productos
                $impresora->tablaProductos($productos);
                
                // Total
                $impresora->saltoLinea();
                $impresora->texto('TOTAL: $' . number_format($orden['total'], 2), 'right', true, 'large');
                $impresora->saltoLinea();
                
                // Total en texto (optimizado para tickets térmicos)
                $totalTexto = numeroATextoHelper($orden['total']);
                
                // Dividir texto largo en líneas para mejor ajuste
                $lineas = dividirTextoParaTicketHelper($totalTexto, 32);
                foreach ($lineas as $linea) {
                    $impresora->texto($linea, 'center', false, 'normal');
                    $impresora->saltoLinea();
                }
                
                // Información del pago (siempre mostrar si la orden está cerrada)
                if ($orden['estado'] === 'cerrada') {
                    $impresora->linea('-', 45);
                    $impresora->texto('METODO DE PAGO: ' . formatearMetodoPagoHelper($orden['metodo_pago'] ?? 'efectivo'), 'left', true);
                    
                    if (($orden['metodo_pago'] === 'efectivo' || !isset($orden['metodo_pago'])) && isset($orden['dinero_recibido']) && $orden['dinero_recibido'] !== null) {
                        $impresora->texto('Dinero recibido: $' . number_format($orden['dinero_recibido'], 2), 'left');
                        
                        if (isset($orden['cambio']) && $orden['cambio'] !== null && $orden['cambio'] > 0) {
                            $impresora->texto('Cambio: $' . number_format($orden['cambio'], 2), 'left', true);
                        } else {
                            $impresora->texto('Pago exacto', 'left');
                        }
                    }
                    $impresora->saltoLinea();
                }
                // Información del pago (si se proporciona desde parámetros - para compatibilidad)
                elseif (isset($input['metodo_pago'])) {
                    $impresora->linea('-', 45);
                    $impresora->texto('METODO DE PAGO: ' . formatearMetodoPagoHelper($input['metodo_pago']), 'left', true);
                    
                    if ($input['metodo_pago'] === 'efectivo' && isset($input['dinero_recibido'])) {
                        $dineroRecibido = floatval($input['dinero_recibido']);
                        $impresora->texto('Dinero recibido: $' . number_format($dineroRecibido, 2), 'left');
                        
                        if (isset($input['cambio'])) {
                            $cambio = floatval($input['cambio']);
                            if ($cambio > 0) {
                                $impresora->texto('Cambio: $' . number_format($cambio, 2), 'left', true);
                            } else {
                                $impresora->texto('Pago exacto', 'left');
                            }
                        }
                    }
                    $impresora->saltoLinea();
                }
                
                // Obtener productos cancelados (si los hay)
                $stmt = $pdo->prepare("
                    SELECT op.*, p.nombre, p.precio 
                    FROM orden_productos op 
                    JOIN productos p ON op.producto_id = p.id 
                    WHERE op.orden_id = ? AND op.cancelado = 1
                ");
                $stmt->execute([$input['orden_id']]);
                $productosCancelados = $stmt->fetchAll();
                
                // Mostrar productos cancelados si existen
                if (!empty($productosCancelados)) {
                    $impresora->linea('-', 24);
                    $impresora->texto('PRODUCTOS CANCELADOS:', 'left', true);
                    $impresora->saltoLinea();
                    
                    // Usar el mismo formato que tablaProductos pero para cancelados
                    foreach ($productosCancelados as $producto) {
                        $nombre = substr($producto['nombre'], 0, 28);
                        $cantidad = str_pad($producto['cantidad'], 4, ' ', STR_PAD_LEFT);
                        $precio = str_pad('$' . number_format($producto['precio'], 2), 10, ' ', STR_PAD_LEFT);
                        
                        $linea = str_pad($nombre, 28) . $cantidad . $precio;
                        $impresora->texto($linea, 'left');
                    }
                    $impresora->saltoLinea();
                }
                
                $impresora->linea('=', 45);
                $impresora->texto('Gracias por su compra!', 'center');
                $impresora->saltoLinea();
                $impresora->cortar();
                
                // Imprimir si se especifica impresora
                if (isset($input['impresora'])) {
                    $resultado = $impresora->imprimir($input['impresora']);
                    
                    // Verificar si el resultado es un array (nueva versión) o string (versión antigua)
                    if (is_array($resultado)) {
                        if ($resultado['success']) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Ticket enviado a impresora',
                                'resultado' => $resultado['output'] ?? ''
                            ]);
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => $resultado['message'],
                                'error' => $resultado['error'],
                                'suggestion' => $resultado['suggestion'] ?? null
                            ]);
                        }
                    } else {
                        // Compatibilidad con versión antigua (string)
                        echo json_encode([
                            'success' => true,
                            'message' => 'Ticket enviado a impresora',
                            'resultado' => $resultado
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => true,
                        'comandos' => base64_encode($impresora->obtenerComandos()),
                        'message' => 'Comandos ESC/POS generados'
                    ]);
                }
                break;
                
            default:
                throw new Exception('Tipo de impresión no válido');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
