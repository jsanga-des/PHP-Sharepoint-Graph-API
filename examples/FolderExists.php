<?php

use SharepointClient\SharepointClient;
use SharepointClient\Config\ConfigManager;

require '../vendor/autoload.php';

try {
    // Carga la configuración y crea el cliente.
    $config = ConfigManager::getInstance('empresa_a');
    $client = new SharepointClient($config);

    // Define los parámetros.
    // Ruta de la carpeta a verificar.
    $remotePath = 'remote_folder'; 

    echo "Verificando si la carpeta '{$remotePath}' existe...\n\n";

    // Llama al método para verificar la existencia de la carpeta.
    $exists = $client->folderExists($remotePath);

    if ($exists) {
        echo "La carpeta '{$remotePath}' existe.\n";
    } else {
        echo "La carpeta '{$remotePath}' no existe.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}