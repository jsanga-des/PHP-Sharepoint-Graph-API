<?php

// =============================================================================
// tests/Integration/Services/SiteServiceTest.php
// =============================================================================

# Requisitos:
# Importante tener el .env con las credenciales para la instancia definida aqui:
# $config = ConfigManager::getInstance('empresa_a');

# Ejecutar test 
# vendor/bin/phpunit tests/Integration/Services/SiteServiceTest.php

# Ejecutar test (mostrar detalles)
# vendor/bin/phpunit --testdox tests/Integration/Services/SiteServiceTest.php

# Ejecutar test (mostrar información de depuración)
# vendor/bin/phpunit --debug tests/Integration/Services/SiteServiceTest.php

namespace Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use SharepointClient\Config\ConfigManager;
use SharepointClient\Authentication\AuthenticationManager;
use SharepointClient\Services\SiteService;

class SiteServiceTest extends TestCase
{
    private AuthenticationManager $authManager;
    private SiteService $siteService;
    private string $sitePath;

    public static function setUpBeforeClass(): void
    {
        echo "\nINICIANDO TEST - SITE SERVICE\n";
    }

    protected function setUp(): void
    {
        $config = ConfigManager::getInstance('empresa_a');
        $authMethod = $config->getAuthMethod();
        $this->sitePath = $config->get('site_path');

        echo "\n=== CONFIGURACIÓN DETECTADA ===\n";
        echo "Auth Method: " . $authMethod . "\n";
        echo "Site Path: " . ($this->sitePath ?: 'NO DEFINIDO') . "\n";

        switch ($authMethod) {
            case 'client_secret':
                $client_id = $config->get('client_id');
                $tenant_id = $config->get('tenant_id');
                $client_secret = $config->get('secret');

                echo "Client ID: " . substr($client_id ?? '', 0, 10) . "...\n";
                echo "Tenant ID: " . substr($tenant_id ?? '', 0, 10) . "...\n";
                echo "Secret: " . (!empty($client_secret) ? '*** OK ***' : 'VACÍO') . "\n";

                if (empty($client_id) || empty($tenant_id) || empty($client_secret) || empty($this->sitePath)) {
                    $this->markTestSkipped(
                        "Faltan credenciales para secret_client (client_id, tenant_id, secret, site_path)"
                    );
                }
                break;

            case 'certificate_crt':
                $certPath = $config->get('cert_path');
                $keyPath = $config->get('key_path');
                $passphrase = $config->get('passphrase');

                echo "Cert Path: " . ($certPath ?: 'NO DEFINIDO') . "\n";
                echo "Key Path: " . ($keyPath ?: 'NO DEFINIDO') . "\n";
                echo "Passphrase: " . (!empty($passphrase) ? '*** OK ***' : 'VACÍO') . "\n";

                if (empty($certPath) || empty($keyPath) || empty($passphrase) || empty($this->sitePath)) {
                    $this->markTestSkipped(
                        "Faltan credenciales para certificate_crt (cert_path, key_path, passphrase, site_path)"
                    );
                }
                break;

            case 'certificate_pfx':
                $pfxPath = $config->get('path');
                $pfxPass = $config->get('passphrase');

                echo "PFX Path: " . ($pfxPath ?: 'NO DEFINIDO') . "\n";
                echo "Passphrase: " . (!empty($pfxPass) ? '*** OK ***' : 'VACÍO') . "\n";

                if (empty($pfxPath) || empty($pfxPass) || empty($this->sitePath)) {
                    echo "Faltan credenciales para certificate_pfx (path, passphrase, site_path)";
                    $this->markTestSkipped(
                        "Faltan credenciales para certificate_pfx (path, passphrase, site_path)"
                    );
                }
                break;

            default:
                echo "Auth method '$authMethod' no soportado en este test";
                $this->markTestSkipped("Auth method '$authMethod' no soportado en este test");
        }

        echo "================================\n";

        $this->authManager = new AuthenticationManager($config);
        $this->siteService = new SiteService($this->authManager);
    }

    /**
     * Verifica que se puede obtener el SITE_ID real desde Microsoft Graph.
     */
    public function testGetSiteId()
    {
        echo "\nProbando con sitePath: {$this->sitePath}\n";

        $siteId = $this->siteService->getSiteId($this->sitePath);

        echo "\n=== SITE ID OBTENIDO ===\n";
        echo $siteId . "\n";
        echo "=========================\n";

        $this->assertIsString($siteId, "El SITE_ID debe ser un string");
        $this->assertNotEmpty($siteId, "El SITE_ID no debe estar vacío");
    }

    public function testInvalidSitePathThrowsException()
    {
        $invalidSitePath = 'empresa_a2014.sharepoint.com:/sites/NO_EXISTE';

        echo "\nProbando con sitePath inválido: {$invalidSitePath}\n";

        $this->expectException(\SharepointClient\Exceptions\SharepointException::class);
        $this->expectExceptionMessageMatches('/Error al obtener el SITE_ID/');

        $this->siteService->getSiteId($invalidSitePath);
    }    
}
