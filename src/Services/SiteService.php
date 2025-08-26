<?php

// =============================================================================
// 6. src/Services/SiteService.php
// =============================================================================

namespace SharepointClient\Services;

use SharepointClient\Utils\HttpClient;
use SharepointClient\Exceptions\SharepointException;

/**
 * Servicio para la gestión de sitios (sites) en SharePoint mediante Microsoft Graph API
 * 
 * Esta clase proporciona funcionalidades para obtener información de un sitio
 * de SharePoint Online, incluyendo la obtención del ID del sitio a partir de su ruta.
 * 
 * @package SharepointClient\Services
 * @author Jose Sánchez G.
 * @version 2.0.0
 */
class SiteService {
    /**
     * Gestor de autenticación para las peticiones a Graph API
     * 
     * @var AuthenticationManager
     */
    private $authManager;
    
    /**
     * Constructor del servicio de sitios
     * 
     * @param AuthenticationManager $authManager Instancia del gestor de autenticación
     */
    public function __construct($authManager) {
        $this->authManager = $authManager;
    }
    
    /**
     * Obtiene el ID de un sitio de SharePoint por su ruta
     * 
     * Este método consulta un sitio de SharePoint utilizando la ruta completa
     * (por ejemplo: 'contoso.sharepoint.com/sites/misite') y devuelve su ID único.
     * 
     * @param string $site_path Ruta completa del sitio de SharePoint
     * 
     * @return string ID del sitio
     * 
     * @throws SharepointException Si ocurre un error en la petición o no se encuentra el sitio
     * 
     * @example
     * $siteId = $siteService->getSiteId('contoso.sharepoint.com/sites/misite');
     */
    public function getSiteId($site_path) {
        // Obtener headers de autenticación
        $headers = $this->authManager->getHeaders();
        
        // Construir la URL de la API de Graph para obtener la información del sitio
        $url = "https://graph.microsoft.com/v1.0/sites/" . urlencode($site_path);
        
        // Realizar la petición GET
        $response = HttpClient::get($url, $headers);
        
        // Verificar si la respuesta fue exitosa (HTTP 200)
        if ($response['http_code'] == 200) {
            // Decodificar la respuesta JSON
            $data = json_decode($response['body'], true);
            
            // Retornar el ID del sitio
            return $data['id'];
        } else {
            // Lanzar excepción si ocurre un error
            throw new SharepointException(
                "Error al obtener el SITE_ID: HTTP {$response['http_code']} - {$response['body']}"
            );
        }
    }
}
