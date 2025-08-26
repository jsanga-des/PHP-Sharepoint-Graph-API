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
}