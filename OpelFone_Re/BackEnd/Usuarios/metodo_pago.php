<?php
session_start();
require_once '../Conexión/conexion.php';

if (!isset($_SESSION['ID_cliente'])) {
    die("Acceso no autorizado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['ID_cliente']; 
    $nombre = $_POST['nombre'];
    $numero = $_POST['numero'];
    $cvv = $_POST['cvv'];
    $fecha_exp = $_POST['fecha_exp'];

    try {
        $sql = "INSERT INTO metodo_pago (nombre, numero, ID_usuario, cvv, fecha_exp) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $numero, $id_usuario, $cvv, $fecha_exp]);

        echo "Método de pago guardado exitosamente.";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>