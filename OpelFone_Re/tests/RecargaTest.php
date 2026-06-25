<?php

/**
 * Pruebas unitarias para la lógica de Recarga (BackEnd/Usuarios/recarga.php)
 *
 * recarga.php calcula comisión (5%) y total_cobrado.
 * Extraemos esa lógica para probarla sin BD.
 */

use PHPUnit\Framework\TestCase;

class RecargaTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper: replica el cálculo de recarga.php
    // -------------------------------------------------------------------------

    /**
     * Calcula comisión (5%) y total_cobrado igual que recarga.php.
     *
     * @return array ['monto'=>float, 'comision'=>float, 'total_cobrado'=>float]
     */
    private function calcularRecarga(float $monto): array
    {
        $comision      = $monto * 0.05;
        $total_cobrado = $monto + $comision;

        return [
            'monto'         => $monto,
            'comision'      => $comision,
            'total_cobrado' => $total_cobrado,
        ];
    }

    /**
     * Valida que el POST tenga los campos obligatorios de recarga.php.
     */
    private function validarDatosRecarga(array $post): bool
    {
        return isset($post['recargar'])
            && isset($post['monto'])     && $post['monto'] > 0
            && isset($post['id_telefono'])
            && isset($post['id_metodo']);
    }

    // -------------------------------------------------------------------------
    // Pruebas de cálculo de comisión
    // -------------------------------------------------------------------------

    /**
     * @test
     * Recarga de $100 → comisión $5, total $105
     */
    public function testComisionEs5PorcientoDe100(): void
    {
        $resultado = $this->calcularRecarga(100.0);

        $this->assertEquals(5.0,   $resultado['comision'],      'Comisión debe ser 5');
        $this->assertEquals(105.0, $resultado['total_cobrado'], 'Total debe ser 105');
    }

    /**
     * @test
     * Recarga de $200 → comisión $10, total $210
     */
    public function testComisionEs5PorcientoDe200(): void
    {
        $resultado = $this->calcularRecarga(200.0);

        $this->assertEquals(10.0,  $resultado['comision']);
        $this->assertEquals(210.0, $resultado['total_cobrado']);
    }

    /**
     * @test
     * Recarga de $50 → comisión $2.50, total $52.50
     */
    public function testComisionConMontoFraccionario(): void
    {
        $resultado = $this->calcularRecarga(50.0);

        $this->assertEquals(2.5,  $resultado['comision']);
        $this->assertEquals(52.5, $resultado['total_cobrado']);
    }

    /**
     * @test
     * total_cobrado = monto + comision siempre
     */
    public function testTotalCobradoEsSiempreMontoMasComision(): void
    {
        foreach ([10, 75, 150, 500, 999.99] as $monto) {
            $r = $this->calcularRecarga((float)$monto);
            $this->assertEquals(
                round($r['monto'] + $r['comision'], 10),
                round($r['total_cobrado'], 10),
                "Falla con monto=$monto"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Pruebas de validación de campos POST
    // -------------------------------------------------------------------------

    /**
     * @test
     * POST completo y válido → pasa validación
     */
    public function testValidacionPostCompletoEsValido(): void
    {
        $post = [
            'recargar'    => '1',
            'monto'       => 100,
            'id_telefono' => 5,
            'id_metodo'   => 2,
        ];

        $this->assertTrue($this->validarDatosRecarga($post));
    }

    /**
     * @test
     * Sin 'recargar' → falla validación
     */
    public function testValidacionFallaSinCampoRecargar(): void
    {
        $post = ['monto' => 100, 'id_telefono' => 5, 'id_metodo' => 2];

        $this->assertFalse($this->validarDatosRecarga($post));
    }

    /**
     * @test
     * Monto 0 → falla validación (no tiene sentido recargar $0)
     */
    public function testValidacionFallaConMontoEnCero(): void
    {
        $post = [
            'recargar'    => '1',
            'monto'       => 0,
            'id_telefono' => 5,
            'id_metodo'   => 2,
        ];

        $this->assertFalse($this->validarDatosRecarga($post));
    }

    /**
     * @test
     * Sin id_telefono → falla validación
     */
    public function testValidacionFallaSinIdTelefono(): void
    {
        $post = ['recargar' => '1', 'monto' => 100, 'id_metodo' => 2];

        $this->assertFalse($this->validarDatosRecarga($post));
    }
}
