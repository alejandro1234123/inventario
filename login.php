<?php
/*
 * login.php  —  FASE 3: enlace a recuperar contraseña
 * Compatible con PHP 5.6 / XAMPP 3.2.2
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php';

    $usuario   = trim($_POST['usuario']);
    $contrasena = $_POST['contrasena'];

    if (empty($usuario) || empty($contrasena)) {
        $error = 'Usuario y contraseña son requeridos.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id_usuario, nombre_usuario, nombre_completo,
                       nivel_acceso, `contraseña`
                FROM   usuarios
                WHERE  nombre_usuario = ? AND activo = 1
            ");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($contrasena, $user['contraseña'])) {
                $_SESSION['id_usuario']      = $user['id_usuario'];
                $_SESSION['nombre_usuario']  = $user['nombre_usuario'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['nivel_acceso']    = $user['nivel_acceso'];
                header("Location: index.php");
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error en la autenticación.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Inventario</title>
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
        .login-container{
            width:100%;max-width:420px;background:white;
            border-radius:18px;padding:40px;
            box-shadow:0 20px 60px rgba(0,0,0,0.25);
        }
        .login-header{text-align:center;margin-bottom:30px;}
        .logo-login{width:180px;margin-bottom:20px;}
        .login-header h1{font-size:42px;color:var(--text);margin-bottom:10px;}
        .subtitle{color:#666;font-size:15px;line-height:1.5;}
        .form-group{margin-bottom:20px;}
        label{display:block;margin-bottom:8px;font-weight:600;color:var(--text);}
        input{width:100%;padding:14px;border:1px solid var(--border);
              border-radius:10px;font-size:15px;transition:0.3s;}
        input:focus{outline:none;border-color:var(--cyan);}
        .btn-login{
            width:100%;padding:14px;border:none;border-radius:10px;
            background:linear-gradient(135deg,var(--cyan) 0%,var(--cyan-d) 100%);
            color:white;font-size:16px;font-weight:bold;cursor:pointer;transition:0.3s;
        }
        .btn-login:hover{transform:translateY(-2px);}
        .error-box{
            background:#ffebee;border:1px solid #ffcdd2;color:#d32f2f;
            padding:12px;border-radius:8px;margin-bottom:20px;
        }
        .footer-text{text-align:center;margin-top:20px;color:var(--muted);font-size:13px;}
        .link-recuperar{
            display:block;text-align:center;margin-top:14px;
            color:var(--cyan-d);font-size:13px;text-decoration:none;
        }
        .link-recuperar:hover{text-decoration:underline;}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="http://localhost/inventario/img/logo.jpg" alt="Logo" class="logo-login">
            <h1>Bienvenidos</h1>
            <p class="subtitle">Sistema de Inventario del Concejo Municipal Libertador</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" required placeholder="Ingrese su usuario">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="contrasena" required placeholder="Ingrese su contraseña">
            </div>
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
            </button>
        </form>

        <!-- FASE 3 / FASE 4: enlace de recuperación -->
        <a href="recuperar.php" class="link-recuperar">
            <i class="bi bi-key"></i> ¿Olvidaste tu contraseña?
        </a>

        <p class="footer-text">Concejo Municipal Libertador &copy; <?= date('Y') ?></p>
    </div>
</body>
</html>
