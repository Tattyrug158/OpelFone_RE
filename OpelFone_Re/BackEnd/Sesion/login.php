<?php
session_start();
require_once '../Conexión/conexion.php';

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT ID_cliente, Contrasena_cliente FROM cliente WHERE Email_C = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

   if ($user) {
    if (password_verify($password, $user['Contrasena_cliente'])) {
        $_SESSION['ID_cliente'] = $user['ID_cliente'];
        echo "exito"; 
    } else {
        echo "Contraseña incorrecta"; 
    }
} else {
    echo "usuario_no_encontrado";
}
}
?>