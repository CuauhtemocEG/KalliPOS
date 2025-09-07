<?php
/**
 * Configuración para Impresión Térmica
 * Personaliza estos valores según tu impresora térmica
 */

class ConfiguracionTermica {
    // Dimensiones del papel térmico (en mm)
    const ANCHO_PAPEL = 80;        // Ancho estándar para impresoras térmicas
    const ALTO_PAPEL = 200;        // Alto inicial (se ajusta automáticamente)
    
    // Márgenes (en mm)
    const MARGEN_IZQUIERDO = 4;
    const MARGEN_DERECHO = 4;
    const MARGEN_SUPERIOR = 4;
    const MARGEN_INFERIOR = 4;
    
    // Configuración de fuentes
    const FUENTE_TITULO = 'Arial';
    const TAMANO_TITULO = 14;
    
    const FUENTE_SUBTITULO = 'Arial';
    const TAMANO_SUBTITULO = 10;
    
    const FUENTE_NORMAL = 'Arial';
    const TAMANO_NORMAL = 9;
    
    const FUENTE_PEQUENA = 'Arial';
    const TAMANO_PEQUENA = 8;
    
    const FUENTE_MICRO = 'Arial';
    const TAMANO_MICRO = 7;
    
    // Configuración de la tabla de productos
    const ANCHO_COLUMNA_PRODUCTO = 36;  // mm
    const ANCHO_COLUMNA_CANTIDAD = 12;  // mm
    const ANCHO_COLUMNA_PRECIO = 16;    // mm
    const ANCHO_COLUMNA_TOTAL = 16;     // mm
    
    // Configuración del logo
    const MOSTRAR_LOGO = true;
    const ANCHO_LOGO = 28;             // mm
    const ALTO_LOGO = 20;              // mm aproximado
    
    // Configuración del código de barras
    const MOSTRAR_CODIGO_BARRAS = true;
    const ANCHO_CODIGO_BARRAS = 50;    // mm
    const ALTO_CODIGO_BARRAS = 10;     // mm
    
    // Configuración de mensajes
    const NOMBRE_RESTAURANTE = 'Kalli Jaguar';
    const SUBTITULO_TICKET = 'Ticket de Venta';
    const MENSAJE_DESPEDIDA_1 = 'Gracias por su visita';
    const MENSAJE_DESPEDIDA_2 = 'Kalli Jaguar Restaurant';
    const MENSAJE_INFO = 'Productos mostrados: solo preparados';
    
    // Configuración de impresión
    const ESPACIADO_FINAL = 8;         // mm de espacio al final para corte
    const INCLUIR_FECHA_GENERACION = true;
    const FORMATO_FECHA = 'd/m/Y H:i';
    
    /**
     * Obtiene el ancho disponible para contenido
     */
    public static function getAnchoContenido() {
        return self::ANCHO_PAPEL - self::MARGEN_IZQUIERDO - self::MARGEN_DERECHO;
    }
    
    /**
     * Verifica si debe mostrar el logo
     */
    public static function debeMotrarLogo() {
        return self::MOSTRAR_LOGO && file_exists('../assets/img/LogoBlack.png');
    }
    
    /**
     * Obtiene la ruta del logo
     */
    public static function getRutaLogo() {
        return '../assets/img/LogoBlack.png';
    }
    
    /**
     * Genera el nombre del archivo de salida
     */
    public static function getNombreArchivo($mesa, $incluirFecha = true) {
        $nombre = 'ticket_termico_mesa_' . $mesa;
        if ($incluirFecha) {
            $nombre .= '_' . date('Ymd_His');
        }
        return $nombre . '.pdf';
    }
    
    /**
     * Configuración de caracteres especiales para reemplazar
     */
    public static function getMapaCaracteres() {
        return [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U',
            '¿' => '?', '¡' => '!', 'º' => 'o', 'ª' => 'a',
            '€' => 'EUR', '°' => 'o', '¢' => 'c', '£' => 'L'
        ];
    }
}
?>
