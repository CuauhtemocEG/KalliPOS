#!/usr/bin/env php
<?php
/**
 * Script de verificación de URLs hardcodeadas
 * Busca posibles URLs hardcodeadas en el proyecto
 */

define('PROJECT_ROOT', __DIR__);
require_once PROJECT_ROOT . '/config/config.php';

echo "🔍 Verificando URLs hardcodeadas en el proyecto...\n";
echo "BASE_URL configurada: " . BASE_URL . "\n\n";

// Directorios a revisar
$directories = [
    'views/',
    'controllers/',
    'api/',
    'auth/',
    'includes/',
    'src/',
    './' // archivos en la raíz
];

// Patrones a buscar (URLs hardcodeadas sospechosas)
$patterns = [
    '/https?:\/\/[^\s\'"]+\.com\/[^\s\'"]*/', // URLs completas
    '/window\.location\.origin/', // window.location.origin
    '/location\.href\s*=\s*[\'"][^\'"]*(http|\/[^\/])/', // redirects con URLs
    '/fetch\s*\(\s*[\'"][^\'"]*(http|\/[^\/])/', // fetch con URLs
    '/href\s*=\s*[\'"][^\'"]*(http|\/[^\/])/', // enlaces con URLs absolutas
    '/src\s*=\s*[\'"][^\'"]*(http|\/[^\/])/', // recursos con URLs absolutas
];

// Archivos a excluir de la verificación
$excludeFiles = [
    'URL_HELPERS.md',
    'EJEMPLO_URL_HELPERS.php',
    'verificar_urls.php',
    'vendor/',
    'fpdf/',
    '.git/',
    'node_modules/',
];

$foundIssues = [];

function shouldExcludeFile($file) {
    global $excludeFiles;
    foreach ($excludeFiles as $exclude) {
        if (strpos($file, $exclude) !== false) {
            return true;
        }
    }
    return false;
}

function scanDirectory($dir) {
    global $patterns, $foundIssues;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/\.(php|js|html|css)$/', $file->getFilename())) {
            $relativePath = str_replace(PROJECT_ROOT . '/', '', $file->getPathname());
            
            if (shouldExcludeFile($relativePath)) {
                continue;
            }
            
            $content = file_get_contents($file->getPathname());
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line, $matches)) {
                        // Ignorar comentarios y documentación
                        if (preg_match('/^\s*(\/\/|\/\*|\*|#|<!--)/', trim($line))) {
                            continue;
                        }
                        
                        $foundIssues[] = [
                            'file' => $relativePath,
                            'line' => $lineNumber + 1,
                            'content' => trim($line),
                            'match' => $matches[0]
                        ];
                    }
                }
            }
        }
    }
}

// Escanear cada directorio
foreach ($directories as $dir) {
    if (is_dir(PROJECT_ROOT . '/' . $dir)) {
        scanDirectory(PROJECT_ROOT . '/' . $dir);
    }
}

// Reportar resultados
if (empty($foundIssues)) {
    echo "✅ No se encontraron URLs hardcodeadas sospechosas.\n";
    echo "Tu proyecto está configurado correctamente para usar las funciones helper.\n";
} else {
    echo "⚠️  Se encontraron " . count($foundIssues) . " posibles URLs hardcodeadas:\n\n";
    
    foreach ($foundIssues as $issue) {
        echo "📄 {$issue['file']}:{$issue['line']}\n";
        echo "   {$issue['content']}\n";
        echo "   🔍 Encontrado: {$issue['match']}\n\n";
    }
    
    echo "📝 Recomendaciones:\n";
    echo "1. Revisa cada archivo listado arriba\n";
    echo "2. Reemplaza URLs hardcodeadas con las funciones helper:\n";
    echo "   - url('path/to/file.php') para URLs internas\n";
    echo "   - asset('path/to/asset') para recursos\n";
    echo "   - apiUrl('endpoint.php') para APIs\n";
    echo "3. En JavaScript, usa las constantes definidas con PHP\n";
}

echo "\n📋 Resumen de funciones helper disponibles:\n";
echo "- getBaseUrl(): " . getBaseUrl() . "\n";
echo "- url('index.php'): " . url('index.php') . "\n";
echo "- asset('css/pos.css'): " . asset('css/pos.css') . "\n";
echo "- apiUrl('ordenes.php'): " . apiUrl('ordenes.php') . "\n";

echo "\n✨ ¡Proyecto verificado!\n";
?>
