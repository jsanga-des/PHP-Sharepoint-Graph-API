<?php

// =============================================================================
// tests/Integration/Services/DriveServiceTest.php
// =============================================================================

# Requisitos:
# Importante tener el .env con las credenciales para la instancia definida aqui:
# $config = ConfigManager::getInstance('empresa_a');

# Ejecutar test 
# vendor/bin/phpunit tests/Integration/Services/DriveServiceTest.php

# Ejecutar test (mostrar detalles)
# vendor/bin/phpunit --testdox tests/Integration/Services/DriveServiceTest.php

# Ejecutar test (mostrar información de depuración)
# vendor/bin/phpunit --debug tests/Integration/Services/DriveServiceTest.php

namespace Tests\Integration\Services;

use PHPUnit\Framework\TestCase;
use SharepointClient\Config\ConfigManager;
use SharepointClient\Authentication\AuthenticationManager;
use SharepointClient\Services\SiteService;
use SharepointClient\Services\DriveService;
use SharepointClient\Exceptions\SharepointException;

class DriveServiceTest extends TestCase
{
    private AuthenticationManager $authManager;
    private SiteService $siteService;
    private DriveService $driveService;
    private string $sitePath;
    private string $siteId;

    public static function setUpBeforeClass(): void
    {
        echo "\nINICIANDO TEST - DRIVE SERVICE\n";
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
                        "Faltan credenciales para client_secret (client_id, tenant_id, secret, site_path)"
                    );
                }
                break;

            case 'certificate_crt':
                $certPath = $config->get('cert_path');
                $keyPath = $config->get('key_path');
                $passphrase = $config->get('passphrase');

                if (empty($certPath) || empty($keyPath) || empty($passphrase) || empty($this->sitePath)) {
                    $this->markTestSkipped(
                        "Faltan credenciales para certificate_crt (cert_path, key_path, passphrase, site_path)"
                    );
                }
                break;

            case 'certificate_pfx':
                $pfxPath = $config->get('path');
                $pfxPass = $config->get('passphrase');

                if (empty($pfxPath) || empty($pfxPass) || empty($this->sitePath)) {
                    $this->markTestSkipped(
                        "Faltan credenciales para certificate_pfx (path, passphrase, site_path)"
                    );
                }
                break;

            default:
                $this->markTestSkipped("Auth method '$authMethod' no soportado en este test");
        }

        echo "================================\n";

        $this->authManager = new AuthenticationManager($config);
        $this->siteService = new SiteService($this->authManager);
        $this->driveService = new DriveService($this->authManager);

        // Obtener site_id real
        $this->siteId = $this->siteService->getSiteId($this->sitePath);
        echo "\n=== SITE ID OBTENIDO ===\n";
        echo $this->siteId . "\n";
        echo "=========================\n";
    }

    /**
     * Verifica que se puede obtener un drive_id real desde Microsoft Graph
     */
    public function testGetDriveId()
    {
        $driveName = 'Documentos'; // Cambiar al nombre real de la biblioteca si es distinto
        echo "\nProbando con driveName: {$driveName}\n";

        $driveId = $this->driveService->getDriveId($this->siteId, $driveName);

        echo "\n=== DRIVE ID OBTENIDO ===\n";
        echo $driveId . "\n";
        echo "=========================\n";

        $this->assertIsString($driveId, "El DRIVE_ID debe ser un string");
        $this->assertNotEmpty($driveId, "El DRIVE_ID no debe estar vacío");
    }

    /**
     * Verifica que al usar un drive_name inválido, DriveService lance SharepointException
     */
    public function testInvalidDriveNameThrowsException()
    {
        $invalidDriveName = 'BIBLIOTECA_NO_EXISTE';
        echo "\nProbando driveName inválido: {$invalidDriveName}\n";

        $this->expectException(SharepointException::class);
        $this->expectExceptionMessageMatches("/No se encontró la biblioteca/");

        $this->driveService->getDriveId($this->siteId, $invalidDriveName);
    }
}
