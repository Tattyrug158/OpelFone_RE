<?php
session_start();
require_once '../Conexión/conexion.php'; 

$respuesta = [];

if (isset($_POST['numero']) && !empty(trim($_POST['numero']))) {
    
    $numero = trim($_POST['numero']);

    $sql = "INSERT INTO telefono (campo_numero) VALUES (?)"; 
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$numero])) {
        $respuesta['status'] = 'success';
        $respuesta['message'] = '¡Número agregado correctamente!';
    } else {
        $respuesta['status'] = 'error';
        $respuesta['message'] = 'Error al ejecutar la consulta.';
    }

} else {
    $respuesta['status'] = 'error';
    $respuesta['message'] = 'El campo de número está vacío.';
}

header('Content-Type: application/json');
echo json_encode($respuesta);
?>