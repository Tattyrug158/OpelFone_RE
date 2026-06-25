<?php
session_start();
require_once '../../Conexión/conexion.php';



if(!isset($_SESSION['ID_cliente'])){
    die("No hay sesión iniciada");
}

if(isset($_POST['numero']) && isset($_POST['estado'])){
    $numero = $_POST['numero'];
    $estado = $_POST['estado']; 
    $id_cliente = $_SESSION['ID_cliente'];

    $sql = "UPDATE telefono SET Estado_activo = ? 
            WHERE Numero = ? AND ID_cliente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$estado, $numero, $id_cliente]);

    header("Location: ../../../FrontEnd/Usuarios/Mi_Linea.php");
    exit;
}
?>