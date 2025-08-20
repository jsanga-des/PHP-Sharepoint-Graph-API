<?php
/**
 * Listar archivos en la biblioteca SharePoint
 */
require_once __DIR__ . '/../config.php';

echo "=== Listando archivos en '$drive_name' ===\n";

$files = $client->listFiles($site_id, $drive_id, 'root', '', 0);

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
