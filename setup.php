<?php
require 'db.php';

$nombre_usuario  = 'admin';
$contrasena      = 'admin123';
$nombre_completo = 'Administrador Principal';
$nivel_acceso    = 'admin';

// Verificar si ya existe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ?");
$stmt->execute([$nombre_usuario]);
$existe = $stmt->fetchColumn();

if ($existe) {
    // Actualizar contraseña del admin existente
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE usuarios SET `contraseña` = ? WHERE nombre_usuario = ?");
    $stmt->execute([$hash, $nombre_usuario]);
    $mensaje = "Contraseña del admin actualizada correctamente.";
} else {
    // Insertar admin nuevo
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, `contraseña`, nombre_completo, nivel_acceso) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre_usuario, $hash, $nombre_completo, $nivel_acceso]);
    $mensaje = "Usuario administrador creado correctamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f0f4f8; margin: 0; }
        .box { background: white; border-radius: 16px; padding: 40px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); max-width: 420px; width: 100%; text-align: center; }
        .icon { font-size: 3rem; margin-bottom: 16px; }
        h2 { margin: 0 0 8px; color: #2d3748; }
        p  { color: #718096; margin-bottom: 24px; }
        .credenciales { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 24px; text-align: left; }
        .credenciales div { font-size: 0.9rem; color: #4a5568; margin-bottom: 6px; }
        .credenciales span { font-weight: 700; color: #2d3748; }
        .btn { display: inline-block; padding: 12px 28px; background: #00bcd4; color: white; border-radius: 10px; text-decoration: none; font-weight: 600; }
        .btn:hover { background: #0097a7; }
        .warning { background: #fff8e1; border: 1px solid #ffe082; border-radius: 10px; padding: 12px 16px; font-size: 0.82rem; color: #f57f17; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="box">
    <div class="icon">✅</div>
    <h2>Setup completado</h2>
    <p><?= $mensaje ?></p>

    <div class="credenciales">
        <div>Usuario: <span><?= $nombre_usuario ?></span></div>
        <div>Contraseña: <span><?= $contrasena ?></span></div>
        <div>Nivel: <span><?= $nivel_acceso ?></span></div>
    </div>

    <div class="warning">
        ⚠️ <strong>Importante:</strong> Elimina este archivo (<code>setup.php</code>) después de usarlo.
    </div>

    <a href="login.php" class="btn">Ir al Login →</a>
</div>
</body>
</html>
