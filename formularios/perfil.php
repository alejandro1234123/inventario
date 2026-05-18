<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}
include '../db.php';

$id = $_SESSION['id_usuario'];
$msg_ok  = '';
$msg_err = '';
$tab_activo = isset($_GET['tab']) ? $_GET['tab'] : 'info';

// Obtener datos actuales del usuario
$stmtU = $pdo->prepare("SELECT nombre_usuario, nombre_completo, nivel_acceso, fecha_creacion FROM usuarios WHERE id_usuario = ?");
$stmtU->execute([$id]);
$usuario = $stmtU->fetch(PDO::FETCH_ASSOC);

// ── ACTUALIZAR INFORMACIÓN ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'actualizar_info') {
        $tab_activo = 'info';
        $nombre_completo = isset($_POST['nombre_completo']) ? trim($_POST['nombre_completo']) : '';
        $nombre_usuario  = isset($_POST['nombre_usuario'])  ? trim($_POST['nombre_usuario'])  : '';

        if ($nombre_completo === '' || $nombre_usuario === '') {
            $msg_err = 'Los campos no pueden estar vacíos.';
        } else {
            // Verificar que el nombre de usuario no lo use otro
            $stmtChk = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ? AND id_usuario != ?");
            $stmtChk->execute([$nombre_usuario, $id]);
            if ($stmtChk->fetchColumn() > 0) {
                $msg_err = 'Ese nombre de usuario ya está en uso por otra cuenta.';
            } else {
                $stmtUpd = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, nombre_usuario = ? WHERE id_usuario = ?");
                $stmtUpd->execute([$nombre_completo, $nombre_usuario, $id]);
                $_SESSION['nombre_completo'] = $nombre_completo;
                $usuario['nombre_completo'] = $nombre_completo;
                $usuario['nombre_usuario']  = $nombre_usuario;
                $msg_ok = 'Información actualizada correctamente.';
            }
        }
    }

    if ($_POST['accion'] === 'cambiar_password') {
        $tab_activo   = 'password';
        $actual       = isset($_POST['password_actual'])   ? $_POST['password_actual']   : '';
        $nueva        = isset($_POST['password_nueva'])    ? $_POST['password_nueva']    : '';
        $confirmar    = isset($_POST['password_confirmar'])? $_POST['password_confirmar']: '';

        if ($actual === '' || $nueva === '' || $confirmar === '') {
            $msg_err = 'Por favor completa todos los campos.';
        } elseif (strlen($nueva) < 6) {
            $msg_err = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $msg_err = 'La nueva contraseña y su confirmación no coinciden.';
        } else {
            $stmtHash = $pdo->prepare("SELECT `contraseña` AS hash FROM usuarios WHERE id_usuario = ?");
            $stmtHash->execute([$id]);
            $hash_actual = $stmtHash->fetchColumn();

            if (!password_verify($actual, $hash_actual)) {
                $msg_err = 'La contraseña actual es incorrecta.';
            } else {
                $nuevo_hash = password_hash($nueva, PASSWORD_DEFAULT);
                $stmtPwd = $pdo->prepare("UPDATE usuarios SET `contraseña` = ? WHERE id_usuario = ?");
                $stmtPwd->execute([$nuevo_hash, $id]);
                $msg_ok = 'Contraseña cambiada correctamente.';
                $tab_activo = 'info';
            }
        }
    }
}

// Iniciales para el avatar
$partes   = explode(' ', $usuario['nombre_completo']);
$iniciales = strtoupper(substr($partes[0], 0, 1));
if (isset($partes[1])) $iniciales .= strtoupper(substr($partes[1], 0, 1));
$es_admin = $usuario['nivel_acceso'] === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="../bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --cyan:   #00bcd4;
            --cyan-d: #0097a7;
            --cyan-l: #e0f7fa;
            --bg:     #f4f7f6;
            --surface:#ffffff;
            --border: #e2e8f0;
            --text:   #1a202c;
            --muted:  #718096;
            --green:  #43a047;
            --red:    #e53935;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); padding: 32px 24px; }

        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 10px;
            border: 1.5px solid var(--border); background: white;
            color: var(--muted); font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem; font-weight: 500; text-decoration: none;
            margin-bottom: 24px; transition: all 0.2s;
        }
        .btn-back:hover { border-color: var(--cyan); color: var(--cyan); }

        /* Layout */
        .layout { display: grid; grid-template-columns: 300px 1fr; gap: 24px; align-items: start; }

        /* Sidebar perfil */
        .profile-sidebar {
            background: var(--surface); border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .profile-banner {
            height: 80px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
        }
        .profile-body { padding: 0 24px 28px; }
        .avatar-wrap {
            margin-top: -32px; margin-bottom: 14px;
        }
        .avatar {
            width: 64px; height: 64px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.3rem; color: white;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .avatar.admin   { background: linear-gradient(135deg, #e53935, #b71c1c); }
        .avatar.usuario { background: linear-gradient(135deg, var(--cyan), var(--cyan-d)); }

        .profile-name { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 2px; }
        .profile-user { font-size: 0.82rem; color: var(--muted); margin-bottom: 12px; }

        .badge-rol {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600; margin-bottom: 20px;
        }
        .badge-rol.admin   { background: #ffebee; color: var(--red); }
        .badge-rol.usuario { background: var(--cyan-l); color: var(--cyan-d); }

        .profile-meta { border-top: 1px solid var(--border); padding-top: 16px; }
        .meta-item {
            display: flex; align-items: center; gap: 10px;
            font-size: 0.83rem; color: var(--muted); margin-bottom: 10px;
        }
        .meta-item i { color: var(--cyan); font-size: 1rem; width: 16px; text-align: center; }
        .meta-item span { color: var(--text); font-weight: 500; }

        /* Menú lateral */
        .profile-nav { border-top: 1px solid var(--border); padding-top: 16px; margin-top: 4px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            font-size: 0.88rem; font-weight: 500; color: var(--muted);
            cursor: pointer; transition: all 0.2s; margin-bottom: 4px;
            border: none; background: none; width: 100%; text-align: left;
            font-family: 'DM Sans', sans-serif;
        }
        .nav-item:hover { background: var(--bg); color: var(--cyan); }
        .nav-item.active { background: var(--cyan-l); color: var(--cyan-d); font-weight: 600; }
        .nav-item i { font-size: 1rem; width: 18px; text-align: center; }

        /* Panel principal */
        .main-panel {
            background: var(--surface); border-radius: 18px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
        }
        .panel-header {
            padding: 24px 28px 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 28px;
        }
        .panel-header h5 {
            font-size: 1rem; font-weight: 700; color: var(--text);
            display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
        }
        .panel-header h5 i { color: var(--cyan); }

        .tab-content { display: none; padding: 0 28px 28px; }
        .tab-content.active { display: block; }

        /* Formularios */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block; font-size: 0.74rem; font-weight: 600;
            color: var(--muted); margin-bottom: 7px;
            letter-spacing: 0.05em; text-transform: uppercase;
        }
        .input-wrap { position: relative; }
        .input-wrap i.ico {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%); color: #b0bec5;
            font-size: 0.95rem; pointer-events: none; transition: color 0.2s;
        }
        .input-wrap input {
            width: 100%; background: var(--bg);
            border: 1.5px solid var(--border); border-radius: 10px;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem; padding: 11px 13px 11px 38px;
            outline: none; transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }
        .input-wrap input:focus {
            border-color: var(--cyan); background: var(--cyan-l);
            box-shadow: 0 0 0 3px rgba(0,188,212,0.12);
        }
        .input-wrap input:disabled { opacity: 0.5; cursor: not-allowed; }
        .input-wrap:focus-within i.ico { color: var(--cyan); }
        .pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #b0bec5;
            cursor: pointer; font-size: 0.95rem; padding: 0; transition: color 0.2s;
        }
        .pw-toggle:hover { color: var(--cyan); }

        .form-hint { font-size: 0.75rem; color: var(--muted); margin-top: 5px; }

        /* Alerts */
        .alert { border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
        .alert-ok  { background: #e8f5e9; border: 1px solid rgba(67,160,71,0.3); color: var(--green); }
        .alert-err { background: #ffebee; border: 1px solid rgba(229,57,53,0.3); color: var(--red); }

        /* Botones */
        .btn-save {
            padding: 11px 24px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            color: white; font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px;
            box-shadow: 0 3px 14px rgba(0,188,212,0.28);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 5px 18px rgba(0,188,212,0.38); }
        .btn-save:active { transform: translateY(0); }

        /* Strength bar */
        .strength-bar { height: 4px; border-radius: 4px; background: var(--border); margin-top: 6px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 4px; width: 0; transition: width 0.3s, background 0.3s; }

        /* Sección peligrosa */
        .danger-zone {
            background: #fff5f5; border: 1.5px solid rgba(229,57,53,0.2);
            border-radius: 14px; padding: 20px 22px; margin-top: 8px;
        }
        .danger-zone h6 { font-size: 0.85rem; font-weight: 700; color: var(--red); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .danger-zone p  { font-size: 0.82rem; color: var(--muted); margin-bottom: 14px; }
        .btn-danger {
            padding: 9px 20px; border-radius: 10px;
            border: 1.5px solid var(--red); background: none;
            color: var(--red); font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s;
        }
        .btn-danger:hover { background: var(--red); color: white; }

        @media (max-width: 860px) {
            .layout { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- navegación manejada por el frame padre -->

<div class="layout">

    <!-- ── Sidebar ── -->
    <div class="profile-sidebar">
        <div class="profile-banner"></div>
        <div class="profile-body">
            <div class="avatar-wrap">
                <div class="avatar <?= $usuario['nivel_acceso'] ?>"><?= $iniciales ?></div>
            </div>
            <div class="profile-name"><?= htmlspecialchars($usuario['nombre_completo']) ?></div>
            <div class="profile-user">@<?= htmlspecialchars($usuario['nombre_usuario']) ?></div>
            <div class="badge-rol <?= $usuario['nivel_acceso'] ?>">
                <i class="bi bi-<?= $es_admin ? 'shield-fill' : 'person-fill' ?>"></i>
                <?= $es_admin ? 'Administrador' : 'Usuario' ?>
            </div>

            <div class="profile-meta">
                <div class="meta-item">
                    <i class="bi bi-calendar3"></i>
                    Miembro desde <span><?= date('d/m/Y', strtotime($usuario['fecha_creacion'])) ?></span>
                </div>
                <div class="meta-item">
                    <i class="bi bi-circle-fill" style="font-size:0.5rem; color:#43a047"></i>
                    Estado <span>Activo</span>
                </div>
            </div>

            <div class="profile-nav">
                <button class="nav-item <?= $tab_activo === 'info' ? 'active' : '' ?>" onclick="switchTab('info')">
                    <i class="bi bi-person-lines-fill"></i> Información personal
                </button>
                <button class="nav-item <?= $tab_activo === 'password' ? 'active' : '' ?>" onclick="switchTab('password')">
                    <i class="bi bi-key-fill"></i> Cambiar contraseña
                </button>
                <button class="nav-item <?= $tab_activo === 'cuenta' ? 'active' : '' ?>" onclick="switchTab('cuenta')">
                    <i class="bi bi-shield-exclamation"></i> Cuenta
                </button>
            </div>
        </div>
    </div>

    <!-- ── Panel principal ── -->
    <div class="main-panel">
        <div class="panel-header">
            <h5 id="panel-title"><i class="bi bi-person-lines-fill"></i> Información personal</h5>
        </div>

        <?php if ($msg_ok): ?>
        <div style="padding: 0 28px;">
            <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg_ok) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($msg_err): ?>
        <div style="padding: 0 28px;">
            <div class="alert alert-err"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($msg_err) ?></div>
        </div>
        <?php endif; ?>

        <!-- Tab: Información personal -->
        <div class="tab-content <?= $tab_activo === 'info' ? 'active' : '' ?>" id="tab-info">
            <form method="POST" action="perfil.php">
                <input type="hidden" name="accion" value="actualizar_info">

                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <div class="input-wrap">
                            <i class="bi bi-person ico"></i>
                            <input type="text" name="nombre_completo"
                                   value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nombre de Usuario</label>
                        <div class="input-wrap">
                            <i class="bi bi-at ico"></i>
                            <input type="text" name="nombre_usuario"
                                   value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nivel de Acceso</label>
                    <div class="input-wrap">
                        <i class="bi bi-shield ico"></i>
                        <input type="text" value="<?= $es_admin ? 'Administrador' : 'Usuario' ?>" disabled>
                    </div>
                    <div class="form-hint">El nivel de acceso solo puede modificarlo un administrador.</div>
                </div>

                <div class="form-group">
                    <label>Fecha de Registro</label>
                    <div class="input-wrap">
                        <i class="bi bi-calendar3 ico"></i>
                        <input type="text" value="<?= date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])) ?>" disabled>
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="bi bi-check-lg"></i> Guardar cambios
                </button>
            </form>
        </div>

        <!-- Tab: Cambiar contraseña -->
        <div class="tab-content <?= $tab_activo === 'password' ? 'active' : '' ?>" id="tab-password">
            <form method="POST" action="perfil.php">
                <input type="hidden" name="accion" value="cambiar_password">

                <div class="form-group">
                    <label>Contraseña Actual</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock ico"></i>
                        <input type="password" name="password_actual" id="pw_actual" placeholder="Tu contraseña actual" required>
                        <button type="button" class="pw-toggle" onclick="togglePw('pw_actual','eye_actual')">
                            <i class="bi bi-eye" id="eye_actual"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill ico"></i>
                        <input type="password" name="password_nueva" id="pw_nueva"
                               placeholder="Mínimo 6 caracteres" required oninput="checkStrength(this.value)">
                        <button type="button" class="pw-toggle" onclick="togglePw('pw_nueva','eye_nueva')">
                            <i class="bi bi-eye" id="eye_nueva"></i>
                        </button>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="form-hint" id="strengthText"></div>
                </div>

                <div class="form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill ico"></i>
                        <input type="password" name="password_confirmar" id="pw_confirm"
                               placeholder="Repite la nueva contraseña" required oninput="checkMatch()">
                        <button type="button" class="pw-toggle" onclick="togglePw('pw_confirm','eye_confirm')">
                            <i class="bi bi-eye" id="eye_confirm"></i>
                        </button>
                    </div>
                    <div class="form-hint" id="matchText"></div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="bi bi-key-fill"></i> Cambiar contraseña
                </button>
            </form>
        </div>

        <!-- Tab: Cuenta -->
        <div class="tab-content <?= $tab_activo === 'cuenta' ? 'active' : '' ?>" id="tab-cuenta">
            <div class="form-group">
                <label>ID de cuenta</label>
                <div class="input-wrap">
                    <i class="bi bi-hash ico"></i>
                    <input type="text" value="<?= $id ?>" disabled>
                </div>
            </div>
            <div class="form-group">
                <label>Estado de la cuenta</label>
                <div class="input-wrap">
                    <i class="bi bi-circle-fill ico" style="color:#43a047; font-size:0.5rem;"></i>
                    <input type="text" value="Activa" disabled>
                </div>
            </div>

            <div class="danger-zone">
                <h6><i class="bi bi-exclamation-triangle-fill"></i> Cerrar sesión</h6>
                <p>Cierra tu sesión actual en el sistema. Tendrás que volver a iniciar sesión para acceder.</p>
                <a href="../logout.php" class="btn-danger">
                    <i class="bi bi-box-arrow-left"></i> Cerrar sesión ahora
                </a>
            </div>
        </div>

    </div><!-- /main-panel -->
</div><!-- /layout -->

<script>
var titulos = {
    'info':     '<i class="bi bi-person-lines-fill"></i> Información personal',
    'password': '<i class="bi bi-key-fill"></i> Cambiar contraseña',
    'cuenta':   '<i class="bi bi-shield-exclamation"></i> Cuenta'
};

function switchTab(tab) {
    var tabs = ['info','password','cuenta'];
    for (var i = 0; i < tabs.length; i++) {
        var t = tabs[i];
        document.getElementById('tab-' + t).className    = 'tab-content' + (t === tab ? ' active' : '');
        var btns = document.querySelectorAll('.nav-item');
        btns[i].className = 'nav-item' + (t === tab ? ' active' : '');
    }
    document.getElementById('panel-title').innerHTML = titulos[tab];
}

function togglePw(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function checkStrength(val) {
    var fill = document.getElementById('strengthFill');
    var text = document.getElementById('strengthText');
    var score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var colors = ['#e53935','#ff7043','#ffa726','#66bb6a','#43a047'];
    var labels = ['Muy débil','Débil','Regular','Buena','Muy fuerte'];
    var pct    = [20,40,60,80,100];

    var idx = score > 0 ? score - 1 : 0;
    fill.style.width      = (val.length === 0 ? 0 : pct[idx]) + '%';
    fill.style.background = colors[idx];
    text.textContent      = val.length === 0 ? '' : labels[idx];
    text.style.color      = colors[idx];
}

function checkMatch() {
    var nueva   = document.getElementById('pw_nueva').value;
    var confirm = document.getElementById('pw_confirm').value;
    var text    = document.getElementById('matchText');
    if (confirm === '') { text.textContent = ''; return; }
    if (nueva === confirm) {
        text.textContent = '✓ Las contraseñas coinciden';
        text.style.color = '#43a047';
    } else {
        text.textContent = '✗ Las contraseñas no coinciden';
        text.style.color = '#e53935';
    }
}
</script>
</body>
</html>
