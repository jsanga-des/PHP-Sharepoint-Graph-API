<?php
/**
 * Cliente PHP para Microsoft Graph API - SharePoint Operations
 * Soporta autenticación con Client Secret, Certificado y archivos PFX
 */
namespace SharePointClient;

use Exception;  
use Firebase\JWT\JWT;

class SharePointGraphApi {
    private $client_id;
    private $tenant_id;
    private $auth_method;
    private $client_secret;
    private $certificate_path;
    private $private_key_path;
    private $private_key_passphrase;
    private $pfx_path;
    private $pfx_passphrase;
    private $access_token;
    private $temp_cert_files = []; // Para almacenar archivos temporales
    
    // Constantes para métodos de autenticación
    const AUTH_CLIENT_SECRET = 'client_secret';
    const AUTH_CERTIFICATE = 'certificate';
    const AUTH_PFX = 'pfx';
    
    /**
     * Constructor del cliente SharePoint
     * 
     * Para autenticación con Client Secret:
     * @param string $client_id     ID de la aplicación Azure AD
     * @param string $tenant_id     ID del tenant/directorio Azure AD
     * @param string $client_secret Secreto de la aplicación Azure AD
     * 
     * Para autenticación con Certificado:
     * @param string $client_id     ID de la aplicación Azure AD
     * @param string $tenant_id     ID del tenant/directorio Azure AD
     * @param string $certificate_path Ruta al certificado público (.cer, .crt, .pem)
     * @param string $private_key_path Ruta a la clave privada (.key, .pem)
     * @param string $private_key_passphrase Passphrase de la clave privada (opcional)
     * 
     * Para autenticación con PFX:
     * @param string $client_id     ID de la aplicación Azure AD
     * @param string $tenant_id     ID del tenant/directorio Azure AD
     * @param string $pfx_path      Ruta al archivo PFX (.pfx, .p12)
     * @param string $pfx_passphrase Passphrase del archivo PFX
     * @param string $auth_method   Método de autenticación
     */
    public function __construct($client_id, $tenant_id) {
        $this->client_id = $client_id;
        $this->tenant_id = $tenant_id;
        $this->auth_method = self::AUTH_CLIENT_SECRET; // Por defecto
        
        // Obtener argumentos adicionales
        $args = func_get_args();
        if (count($args) > 2) {
            if (count($args) === 3 && is_string($args[2])) {
                // Client Secret
                $this->client_secret = $args[2];
                $this->auth_method = self::AUTH_CLIENT_SECRET;
            } else if (count($args) === 4 && is_string($args[2]) && is_string($args[3])) {
                // PFX
                $this->pfx_path = $args[2];
                $this->pfx_passphrase = $args[3];
                $this->auth_method = self::AUTH_PFX;
            } else if (count($args) >= 4) {
                // Certificado
                $this->certificate_path = $args[2];
                $this->private_key_path = $args[3];
                $this->private_key_passphrase = count($args) > 4 ? $args[4] : null;
                $this->auth_method = count($args) > 5 ? $args[5] : self::AUTH_CERTIFICATE;
            }
        }
        
        // Registrar destructor para limpiar archivos temporales
        register_shutdown_function([$this, 'cleanupTempFiles']);
    }
    
    /**
     * Destructor - limpia archivos temporales
     */
    public function __destruct() {
        $this->cleanupTempFiles();
    }
    
    /**
     * Limpia archivos temporales creados para PFX
     */
    public function cleanupTempFiles() {
        foreach ($this->temp_cert_files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->temp_cert_files = [];
    }
    
    /**
     * Establecer método de autenticación
     */
    public function setAuthMethod($auth_method) {
        $this->auth_method = $auth_method;
    }
    
    /**
     * Configurar autenticación con Client Secret
     */
    public function setClientSecretAuth($client_secret) {
        $this->client_secret = $client_secret;
        $this->auth_method = self::AUTH_CLIENT_SECRET;
    }
    
    /**
     * Configurar autenticación con Certificado
     */
    public function setCertificateAuth($certificate_path, $private_key_path, $private_key_passphrase = null) {
        $this->certificate_path = $certificate_path;
        $this->private_key_path = $private_key_path;
        $this->private_key_passphrase = $private_key_passphrase;
        $this->auth_method = self::AUTH_CERTIFICATE;
    }
    
    /**
     * Configurar autenticación con archivo PFX
     */
    public function setPfxAuth($pfx_path, $pfx_passphrase) {
        $this->pfx_path = $pfx_path;
        $this->pfx_passphrase = $pfx_passphrase;
        $this->auth_method = self::AUTH_PFX;
    }
    
    /**
     * Obtiene un token de acceso para Microsoft Graph API
     * 
     * @return string Token de acceso
     * @throws Exception Si la autenticación falla
     */
    public function getAccessToken() {
        if ($this->auth_method === self::AUTH_CLIENT_SECRET) {
            return $this->getAccessTokenWithSecret();
        } else if ($this->auth_method === self::AUTH_CERTIFICATE) {
            return $this->getAccessTokenWithCertificate();
        } else if ($this->auth_method === self::AUTH_PFX) {
            return $this->getAccessTokenWithPfx();
        } else {
            throw new Exception("Método de autenticación no válido");
        }
    }
    
    /**
     * Obtiene token usando Client Secret
     */
    private function getAccessTokenWithSecret() {
        if (empty($this->client_secret)) {
            throw new Exception("Client Secret no configurado");
        }
        
        $authority = "https://login.microsoftonline.com/" . $this->tenant_id;
        $scope = "https://graph.microsoft.com/.default";
        
        $url = $authority . "/oauth2/v2.0/token";
        
        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => $scope,
            'grant_type' => 'client_credentials'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            $this->access_token = $tokenData['access_token'];
            return $this->access_token;
        } else {
            throw new Exception("Error al obtener token con Client Secret: HTTP $httpCode - $response");
        }
    }
    
    /**
     * Obtiene token usando Certificado
     */
    private function getAccessTokenWithCertificate() {
        if (empty($this->certificate_path) || empty($this->private_key_path)) {
            throw new Exception("Certificado o clave privada no configurados");
        }
        
        $authority = "https://login.microsoftonline.com/" . $this->tenant_id;
        $scope = "https://graph.microsoft.com/.default";
        
        $url = $authority . "/oauth2/v2.0/token";
        
        // Generar el JWT para client assertion
        $client_assertion = $this->generateClientAssertion();
        
        $data = [
            'client_id' => $this->client_id,
            'scope' => $scope,
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $client_assertion,
            'grant_type' => 'client_credentials'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            $this->access_token = $tokenData['access_token'];
            return $this->access_token;
        } else {
            throw new Exception("Error al obtener token con Certificado: HTTP $httpCode - $response");
        }
    }
    
    /**
     * Obtiene token usando archivo PFX
     */
    private function getAccessTokenWithPfx() {
        if (empty($this->pfx_path)) {
            throw new Exception("Archivo PFX no configurado");
        }
        
        // Extraer certificado y clave privada del PFX
        $this->extractFromPfx();
        
        // Usar el método de certificado para obtener el token
        return $this->getAccessTokenWithCertificate();
    }
    
    /**
     * Extrae certificado y clave privada de un archivo PFX
     * 
     * @throws Exception Si no se puede extraer el PFX
     */
    private function extractFromPfx() {
        if (!file_exists($this->pfx_path)) {
            throw new Exception("El archivo PFX no existe: " . $this->pfx_path);
        }
        
        // Leer el archivo PFX
        $pfx_data = file_get_contents($this->pfx_path);
        if (!$pfx_data) {
            throw new Exception("No se pudo leer el archivo PFX: " . $this->pfx_path);
        }
        
        // Intentar abrir el PFX
        if (openssl_pkcs12_read($pfx_data, $certs, $this->pfx_passphrase)) {
            // Crear archivos temporales
            $temp_cert = tempnam(sys_get_temp_dir(), 'sharepoint_cert_');
            $temp_key = tempnam(sys_get_temp_dir(), 'sharepoint_key_');
            
            // Guardar certificado
            file_put_contents($temp_cert, $certs['cert']);
            
            // Guardar clave privada
            file_put_contents($temp_key, $certs['pkey']);
            
            // Almacener rutas para limpiar después
            $this->temp_cert_files[] = $temp_cert;
            $this->temp_cert_files[] = $temp_key;
            
            // Configurar para usar estos archivos
            $this->certificate_path = $temp_cert;
            $this->private_key_path = $temp_key;
            $this->private_key_passphrase = null; // La clave ya debería estar sin passphrase
            
        } else {
            throw new Exception("No se pudo extraer el certificado y clave privada del PFX. Verifica la contraseña.");
        }
    }
    
    /**
     * Genera el JWT para client assertion
     * 
     * @return string JWT firmado
     * @throws Exception Si no se puede generar el JWT
     */
    private function generateClientAssertion() {
        // Leer la clave privada
        $private_key = file_get_contents($this->private_key_path);
        if (!$private_key) {
            throw new Exception("No se pudo leer la clave privada: " . $this->private_key_path);
        }
        
        // Si hay passphrase, usar openssl para leer la clave
        if ($this->private_key_passphrase) {
            $private_key_resource = openssl_pkey_get_private($private_key, $this->private_key_passphrase);
            if (!$private_key_resource) {
                throw new Exception("No se pudo leer la clave privada con el passphrase proporcionado");
            }
        }
        
        // Timestamps
        $now = time();
        $expire = $now + 3600; // 1 hora
        
        // Payload del JWT
        $payload = [
            'aud' => "https://login.microsoftonline.com/{$this->tenant_id}/oauth2/v2.0/token",
            'exp' => $expire,
            'iss' => $this->client_id,
            'jti' => bin2hex(random_bytes(16)),
            'nbf' => $now,
            'sub' => $this->client_id
        ];
        
        // Headers del JWT
        $headers = [
            'x5t' => $this->getCertificateThumbprint(),
            'alg' => 'RS256'
        ];
        
        try {
            // Generar el JWT firmado
            $jwt = JWT::encode($payload, $private_key, 'RS256', null, $headers);
            return $jwt;
        } catch (Exception $e) {
            throw new Exception("Error al generar el JWT: " . $e->getMessage());
        }
    }
    
    /**
     * Calcula el thumbprint del certificado (x5t)
     * 
     * @return string Thumbprint en base64url
     * @throws Exception Si no se puede leer el certificado
     */
    private function getCertificateThumbprint() {
        $certificate = file_get_contents($this->certificate_path);
        if (!$certificate) {
            throw new Exception("No se pudo leer el certificado: " . $this->certificate_path);
        }
        
        // Limpiar el certificado (remover headers/footers si es PEM)
        $certificate = $this->cleanCertificate($certificate);
        
        // Calcular SHA-1 hash
        $hash = sha1($certificate, true);
        
        // Codificar en base64url (sin padding)
        $thumbprint = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($hash));
        
        return $thumbprint;
    }
    
    /**
     * Limpia el certificado PEM removiendo headers y footers
     * 
     * @param string $certificate Contenido del certificado
     * @return string Certificado limpio
     */
    private function cleanCertificate($certificate) {
        // Si es certificado PEM, extraer solo el contenido base64
        if (strpos($certificate, '-----BEGIN CERTIFICATE-----') !== false) {
            $certificate = preg_replace('/-----BEGIN CERTIFICATE-----/', '', $certificate);
            $certificate = preg_replace('/-----END CERTIFICATE-----/', '', $certificate);
            $certificate = preg_replace('/\s+/', '', $certificate);
            $certificate = base64_decode($certificate);
        }
        
        return $certificate;
    }
    
    // Los métodos restantes (getSiteId, getDriveId, listFiles, etc.) 
    // permanecen exactamente iguales que en la clase original...
    // Solo cambia la autenticación, el resto del funcionamiento es idéntico
    
    /**
     * Obtiene el ID de un sitio de SharePoint
     * 
     * @param string $site_path Ruta del sitio (ej: "dominio.sharepoint.com:/sites/NombreSitio")
     * @return string ID del sitio
     * @throws Exception Si no se puede obtener el ID del sitio
     */
    public function getSiteId($site_path) {
        $headers = $this->getHeaders();
        $url = "https://graph.microsoft.com/v1.0/sites/" . urlencode($site_path);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            return $data['id'];
        } else {
            throw new Exception("Error al obtener el SITE_ID: HTTP $httpCode - $response");
        }
    }
    
    /**
     * Obtiene el ID de una biblioteca de documentos (drive) de SharePoint
     * 
     * @param string $site_id    ID del sitio de SharePoint
     * @param string $drive_name Nombre de la biblioteca (ej: "Documentos")
     * @return string ID de la biblioteca
     * @throws Exception Si no se encuentra la biblioteca
     */
    public function getDriveId($site_id, $drive_name) {
        $headers = $this->getHeaders();
        $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            throw new Exception("Error al obtener los drives: HTTP $httpCode - $response");
        }
        
        $data = json_decode($response, true);
        $drives = $data['value'];
        $drive_id = null;
        
        foreach ($drives as $drive) {
            if ($drive['name'] === $drive_name) {
                $drive_id = $drive['id'];
                break;
            }
        }
        
        if (!$drive_id) {
            throw new Exception("No se encontró la biblioteca '$drive_name'.");
        }
        
        return $drive_id;
    }
    
    /**
     * Lista todos los archivos de una biblioteca de SharePoint de forma recursiva
     * 
     * @param string $site_id ID del sitio de SharePoint
     * @param string $drive_id ID de la biblioteca
     * @param string $remote_path Ruta remota en SharePoint (ej: "Carpeta/Subcarpeta")
     * @param int $maxDepth Nivel máximo de recursividad (por defecto: 1)
     * @param string $current_path Ruta actual para recursividad
     * @param int $indent Nivel de indentación para recursividad
     * @return array Lista de rutas de archivos
     */
    public function listFiles($site_id, $drive_id, $remote_path = "", $maxDepth = 1, $current_path = "", $indent = 0) {
        $headers = $this->getHeaders();
        
        // Construir la URL según si es la raíz o una carpeta específica
        if (empty($remote_path) || $remote_path === "root") {
            $graph_path = "root";
        } else {
            // Codificar la ruta para URL y construir el path de Graph
            $encoded_path = rawurlencode(trim($remote_path, '/'));
            $graph_path = "root:/{$encoded_path}:";
        }
        
        $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/$graph_path/children";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            echo "Error HTTP $httpCode en $url\n";
            return [];
        }
        
        $data = json_decode($response, true);
        if (!isset($data['value'])) {
            echo "Respuesta sin 'value'\n";
            return [];
        }

        $items = $data['value'];
        $all_files = [];

        foreach ($items as $item) {
            $item_name = $item['name'];
            $full_path = trim($current_path . '/' . $item_name, '/');
            
            if (isset($item['folder']) && $indent < $maxDepth) {
                echo str_repeat("  ", $indent) . "📁 $full_path\n";
                
                // Para subcarpetas, usar el ID del item para la recursividad
                $subfolder_id = $item['id'];
                $subfiles = $this->listFiles($site_id, $drive_id, "", $maxDepth, $full_path, $indent + 1, $subfolder_id);
                $all_files = array_merge($all_files, $subfiles);
            } else {
                echo str_repeat("  ", $indent) . "📄 $full_path\n";
                $all_files[] = $full_path;
            }
        }
        
        return $all_files;
    }

    /**
     * Sube un archivo a SharePoint en una ruta específica
     * 
     * @param string $site_id    ID del sitio de SharePoint
     * @param string $drive_id   ID de la biblioteca
     * @param string $file_path  Ruta local del archivo
     * @param string $remote_path Ruta remota en SharePoint (ej: "Test/ZN/")
     * @param string $file_name  Nombre del archivo en SharePoint (opcional, si no se proporciona se usa el nombre local)
     * @return bool True si la subida fue exitosa
     */
    public function uploadFile($site_id, $drive_id, $file_path, $remote_path = '', $file_name = null) {
        try {
            if (!file_exists($file_path)) {
                throw new Exception("El archivo no existe: $file_path");
            } 

            // Si no se proporciona nombre de archivo, usar el nombre del archivo local
            if ($file_name === null) {
                $file_name = basename($file_path);
            }

            $file_content = file_get_contents($file_path);
            
            // Construir la ruta completa
            $full_remote_path = trim($remote_path, '/'); // Quitar slashes al inicio/final
            if (!empty($full_remote_path)) {
                $full_remote_path .= '/'; // Añadir slash al final si hay ruta
            }
            
            $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/root:/{$full_remote_path}$file_name:/content";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders("application/octet-stream"));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode != 200 && $httpCode != 201) {
                error_log("Error Graph upload: HTTP $httpCode - $response");
                error_log("URL attempted: $url");
            }

            return $httpCode == 200 || $httpCode == 201;
        } catch (Exception $e) {
            error_log("Excepción al subir el archivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un archivo de SharePoint
     * 
     * @param string $site_id    ID del sitio de SharePoint
     * @param string $drive_id   ID de la biblioteca
     * @param string $file_name  Nombre del archivo en SharePoint
     * @return bool True si la eliminación fue exitosa
     */
    public function deleteFile($site_id, $drive_id, $remote_filepath) {
        try {
            $headers = $this->getHeaders();
            $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/root:/$remote_filepath";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode == 204;
        } catch (Exception $e) {
            error_log("Excepción al eliminar el archivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica si una carpeta existe en una ruta específica
     * 
     * @param string $site_id      ID del sitio de SharePoint
     * @param string $drive_id     ID de la biblioteca
     * @param string $folder_path  Ruta completa de la carpeta (ej: "Carpeta/Subcarpeta")
     * @return bool True si la carpeta existe
     */
    public function folderExists($site_id, $drive_id, $folder_path) {
        try {
            $headers = $this->getHeaders();
            
            // Limpiar y formatear la ruta
            $folder_path = trim($folder_path, '/');
            
            // Si la ruta está vacía, estamos verificando la raíz
            if (empty($folder_path)) {
                return true; // La raíz siempre existe
            }
            
            // Codificar el path completo para URL
            $encoded_path = rawurlencode($folder_path);
            $encoded_path = str_replace('%2F', '/', $encoded_path); // Mantener slashes
            
            $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/root:/$encoded_path";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                // Verificar que realmente es una carpeta
                $data = json_decode($response, true);
                if (isset($data['folder'])) {
                    echo("✅ Carpeta confirmada: Sí existe y es una carpeta\n");
                    return true;
                } else {
                    echo("Existe pero NO es una carpeta\n");
                    return false;
                }
            } else {
                echo("Carpeta no encontrada o error (HTTP $httpCode)\n");
                return false;
            }
                
        } catch (Exception $e) {
            echo("Excepción en folderExists: " . $e->getMessage() . "\n");
            return false;
        }
    }
    
    /**
     * Verifica si un archivo existe en una carpeta específica
     * 
     * @param string $site_id      ID del sitio de SharePoint
     * @param string $drive_id     ID de la biblioteca
     * @param string $folder_path  Ruta de la carpeta
     * @param string $file_name    Nombre del archivo
     * @return bool True si el archivo existe en la carpeta
     */
    public function fileExistsInFolder($site_id, $drive_id, $folder_path, $file_name) {
        try {
            $headers = $this->getHeaders();
            
            // Limpiar y formatear la ruta
            $folder_path = trim($folder_path, '/');
            
            // Construir el path completo
            $full_path = $folder_path;
            if (!empty($folder_path)) {
                $full_path .= '/';
            }
            $full_path .= $file_name;
            
            // Codificar el path completo para URL
            $encoded_path = rawurlencode($full_path);
            $encoded_path = str_replace('%2F', '/', $encoded_path); // Mantener slashes
            
            $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/root:/$encoded_path";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // Usamos GET en lugar de HEAD para mayor compatibilidad
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // DEBUG: Mostrar el código de respuesta
            echo("Código HTTP: " . $httpCode);
            
            if ($httpCode == 200) {
                // Opcional: verificar que realmente es un archivo y no una carpeta
                $data = json_decode($response, true);
                if (isset($data['file'])) {
                    echo ("Archivo confirmado: Sí existe y es un archivo");
                    return true;
                } else {
                    echo("Existe pero NO es un archivo (probablemente carpeta)");
                    return false;
                }
            } else {
                echo("Archivo no encontrado o error");
                return false;
            }
                
        } catch (Exception $e) {
            echo("Excepción en fileExistsInFolder: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lista archivos de una biblioteca de SharePoint usando path del sitio y nombre de la biblioteca
     * 
     * @param string $site_path Ruta del sitio de SharePoint (ej: "miSitio" o "miSitio/miSubsitio")
     * @param string $drive_name Nombre de la biblioteca/document library
     * @param string $remote_path Ruta remota dentro de la biblioteca (ej: "Carpeta/Subcarpeta")
     * @param int $maxDepth Nivel máximo de recursividad
     * @return array Lista de rutas de archivos
     */
    public function listFilesBySitePath($site_path, $drive_name, $remote_path = "", $maxDepth = 1) {
        $site_id = $this->getSiteId($site_path);
        $drive_id = $this->getDriveId($site_id, $drive_name);
        return $this->listFiles($site_id, $drive_id, $remote_path, $maxDepth);
    }
    
    /**
     * Sube un archivo a SharePoint usando la ruta del sitio y nombre de la biblioteca
     * 
     * @param string $site_path   Ruta del sitio (ej: "contoso.sharepoint.com/sites/misitio")
     * @param string $drive_name  Nombre de la biblioteca (ej: "Documentos")
     * @param string $file_path   Ruta local del archivo
     * @param string $remote_path Ruta remota en SharePoint (ej: "Test/ZN/")
     * @param string $file_name   Nombre del archivo en SharePoint (opcional)
     * @return bool True si la subida fue exitosa
     */
    public function uploadFileBySitePath($site_path, $drive_name, $file_path, $remote_path = '', $file_name = null) {
        try {
            $site_id = $this->getSiteId($site_path);        
            $drive_id = $this->getDriveId($site_id, $drive_name);            
            return $this->uploadFile($site_id, $drive_id, $file_path, $remote_path, $file_name);            
        } catch (Exception $e) {
            error_log("Excepción en uploadFileBySitePath: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Método de conveniencia para eliminar archivos usando la ruta del sitio
     * 
     * @param string $site_path   Ruta del sitio de SharePoint
     * @param string $drive_name  Nombre de la biblioteca
     * @param string $file_name   Nombre del archivo en SharePoint
     * @return bool True si la eliminación fue exitosa
     */
    public function deleteFileBySitePath($site_path, $drive_name, $file_name) {
        $site_id = $this->getSiteId($site_path);
        $drive_id = $this->getDriveId($site_id, $drive_name);
        return $this->deleteFile($site_id, $drive_id, $file_name);
    }
    
    /**
     * Genera los headers para las solicitudes HTTP
     * 
     * @return array Headers para las solicitudes
     */
    private function getHeaders($contentType = "application/json") {
        if (!$this->access_token) {
            $this->getAccessToken();
        }

        return [
            "Authorization: Bearer {$this->access_token}",
            "Content-Type: $contentType"
        ];
    }
}

?>
