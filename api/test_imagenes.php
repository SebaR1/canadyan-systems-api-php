<?php
// Test de producto_imagenes - ELIMINAR en producción

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Test de producto_imagenes.php<br><br>";

// Simular la llamada a list
$_GET['action'] = 'list';
$_GET['producto_id'] = 132;

echo "Incluyendo producto_imagenes.php...<br>";

try {
    include 'routes/producto_imagenes.php';
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br>";
    echo nl2br($e->getTraceAsString());
}
?>
