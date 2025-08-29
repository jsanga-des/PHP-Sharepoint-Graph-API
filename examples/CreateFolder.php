<?php

use SharepointClient\SharepointClient;
use SharepointClient\Config\ConfigManager;

require '../vendor/autoload.php';

try {
    // Carga la configuración de la instancia 'empresa_a'
    $config = ConfigManager::getInstance('empresa_a');

    // Inicializa el cliente de Sharepoint
    $client = new SharepointClient($config);

    // Ejemplo 1: Crear carpeta en la raíz con nombre específico
    $created = $client->createFolder('', 'Nueva Carpeta');
    
    // Ejemplo 2: Crear carpeta dentro de Documents con nombre específico
    // $created = $client->createFolder('Documents', 'Reports');
    
    // Ejemplo 3: Crear estructura completa anidada (crea Documents, Reports y 2024 si no existen)
    // $created = $client->createFolder('Documents/Reports', '2024');
    
    // Ejemplo 4: Crear carpeta usando el último segmento del path como nombre
    // $created = $client->createFolder('Documents/Reports/2024');
    
    // Ejemplo 5: Crear estructura profunda automáticamente
    // $created = $client->createFolder('Projects/WebApp/Frontend/Components');

    if ($created) {
        echo "Carpeta creada correctamente\n";
    } else {
        echo "No se pudo crear la carpeta\n";
    }

} catch (\Exception $e) {
    // Si la llamada lanza una excepción, la capturamos y mostramos el error
    echo "Error creando la carpeta:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
}