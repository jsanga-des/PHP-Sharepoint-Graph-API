<?php

use SharepointClient\SharepointClient;
use SharepointClient\Config\ConfigManager;

require '../vendor/autoload.php';

try {
    // Carga la configuración y crea el cliente.
    $config = ConfigManager::getInstance('empresa_a');
    $client = new SharepointClient($config);

    // Define los parámetros.
    // La ruta de la carpeta donde buscar, y el nombre del archivo a verificar.
    $folderPath = 'remote_folder/remote_subfolder'; 
    $fileName = 'remote_file.xlsx';

    echo "Verificando si el archivo '{$fileName}' existe en la carpeta '{$folderPath}'...\n\n";

    // Llama al método para verificar la existencia del archivo.
    $exists = $client->fileExistsInFolder($folderPath, $fileName);

    if ($exists) {
        echo "El archivo '{$fileName}' existe en la carpeta '{$folderPath}'.\n";
    } else {
        echo "El archivo '{$fileName}' no existe en la carpeta '{$folderPath}'.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}