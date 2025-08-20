<?php
/**
 * Eliminar archivo usando sitePath
 */
require_once __DIR__ . '/../config.php';

$remote_file = 'test_file_uploaded.txt';


echo "Eliminando archivo mediante ruta del sitio Sharepoint...\n";

$result = $client->deleteFileBySitePath($site_path, $drive_name, $remote_file);

echo $result
    ? "✅ Archivo eliminado\n"
    : "❌ Error al eliminar\n";
