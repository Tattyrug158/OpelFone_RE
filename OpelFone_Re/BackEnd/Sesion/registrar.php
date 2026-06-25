<?php
require_once '../Conexión/conexion.php';

try {
    $pdo = new PDO($dsn, $user, $pass);
    
    $sql = "INSERT INTO cliente (Nombre, Apellidos, Domicilio, Email_C, Contrasena_cliente) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        $_POST['nombre'], 
        $_POST['apellidos'], 
        $_POST['domicilio'], 
        $_POST['email'], 
        password_hash($_POST['password'], PASSWORD_DEFAULT)
    ]);

    echo "exito"; 

} catch (PDOException $e) {
    // Si hay un error de conexión o de SQL, se mostrará aquí
    echo "Error de conexión o base de datos: " . $e->getMessage();
}
?>