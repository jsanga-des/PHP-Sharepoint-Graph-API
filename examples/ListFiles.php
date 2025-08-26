<?php

use SharepointClient\SharepointClient;
use SharepointClient\Config\ConfigManager;

require '../vendor/autoload.php';

try {
    // Carga la configuración y crea el cliente.
    $config = ConfigManager::getInstance('empresa_a');
    $client = new SharepointClient($config);

    // Define los parámetros.
    // Lista los archivos y carpetas con la profundidad indicada (maxDepth = 0).
    $remotePath = 'remote_folder';
    $maxDepth = 0; 
    
    echo "Buscando en la raíz de la biblioteca 'Documentos/" . $remotePath . "...\n\n";

    $items = $client->listFiles($remotePath, $maxDepth);

    // Procesa y limpia los datos.
    $results = [];
    foreach ($items as $item) {
        // Solo procesamos elementos que sean archivos
        if (isset($item['file'])) {
            $results[] = [
                'nombre' => $item['name'] ?? null,
                'link_descarga' => $item['@microsoft.graph.downloadUrl'],
                'tamano' => $item['size'] ?? null,
                'creador' => $item['createdBy']['user']['displayName'] ?? 'Desconocido',
                'ultima_modificacion' => $item['lastModifiedDateTime'] ?? null
            ];
        }
    }
    print_r($results);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}