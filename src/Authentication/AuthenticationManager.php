<?php

// =============================================================================
// src/Authentication/AuthenticationManager.php
// =============================================================================

namespace SharepointClient\Authentication;

use SharepointClient\Config\ConfigManager;
use SharepointClient\Exceptions\SharepointException;

/**
 * Gestor de autenticación para SharePoint mediante Microsoft Graph API
 * 
 * Esta clase centraliza la autenticación utilizando diferentes métodos:
 * - Client Secret
 * - Certificado CRT
 * - Certificado PFX
 * 
 * Permite obtener tokens de acceso válidos y construir headers HTTP
 * para las peticiones a Graph API.
 * 
 * @package SharepointClient\Authentication
 * @author Jose Sánchez G.
 * @version 2.0.0
 */
class AuthenticationManager {

    /**
     * Configuración general de la aplicación
     * 
     * @var ConfigManager
     */
    private $config;

    /**
     * ID de la aplicación registrada en Azure AD
     * 
     * @var string
     */
    private $clientId;

    /**
     * ID del tenant de Azure AD
     * 
     * @var string
     */
    private $tenantId;

    /**
     * Instancia del método de autenticación seleccionado
     * 
     * @var object|null
     */
    private $authentication;

    /**
     * Token de acceso actual
     * 
     * @var string|null
     */
    private $accessToken;

    /**
     * Constantes para los tipos de autenticación soportados
     */
    const AUTH_CLIENT_SECRET = 'client_secret';
    const AUTH_CERTIFICATE_CRT = 'certificate_crt';
    const AUTH_CERTIFICATE_PFX = 'certificate_pfx';

    /**
     * Constructor del AuthenticationManager
     * 
     * Inicializa el método de autenticación a partir de la configuración.
     * 
     * @param ConfigManager $config Instancia de la configuración de la aplicación
     */
    public function __construct(ConfigManager $config) {
        $this->config = $config;
        $this->clientId = $this->config->get('client_id');
        $this->tenantId = $this->config->get('tenant_id');
        $this->authentication = null;
        $this->initializeFromConfig();
    }

    /**
     * Inicializa el método de autenticación basado en la configuración
     * 
     * @throws SharepointException Si la configuración es inválida o el método no es compatible
     */
    private function initializeFromConfig() {
        $authMethod = $this->config->getAuthMethod();
        
        switch ($authMethod) {
            case self::AUTH_CLIENT_SECRET:
                $this->setClientSecretAuth($this->config->get('secret'));
                break;
            case self::AUTH_CERTIFICATE_CRT:
                $this->setCrtAuth(
                    $this->config->get('cert_path'),
                    $this->config->get('key_path'),
                    $this->config->get('passphrase')
                );
                break;
            case self::AUTH_CERTIFICATE_PFX:
                $this->setPfxAuth(
                    $this->config->get('path'),
                    $this->config->get('passphrase')
                );
                break;
            default:
                throw SharepointException::configurationError(
                    "Método de autenticación no compatible con AuthenticationManager: {$authMethod}"
                );
        }
    }

    /**
     * Configura autenticación mediante Client Secret
     * 
     * @param string $clientSecret Secreto de la aplicación
     */
    public function setClientSecretAuth($clientSecret) {
        $this->authentication = new ClientSecretAuth($this->clientId, $this->tenantId, $clientSecret);
    }

    /**
     * Configura autenticación mediante certificado CRT
     * 
     * @param string $certPath Ruta al archivo .crt
     * @param string $keyPath Ruta al archivo de clave privada
     * @param string|null $passphrase Contraseña opcional de la clave
     */
    public function setCrtAuth($certPath, $keyPath, $passphrase = null) {
        $this->authentication = new CertificateCrtAuth(
            $this->clientId,
            $this->tenantId,
            $certPath,
            $keyPath,
            $passphrase
        );
    }

    /**
     * Configura autenticación mediante certificado PFX
     * 
     * @param string $pfxPath Ruta al archivo .pfx
     * @param string $passphrase Contraseña del archivo
     */
    public function setPfxAuth($pfxPath, $passphrase) {
        $this->authentication = new CertificatePfxAuth($this->clientId, $this->tenantId, $pfxPath, $passphrase);
    }

    /**
     * Fuerza la reinicialización del método de autenticación
     * 
     * Útil si la configuración cambia en tiempo de ejecución.
     */
    public function reinitialize() {
        $this->authentication = null;
        $this->accessToken = null;
        $this->initializeFromConfig();
    }

    /**
     * Obtiene información del método de autenticación actual
     * 
     * @return array Array con 'method' (tipo) y 'class' (nombre de clase)
     */
    public function getCurrentAuthInfo() {
        if (!$this->authentication) {
            return ['method' => 'none', 'class' => null];
        }

        $className = get_class($this->authentication);
        $method = 'unknown';

        if (strpos($className, 'ClientSecretAuth') !== false) {
            $method = self::AUTH_CLIENT_SECRET;
        } elseif (strpos($className, 'CertificateCrtAuth') !== false) {
            $method = self::AUTH_CERTIFICATE_CRT;
        } elseif (strpos($className, 'CertificatePfxAuth') !== false) {
            $method = self::AUTH_CERTIFICATE_PFX;
        }

        return [
            'method' => $method,
            'class' => $className
        ];
    }

    /**
     * Obtiene un token de acceso válido
     * 
     * Si no existe un token actual, lo solicita al método de autenticación configurado.
     * 
     * @return string Token de acceso
     * @throws SharepointException Si no se ha configurado ningún método de autenticación
     */
    public function getAccessToken() {
        if (!$this->authentication) {
            throw new SharepointException("No se ha configurado ningún método de autenticación");
        }

        if (!$this->accessToken) {
            $this->accessToken = $this->authentication->getAccessToken();
        }

        return $this->accessToken;
    }

    /**
     * Construye los headers HTTP para una petición a Graph API
     * 
     * @param string $contentType Tipo de contenido (por defecto: application/json)
     * @return array Array de headers con Authorization y Content-Type
     */
    public function getHeaders($contentType = "application/json") {
        $token = $this->getAccessToken();
        return [
            "Authorization: Bearer {$token}",
            "Content-Type: {$contentType}"
        ];
    }

    /**
     * Invalida el token actual, forzando obtener uno nuevo en la próxima solicitud
     */
    public function refreshToken() {
        $this->accessToken = null;
    }

    /**
     * Limpieza del método de autenticación (si implementa cleanup)
     */
    public function cleanup() {
        if ($this->authentication && method_exists($this->authentication, 'cleanup')) {
            $this->authentication->cleanup();
        }
    }
}
