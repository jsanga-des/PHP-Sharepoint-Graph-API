<?php
/**
 * Eliminar archivo en SharePoint
 */
require_once __DIR__ . '/../config.php';

$remote_file = 'test_file_uploaded.txt';

echo "Eliminando archivo '$remote_file'...\n";

$result = $client->deleteFile($site_id, $drive_id, $remote_file);

echo $result
    ? "✅ Archivo eliminado correctamente\n"
    : "❌ Error al eliminar archivo\n";
