<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $nombreArchivo = $_FILES['foto']['name'];
    $rutaTemporal = $_FILES['foto']['tmp_name'];
    $carpetaDestino = "uploads/" . $nombreArchivo;

    // 1. Mover el archivo a la carpeta del servidor
    if (move_uploaded_file($rutaTemporal, $carpetaDestino)) {
        
        // 2. Conectar a BD y guardar solo el nombre del archivo
        $id_cliente = 1; // Debes obtener este ID dinámicamente
        $sql = "UPDATE tu_tabla SET imagen = '$nombreArchivo' WHERE ID_cliente = $id_cliente";
        // Ejecuta tu consulta SQL aquí...
        
        echo "Imagen subida y base de datos actualizada.";
    }
}
?>