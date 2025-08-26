<?php

// =============================================================================
// 7. src/Services/DriveService.php
// =============================================================================

namespace SharepointClient\Services;

use SharepointClient\Utils\HttpClient;
use SharepointClient\Exceptions\SharepointException;

/**
 * Servicio para la gestión de bibliotecas (drives) en SharePoint mediante Microsoft Graph API
 * 
 * Esta clase proporciona funcionalidades para obtener información sobre los drives
 * de un sitio de SharePoint Online a través de la API de Microsoft Graph.
 * 
 * @package SharepointClient\Services
 * @author Jose Sánchez G.
 * @version 2.0.0
 */
class DriveService {
    /**
     * Gestor de autenticación para las peticiones a Graph API
     * 
     * @var AuthenticationManager
     */
    private $authManager;
    
    /**
     * Constructor del servicio de drives
     * 
     * @param AuthenticationManager $authManager Instancia del gestor de autenticación
     */
    public function __construct($authManager) {
        $this->authManager = $authManager;
    }
    
    /**
     * Obtiene el ID de una biblioteca (drive) de SharePoint por su nombre
     * 
     * Este método consulta todos los drives de un sitio de SharePoint y devuelve
     * el ID del drive que coincide con el nombre proporcionado.
     * 
     * @param string $site_id ID del sitio de SharePoint
     * @param string $drive_name Nombre del drive/biblioteca a buscar
     * 
     * @return string ID del drive encontrado
     * 
     * @throws SharepointException Si ocurre un error en la petición o no se encuentra el drive
     * 
     * @example
     * $driveId = $driveService->getDriveId('site123', 'Documents');
     */
    public function getDriveId($site_id, $drive_name) {
        // Obtener headers de autenticación
        $headers = $this->authManager->getHeaders();
        
        // Construir la URL de la API de Graph para listar todos los drives del sitio
        $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives";
        
        // Realizar la petición GET
        $response = HttpClient::get($url, $headers);
        
        // Verificar que la respuesta fue exitosa (HTTP 200)
        if ($response['http_code'] != 200) {
            throw new SharepointException(
                "Error al obtener los drives: HTTP {$response['http_code']} - {$response['body']}"
            );
        }
        
        // Decodificar la respuesta JSON
        $data = json_decode($response['body'], true);
        $drives = $data['value'];
        $drive_id = null;
        
        // Recorrer los drives para encontrar el que coincida con el nombre
        foreach ($drives as $drive) {
            if ($drive['name'] === $drive_name) {
                $drive_id = $drive['id'];
                break;
            }
        }
        
        // Si no se encuentra el drive, lanzar excepción
        if (!$drive_id) {
            throw new SharepointException("No se encontró la biblioteca '$drive_name'.");
        }
        
        // Retornar el ID del drive encontrado
        return $drive_id;
    }
}
