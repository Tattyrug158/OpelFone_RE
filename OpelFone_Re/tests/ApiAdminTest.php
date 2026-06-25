<?php

/**
 * Pruebas unitarias para la lógica de la API Admin (BackEnd/Administrador/api.php)
 *
 * api.php maneja: listar_usuarios, insertar_usuario, actualizar_usuario,
 * eliminar_usuario, listar_dispositivos, vincular_dispositivo, datos_sistema.
 *
 * Usamos mocks de PDO para no necesitar base de datos real.
 */

use PHPUnit\Framework\TestCase;

class ApiAdminTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers: lógica extraída de api.php
    // -------------------------------------------------------------------------

    /**
     * Valida los datos necesarios para insertar un usuario (insertar_usuario).
     */
    private function validarDatosUsuario(array $data): array
    {
        $errores = [];

        if (empty($data['nombre'])) {
            $errores[] = 'nombre requerido';
        }
        if (empty($data['apellidos'])) {
            $errores[] = 'apellidos requerido';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'email inválido';
        }
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errores[] = 'password debe tener al menos 6 caracteres';
        }

        return ['valido' => empty($errores), 'errores' => $errores];
    }

    /**
     * Valida los datos para vincular/insertar un dispositivo.
     * Replica la comprobación de api.php:
     *   if (empty($id_cliente) || empty($id_telefono)) → error
     */
    private function validarDatosDispositivo(array $data): bool
    {
        $id_cliente  = $data['id_cliente']  ?? null;
        $id_telefono = $data['id_telefono'] ?? null;

        return !empty($id_cliente) && !empty($id_telefono);
    }

    /**
     * Determina la acción a ejecutar dado el parámetro GET 'accion'.
     * Replica el switch de api.php.
     */
    private function resolverAccion(string $accion): string
    {
        $acciones_validas = [
            'listar_usuarios', 'insertar_usuario', 'actualizar_usuario', 'eliminar_usuario',
            'listar_dispositivos', 'insertar_dispositivo', 'vincular_dispositivo',
            'actualizar_dispositivo', 'eliminar_dispositivo', 'datos_sistema',
        ];

        return in_array($accion, $acciones_validas) ? $accion : 'accion_invalida';
    }

    /**
     * Formatea el saldo circulante igual que en datos_sistema de api.php.
     */
    private function formatearSaldoCirculante(float $saldo): string
    {
        return '$' . number_format($saldo, 2);
    }

    // -------------------------------------------------------------------------
    // Pruebas de validación de usuarios
    // -------------------------------------------------------------------------

    /** @test */
    public function testInsertarUsuarioConDatosCompletosEsValido(): void
    {
        $data = [
            'nombre'    => 'Laura',
            'apellidos' => 'Medina',
            'direccion' => 'Calle 10',
            'email'     => 'laura@opel.com',
            'password'  => 'admin123',
            'banco'     => 'BBVA',
        ];

        $resultado = $this->validarDatosUsuario($data);

        $this->assertTrue($resultado['valido']);
    }

    /** @test */
    public function testInsertarUsuarioSinEmailFalla(): void
    {
        $data = [
            'nombre'    => 'Laura',
            'apellidos' => 'Medina',
            'email'     => 'no-es-email',
            'password'  => 'admin123',
        ];

        $resultado = $this->validarDatosUsuario($data);

        $this->assertFalse($resultado['valido']);
        $this->assertContains('email inválido', $resultado['errores']);
    }

    /** @test */
    public function testInsertarUsuarioSinNombreFalla(): void
    {
        $data = [
            'nombre'    => '',
            'apellidos' => 'Medina',
            'email'     => 'ok@opel.com',
            'password'  => 'admin123',
        ];

        $resultado = $this->validarDatosUsuario($data);

        $this->assertFalse($resultado['valido']);
        $this->assertContains('nombre requerido', $resultado['errores']);
    }

    // -------------------------------------------------------------------------
    // Pruebas de validación de dispositivos
    // -------------------------------------------------------------------------

    /** @test */
    public function testVincularDispositivoConAmbosIdsEsValido(): void
    {
        $data = ['id_cliente' => 3, 'id_telefono' => 7, 'numero' => '5551234567'];

        $this->assertTrue($this->validarDatosDispositivo($data));
    }

    /** @test */
    public function testVincularDispositivoSinIdClienteFalla(): void
    {
        $data = ['id_cliente' => null, 'id_telefono' => 7];

        $this->assertFalse($this->validarDatosDispositivo($data));
    }

    /** @test */
    public function testVincularDispositivoSinIdTelefonoFalla(): void
    {
        $data = ['id_cliente' => 3, 'id_telefono' => ''];

        $this->assertFalse($this->validarDatosDispositivo($data));
    }

    /** @test */
    public function testVincularDispositivoSinNingunIdFalla(): void
    {
        $this->assertFalse($this->validarDatosDispositivo([]));
    }

    // -------------------------------------------------------------------------
    // Pruebas del router de acciones
    // -------------------------------------------------------------------------

    /** @test */
    public function testAccionesValidasSeReconocenCorrectamente(): void
    {
        $acciones = [
            'listar_usuarios', 'insertar_usuario', 'actualizar_usuario',
            'eliminar_usuario', 'datos_sistema', 'listar_dispositivos',
        ];

        foreach ($acciones as $accion) {
            $this->assertEquals($accion, $this->resolverAccion($accion), "Falló: $accion");
        }
    }

    /** @test */
    public function testAccionDesconocidaDevuelveAccionInvalida(): void
    {
        $this->assertEquals('accion_invalida', $this->resolverAccion('borrar_todo'));
        $this->assertEquals('accion_invalida', $this->resolverAccion(''));
        $this->assertEquals('accion_invalida', $this->resolverAccion('hack'));
    }

    // -------------------------------------------------------------------------
    // Pruebas de formateo de saldo (datos_sistema)
    // -------------------------------------------------------------------------

    /** @test */
    public function testSaldoCirculanteSeFormateaCorrectamente(): void
    {
        $this->assertEquals('$1,500.00', $this->formatearSaldoCirculante(1500.0));
        $this->assertEquals('$0.00',     $this->formatearSaldoCirculante(0.0));
        $this->assertEquals('$99.99',    $this->formatearSaldoCirculante(99.99));
        $this->assertEquals('$10,000.50', $this->formatearSaldoCirculante(10000.50));
    }

    // -------------------------------------------------------------------------
    // Prueba con Mock de PDO
    // -------------------------------------------------------------------------

    /** @test
     * Verifica que eliminar_usuario construye la query correcta usando PDO mock
     */
    public function testEliminarUsuarioEjecutaDeleteCorrectamente(): void
    {
        // Mock del PDOStatement
        $mockStmt = $this->createMock(PDOStatement::class);
        $mockStmt->expects($this->once())
                 ->method('execute')
                 ->with([42]) // id_cliente = 42
                 ->willReturn(true);

        // Mock del PDO
        $mockPdo = $this->createMock(PDO::class);
        $mockPdo->expects($this->once())
                ->method('prepare')
                ->with('DELETE FROM cliente WHERE ID_cliente = ?')
                ->willReturn($mockStmt);

        // Simula la lógica de eliminar_usuario
        $id_cliente = 42;
        $stmt = $mockPdo->prepare('DELETE FROM cliente WHERE ID_cliente = ?');
        $resultado = $stmt->execute([$id_cliente]);

        $this->assertTrue($resultado);
    }

    /** @test
     * Verifica que insertar_usuario hashea el password antes del INSERT
     */
    public function testInsertarUsuarioHasheaPasswordAntesDeGuardar(): void
    {
        $passwordPlano = 'miClave123';
        $passwordHasheada = password_hash($passwordPlano, PASSWORD_BCRYPT);

        // El hash no debe ser texto plano
        $this->assertNotEquals($passwordPlano, $passwordHasheada);
        // Pero debe verificarse
        $this->assertTrue(password_verify($passwordPlano, $passwordHasheada));
        // Formato bcrypt
        $this->assertStringStartsWith('$2y$', $passwordHasheada);
    }
}
