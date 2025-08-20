<?php
/**
 * Subir archivo a SharePoint
 */
require_once __DIR__ . '/../config.php';

$local_file = __DIR__ . '/test_file.txt';
$remote_file = 'test_file_uploaded.txt';

// Crear archivo local si no existe
if (!file_exists($local_file)) {
    file_put_contents($local_file, "Archivo de prueba " . date('Y-m-d H:i:s') . "\n");
}

echo "Subiendo archivo '$local_file' como '$remote_file'...\n";

$result = $client->uploadFile($site_id, $drive_id, $remote_file, $local_file);

if ($result) {
    echo "✅ Archivo subido correctamente\n";
} else {
    echo "❌ Error al subir archivo\n";
}
