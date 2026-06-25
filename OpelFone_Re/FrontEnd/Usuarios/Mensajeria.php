<?php
session_start();
require_once '../../BackEnd/Conexión/conexion.php';

$id = $_SESSION['ID_cliente'];

$sql = "SELECT Nombre FROM cliente WHERE ID_cliente = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$nombre = $stmt->fetchColumn();

$sqlSaldo = "SELECT SUM(saldo) as total_acumulado FROM saldo WHERE ID_cliente = ?";
$stmtSaldo = $pdo->prepare($sqlSaldo);
$stmtSaldo->execute([$id]);
$resultado = $stmtSaldo->fetch(PDO::FETCH_ASSOC);
$saldo_actual = $resultado['total_acumulado'] ?? 0;

$sqlVencimiento = "SELECT MIN(vencimiento) as proximo_vencimiento FROM saldo WHERE ID_cliente = ? AND vencimiento >= CURDATE()";
$stmtVenc = $pdo->prepare($sqlVencimiento);
$stmtVenc->execute([$id]);
$resVenc = $stmtVenc->fetch(PDO::FETCH_ASSOC);
$fecha_venc = $resVenc['proximo_vencimiento'];

$sqlTels = "SELECT ID_Telefono, Numero FROM telefono WHERE ID_cliente = ?";
$stmtTels = $pdo->prepare($sqlTels);
$stmtTels->execute([$id]);
$telefonos = $stmtTels->fetchAll(PDO::FETCH_ASSOC);
$misNumeros = array_column($telefonos, 'Numero');

define('COSTO_SMS', 1.50);
$success = $error = '';

// ENVIAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $id_tel_origen  = (int)$_POST['id_telefono_origen'];
    $numero_destino = trim($_POST['numero_destino']);
    $contenido      = trim($_POST['contenido']);

    $chk = $pdo->prepare("SELECT Numero FROM telefono WHERE ID_Telefono = ? AND ID_cliente = ?");
    $chk->execute([$id_tel_origen, $id]);
    $telOrigen = $chk->fetch();

    if (!$id_tel_origen || !$numero_destino || !$contenido) {
        $error = 'Completa todos los campos requeridos.';
    } elseif (!$telOrigen) {
        $error = 'El número de origen no es válido.';
    } else {
        $ins = $pdo->prepare("
            INSERT INTO mensaje (Contenido, Fecha_envio, Costo, Tipo, ID_telefono_origen, Numero_destino, Estado, Hora_envio)
            VALUES (?, CURDATE(), ?, 'SMS', ?, ?, 'Enviado', CURTIME())
        ");
        $ins->execute([$contenido, COSTO_SMS, $id_tel_origen, $numero_destino]);
        $success = "Mensaje enviado a <strong>{$numero_destino}</strong>.";
    }
}

// ELIMINAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $id_msg = (int)$_POST['id_mensaje'];
    $del = $pdo->prepare("
        DELETE m FROM mensaje m
        JOIN telefono t ON m.ID_telefono_origen = t.ID_Telefono
        WHERE m.Id_mensaje = ? AND t.ID_cliente = ?
    ");
    $del->execute([$id_msg, $id]);
    $success = 'Mensaje eliminado.';
}

// EDITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $id_msg          = (int)$_POST['id_mensaje'];
    $nuevo_contenido = trim($_POST['nuevo_contenido']);
    if (!$nuevo_contenido) {
        $error = 'El contenido no puede estar vacío.';
    } else {
        $upd = $pdo->prepare("
            UPDATE mensaje m
            JOIN telefono t ON m.ID_telefono_origen = t.ID_Telefono
            SET m.Contenido = ?
            WHERE m.Id_mensaje = ? AND t.ID_cliente = ?
        ");
        $upd->execute([$nuevo_contenido, $id_msg, $id]);
        $success = 'Mensaje actualizado.';
    }
}

// MENSAJES ENVIADOS
$stmtEnv = $pdo->prepare("
    SELECT m.*, t.Numero AS numero_origen,
           c2.Nombre AS nombre_destino, c2.Apellidos AS apellidos_destino
    FROM mensaje m
    JOIN telefono t ON m.ID_telefono_origen = t.ID_Telefono
    LEFT JOIN telefono t2 ON m.Numero_destino = t2.Numero
    LEFT JOIN cliente c2  ON t2.ID_cliente = c2.ID_cliente
    WHERE t.ID_cliente = ?
    ORDER BY m.Fecha_envio DESC, m.Hora_envio DESC
    LIMIT 50
");
$stmtEnv->execute([$id]);
$mensajesEnviados = $stmtEnv->fetchAll(PDO::FETCH_ASSOC);

// MENSAJES RECIBIDOS
$recibidos = [];
if (!empty($misNumeros)) {
    $placeholders = implode(',', array_fill(0, count($misNumeros), '?'));
    $stmtRec = $pdo->prepare("
        SELECT m.*, t.Numero AS numero_origen,
               c.Nombre AS nombre_remitente, c.Apellidos AS apellidos_remitente
        FROM mensaje m
        JOIN telefono t ON m.ID_telefono_origen = t.ID_Telefono
        JOIN cliente  c ON t.ID_cliente = c.ID_cliente
        WHERE m.Numero_destino IN ($placeholders)
        ORDER BY m.Fecha_envio DESC, m.Hora_envio DESC
        LIMIT 50
    ");
    $stmtRec->execute($misNumeros);
    $recibidos = $stmtRec->fetchAll(PDO::FETCH_ASSOC);
}

$tab = $_GET['tab'] ?? 'enviados';
if (!in_array($tab, ['enviados','recibidos'])) $tab = 'enviados';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/css.css">
    <title>OpelFone — Mensajería</title>
    <style>
        /* Colores del proyecto */
        :root {
            --verde      : #62715e;
            --verde-clr  : #94b889;
            --crema      : #fffbf1;
            --rojo       : #d95050;
        }

        .contenedor {
            padding: 30px 40px;
            gap: 0;
            align-items: flex-start;
        }

        /* Panel izquierdo */
        .panel-envio {
            min-width: 260px;
            max-width: 550px;
            border-right: 5px solid var(--verde);
            padding-right: 30px;
            flex-shrink: 0;
        }

        /* Panel derecho */
        .registro {
            flex: 1;
            min-width: 0;
            padding-left: 30px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Pestañas */
        .tabs-bar {
            display: flex;
            border-bottom: 3px solid var(--verde);
            margin-bottom: 4px;
        }
        .tab-btn {
            font-family: OpelFont;
            font-size: 20px;
            padding: 8px 24px;
            background: transparent;
            border: none;
            border-bottom: 4px solid transparent;
            margin-bottom: -3px;
            cursor: pointer;
            color: var(--verde);
            opacity: .5;
            transition: opacity .2s, border-color .2s;
        }
        .tab-btn:hover { opacity: .75; }
        .tab-btn.active { opacity: 1; border-bottom-color: var(--verde-clr); }

        .tab-panel { display: none; }
        .tab-panel.active { display: flex; flex-direction: column; gap: 12px; }

        /* Tabla */
        .scroll-wrap {
            overflow-x: auto;
            border: 2px solid var(--verde-clr);
            border-radius: 20px;
        }
        .msg-table {
            width: 100%;
            border-collapse: collapse;
            font-family: OpelFont;
        }
        .msg-table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 16px;
            color: var(--crema);
            background: var(--verde-clr);
            white-space: nowrap;
        }
        .msg-table th:first-child { border-radius: 18px 0 0 0; }
        .msg-table th:last-child  { border-radius: 0 18px 0 0; }
        .msg-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #c4dcbd;
            vertical-align: middle;
            color: var(--verde);
            font-size: 16px;
        }
        .msg-table tbody tr:last-child td { border-bottom: none; }
        .msg-table tbody tr:hover td { background: #f5f9f4; }

        /* Mensaje truncado, expandible con clic */
        .msg-txt {
            max-width: 200px;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--verde);
        }
        .msg-txt.expanded {
            white-space: normal;
            overflow: visible;
            max-width: 340px;
        }

        /* Burbuja recibidos */
        .bubble {
            background: #e8f5e3;
            border: 1px solid #c4dcbd;
            border-radius: 0 14px 14px 14px;
            padding: 8px 14px;
            font-size: 16px;
            max-width: 260px;
            word-break: break-word;
            color: var(--verde);
        }
        .remitente-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--verde-clr);
            margin-bottom: 3px;
        }

        /* Número destino/origen */
        .num-tag {
            font-family: OpelFont;
            font-size: 15px;
            color: var(--verde);
        }
        .name-tag {
            font-size: 13px;
            color: #888;
            margin-top: 2px;
        }

        /* Botones de acción */
        .btn-editar {
            font-family: OpelFont;
            font-size: 15px;
            padding: 6px 16px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            background-color: var(--verde-clr);
            color: var(--crema);
            box-shadow: 0px 4px 10px #9ab692;
            transition: opacity .2s;
        }
        .btn-editar:hover { opacity: .8; }
        .btn-borrar {
            font-family: OpelFont;
            font-size: 15px;
            padding: 6px 16px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            background-color: #efb3b3;
            color: #7a2020;
            box-shadow: 0px 4px 10px #c16c6c;
            transition: opacity .2s;
        }
        .btn-borrar:hover { opacity: .8; }

        /* Alertas */
        .msg-alert {
            font-family: OpelFont;
            font-size: 17px;
            padding: 10px 18px;
            border-radius: 15px;
            margin-bottom: 14px;
        }
        .alert-ok  { background: #d4edda; color: #2d6a35; border: 1px solid #b8dbbf; }
        .alert-err { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        /* Estado vacío */
        .empty-reg {
            font-family: OpelFont;
            font-size: 18px;
            text-align: center;
            padding: 40px 20px;
            color: var(--verde);
            border: 2px dashed var(--verde-clr);
            border-radius: 20px;
            opacity: .6;
        }

        /* Modal edición */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            backdrop-filter: blur(4px);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: var(--crema);
            border: 2px solid var(--verde-clr);
            border-radius: 25px;
            padding: 30px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(98,113,94,.4);
            font-family: OpelFont;
            color: var(--verde);
        }
        .modal-box h3 {
            font-size: 25px;
            font-weight: lighter;
            color: var(--verde);
            margin-bottom: 18px;
        }
        .modal-box textarea {
            width: 100%;
            border-radius: 15px;
            border: 2px solid var(--verde-clr);
            font-size: 18px;
            font-family: OpelFont;
            color: var(--verde);
            padding: 10px 14px;
            resize: vertical;
            min-height: 100px;
            box-sizing: border-box;
        }
        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 18px;
        }

        @media (max-width: 768px) {
            .contenedor { flex-direction: column !important; padding: 20px; }
            .panel-envio { max-width: 100%; border-right: none; padding-right: 0; border-bottom: 5px solid var(--verde); padding-bottom: 24px; margin-bottom: 8px; }
            .registro { padding-left: 0; padding-top: 20px; }
        }
    </style>
</head>
<body>

<div class="nab_div">
    <div><h1>OpelFone</h1></div>
    <div>
        <a href="perfil.php"><button class="user"><span><?php echo htmlspecialchars($nombre); ?></span></button></a>
        <a href="../../FrontEnd/Sesion/Inicio_sesión_usuario.html" class="sesion">Cerrar Sesión</a>
    </div>
</div>

<nav>
    <div class="nab_div2">
        <ul class="navbar">
            <li><a href="index.php">Inicio</a></li>
            <li><a href="Mi_Linea.php">Mi Linea</a></li>
            <li><a href="Recarga_Saldo.php">Recarga de Saldo</a></li>
            <li><a href="Mi_Saldo.php">Mi Saldo</a></li>
            <li class="navbarselected"><a href="Mensajeria.php">Mensajeria</a></li>
        </ul>
    </div>
</nav>

<div class="flex contenedor">

    <!-- Panel izquierdo: formulario de envío -->
    <div class="panel-envio">

        <?php if ($success): ?>
            <div class="msg-alert alert-ok"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg-alert alert-err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">

            <h1 class="sub">Enviar desde...</h1><br>
            <select class="texto" name="id_telefono_origen" required style="margin-bottom:20px; text-align:left; padding-left:16px; width=100%">
                <option value="">— Tu número —</option>
                <?php foreach ($telefonos as $t): ?>
                    <option value="<?= $t['ID_Telefono'] ?>"
                        <?= (isset($_POST['id_telefono_origen']) && $_POST['id_telefono_origen'] == $t['ID_Telefono']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['Numero']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <h1 class="sub">Enviar a...</h1><br>
            <input class="texto" type="text" name="numero_destino" maxlength="15"
                placeholder="Número de teléfono"
                value="<?= htmlspecialchars($_POST['numero_destino'] ?? '') ?>"><br><br>

            <h1 class="sub">Escríbele algo...</h1><br>
            <textarea class="texto" name="contenido"
                placeholder="Escribe tu mensaje aquí..."
                style="min-height:120px; text-align:left; padding:12px 16px;"><?= htmlspecialchars($_POST['contenido'] ?? '') ?></textarea><br>

            <button type="submit" name="enviar" class="boton" style="width:100%; margin-left:0;">Enviar</button>
        </form>
    </div>

    <!-- Panel derecho: bandeja -->
    <div class="registro">

        <div class="tabs-bar">
            <button class="tab-btn <?= $tab==='enviados'  ? 'active' : '' ?>" onclick="switchTab('enviados')">Enviados</button>
            <button class="tab-btn <?= $tab==='recibidos' ? 'active' : '' ?>" onclick="switchTab('recibidos')">Recibidos</button>
        </div>

        <!-- ENVIADOS -->
        <div class="tab-panel <?= $tab==='enviados' ? 'active' : '' ?>" id="panel-enviados">
            <?php if (empty($mensajesEnviados)): ?>
                <div class="empty-reg">Aún no has enviado mensajes.</div>
            <?php else: ?>
                <div class="scroll-wrap">
                    <table class="msg-table">
                        <thead>
                            <tr>
                                <th>Desde</th>
                                <th>Para</th>
                                <th>Mensaje</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mensajesEnviados as $m): ?>
                            <tr>
                                <td><span class="num-tag"><?= htmlspecialchars($m['numero_origen']) ?></span></td>
                                <td>
                                    <span class="num-tag"><?= htmlspecialchars($m['Numero_destino']) ?></span>
                                    <?php if (!empty($m['nombre_destino'])): ?>
                                        <div class="name-tag"><?= htmlspecialchars($m['nombre_destino'].' '.$m['apellidos_destino']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="msg-txt" title="<?= htmlspecialchars($m['Contenido']) ?>"
                                         onclick="this.classList.toggle('expanded')">
                                        <?= htmlspecialchars($m['Contenido']) ?>
                                    </div>
                                </td>
                                <td style="white-space:nowrap; font-size:15px; color:#888;">
                                    <?= $m['Fecha_envio'] ?><br>
                                    <span style="font-size:13px;"><?= substr($m['Hora_envio'],0,5) ?></span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <button class="btn-editar"
                                            onclick="abrirModal(<?= $m['Id_mensaje'] ?>, <?= htmlspecialchars(json_encode($m['Contenido'])) ?>)">
                                            Editar
                                        </button>
                                        <form method="POST" onsubmit="return confirm('¿Eliminar este mensaje?');" style="display:inline;">
                                            <input type="hidden" name="id_mensaje" value="<?= $m['Id_mensaje'] ?>">
                                            <button type="submit" name="eliminar" class="btn-borrar">Borrar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECIBIDOS -->
        <div class="tab-panel <?= $tab==='recibidos' ? 'active' : '' ?>" id="panel-recibidos">
            <?php if (empty($recibidos)): ?>
                <div class="empty-reg">No tienes mensajes recibidos aún.</div>
            <?php else: ?>
                <div class="scroll-wrap">
                    <table class="msg-table">
                        <thead>
                            <tr>
                                <th>De</th>
                                <th>Para</th>
                                <th>Mensaje</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recibidos as $m): ?>
                            <tr>
                                <td>
                                    <span class="num-tag"><?= htmlspecialchars($m['numero_origen']) ?></span>
                                    <?php if (!empty($m['nombre_remitente'])): ?>
                                        <div class="name-tag"><?= htmlspecialchars($m['nombre_remitente'].' '.$m['apellidos_remitente']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="num-tag"><?= htmlspecialchars($m['Numero_destino']) ?></span></td>
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:3px;">
                                        <?php if (!empty($m['nombre_remitente'])): ?>
                                            <div class="remitente-label"><?= htmlspecialchars($m['nombre_remitente']) ?></div>
                                        <?php endif; ?>
                                        <div class="bubble"><?= htmlspecialchars($m['Contenido']) ?></div>
                                    </div>
                                </td>
                                <td style="white-space:nowrap; font-size:15px; color:#888;">
                                    <?= $m['Fecha_envio'] ?><br>
                                    <span style="font-size:13px;"><?= substr($m['Hora_envio'],0,5) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /registro -->
</div><!-- /contenedor -->

<!-- Modal edición -->
<div class="modal-overlay" id="modal-editar">
    <div class="modal-box">
        <h3>Editar mensaje</h3>
        <form method="POST">
            <input type="hidden" name="id_mensaje" id="edit-id">
            <textarea name="nuevo_contenido" id="edit-contenido"
                placeholder="Nuevo contenido del mensaje..."></textarea>
            <div class="modal-footer">
                <button type="button" class="botoncan" style="width:auto; margin:0;" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" name="actualizar" class="boton" style="margin:0;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(name) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + name).classList.add('active');
        document.querySelectorAll('.tab-btn').forEach(b => {
            if (b.textContent.trim().toLowerCase() === name) b.classList.add('active');
        });
        sessionStorage.setItem('msg_tab', name);
    }
    (function() {
        const saved = sessionStorage.getItem('msg_tab');
        const init  = <?= json_encode($tab) ?>;
        if (saved && saved !== init) switchTab(saved);
    })();

    const overlay = document.getElementById('modal-editar');
    function abrirModal(id, contenido) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-contenido').value = contenido;
        overlay.classList.add('open');
    }
    function cerrarModal() { overlay.classList.remove('open'); }
    overlay.addEventListener('click', e => { if (e.target === overlay) cerrarModal(); });
</script>
</body>
</html>