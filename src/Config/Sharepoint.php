<?php

// =============================================================================
// src/Config/Sharepoint.php 
// =============================================================================

return [

    /*
     * Configuración del entorno de ejecución
     * Estos valores afectan a todas las instancias.
     */
    'env' => [
        'debug' => getenv('SHAREPOINT_DEBUG') ?? true,
        'log_level' => getenv('SHAREPOINT_LOG_LEVEL') ?? 'DEBUG',
        'timeout' => getenv('SHAREPOINT_TIMEOUT') ?? 120,
        'connect_timeout' => getenv('SHAREPOINT_CONNECT_TIMEOUT') ?? 60,
    ],

    /*
     * Definición de configuraciones por sitio (instancias de Sharepoint)
     */
    'sites' => [
        
        // Configuración para el sitio 'default'
        'default' => [
            'general' => [
                'client_id' => getenv('SHAREPOINT_EMPRESA_A_CLIENT_ID'),
                'tenant_id' => getenv('SHAREPOINT_EMPRESA_A_TENANT_ID'),
                'site_path' => getenv('SHAREPOINT_EMPRESA_A_SITE_PATH'),
                'default_library' => getenv('SHAREPOINT_EMPRESA_A_DEFAULT_LIBRARY'),
            ],
            'auth_method' => getenv('SHAREPOINT_EMPRESA_A_AUTH_METHOD') ?? 'client_secret',
            'auth' => [
                'client_secret' => [
                    'secret' => getenv('SHAREPOINT_EMPRESA_A_CLIENT_SECRET'),
                ],
                'certificate_pfx' => [
                    'path' => getenv('SHAREPOINT_EMPRESA_A_PFX_PATH'),
                    'passphrase' => getenv('SHAREPOINT_EMPRESA_A_PFX_PASSPHRASE'),
                ],
                'certificate_crt' => [
                    'cert_path' => getenv('SHAREPOINT_EMPRESA_A_CERT_PATH'),
                    'key_path' => getenv('SHAREPOINT_EMPRESA_A_KEY_PATH'),
                    'passphrase' => getenv('SHAREPOINT_EMPRESA_A_KEY_PASSPHRASE'),
                ],
            ],
        ],

        // Configuración para el sitio 'empresa_a'
        'empresa_a' => [
            'general' => [
                'client_id' => getenv('SHAREPOINT_EMPRESA_A_CLIENT_ID'),
                'tenant_id' => getenv('SHAREPOINT_EMPRESA_A_TENANT_ID'),
                'site_path' => getenv('SHAREPOINT_EMPRESA_A_SITE_PATH'),
                'default_library' => getenv('SHAREPOINT_EMPRESA_A_DEFAULT_LIBRARY'),
            ],
            'auth_method' => getenv('SHAREPOINT_EMPRESA_A_AUTH_METHOD') ?? 'client_secret',
            'auth' => [
                'client_secret' => [
                    'secret' => getenv('SHAREPOINT_EMPRESA_A_CLIENT_SECRET'),
                ],
                'certificate_pfx' => [
                    'path' => getenv('SHAREPOINT_EMPRESA_A_PFX_PATH'),
                    'passphrase' => getenv('SHAREPOINT_EMPRESA_A_PFX_PASSPHRASE'),
                ],
                'certificate_crt' => [
                    'cert_path' => getenv('SHAREPOINT_EMPRESA_A_CERT_PATH'),
                    'key_path' => getenv('SHAREPOINT_EMPRESA_A_KEY_PATH'),
                    'passphrase' => getenv('SHAREPOINT_EMPRESA_A_KEY_PASSPHRASE'),
                ],
            ],
        ],

        // Configuración para el sitio 'empresa_b'
        'empresa_b' => [
            'general' => [
                'client_id' => getenv('SHAREPOINT_EMPRESA_B_CLIENT_ID'),
                'tenant_id' => getenv('SHAREPOINT_EMPRESA_B_TENANT_ID'),
                'site_path' => getenv('SHAREPOINT_EMPRESA_B_SITE_PATH'),
                'default_library' => getenv('SHAREPOINT_EMPRESA_B_DEFAULT_LIBRARY'),
            ],
            'auth_method' => getenv('SHAREPOINT_EMPRESA_B_AUTH_METHOD') ?? 'certificate_pfx',
            'auth' => [
                'client_secret' => [
                    'secret' => getenv('SHAREPOINT_EMPRESA_B_CLIENT_SECRET'),
                ],
                'certificate_pfx' => [
                    'path' => getenv('SHAREPOINT_EMPRESA_B_PFX_PATH'),
                    'passphrase' => getenv('SHAREPOINT_EMPRESA_B_PFX_PASSPHRASE'),
                ],
                'certificate_crt' => [
                    'cert_path' => getenv('SHAREPOINT_EMPRESA_B_CERT_PATH'),
                    'key_path' => getenv('SHAREPOINT_EMPRESA_B_KEY_PATH'),
                    'passphrase' => getenv('SHAREPOINT_EMPRESA_B_KEY_PASSPHRASE'),
                ],
            ],
        ],
    ],
];