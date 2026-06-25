<?php

/**
 * Pruebas unitarias para la lógica de Registro (BackEnd/Sesion/registrar.php)
 *
 * registrar.php hace un INSERT en la tabla 'cliente'.
 * Probamos: validación de datos de entrada y que el password se hashea.
 */

use PHPUnit\Framework\TestCase;

class RegistrarTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Valida los campos del formulario de registro.
     * Replica las condiciones mínimas que debería tener registrar.php.
     *
     * @return array ['valido'=>bool, 'errores'=>string[]]
     */
    private function validarRegistro(array $post): array
    {
        $errores = [];

        if (empty(trim($post['nombre'] ?? ''))) {
            $errores[] = 'El nombre es obligatorio';
        }

        if (empty(trim($post['apellidos'] ?? ''))) {
            $errores[] = 'Los apellidos son obligatorios';
        }

        if (empty(trim($post['email'] ?? '')) || !filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }

        if (strlen($post['password'] ?? '') < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres';
        }

        return ['valido' => empty($errores), 'errores' => $errores];
    }

    /**
     * Simula la preparación de datos que registrar.php pasa al INSERT.
     */
    private function prepararDatosInsert(array $post): array
    {
        return [
            'nombre'    => trim($post['nombre']),
            'apellidos' => trim($post['apellidos']),
            'domicilio' => trim($post['domicilio'] ?? ''),
            'email'     => trim($post['email']),
            'password'  => password_hash($post['password'], PASSWORD_DEFAULT),
        ];
    }

    // -------------------------------------------------------------------------
    // Pruebas de validación
    // -------------------------------------------------------------------------

    /**
     * @test
     * Datos completos y válidos → pasa validación
     */
    public function testRegistroValidoConTodosLosCampos(): void
    {
        $post = [
            'nombre'    => 'Juan',
            'apellidos' => 'Pérez López',
            'domicilio' => 'Calle 5 #10',
            'email'     => 'juan@correo.com',
            'password'  => 'segura123',
        ];

        $resultado = $this->validarRegistro($post);

        $this->assertTrue($resultado['valido']);
        $this->assertEmpty($resultado['errores']);
    }

    /**
     * @test
     * Email inválido → debe reportar error de email
     */
    public function testRegistroFallaConEmailInvalido(): void
    {
        $post = [
            'nombre'    => 'Ana',
            'apellidos' => 'García',
            'email'     => 'no-es-un-email',
            'password'  => 'clave123',
        ];

        $resultado = $this->validarRegistro($post);

        $this->assertFalse($resultado['valido']);
        $this->assertContains('El email no es válido', $resultado['errores']);
    }

    /**
     * @test
     * Contraseña corta (menos de 6 chars) → debe reportar error
     */
    public function testRegistroFallaConPasswordCorta(): void
    {
        $post = [
            'nombre'    => 'Pedro',
            'apellidos' => 'Sánchez',
            'email'     => 'pedro@mail.com',
            'password'  => '123', // muy corta
        ];

        $resultado = $this->validarRegistro($post);

        $this->assertFalse($resultado['valido']);
        $this->assertContains(
            'La contraseña debe tener al menos 6 caracteres',
            $resultado['errores']
        );
    }

    /**
     * @test
     * Nombre vacío → debe reportar error
     */
    public function testRegistroFallaConNombreVacio(): void
    {
        $post = [
            'nombre'    => '   ', // solo espacios
            'apellidos' => 'Ramírez',
            'email'     => 'r@mail.com',
            'password'  => 'clave123',
        ];

        $resultado = $this->validarRegistro($post);

        $this->assertFalse($resultado['valido']);
        $this->assertContains('El nombre es obligatorio', $resultado['errores']);
    }

    /**
     * @test
     * Formulario vacío → múltiples errores
     */
    public function testRegistroVacioTieneMultiplesErrores(): void
    {
        $resultado = $this->validarRegistro([]);

        $this->assertFalse($resultado['valido']);
        $this->assertGreaterThanOrEqual(3, count($resultado['errores']));
    }

    // -------------------------------------------------------------------------
    // Pruebas de preparación del INSERT
    // -------------------------------------------------------------------------

    /**
     * @test
     * La contraseña en el INSERT debe ser un hash, no texto plano
     */
    public function testPasswordSeGuardaHasheada(): void
    {
        $post = [
            'nombre'    => 'María',
            'apellidos' => 'Torres',
            'domicilio' => 'Av. Central',
            'email'     => 'maria@mail.com',
            'password'  => 'miPassword',
        ];

        $datos = $this->prepararDatosInsert($post);

        // El hash no debe ser igual al texto plano
        $this->assertNotEquals('miPassword', $datos['password']);
        // Pero debe poder verificarse
        $this->assertTrue(password_verify('miPassword', $datos['password']));
    }

    /**
     * @test
     * Los espacios al inicio/final del nombre deben limpiarse (trim)
     */
    public function testNombreSeGuardaSinEspaciosExtra(): void
    {
        $post = [
            'nombre'    => '  Carlos  ',
            'apellidos' => '  Mendoza  ',
            'domicilio' => '',
            'email'     => 'c@mail.com',
            'password'  => 'pass123',
        ];

        $datos = $this->prepararDatosInsert($post);

        $this->assertEquals('Carlos',  $datos['nombre']);
        $this->assertEquals('Mendoza', $datos['apellidos']);
    }
}
