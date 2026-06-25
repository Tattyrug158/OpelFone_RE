# Pruebas Unitarias – OpelFone

## Archivos generados

```
OpelFone_Re/
├── composer.json          ← dependencias (PHPUnit)
├── phpunit.xml            ← configuración de PHPUnit
└── tests/
    ├── LoginTest.php       ← pruebas de autenticación
    ├── RegistrarTest.php   ← pruebas de registro de clientes
    ├── RecargaTest.php     ← pruebas de comisión y validación de recarga
    ├── ApiAdminTest.php    ← pruebas de la API del administrador
    └── UsuariosTest.php    ← pruebas de número, saldo y método de pago
```

---

## Instalación (una sola vez)

### 1. Instalar Composer (si no lo tienes)
```bash
# Windows: descargar en https://getcomposer.org/Composer-Setup.exe
# Mac/Linux:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Instalar PHPUnit
```bash
# Desde la carpeta OpelFone_Re/
composer install
```

---

## Ejecutar las pruebas

```bash
# Todas las pruebas
./vendor/bin/phpunit

# Un archivo específico
./vendor/bin/phpunit tests/LoginTest.php

# Con detalle de cada prueba
./vendor/bin/phpunit --testdox

# En Windows
vendor\bin\phpunit
```

---

## Resumen de pruebas por archivo

### LoginTest.php (5 pruebas)
| Prueba | Qué verifica |
|--------|-------------|
| `testLoginExitosoConCredencialesCorrectas` | Credenciales correctas → `exito` |
| `testLoginFallaConPasswordIncorrecta` | Password mal → `Contraseña incorrecta` |
| `testLoginFallaConEmailInexistente` | Email no existe → `usuario_no_encontrado` |
| `testLoginConCamposVacios` | POST vacío → `usuario_no_encontrado` |
| `testPasswordHashEsDiferenteCadaVez` | Cada hash es único (salt) |

### RegistrarTest.php (7 pruebas)
| Prueba | Qué verifica |
|--------|-------------|
| `testRegistroValidoConTodosLosCampos` | Datos completos → válido |
| `testRegistroFallaConEmailInvalido` | Email sin @ → error |
| `testRegistroFallaConPasswordCorta` | < 6 chars → error |
| `testRegistroFallaConNombreVacio` | Solo espacios → error |
| `testRegistroVacioTieneMultiplesErrores` | POST vacío → múltiples errores |
| `testPasswordSeGuardaHasheada` | El INSERT guarda hash, no texto plano |
| `testNombreSeGuardaSinEspaciosExtra` | trim() aplicado correctamente |

### RecargaTest.php (7 pruebas)
| Prueba | Qué verifica |
|--------|-------------|
| `testComisionEs5PorcientoDe100` | $100 → comisión $5, total $105 |
| `testComisionEs5PorcientoDe200` | $200 → comisión $10, total $210 |
| `testComisionConMontoFraccionario` | $50 → comisión $2.50 |
| `testTotalCobradoEsSiempreMontoMasComision` | 5 montos distintos verificados |
| `testValidacionPostCompletoEsValido` | POST completo → válido |
| `testValidacionFallaSinCampoRecargar` | Sin `recargar` → inválido |
| `testValidacionFallaConMontoEnCero` | Monto 0 → inválido |

### ApiAdminTest.php (10 pruebas)
| Prueba | Qué verifica |
|--------|-------------|
| `testInsertarUsuarioConDatosCompletosEsValido` | Datos OK → válido |
| `testInsertarUsuarioSinEmailFalla` | Email inválido → error |
| `testVincularDispositivoConAmbosIdsEsValido` | ID cliente + ID teléfono → válido |
| `testVincularDispositivoSinIdClienteFalla` | Sin id_cliente → inválido |
| `testAccionesValidasSeReconocenCorrectamente` | Switch reconoce acciones |
| `testAccionDesconocidaDevuelveAccionInvalida` | Acción inexistente → error |
| `testSaldoCirculanteSeFormateaCorrectamente` | Formato `$1,500.00` |
| `testEliminarUsuarioEjecutaDeleteCorrectamente` | Mock PDO → DELETE correcto |
| `testInsertarUsuarioHasheaPasswordAntesDeGuardar` | Hash bcrypt verificado |

### UsuariosTest.php (12 pruebas)
Cubre numeros.php, saldo.php y metodo_pago.php con validaciones de formato.

---

## Resultado esperado al correr todas las pruebas

```
PHPUnit 10.x

.....................   41 / 41 (100%)

Time: 00:00.050, Memory: 6.00 MB

OK (41 tests, 65 assertions)
```
