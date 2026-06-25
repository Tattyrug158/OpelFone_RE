<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");

$serverName = "localhost";
$database   = "opelfone"; 
$username   = "root";
$password   = "";

try {
    $conn = new PDO("mysql:host=$serverName;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de conexión"]);
    exit;
}

$accion = $_GET['accion'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

switch ($accion) {
    case 'listar_usuarios':
        $sql = "SELECT ID_cliente, Nombre, Apellidos, Domicilio, Email_C, Estado, Informacion_Bancaria FROM cliente";
        $stmt = $conn->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'insertar_usuario':
        $sql = "INSERT INTO cliente (Nombre, Apellidos, Domicilio, Email_C, Contrasena_cliente, Informacion_Bancaria) 
                VALUES (:nombre, :apellidos, :direccion, :email, :pass, :banco)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'], ':apellidos' => $data['apellidos'],
            ':direccion' => $data['direccion'], ':email' => $data['email'],
            ':pass' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':banco' => $data['banco']
        ]);
        echo json_encode(["status" => "success", "message" => "Usuario registrado"]);
        break;

    case 'actualizar_usuario':
        $sql = "UPDATE cliente SET Nombre=:nombre, Apellidos=:apellidos, Domicilio=:direccion, Email_C=:email, Estado=:estado, Informacion_Bancaria=:banco WHERE ID_cliente=:id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nombre' => $data['nombre'], ':apellidos' => $data['apellidos'], ':direccion' => $data['direccion'],
            ':email' => $data['email'], ':estado' => $data['estado'], ':banco' => $data['banco'], ':id' => $data['id_cliente']
        ]);
        echo json_encode(["status" => "success", "message" => "Datos actualizados"]);
        break;

    case 'eliminar_usuario':
        $stmt = $conn->prepare("DELETE FROM cliente WHERE ID_cliente = ?");
        $stmt->execute([$data['id_cliente']]);
        echo json_encode(["status" => "success", "message" => "Usuario eliminado"]);
        break;
}