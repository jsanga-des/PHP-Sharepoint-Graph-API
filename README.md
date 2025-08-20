# Cliente SharePoint PHP

Sencillo cliente PHP para interactuar con SharePoint Online a través de Microsoft Graph API.

## Descripción

Este cliente permite realizar operaciones básicas con SharePoint Online, incluyendo la gestión de archivos y carpetas a través de la API de Microsoft Graph.

## Características

- Autenticación con Microsoft Graph API
- Obtención de IDs de sitio y biblioteca
- Listado de archivos y carpetas
- Subida de archivos
- Eliminación de archivos
- Verificación de existencia de archivos y carpetas

## Instalación con composer

1. Incluir en el proyecto
```bash
composer require tu-usuario/php-sharepoint-graph-api:dev-main
```

2. Uso
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use SharePointClient\SharePointGraphApi;

$client = new SharePointGraphApi($client_id, $tenant_id, $client_secret);
$archivos = $client->listFilesBySitePath($site_path, 'Documentos');
```

## Instalación Manual

1. Clonar o descargar el repositorio:
```bash
git clone https://github.com/tu-usuario/php-sharepoint-graph-api.git

2. Uso
```php
<?php
require_once 'path/to/SharePointClient.php';

// Inicializar el cliente SharePoint con las credenciales de Azure AD
$client = new SharePointClient($client_id, $tenant_id, $client_secret);
```

## Configuración

### Requisitos previos

Antes de usar este cliente, necesitas registrar una aplicación en Azure AD y obtener las siguientes credenciales:

| Variable | Descripción |
|----------|-------------|
| `CLIENT_ID` | ID de aplicación (cliente) - Identificador único de tu aplicación registrada |
| `TENANT_ID` | ID de inquilino (tenant) - Identificador de tu organización en Azure AD |
| `CLIENT_SECRET` | Valor del secreto de cliente - El valor secreto generado en Azure AD |

### Configuración básica

```php
<?php
$client_id = 'tu-client-id-real';
$tenant_id = 'tu-tenant-id-real';
$client_secret = 'tu-client-secret-real';
$site_path = 'tu-dominio-real.sharepoint.com:/sites/TuSitioReal';
```

## Ejemplos de uso

### Operaciones básicas con archivos

```php
// Subir un archivo
include 'TestUploadFile.php';

// Listar archivos
include 'TestListFiles.php';

// Verificar si existe un archivo
include 'TestFileExistsInFolder.php';

// Eliminar un archivo
include 'TestDeleteFile.php';
```

### Operaciones con rutas específicas del sitio

```php
// Subir archivo usando ruta del sitio
include 'uploadFileBySitePath.php';

// Listar archivos por ruta del sitio
include 'TestListFilesBySitePath.php';

// Eliminar archivo por ruta del sitio
include 'TestDeleteFileBySitePath.php';
```

### Operaciones con carpetas

```php
// Verificar si existe una carpeta
include 'TestFolderExists.php';
```

## Estructura de archivos de ejemplo

```
/ejemplos
├── TestDeleteFile.php              # Eliminar archivo
├── TestDeleteFileBySitePath.php    # Eliminar archivo por ruta del sitio
├── TestFileExistsInFolder.php      # Verificar existencia de archivo
├── TestFolderExists.php            # Verificar existencia de carpeta
├── TestListFiles.php               # Listar archivos
├── TestListFilesBySitePath.php     # Listar archivos por ruta del sitio
├── TestUploadFile.php              # Subir archivo
└── uploadFileBySitePath.php        # Subir archivo por ruta del sitio
```

## Configuración avanzada

### Variables de entorno

Se recomienda usar variables de entorno:

```php
$client_id = $_ENV['SHAREPOINT_CLIENT_ID'];
$tenant_id = $_ENV['SHAREPOINT_TENANT_ID'];
$client_secret = $_ENV['SHAREPOINT_CLIENT_SECRET'];
$site_path = $_ENV['SHAREPOINT_SITE_PATH'];
```

### Archivo .env

```env
SHAREPOINT_CLIENT_ID=tu-client-id-real
SHAREPOINT_TENANT_ID=tu-tenant-id-real
SHAREPOINT_CLIENT_SECRET=tu-client-secret-real
SHAREPOINT_SITE_PATH=tu-dominio-real.sharepoint.com:/sites/TuSitioReal
```

## Documentación adicional

- [Microsoft Graph API Documentation](https://docs.microsoft.com/en-us/graph/)
- [SharePoint REST API Reference](https://docs.microsoft.com/en-us/sharepoint/dev/sp-add-ins/complete-basic-operations-using-sharepoint-rest-endpoints)

## Contribuciones

Las contribuciones son bienvenidas. Por favor, abre un issue o envía un pull request.

## Licencia

GNU General Public License v3.0