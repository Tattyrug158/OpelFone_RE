<?php
session_start();
$conexion = mysqli_connect("localhost", "root", "", "opelfone");

if (isset($_POST['ingresar'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Buscamos al admin por su email
    $stmt = $conexion->prepare("SELECT * FROM administrador WHERE Email_Admin = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($row = $resultado->fetch_assoc()) {
        if (password_verify($password, $row['Contrasena_admin'])) {
            $_SESSION['admin_id'] = $row['ID_admin'];
            $_SESSION['admin_nombre'] = $row['Nombre'];
            
            header("Location: ../../FrontEnd/Administrador/sistema.html"); 
        } else {
            echo "Contraseña incorrecta.";
            header("Location: ../../FrontEnd/Administrador/sistema.html"); 
        }
    } else {
        echo "Administrador no encontrado.";
    }
}
?>