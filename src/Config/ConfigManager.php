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

    /**
     * Indica si las variables de entorno ya fueron cargadas
     * @var bool
     */
    private static $envLoaded = false;

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
     * @return string|bool
     */
    public function isDebugEnabled() {
        return $this->get('enviroment', 'local');
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
     * Carga la configuración completa desde el archivo sharepoint.php y maneja variables de entorno
     *
     * @return array
     * @throws SharepointException
     */
    private static function loadFullConfigStatic() {
        // Cargar variables de entorno antes de cargar el archivo de configuración
        if (!self::$envLoaded) {
            self::loadEnvironmentVariables();
            self::$envLoaded = true;
        }

        $configFile = self::findConfigFileStatic();
        if (!$configFile) {
            throw SharepointException::configurationError('No se encontró el archivo de configuración `Sharepoint.php`.');
        }

        // Incluir sharepoint.php; getenv() ahora devolverá las variables correctas según el entorno
        $configArray = require $configFile;

        if (!is_array($configArray) || empty($configArray['sites'])) {
            throw SharepointException::configurationError(
                'El archivo de configuración debe retornar un array con la clave "sites"'
            );
        }

        return $configArray;
    }

    /**
     * Carga las variables de entorno según la configuración de SHAREPOINT_ENV
     *
     * @throws SharepointException
     */
    private static function loadEnvironmentVariables() {
        // Primero, verificar si existe un archivo .env para determinar el entorno
        $envFile = self::findEnvFileStatic();
        
        if ($envFile) {
            // Cargar solo la variable SHAREPOINT_ENV del archivo .env para determinar el modo
            $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $sharepoint_env = null;
            
            foreach ($envLines as $line) {
                $line = trim($line);
                if (strpos($line, '#') === 0 || empty($line)) continue;
                
                if (strpos($line, 'SHAREPOINT_ENV=') === 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $sharepoint_env = trim($value, " \n\r\t\v\x00\"'");
                    break;
                }
            }
            
            // Si encontramos SHAREPOINT_ENV en el .env, usamos ese valor
            if ($sharepoint_env !== null) {
                putenv("SHAREPOINT_ENV=$sharepoint_env");
            }
        }
        
        // Obtener el entorno final (del .env o del sistema)
        $environment = getenv('SHAREPOINT_ENV') ?: 'local';
        
        // Si el entorno es 'local', cargar todas las variables del archivo .env
        if ($environment === 'local' && $envFile) {
            $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($envLines as $line) {
                $line = trim($line);
                if (strpos($line, '#') === 0 || empty($line)) continue;
                
                $equalPos = strpos($line, '=');
                if ($equalPos === false) continue;
                
                $key = trim(substr($line, 0, $equalPos));
                $value = trim(substr($line, $equalPos + 1), " \n\r\t\v\x00\"'");
                
                if (!empty($key)) {
                    putenv("$key=$value");
                }
            }
        }
        // Si el entorno NO es 'local', las variables se tomarán del sistema (ya están en getenv())
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
                if (empty($this->config['path'])) {
                    throw SharepointException::configurationError(
                        "No se ha indicado una variable de entorno para el path del certificado pfx " . $instanceInfo
                    );
                }
                $this->config['path'] = "../" . $this->config['path'];           
                if (!file_exists($this->config['path'])) {
                    throw SharepointException::configurationError(
                        "La ruta del archivo PFX no es válida o el archivo no existe" . $instanceInfo
                    );
                }
                break;
            case 'certificate_crt':         
                if (empty($this->config['cert_path'])) {
                    throw SharepointException::configurationError(
                        "No se ha indicado una variable de entorno para el path del certificado crt" . $instanceInfo
                    );
                }   
                $this->config['path'] = "../" . $this->config['path'];   
                if (!file_exists($this->config['cert_path'])) {
                    throw SharepointException::configurationError(
                        "La ruta del certificado CRT no es válida o el archivo no existe" . $instanceInfo
                    );
                }             
                if (empty($this->config['key_path'])) {
                    throw SharepointException::configurationError(
                        "No se ha indicado una variable de entorno para el archivo con la clave del certificado CRT" . $instanceInfo
                    );
                }
                if (!file_exists($this->config['key_path'])) {
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
            './Sharepoint.php',
            '../Sharepoint.php',
            '../../Sharepoint.php',
        ];

        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                return realpath($path);
            }
        }

        return null;
    }

    /**
     * Busca de forma estática el archivo .env en rutas posibles
     *
     * @return string|null Ruta del archivo
     */
    private static function findEnvFileStatic() {
        $possible_paths = [
            './.env',
            '../.env',
            '../../.env',
        ];

        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                return realpath($path);
            }
        }

        return null;
    }
}