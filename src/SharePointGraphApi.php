<?php
/**
 * Cliente PHP para Microsoft Graph API - SharePoint Operations
 * 
 * Proporciona métodos para autenticar y realizar operaciones en SharePoint
 * mediante Microsoft Graph API usando el flujo de credenciales de cliente OAuth 2.0.
 */
namespace SharePointClient;

use Exception;  

class SharePointGraphApi {
    private $client_id;
    private $tenant_id;
    private $client_secret;
    private $access_token;
    
    /**
     * Constructor del cliente SharePoint
     * 
     * @param string $client_id     ID de la aplicación Azure AD
     * @param string $tenant_id     ID del tenant/directorio Azure AD
     * @param string $client_secret Secreto de la aplicación Azure AD
     */
    public function __construct($client_id, $tenant_id, $client_secret) {
        $this->client_id = $client_id;
        $this->tenant_id = $tenant_id;
        $this->client_secret = $client_secret;
    }
    
    /**
     * Obtiene un token de acceso para Microsoft Graph API
     * 
     * @return string Token de acceso
     * @throws Exception Si la autenticación falla
     */
    public function getAccessToken() {
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
            throw new Exception("Error al obtener token: HTTP $httpCode - $response");
        }
    }
    
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
     * @param string $site_id    ID del sitio de SharePoint
     * @param string $drive_id   ID de la biblioteca
     * @param string $folder_path Ruta de la carpeta (por defecto: "root")
     * @param string $current_path Ruta actual para recursividad
     * @param int $indent Nivel de indentación para recursividad
     * @return array Lista de rutas de archivos
     */
    public function /* The above code is a comment in PHP. It is not performing any action or
    functionality in the code. It is simply a comment that is ignored by the PHP
    interpreter when the code is executed. */
    listFiles($site_id, $drive_id, $folder_path = "root", $current_path = "", $indent = 0) {
        $headers = $this->getHeaders();
        $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/$folder_path/children";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            return [];
        }
        
        $data = json_decode($response, true);
        $items = $data['value'];
        $all_files = [];
        
        foreach ($items as $item) {
            $item_name = $item['name'];
            $full_path = trim($current_path . '/' . $item_name, '/');
            
            if (isset($item['folder'])) {
                // Es una carpeta: recorrer recursivamente
                $subfolder_path = "items/{$item['id']}";
                $subfiles = $this->listFiles($site_id, $drive_id, $subfolder_path, $full_path, $indent + 1);
                $all_files = array_merge($all_files, $subfiles);
            } else {
                // Es un archivo: agregar al listado
                $all_files[] = $full_path;
            }
        }
        
        return $all_files;
    }
    
    /**
     * Sube un archivo a SharePoint
     * 
     * @param string $site_id    ID del sitio de SharePoint
     * @param string $drive_id   ID de la biblioteca
     * @param string $file_name  Nombre del archivo en SharePoint
     * @param string $file_path  Ruta local del archivo
     * @return bool True si la subida fue exitosa
     */
    public function uploadFile($site_id, $drive_id, $file_name, $file_path) {
        try {
            if (!file_exists($file_path)) {
                throw new Exception("El archivo no existe: $file_path");
            }

            $file_content = file_get_contents($file_path);
            $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/root:/$file_name:/content";

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
                // Debug útil
                error_log("Error Graph upload: HTTP $httpCode - $response");
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
    public function deleteFile($site_id, $drive_id, $file_name) {
        try {
            $headers = $this->getHeaders();
            $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/root:/$file_name";
            
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
     * Verifica si una carpeta existe en la raíz de la biblioteca
     * 
     * @param string $site_id      ID del sitio de SharePoint
     * @param string $drive_id     ID de la biblioteca
     * @param string $folder_name  Nombre de la carpeta
     * @return bool True si la carpeta existe
     */
    public function folderExists($site_id, $drive_id, $folder_name) {
        $headers = $this->getHeaders();
        $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/root/children";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            error_log("Error al acceder al drive: HTTP $httpCode");
            return false;
        }
        
        $data = json_decode($response, true);
        $items = $data['value'];
        
        foreach ($items as $item) {
            if (isset($item['folder']) && strtolower($item['name']) === strtolower($folder_name)) {
                return true;
            }
        }
        
        return false;
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
        $headers = $this->getHeaders();
        $url = "https://graph.microsoft.com/v1.0/sites/$site_id/drives/$drive_id/root:/$folder_path:/children";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            error_log("Error al acceder a la carpeta '$folder_path': HTTP $httpCode");
            return false;
        }
        
        $data = json_decode($response, true);
        $items = $data['value'];
        
        foreach ($items as $item) {
            if (!isset($item['folder']) && strtolower($item['name']) === strtolower($file_name)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Método de conveniencia para listar archivos usando la ruta del sitio
     * 
     * @param string $site_path   Ruta del sitio de SharePoint
     * @param string $drive_name  Nombre de la biblioteca
     * @return array Lista de archivos
     */
    public function listFilesBySitePath($site_path, $drive_name) {
        $site_id = $this->getSiteId($site_path);
        $drive_id = $this->getDriveId($site_id, $drive_name);
        return $this->listFiles($site_id, $drive_id);
    }
    
    /**
     * Método de conveniencia para subir archivos usando la ruta del sitio
     * 
     * @param string $site_path   Ruta del sitio de SharePoint
     * @param string $drive_name  Nombre de la biblioteca
     * @param string $file_name   Nombre del archivo en SharePoint
     * @param string $file_path   Ruta local del archivo
     * @return bool True si la subida fue exitosa
     */
    public function uploadFileBySitePath($site_path, $drive_name, $file_name, $file_path) {
        $site_id = $this->getSiteId($site_path);
        $drive_id = $this->getDriveId($site_id, $drive_name);
        return $this->uploadFile($site_id, $drive_id, $file_name, $file_path);
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