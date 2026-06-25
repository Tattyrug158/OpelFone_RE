<?php
// ============================================================
//  Centro de Mensajes — OpelFone
//  Requiere: MySQL/MariaDB · PHP 7.4+
//  Ajusta las credenciales en la sección CONFIG
// ============================================================

// ── CONFIG ──────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'opelfone');
// Costo por mensaje (puedes ajustarlo o traerlo de tarifa)
define('COSTO_SMS', 1.50);
// ────────────────────────────────────────────────────────────

// Conexión
$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$success = $error = '';

// ── ENVIAR MENSAJE ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $id_telefono_origen = (int)$_POST['id_telefono_origen'];
    $numero_destino     = trim($_POST['numero_destino']);
    $contenido          = trim($_POST['contenido']);
    $tipo               = trim($_POST['tipo'] ?? 'SMS');

    if (!$id_telefono_origen || !$numero_destino || !$contenido) {
        $error = 'Completa todos los campos requeridos.';
    } else {
        // Verificar que el teléfono origen existe
        $stmtTel = $pdo->prepare("SELECT Numero FROM telefono WHERE ID_Telefono = ?");
        $stmtTel->execute([$id_telefono_origen]);
        $telOrigen = $stmtTel->fetch();

        if (!$telOrigen) {
            $error = 'El teléfono de origen no existe.';
        } else {
            $fecha = date('Y-m-d');
            $hora  = date('H:i:s');
            $stmt  = $pdo->prepare("
                INSERT INTO mensaje
                    (Contenido, Fecha_envio, Costo, Tipo, ID_telefono_origen, Numero_destino, Estado, Hora_envio)
                VALUES (?, ?, ?, ?, ?, ?, 'Enviado', ?)
            ");
            $stmt->execute([$contenido, $fecha, COSTO_SMS, $tipo, $id_telefono_origen, $numero_destino, $hora]);
            $success = "✓ Mensaje enviado correctamente desde <strong>{$telOrigen['Numero']}</strong> hacia <strong>{$numero_destino}</strong>.";
        }
    }
}

// ── CARGAR TELÉFONOS (para el selector) ──────────────────────
$telefonos = $pdo->query("
    SELECT t.ID_Telefono, t.Numero, c.Nombre, c.Apellidos
    FROM telefono t
    JOIN cliente c ON t.ID_cliente = c.ID_cliente
    ORDER BY c.Nombre
")->fetchAll();

// ── FILTRO DE BANDEJA ─────────────────────────────────────────
$filtroTel = $_GET['filtro_tel'] ?? '';
$mensajes   = [];

if ($filtroTel !== '') {
    // Buscar teléfono seleccionado para obtener su número
    $stmtNum = $pdo->prepare("SELECT Numero FROM telefono WHERE ID_Telefono = ?");
    $stmtNum->execute([(int)$filtroTel]);
    $numRow = $stmtNum->fetch();

    if ($numRow) {
        $numero = $numRow['Numero'];
        // Traer mensajes enviados a este número (destino)
        $stmtMsg = $pdo->prepare("
            SELECT m.*,
                   t.Numero AS numero_origen,
                   c.Nombre AS nombre_cliente,
                   c.Apellidos AS apellidos_cliente
            FROM mensaje m
            LEFT JOIN telefono t   ON m.ID_telefono_origen = t.ID_Telefono
            LEFT JOIN cliente c    ON t.ID_cliente = c.ID_cliente
            WHERE m.Numero_destino = ?
            ORDER BY m.Fecha_envio DESC, m.Hora_envio DESC
        ");
        $stmtMsg->execute([$numero]);
        $mensajes = $stmtMsg->fetchAll();
    }
}

// ── ÚLTIMOS MENSAJES GLOBALES ─────────────────────────────────
$ultimosMensajes = $pdo->query("
    SELECT m.*,
           t.Numero AS numero_origen,
           c.Nombre AS nombre_cliente,
           c.Apellidos AS apellidos_cliente
    FROM mensaje m
    LEFT JOIN telefono t ON m.ID_telefono_origen = t.ID_Telefono
    LEFT JOIN cliente c  ON t.ID_cliente = c.ID_cliente
    ORDER BY m.Fecha_envio DESC, m.Hora_envio DESC
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Centro de Mensajes — OpelFone</title>
<style>
  /* ── TOKENS ──────────────────────────────────── */
  :root {
    --bg:        #0d1117;
    --surface:   #161b22;
    --card:      #1c2330;
    --border:    #30363d;
    --accent:    #2563eb;
    --accent2:   #1d4ed8;
    --green:     #22c55e;
    --red:       #ef4444;
    --yellow:    #f59e0b;
    --text:      #e6edf3;
    --muted:     #8b949e;
    --radius:    10px;
    --font:      'Inter', 'Segoe UI', system-ui, sans-serif;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--font);
    font-size: 14px;
    line-height: 1.6;
    min-height: 100vh;
  }

  /* ── HEADER ──────────────────────────────────── */
  header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 14px 32px;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .logo-icon {
    width: 36px; height: 36px;
    background: var(--accent);
    border-radius: 8px;
    display: grid; place-items: center;
    font-size: 18px;
  }
  header h1 { font-size: 18px; font-weight: 600; letter-spacing: -.3px; }
  header span { color: var(--muted); font-size: 13px; margin-left: 8px; }

  /* ── LAYOUT ──────────────────────────────────── */
  .wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 28px 24px;
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 24px;
  }
  @media (max-width: 860px) {
    .wrapper { grid-template-columns: 1fr; }
  }

  /* ── CARD ────────────────────────────────────── */
  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
  }
  .card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .card-header h2 { font-size: 14px; font-weight: 600; }
  .card-header .icon { font-size: 16px; }
  .card-body { padding: 20px; }

  /* ── FORM ────────────────────────────────────── */
  label { display: block; margin-bottom: 4px; font-size: 12px; color: var(--muted); font-weight: 500; text-transform: uppercase; letter-spacing: .5px; }
  .field { margin-bottom: 16px; }

  select, input[type="text"], textarea {
    width: 100%;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 6px;
    padding: 9px 12px;
    font-size: 14px;
    font-family: var(--font);
    outline: none;
    transition: border-color .15s;
  }
  select:focus, input:focus, textarea:focus { border-color: var(--accent); }
  textarea { resize: vertical; min-height: 110px; }

  .char-count { text-align: right; font-size: 11px; color: var(--muted); margin-top: 4px; }
  .char-count.over { color: var(--red); }

  /* Tipo de mensaje */
  .tipo-group { display: flex; gap: 8px; flex-wrap: wrap; }
  .tipo-btn {
    padding: 6px 14px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--muted);
    border-radius: 20px;
    cursor: pointer;
    font-size: 12px;
    font-family: var(--font);
    transition: all .15s;
  }
  .tipo-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }

  /* Botón enviar */
  .btn-send {
    width: 100%;
    padding: 11px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    font-family: var(--font);
    cursor: pointer;
    transition: background .15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  .btn-send:hover { background: var(--accent2); }

  /* ── ALERTS ──────────────────────────────────── */
  .alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 13px;
    display: flex;
    align-items: flex-start;
    gap: 8px;
  }
  .alert-success { background: #14291a; border: 1px solid #22c55e44; color: var(--green); }
  .alert-error   { background: #2b0e0e; border: 1px solid #ef444444; color: var(--red); }

  /* ── BANDEJA ─────────────────────────────────── */
  .filter-bar {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 16px;
  }
  .filter-bar select { flex: 1; }
  .btn-filter {
    padding: 9px 18px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-family: var(--font);
    white-space: nowrap;
    transition: background .15s;
  }
  .btn-filter:hover { background: var(--accent2); }

  /* Tabla mensajes */
  .msg-table { width: 100%; border-collapse: collapse; }
  .msg-table th {
    text-align: left;
    padding: 10px 12px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
  }
  .msg-table td {
    padding: 11px 12px;
    border-bottom: 1px solid var(--border);
    vertical-align: top;
  }
  .msg-table tr:last-child td { border-bottom: none; }
  .msg-table tr:hover td { background: #ffffff06; }

  .msg-content {
    max-width: 260px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: var(--text);
  }
  .phone-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 4px;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    white-space: nowrap;
  }
  .estado-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
  }
  .estado-Enviado  { background: #14291a; color: var(--green); }
  .estado-Fallido  { background: #2b0e0e; color: var(--red); }
  .estado-Pendiente{ background: #2b200e; color: var(--yellow); }

  .tipo-sms  { color: #60a5fa; }
  .tipo-MMS  { color: #a78bfa; }
  .tipo-WhatsApp { color: #4ade80; }

  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--muted);
  }
  .empty-state .empty-icon { font-size: 36px; margin-bottom: 10px; }

  /* ── TABS ────────────────────────────────────── */
  .tabs { display: flex; border-bottom: 1px solid var(--border); }
  .tab {
    padding: 12px 20px;
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    text-decoration: none;
    transition: color .15s, border-color .15s;
  }
  .tab.active { color: var(--text); border-bottom-color: var(--accent); }

  /* ── STATS ───────────────────────────────────── */
  .stats-row {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
  }
  .stat-chip {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 16px;
    flex: 1;
    min-width: 110px;
  }
  .stat-chip .val { font-size: 22px; font-weight: 700; }
  .stat-chip .lbl { font-size: 11px; color: var(--muted); margin-top: 2px; }

  .scroll-table { overflow-x: auto; }

  /* ── COSTO PREVIEW ───────────────────────────── */
  .costo-info {
    background: #0f1f0f;
    border: 1px solid #22c55e33;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 12px;
    color: var(--green);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
  }
</style>
</head>
<body>

<header>
  <div class="logo-icon">📡</div>
  <h1>OpelFone</h1>
  <span>/ Centro de Mensajes</span>
</header>

<div class="wrapper">

  <!-- ── PANEL IZQUIERDO: ENVÍO ─────────────────────────── -->
  <div>
    <div class="card">
      <div class="card-header">
        <span class="icon">✉️</span>
        <h2>Nuevo Mensaje</h2>
      </div>
      <div class="card-body">

        <?php if ($success): ?>
          <div class="alert alert-success">✓ <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="form-envio">
          <input type="hidden" name="tipo" id="tipo-hidden" value="SMS">

          <div class="field">
            <label>Teléfono de origen (cliente)</label>
            <select name="id_telefono_origen" required onchange="actualizarOrigen(this)">
              <option value="">— Seleccionar número —</option>
              <?php foreach ($telefonos as $t): ?>
                <option value="<?= $t['ID_Telefono'] ?>"
                  <?= (isset($_POST['id_telefono_origen']) && $_POST['id_telefono_origen'] == $t['ID_Telefono']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['Numero']) ?> — <?= htmlspecialchars($t['Nombre'] . ' ' . $t['Apellidos']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label>Número destino</label>
            <input type="text" name="numero_destino" placeholder="Ej. 5512345678"
              maxlength="15" required
              value="<?= htmlspecialchars($_POST['numero_destino'] ?? '') ?>">
          </div>

          <div class="field">
            <label>Tipo de mensaje</label>
            <div class="tipo-group">
              <?php foreach (['SMS','MMS','WhatsApp'] as $t): ?>
                <button type="button" class="tipo-btn <?= $t === 'SMS' ? 'active' : '' ?>"
                  onclick="setTipo('<?= $t ?>', this)"><?= $t ?></button>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="field">
            <label>Contenido del mensaje</label>
            <textarea name="contenido" id="msg-content" maxlength="160"
              placeholder="Escribe el mensaje aquí…" required><?= htmlspecialchars($_POST['contenido'] ?? '') ?></textarea>
            <div class="char-count" id="char-count">0 / 160</div>
          </div>

          <div class="costo-info">
            💰 Costo por mensaje: <strong>$<?= number_format(COSTO_SMS, 2) ?> MXN</strong>
          </div>

          <button type="submit" name="enviar" class="btn-send">
            <span>📤</span> Enviar Mensaje
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ── PANEL DERECHO: BANDEJA ─────────────────────────── -->
  <div>
    <div class="card">
      <div class="tabs">
        <span class="tab <?= $filtroTel === '' ? 'active' : '' ?>"
          onclick="location.href='<?= $_SERVER['PHP_SELF'] ?>'">
          📋 Todos los mensajes
        </span>
        <span class="tab <?= $filtroTel !== '' ? 'active' : '' ?>"
          onclick="document.getElementById('filter-form').scrollIntoView()">
          🔍 Filtrar por número
        </span>
      </div>

      <div class="card-body">

        <!-- Stats -->
        <?php
          $totalMensajes = $pdo->query("SELECT COUNT(*) FROM mensaje")->fetchColumn();
          $hoyMensajes   = $pdo->query("SELECT COUNT(*) FROM mensaje WHERE Fecha_envio = CURDATE()")->fetchColumn();
          $costoTotal    = $pdo->query("SELECT COALESCE(SUM(Costo),0) FROM mensaje")->fetchColumn();
        ?>
        <div class="stats-row">
          <div class="stat-chip">
            <div class="val"><?= $totalMensajes ?></div>
            <div class="lbl">Total enviados</div>
          </div>
          <div class="stat-chip">
            <div class="val"><?= $hoyMensajes ?></div>
            <div class="lbl">Enviados hoy</div>
          </div>
          <div class="stat-chip">
            <div class="val">$<?= number_format($costoTotal, 2) ?></div>
            <div class="lbl">Costo acumulado</div>
          </div>
        </div>

        <!-- Filtro por número destino -->
        <form method="GET" id="filter-form" style="margin-bottom:20px;">
          <label style="margin-bottom:8px; display:block;">Filtrar mensajes recibidos en número:</label>
          <div class="filter-bar">
            <select name="filtro_tel">
              <option value="">— Ver todos —</option>
              <?php foreach ($telefonos as $t): ?>
                <option value="<?= $t['ID_Telefono'] ?>"
                  <?= $filtroTel == $t['ID_Telefono'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['Numero']) ?> — <?= htmlspecialchars($t['Nombre'] . ' ' . $t['Apellidos']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter">🔍 Filtrar</button>
          </div>
        </form>

        <!-- Tabla de mensajes -->
        <?php
          $lista = $filtroTel !== '' ? $mensajes : $ultimosMensajes;
          $tituloLista = $filtroTel !== '' ? 'Mensajes recibidos en número seleccionado' : 'Últimos 50 mensajes globales';
        ?>

        <div style="font-size:12px; color:var(--muted); margin-bottom:10px; font-weight:500; text-transform:uppercase; letter-spacing:.5px;">
          <?= $tituloLista ?> (<?= count($lista) ?>)
        </div>

        <?php if (empty($lista)): ?>
          <div class="empty-state">
            <div class="empty-icon">📭</div>
            <div>No hay mensajes para mostrar</div>
            <div style="font-size:12px; margin-top:4px;">Envía un mensaje desde el panel izquierdo</div>
          </div>
        <?php else: ?>
          <div class="scroll-table">
            <table class="msg-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Origen</th>
                  <th>Destino</th>
                  <th>Mensaje</th>
                  <th>Tipo</th>
                  <th>Fecha</th>
                  <th>Estado</th>
                  <th>Costo</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lista as $m): ?>
                <tr>
                  <td style="color:var(--muted); font-size:11px;"><?= $m['Id_mensaje'] ?></td>
                  <td>
                    <?php if ($m['numero_origen']): ?>
                      <span class="phone-badge">📱 <?= htmlspecialchars($m['numero_origen']) ?></span>
                      <?php if ($m['nombre_cliente']): ?>
                        <div style="font-size:11px; color:var(--muted); margin-top:3px;">
                          <?= htmlspecialchars($m['nombre_cliente'] . ' ' . $m['apellidos_cliente']) ?>
                        </div>
                      <?php endif; ?>
                    <?php else: ?>
                      <span style="color:var(--muted); font-size:12px;">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="phone-badge">📲 <?= htmlspecialchars($m['Numero_destino']) ?></span>
                  </td>
                  <td>
                    <div class="msg-content" title="<?= htmlspecialchars($m['Contenido']) ?>">
                      <?= htmlspecialchars($m['Contenido']) ?>
                    </div>
                  </td>
                  <td>
                    <span class="tipo-<?= htmlspecialchars($m['Tipo']) ?>" style="font-size:12px; font-weight:600;">
                      <?= htmlspecialchars($m['Tipo']) ?>
                    </span>
                  </td>
                  <td style="font-size:12px; color:var(--muted); white-space:nowrap;">
                    <?= $m['Fecha_envio'] ?>
                    <div style="font-size:11px;"><?= $m['Hora_envio'] ?></div>
                  </td>
                  <td>
                    <span class="estado-badge estado-<?= htmlspecialchars($m['Estado'] ?? 'Enviado') ?>">
                      <?= htmlspecialchars($m['Estado'] ?? 'Enviado') ?>
                    </span>
                  </td>
                  <td style="font-size:12px; color:var(--green);">
                    $<?= number_format($m['Costo'], 2) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

</div>

<script>
  // ── Contador de caracteres ──
  const textarea  = document.getElementById('msg-content');
  const charCount = document.getElementById('char-count');
  function updateCount() {
    const len = textarea.value.length;
    charCount.textContent = len + ' / 160';
    charCount.classList.toggle('over', len >= 160);
  }
  textarea.addEventListener('input', updateCount);
  updateCount();

  // ── Selector de tipo ──
  function setTipo(tipo, btn) {
    document.getElementById('tipo-hidden').value = tipo;
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    // Ajustar límite de caracteres según tipo
    const max = tipo === 'SMS' ? 160 : 1000;
    textarea.setAttribute('maxlength', max);
    charCount.textContent = textarea.value.length + ' / ' + max;
  }

  // ── Actualizar origen (info visual) ──
  function actualizarOrigen(sel) {
    // Solo feedback visual, la info ya está en el option
  }
</script>
</body>
</html>
