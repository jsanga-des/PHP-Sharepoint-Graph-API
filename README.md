# Cliente SharePoint PHP

Sencillo **cliente PHP** para interactuar con **SharePoint** Online a **través de Microsoft Graph API**.
**Versión 2.0.0**


## Descripción

Este cliente permite realizar operaciones básicas con SharePoint Online, incluyendo la gestión de archivos y carpetas a través de la API de Microsoft Graph. Soporta tres métodos de autenticación: Client Secret, Certificado PEM y PFX.


## Características

- Autenticación con Microsoft Graph API
- Soporta múltiples entornos Sharepoint y configura cada uno de ellos, con sus respectivas credenciales.
- Múltiples métodos de autenticación: Client Secret, Certificado PEM y PFX
- Obtención de IDs de sitio y biblioteca
- Listado de archivos y carpetas
- Subida de archivos
- Eliminación de archivos
- Verificación de existencia de archivos y carpetas


## Instalación con composer

Instalar la librería php-sharepoint-graph-api 
```bash
composer require jsanga-des/php-sharepoint-graph-api:dev-main
```

Incluir/disponer del autoloader
```php
require_once __DIR__ . '/vendor/autoload.php';
```

## Instalación Manual

Clonar o descargar el repositorio:
```bash
git clone https://github.com/jsanga-des/php-sharepoint-graph-api.git
```

Cargar e incluir la clase SharePointClient: 
```php
require_once 'path/to/SharePointClient.php';
```

## Inicialización del cliente:
```php
use SharepointClient\SharepointClient;
use SharepointClient\Config\ConfigManager;

// Cargar configuración por nombre de entorno
$config = ConfigManager::getInstance('default');
// O Alternativamente: $config = ConfigManager::getInstance('entorno1');

// Crear el cliente
$client = new SharepointClient($config);
```


## Configuración de entornos:

El cliente utiliza un sistema de entornos definido en el archivo src/Config/Sharepoint.php. Cada sitio o instancia de SharePoint se configura aquí con sus credenciales, método de autenticación y valores generales de ejecución.
Es importante no exponer aquí las credenciales. Aquí sólo se debe nombrar la variable de entorno que contiene el valor secreto.
```php
return [
    'env' => [
        'debug' => true,
        'log_level' => 'DEBUG',
        'timeout' => 120,
        'connect_timeout' => 60,
    ],
    'sites' => [
        'default' => [
            'general' => [
                'client_id' => getenv('SHAREPOINT_ENTORNO1_CLIENT_ID'),
                'tenant_id' => getenv('SHAREPOINT_ENTORNO1_TENANT_ID'),
                'site_path' => getenv('SHAREPOINT_ENTORNO1_SITE_PATH'),
                'default_library' => getenv('SHAREPOINT_ENTORNO1_DEFAULT_LIBRARY'),
            ],
            'auth_method' => getenv('SHAREPOINT_ENTORNO1_AUTH_METHOD') ?? 'client_secret',
            'auth' => [
                'client_secret' => [
                    'secret' => getenv('SHAREPOINT_ENTORNO1_CLIENT_SECRET'),
                ],
                'certificate_pfx' => [
                    'path' => getenv('SHAREPOINT_ENTORNO1_PFX_PATH'),
                    'passphrase' => getenv('SHAREPOINT_ENTORNO1_PFX_PASSPHRASE'),
                ],
                'certificate_crt' => [
                    'cert_path' => getenv('SHAREPOINT_ENTORNO1_CERT_PATH'),
                    'key_path' => getenv('SHAREPOINT_ENTORNO1_KEY_PATH'),
                    'passphrase' => getenv('SHAREPOINT_ENTORNO1_KEY_PASSPHRASE'),
                ],
            ],
        ],
        // Otros entornos 'entorno2', 'entorno3', etc, con sus respectivas credenciales.
    ],
];

```

> **Nota:** Puedes definir tantos entornos en `sites` como desees. 

## Variables de entorno

Ejemplo para el archivo .env

```env
SHAREPOINT_EMPRESA_A_CLIENT_ID=xxxx-xxxx-xxxx-xxxx
SHAREPOINT_EMPRESA_A_TENANT_ID=yyyy-yyyy-yyyy-yyyy
SHAREPOINT_EMPRESA_A_SITE_PATH=miempresa.sharepoint.com:/sites/MiSitio
SHAREPOINT_EMPRESA_A_DEFAULT_LIBRARY=Documentos
SHAREPOINT_EMPRESA_A_AUTH_METHOD=client_secret
SHAREPOINT_EMPRESA_A_CLIENT_SECRET=mi-secret
SHAREPOINT_EMPRESA_A_PFX_PATH=certificados/mi-certificado.pfx
SHAREPOINT_EMPRESA_A_PFX_PASSPHRASE=mi-password
SHAREPOINT_EMPRESA_A_CERT_PATH=certificados/mi-certificado.crt
SHAREPOINT_EMPRESA_A_KEY_PATH=certificados/private.key
SHAREPOINT_EMPRESA_A_KEY_PASSPHRASE=mi-key-password

SHAREPOINT_EMPRESA_B_CLIENT_ID=xxxx-xxxx-xxxx-xxxx
SHAREPOINT_EMPRESA_B_TENANT_ID=yyyy-yyyy-yyyy-yyyy
SHAREPOINT_EMPRESA_B_SITE_PATH=miempresa.sharepoint.com:/sites/MiSitio
SHAREPOINT_EMPRESA_B_DEFAULT_LIBRARY=Documentos
SHAREPOINT_EMPRESA_B_AUTH_METHOD=client_secret
SHAREPOINT_EMPRESA_B_CLIENT_SECRET=mi-secret
SHAREPOINT_EMPRESA_B_PFX_PATH=certificados/mi-certificado.pfx
SHAREPOINT_EMPRESA_B_PFX_PASSPHRASE=mi-password
SHAREPOINT_EMPRESA_B_CERT_PATH=certificados/mi-certificado.crt
SHAREPOINT_EMPRESA_B_KEY_PATH=certificados/private.key
SHAREPOINT_EMPRESA_B_KEY_PASSPHRASE=mi-key-password

SHAREPOINT_OTRO_ENTORNO_CLIENT_ID=xxxx-xxxx-xxxx-xxxx
SHAREPOINT_OTRO_ENTORNO_TENANT_ID=yyyy-yyyy-yyyy-yyyy
SHAREPOINT_OTRO_ENTORNO_SITE_PATH=miempresa.sharepoint.com:/sites/MiSitio
SHAREPOINT_OTRO_ENTORNO_DEFAULT_LIBRARY=Documentos
SHAREPOINT_OTRO_ENTORNO_AUTH_METHOD=client_secret
SHAREPOINT_OTRO_ENTORNO_CLIENT_SECRET=mi-secret
SHAREPOINT_OTRO_ENTORNO_PFX_PATH=certificados/mi-certificado.pfx
SHAREPOINT_OTRO_ENTORNO_PFX_PASSPHRASE=mi-password
SHAREPOINT_OTRO_ENTORNO_CERT_PATH=certificados/mi-certificado.crt
SHAREPOINT_OTRO_ENTORNO_KEY_PATH=certificados/private.key
SHAREPOINT_OTRO_ENTORNO_KEY_PASSPHRASE=mi-key-password
```

> **Nota:** Puedes definir varios entornos (src/Config/Sharepoint.php) y referenciar qué variables de entorno deben emplear esos entornos. Lo siguiente será incluir esas variables de entorno en tu servidor, y podrás cargar la configuración correspondiente con ConfigManager::getInstance('otro_entorno').


## Requisitos previos

### Dependencias del proyecto

- **PHP**: >= 8.2  
- **Extensiones PHP requeridas**:  
  - ext-curl  
  - ext-json  
- **Dependencias Composer**:  
  - firebase/php-jwt: ^6.11  

### Autenticación mediante CLIENT_SECRET

| Variable | Descripción |
|----------|-------------|
| `CLIENT_ID` | ID de aplicación (cliente) - Identificador único de tu aplicación registrada |
| `TENANT_ID` | ID de inquilino (tenant) - Identificador de tu organización en Azure AD |
| `CLIENT_SECRET` | Valor del secreto de cliente - El valor secreto generado en Azure AD |

### Autenticación mediante certificado PFX

| Variable | Descripción |
|----------|-------------|
| `CLIENT_ID` | ID de aplicación (cliente) - Identificador único de tu aplicación registrada |
| `TENANT_ID` | ID de inquilino (tenant) - Identificador de tu organización en Azure AD |
| `PFX_FILE_PATH` | Certificado en formato PFX/PKCS12 |
| `PFX_FILE_PASSWORD` | Password para poder emplear el certificado |

### Autenticación mediante certificado PEM

| Variable | Descripción |
|----------|-------------|
| `CLIENT_ID` | ID de aplicación (cliente) - Identificador único de tu aplicación registrada |
| `TENANT_ID` | ID de inquilino (tenant) - Identificador de tu organización en Azure AD |
| `PEM_FILE_PATH` | Certificado en formato PEM |
| `PEM_FILE_KEY` | Archivo de clave privada |



### Ejemplos de uso

```
/ejemplos
├── TestDeleteFile.php            
├── TestDeleteFileBySitePath.php    
├── TestFileExistsInFolder.php     
├── TestFolderExists.php          
├── TestListFiles.php              
├── TestListFilesBySitePath.php     
├── TestUploadFile.php            
└── TestUploadFileBySitePath.php        
```

#### Operaciones básicas con archivos

- [TestUploadFile.php](ejemplos/TestUploadFile.php) → Subir un archivo
- [TestListFiles.php](ejemplos/TestListFiles.php) → Listar archivos
- [TestFileExistsInFolder.php](ejemplos/TestFileExistsInFolder.php) → Verificar si existe un archivo
- [TestDeleteFile.php](ejemplos/TestDeleteFile.php) → Eliminar un archivo

#### Operaciones con rutas específicas del sitio

- [TestUploadFileBySitePath.php](ejemplos/TestUploadFileBySitePath.php) → Subir archivo usando ruta del sitio
- [TestListFilesBySitePath.php](ejemplos/TestListFilesBySitePath.php) → Listar archivos por ruta del sitio
- [TestDeleteFileBySitePath.php](ejemplos/TestDeleteFileBySitePath.php) → Eliminar archivo por ruta del sitio

#### Operaciones con carpetas

- [TestFolderExists.php](ejemplos/TestFolderExists.php) → Verificar si existe una carpeta


## Documentación adicional

- [Microsoft Graph API Documentation](https://docs.microsoft.com/en-us/graph/)
- [SharePoint REST API Reference](https://docs.microsoft.com/en-us/sharepoint/dev/sp-add-ins/complete-basic-operations-using-sharepoint-rest-endpoints)


## Contribuciones

Las contribuciones son bienvenidas. Por favor, abre un issue o envía un pull request.


## Licencia

GNU General Public License v3.0