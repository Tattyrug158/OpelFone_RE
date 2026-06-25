<?php
session_start();
require_once '../Conexión/conexion.php';

if (isset($_POST['recargar'])) {
    $monto = $_POST['monto'];
    $id_telefono = $_POST['id_telefono'];
    $id_metodo = $_POST['id_metodo'];
    
    $comision = $monto * 0.05;
    $total_cobrado = $monto + $comision;
    $fecha = date('Y-m-d H:i:s');
    $tipo_horario = 'Normal'; 

    try {
        $sql = "INSERT INTO recarga (Monto, Fecha, Tipo_horario, Comision, Total_cobrado, Id_telefono, Id_metodo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $monto, 
            $fecha, 
            $tipo_horario, 
            $comision, 
            $total_cobrado, 
            $id_telefono, 
            $id_metodo
        ]);

        echo "Recarga realizada con éxito.";
    } catch (PDOException $e) {
        echo "Error al realizar la recarga: " . $e->getMessage();
    }


}
?>