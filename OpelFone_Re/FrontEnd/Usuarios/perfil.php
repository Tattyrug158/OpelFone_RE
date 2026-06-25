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

// 1. Inicialización de variables
$nombreUsuario = 'Invitado';
$cliente = ['Nombre' => '', 'Apellidos' => '', 'Domicilio' => '', 'Email_C' => ''];

if (isset($_SESSION['ID_cliente'])) {
    // Consultar datos
    $stmt = $pdo->prepare("SELECT * FROM cliente WHERE ID_cliente = ?");
    $stmt->execute([$_SESSION['ID_cliente']]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        $cliente = $resultado;
        $nombreUsuario = $cliente['Nombre'];
    }
}

// 2. Procesar el formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    // Asegúrate de que los nombres de los campos coincidan con tu base de datos
    // Si tu columna es "Apellidos" (plural), cámbialo abajo
    $sql = "UPDATE cliente SET Nombre = ?, Apellidos = ?, Domicilio = ?, Email_C = ? WHERE ID_cliente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['nombre'], 
        $_POST['apellido'], 
        $_POST['domicilio'], 
        $_POST['email'], 
        $_SESSION['ID_cliente']
    ]);
    
    header("Location: perfil.php"); // Recargar para mostrar cambios
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/css.css">
    <title>OpelFone</title>
</head>
<body>
    <div class="nab_div">
        <div><h1>OpelFone</h1></div>
        <div><a href="perfil.php"><button class="user"><?php echo htmlspecialchars($nombreUsuario); ?></button></a>            <a href="../Sesion/Inicio_sesión_usuario.html" class="sesion"  >Cerrar Sesion</a>    </div>
    </div>
    <nav>
        <div class="nab_div2">
            <ul class="navbar">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="Mi_Linea.php">Mi Linea</a></li>
                <li><a href="Recarga_Saldo.php">Recarga de Saldo</a></li>
                <li><a href="Mi_Saldo.php">Mi Saldo</a></li>
                <li><a href="Mensajeria.php">Mensajeria</a></li>
            </ul>
        </div>
    </nav>

    <form method="POST">
        <input type="hidden" name="accion" value="actualizar">
        
        <div style="margin: 25px;">
            <div style="border: black solid; border-radius: 25px; padding: 25px; display: flex; justify-content: space-between;"> 
                <h1 class="sub_int">Mi cuenta</h1>
                <h1 class="sub"><?php echo htmlspecialchars($nombreUsuario); ?></h1>
            </div>

            <div class="flex">
                <div class="usuario">
                    <div class="flex">
                        <div> 
                            <h1 class="sub_int">Nombre/s</h1>
                            <input type="text" name="nombre" class="texto" value="<?= htmlspecialchars($cliente['Nombre'] ?? '') ?>"> 
                        </div>
                        <div> 
                            <h1 class="sub_int">Apellido/s</h1>
                            <input type="text" name="apellido" class="texto" value="<?= htmlspecialchars($cliente['Apellido'] ?? '') ?>"> 
                        </div>
                    </div>

                    <div class="flex">
                        <div> 
                            <h1 class="sub_int">Domicilio</h1>
                            <input type="text" name="domicilio" class="texto" value="<?= htmlspecialchars($cliente['Domicilio'] ?? '') ?>"> 
                        </div>
                        <div> 
                            <h1 class="sub_int">E-mail</h1>
                            <input type="text" name="email" class="texto" value="<?= htmlspecialchars($cliente['Email_C'] ?? '') ?>"> 
                        </div>
                    </div>

                    <div> 
                        <h1 class="sub_int">Contraseña</h1>
                        <input type="password" name="password" class="texto" placeholder="Nueva contraseña"> 
                    </div>
                </div>

                <div style="justify-content: center; align-items: center; text-align: center;">
                    <div style="border: slategray solid; height: 300px; width: 100%;">
                        <img src="Sources/user.png" alt="Foto" style="width: 110%; height: 100%;">
                    </div>
                    <button type="button" class="boton2">Actualizar foto</button>
                    <button type="submit" class="boton2">Aplicar cambios</button>
                </div>

                
            </div>
        </div>
    </form>
</body>
</html>