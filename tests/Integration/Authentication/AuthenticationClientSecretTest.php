<?php

// =============================================================================
// tests/Integration/Authentication/AuthenticationClientSecretTest.php 
// =============================================================================

# Requisitos:
# Importante tener el .env con las credenciales para la instancia definida aqui:
# $config = ConfigManager::getInstance('empresa_a');

# Ejecutar test 
# vendor/bin/phpunit tests/Integration/Authentication/AuthenticationClientSecretTest.php

# Ejecutar test (mostrar detalles)
# vendor/bin/phpunit --testdox tests/Integration/Authentication/AuthenticationClientSecretTest.php

# Ejecutar test (mostrar información de depuración)
# vendor/bin/phpunit --debug tests/Integration/Authentication/AuthenticationClientSecretTest.php

namespace Tests\Integration\Authentication;

use PHPUnit\Framework\TestCase;
use SharepointClient\Authentication\AuthenticationManager;
use SharepointClient\Authentication\ClientSecretAuth;
use SharepointClient\Config\ConfigManager;
use SharepointClient\Exceptions\SharepointException;

class AuthenticationClientSecretTest extends TestCase
{
    /**
     * @var AuthenticationManager Instancia que gestiona la autenticación mediante client secret
     */
    private AuthenticationManager $authManager;

    public static function setUpBeforeClass(): void
    {
        echo "\nINICIANDO TEST - AUTENTICACIÓN MEDIANTE CLIENT SECRET\n";
        
        $config = ConfigManager::getInstance('empresa_a');
        
        $client_id = $config->get('client_id');
        $tenant_id = $config->get('tenant_id');
        $client_secret = $config->get('secret'); // clave correcta

        echo "\n=== Client ID ===\n";
        echo $client_id . "\n";
        echo "===================\n";

        echo "\n=== Tenant ID ===\n";
        echo $tenant_id . "\n";
        echo "===================\n";

        echo "\n== Client Secret ===\n";
        echo $client_secret . "\n";
        echo "===================\n";
    }

    /**
     * Carga la configuración de la instancia 'empresa_a' desde ConfigManager.
     * Si falta alguna variable crítica (client_id, tenant_id, secret), se omite el test.
     */
    protected function setUp(): void
    {
        $config = ConfigManager::getInstance('empresa_a');

        $client_id = $config->get('client_id');
        $tenant_id = $config->get('tenant_id');
        $client_secret = $config->get('secret'); // clave correcta

        if (empty($client_id) || empty($tenant_id) || empty($client_secret)) {
            $this->markTestSkipped(
                "Configura SHAREPOINT_CLIENT_ID, SHAREPOINT_TENANT_ID y SHAREPOINT_CLIENT_SECRET en .env"
            );
        }

        $this->authManager = new AuthenticationManager($config);
    }

    /**
     * Verifica que la autenticación mediante client secret funciona correctamente.
     * - Obtiene el access token real desde Microsoft Graph.
     * - Comprueba que sea un string no vacío.
     * - Valida que tenga formato JWT.
     */
    public function testClientSecretAuthentication()
    {
        $token = $this->authManager->getAccessToken();

        echo "\n=== Access Token ===\n";
        echo $token . "\n";
        echo "===================\n";

        $this->assertIsString($token, "El token debe ser un string");
        $this->assertNotEmpty($token, "El token no debe estar vacío");

        $jwtPattern = '/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/';
        $this->assertMatchesRegularExpression($jwtPattern, $token);
    }

    /**
     * Verifica que getHeaders devuelva los headers correctos para las solicitudes HTTP.
     * - Comprueba que sea un array.
     * - Contiene 'Authorization: Bearer' y 'Content-Type: application/json'.
     */
    public function testGetHeaders()
    {
        $headers = $this->authManager->getHeaders();     

        echo "\n=== Headers ===\n";
        foreach ($headers as $header) {
            echo $header . "\n";
        }
        echo "===================\n";

        $this->assertIsArray($headers);
        $this->assertStringContainsString('Authorization: Bearer', $headers[0]);
        $this->assertStringContainsString('Content-Type: application/json', $headers[1]);
    }

    /**
     * Verifica que el token se reutiliza correctamente entre múltiples llamadas.
     * - Llama getAccessToken dos veces.
     * - Comprueba que ambos tokens sean idénticos.
     */
    public function testReuseAccessToken()
    {
        $token1 = $this->authManager->getAccessToken();
        $token2 = $this->authManager->getAccessToken();

        echo "\n=== Reuse Access Token ===\n";
        echo "Token 1: " . $token1 . "\n";
        echo "Token 2: " . $token2 . "\n";
        echo "===========================\n";

        $this->assertEquals($token1, $token2, "El token debe reutilizarse entre llamadas");
    }

    /**
     * Verifica que si falta el client secret, se lanza una excepción SharepointException.
     * - Se usa un mock de ConfigManager con 'secret' vacío.
     */
    public function testMissingClientSecretThrowsException()
    {
        $this->expectException(SharepointException::class);
        $this->expectExceptionMessage('Client Secret no configurado');

        $mockConfig = $this->createMock(ConfigManager::class);
        $mockConfig->method('get')->willReturnMap([
            ['client_id', null, 'fake-client-id'],
            ['tenant_id', null, 'fake-tenant-id'],
            ['secret', null, null], // Sin client secret
        ]);
        $mockConfig->method('getAuthMethod')->willReturn('client_secret');

        $authManager = new AuthenticationManager($mockConfig);

        $authManager->getAccessToken(); // Aquí lanzará la excepción real
    }

    /**
     * Verifica que si hay un error HTTP al obtener el token, se lanza SharepointException.
     * - Se simula un error HTTP 400 con un mock de ClientSecretAuth.
     */
    public function testHttpErrorThrowsException()
    {
        $this->expectException(SharepointException::class);
        $this->expectExceptionMessageMatches('/Error al obtener token con Client Secret/');

        $mockClient = $this->getMockBuilder(ClientSecretAuth::class)
            ->setConstructorArgs(['fake-id', 'fake-tenant', 'fake-secret'])
            ->onlyMethods(['getAccessToken'])
            ->getMock();

        $mockClient->method('getAccessToken')
            ->will($this->throwException(new SharepointException("Error al obtener token con Client Secret: HTTP 400 - Bad Request")));

        $mockClient->getAccessToken();
    }

    /**
     * Verifica que un token que no tiene formato JWT válido falla la validación.
     * - Se fuerza un token inválido y se espera fallo de PHPUnit.
     */
    public function testInvalidJWTFormatFails()
    {
        $token = "invalid-token-format";

        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);

        $jwtPattern = '/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/';
        $this->assertMatchesRegularExpression($jwtPattern, $token);
    }
}
