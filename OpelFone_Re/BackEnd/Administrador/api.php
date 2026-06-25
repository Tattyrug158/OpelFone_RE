<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");

ini_set('display_errors', 1);
error_reporting(E_ALL);

$serverName = "localhost";
$database   = "opelfone"; 
$username   = "root";
$password   = "";

try {
    $conn = new PDO("mysql:host=$serverName;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$data = json_decode(file_get_contents("php://input"), true);

switch ($accion) {

    // =========================================================================
    // MODULO 1: CONTROL DE USUARIOS (CLIENTES)
    // =========================================================================
    case 'listar_usuarios':
        try {
            $sql = "SELECT ID_cliente, Nombre, Apellidos, Domicilio, Email_C, Estado, Contrasena_cliente, Informacion_Bancaria,
                           '2026-06-24' AS Fecha_registro 
                    FROM cliente";
            $stmt = $conn->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            try {
                $sqlRespaldo = "SELECT ID_cliente, Nombre, Apellidos, Domicilio, Email_C, 'Activo' AS Estado, 'Tarjeta' AS Informacion_Bancaria FROM cliente";
                $stmt = $conn->query($sqlRespaldo);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (PDOException $ex) {
                echo json_encode([]);
            }
        }
        break;

    case 'insertar_usuario':
        try {
            $passwordEncriptada = password_hash($data['password'], PASSWORD_BCRYPT);
            $sql = "INSERT INTO cliente (Nombre, Apellidos, Domicilio, Email_C, Estado, Contrasena_cliente, Informacion_Bancaria) 
                    VALUES (:nombre, :apellidos, :direccion, :email, 'Activo', :password, :banco)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nombre' => $data['nombre'], ':apellidos' => $data['apellidos'],
                ':direccion' => $data['direccion'], ':email' => $data['email'], 
                ':password' => $passwordEncriptada, ':banco' => $data['banco']
            ]);
            echo json_encode(["status" => "success", "message" => "Cliente registrado con éxito."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al insertar usuario: " . $e->getMessage()]);
        }
        break;

    case 'actualizar_usuario':
        try {
            $banco = isset($data['banco']) ? $data['banco'] : 'Tarjeta';
            $estado = isset($data['estado']) ? $data['estado'] : 'Activo';
            $sql = "UPDATE cliente SET Nombre = :nombre, Apellidos = :apellidos, Domicilio = :direccion, Email_C = :email, Estado = :estado, Informacion_Bancaria = :banco WHERE ID_cliente = :id_cliente";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nombre' => $data['nombre'], ':apellidos' => $data['apellidos'], ':direccion' => $data['direccion'],
                ':email' => $data['email'], ':estado' => $estado, ':banco' => $banco, ':id_cliente' => $data['id_cliente']
            ]);
            echo json_encode(["status" => "success", "message" => "Registro actualizado."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al actualizar: " . $e->getMessage()]);
        }
        break;

    case 'eliminar_usuario':
        try {
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            $stmt = $conn->prepare("DELETE FROM cliente WHERE ID_cliente = ?");
            $stmt->execute([$data['id_cliente']]);
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            echo json_encode(["status" => "success", "message" => "Cliente eliminado con éxito."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al eliminar: " . $e->getMessage()]);
        }
        break;

    // =========================================================================
    // MODULO 2: CONTROL Y REGISTRO DE DISPOSITIVOS
    // =========================================================================
    case 'listar_dispositivos':
        try {
            $sql = "SELECT t.ID_Telefono, t.Numero, t.Saldo, t.Estado_activo, 
                           COALESCE(ct.ID_cliente, 'Sin asignar') AS ID_cliente,
                           COALESCE(ct.Fecha_alta, 'N/A') AS Fecha_alta,
                           COALESCE(CONCAT(c.Nombre, ' ', c.Apellidos), 'Usuario General') AS Propietario
                    FROM telefono t
                    LEFT JOIN cliente_telefono ct ON t.ID_Telefono = ct.ID_Telefono
                    LEFT JOIN cliente c ON ct.ID_cliente = c.ID_cliente";
            $stmt = $conn->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            echo json_encode([]);
        }
        break;

    case 'insertar_dispositivo':
    case 'vincular_dispositivo':
        try {
            $id_cliente  = isset($data['id_cliente']) ? $data['id_cliente'] : null;
            $id_telefono = isset($data['id_telefono']) ? $data['id_telefono'] : null;
            $numero      = isset($data['numero']) ? $data['numero'] : '';
            $saldo       = isset($data['saldo']) ? $data['saldo'] : 0;
            $estado      = isset($data['estado']) ? $data['estado'] : 1;

            if (empty($id_cliente) || empty($id_telefono)) {
                echo json_encode(["status" => "error", "message" => "IDs de Cliente o Hardware inválidos."]);
                exit;
            }

            $conn->query("SET FOREIGN_KEY_CHECKS = 0");

            $sqlTel = "INSERT INTO telefono (ID_Telefono, Numero, Saldo, Estado_activo) 
                       VALUES (:id_tel, :num, :saldo, :estado)
                       ON DUPLICATE KEY UPDATE Numero = :num, Saldo = :saldo, Estado_activo = :estado";
            $stmtTel = $conn->prepare($sqlTel);
            $stmtTel->execute([':id_tel' => $id_telefono, ':num' => $numero, ':saldo' => $saldo, ':estado' => $estado]);

            $del = $conn->prepare("DELETE FROM cliente_telefono WHERE ID_Telefono = ?");
            $del->execute([$id_telefono]);

            $ins = $conn->prepare("INSERT INTO cliente_telefono (ID_cliente, ID_Telefono, Fecha_alta) VALUES (?, ?, NOW())");
            $ins->execute([$id_cliente, $id_telefono]);

            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            echo json_encode(["status" => "success", "message" => "Línea dada de alta y vinculada correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error de base de datos: " . $e->getMessage()]);
        }
        break;

    case 'actualizar_dispositivo':
        try {
            $id_telefono = $data['id_telefono'];
            $id_cliente  = $data['id_cliente'];
            $numero      = $data['numero'];
            $saldo       = $data['saldo'];
            $estado      = $data['estado'];

            $conn->query("SET FOREIGN_KEY_CHECKS = 0");

            $sql = "UPDATE telefono SET Numero = :numero, Saldo = :saldo, Estado_activo = :estado WHERE ID_Telefono = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':numero' => $numero, ':saldo' => $saldo, ':estado' => $estado, ':id' => $id_telefono]);

            if (!empty($id_cliente)) {
                $del = $conn->prepare("DELETE FROM cliente_telefono WHERE ID_Telefono = ?");
                $del->execute([$id_telefono]);

                $ins = $conn->prepare("INSERT INTO cliente_telefono (ID_cliente, ID_Telefono, Fecha_alta) VALUES (?, ?, NOW())");
                $ins->execute([$id_cliente, $id_telefono]);
            }

            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            echo json_encode(["status" => "success", "message" => "Configuración de línea actualizada."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al actualizar: " . $e->getMessage()]);
        }
        break;

    case 'eliminar_dispositivo':
        try {
            $id = $data['id_telefono'];
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            $conn->prepare("DELETE FROM cliente_telefono WHERE ID_Telefono = ?")->execute([$id]);
            $conn->prepare("DELETE FROM telefono WHERE ID_Telefono = ?")->execute([$id]);
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            echo json_encode(["status" => "success", "message" => "Línea eliminada correctamente."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Error al dar de baja: " . $e->getMessage()]);
        }
        break;

    // =========================================================================
    // MODULO 3: DIAGNÓSTICO DE SISTEMAS (Métricas 100% operacionales)
    // =========================================================================
    case 'datos_sistema':
        try {
            // 1. Calcular el saldo de forma ultra-segura
            $stmtSaldo = $conn->query("SELECT SUM(Saldo) AS total FROM telefono");
            $resultadoSaldo = $stmtSaldo->fetch(PDO::FETCH_ASSOC);
            $saldoTotal = ($resultadoSaldo && $resultadoSaldo['total']) ? floatval($resultadoSaldo['total']) : 0.0;
            $txtSaldoCirculante = "$" . number_format($saldoTotal, 2);

            // 2. Usuarios Activos e Inactivos
            $totalClientes = intval($conn->query("SELECT COUNT(*) FROM cliente WHERE Estado = 'Activo'")->fetchColumn());
            $totalInactivos = intval($conn->query("SELECT COUNT(*) FROM cliente WHERE Estado != 'Activo'")->fetchColumn());

            // 3. Total de líneas / hardware
            $totalLineas = intval($conn->query("SELECT COUNT(*) FROM telefono")->fetchColumn());

            // 4. Líneas inactivas o suspendidas (Estado_activo = 0)
            $lineasInactivas = intval($conn->query("SELECT COUNT(*) FROM telefono WHERE Estado_activo = 0")->fetchColumn());

            // 5. Enviamos la respuesta limpia al frontend
            echo json_encode([
                "status" => "success",
                "saldo_circulante" => $txtSaldoCirculante,
                "crecimiento_usuarios" => "+" . $totalClientes . " Activos",
                "dispositivos_total" => $totalLineas . " Líneas",
                "errores" => $lineasInactivas,
                "grafica" => [
                    "Clientes Activos" => $totalClientes,
                    "Clientes Inactivos" => $totalInactivos,
                    "Líneas Totales" => $totalLineas,
                    "Líneas Suspendidas" => $lineasInactivas
                ]
            ]);
        } catch (\Exception $e) {
            // Si algo falla, este bloque nos dirá exactamente qué pasó en lugar de dejar la pantalla en blanco
            echo json_encode([
                "status" => "error", 
                "message" => $e->getMessage()
            ]);
        }
        break;
           default:
        echo json_encode(["status" => "error", "message" => "Acción no válida."]);
        break;
}