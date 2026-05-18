<?php
/*
 * recuperar.php
 * Recuperación de contraseña por preguntas de seguridad
 * FASE 3 (política de contraseña) + FASE 4 (flujo de preguntas)
 * Compatible con PHP 5.6 / XAMPP 3.2.2
 *
 * Flujo:
 *   Paso 1 → Ingresar nombre de usuario
 *   Paso 2 → Responder pregunta 1
 *   Paso 3 → Responder pregunta 2
 *   Paso 4 → Definir nueva contraseña
 *   Paso 5 → Confirmación y regreso al login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

// ── Limpieza de sesión de recuperación al entrar por GET sin parámetros ──────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['paso'])) {
    unset($_SESSION['rec_id_usuario']);
    unset($_SESSION['rec_paso']);
}

$paso  = isset($_SESSION['rec_paso'])      ? (int)$_SESSION['rec_paso']      : 1;
$error = '';
$ok    = '';

// ── Validar política de contraseña (igual que guardar.php) ────────────────────
function validarContrasena($pass) {
    if (strlen($pass) < 8 || strlen($pass) > 16) {
        return 'La contraseña debe tener entre 8 y 16 caracteres.';
    }
    if (!preg_match('/^[A-Za-z0-9]+$/', $pass)) {
        return 'Solo letras y números (sin símbolos ni espacios).';
    }
    if (!preg_match('/[A-Z]/', $pass)) {
        return 'Debe incluir al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-z]/', $pass)) {
        return 'Debe incluir al menos una letra minúscula.';
    }
    if (!preg_match('/[0-9]/', $pass)) {
        return 'Debe incluir al menos un número.';
    }
    return '';
}

// ── Procesamiento POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    // ─ Paso 1: buscar usuario ────────────────────────────────────────────────
    if ($accion === 'buscar_usuario') {
        $nombreUsuario = trim($_POST['nombre_usuario']);

        if (empty($nombreUsuario)) {
            $error = 'Ingresa tu nombre de usuario.';
        } else {
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? AND activo = 1");
            $stmt->execute([$nombreUsuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'No existe ningún usuario activo con ese nombre.';
            } else {
                // Verificar que tenga preguntas configuradas
                $stmtPS = $pdo->prepare("SELECT COUNT(*) FROM preguntas_seguridad WHERE id_usuario = ?");
                $stmtPS->execute([$user['id_usuario']]);
                $totalPS = $stmtPS->fetchColumn();

                if ($totalPS < 2) {
                    $error = 'Este usuario no tiene preguntas de seguridad configuradas. Contacta al administrador.';
                } else {
                    $_SESSION['rec_id_usuario']     = $user['id_usuario'];
                    $_SESSION['rec_nombre_usuario'] = $nombreUsuario;
                    $_SESSION['rec_paso']           = 2;
                    $paso = 2;
                }
            }
        }
    }

    // ─ Paso 2: responder pregunta 1 ──────────────────────────────────────────
    elseif ($accion === 'responder_p1') {
        if (!isset($_SESSION['rec_id_usuario'])) { $paso = 1; }
        else {
            $respuesta = strtolower(trim($_POST['respuesta_1']));
            $idUsuario = $_SESSION['rec_id_usuario'];

            $stmt = $pdo->prepare("
                SELECT respuesta_hash FROM preguntas_seguridad
                WHERE id_usuario = ? AND numero_pregunta = 1
            ");
            $stmt->execute([$idUsuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($respuesta, $row['respuesta_hash'])) {
                $_SESSION['rec_paso'] = 3;
                $paso = 3;
            } else {
                $error = 'Respuesta incorrecta. Inténtalo de nuevo.';
                $paso  = 2;
            }
        }
    }

    // ─ Paso 3: responder pregunta 2 ──────────────────────────────────────────
    elseif ($accion === 'responder_p2') {
        if (!isset($_SESSION['rec_id_usuario'])) { $paso = 1; }
        else {
            $respuesta = strtolower(trim($_POST['respuesta_2']));
            $idUsuario = $_SESSION['rec_id_usuario'];

            $stmt = $pdo->prepare("
                SELECT respuesta_hash FROM preguntas_seguridad
                WHERE id_usuario = ? AND numero_pregunta = 2
            ");
            $stmt->execute([$idUsuario]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($respuesta, $row['respuesta_hash'])) {
                $_SESSION['rec_paso'] = 4;
                $paso = 4;
            } else {
                $error = 'Respuesta incorrecta. Inténtalo de nuevo.';
                $paso  = 3;
            }
        }
    }

    // ─ Paso 4: nueva contraseña ──────────────────────────────────────────────
    elseif ($accion === 'nueva_contrasena') {
        if (!isset($_SESSION['rec_id_usuario'])) { $paso = 1; }
        else {
            $nuevaPass   = isset($_POST['nueva_contrasena'])    ? $_POST['nueva_contrasena']    : '';
            $confirmaPass = isset($_POST['confirmar_contrasena']) ? $_POST['confirmar_contrasena'] : '';
            $idUsuario   = $_SESSION['rec_id_usuario'];

            if ($nuevaPass !== $confirmaPass) {
                $error = 'Las contraseñas no coinciden.';
                $paso  = 4;
            } else {
                $errPass = validarContrasena($nuevaPass);
                if ($errPass !== '') {
                    $error = $errPass;
                    $paso  = 4;
                } else {
                    $hash = password_hash($nuevaPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET `contraseña` = ? WHERE id_usuario = ?");
                    $stmt->execute([$hash, $idUsuario]);

                    // Limpiar sesión de recuperación
                    unset($_SESSION['rec_id_usuario']);
                    unset($_SESSION['rec_nombre_usuario']);
                    unset($_SESSION['rec_paso']);

                    $paso = 5; // Éxito
                }
            }
        }
    }
}

// ── Obtener pregunta del paso actual ─────────────────────────────────────────
$preguntaActual = '';
if (($paso === 2 || $paso === 3) && isset($_SESSION['rec_id_usuario'])) {
    $numPregunta = ($paso === 2) ? 1 : 2;
    $stmtPQ = $pdo->prepare("
        SELECT pregunta FROM preguntas_seguridad
        WHERE id_usuario = ? AND numero_pregunta = ?
    ");
    $stmtPQ->execute([$_SESSION['rec_id_usuario'], $numPregunta]);
    $rowPQ = $stmtPQ->fetch(PDO::FETCH_ASSOC);
    $preguntaActual = $rowPQ ? $rowPQ['pregunta'] : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--cyan:#00bcd4;--cyan-d:#0097a7;--border:#e2e8f0;--text:#1a202c;--muted:#718096;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{
            background:linear-gradient(135deg,var(--cyan) 0%,var(--cyan-d) 100%);
            font-family:'DM Sans',sans-serif;min-height:100vh;
            display:flex;justify-content:center;align-items:center;padding:20px;
        }
        .box{
            width:100%;max-width:440px;background:white;border-radius:18px;
            padding:36px 40px;box-shadow:0 20px 60px rgba(0,0,0,.25);
        }
        .box-header{text-align:center;margin-bottom:28px;}
        .box-header .icon{
            width:60px;height:60px;border-radius:16px;
            background:linear-gradient(135deg,var(--cyan),var(--cyan-d));
            display:flex;align-items:center;justify-content:center;
            font-size:1.6rem;color:white;margin:0 auto 14px;
        }
        .box-header h2{font-size:1.35rem;font-weight:700;margin-bottom:4px;}
        .box-header p{color:var(--muted);font-size:.85rem;}

        /* Stepper */
        .stepper{display:flex;justify-content:center;gap:6px;margin-bottom:24px;}
        .step{
            width:28px;height:6px;border-radius:99px;
            background:var(--border);transition:background .3s;
        }
        .step.done  { background:var(--cyan-d); }
        .step.active{ background:var(--cyan); }

        .form-group{margin-bottom:18px;}
        label{display:block;font-weight:600;font-size:.87rem;margin-bottom:7px;color:var(--text);}
        input{
            width:100%;padding:12px 14px;border:1px solid var(--border);
            border-radius:10px;font-size:.95rem;transition:.2s;
        }
        input:focus{outline:none;border-color:var(--cyan);}
        .policy{
            background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;
            padding:9px 12px;font-size:.78rem;color:#0369a1;margin-top:6px;line-height:1.6;
        }
        .btn{
            width:100%;padding:13px;border:none;border-radius:10px;
            background:linear-gradient(135deg,var(--cyan),var(--cyan-d));
            color:white;font-size:.95rem;font-weight:700;cursor:pointer;transition:.25s;
        }
        .btn:hover{transform:translateY(-2px);}
        .error-box{
            background:#ffebee;border:1px solid #ffcdd2;color:#c62828;
            padding:11px 14px;border-radius:8px;margin-bottom:18px;font-size:.85rem;
        }
        .success-box{
            background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;
            padding:11px 14px;border-radius:8px;margin-bottom:18px;font-size:.85rem;
        }
        .pregunta-label{
            background:#f7fafc;border:1px solid var(--border);border-radius:8px;
            padding:10px 14px;font-size:.87rem;color:var(--text);margin-bottom:14px;
            font-style:italic;
        }
        .link-back{
            display:block;text-align:center;margin-top:16px;
            color:var(--muted);font-size:.82rem;text-decoration:none;
        }
        .link-back:hover{color:var(--cyan-d);}
    </style>
</head>
<body>
<div class="box">

    <!-- ─ Stepper (pasos 1..4, paso 5 = éxito) ─────────────────────── -->
    <?php if ($paso < 5): ?>
    <div class="stepper">
        <?php for ($i = 1; $i <= 4; $i++): ?>
            <div class="step <?php
                if ($i < $paso)       echo 'done';
                elseif ($i === $paso) echo 'active';
            ?>"></div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="error-box"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- PASO 1: ingresar nombre de usuario                              -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <?php if ($paso === 1): ?>
    <div class="box-header">
        <div class="icon"><i class="bi bi-key"></i></div>
        <h2>Recuperar contraseña</h2>
        <p>Ingresa tu nombre de usuario para comenzar.</p>
    </div>
    <form method="POST" action="recuperar.php">
        <input type="hidden" name="accion" value="buscar_usuario">
        <div class="form-group">
            <label>Nombre de usuario</label>
            <input type="text" name="nombre_usuario" required
                   placeholder="ej: jperez"
                   value="<?= isset($_POST['nombre_usuario']) ? htmlspecialchars($_POST['nombre_usuario']) : '' ?>">
        </div>
        <button type="submit" class="btn"><i class="bi bi-arrow-right-circle"></i> Continuar</button>
    </form>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- PASO 2: pregunta de seguridad 1                                 -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <?php elseif ($paso === 2): ?>
    <div class="box-header">
        <div class="icon"><i class="bi bi-shield-check"></i></div>
        <h2>Verificación — Pregunta 1 de 2</h2>
        <p>Usuario: <strong><?= htmlspecialchars($_SESSION['rec_nombre_usuario']) ?></strong></p>
    </div>
    <div class="pregunta-label">
        <i class="bi bi-question-circle"></i> <?= htmlspecialchars($preguntaActual) ?>
    </div>
    <form method="POST" action="recuperar.php">
        <input type="hidden" name="accion" value="responder_p1">
        <div class="form-group">
            <label>Tu respuesta</label>
            <input type="text" name="respuesta_1" required placeholder="Escribe tu respuesta..." autocomplete="off">
        </div>
        <button type="submit" class="btn"><i class="bi bi-arrow-right-circle"></i> Continuar</button>
    </form>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- PASO 3: pregunta de seguridad 2                                 -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <?php elseif ($paso === 3): ?>
    <div class="box-header">
        <div class="icon"><i class="bi bi-shield-check"></i></div>
        <h2>Verificación — Pregunta 2 de 2</h2>
        <p>Usuario: <strong><?= htmlspecialchars($_SESSION['rec_nombre_usuario']) ?></strong></p>
    </div>
    <div class="pregunta-label">
        <i class="bi bi-question-circle"></i> <?= htmlspecialchars($preguntaActual) ?>
    </div>
    <form method="POST" action="recuperar.php">
        <input type="hidden" name="accion" value="responder_p2">
        <div class="form-group">
            <label>Tu respuesta</label>
            <input type="text" name="respuesta_2" required placeholder="Escribe tu respuesta..." autocomplete="off">
        </div>
        <button type="submit" class="btn"><i class="bi bi-arrow-right-circle"></i> Continuar</button>
    </form>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- PASO 4: nueva contraseña                                        -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <?php elseif ($paso === 4): ?>
    <div class="box-header">
        <div class="icon"><i class="bi bi-lock"></i></div>
        <h2>Nueva contraseña</h2>
        <p>Identidad verificada. Define tu nueva contraseña.</p>
    </div>
    <form method="POST" action="recuperar.php">
        <input type="hidden" name="accion" value="nueva_contrasena">
        <div class="form-group">
            <label>Nueva contraseña</label>
            <input type="password" name="nueva_contrasena" required
                   placeholder="Ej: Segura2024" minlength="8" maxlength="16">
            <div class="policy">
                <strong>Política:</strong> 8–16 caracteres &bull; Solo letras y números &bull;
                Al menos 1 mayúscula, 1 minúscula y 1 dígito.
            </div>
        </div>
        <div class="form-group">
            <label>Confirmar contraseña</label>
            <input type="password" name="confirmar_contrasena" required
                   placeholder="Repite la contraseña" minlength="8" maxlength="16">
        </div>
        <button type="submit" class="btn"><i class="bi bi-check-circle"></i> Guardar contraseña</button>
    </form>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- PASO 5: éxito                                                   -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <?php elseif ($paso === 5): ?>
    <div class="box-header">
        <div class="icon" style="background:linear-gradient(135deg,#43a047,#2e7d32)">
            <i class="bi bi-check-lg"></i>
        </div>
        <h2>¡Contraseña actualizada!</h2>
        <p>Tu contraseña se ha cambiado exitosamente.</p>
    </div>
    <div class="success-box">
        <i class="bi bi-check-circle"></i>
        Ahora puedes iniciar sesión con tu nueva contraseña.
    </div>
    <a href="login.php" class="btn" style="display:block;text-align:center;text-decoration:none;">
        <i class="bi bi-box-arrow-in-right"></i> Ir al Login
    </a>
    <?php endif; ?>

    <?php if ($paso < 5): ?>
    <a href="login.php" class="link-back"><i class="bi bi-arrow-left"></i> Volver al login</a>
    <?php endif; ?>

</div>
</body>
</html>
