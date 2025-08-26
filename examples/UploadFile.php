<?php

use SharepointClient\SharepointClient;
use SharepointClient\Config\ConfigManager;

require '../vendor/autoload.php';

try {
    // Carga la configuración y crea el cliente.
    $config = ConfigManager::getInstance('empresa_a');
    $client = new SharepointClient($config);

    // Definir los parámetros.
    // Ruta del archivo local, carpeta de destino en Sharepoint (vacío para la raíz)
    // y nombre que tendrá el archivo en Sharepoint
    $localFilePath = __DIR__ . '/test_uploaded.txt'; 
    $remotePath = 'remote_folder/'; 
    $fileName = 'UploadedFile.txt'; 

    echo "Subiendo el archivo '{$localFilePath}' a la raíz de la biblioteca 'Documentos'...\n\n";

    // Llamar al método para subir el archivo.
    $uploaded = $client->uploadFile(
        $localFilePath,
        $remotePath,
        $fileName
    );

    if ($uploaded) {
        echo "Subida de archivo correcta.\n";
    } else {
        echo "Error en la subida del archivo.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}