<?php
// 1. Incluye la conexión al puro inicio del archivo
$conexion = mysqli_connect("localhost", "root", "", "opelfone");

// Verifica si la conexión funciona
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="css.css">
    <title>OpelFone</title>
</head>
<body>
    <div class="nab_div">
        <h1>OpelFone Administrator</h1>
        <a href="perfil.html"><button class="user">A.Alison</button></a>
    </div>

    <nav>
        <div class="nab_div2">
            <ul class="navbar">
                <li><a href="sistema.html">Sistema</a></li>
                <li class="navbarselected"><a href="facturacion.php">Facturación</a></li>
                <li><a href="usuarios.html">Usuarios</a></li>
                <li><a href="dispositivos.html">Dispositivos</a></li>
                
            </ul>
        </div>
    </nav>
<h1 style="margin: 50px;" class="sub">Facturación</h1>





<form action="../../BackEnd/Administrador/facturacion.php" method="POST">
    <div style="display: flex; justify-content: space-evenly; border: rgb(84, 39, 39) solid; margin: 50px; border-radius: 25px; box-shadow: 0px 0px 25px rgb(84, 39, 39); padding: 25px;">
        
        <div style="margin: 25px;">
            <div> 
                <h1 class="sub_int">Folio:</h1>
                <input type="text" name="folio" class="texto" required> 
            </div>
            <div> 
                <h1 class="sub_int">Seleccionar Cliente:</h1>
                <select name="id_usuario" class="texto" required>
                    <?php 
                        $res = mysqli_query($conexion, "SELECT ID_cliente, Nombre, Apellidos FROM cliente");
                        while($row = mysqli_fetch_assoc($res)) {
                            echo "<option value='{$row['ID_cliente']}'>{$row['Nombre']} {$row['Apellidos']}</option>";
                        }
                    ?>
                </select>
            </div>
        </div>

        <div>
            <div> 
                <h1 class="sub_int">Fecha de emision:</h1>
                <input type="datetime-local" name="fecha_emision" class="texto" required> 
            </div>
            <div> 
                <h1 class="sub_int">Total:</h1>
                <input type="number" step="0.01" name="total" class="texto" required> 
            </div>
            <div> 
                <h1 class="sub_int">Seleccionar Pago:</h1>
                <select name="id_pago" class="texto" required>
                    <?php 
                        $resP = mysqli_query($conexion, "SELECT Id_pago FROM pago");
                        while($rowP = mysqli_fetch_assoc($resP)) {
                            echo "<option value='{$rowP['Id_pago']}'>ID Pago: {$rowP['Id_pago']}</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: center;">
        <button class="boton" type="submit" name="mandar">Mandar solicitud</button>
    </div>
</form>


</body>
</html>