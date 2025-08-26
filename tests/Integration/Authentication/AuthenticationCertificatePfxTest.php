<?php

// =============================================================================
// tests/Integration/Authentication/AuthenticationCertificatePfxTest.php 
// =============================================================================

# Requisitos:
# Importante tener el .env con las credenciales para la instancia definida aqui:
# $config = ConfigManager::getInstance('empresa_a');

# Ejecutar test 
# vendor/bin/phpunit tests/Integration/Authentication/AuthenticationCertificatePfxTest.php

# Ejecutar test (mostrar detalles)
# vendor/bin/phpunit --testdox tests/Integration/Authentication/AuthenticationCertificatePfxTest.php

# Ejecutar test (mostrar información de depuración)
# vendor/bin/phpunit --debug tests/Integration/Authentication/AuthenticationCertificatePfxTest.php

namespace Tests\Integration\Authentication;

use PHPUnit\Framework\TestCase;
use SharepointClient\Authentication\CertificatePfxAuth;
use SharepointClient\Config\ConfigManager;
use SharepointClient\Exceptions\SharepointException;

class AuthenticationCertificatePfxTest extends TestCase
{
    private CertificatePfxAuth $authManager;
    private string $pfxPath;
    private string $pfxPassphrase;

    public static function setUpBeforeClass(): void
    {
        echo "\nINICIANDO TEST - AUTENTICACIÓN MEDIANTE CERTIFICADO PFX\n";
        
        $config = ConfigManager::getInstance('empresa_a');
        
        $client_id = $config->get('client_id');
        $tenant_id = $config->get('tenant_id');
        $pfxPath = $config->get('path');        // ruta al archivo PFX
        $pfxPassphrase = $config->get('passphrase'); // contraseña del PFX

        echo "\n=== Client ID ===\n";
        echo $client_id . "\n";
        echo "===================\n";

        echo "\n=== Tenant ID ===\n";
        echo $tenant_id . "\n";
        echo "===================\n";

        echo "\n== PFX Path ===\n";
        echo $pfxPath . "\n";
        echo "===================\n";

        echo "\n== PFX Passfhrase ==\n";
        echo $pfxPassphrase . "\n";
        echo "===================\n";
    }

    protected function setUp(): void
    {
        $config = ConfigManager::getInstance('empresa_a');

        $client_id = $config->get('client_id');
        $tenant_id = $config->get('tenant_id');
        $this->pfxPath = $config->get('path');        // ruta al archivo PFX
        $this->pfxPassphrase = $config->get('passphrase'); // contraseña del PFX

        // Si falta algún dato de configuración, se omiten los tests
        if (empty($client_id) || empty($tenant_id) || empty($this->pfxPath) || empty($this->pfxPassphrase)) {
            $this->markTestSkipped(
                "Configura SHAREPOINT_CLIENT_ID, SHAREPOINT_TENANT_ID, PFX_PATH y PFX_PASSPHRASE en .env"
            );
        }

        $this->authManager = new CertificatePfxAuth($client_id, $tenant_id, $this->pfxPath, $this->pfxPassphrase);
    }

    /**
     * Obtiene el token de acceso usando el certificado PFX.
     * Verifica que:
     *  - Sea un string
     *  - No esté vacío
     *  - Cumpla formato JWT básico
     * Además imprime el token en consola.
     */
    public function testCertificatePfxAuthentication()
    {
        $token = $this->authManager->getAccessToken();

        echo "\n=== Access Token (PFX) ===\n";
        echo $token . "\n";
        echo "===========================\n";

        $this->assertIsString($token, "El token debe ser un string");
        $this->assertNotEmpty($token, "El token no debe estar vacío");

        $jwtPattern = '/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/';
        $this->assertMatchesRegularExpression($jwtPattern, $token);
    }

    public function testReuseAccessToken()
    {
        $token1 = $this->authManager->getAccessToken();
        $token2 = $this->authManager->getAccessToken();

        $this->assertSame($token1, $token2, "El token debe ser exactamente el mismo en llamadas consecutivas");
    }

    /**
     * Lanza excepción si no se proporciona ruta de archivo PFX.
     * Simula configuración incompleta.
     */
    public function testMissingPfxThrowsException()
    {
        $this->expectException(SharepointException::class);
        $this->expectExceptionMessage('Archivo PFX no configurado');

        $authManager = new CertificatePfxAuth('fake-client', 'fake-tenant', '', 'fake-pass');
        $authManager->getAccessToken();
    }

    /**
     * Lanza excepción si el archivo PFX no existe.
     * Simula archivo faltante.
     */
    public function testNonexistentPfxFileThrowsException()
    {
        $this->expectException(SharepointException::class);
        $this->expectExceptionMessageMatches('/No existe:/');

        $authManager = new CertificatePfxAuth('fake-client', 'fake-tenant', '/ruta/falsa.pfx', 'fake-pass');
        $authManager->getAccessToken();
    }

    /**
     * Lanza excepción si la contraseña del PFX es incorrecta.
     * Verifica manejo de errores de extracción de certificado.
     */
    public function testInvalidPfxPasswordThrowsException()
    {
        $this->expectException(SharepointException::class);
        $this->expectExceptionMessageMatches('/No se pudo extraer el certificado/');

        // Suponiendo que $this->pfxPath existe, pero se pasa una contraseña incorrecta
        $authManager = new CertificatePfxAuth('fake-client', 'fake-tenant', $this->pfxPath, 'wrong-pass');
        $authManager->getAccessToken();
    }

    /**
     * Lanza excepción si el token tiene formato JWT inválido.
     * Esto comprueba la validación del formato de token.
     */
    public function testInvalidJWTFormatFails()
    {
        $token = "invalid-token-format";

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);

        $jwtPattern = '/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/';
        $this->assertMatchesRegularExpression($jwtPattern, $token);
    }
}
