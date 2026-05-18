<?php
require 'db.php';

$usuario = 'jorge';
$contraseña_nueva = 'Segura2024!';

// Generar hash nuevo
$hash_nuevo = password_hash($contraseña_nueva, PASSWORD_DEFAULT);

echo <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .container {
        background: white;
        border-radius: 16px;
        padding: 40px;
        max-width: 600px;
        width: 100%;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    h1 { color: #2d3748; margin-bottom: 24px; text-align: center; }
    .section {
        background: #f7fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 20px;
    }
    .section h2 {
        font-size: 0.95rem;
        color: #4a5568;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .code {
        background: white;
        border: 1px solid #cbd5e0;
        border-radius: 6px;
        padding: 12px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        overflow-x: auto;
        margin-bottom: 10px;
        word-break: break-all;
    }
    .success { color: #22863a; background: #f0f9ff; border-color: #0366d6; }
    .warning { color: #d73a49; background: #fff5f5; border-color: #d73a49; }
    .copy-btn {
        background: #667eea;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        margin-top: 8px;
        width: 100%;
        transition: background 0.2s;
    }
    .copy-btn:hover { background: #764ba2; }
    .credentials {
        background: #f0f9ff;
        border: 2px solid #0366d6;
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
    }
    .cred-item {
        margin-bottom: 16px;
    }
    .cred-item:last-child { margin-bottom: 0; }
    .cred-label {
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 4px;
    }
    .cred-value {
        background: white;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid #cbd5e0;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        word-break: break-all;
    }
    .steps {
        counter-reset: item;
        list-style-type: none;
        padding: 0;
    }
    .steps li {
        counter-increment: item;
        margin-bottom: 16px;
        padding-left: 30px;
        position: relative;
    }
    .steps li:before {
        content: counter(item);
        position: absolute;
        left: 0;
        top: -2px;
        background: #667eea;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .btn {
        display: inline-block;
        padding: 12px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        text-align: center;
        margin-top: 20px;
        width: 100%;
        border: none;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .btn:hover { transform: translateY(-2px); }
</style>;

try {
    // Verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Crear usuario nuevo
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, `contraseña`, nombre_completo, nivel_acceso, activo) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$usuario, $hash_nuevo, 'Jorge Rafael Admin', 'admin']);
        $accion = "CREADO";
    } else {
        // Actualizar usuario existente
        $stmt = $pdo->prepare("UPDATE usuarios SET `contraseña` = ? WHERE nombre_usuario = ?");
        $stmt->execute([$hash_nuevo, $usuario]);
        $accion = "ACTUALIZADO";
    }

    echo "<div class='container'>";
    echo "<h1>✅ Usuario $accion Correctamente</h1>";

    echo "<div class='credentials'>";
    echo "<div class='cred-item'>";
    echo "<div class='cred-label'>👤 Usuario</div>";
    echo "<div class='cred-value' id='usuario'>$usuario</div>";
    echo "</div>";
    
    echo "<div class='cred-item'>";
    echo "<div class='cred-label'>🔐 Contraseña</div>";
    echo "<div class='cred-value' id='contraseña'>$contraseña_nueva</div>";
    echo "</div>";
    
    echo "<div class='cred-item'>";
    echo "<div class='cred-label'>👑 Nivel</div>";
    echo "<div class='cred-value'>admin</div>";
    echo "</div>";
    echo "</div>";

    echo "<div class='section warning'>";
    echo "<h2>⚠️ Información del Hash</h2>";
    echo "<div style='color: #2d3748; font-size: 0.9rem; line-height: 1.6;'>";
    echo "Se ha generado un <strong>nuevo hash BCrypt</strong> que garantiza compatibilidad.<br><br>";
    echo "<strong>Hash generado:</strong><br>";
    echo "<div class='code'>$hash_nuevo</div>";
    echo "</div>";
    echo "</div>";

    echo "<div class='section success'>";
    echo "<h2>✓ Próximos Pasos</h2>";
    echo "<ol class='steps'>";
    echo "<li><strong>Abre el navegador</strong> e ingresa: <code>http://localhost/login.php</code></li>";
    echo "<li><strong>Usuario:</strong> <code>$usuario</code></li>";
    echo "<li><strong>Contraseña:</strong> <code>$contraseña_nueva</code></li>";
    echo "<li>Haz clic en <strong>Iniciar sesión</strong></li>";
    echo "</ol>";
    echo "</div>";

    echo "<button class='btn' onclick=\"location.href='login.php'\">Ir a Login →</button>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='container'>";
    echo "<h1>❌ Error</h1>";
    echo "<div class='section'>";
    echo "<p style='color: #d73a49; margin-bottom: 16px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: #4a5568; font-size: 0.9rem;'>Verifica que:</p>";
    echo "<ul style='margin-top: 8px; color: #4a5568; font-size: 0.9rem;'>";
    echo "<li>La conexión a la BD es correcta en db.php</li>";
    echo "<li>La tabla 'usuarios' existe</li>";
    echo "<li>Tienes permisos de lectura/escritura</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
}
?>

<script>
function copiar(id) {
    const texto = document.getElementById(id).textContent;
    navigator.clipboard.writeText(texto).then(() => {
        alert('✓ Copiado a portapapeles');
    });
}
</script>
