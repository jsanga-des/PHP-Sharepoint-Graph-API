<?php
/**
 * Verificar existencia de un archivo dentro de una carpeta
 */
require_once __DIR__ . '/../config.php';

$folder = 'InformesNokia';
$file = 'informe_geco_20250820_161457.xlsx';

echo "Verificando existencia de '$file' en carpeta '$folder'...\n";

$exists = $client->fileExistsInFolder($site_id, $drive_id, $folder, $file);

echo $exists
    ? "✅ El archivo existe en la carpeta\n"
    : "❌ El archivo no existe en la carpeta\n";
