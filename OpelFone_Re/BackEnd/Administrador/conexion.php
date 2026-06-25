<?php
// Backend/Administrador/conexion.php

$serverName = "ALISON"; // Tu instancia de SQL Server

// Usamos el usuario exclusivo que acabamos de crear con permisos totales
$connectionInfo = array(
    "Database" => "opelfone1",
    "UID" => "user_opelfone",
    "PWD" => "1234", // La contraseña que le asignaste
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
);

// Intentar conectar
$conn = sqlsrv_connect($serverName, $connectionInfo);

// Verificar conexión
if (!$conn) {
    echo "¡Error de conexión con Microsoft SQL Server!<br />";
    die(print_r(sqlsrv_errors(), true));
}
?>