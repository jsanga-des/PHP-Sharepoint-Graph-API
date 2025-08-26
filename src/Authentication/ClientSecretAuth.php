<?php

// =============================================================================
// src/Authentication/ClientSecretAuth.php
// =============================================================================

namespace SharepointClient\Authentication;

use SharepointClient\Utils\HttpClient;
use SharepointClient\Exceptions\SharepointException;

/**
 * Autenticación de tipo Client Secret para Microsoft Graph API
 * 
 * Esta clase permite obtener un token de acceso válido utilizando las credenciales
 * de tipo Client Secret (aplicación registrada en Azure AD) para acceder a SharePoint.
 * 
 * @package SharepointClient\Authentication
 * @author Jose Sánchez
 * @version 2.0.0
 */
class ClientSecretAuth {
    /**
     * ID de la aplicación registrada en Azure AD
     * @var string
     */
    private $client_id;

    /**
     * ID del tenant de Azure AD
     * @var string
     */
    private $tenant_id;

    /**
     * Secreto de la aplicación
     * @var string
     */
    private $client_secret;

    /**
     * Token de acceso actual en caché
     * @var string|null
     */
    private ?string $token = null;

    /**
     * Fecha de expiración del token
     * @var int|null Timestamp UNIX del vencimiento
     */
    private ?int $token_expiration = null;

    /**
     * Constructor de ClientSecretAuth
     * 
     * @param string $client_id ID de la aplicación
     * @param string $tenant_id ID del tenant
     * @param string $client_secret Secreto de la aplicación
     */
    public function __construct($client_id, $tenant_id, $client_secret) {
        $this->client_id = $client_id;
        $this->tenant_id = $tenant_id;
        $this->client_secret = $client_secret;
    }

    /**
     * Obtiene un token de acceso válido
     * 
     * Este método devuelve un token de acceso para Microsoft Graph API. Si ya existe
     * un token válido en caché, se reutiliza; si no, se solicita uno nuevo usando
     * el flujo de Client Credentials.
     * 
     * @return string Token de acceso
     * @throws SharepointException Si el Client Secret no está configurado o la petición falla
     * 
     * @example
     * $token = $clientSecretAuth->getAccessToken();
     */
    public function getAccessToken(): string
    {
        // Si ya existe un token válido en caché y no ha expirado, lo retornamos
        if ($this->token !== null && $this->token_expiration !== null && time() < $this->token_expiration - 30) {
            return $this->token;
        }

        // Validar que el Client Secret esté configurado
        if (empty($this->client_secret)) {
            throw new SharepointException("Client Secret no configurado");
        }

        // Construir la URL de autenticación de Azure AD para el tenant
        $authority = "https://login.microsoftonline.com/" . $this->tenant_id;
        $scope = "https://graph.microsoft.com/.default";
        $url = $authority . "/oauth2/v2.0/token";

        // Datos para la petición POST usando el flujo Client Credentials
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => $scope,
            'grant_type' => 'client_credentials'
        ];

        // Headers de la petición
        $headers = ['Content-Type: application/x-www-form-urlencoded'];

        // Realizar la petición POST para obtener el token
        $response = HttpClient::post($url, http_build_query($data), $headers);

        // Verificar respuesta HTTP
        if ($response['http_code'] === 200) {
            $body_response = json_decode($response['body'], true);
            $this->token = $body_response['access_token'] ?? null;

            // Decodificar el token JWT para obtener la fecha de expiración
            $decoded = json_decode(
                base64_decode(
                    str_replace('_', '/', str_replace('-', '+', explode('.', $this->token)[1]))
                ), 
                true
            );
            $this->token_expiration = $decoded['exp'] ?? (time() + 3600);

            return $this->token;
        } else {
            // Lanzar excepción si la petición falla
            throw new SharepointException(
                "Error al obtener token con Client Secret: HTTP {$response['http_code']} - {$response['body']}"
            );
        }
    }
}
