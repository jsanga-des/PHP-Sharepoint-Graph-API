<?php

/**
 * Listar archivos usando sitePath
 */


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
    // Capturas cualquier excepción lanzada dentro de SharePointGraphApi
    echo "Error: " . $e->getMessage();
}


try {
    $files = $client->listFilesBySitePath($site_path, $drive_name, $folder, 1);
} catch (Exception $e) {
    // Capturas cualquier excepción lanzada dentro de SharePointGraphApi
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




