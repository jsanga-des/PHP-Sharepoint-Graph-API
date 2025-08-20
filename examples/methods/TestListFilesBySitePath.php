<?php
/**
 * Listar archivos usando sitePath
 */
require_once __DIR__ . '/../config.php';

echo "Listando archivos mediante ruta del sitio Sharepoint...\n";

$files = $client->listFilesBySitePath($site_path, $drive_name);

if (count($files) > 0) {
    echo "Archivos encontrados (" . count($files) . "):\n";
    foreach (array_slice($files, 0, 10) as $file) {
        echo " - $file\n";
    }
    if (count($files) > 10) {
        echo "... y " . (count($files) - 10) . " más\n";
    }
} else {
    echo "No se encontraron archivos.\n";
}