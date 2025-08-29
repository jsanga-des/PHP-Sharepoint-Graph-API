<?php

// =============================================================================
// 8. src/Services/FileService.php
// =============================================================================

namespace SharepointClient\Services;

use SharepointClient\Utils\HttpClient;
use SharepointClient\Authentication\AuthenticationManager;
use SharepointClient\Utils\Helpers; 

/**
 * Servicio para la gestión de archivos en SharePoint mediante Microsoft Graph API
 * 
 * Esta clase proporciona funcionalidades para subir, eliminar, verificar existencia
 * y listar archivos en SharePoint Online a través de la API de Microsoft Graph.
 * 
 * @package SharepointClient\Services
 * @author Jose Sánchez G.
 * @version 2.0.0
 */
class FileService {
    /**
     * Gestor de autenticación para las peticiones a Graph API
     * 
     * @var AuthenticationManager
     */
    private $authManager;
    
    /**
     * Constructor del servicio de archivos
     * 
     * @param AuthenticationManager $authManager Instancia del gestor de autenticación
     */
    public function __construct(AuthenticationManager $authManager) {
        $this->authManager = $authManager;
    }
    
    /**
     * Sube un archivo local a SharePoint
     * 
     * Este método permite subir un archivo desde el sistema local a una ubicación
     * específica en SharePoint. Si no se especifica un nombre remoto, usa el nombre
     * del archivo local.
     * 
     * @param string $siteId ID del sitio de SharePoint
     * @param string $driveId ID del drive donde subir el archivo
     * @param string $localFilePath Ruta completa del archivo local a subir
     * @param string $remoteFilePath Ruta de la carpeta remota (opcional, vacío para raíz)
     * @param string|null $remoteFileName Nombre del archivo en SharePoint (opcional, usa el nombre local si es null)
     * 
     * @return bool True si la subida fue exitosa, false en caso de error
     * 
     * @throws \Exception Si ocurre un error durante el proceso
     * 
     * @example
     * $success = $fileService->uploadFile(
     *     'site123', 
     *     'drive456', 
     *     '/local/path/document.pdf',
     *     'Documents/Reports',
     *     'monthly_report.pdf'
     * );
     */
    public function uploadFile($siteId, $driveId, $localFilePath, $remoteFilePath = '', $remoteFileName = null) {
        try {
            // Verificar que el archivo local existe y es accesible
            Helpers::verifyFileReadable($localFilePath);
                    
            // Si no se especifica nombre remoto, usar el nombre del archivo local
            if ($remoteFileName === null) {
                $remoteFileName = basename($localFilePath);
            }
            
            // Leer el contenido del archivo local
            $fileContent = file_get_contents($localFilePath);

            // Construir la ruta relativa completa en SharePoint
            $relativePath = Helpers::buildRelativeRemotePath($remoteFilePath, $remoteFileName);
            
            // Construir la URL de la API de Graph para subir el archivo
            $url = "https://graph.microsoft.com/v1.0/sites/$siteId/drives/$driveId/root:/{$relativePath}:/content";
            
            // Obtener headers de autenticación con content-type específico para archivos
            $headers = $this->authManager->getHeaders("application/octet-stream");
            
            // Realizar la petición PUT para subir el archivo
            $response = HttpClient::put($url, $fileContent, $headers);
            
            // Verificar si la respuesta indica éxito (200 OK o 201 Created)
            if ($response['http_code'] != 200 && $response['http_code'] != 201) {
                Helpers::logError("Error Graph upload: HTTP {$response['http_code']} - {$response['body']}");
                Helpers::logError("URL attempted: $url");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Helpers::logError("Excepción al subir el archivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un archivo de SharePoint
     * 
     * Este método elimina un archivo específico de SharePoint utilizando
     * la ruta y nombre del archivo proporcionados.
     * 
     * @param string $siteId ID del sitio de SharePoint
     * @param string $driveId ID del drive que contiene el archivo
     * @param string $remoteFilePath Ruta de la carpeta donde está el archivo
     * @param string $remoteFileName Nombre del archivo a eliminar
     * 
     * @return bool True si la eliminación fue exitosa, false en caso de error
     * 
     * @throws \Exception Si ocurre un error durante el proceso
     * 
     * @example
     * $deleted = $fileService->deleteFile(
     *     'site123', 
     *     'drive456', 
     *     'Documents/Reports', 
     *     'old_report.pdf'
     * );
     */
    public function deleteFile($siteId, $driveId, $remoteFilePath, $remoteFileName) {
        try {
            // Construir la ruta relativa completa del archivo
            $relativePath = Helpers::buildRelativeRemotePath($remoteFilePath, $remoteFileName);
            
            // Limpiar la ruta para uso en URL
            $cleanedPath = Helpers::cleanUrlPath($relativePath);

            // Construir la URL de la API de Graph para eliminar el archivo
            $url = "https://graph.microsoft.com/v1.0/sites/$siteId/drives/$driveId/root:/$cleanedPath";
            
            // Obtener headers de autenticación
            $headers = $this->authManager->getHeaders();
            
            // Realizar la petición DELETE
            $response = HttpClient::delete($url, $headers);
            
            // HTTP 204 No Content indica eliminación exitosa
            return $response['http_code'] == 204;
        } catch (\Exception $e) {
            Helpers::logError("Excepción al eliminar el archivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si una carpeta existe en SharePoint
     * 
     * Este método comprueba la existencia de una carpeta específica en
     * SharePoint utilizando la API de Graph.
     * 
     * @param string $siteId ID del sitio de SharePoint
     * @param string $driveId ID del drive donde buscar la carpeta
     * @param string $remoteFolderPath Ruta de la carpeta a verificar
     * 
     * @return bool True si la carpeta existe, false si no existe o hay error
     * 
     * @throws \Exception Si ocurre un error durante el proceso
     * 
     * @example
     * $exists = $fileService->folderExists('site123', 'drive456', 'Documents/Reports');
     */
    public function folderExists($siteId, $driveId, $remoteFolderPath) {
        try {
            // Limpiar la ruta eliminando barras al inicio y final
            $folderPath = trim($remoteFolderPath, '/');
            
            // Si la ruta está vacía, retornar false (no es una carpeta válida)
            if (empty($folderPath)) {
                return false;
            }
            
            // Limpiar la ruta para uso en URL
            $cleanedPath = Helpers::cleanUrlPath($folderPath);

            // Construir la URL de la API de Graph para verificar la carpeta
            $url = "https://graph.microsoft.com/v1.0/sites/$siteId/drives/$driveId/root:/$cleanedPath";
            $headers = $this->authManager->getHeaders();
            
            // Realizar la petición GET para obtener información del elemento
            $response = HttpClient::get($url, $headers);
            
            // Si la respuesta es exitosa, verificar que es una carpeta
            if ($response['http_code'] == 200) {
                $data = json_decode($response['body'], true);
                // Verificar que el elemento tiene la propiedad 'folder'
                return isset($data['folder']);
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Verifica si un archivo específico existe en una carpeta de SharePoint
     * 
     * Este método comprueba la existencia de un archivo específico dentro
     * de una carpeta determinada en SharePoint.
     * 
     * @param string $siteId ID del sitio de SharePoint
     * @param string $driveId ID del drive donde buscar el archivo
     * @param string $folderPath Ruta de la carpeta donde buscar
     * @param string $fileName Nombre del archivo a verificar
     * 
     * @return bool True si el archivo existe, false si no existe o hay error
     * 
     * @throws \Exception Si ocurre un error durante el proceso
     * 
     * @example
     * $exists = $fileService->fileExistsInFolder(
     *     'site123', 
     *     'drive456', 
     *     'Documents/Reports', 
     *     'report.pdf'
     * );
     */
    public function fileExistsInFolder($siteId, $driveId, $folderPath, $fileName) {
        try {
            // Construir la ruta completa del archivo
            $fullPath = Helpers::buildRelativeRemotePath($folderPath, $fileName);
            
            // Limpiar la ruta para uso en URL
            $cleanedPath = Helpers::cleanUrlPath($fullPath);
            
            // Construir la URL de la API de Graph para verificar el archivo
            $url = "https://graph.microsoft.com/v1.0/sites/$siteId/drives/$driveId/root:/$cleanedPath";
            $headers = $this->authManager->getHeaders();
            
            // Realizar la petición GET para obtener información del elemento
            $response = HttpClient::get($url, $headers);
            
            // Si la respuesta es exitosa, verificar que es un archivo
            if ($response['http_code'] == 200) {
                $data = json_decode($response['body'], true);
                // Verificar que el elemento tiene la propiedad 'file'
                return isset($data['file']);
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Lista archivos y carpetas de SharePoint de forma recursiva
     * 
     * Este método obtiene una lista de todos los elementos (archivos y carpetas)
     * en una ubicación específica de SharePoint, con soporte para navegación recursiva
     * hasta una profundidad máxima especificada.
     * 
     * @param string $siteId ID del sitio de SharePoint
     * @param string $driveId ID del drive a explorar
     * @param string $remoteFolderDirectory Directorio remoto a listar (vacío para raíz)
     * @param int $maxDepth Profundidad máxima de recursión (1 = solo nivel actual)
     * @param string $currentPath Ruta actual para construcción recursiva (uso interno)
     * @param int $indent Nivel de indentación actual (uso interno para recursión)
     * 
     * @return array Array de elementos encontrados, cada uno con metadata de Graph API
     * 
     * @throws \Exception Si ocurre un error durante el proceso
     * 
     * @example
     * // Listar solo el nivel raíz
     * $files = $fileService->listFiles('site123', 'drive456');
     * 
     * // Listar recursivamente hasta 3 niveles de profundidad
     * $files = $fileService->listFiles('site123', 'drive456', 'Documents', 3);
     * 
     * @note El array retornado contiene objetos con estructura de Graph API:
     *       - 'name': nombre del archivo/carpeta
     *       - 'file': presente si es archivo
     *       - 'folder': presente si es carpeta
     *       - Otros metadatos de Graph API
     */
    public function listFiles($siteId, $driveId, $remoteFolderDirectory = "", $maxDepth = 1, $currentPath = "", $indent = 0) {
        // Determinar la ruta de Graph API según el directorio especificado
        if (empty($remoteFolderDirectory) || $remoteFolderDirectory === "root") {
            // Para raíz, usar 'root' directamente
            $graphPath = "root";
        } else {
            // Para carpetas específicas, construir ruta con formato Graph API
            $trimmedPath = trim($remoteFolderDirectory, '/');
            $cleanedPath = Helpers::cleanUrlPath($trimmedPath);
            
            $graphPath = "root:/{$cleanedPath}:";
        }
        
        // Construir URL para obtener los hijos del elemento (archivos y subcarpetas)
        $url = "https://graph.microsoft.com/v1.0/sites/$siteId/drives/$driveId/$graphPath/children";
        
        // Obtener headers de autenticación
        $headers = $this->authManager->getHeaders();
        
        // Realizar petición GET para obtener la lista de elementos
        $response = HttpClient::get($url, $headers);
        
        // Si la petición falla, retornar array vacío
        if ($response['http_code'] != 200) {
            return [];
        }
        
        // Decodificar la respuesta JSON
        $data = json_decode($response['body'], true);
        
        // Verificar que la respuesta contiene el array de elementos
        if (!isset($data['value'])) {
            return [];
        }
        
        // Obtener los elementos del directorio actual
        $items = $data['value'];
        $allFiles = [];
        
        // Procesar cada elemento encontrado
        foreach ($items as $item) {
            // Construir la ruta completa del elemento actual
            $fullPath = trim(($currentPath ? $currentPath . '/' : '') . $item['name'], '/');
            
            // Si es una carpeta y no hemos alcanzado la profundidad máxima,
            // realizar llamada recursiva para obtener su contenido
            if (isset($item['folder']) && $indent < $maxDepth) {
                $subFiles = $this->listFiles(
                    $siteId, 
                    $driveId, 
                    $fullPath, 
                    $maxDepth, 
                    $fullPath, 
                    $indent + 1
                );
                // Combinar los archivos de la subcarpeta con el array principal
                $allFiles = array_merge($allFiles, $subFiles);
            }
            
            // Agregar el elemento actual al array de resultados
            $allFiles[] = $item;
        }
        
        return $allFiles;
    }

    /**
     * Crea una nueva carpeta en SharePoint con creación recursiva de estructura
     * 
     * Este método crea una nueva carpeta en una ubicación específica de SharePoint.
     * Si las carpetas padre no existen, las crea automáticamente de forma recursiva,
     * construyendo toda la estructura de directorios necesaria desde la raíz.
     * 
     * @param string $siteId ID del sitio de SharePoint (obligatorio)
     * @param string $driveId ID del drive donde crear la carpeta (obligatorio)
     * @param string $parentFolderPath Ruta de la carpeta padre (opcional, vacío para raíz)
     * @param string|null $folderName Nombre de la nueva carpeta a crear (opcional, si es null se extrae del parentFolderPath)
     * 
     * @return bool True si la creación fue exitosa, false en caso de error
     * 
     * @throws \Exception Si ocurre un error durante el proceso
     * 
     * @example
     * // Crear carpeta en la raíz con nombre específico
     * $created = $fileService->createFolder('site123', 'drive456', '', 'Nueva Carpeta');
     * $created = $fileService->createFolder('site123', 'drive456', 'root', 'Nueva Carpeta');
     * 
     * // Crear carpeta dentro de Documents con nombre específico
     * $created = $fileService->createFolder('site123', 'drive456', 'Documents', 'Reports');
     * 
     * // Crear estructura completa anidada (crea Documents, Reports y 2024 si no existen)
     * $created = $fileService->createFolder('site123', 'drive456', 'Documents/Reports', '2024');
     * 
     * // Crear carpeta usando el último segmento del path como nombre
     * $created = $fileService->createFolder('site123', 'drive456', 'Documents/Reports/2024');
     * // Esto creará toda la estructura Documents/Reports/2024 si no existe
     * 
     * // Crear estructura profunda automáticamente
     * $created = $fileService->createFolder('site123', 'drive456', 'Projects/WebApp/Frontend/Components');
     * // Crea toda la estructura: Projects -> WebApp -> Frontend -> Components
     */
    public function createFolder($siteId, $driveId, $parentFolderPath = '', $folderName = null) {
        try {
            // Si no se proporciona folderName, extraerlo del parentFolderPath
            if ($folderName === null) {
                if (empty($parentFolderPath) || $parentFolderPath === "root") {
                    Helpers::logError("El nombre de la carpeta es requerido cuando se crea en la raíz");
                    return false;
                }
                
                // Extraer el último segmento de la ruta como nombre de carpeta
                $trimmedPath = trim($parentFolderPath, '/');
                $pathSegments = explode('/', $trimmedPath);
                $folderName = end($pathSegments);
                
                // Remover el último segmento del parentFolderPath para obtener la ruta padre real
                array_pop($pathSegments);
                $parentFolderPath = empty($pathSegments) ? '' : implode('/', $pathSegments);
            }
            
            // Validar que el nombre de la carpeta no esté vacío
            if (empty(trim($folderName))) {
                Helpers::logError("El nombre de la carpeta no puede estar vacío");
                return false;
            }
            
            // Si hay una ruta padre, asegurar que existe (crear recursivamente si es necesario)
            if (!empty($parentFolderPath) && $parentFolderPath !== "root") {
                if (!$this->ensureFolderStructureExists($siteId, $driveId, $parentFolderPath)) {
                    Helpers::logError("No se pudo crear o verificar la estructura de carpetas padre: '{$parentFolderPath}'");
                    return false;
                }
            }
            
            // Determinar la URL base según si se especifica carpeta padre
            if (empty($parentFolderPath) || $parentFolderPath === "root") {
                // Para raíz, usar 'root' directamente
                $graphPath = "root";
            } else {
                // Para carpetas específicas, construir ruta con formato Graph API
                $trimmedPath = trim($parentFolderPath, '/');
                $cleanedPath = Helpers::cleanUrlPath($trimmedPath);
                $graphPath = "root:/{$cleanedPath}:";
            }
            
            // Construir la URL de la API de Graph para crear la carpeta
            $url = "https://graph.microsoft.com/v1.0/sites/$siteId/drives/$driveId/$graphPath/children";
            
            // Preparar el payload JSON para crear la carpeta
            $payload = [
                'name' => $folderName,
                'folder' => new \stdClass(), // Objeto vacío para indicar que es una carpeta
                '@microsoft.graph.conflictBehavior' => 'fail' // Fallar si ya existe
            ];
            
            // Obtener headers de autenticación con content-type para JSON
            $headers = $this->authManager->getHeaders("application/json");
            
            // Realizar la petición POST para crear la carpeta
            $response = HttpClient::post($url, json_encode($payload), $headers);
            
            // Verificar si la respuesta indica éxito (201 Created)
            if ($response['http_code'] == 201) {
                // $responseData = json_decode($response['body'], true);
                return true;
            }
            
            // HTTP 409 Conflict indica que la carpeta ya existe
            if ($response['http_code'] == 409) {
                Helpers::logError("La carpeta '{$folderName}' ya existe en '{$parentFolderPath}'");
                return false;
            }
            
            // Otros códigos de error
            if ($response['http_code'] != 201) {
                Helpers::logError("Error Graph createFolder: HTTP {$response['http_code']} - {$response['body']}");
                Helpers::logError("URL attempted: $url");
                return false;
            }
            
        } catch (\Exception $e) {
            Helpers::logError("Excepción al crear la carpeta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Asegura que toda la estructura de carpetas padre existe, creándola recursivamente si es necesario
     * 
     * @param string $siteId ID del sitio de SharePoint
     * @param string $driveId ID del drive
     * @param string $folderPath Ruta completa de carpetas a verificar/crear
     * 
     * @return bool True si la estructura existe o se creó exitosamente
     */
    private function ensureFolderStructureExists($siteId, $driveId, $folderPath) {
        try {
            $trimmedPath = trim($folderPath, '/');
            if (empty($trimmedPath)) {
                return true; // Ruta vacía significa raíz, que siempre existe
            }
            
            $pathSegments = explode('/', $trimmedPath);
            $currentPath = '';
            
            // Crear cada nivel de carpeta si no existe
            foreach ($pathSegments as $segment) {
                $parentPath = $currentPath;
                $currentPath = empty($currentPath) ? $segment : $currentPath . '/' . $segment;
                
                // Verificar si la carpeta actual existe (usando tu función existente)
                if (!$this->folderExists($siteId, $driveId, $currentPath)) {
                    // No existe, crearla
                    if (!$this->createSingleFolder($siteId, $driveId, $parentPath, $segment)) {
                        return false;
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Helpers::logError("Error al asegurar estructura de carpetas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea una sola carpeta sin verificar dependencias
     * 
     * @param string $siteId ID del sitio de SharePoint
     * @param string $driveId ID del drive
     * @param string $parentPath Ruta de la carpeta padre
     * @param string $folderName Nombre de la carpeta a crear
     * 
     * @return bool True si la creación fue exitosa
     */
    private function createSingleFolder($siteId, $driveId, $parentPath, $folderName) {
        try {
            // Determinar la URL base según si se especifica carpeta padre
            if (empty($parentPath)) {
                $graphPath = "root";
            } else {
                $trimmedPath = trim($parentPath, '/');
                $cleanedPath = Helpers::cleanUrlPath($trimmedPath);
                $graphPath = "root:/{$cleanedPath}:";
            }
            
            $url = "https://graph.microsoft.com/v1.0/sites/$siteId/drives/$driveId/$graphPath/children";
            
            $payload = [
                'name' => $folderName,
                'folder' => new \stdClass(),
                '@microsoft.graph.conflictBehavior' => 'rename' // Renombrar si existe para evitar conflictos durante creación recursiva
            ];
            
            $headers = $this->authManager->getHeaders("application/json");
            $response = HttpClient::post($url, json_encode($payload), $headers);
            
            return $response['http_code'] == 201;
        } catch (\Exception $e) {
            Helpers::logError("Error al crear carpeta individual '{$folderName}': " . $e->getMessage());
            return false;
        }
    }
    
}