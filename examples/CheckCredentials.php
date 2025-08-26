<?php

use SharepointClient\SharepointClient;
use SharepointClient\Config\ConfigManager;

require '../vendor/autoload.php';

try {
    // Carga la configuración de la instancia 'empresa_a'
    $config = ConfigManager::getInstance('empresa_a');

    // Inicializa el cliente de Sharepoint
    $client = new SharepointClient($config);

    // Obtiene el Site ID
    $siteId = $client->getSiteId();
    
    // Si la llamada no lanza una excepción, es correcta.
    // Mostramos el Site ID al usuario.
    echo "Conexión exitosa. Site ID obtenido correctamente: \n";
    echo $siteId . "\n";

} catch (\Exception $e) {
    // Si la llamada lanza una excepción, la capturamos y mostramos el error.
    echo "Error en la conexión o al obtener el Site ID: \n";
    echo "Mensaje: " . $e->getMessage() . "\n";
}