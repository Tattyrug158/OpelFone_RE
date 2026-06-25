<?php
$conexion = mysqli_connect("localhost", "root", "", "opelfone");

if (isset($_POST['mandar'])) {
    $folio = $_POST['folio'];
    $fecha = $_POST['fecha_emision'];
    $total = $_POST['total'];
    $id_usuario = $_POST['id_usuario'];
    $id_pago = $_POST['id_pago'];

    // Usamos prepare para seguridad
    $stmt = $conexion->prepare("INSERT INTO factura (Folio, Fecha_Factura, Total, Estado, Id_usuario, Id_pago) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsii", $folio, $fecha, $total, $estado, $id_usuario, $id_pago);

    if ($stmt->execute()) {
        echo "<script>alert('Factura creada exitosamente'); window.location='../../FrontEnd/Administrador/facturacion.php';</script>";
    } else {
        echo "Error al guardar: " . $stmt->error;
    }
}
?>