
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

        $sqlSaldo = "SELECT SUM(saldo) as total_acumulado 
                    FROM saldo 
                    WHERE ID_cliente = ?";

        $stmtSaldo = $pdo->prepare($sqlSaldo);
        $stmtSaldo->execute([$id]);
        $resultado = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

        $saldo_actual = $resultado['total_acumulado'] ?? 0;

        
        $sqlVencimiento = "SELECT MIN(vencimiento) as proximo_vencimiento 
                   FROM saldo 
                   WHERE ID_cliente = ? 
                   AND vencimiento >= CURDATE()"; // Solo consideramos las que no han vencido

        $stmtVenc = $pdo->prepare($sqlVencimiento);
        $stmtVenc->execute([$id]);
        $resVenc = $stmtVenc->fetch(PDO::FETCH_ASSOC);

        $fecha_venc = $resVenc['proximo_vencimiento'];
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
        <div><h1 href="../"> OpelFone</h1></div>
        <div><a href="perfil.php" ><button class="user"><span><?php echo $nombre; ?></span></button></a><a href="../../FrontEnd/Sesion/Inicio_sesión_usuario.html" class="sesion"  >Cerrar Sesion</a>    </div>
    </div>
    </div>
    <nav>
            <div class="nab_div2">
                <ul class="navbar">
                <li  class="navbarselected"><a href="index.php">Inicio</a></li>
                <li><a href="Mi_Linea.php">Mi Linea</a></li>
                <li><a href="Recarga_Saldo.php">Recarga de Saldo</a></li>
                <li><a href="Mi_Saldo.php">Mi Saldo</a></li>
                <li><a href="Mensajeria.php">Mensajeria</a></li>

            </ul>
            </div>
        </nav>

    <div class="main">
    <article class="info">
        <h1 class="sub_int">Opel Amigo</h1><br>
        <span class="sub" ><?php echo $nombre; ?></span><br>

        <a href="../html/Mi_Linea.php"><button class="boton">Ver mas detalles</button></a>

    </article>

    <article class="info2">
        <h1 class="sub_int">Mi Saldo</h1>
        <hr class="linea">
            <div class="Saldo">
                <div>
                    <h2>Acumulado</h2>
                    <h2>Vencimiento</h2>
                </div>

                <div>
                    <h2 class="monto-grande">$ <?= number_format($saldo_actual, 2) ?></h2>
                    <h2><?= date('d/m/Y', strtotime($fecha_venc)) ?></h2>
                </div>

            </div>
            <a href="../html/Mi_Saldo.php"><button class="boton" >Ver mas detalles</button> </a>
    </article>
    </div>




    <script>
    </script>



</body>
</html>