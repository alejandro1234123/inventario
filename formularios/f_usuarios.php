<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Usuarios normales van directo a su perfil
if ($_SESSION['nivel_acceso'] !== 'admin') {
    header('Location: perfil.php');
    exit();
}

include '../db.php';

$msg_ok  = '';
$msg_err = '';

// ── ELIMINAR USUARIO ────────────────────────────────────
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    if ($id_eliminar === (int)$_SESSION['id_usuario']) {
        $msg_err = 'No puedes eliminar tu propia cuenta.';
    } else {
        $stmtDel = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id_usuario = ?");
        $stmtDel->execute([$id_eliminar]);
        $msg_ok = 'Usuario desactivado correctamente.';
    }
}

// ── CAMBIAR CÓDIGO ADMIN ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_codigo') {
    $nuevo_codigo = isset($_POST['nuevo_codigo']) ? trim($_POST['nuevo_codigo']) : '';
    if (strlen($nuevo_codigo) < 4) {
        $msg_err = 'El código debe tener al menos 4 caracteres.';
    } else {
        $stmtCod = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'codigo_admin'");
        $stmtCod->execute([$nuevo_codigo]);
        $msg_ok = 'Código de administrador actualizado correctamente.';
    }
}

// ── CAMBIAR NIVEL DE ACCESO ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_nivel') {
    $id_target   = (int)$_POST['id_usuario'];
    $nuevo_nivel = $_POST['nuevo_nivel'];
    if ($id_target === (int)$_SESSION['id_usuario']) {
        $msg_err = 'No puedes cambiar tu propio nivel de acceso.';
    } elseif ($nuevo_nivel === 'admin' || $nuevo_nivel === 'usuario') {
        $stmtNivel = $pdo->prepare("UPDATE usuarios SET nivel_acceso = ? WHERE id_usuario = ?");
        $stmtNivel->execute([$nuevo_nivel, $id_target]);
        $msg_ok = 'Nivel de acceso actualizado.';
    }
}

// Obtener usuarios y código admin
$stmtUsers = $pdo->query("SELECT id_usuario, nombre_usuario, nombre_completo, nivel_acceso, fecha_creacion, activo FROM usuarios ORDER BY activo DESC, fecha_creacion DESC");
$usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

$stmtCod = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'codigo_admin'");
$stmtCod->execute();
$codigo_actual = $stmtCod->fetchColumn();

if (isset($_GET['status']) && $_GET['status'] === 'success') $msg_ok = 'Usuario registrado con éxito.';
if (isset($_GET['status']) && $_GET['status'] === 'duplicate') $msg_err = 'El nombre de usuario ya existe.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="../bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --cyan:   #00bcd4; --cyan-d: #0097a7; --cyan-l: #e0f7fa;
            --bg:     #f4f7f6; --surface: #ffffff;
            --border: #e2e8f0; --text: #1a202c; --muted: #718096;
            --green:  #43a047; --red: #e53935;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); padding: 32px 24px; }

        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 10px;
            border: 1.5px solid var(--border); background: white;
            color: var(--muted); font-size: 0.85rem; font-weight: 500;
            text-decoration: none; margin-bottom: 24px; transition: all 0.2s;
        }
        .btn-back:hover { border-color: var(--cyan); color: var(--cyan); }

        .page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .icon-box {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            border-radius: 14px; display: flex; align-items: center;
            justify-content: center; color: white; font-size: 22px;
            box-shadow: 0 4px 14px rgba(0,188,212,0.32);
        }
        .page-header h4 { margin: 0; font-weight: 700; font-size: 1.35rem; }
        .page-header small { color: var(--muted); font-size: 0.82rem; }

        .alert { border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
        .alert-ok  { background: #e8f5e9; border: 1px solid rgba(67,160,71,0.3); color: var(--green); }
        .alert-err { background: #ffebee; border: 1px solid rgba(229,57,53,0.3); color: var(--red); }

        /* Grid principal */
        .grid-top { display: grid; grid-template-columns: 360px 1fr; gap: 20px; margin-bottom: 20px; align-items: start; }

        /* Tarjetas */
        .card {
            background: var(--surface); border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            padding: 24px;
        }
        .card-title {
            font-size: 0.92rem; font-weight: 700; color: var(--text);
            display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
            padding-bottom: 14px; border-bottom: 1px solid var(--border);
        }
        .card-title i { color: var(--cyan); }

        /* Form */
        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block; font-size: 0.73rem; font-weight: 600;
            color: var(--muted); margin-bottom: 6px;
            letter-spacing: 0.05em; text-transform: uppercase;
        }
        .input-wrap { position: relative; }
        .input-wrap i.ico {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%); color: #b0bec5;
            font-size: 0.9rem; pointer-events: none;
        }
        .input-wrap input, .input-wrap select {
            width: 100%; background: var(--bg);
            border: 1.5px solid var(--border); border-radius: 10px;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem; padding: 10px 12px 10px 36px; outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }
        .input-wrap input:focus, .input-wrap select:focus {
            border-color: var(--cyan); background: var(--cyan-l);
            box-shadow: 0 0 0 3px rgba(0,188,212,0.12);
        }
        .input-wrap:focus-within i.ico { color: var(--cyan); }
        .pw-toggle {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #b0bec5; cursor: pointer; padding: 0;
        }
        .pw-toggle:hover { color: var(--cyan); }

        /* Nivel selector */
        .nivel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
        .nivel-opt input[type="radio"] { display: none; }
        .nivel-opt label {
            display: flex; flex-direction: column; align-items: center; gap: 4px;
            padding: 10px 6px; border: 1.5px solid var(--border); border-radius: 10px;
            cursor: pointer; transition: all 0.2s; background: var(--bg);
            text-align: center; font-size: 0.8rem; color: var(--muted);
        }
        .nivel-opt label i { font-size: 1.2rem; }
        .nivel-opt input[value="usuario"]:checked + label { border-color:var(--green); background:#f1f8f1; color:var(--green); }
        .nivel-opt input[value="admin"]:checked   + label { border-color:var(--red);   background:#fff5f5; color:var(--red); }

        /* Código admin */
        .codigo-box { background: #fff5f5; border: 1.5px solid rgba(229,57,53,0.2); border-radius: 12px; padding: 14px; margin-bottom: 14px; display: none; }
        .codigo-box.visible { display: block; }
        .codigo-label { font-size:0.72rem; font-weight:600; color:var(--red); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:6px; display:block; }
        .codigo-box input { width:100%; background:white; border:1.5px solid rgba(229,57,53,0.3); border-radius:8px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.9rem; padding:9px 12px; outline:none; letter-spacing:0.1em; }

        .btn-save {
            width: 100%; padding: 11px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            color: white; font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            box-shadow: 0 3px 12px rgba(0,188,212,0.28); transition: transform 0.15s;
        }
        .btn-save:hover { transform: translateY(-1px); }

        /* Tabla */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 0.71rem; text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--muted); font-weight: 600; padding: 10px 14px;
            border-bottom: 2px solid var(--border); text-align: left; white-space: nowrap;
        }
        tbody tr { border-bottom: 1px solid #f7fafc; transition: background 0.15s; }
        tbody tr:hover { background: #f7fafc; }
        tbody td { padding: 12px 14px; font-size: 0.87rem; color: var(--muted); vertical-align: middle; }

        .user-cell { display: flex; align-items: center; gap: 10px; }
        .avatar {
            width: 34px; height: 34px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.82rem; color: white; flex-shrink: 0;
        }
        .avatar.admin   { background: linear-gradient(135deg, #e53935, #b71c1c); }
        .avatar.usuario { background: linear-gradient(135deg, var(--cyan), var(--cyan-d)); }
        .user-name  { font-weight: 600; color: var(--text); font-size: 0.88rem; }
        .user-login { font-size: 0.76rem; color: var(--muted); }

        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 0.73rem; font-weight: 600; }
        .badge-admin   { background: #ffebee; color: var(--red); }
        .badge-usuario { background: var(--cyan-l); color: var(--cyan-d); }
        .badge-inactivo { background: #f5f5f5; color: #aaa; }

        .dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; margin-right: 4px; }
        .dot-on  { background: var(--green); }
        .dot-off { background: #ccc; }

        /* Acciones */
        .actions { display: flex; gap: 6px; }
        .btn-action {
            padding: 5px 10px; border-radius: 7px; border: 1.5px solid var(--border);
            background: white; font-size: 0.78rem; font-weight: 500;
            cursor: pointer; font-family: 'DM Sans', sans-serif;
            display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;
            color: var(--muted); text-decoration: none;
        }
        .btn-action:hover { border-color: var(--cyan); color: var(--cyan); }
        .btn-action.danger:hover { border-color: var(--red); color: var(--red); }

        /* Modal inline para cambiar nivel */
        .select-nivel { padding: 5px 8px; border: 1.5px solid var(--border); border-radius: 7px; font-family:'DM Sans',sans-serif; font-size:0.8rem; color:var(--text); background:white; cursor:pointer; outline:none; }
        .select-nivel:focus { border-color: var(--cyan); }

        /* Código config */
        .config-card {
            background: var(--surface); border-radius: 18px;
            border: 1.5px solid rgba(229,57,53,0.15);
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            padding: 24px;
        }
        .count-badge { background: var(--cyan); color: white; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-left: auto; }

        @media (max-width: 960px) { .grid-top { grid-template-columns: 1fr; } .nivel-grid { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>

<!-- navegación manejada por el frame padre -->

<div class="page-header">
    <div class="icon-box"><i class="bi bi-people-fill"></i></div>
    <div>
        <h4>Gestión de Usuarios</h4>
        <small>Administra cuentas y permisos del sistema</small>
    </div>
</div>

<?php if ($msg_ok): ?>
<div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg_ok) ?></div>
<?php endif; ?>
<?php if ($msg_err): ?>
<div class="alert alert-err"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($msg_err) ?></div>
<?php endif; ?>

<div class="grid-top">

    <!-- Formulario registro -->
    <div class="card">
        <div class="card-title"><i class="bi bi-person-plus-fill"></i> Nuevo Usuario</div>

        <form action="../guardar.php" method="POST">
            <input type="hidden" name="tabla" value="usuarios">

            <div class="form-group">
                <label>Nombre Completo</label>
                <div class="input-wrap">
                    <i class="bi bi-person ico"></i>
                    <input type="text" name="nombre_completo" placeholder="Ej: Juan Pérez" required>
                </div>
            </div>
            <div class="form-group">
                <label>Nombre de Usuario</label>
                <div class="input-wrap">
                    <i class="bi bi-at ico"></i>
                    <input type="text" name="nombre_usuario" placeholder="Ej: jperez" autocomplete="off" required>
                </div>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <div class="input-wrap">
                    <i class="bi bi-lock ico"></i>
                    <input type="password" name="contrasena" id="pwReg" placeholder="Mínimo 6 caracteres" required>
                    <button type="button" class="pw-toggle" onclick="togglePw('pwReg','eyeReg')">
                        <i class="bi bi-eye" id="eyeReg"></i>
                    </button>
                </div>
            </div>

            <label style="font-size:0.73rem;font-weight:600;color:var(--muted);letter-spacing:0.05em;text-transform:uppercase;display:block;margin-bottom:8px;">Tipo de cuenta</label>
            <div class="nivel-grid">
                <div class="nivel-opt">
                    <input type="radio" name="nivel_acceso" id="ru" value="usuario" checked onchange="toggleCodigo()">
                    <label for="ru"><i class="bi bi-person-fill"></i> Usuario</label>
                </div>
                <div class="nivel-opt">
                    <input type="radio" name="nivel_acceso" id="ra" value="admin" onchange="toggleCodigo()">
                    <label for="ra"><i class="bi bi-shield-fill"></i> Admin</label>
                </div>
            </div>

            <div class="codigo-box" id="codigoBox">
                <span class="codigo-label"><i class="bi bi-key-fill"></i> Código de Administrador</span>
                <input type="text" name="codigo_admin" id="codigoInput" placeholder="Código secreto" autocomplete="off">
            </div>

            <button type="submit" class="btn-save"><i class="bi bi-person-check-fill"></i> Registrar Usuario</button>
        </form>
    </div>

    <!-- Config código admin -->
    <div class="config-card">
        <div class="card-title"><i class="bi bi-key-fill"></i> Código de Administrador</div>
        <p style="font-size:0.85rem;color:var(--muted);margin-bottom:16px;">
            Este código es requerido para registrar nuevas cuentas de administrador desde la pantalla de login o desde este panel. Cámbialo periódicamente.
        </p>
        <form method="POST" action="f_usuarios.php">
            <input type="hidden" name="accion" value="cambiar_codigo">
            <div class="form-group">
                <label>Código actual</label>
                <div class="input-wrap">
                    <i class="bi bi-lock-fill ico"></i>
                    <input type="text" value="<?= htmlspecialchars($codigo_actual) ?>" disabled style="letter-spacing:0.15em;font-weight:700;">
                </div>
            </div>
            <div class="form-group">
                <label>Nuevo código</label>
                <div class="input-wrap">
                    <i class="bi bi-key ico"></i>
                    <input type="text" name="nuevo_codigo" placeholder="Mínimo 4 caracteres" autocomplete="off" style="letter-spacing:0.1em;" required>
                </div>
            </div>
            <button type="submit" class="btn-save" style="width:auto;padding:10px 22px;">
                <i class="bi bi-arrow-repeat"></i> Actualizar código
            </button>
        </form>
    </div>
</div>

<!-- Lista de usuarios -->
<div class="card">
    <div class="card-title">
        <i class="bi bi-list-ul"></i> Usuarios Registrados
        <span class="count-badge"><?= count($usuarios) ?></span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Nivel</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u):
                $ini = strtoupper(substr($u['nombre_completo'], 0, 1));
                $p   = explode(' ', $u['nombre_completo']);
                if (isset($p[1])) $ini .= strtoupper(substr($p[1], 0, 1));
                $es_yo = ((int)$u['id_usuario'] === (int)$_SESSION['id_usuario']);
            ?>
            <tr>
                <td>
                    <div class="user-cell">
                        <div class="avatar <?= $u['nivel_acceso'] ?>"><?= $ini ?></div>
                        <div>
                            <div class="user-name"><?= htmlspecialchars($u['nombre_completo']) ?> <?= $es_yo ? '<span style="font-size:0.7rem;background:#e0f7fa;color:#0097a7;padding:2px 7px;border-radius:20px;font-weight:600;">Tú</span>' : '' ?></div>
                            <div class="user-login">@<?= htmlspecialchars($u['nombre_usuario']) ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-<?= $u['nivel_acceso'] ?>">
                        <i class="bi bi-<?= $u['nivel_acceso'] === 'admin' ? 'shield-fill' : 'person-fill' ?>"></i>
                        <?= $u['nivel_acceso'] === 'admin' ? 'Admin' : 'Usuario' ?>
                    </span>
                </td>
                <td>
                    <span class="dot <?= $u['activo'] ? 'dot-on' : 'dot-off' ?>"></span>
                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                </td>
                <td><?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?></td>
                <td>
                    <?php if (!$es_yo): ?>
                    <form method="POST" action="f_usuarios.php" style="display:inline;">
                        <input type="hidden" name="accion" value="cambiar_nivel">
                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                        <select name="nuevo_nivel" class="select-nivel" onchange="this.form.submit()">
                            <option value="usuario" <?= $u['nivel_acceso']==='usuario' ? 'selected' : '' ?>>Usuario</option>
                            <option value="admin"   <?= $u['nivel_acceso']==='admin'   ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </form>
                    <?php else: ?>
                    <span style="font-size:0.78rem;color:var(--muted);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="actions">
                        <?php if (!$es_yo && $u['activo']): ?>
                        <a href="f_usuarios.php?eliminar=<?= $u['id_usuario'] ?>"
                           class="btn-action danger"
                           onclick="return confirm('¿Desactivar a <?= htmlspecialchars($u['nombre_completo']) ?>?')">
                            <i class="bi bi-person-dash"></i> Desactivar
                        </a>
                        <?php elseif (!$u['activo']): ?>
                        <span style="font-size:0.78rem;color:#ccc;">Inactivo</span>
                        <?php else: ?>
                        <span style="font-size:0.78rem;color:var(--muted);">—</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function togglePw(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text'; icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password'; icon.className = 'bi bi-eye';
    }
}
function toggleCodigo() {
    var adminRol = document.getElementById('ra');
    var box      = document.getElementById('codigoBox');
    var input    = document.getElementById('codigoInput');
    if (adminRol.checked) {
        box.className  = 'codigo-box visible';
        input.required = true;
    } else {
        box.className  = 'codigo-box';
        input.required = false;
        input.value    = '';
    }
}
</script>
</body>
</html>
