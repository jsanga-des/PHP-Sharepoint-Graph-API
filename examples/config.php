<?php
// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Importar la clase
use SharePointClient\SharePointGraphApi;

// Configuración
$client_id = 'xxxx';
$tenant_id = 'yyyy';
$client_secret = 'zzzz';

$site_path = 'mysite.sharepoint.com:/sites/nameofmysite';
$drive_name = 'Documentos';

try {
    // Crear cliente
    $client = new SharePointGraphApi($client_id, $tenant_id, $client_secret);

    // Obtener IDs básicos
    echo "🔍 Obteniendo Site ID...\\n";
    $site_id = $client->getSiteId($site_path);
    echo "✅ Site ID: $site_id\\n";

    echo "🔍 Obteniendo Drive ID...\\n";
    $drive_id = $client->getDriveId($site_id, $drive_name);
    echo "✅ Drive ID: $drive_id\\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\\n";
    exit(1);
}