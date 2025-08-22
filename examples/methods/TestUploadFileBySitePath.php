<?php

require '../../vendor/autoload.php';
require '../config.php';

use SharePointClient\SharePointGraphApi;

$local_filepath = '../test_file.txt';
$remote_filename = 'test_file_uploaded2.txt';
$remote_filepath = 'Test/ZN'; // Vacío para raiz

try {
    $client = new SharePointGraphApi(
        $client_id,
        $tenant_id,
        $pfx_path,
        $pfx_password
    );
     echo "Constructor inicializado correctamente";
} catch (Exception $e) {
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
    echo "Error: " . $e->getMessage();
}

try {
    if (!file_exists($local_filepath)) {
        file_put_contents($local_filepath, "Archivo de prueba " . date('Y-m-d H:i:s') . "\n");
    }
    echo "Subiendo archivo '$local_filepath' a Sharepoint ($remote_filepath), renombrado como: '$remote_filename'...\n";
    $result = $client->uploadFileBySitePath(
        $site_id, 
        $drive_id, 
        $local_filepath,   // Archivo local
        $remote_filepath,  // Ruta remota
        $remote_filename   // (opcional)
    );
    echo $result
        ? "✅ Subido correctamente\n"
        : "❌ Error en la subida\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}









