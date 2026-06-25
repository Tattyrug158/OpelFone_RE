<?php
session_start();
require_once '../../Conexión/conexion.php';

if(!isset($_SESSION['ID_cliente'])){
    die("No hay sesión iniciada");
}

    if(isset($_POST['eliminar'])){

        $telefono = $_POST['solicitar'];
        $id_cliente = $_SESSION['ID_cliente'];
    
        $sql = "DELETE FROM telefono WHERE Numero = ? AND ID_cliente = ?";        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$telefono, $id_cliente]);
        header("Location: ../../../FrontEnd/Usuarios/Mi_Linea.php");
        exit;
    }
?>