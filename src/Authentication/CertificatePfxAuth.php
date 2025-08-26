<?php

// =============================================================================
// src/Authentication/CertificatePfxAuth.php
// =============================================================================

namespace SharepointClient\Authentication;

use SharepointClient\Utils\HttpClient;
use SharepointClient\Exceptions\SharepointException;
use SharepointClient\Utils\Helpers; 
use Firebase\JWT\JWT;

/**
 * Autenticación mediante certificado PFX para Microsoft Graph API
 * 
 * Esta clase permite obtener un token de acceso válido utilizando un archivo
 * PFX (certificado + clave privada) para autenticación de tipo aplicación
 * (Client Credentials) contra Microsoft Graph API.
 * 
 * @package SharepointClient\Authentication
 * @author Jose Sánchez
 * @version 2.0.0
 */
class CertificatePfxAuth {
    /**
     * ID de la aplicación registrada en Azure AD
     * @var string
     */
    private $clientId;

    /**
     * ID del tenant de Azure AD
     * @var string
     */
    private $tenantId;

    /**
     * Ruta al archivo PFX
     * @var string
     */
    private $pfxPath;

    /**
     * Contraseña del archivo PFX
     * @var string
     */
    private $pfxPassphrase;

    /**
     * Archivos temporales generados al extraer certificado y clave privada
     * @var array
     */
    private $tempCertFiles = [];

    /**
     * Token de acceso en caché
     * @var string|null
     */
    private $token = null;

    /**
     * Expiración del token en timestamp UNIX
     * @var int|null
     */
    private $tokenExpiration = null;

    /**
     * Constructor
     * 
     * @param string $clientId ID de la aplicación
     * @param string $tenantId ID del tenant
     * @param string $pfxPath Ruta al archivo PFX
     * @param string $pfxPassphrase Contraseña del PFX
     */
    public function __construct($clientId, $tenantId, $pfxPath, $pfxPassphrase) {
        $this->clientId = $clientId;
        $this->tenantId = $tenantId;
        $this->pfxPath = $pfxPath;
        $this->pfxPassphrase = $pfxPassphrase;

        // Registrar función de limpieza al finalizar ejecución
        register_shutdown_function([$this, 'cleanup']);
    }

    /**
     * Obtiene un token de acceso válido
     * 
     * Si existe un token en caché que no haya expirado, se reutiliza.
     * De lo contrario, se solicita uno nuevo generando un JWT firmado con el certificado.
     * 
     * @return string Token de acceso
     * @throws SharepointException Si no se puede leer el PFX o la petición falla
     */
    public function getAccessToken(): string
    {
        // Reutilizar token en caché si aún es válido
        if ($this->token !== null && $this->tokenExpiration !== null && time() < $this->tokenExpiration - 30) {
            return $this->token;
        }

        if (empty($this->pfxPath)) {
            throw new SharepointException("Archivo PFX no configurado");
        }

        // Extraer certificado y clave privada del PFX a archivos temporales
        $this->extractFromPfx();

        $authority = "https://login.microsoftonline.com/" . $this->tenantId;
        $scope = "https://graph.microsoft.com/.default";
        $url = $authority . "/oauth2/v2.0/token";

        // Generar JWT firmado para client_assertion
        $clientAssertion = $this->generateClientAssertion();

        // Datos para la petición POST al endpoint de token
        $data = [
            'client_id' => $this->clientId,
            'scope' => $scope,
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $clientAssertion,
            'grant_type' => 'client_credentials'
        ];

        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        $response = HttpClient::post($url, http_build_query($data), $headers);

        if ($response['http_code'] == 200) {
            $bodyResponse = json_decode($response['body'], true);
            $this->token = $bodyResponse['access_token'];

            // Guardar fecha de expiración para reutilización del token
            $decoded = json_decode(
                base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $this->token)[1]))),
                true
            );
            $this->tokenExpiration = $decoded['exp'] ?? (time() + 3600);

            return $this->token;
        } else {
            throw new SharepointException("Error al obtener token con Certificado PFX: HTTP {$response['http_code']} - {$response['body']}");
        }
    }

    /**
     * Extrae certificado y clave privada de un archivo PFX a archivos temporales
     * 
     * @throws SharepointException Si no se puede leer el PFX o crear los archivos temporales
     */
    private function extractFromPfx() {
        Helpers::verifyFileReadable($this->pfxPath);

        $pfxData = file_get_contents($this->pfxPath);
        if ($pfxData === false) {
            throw new SharepointException("No se pudo leer el archivo PFX: " . $this->pfxPath);
        }

        if (openssl_pkcs12_read($pfxData, $certs, $this->pfxPassphrase)) {
            $tempCert = tempnam(sys_get_temp_dir(), 'sharepoint_cert_');
            $tempKey = tempnam(sys_get_temp_dir(), 'sharepoint_key_');

            if ($tempCert === false || $tempKey === false) {
                throw new SharepointException("No se pudo crear archivos temporales para el certificado");
            }

            if (file_put_contents($tempCert, $certs['cert']) === false) {
                throw new SharepointException("No se pudo escribir el certificado temporal");
            }

            if (file_put_contents($tempKey, $certs['pkey']) === false) {
                throw new SharepointException("No se pudo escribir la clave privada temporal");
            }

            $this->tempCertFiles[] = $tempCert;  // índice 0: certificado
            $this->tempCertFiles[] = $tempKey;   // índice 1: clave privada

        } else {
            throw new SharepointException("No se pudo extraer el certificado y clave privada del PFX. Verifica la contraseña.");
        }
    }

    /**
     * Genera el JWT firmado (client_assertion) para solicitar el token
     * 
     * @return string JWT firmado
     * @throws SharepointException Si no se puede generar el JWT
     */
    private function generateClientAssertion() {
        if (count($this->tempCertFiles) < 2 || !file_exists($this->tempCertFiles[1])) {
            throw new SharepointException("Clave privada temporal no disponible");
        }

        $privateKey = file_get_contents($this->tempCertFiles[1]);
        if ($privateKey === false) {
            throw new SharepointException("No se pudo leer la clave privada temporal");
        }

        $now = time();
        $expire = $now + 3600;

        $payload = [
            'aud' => "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
            'exp' => $expire,
            'iss' => $this->clientId,
            'jti' => bin2hex(random_bytes(16)),
            'nbf' => $now,
            'sub' => $this->clientId
        ];

        $headers = [
            'x5t' => $this->getCertificateThumbprint(),
            'alg' => 'RS256'
        ];

        try {
            $jwt = JWT::encode($payload, $privateKey, 'RS256', null, $headers);
            return $jwt;
        } catch (\Exception $e) {
            throw new SharepointException("Error al generar el JWT: " . $e->getMessage());
        }
    }

    /**
     * Obtiene el thumbprint del certificado temporal
     * 
     * @return string Thumbprint del certificado en Base64 URL-safe
     * @throws SharepointException Si el certificado no está disponible
     */
    private function getCertificateThumbprint() {
        if (count($this->tempCertFiles) < 2 || !file_exists($this->tempCertFiles[0])) {
            throw new SharepointException("Certificado temporal no disponible");
        }

        $certificate = file_get_contents($this->tempCertFiles[0]);
        if ($certificate === false) {
            throw new SharepointException("No se pudo leer el certificado temporal");
        }

        $certificate = $this->cleanCertificate($certificate);
        $hash = sha1($certificate, true);
        $thumbprint = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($hash));

        return $thumbprint;
    }

    /**
     * Limpia el certificado de encabezados y decodifica Base64
     * 
     * @param string $certificate Certificado en formato PEM
     * @return string Certificado binario
     */
    private function cleanCertificate($certificate) {
        if (strpos($certificate, '-----BEGIN CERTIFICATE-----') !== false) {
            $certificate = preg_replace('/-----BEGIN CERTIFICATE-----/', '', $certificate);
            $certificate = preg_replace('/-----END CERTIFICATE-----/', '', $certificate);
            $certificate = preg_replace('/\s+/', '', $certificate);
            $certificate = base64_decode($certificate);
        }

        return $certificate;
    }

    /**
     * Limpieza de archivos temporales de certificado y clave privada
     */
    public function cleanup() {
        foreach ($this->tempCertFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->tempCertFiles = [];
    }
}
