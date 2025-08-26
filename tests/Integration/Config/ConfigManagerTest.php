<?php

// =============================================================================
// tests/Integration/Config/ConfigManagerTest.php 
// =============================================================================

# Requisitos:
# Importante tener el .env con las credenciales en todas las instancias  
# definidas en Config/Sharepoint.php:

# Ejecutar test 
# vendor/bin/phpunit tests/Integration/Config/ConfigManagerTest.php

# Ejecutar test (mostrar detalles)
# vendor/bin/phpunit --testdox tests/Integration/Config/ConfigManagerTest.php

# Ejecutar test (mostrar información de depuración)
# vendor/bin/phpunit --debug tests/Integration/Config/ConfigManagerTest.php

namespace Tests\Integration\Config;

use PHPUnit\Framework\TestCase;
use SharepointClient\Config\ConfigManager;

class ConfigManagerTest extends TestCase
{
    /**
     * Comprueba que todas las instancias de configuración están disponibles
     * y que cargan correctamente sus datos de autenticación.
     */
    public function testAllInstancesLoadCorrectly()
    {
        $instances = ConfigManager::getAvailableInstances();
        // Mostrar instancias detectadas
        echo "\n=== Instancias detectadas ===\n";
        print_r($instances);
        echo "============================\n";

        $instances = array_filter(ConfigManager::getAvailableInstances(), fn($name) => $name !== 'default');

        // Mostrar instancias detectadas
        echo "\n=== Se omitirá Default ===\n";
        print_r($instances);
        echo "============================\n";

        $this->assertNotEmpty($instances, "Debe haber al menos una instancia configurada");

        foreach ($instances as $instanceName) {
            $config = ConfigManager::getInstance($instanceName);

            // Mostrar configuración completa cargada
            echo "\n=== Configuración cargada para '$instanceName' ===\n";
            print_r($config->getAll());
            echo "===============================================\n";

            // Validar datos básicos
            $this->assertNotEmpty($config->get('client_id'), "El client_id no debe estar vacío para la instancia $instanceName");
            $this->assertNotEmpty($config->get('tenant_id'), "El tenant_id no debe estar vacío para la instancia $instanceName");

            // Validar método de autenticación
            $authMethod = $config->getAuthMethod();
            echo "Método de autenticación para '$instanceName': " . var_export($authMethod, true) . "\n";
            $this->assertNotEmpty($authMethod, "El método de autenticación no debe estar vacío para $instanceName");

            switch ($authMethod) {
                case 'client_secret':
                    $secret = $config->getSecure('secret');
                    echo "Secret para '$instanceName': " . var_export($secret, true) . "\n";
                    $this->assertNotEmpty($secret, "La secret es requerida para client_secret en $instanceName");
                    break;
                case 'certificate_pfx':
                    $pfxPath = $config->get('path');
                    echo "Ruta PFX para '$instanceName': $pfxPath\n";
                    $this->assertFileExists($pfxPath, "El archivo PFX debe existir en $instanceName");
                    break;
                case 'certificate_crt':
                    $certPath = $config->get('cert_path');
                    $keyPath = $config->get('key_path');
                    echo "Certificado CRT para '$instanceName': $certPath\n";
                    echo "Clave privada CRT para '$instanceName': $keyPath\n";
                    $this->assertFileExists($certPath, "El certificado CRT debe existir en $instanceName");
                    $this->assertFileExists($keyPath, "La clave privada debe existir en $instanceName");
                    break;
                default:
                    $this->fail("Método de autenticación desconocido '$authMethod' en $instanceName");
            }
        }
    }


    /**
     * Comprueba que la configuración default también se puede cargar
     */
    public function testDefaultInstanceLoads()
    {
        $config = ConfigManager::getInstance();
        $this->assertNotNull($config, "La instancia default debe cargarse correctamente");

        echo "\n=== Valores de la instancia 'default' ===\n";
        print_r($config->getAll());
        echo "========================================\n";

        $authMethod = $config->getAuthMethod();
        echo "Método de autenticación: " . var_export($authMethod, true) . "\n";

        $clientId = $config->get('client_id');
        echo "Client ID: " . var_export($clientId, true) . "\n";

        $tenantId = $config->get('tenant_id');
        echo "Tenant ID: " . var_export($tenantId, true) . "\n";

        $this->assertNotEmpty($authMethod, "El default debe tener método de autenticación definido");
    }

}
