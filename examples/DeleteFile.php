<?php

use SharepointClient\SharepointClient;
use SharepointClient\Config\ConfigManager;

require '../vendor/autoload.php';

try {
    // Carga la configuración y crea el cliente.
    $config = ConfigManager::getInstance('empresa_a');
    $client = new SharepointClient($config);

    // Define la ruta del archivo a eliminar.
    // La ruta debe ser relativa a la biblioteca de documentos (o la que hayas definido en el .env)
    $remoteFilePath = 'remote_folder'; 
    $remoteFileName = 'UploadedFile.txt'; 

    echo "Intentando eliminar el archivo '{$remoteFilePath}'...\n\n";

    // Llama al método para eliminar el archivo.
    // El método devuelve 'true' si la eliminación fue exitosa (código 204).
    $deleted = $client->deleteFile($remoteFilePath, $remoteFileName);

    if ($deleted) {
        echo "Archivo '{$remoteFileName}' eliminado correctamente.\n";
    } else {
        echo "No se pudo eliminar el archivo '{$remoteFilePath}'/" . $remoteFileName . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}