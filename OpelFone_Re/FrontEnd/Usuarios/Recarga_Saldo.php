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

        

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/css.css">
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
                <li class="navbarselected"><a href="Recarga_Saldo.php">Recarga de Saldo</a></li>
                <li><a href="Mi_Saldo.php">Mi Saldo</a></li>
                <li><a href="Mensajeria.php">Mensajeria</a></li>
            </ul>
            </div>
        </nav>



        <div style="padding: 35px;">
            <div class="flex">
                <div>
                    <h1 class="sub_int">Recargar Saldo</h1>
                </div>



                
                <form action="../../BackEnd/Usuarios/recarga.php" method="POST">
                <div><h1 class="sub">Monto a Recargar: $<span>
                    <select id="montos" name="monto" class="texto">
                        <option value="0">Seleccione una cantidad</option>
                    <option value="10">$10</option>
                    <option value="15">$15</option>
                    <option value="30">$30</option>
                    <option value="50">$50</option>
                    <option value="75">$75</option>
                    <option value="85">$85</option>
                    <option value="100">$100</option>
                    <option value="150">$150</option>
                    <option value="200">$200</option>
                    </select>
                </span></h1></div>
            </div>
                
                <div class="flex" style="align-items: center; justify-content: center;">
                    <div style="align-items: center; justify-content: center;">
                        <h1 class="sub">Numero a recargar</h1>
                        <select name="id_telefono" class="texto">

                            <?php foreach($telefonos as $tel): ?>

                                <option value="<?= $tel['ID_telefono'] ?>">
                                    <?= htmlspecialchars($tel['Numero']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>
                    <div style="align-items: center; justify-content: center;">
                        <h1 class="sub">A nombre de: </h1>
                        <input type="text"
                            name="nombred"
                            class="texto"
                            value="<?= htmlspecialchars($nombre) ?>"
                            readonly>>
                    </div>
                </div>


            <div class="flex " style="align-items: center; border: rgb(83, 126, 68) solid; border-radius: 25px; padding: 25px;" >
                <div><h1 class="sub_int">Método de pago: </h1></div>
                <div><select name="id_metodo" class="texto" required>
                <option value="">Seleccione un método...</option>
                <?php if ($saldo_total > 0): ?>
                <option value="saldo_general">Saldo Disponible ($ <?= number_format($saldo_total, 2) ?>)</option>
                <?php endif; ?>

                <?php foreach($metodos as $metodo): ?>
                    <option value="<?= $metodo['ID_metodo'] ?>">
                        <?= htmlspecialchars($metodo['nombre']) ?> - ****<?= substr($metodo['numero'], -4) ?>
                    </option>
                <?php endforeach; ?>
            </select></div>

                
                <button class="boton" type="button" name="metodo" id="abrirModal">Cambiar método de pago</button>
            </div><br><br>

            <div class="flex contenedor" style="align-items: center;" >
                <div><h1 class="sub_int">Usted paga: </h1></div>
                <div><h1 id="tardig" class="sub">$ <span id="total">0.0</span></h1></div>
                <button class="boton" id="abrirAce2" type="submit" name="recargar" >Realizar pago</button>
            </div><br><br>
            </form>





                <hr class="linea">



    <dialog id="modal" class="modal">
        <h1 class="sub_int" style="justify-content: left;">Comprar paquete: <span id="textoModal"></span></h1><br>
        <div class="flex">
            <div style="border-right: black solid; width: 50%;">
                <h3 class="sub">Recargar a:</h3>
                <input type="text" name="" id="telefono" class="texto"><br>
                <button class="boton" onclick="agregarTelefono()">Guardar número de telefono</button><br><br><br><br>
                <h1 class="sub_int">Total: </h1>
                <span id="total" class="sub">0.00</span>
            </div>
            <div style="width: 100%;" >
                <h1 class="sub_int" >Mis telefonos</h1>
                <div id="lista" ></div>
                <button class="botoneli">Borrar telefono</button>
            </div>
        </div>
        <button class="botoncan" id="cerrar">Cancelar Acción</button>
    </dialog>

    <dialog id="miVentana" class="modal">

<form action="../../BackEnd/Usuarios/metodo_pago.php" method="POST">
    <div class="contenedor2" style="padding: 25px;">
        <h1 class="sub">Nuevo método de pago</h1>
        
        <div class="flex">
            <div>
                <label class="label">Nombre del titular</label><br>
                <input class="texto" type="text" name="nombre" required><br>
                
                <label class="label">Numero de Tarjeta</label><br>
                <input class="texto" type="number" name="numero" required><br>
            </div>
            <div>
                <label class="label">CVV</label><br>
                <input class="texto" type="number" name="cvv" required><br>
                
                <label class="label">Fecha de vencimiento</label><br>
                <input class="texto" type="date" name="fecha_exp" required><br>
            </div>
        </div>

        <button type="submit" class="boton">Confirmar nuevo metodo de pago</button>
        <button class="botoncan" id="cerrarModal1">Cancelar</button>
    </div>
</form>


    </dialog>


    <dialog id="ace" class="contenedor flex">
        <h1 class="sub_int">Se confirmo el metodo de pago</h1>
        <button class="boton" id="cerrarAce">Aceptar</button>
    </dialog>

    <dialog id="ace2" class="contenedor flex">
        <h1 class="sub_int">Se realizo el pago</h1>
        <a href="Mi_Saldo.php"><button class="boton" id="cerrarAce2">Aceptar</button></a>
    </dialog>
        
    <script>
        


         function agregarTelefono() {
            let telefono = document.getElementById("telefono").value;

            if (telefono.trim() !== "") {
                // Crear botón
                let boton = document.createElement("button");

                // Texto del botón
                boton.textContent = telefono;
                boton.classList.add("telefono-btn");

                // Salto de línea después del botón
                document.getElementById("lista").appendChild(boton);
                document.getElementById("lista").appendChild(document.createElement("br"));

                // Limpiar campo
                document.getElementById("telefono").value = "";
            }
        }

        const modal = document.getElementById("modal");
        const textoModal = document.getElementById("textoModal");

        document.querySelectorAll(".tarboton").forEach(boton => {

            boton.addEventListener("click", () => {

                textoModal.textContent = boton.dataset.texto;

                modal.showModal();
            });

        });

        document.getElementById("cerrar").addEventListener("click", () => {
            modal.close();


        });
        const ventana1 = document.getElementById("miVentana");

        document.getElementById("abrirModal").addEventListener("click", () => {
            ventana1.showModal();
        });

        document.getElementById("cerrarModal1").addEventListener("click", () => {
            ventana1.close();
        });

        const aceptar = document.getElementById("ace");

        document.getElementById("abrirAce").addEventListener("click", () => {
            aceptar.showModal();
        });

        document.getElementById("cerrarAce").addEventListener("click", () => {
            aceptar.close();
        });

        const aceptar2 = document.getElementById("ace2");

        document.getElementById("abrirAce2").addEventListener("click", () => {
            aceptar2.showModal();
        });

        document.getElementById("cerrarAce2").addEventListener("click", () => {
            aceptar2.close();
        });
        


        const montoSelect = document.getElementById("montos");
        const totalTexto = document.getElementById("total");

        function calcularTotal(){

            let monto = parseFloat(montoSelect.value) || 0;

            let ahora = new Date();
            let hora = ahora.toTimeString().substring(0,8);

            let tarifaActual = tarifas.find(t =>
                hora >= t.Hora_inicio &&
                hora <= t.Hora_fin
            );

            if(tarifaActual){

                let porcentaje = parseFloat(tarifaActual.Porcentaje);
                let fija = parseFloat(tarifaActual.Comision_fija);

                let total = monto + (monto * porcentaje / 100) + fija;

                totalTexto.textContent = total.toFixed(2);
            }
        }

        montoSelect.addEventListener("change", calcularTotal);

        



    </script>

    <?php
$sql = "SELECT * FROM tarifa WHERE Activa = 1";
$tarifas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<script>
const tarifas = <?= json_encode($tarifas) ?>;
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