<?php
/**
 * Verificar existencia de carpeta en SharePoint
 */
require_once __DIR__ . '/../config.php';

$folder = 'InformesNokia';

echo "Verificando existencia de carpeta '$folder'...\n";

$exists = $client->folderExists($site_id, $drive_id, $folder);

echo $exists
    ? "✅ Carpeta encontrada\n"
    : "❌ Carpeta no existe\n";
