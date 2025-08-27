<?php

// =============================================================================
// src/Config/ConfigManager.php - Gestor de configuración
// =============================================================================

namespace SharepointClient\Config;

use SharepointClient\Exceptions\SharepointException;

/**
 * Clase para la gestión centralizada de múltiples configuraciones de Sharepoint
 *
 * Esta clase maneja la carga, validación y acceso a configuraciones múltiples
 * para la autenticación y conexión con diferentes instancias de Microsoft Sharepoint.
 * Implementa el patrón Singleton/Multi-ton para mantener instancias separadas
 * por nombre de configuración.
 * 
 * @package SharepointClient\Config
 * @author Jose Sánchez
 * @version 2.0.0
 */
class ConfigManager {

    /**
     * Instancias de configuración por nombre
     * @var array
     */
    private static $instances = [];

    /**
     * Configuración cargada para la instancia actual
     * @var array
     */
    private $config = [];

    /**
     * Nombre de la instancia de configuración actual
     * @var string
     */
    private $instanceName;

    /**
     * Configuración completa cargada desde el archivo
     * @var array|null
     */
    private static $fullConfig = null;

    // -------------------------------------------------------------------------
    // Métodos públicos de acceso
    // -------------------------------------------------------------------------

    /**
     * Obtiene la instancia de configuración por nombre (Multi-ton)
     *
     * @param string $instanceName Nombre de la instancia
     * @return ConfigManager Instancia de configuración solicitada
     * @throws SharepointException Si no se puede cargar la configuración
     */
    public static function getInstance($instanceName = 'default') {
        if (!isset(self::$instances[$instanceName])) {
            self::$instances[$instanceName] = new self($instanceName);
        }
        return self::$instances[$instanceName];
    }

    /**
     * Obtiene todas las instancias de configuración disponibles
     *
     * @return array Nombres de todas las instancias configuradas
     * @throws SharepointException Si no se puede cargar la configuración completa
     */
    public static function getAvailableInstances() {
        if (self::$fullConfig === null) {
            self::$fullConfig = self::loadFullConfigStatic();
        }

        $instances = array_keys(self::$fullConfig['sites'] ?? []);

        return empty($instances) ? ['default'] : $instances;
    }

    /**
     * Devuelve el nombre de la instancia de configuración actual
     * @return string
     */
    public function getInstanceName() {
        return $this->instanceName;
    }

    /**
     * Devuelve la configuración completa de la instancia
     * @return array
     */
    public function getAll() {
        return $this->config;
    }

    /**
     * Obtiene un valor de configuración por clave
     *
     * @param string $key Clave de configuración
     * @param mixed $default Valor por defecto si la clave no existe
     * @return mixed
     */
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    /**
     * Verifica si una clave de configuración existe y no está vacía
     *
     * @param string $key Clave de configuración
     * @return bool
     */
    public function has($key) {
        return isset($this->config[$key]) && !empty($this->config[$key]);
    }

    /**
     * Obtiene el método de autenticación configurado para esta instancia
     *
     * @return string
     * @throws SharepointException Si no se puede determinar
     */
    public function getAuthMethod() {
        if (isset($this->config['auth_method'])) {
            return $this->config['auth_method'];
        }
        throw SharepointException::configurationError(
            "No se pudo determinar el método de autenticación para la instancia '{$this->instanceName}'"
        );
    }

    /**
     * Verifica si el modo debug está activado
     *
     * @return bool
     */
    public function isDebugEnabled() {
        return (bool)$this->get('debug', false);
    }

    /**
     * Obtiene configuración sensible de forma segura (masking en logs)
     *
     * @param string $key Clave de configuración sensible
     * @return mixed
     */
    public function getSecure($key) {
        $value = $this->get($key);
        if ($value && in_array($key, ['secret', 'passphrase'])) {
            $masked = substr($value, 0, 4) . str_repeat('*', max(0, strlen($value) - 8)) . substr($value, -4);
            error_log("Retrieved secure config '{$this->instanceName}.{$key}': $masked");
        }
        return $value;
    }

    // -------------------------------------------------------------------------
    // Métodos privados / carga y validación de configuración
    // -------------------------------------------------------------------------

    /**
     * Constructor privado para implementar patrón Multi-ton
     *
     * @param string $instanceName Nombre de la instancia
     * @throws SharepointException
     */
    private function __construct($instanceName = 'default') {
        $this->instanceName = $instanceName;
        $this->loadConfiguration();
    }

    /**
     * Carga la configuración desde el archivo y la fusiona con variables de entorno
     *
     * @throws SharepointException
     */
    private function loadConfiguration() {
        if (self::$fullConfig === null) {
            self::$fullConfig = self::loadFullConfigStatic();
        }

        $instanceConfig = self::$fullConfig['sites'][$this->instanceName] ?? [];

        if (empty($instanceConfig) && $this->instanceName !== 'default') {
            throw SharepointException::configurationError(
                "No se encontró configuración para la instancia: {$this->instanceName}"
            );
        }

        // Fusionar configuración global, general y de autenticación
        $this->config = array_merge(
            self::$fullConfig['env'] ?? [],
            $instanceConfig['general'] ?? [],
            $instanceConfig['auth'][$instanceConfig['auth_method'] ?? ''] ?? []
        );

        // Valores especiales
        $this->config['instance_name'] = $this->instanceName;
        $this->config['auth_method'] = $instanceConfig['auth_method'] ?? null;

        $this->validateConfig();
    }

    /**
     * Carga la configuración completa desde el archivo sharepoint.php y el .env
     *
     * @return array
     * @throws SharepointException
     */
    private static function loadFullConfigStatic() {
        $configFile = self::findConfigFileStatic();

        if (!$configFile) {
            throw SharepointException::configurationError('No se encontró el archivo de configuración `sharepoint.php`');
        }

        // --- Carga de .env ---
        $envFile = '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \n\r\t\v\x00\"'");
                if (!empty($key)) putenv("$key=$value");
            }
        }

        // Cargar configuración desde sharepoint.php
        $configArray = require $configFile;

        if (!is_array($configArray) || empty($configArray['sites'])) {
            throw SharepointException::configurationError(
                'El archivo de configuración debe retornar un array con la clave "sites"'
            );
        }

        return $configArray;
    }

    /**
     * Valida la configuración cargada para la instancia actual
     *
     * @throws SharepointException
     */
    private function validateConfig() {
        $instanceInfo = $this->instanceName !== 'default' ? " para la instancia '{$this->instanceName}'" : '';

        if (empty($this->config['client_id'])) {
            throw SharepointException::configurationError("El 'client_id' es requerido" . $instanceInfo);
        }

        if (empty($this->config['tenant_id'])) {
            throw SharepointException::configurationError("El 'tenant_id' es requerido" . $instanceInfo);
        }

        $authMethod = $this->config['auth_method'] ?? null;

        if (empty($authMethod)) {
            throw SharepointException::configurationError("El método de autenticación ('auth_method') es requerido" . $instanceInfo);
        }

        switch ($authMethod) {
            case 'client_secret':
                if (empty($this->config['secret'])) {
                    throw SharepointException::configurationError(
                        "La clave 'secret' es requerida para el método 'client_secret'" . $instanceInfo
                    );
                }
                break;
            case 'certificate_pfx':                
                if (empty($this->config['path']) || !file_exists($this->config['path'])) {
                    throw SharepointException::configurationError(
                        "La ruta del archivo PFX no es válida o el archivo no existe" . $instanceInfo
                    );
                }
                break;
            case 'certificate_crt':                
                if (empty($this->config['cert_path']) || !file_exists($this->config['cert_path'])) {
                    throw SharepointException::configurationError(
                        "La ruta del certificado CRT no es válida o el archivo no existe" . $instanceInfo
                    );
                }                
                if (empty($this->config['key_path']) || !file_exists($this->config['key_path'])) {
                    throw SharepointException::configurationError(
                        "La ruta de la clave privada no es válida o el archivo no existe" . $instanceInfo
                    );
                }
                throw SharepointException::configurationError(
                    "Aun no se admite autenticación mediante certificado CRT" . $instanceInfo
                );
                break;
            default:
                throw SharepointException::configurationError(
                    "El método de autenticación '{$authMethod}' no es válido" . $instanceInfo
                );
        }
    }

    /**
     * Busca de forma estática el archivo sharepoint.php en rutas posibles
     *
     * @return string|null Ruta del archivo
     */
    private static function findConfigFileStatic() {
        $possible_paths = [
            // Nueva ubicación principal en la raíz del proyecto
            './Sharepoint.php',
            'Sharepoint.php',
            getcwd() . '/Sharepoint.php',
            
            // Buscar desde la raíz del proyecto navegando hacia arriba desde vendor
            dirname(__DIR__, 4) . '/Sharepoint.php', // vendor/jsanga-des/php-sharepoint-graph-api/src → raíz
            dirname(__DIR__, 3) . '/Sharepoint.php', // vendor/jsanga-des/php-sharepoint-graph-api → raíz
            
            // Rutas alternativas por si el usuario movió el archivo
            './Sharepoint.php',
            '../Sharepoint.php',
            '../../Sharepoint.php',
            
            // Rutas legacy por compatibilidad (mantener las originales por si acaso)
            'src/Config/Sharepoint.php',
            'Sharepoint.php',
            '../../src/Config/Sharepoint.php',
            __DIR__ . '/../../src/Config/Sharepoint.php',
            __DIR__ . '/../src/Config/Sharepoint.php',
            getcwd() . '/src/Config/Sharepoint.php',
        ];

        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                return realpath($path);
            }
        }

        return null;
    }
}
