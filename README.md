# Cliente SharePoint PHP

Sencillo **cliente PHP** para interactuar con **SharePoint** Online a **través de Microsoft Graph API**.
**Versión 1.0.1**


## Descripción

Este cliente permite realizar operaciones básicas con SharePoint Online, incluyendo la gestión de archivos y carpetas a través de la API de Microsoft Graph. Soporta tres métodos de autenticación: Client Secret, Certificado PEM y PFX.


## Características

- Autenticación con Microsoft Graph API
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

Incluir el autoloader
```php
require_once __DIR__ . '/vendor/autoload.php';
```

Importar la clase y creamos instancia del cliente para emplear sus métodos
```php
use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi($client_id, $tenant_id, $client_secret);
$files = $client->listFiles($site_id, $drive_id, $folder, 1);
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

Inicializar cliente:
```php
$client = new SharePointClient($client_id, $tenant_id, $client_secret);
$files = $client->listFiles($site_id, $drive_id, $folder, 1);
```


## Requisitos previos

### Dependencias del proyecto

- **PHP**: >= 7.4  
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

### Configuración básica

```php
$client_id = 'xxxx-xxxx-xxxx-xxxx-xxxx';
$tenant_id = 'yyyy-yyyy-yyyy-yyyy-yyyy';

// Opción 1: Client Secret
$client_secret = 'aaa?_bbb_ccc_###.1111';

// Opción 2: Certificado PEM
$pem_path = "ruta/al/certificado.pem";
$pem_private_key_path = "ruta/al/private_key.pem";
$pem_passphrase = ""; // Opcional si la clave tiene passphrase

// Opción 3: Certificado PFX
$pfx_path = 'ruta/al/certificado.pfx';
$pfx_password = 'clave_del_pfx';

$site_path = 'site.sharepoint.com:/sites/mysite';
$drive_name = 'Documentos';
```


## Inicialización del cliente

### Opción 1: Client Secret

En el constructor:

```php
use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi(
    $client_id,
    $tenant_id,
    $client_secret
);
```

O alternativamente:

```php
use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi($client_id, $tenant_id);
$client->setClientSecretAuth($client_secret);
```

### Opción 2: Certificado PEM

En el constructor:

```php
use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi(
    $client_id,
    $tenant_id,
    $pem_path,
    $pem_private_key_path,
    $pem_passphrase // Opcional
);
```

O alternativamente:

```php
use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi($client_id, $tenant_id);
$client->setCertificateAuth(
    $pem_path,
    $pem_private_key_path,
    $pem_passphrase // Opcional
);
```

### Opción 3: Certificado PFX

En el constructor:

```php
use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi(
    $client_id,
    $tenant_id,
    $pfx_path,
    $pfx_password
);
```

O alternativamente:

```php
use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi($client_id, $tenant_id);
$client->setPfxAuth($pfx_path, $pfx_password);
```

### Cambio dinámico de método de autenticación

```php
use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi($client_id, $tenant_id);

// Usar client secret inicialmente
$client->setClientSecretAuth($client_secret);
$files = $client->listFilesBySitePath($site_path, $drive_name);

// Cambiar a certificado PEM
$client->setCertificateAuth($pem_path, $pem_private_key_path);
$files = $client->listFilesBySitePath($site_path, $drive_name);

// Cambiar a certificado PFX
$client->setPfxAuth($pfx_path, $pfx_password);
$files = $client->listFilesBySitePath($site_path, $drive_name);
```

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


## Configuración avanzada

### Variables de entorno

Se recomienda usar variables de entorno:

```php
$client_id = $_ENV['SHAREPOINT_CLIENT_ID'];
$tenant_id = $_ENV['SHAREPOINT_TENANT_ID'];
$client_secret = $_ENV['SHAREPOINT_CLIENT_SECRET'];
$pfx_path = $_ENV['SHAREPOINT_PFX_PATH'];
$pfx_password = $_ENV['SHAREPOINT_PFX_PASSWORD'];
$site_path = $_ENV['SHAREPOINT_SITE_PATH'];
```

Ejemplo para el archivo .env

```env
SHAREPOINT_CLIENT_ID=tu-client-id-real
SHAREPOINT_TENANT_ID=tu-tenant-id-real
SHAREPOINT_CLIENT_SECRET=tu-client-secret-real
SHAREPOINT_PFX_PATH=../../certificate.pfx
SHAREPOINT_PFX_PASSWORD=tu-password-real
SHAREPOINT_SITE_PATH=tu-dominio-real.sharepoint.com:/sites/TuSitioReal
```


## Documentación adicional

- [Microsoft Graph API Documentation](https://docs.microsoft.com/en-us/graph/)
- [SharePoint REST API Reference](https://docs.microsoft.com/en-us/sharepoint/dev/sp-add-ins/complete-basic-operations-using-sharepoint-rest-endpoints)


## Contribuciones

Las contribuciones son bienvenidas. Por favor, abre un issue o envía un pull request.


## Licencia

GNU General Public License v3.0