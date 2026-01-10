<?php
// Archivo temporal para debug - ELIMINAR en producción

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP Debug Info:<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br><br>";

// Verificar permisos de uploads
$uploads_dir = __DIR__ . '/../uploads/productos';
echo "Uploads dir: $uploads_dir<br>";
echo "Exists: " . (file_exists($uploads_dir) ? 'Yes' : 'No') . "<br>";
echo "Is writable: " . (is_writable($uploads_dir) ? 'Yes' : 'No') . "<br>";
echo "Permissions: " . substr(sprintf('%o', fileperms($uploads_dir)), -4) . "<br><br>";

// Test de write
$test_file = $uploads_dir . '/test.txt';
if (file_put_contents($test_file, 'test')) {
    echo "Write test: SUCCESS<br>";
    unlink($test_file);
} else {
    echo "Write test: FAILED<br>";
}
?>
