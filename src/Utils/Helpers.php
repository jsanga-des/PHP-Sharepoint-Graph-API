<?php

// =============================================================================
// src/Utils/Helpers.php - Clase de utilidades para Sharepoint Client
// =============================================================================

namespace SharepointClient\Utils;

use SharepointClient\Exceptions\SharepointException;

/**
 * Clase de utilidades para Sharepoint Client
 * 
 * Esta clase centraliza funciones de ayuda comunes que se utilizan
 * en diferentes módulos del cliente de Sharepoint, como:
 * - Verificación de archivos y directorios
 * - Construcción y limpieza de rutas para Graph API
 * 
 * Todas las funciones son estáticas y no requieren instanciación.
 * 
 * @package SharepointClient\Utils
 * @author Jose Sánchez
 * @version 2.0.0
 */
class Helpers {
    
    /**
     * Verifica que un archivo existe y es legible
     *
     * @param string $filePath Ruta del archivo
     * @param string $label Etiqueta descriptiva para el error
     * @throws SharepointException
     */
    public static function verifyFileReadable(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new SharepointException("No existe: $filePath");
        }

        if (!is_readable($filePath)) {
            throw new SharepointException("No es legible: $filePath");
        }
    }

    /**
     * Verifica que un path existe y es un directorio
     *
     * @param string $path Ruta a comprobar
     * @param string $label Etiqueta descriptiva para el error
     * @throws SharepointException
     */
    public static function verifyPathExists(string $path)
    {
        if (!file_exists($path)) {
            throw new SharepointException("No existe: $path");
        }

        if (!is_dir($path)) {
            throw new SharepointException("No es un directorio válido: $path");
        }
    }

    /**
     * Codifica una ruta para usar en URLs de Graph API
     * 
     * @param string $path Ruta a codificar
     * @return string Ruta codificada
     */
    public static function cleanUrlPath(string $path): string
    {
        $encodedPath = rawurlencode($path);
        return str_replace('%2F', '/', $encodedPath);
    }

    /**
     * Construye una ruta remota combinando folder y filename
     * 
     * @param string $folderPath Ruta de la carpeta
     * @param string|null $fileName Nombre del archivo (opcional)
     * @return string Ruta completa
     * @throws SharepointException Si no se proporciona ningún parámetro válido
     */
    public static function buildRelativeRemotePath(string $folderPath, ?string $fileName = null): string
    {
        // Validar que se proporcione al menos uno de los parámetros
        if (empty($folderPath) && empty($fileName)) {
            throw new SharepointException('Debe proporcionar al menos folderPath o fileName (si está en raiz) para construir la ruta');
        }
        
        $folderPath = trim($folderPath, '/');
        
        if (empty($folderPath)) {
            return $fileName ?? '';
        }
        
        if ($fileName === null) {
            return $folderPath;
        }
        
        return $folderPath . '/' . $fileName;
    }

    /**
     * Registra un mensaje de error en el archivo errors.log en la raíz del proyecto.
     *
     * @param string $message Mensaje de error a registrar
     * @param string|null $context Información adicional opcional
     */
    public static function logError(string $message, ?string $context = null): void
    {
        $logFile = __DIR__ . '/../../errors.log'; 
        $dateTime = date('Y-m-d H:i:s');
        $logEntry = "[$dateTime] ERROR: $message";
        
        if ($context !== null) {
            $logEntry .= " | Context: $context";
        }

        $logEntry .= PHP_EOL;

        // Intentar crear o escribir en el archivo
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
