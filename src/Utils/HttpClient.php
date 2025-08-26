<?php

// =============================================================================
// src/Utils/HttpClient.php - Cliente HTTP para Sharepoint Client
// =============================================================================

namespace SharepointClient\Utils;

/**
 * Clase HttpClient
 * 
 * Cliente HTTP simple para realizar peticiones GET, POST, PUT y DELETE
 * utilizando cURL. Todos los métodos son estáticos y devuelven un array
 * con el cuerpo de la respuesta y el código HTTP.
 * 
 * @package SharepointClient\Utils
 * @author Jose Sánchez
 * @version 2.0.0
 */
class HttpClient {

    /**
     * Realiza una petición GET a una URL
     * 
     * @param string $url URL de destino
     * @param array $headers Headers opcionales
     * @return array ['body' => string, 'http_code' => int]
     * @throws \Exception Si ocurre un error en cURL
     */
    public static function get($url, $headers = []) {
        return self::makeRequest('GET', $url, null, $headers);
    }
    
    /**
     * Realiza una petición POST a una URL
     * 
     * @param string $url URL de destino
     * @param mixed|null $data Datos a enviar en el cuerpo de la petición
     * @param array $headers Headers opcionales
     * @return array ['body' => string, 'http_code' => int]
     * @throws \Exception Si ocurre un error en cURL
     */
    public static function post($url, $data = null, $headers = []) {
        return self::makeRequest('POST', $url, $data, $headers);
    }
    
    /**
     * Realiza una petición PUT a una URL
     * 
     * @param string $url URL de destino
     * @param mixed|null $data Datos a enviar en el cuerpo de la petición
     * @param array $headers Headers opcionales
     * @return array ['body' => string, 'http_code' => int]
     * @throws \Exception Si ocurre un error en cURL
     */
    public static function put($url, $data = null, $headers = []) {
        return self::makeRequest('PUT', $url, $data, $headers);
    }
    
    /**
     * Realiza una petición DELETE a una URL
     * 
     * @param string $url URL de destino
     * @param array $headers Headers opcionales
     * @return array ['body' => string, 'http_code' => int]
     * @throws \Exception Si ocurre un error en cURL
     */
    public static function delete($url, $headers = []) {
        return self::makeRequest('DELETE', $url, null, $headers);
    }
    
    /**
     * Método privado que ejecuta la petición HTTP usando cURL
     * 
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @param string $url URL de destino
     * @param mixed|null $data Datos a enviar en el cuerpo de la petición
     * @param array $headers Headers HTTP
     * @return array ['body' => string, 'http_code' => int]
     * @throws \Exception Si ocurre un error en cURL
     */
    private static function makeRequest($method, $url, $data = null, $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Error cURL: " . $error);
        }
        
        curl_close($ch);
        
        return [
            'body' => $response,
            'http_code' => $httpCode
        ];
    }
}
