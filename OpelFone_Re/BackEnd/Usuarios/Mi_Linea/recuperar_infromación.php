<?php
session_start();
require_once '../../Conexión/conexion.php';

if(!isset($_SESSION['ID_cliente'])){
    die("No hay sesión iniciada");
}

    if(isset($_POST['agregar'])){

        $telefono = $_POST['numero'];
        $id_cliente = $_SESSION['ID_cliente'];
    
        $sql = "INSERT INTO telefono
                (Numero, ID_cliente)
                VALUES (?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $telefono,
            $id_cliente
        ]);
        header("Location: ../../../FrontEnd/Usuarios/Mi_Linea.php");
        exit;
    }
?>