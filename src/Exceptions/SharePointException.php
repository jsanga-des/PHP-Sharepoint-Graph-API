<?php

// =============================================================================
// src/Exceptions/SharepointException.php - Excepción personalizada para Sharepoint Client
// =============================================================================

namespace SharepointClient\Exceptions;

use Exception;

/**
 * Clase SharepointException
 * 
 * Excepción personalizada para errores en Sharepoint Client.
 * Permite agregar contexto adicional y convertir la excepción a array
 * para facilitar logging y debugging.
 * 
 * @package SharepointClient\Exceptions
 * @author Jose Sánchez
 * @version 2.0.0
 */
class SharepointException extends Exception {

    /**
     * Contexto adicional para la excepción
     * @var array
     */
    protected array $context;

    /**
     * Constructor de la excepción
     * 
     * @param string $message Mensaje de la excepción
     * @param int $code Código de error
     * @param ?Exception $previous Excepción anterior (nullable)
     * @param array $context Contexto adicional
     */
    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null, array $context = []) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    /**
     * Obtiene el contexto adicional del error
     * 
     * @return array
     */
    public function getContext(): array {
        return $this->context;
    }
    
    /**
     * Establece contexto adicional
     * 
     * @param array $context
     * @return $this
     */
    public function setContext(array $context): self {
        $this->context = $context;
        return $this;
    }
    
    /**
     * Convierte la excepción a array para logging
     * 
     * @return array
     */
    public function toArray(): array {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ];
    }
    
    /**
     * Crea una excepción de tipo configuración
     * 
     * @param string $message Mensaje de error
     * @param array $context Contexto adicional
     * @return static
     */
    public static function configurationError(string $message, array $context = []): static {
        return new static("Configuration Error: $message", 500, null, $context);
    }
}
