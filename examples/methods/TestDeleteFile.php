<?php

require '../../vendor/autoload.php';
require '../config.php';

use SharePointClient\SharePointGraphApi;

$remote_file = 'Test/ZN/test_file_uploaded2.txt';

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
    echo "Eliminando archivo '$remote_file'...\n";
    $result = $client->deleteFile($site_id, $drive_id, $remote_file);
    echo $result
        ? "Archivo eliminado correctamente\n"
        : "Error al eliminar archivo\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

