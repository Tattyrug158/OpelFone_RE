<?php
session_start();
require_once '../../BackEnd/Conexión/conexion.php';

if (isset($_POST['recargar'])) {
    // 1. Obtener datos del formulario y sesión
    $id_cliente = $_SESSION['ID_cliente'];
    $monto = $_POST['saldo']; // El valor del input
    $id_metodo = $_POST['id_metodo'];
    
    // 2. Calcular fechas
    $fecha_actual = date('Y-m-d'); // Formato para la BD
    
    // Calcular un año después usando strtotime
    $fecha_vencimiento = date('Y-m-d', strtotime('+1 year'));

    try {
        // 3. Insertar en la tabla de saldos
        $sql = "INSERT INTO saldo (ID_cliente, saldo, vencimiento, fecha_recarga, ID_metodo) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_cliente, 
            $monto, 
            $fecha_vencimiento, 
            $fecha_actual, 
            $id_metodo
        ]);

        header("Location: ../../FrontEnd/Usuarios/Mi_Saldo.php");
        
    } catch (PDOException $e) {
        echo "Error al guardar el saldo: " . $e->getMessage();
    }
}
?>