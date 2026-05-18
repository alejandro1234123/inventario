<?php
session_start();

// Si ya está logueado, redirige al inicio
if (isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php';
    
 	$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
	$contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
    
    if (empty($usuario) || empty($contrasena)) {
        $error = 'Usuario y contraseña son requeridos';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_usuario, nombre_usuario, nombre_completo, nivel_acceso, `contraseña` FROM usuarios WHERE nombre_usuario = ? AND activo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($contrasena, $user['contraseña'])) {
                // Autenticación correcta
                $_SESSION['id_usuario'] = $user['id_usuario'];
                $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['nivel_acceso'] = $user['nivel_acceso'];
                
                // Redirige al inicio
                header("Location: index.php");
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (Exception $e) {
            $error = 'Error en la autenticación';
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
        :root {
            --cyan: #00bcd4;
            --cyan-d: #0097a7;
            --cyan-l: #e0f7fa;
            --bg: #f4f7f6;
            --surface: #fff;
            --border: #e2e8f0;
            --text: #1a202c;
            --muted: #718096;
            --red: #e53935;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(135deg, var(--cyan) 0%, var(--cyan-d) 100%);
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        
        .login-container {
            background: var(--surface);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-icon {
            width: 60px;
            height: 60px;
            background: var(--cyan-l);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--cyan-d);
            margin: 0 auto 16px;
        }
        
        .login-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }
        
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px var(--cyan-l);
        }
        
        .error-box {
            background: #ffebee;
            border: 1px solid #ffcdd2;
            color: var(--red);
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .error-box i {
            flex-shrink: 0;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--cyan) 0%, var(--cyan-d) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 188, 212, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: var(--muted);
            border-top: 1px solid var(--border);
            padding-top: 16px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <div class="login-icon">
            <i class="bi bi-box-seam"></i>
        </div>
        <h1>Inventario</h1>
        <p>Sistema de gestión de inventario</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="error-box">
            <i class="bi bi-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="usuario">👤 Usuario</label>
            <input type="text" id="usuario" name="usuario" required autofocus placeholder="admin">
        </div>
        
        <div class="form-group">
            <label for="contrasena">🔐 Contraseña</label>
            <input type="password" id="contrasena" name="contrasena" required placeholder="••••••••">
        </div>
        
        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
        </button>
    </form>
    
    <div class="footer-text">
        <p><strong>Demo:</strong> Usuario: <code>admin</code></p>
        <p>Contraseña: ejecuta <code>setup.php</code> para ver</p>
    </div>
</div>

</body>
</html>