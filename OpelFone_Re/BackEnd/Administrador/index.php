<?php
session_start(); // ¡Elemento crucial en la Línea 1!

include("Backend/Administrador/conexion.php"); 

if (isset($_POST['ingresar'])) { 
    $usuario = $_POST['usuario'];
    $password = $_POST['contrasena'];

    // Consulta adaptada para SQL Server
    $query = "SELECT * FROM administrador WHERE usuario = ? AND contrasena = ?";
    $params = array($usuario, $password);

    $result = sqlsrv_query($conn, $query, $params);

    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // Comprobamos si el usuario existe
    if (sqlsrv_has_rows($result)) {
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        
        // Guardamos los datos en la sesión
        $_SESSION['id_usuario'] = $row['id_usuario']; 
        $_SESSION['usuario'] = $row['usuario'];
        
        // Te redirige a tu panel principal (ajusta el nombre si se llama facturacion.php)
        header("Location: facturacion.php"); 
        exit();
    } else {
        echo "<script>alert('Usuario o contraseña incorrectos');</script>";
    }
}
?>