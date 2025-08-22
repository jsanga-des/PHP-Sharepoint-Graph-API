<?php

require '../../vendor/autoload.php';
require '../config.php';

use SharePointClient\SharePointGraphApi;

$folder = 'Test/ZN/PR';

try {
    $client = new SharePointGraphApi(
        $client_id,
        $tenant_id,
        $pfx_path,
        $pfx_password
    );
     echo "Constructor inicializado correctamente";
} catch (Exception $e) {
    // Capturas cualquier excepción lanzada dentro de SharePointGraphApi
    echo "Error: " . $e->getMessage();
}

try {
    echo "🔍 Obteniendo Site ID...\\n";
    $site_id = $client->getSiteId($site_path);
    echo "✅ Site ID: $site_id\\n";

    echo "🔍 Obteniendo Drive ID...\\n";
    $drive_id = $client->getDriveId($site_id, $drive_name);
    echo "✅ Drive ID: $drive_id\\n";
} catch (Exception $e) {
    // Capturas cualquier excepción lanzada dentro de SharePointGraphApi
    echo "Error: " . $e->getMessage();
}


try {
    echo "Verificando existencia de carpeta '$folder'...\n";
    $exists = $client->folderExists($site_id, $drive_id, $folder);
    echo $exists
        ? "✅ Carpeta encontrada\n"
        : "❌ Carpeta no existe\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}