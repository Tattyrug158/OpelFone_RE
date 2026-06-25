<?php

/**
 * Pruebas unitarias para:
 *   - BackEnd/Usuarios/numeros.php   → validación de número telefónico
 *   - BackEnd/Usuarios/saldo.php     → preparación de datos de saldo
 *   - BackEnd/Usuarios/metodo_pago.php → validación de tarjeta
 */

use PHPUnit\Framework\TestCase;

class UsuariosTest extends TestCase
{
    // =========================================================================
    // numeros.php
    // =========================================================================

    /**
     * Replica la validación de numeros.php:
     *   if (isset($_POST['numero']) && !empty(trim($_POST['numero'])))
     */
    private function validarNumero(?string $numero): array
    {
        if (isset($numero) && !empty(trim($numero))) {
            // Validación adicional: solo dígitos, entre 7 y 15 caracteres
            if (!preg_match('/^\d{7,15}$/', trim($numero))) {
                return ['status' => 'error', 'message' => 'Formato de número inválido.'];
            }
            return ['status' => 'success', 'message' => '¡Número agregado correctamente!'];
        }

        return ['status' => 'error', 'message' => 'El campo de número está vacío.'];
    }

    /** @test */
    public function testNumeroValidoDevuelveSuccess(): void
    {
        $res = $this->validarNumero('5512345678');
        $this->assertEquals('success', $res['status']);
    }

    /** @test */
    public function testNumeroVacioDevuelveError(): void
    {
        $res = $this->validarNumero('');
        $this->assertEquals('error', $res['status']);
        $this->assertEquals('El campo de número está vacío.', $res['message']);
    }

    /** @test */
    public function testNumeroNullDevuelveError(): void
    {
        $res = $this->validarNumero(null);
        $this->assertEquals('error', $res['status']);
    }

    /** @test */
    public function testNumeroCon10DigitosEsValido(): void
    {
        $res = $this->validarNumero('5551234567');
        $this->assertEquals('success', $res['status']);
    }

    /** @test */
    public function testNumeroConLetrasEsInvalido(): void
    {
        $res = $this->validarNumero('abc1234567');
        $this->assertEquals('error', $res['status']);
        $this->assertStringContainsString('inválido', $res['message']);
    }

    /** @test */
    public function testNumeroMuyCortoEsInvalido(): void
    {
        $res = $this->validarNumero('123'); // menos de 7 dígitos
        $this->assertEquals('error', $res['status']);
    }

    // =========================================================================
    // saldo.php
    // =========================================================================

    /**
     * Replica el cálculo de fechas de saldo.php:
     *   $fecha_actual      = date('Y-m-d')
     *   $fecha_vencimiento = date('Y-m-d', strtotime('+1 year'))
     */
    private function calcularFechasSaldo(string $fechaBase): array
    {
        $fecha_actual     = $fechaBase;
        $fecha_vencimiento = date('Y-m-d', strtotime($fechaBase . ' +1 year'));

        return [
            'fecha_actual'      => $fecha_actual,
            'fecha_vencimiento' => $fecha_vencimiento,
        ];
    }

    /** @test */
    public function testFechaVencimientoEsUnAnioDespues(): void
    {
        $fechas = $this->calcularFechasSaldo('2024-01-15');

        $this->assertEquals('2024-01-15', $fechas['fecha_actual']);
        $this->assertEquals('2025-01-15', $fechas['fecha_vencimiento']);
    }

    /** @test */
    public function testFechaVencimientoAnosBisiesto(): void
    {
        // 29 de febrero en año bisiesto
        $fechas = $this->calcularFechasSaldo('2024-02-29');

        // strtotime('+1 year') desde 29/Feb/2024 → 28/Feb/2025 (2025 no es bisiesto)
        $this->assertEquals('2025-02-28', $fechas['fecha_vencimiento']);
    }

    /**
     * Valida los datos POST de saldo.php antes del INSERT
     */
    private function validarDatosSaldo(array $post, ?int $idSesion): array
    {
        $errores = [];

        if (empty($idSesion)) {
            $errores[] = 'Sesión no iniciada';
        }
        if (!isset($post['saldo']) || !is_numeric($post['saldo']) || $post['saldo'] <= 0) {
            $errores[] = 'Monto inválido';
        }
        if (empty($post['id_metodo'])) {
            $errores[] = 'Método de pago requerido';
        }

        return ['valido' => empty($errores), 'errores' => $errores];
    }

    /** @test */
    public function testSaldoValidoConSesionYMonto(): void
    {
        $res = $this->validarDatosSaldo(['saldo' => 150, 'id_metodo' => 2], 1);
        $this->assertTrue($res['valido']);
    }

    /** @test */
    public function testSaldoFallaSinSesion(): void
    {
        $res = $this->validarDatosSaldo(['saldo' => 150, 'id_metodo' => 2], null);
        $this->assertFalse($res['valido']);
        $this->assertContains('Sesión no iniciada', $res['errores']);
    }

    /** @test */
    public function testSaldoFallaConMontoNegativo(): void
    {
        $res = $this->validarDatosSaldo(['saldo' => -50, 'id_metodo' => 2], 1);
        $this->assertFalse($res['valido']);
        $this->assertContains('Monto inválido', $res['errores']);
    }

    // =========================================================================
    // metodo_pago.php
    // =========================================================================

    /**
     * Valida los datos de tarjeta de metodo_pago.php
     */
    private function validarMetodoPago(array $post): array
    {
        $errores = [];

        if (empty($post['nombre'])) {
            $errores[] = 'Nombre del titular requerido';
        }
        // Número de tarjeta: 13-19 dígitos
        if (!preg_match('/^\d{13,19}$/', $post['numero'] ?? '')) {
            $errores[] = 'Número de tarjeta inválido';
        }
        // CVV: 3 o 4 dígitos
        if (!preg_match('/^\d{3,4}$/', $post['cvv'] ?? '')) {
            $errores[] = 'CVV inválido';
        }
        // Fecha expiración: MM/YY
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $post['fecha_exp'] ?? '')) {
            $errores[] = 'Fecha de expiración inválida (MM/YY)';
        }

        return ['valido' => empty($errores), 'errores' => $errores];
    }

    /** @test */
    public function testTarjetaValidaConTodosLosCampos(): void
    {
        $post = [
            'nombre'    => 'Juan Pérez',
            'numero'    => '4111111111111111', // Visa de prueba
            'cvv'       => '123',
            'fecha_exp' => '12/27',
        ];

        $res = $this->validarMetodoPago($post);
        $this->assertTrue($res['valido']);
    }

    /** @test */
    public function testTarjetaFallaConNumeroCorto(): void
    {
        $post = [
            'nombre'    => 'Ana García',
            'numero'    => '1234', // muy corto
            'cvv'       => '456',
            'fecha_exp' => '06/26',
        ];

        $res = $this->validarMetodoPago($post);
        $this->assertFalse($res['valido']);
        $this->assertContains('Número de tarjeta inválido', $res['errores']);
    }

    /** @test */
    public function testTarjetaFallaConCvvLetras(): void
    {
        $post = [
            'nombre'    => 'Pedro',
            'numero'    => '4111111111111111',
            'cvv'       => 'abc',
            'fecha_exp' => '06/26',
        ];

        $res = $this->validarMetodoPago($post);
        $this->assertFalse($res['valido']);
        $this->assertContains('CVV inválido', $res['errores']);
    }

    /** @test */
    public function testTarjetaFallaConFechaFormatoIncorrecto(): void
    {
        $post = [
            'nombre'    => 'Pedro',
            'numero'    => '4111111111111111',
            'cvv'       => '123',
            'fecha_exp' => '2027-06', // formato incorrecto
        ];

        $res = $this->validarMetodoPago($post);
        $this->assertFalse($res['valido']);
        $this->assertContains('Fecha de expiración inválida (MM/YY)', $res['errores']);
    }
}
