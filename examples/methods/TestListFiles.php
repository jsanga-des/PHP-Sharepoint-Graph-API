<?php

require '../../vendor/autoload.php';
require '../config.php';

use SharePointClient\SharePointGraphApi;

$folder = 'Test/ZN/';

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
    $files = $client->listFiles($site_id, $drive_id, $folder, 1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

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

?>




