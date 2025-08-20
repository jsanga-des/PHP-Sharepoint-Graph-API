<?php
/**
 * Subir archivo usando sitePath
 */
require_once __DIR__ . '/../config.php';

$local_file = __DIR__ . '/../test_file.txt';
$remote_file = 'test_file_uploaded.txt';

echo "Subiendo archivo mediante ruta del sitio Sharepoint...\n";

$result = $client->uploadFileBySitePath($site_path, $drive_name, $remote_file, $local_file);

echo $result
    ? "✅ Subido correctamente\n"
    : "❌ Error en la subida\n";
