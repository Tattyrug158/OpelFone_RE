<?php
session_start();
require_once '../../BackEnd/Conexión/conexion.php';
        $id = $_SESSION['ID_cliente'];
        $sql = "SELECT Nombre
                FROM cliente 
                WHERE ID_cliente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $nombre = $stmt->fetchColumn();

        
        $id_cliente = $_SESSION['ID_cliente'];
        $sql = "SELECT ID_telefono, Numero FROM telefono WHERE ID_cliente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cliente]);
        $telefonos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlMetodos = "SELECT ID_metodo, nombre, numero FROM metodo_pago WHERE ID_usuario = ?";
        $stmtMetodos = $pdo->prepare($sqlMetodos);
        $stmtMetodos->execute([$id_cliente]);
        $metodos = $stmtMetodos->fetchAll(PDO::FETCH_ASSOC);

        $sqlSaldo = "SELECT SUM(saldo) as total_acumulado 
                    FROM saldo 
                    WHERE ID_cliente = ?";

        $stmtSaldo = $pdo->prepare($sqlSaldo);
        $stmtSaldo->execute([$id]);
        $resultado = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

        $saldo_actual = $resultado['total_acumulado'] ?? 0;

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css\css.css">
    <title>OpelFone</title>
</head>
<body>
    <div class="nab_div">
        <div><h1> OpelFone</h1></div>
        <div><a href="perfil.php"><button class="user"><?php echo htmlspecialchars($nombre); ?></button></a>            <a href="../../FrontEnd/Sesion/Inicio_sesión_usuario.html" class="sesion"  >Cerrar Sesion</a>    </div>
    </div>
    <nav>
            <div class="nab_div2">
                <ul class="navbar">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="Mi_Linea.php">Mi Linea</a></li>
                <li><a href="Recarga_Saldo.php">Recarga de Saldo</a></li>
                <li  class="navbarselected"><a href="Mi_Saldo.php">Mi Saldo</a></li>
                <li><a href="Mensajeria.php">Mensajeria</a></li>
            </ul>
            </div>
        </nav>

        <div style="padding: 35px;">
            <div class="flex">
                <div>
                    <h1 class="sub_int">Mis Saldos</h1>
                </div>
                <div><h1 class="sub">Costo por Mensaje: $2 MXN</h1></div>
            </div>
            <hr class="linea">
            
            <div class="contenedorc flex">
                <h1 class="monto-grande">Saldo General de la cuenta: $ <?= number_format($saldo_actual, 2) ?></h1>
                <button class="tarboton"  id="abrirModal">Recargar</button>
            </div><br>



<h1 class="sub_int">Recargar Saldo</h1>

        <form action="../../BackEnd/Usuarios/saldo.php" method="POST">
        <input type="text" id="input-desvio" name="saldo" class="texto" placeholder="Monto">
        <div><select name="id_metodo" class="texto" required>
                <option value="">Seleccione un método...</option>
                <?php foreach($metodos as $metodo): ?>
                    <option value="<?= $metodo['ID_metodo'] ?>">
                        <?= htmlspecialchars($metodo['nombre']) ?> - ****<?= substr($metodo['numero'], -4) ?>
                    </option>
                <?php endforeach; ?>
            </select>


        <button class="boton" type="submit" name="recargar">Recargar</button>
        <button class="botoncan" type="button" id="cerrarModal1">Cancelar</button>
        </form>

        

        </div>

        <dialog id="miVentana" class="modal">
        
        </dialog>



        <script>
            document.getElementById("abrirModal").addEventListener("click", () => document.getElementById("miVentana").showModal());
            document.getElementById("cerrarModal1").addEventListener("click", () => document.getElementById("miVentana").close());
        </script>



<?php
$sql = "SELECT ID_metodo, Nombre
FROM metodo_pago
WHERE ID_usuario = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
</body>
</html>