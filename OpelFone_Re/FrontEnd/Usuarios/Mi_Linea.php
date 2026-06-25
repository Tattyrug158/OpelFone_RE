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

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/css.css">
    <title>OpelFone — Mi Línea</title>
</head>
<body>
    <div class="nab_div">
        <div><h1>OpelFone</h1></div>
        <div><a href="perfil.php"><button class="user"><?php echo htmlspecialchars($nombre); ?></button></a>            <a href="../../FrontEnd/Sesion/Inicio_sesión_usuario.html" class="sesion"  >Cerrar Sesion</a>    </div>
    </div>
    <nav>
        <div class="nab_div2">
            <ul class="navbar">
                <li><a href="index.php">Inicio</a></li>
                <li class="navbarselected"><a href="Mi_Linea.php">Mi Linea</a></li>
                <li><a href="Recarga_Saldo.php">Recarga de Saldo</a></li>
                <li><a href="Mi_Saldo.php">Mi Saldo</a></li>
                <li><a href="Mensajeria.php">Mensajeria</a></li>
            </ul>
        </div>
    </nav>

    <div class="contenedor">


        <form action="../../BackEnd/Usuarios/Mi_Linea/recuperar_infromación.php" method="POST" class="flex">
        <div class="con1">
            <h1 class="sub_int">Agregar número</h1>
            <input type="text" name="numero" placeholder="Ingresa un teléfono" class="texto">
            <button name="agregar" type="submit" class="boton">Agregar nuevo número</button>
            <h2 class="sub_int">Mis teléfonos</h2>
            <div id="lista">
                        <?php if(!empty($telefonos)): ?>
                            <?php foreach($telefonos as $tel): ?>
                                <h1 class="telefono-btn" data-id="<?= $tel['ID_telefono'] ?>">
                                    📞 <?= htmlspecialchars($tel['Numero']) ?>
                            </h1><br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No tienes números registrados aún.</p>
                        <?php endif; ?>
            </div>
        </div>
        </form>

        <form action="../../BackEnd/Usuarios/Mi_Linea/eliminar.php" method="POST">
        <div class="con2">
            <h1 class="sub_int" id="titulo-linea">Selecciona una línea</h1>
            <div class="centrar"><input class="texto" name="solicitar" id="det-numero" type="text" placeholder="Ingrese su numero" ></input>
        </div>
                             <dialog id="miVentana" class="contenedor dialogo">
                            <h3 class="sub">¿Estás seguro de eliminar este número?</h3>
                            <button class="boton" name="eliminar" type="submit">Aceptar</button>
                            <button class="boton" type="button" id="cerrarModal1">Cancelar</button>
                             </dialog>


        </form>

        <form id="form-estado" action="../../BackEnd/Usuarios/Mi_Linea/actualizar_estado.php" method="POST">
            <input type="hidden" name="numero" id="input-numero-estado" value="">
            <input type="hidden" name="estado" id="input-estado" value="">
            <div class="estado">
                <div><button id="encender" name="cambiar_estado" type="submit" class="apaen">Encender Linea</button></div>
                <div><button id="apagar" name="cambiar_estado" type="submit" class="apaen">Apagar Linea</button></div>
            </div>
        </form>



            <button class="estado2" type="button" id="abrirModal">Eliminar Linea</button>
            <a href="Recarga_Saldo.php"><button type="button" class="estado3">Recargar saldo</button></a>
            <button class="estado4" id="abrirModal2">Desvio de llamadas</button>
        </div>
    </div>
    


    <dialog id="miVentana2" class="modal">
        <h1 class="sub_int">Desvío de llamadas</h1>
        <input type="text" id="input-desvio" class="texto" placeholder="Nuevo número">
        <button class="boton">Guardar desvío</button>
        <button class="botoncan" id="cerrarModal2">Cancelar</button>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    document.getElementById("abrirModal").addEventListener("click", () => document.getElementById("miVentana").showModal());
    document.getElementById("abrirModal2").addEventListener("click", () => document.getElementById("miVentana2").showModal());
    document.getElementById("cerrarModal1").addEventListener("click", () => document.getElementById("miVentana").close());
    document.getElementById("cerrarModal2").addEventListener("click", () => document.getElementById("miVentana2").close());

    
    document.getElementById('form-estado').addEventListener('submit', function(e) {
        const numero = document.getElementById('det-numero').value;
        if(!numero) { 
            e.preventDefault();
            alert('Ingresa un número primero'); 
            return; 
        }
        document.getElementById('input-numero-estado').value = numero;
    });

    
document.getElementById('encender').addEventListener('click', () => {
        const numero = document.getElementById('det-numero').value;
        
        if (numero.trim() === "") {
            alert('No puedes encender la línea sin ingresar un número primero');
            return;
        }else{
            document.getElementById('input-estado').value = 1;
        alert("La linea ha cambiado a estado encendido");
        }
        
        
    });

    // --- BOTÓN APAGAR ---
    document.getElementById('apagar').addEventListener('click', () => {
        const numero = document.getElementById('det-numero').value;
        
        if (numero.trim() === "") {
            alert('No puedes apagar la línea sin ingresar un número primero');
            return;
        }else{
            document.getElementById('input-estado').value = 0;
        alert("La linea ha cambiado a estado apagado");
        }

        
    });                   
});
    </script>


</body>
</html>