<?php

/**
 * Pruebas unitarias para la lógica de Login (BackEnd/Sesion/login.php)
 *
 * Como login.php mezcla lógica con sesión/BD, aquí extraemos y probamos
 * la lógica pura de autenticación que podemos aislar.
 */

use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers: simulamos la lógica de login.php de forma aislada
    // -------------------------------------------------------------------------

    /**
     * Replica la lógica de validación de credenciales de login.php:
     *   - Busca el usuario por email (mock)
     *   - Verifica la contraseña con password_verify
     *
     * Retorna: 'exito' | 'Contraseña incorrecta' | 'usuario_no_encontrado'
     */
    private function ejecutarLogin(array $postData, ?array $usuarioDB): string
    {
        $email    = $postData['email']    ?? '';
        $password = $postData['password'] ?? '';

        // Simula el resultado del SELECT en la BD
        $user = $usuarioDB;

        if ($user) {
            if (password_verify($password, $user['Contrasena_cliente'])) {
                return 'exito';
            } else {
                return 'Contraseña incorrecta';
            }
        } else {
            return 'usuario_no_encontrado';
        }
    }

    // -------------------------------------------------------------------------
    // Pruebas
    // -------------------------------------------------------------------------

    /**
     * @test
     * Credenciales correctas → debe devolver 'exito'
     */
    public function testLoginExitosoConCredencialesCorrectas(): void
    {
        $hash = password_hash('miPassword123', PASSWORD_DEFAULT);

        $usuarioDB = [
            'ID_cliente'         => 1,
            'Contrasena_cliente' => $hash,
        ];

        $resultado = $this->ejecutarLogin(
            ['email' => 'cliente@opel.com', 'password' => 'miPassword123'],
            $usuarioDB
        );

        $this->assertEquals('exito', $resultado);
    }

    /**
     * @test
     * Contraseña incorrecta → debe devolver 'Contraseña incorrecta'
     */
    public function testLoginFallaConPasswordIncorrecta(): void
    {
        $hash = password_hash('miPassword123', PASSWORD_DEFAULT);

        $usuarioDB = [
            'ID_cliente'         => 1,
            'Contrasena_cliente' => $hash,
        ];

        $resultado = $this->ejecutarLogin(
            ['email' => 'cliente@opel.com', 'password' => 'passwordMal'],
            $usuarioDB
        );

        $this->assertEquals('Contraseña incorrecta', $resultado);
    }

    /**
     * @test
     * Email no existe en BD → debe devolver 'usuario_no_encontrado'
     */
    public function testLoginFallaConEmailInexistente(): void
    {
        $resultado = $this->ejecutarLogin(
            ['email' => 'noexiste@opel.com', 'password' => 'cualquiera'],
            null // El SELECT no encontró usuario
        );

        $this->assertEquals('usuario_no_encontrado', $resultado);
    }

    /**
     * @test
     * Campos vacíos → debe devolver 'usuario_no_encontrado'
     */
    public function testLoginConCamposVacios(): void
    {
        $resultado = $this->ejecutarLogin(
            ['email' => '', 'password' => ''],
            null
        );

        $this->assertEquals('usuario_no_encontrado', $resultado);
    }

    /**
     * @test
     * password_hash() produce hashes distintos para la misma contraseña (salt aleatorio)
     */
    public function testPasswordHashEsDiferenteCadaVez(): void
    {
        $hash1 = password_hash('misma', PASSWORD_DEFAULT);
        $hash2 = password_hash('misma', PASSWORD_DEFAULT);

        $this->assertNotEquals($hash1, $hash2, 'Cada hash debe ser único por el salt');
        // Pero ambos deben verificarse correctamente
        $this->assertTrue(password_verify('misma', $hash1));
        $this->assertTrue(password_verify('misma', $hash2));
    }
}
