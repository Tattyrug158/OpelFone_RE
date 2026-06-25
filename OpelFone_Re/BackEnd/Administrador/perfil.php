<?php
session_start();
include("Backend/Administrador/conexion.php");

// Verificamos que tenga sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$usuario_sesion = $_SESSION['usuario'];

// Traemos los datos del administrador logueado
$query = "SELECT * FROM administrador WHERE usuario = ?";
$params = array($usuario_sesion);
$result = sqlsrv_query($conn, $query, $params);

if ($result && $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $nombre = $row['nombre'];
    $correo = $row['correo'];
    // ... todas las columnas que tengas en tu tabla administrador
}
?>

<form action="actualizar_perfil.php" method="POST">
    <label>Usuario:</label>
    <input type="text" value="<?php echo $usuario_sesion; ?>" disabled>

    <label>Nombre Completo:</label>
    <input type="text" name="nombre" value="<?php echo $nombre; ?>">

    <label>Correo Electrónico:</label>
    <input type="email" name="correo" value="<?php echo $correo; ?>">

    <button type="submit" name="actualizar">Actualizar Datos</button>
</form>