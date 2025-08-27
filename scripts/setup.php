<?php


// setup.php
// Ejecutar automáticamente en post-install-cmd desde composer

$logFile     = __DIR__ . '/setup.log';

error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline){
    // Log en lugar de interrumpir
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "ERROR [$errno] $errstr en $errfile:$errline" . PHP_EOL, FILE_APPEND);
    return true; // evita que PHP convierta esto en un fatal
});
set_exception_handler(function($e){
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "EXCEPCIÓN: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
});


file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "=== Configurando Sharepoint Client API ===" . PHP_EOL, FILE_APPEND);

// 1️⃣ Detectar la raíz del proyecto desde scripts/
$projectRoot = dirname(__DIR__, 4);  // scripts/ → php-sharepoint-graph-api/ → jsanga-des/ → vendor/ → raíz
$vendorDir   = dirname(__DIR__);    // php-sharepoint-graph-api

file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Raíz del proyecto: $projectRoot" . PHP_EOL, FILE_APPEND);
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Carpeta destino: $projectRoot" . PHP_EOL, FILE_APPEND);

// 2️⃣ Crear carpeta principal
if (!is_dir($projectRoot)) {
    mkdir($projectRoot, 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Carpeta creada: $projectRoot" . PHP_EOL, FILE_APPEND);
} else {
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Carpeta $projectRoot ya existe" . PHP_EOL, FILE_APPEND);
}

// 3️⃣ Crear carpeta certs/
$certsDir = $projectRoot . '/certs';
if (!is_dir($certsDir)) {
    mkdir($certsDir, 0777, true);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Carpeta creada: $certsDir" . PHP_EOL, FILE_APPEND);
} else {
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Carpeta $certsDir ya existe" . PHP_EOL, FILE_APPEND);
}

// 4️⃣ Crear .env.sharepoint.example
$envFile = $projectRoot . '/.env.sharepoint.example';
if (!file_exists($envFile)) {
    $envContent = <<<'ENV'
# =============================================================================
# .env
# =============================================================================

# Configuración del entorno de ejecución
APP_ENV=development
SHAREPOINT_DEBUG=true
SHAREPOINT_LOG_LEVEL=DEBUG
SHAREPOINT_TIMEOUT=120
SHAREPOINT_CONNECT_TIMEOUT=60

# === ENTORNO 1 (Empresa A) ===================================================
SHAREPOINT_EMPRESA_A_CLIENT_ID=12345678-1234-1234-1234-123456789012
SHAREPOINT_EMPRESA_A_TENANT_ID=87654321-4321-4321-4321-210987654321
SHAREPOINT_EMPRESA_A_SITE_PATH=tuempresa.sharepoint.com:/sites/TuSitio
SHAREPOINT_EMPRESA_A_DEFAULT_LIBRARY=Documentos
SHAREPOINT_EMPRESA_A_AUTH_METHOD=certificate_pfx
SHAREPOINT_EMPRESA_A_PFX_PATH=./ruta/completa/al/certificado.pfx
SHAREPOINT_EMPRESA_A_PFX_PASSPHRASE=claveCertificado

# === ENTORNO 2 (Empresa B) ===================================================
SHAREPOINT_EMPRESA_B_CLIENT_ID=12345678-1234-1234-1234-123456735874
SHAREPOINT_EMPRESA_B_TENANT_ID=87654321-4321-4321-4321-210987673453
SHAREPOINT_EMPRESA_B_SITE_PATH=tuotraempresa.sharepoint.com:/sites/TuSitio
SHAREPOINT_EMPRESA_B_DEFAULT_LIBRARY=Documentos
SHAREPOINT_EMPRESA_B_AUTH_METHOD=client_secret
SHAREPOINT_EMPRESA_B_CLIENT_SECRET=ejemplo156-ejemplo02a?.ejemplo*1234-ejemplowachTx
ENV;

    file_put_contents($envFile, $envContent);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Creado: $envFile" . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Se ha generado correctamente el contenido del archivo .env" . PHP_EOL, FILE_APPEND);
    
} else {
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "El archivo $envFile ya existe, no se sobrescribe" . PHP_EOL, FILE_APPEND);
}

// 5️⃣ Crear Sharepoint.php
$phpConfig = $projectRoot . '/Sharepoint.php';
if (!file_exists($phpConfig)) {
    $phpContent = <<<'PHP'
<?php

return [

    'env' => [
        'debug' => getenv('SHAREPOINT_DEBUG') ?? true,
        'log_level' => getenv('SHAREPOINT_LOG_LEVEL') ?? 'DEBUG',
        'timeout' => getenv('SHAREPOINT_TIMEOUT') ?? 120,
        'connect_timeout' => getenv('SHAREPOINT_CONNECT_TIMEOUT') ?? 60,
    ],

    'sites' => [
        'default' => [
            'general' => [
                'client_id' => getenv('SHAREPOINT_EMPRESA_A_CLIENT_ID'),
                'tenant_id' => getenv('SHAREPOINT_EMPRESA_A_TENANT_ID'),
                'site_path' => getenv('SHAREPOINT_EMPRESA_A_SITE_PATH'),
                'default_library' => getenv('SHAREPOINT_EMPRESA_A_DEFAULT_LIBRARY'),
            ],
            'auth_method' => getenv('SHAREPOINT_EMPRESA_A_AUTH_METHOD') ?? 'client_secret',
            'auth' => [
                'client_secret' => ['secret' => getenv('SHAREPOINT_EMPRESA_A_CLIENT_SECRET')],
                'certificate_pfx' => ['path' => getenv('SHAREPOINT_EMPRESA_A_PFX_PATH'), 'passphrase' => getenv('SHAREPOINT_EMPRESA_A_PFX_PASSPHRASE')],
                'certificate_crt' => ['cert_path' => getenv('SHAREPOINT_EMPRESA_A_CERT_PATH'), 'key_path' => getenv('SHAREPOINT_EMPRESA_A_KEY_PATH'), 'passphrase' => getenv('SHAREPOINT_EMPRESA_A_KEY_PASSPHRASE')],
            ],
        ],
        'empresa_a' => [
            'general' => [
                'client_id' => getenv('SHAREPOINT_EMPRESA_A_CLIENT_ID'),
                'tenant_id' => getenv('SHAREPOINT_EMPRESA_A_TENANT_ID'),
                'site_path' => getenv('SHAREPOINT_EMPRESA_A_SITE_PATH'),
                'default_library' => getenv('SHAREPOINT_EMPRESA_A_DEFAULT_LIBRARY'),
            ],
            'auth_method' => getenv('SHAREPOINT_EMPRESA_A_AUTH_METHOD') ?? 'client_secret',
            'auth' => [
                'client_secret' => ['secret' => getenv('SHAREPOINT_EMPRESA_A_CLIENT_SECRET')],
                'certificate_pfx' => ['path' => getenv('SHAREPOINT_EMPRESA_A_PFX_PATH'), 'passphrase' => getenv('SHAREPOINT_EMPRESA_A_PFX_PASSPHRASE')],
                'certificate_crt' => ['cert_path' => getenv('SHAREPOINT_EMPRESA_A_CERT_PATH'), 'key_path' => getenv('SHAREPOINT_EMPRESA_A_KEY_PATH'), 'passphrase' => getenv('SHAREPOINT_EMPRESA_A_KEY_PASSPHRASE')],
            ],
        ],
        'empresa_b' => [
            'general' => [
                'client_id' => getenv('SHAREPOINT_EMPRESA_B_CLIENT_ID'),
                'tenant_id' => getenv('SHAREPOINT_EMPRESA_B_TENANT_ID'),
                'site_path' => getenv('SHAREPOINT_EMPRESA_B_SITE_PATH'),
                'default_library' => getenv('SHAREPOINT_EMPRESA_B_DEFAULT_LIBRARY'),
            ],
            'auth_method' => getenv('SHAREPOINT_EMPRESA_B_AUTH_METHOD') ?? 'certificate_pfx',
            'auth' => [
                'client_secret' => ['secret' => getenv('SHAREPOINT_EMPRESA_B_CLIENT_SECRET')],
                'certificate_pfx' => ['path' => getenv('SHAREPOINT_EMPRESA_B_PFX_PATH'), 'passphrase' => getenv('SHAREPOINT_EMPRESA_B_PFX_PASSPHRASE')],
                'certificate_crt' => ['cert_path' => getenv('SHAREPOINT_EMPRESA_B_CERT_PATH'), 'key_path' => getenv('SHAREPOINT_EMPRESA_B_KEY_PATH'), 'passphrase' => getenv('SHAREPOINT_EMPRESA_B_KEY_PASSPHRASE')],
            ],
        ],
    ],
];
PHP;

    file_put_contents($phpConfig, $phpContent);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Creado: $phpConfig" . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Se ha generado correctamente el contenido del archivo de configuración: $phpConfig" . PHP_EOL, FILE_APPEND);

} else {
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "El archivo $phpConfig ya existe, no se sobrescribe" . PHP_EOL, FILE_APPEND);
}

// 6️⃣ Copiar ejemplos desde el paquete
$packageExamples = $vendorDir . '/examples';
$newExamples     = $projectRoot . '/examples';

if (is_dir($packageExamples)) {
    if (!is_dir($newExamples)) {
        mkdir($newExamples, 0777, true);
    }
    $files = scandir($packageExamples);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $src = $packageExamples . '/' . $file;
        $dst = $newExamples . '/' . $file;
        if (!file_exists($dst)) {
            copy($src, $dst);
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Copiado ejemplo: $file" . PHP_EOL, FILE_APPEND);
        }
    }
} else {
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "No se encontraron ejemplos en el paquete" . PHP_EOL, FILE_APPEND);
}

file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "=== Configuración completada ===" . PHP_EOL, FILE_APPEND);

exit(0);