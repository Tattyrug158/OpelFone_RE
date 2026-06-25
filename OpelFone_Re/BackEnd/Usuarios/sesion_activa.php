<?php
session_start();
require_once '../../Sesion/php/conexion.php'; // Ajusta la ruta a tu archivo conexion.php

$nombreUsuario = 'Invitado'; // Valor por defecto

// 1. Verificamos si hay sesión activa
if (isset($_SESSION['ID_cliente'])) {
    // 2. Consultamos el nombre del usuario
    $sql = "SELECT Nombre FROM cliente WHERE ID_cliente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['ID_cliente']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $nombreUsuario = $user['Nombre'];
    }
}
?>
