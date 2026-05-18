<?php
/*
 * formularios/f_usuarios.php
 * Crear usuario + Preguntas de Seguridad (FASE 4)
 * Compatible con PHP 5.6 / XAMPP 3.2.2
 */
session_start();
if (!isset($_SESSION['id_usuario']) || $_SESSION['nivel_acceso'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require '../db.php';

$status = isset($_GET['status']) ? $_GET['status'] : '';

// Preguntas predefinidas sugeridas
$preguntasSugeridas = array(
    '¿Cuál es el nombre de tu primera mascota?',
    '¿En qué ciudad naciste?',
    '¿Cuál es el apellido de soltera de tu madre?',
    '¿Cuál es el nombre de tu escuela primaria?',
    '¿Cuál era tu apodo de infancia?',
    '¿Cuál es tu película favorita de infancia?',
    '¿Cuál era la marca de tu primer teléfono móvil?',
    '¿Cuál es el nombre del mejor amigo de tu niñez?',
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Usuario</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root{--cyan:#00bcd4;--cyan-d:#0097a7;--border:#e2e8f0;--text:#1a202c;--muted:#718096;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:#f4f7f6;padding:24px;color:var(--text);}
        h2{font-size:1.3rem;font-weight:700;margin-bottom:20px;}
        .card{background:#fff;border-radius:12px;border:1px solid var(--border);
              padding:24px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.04);}
        .card-title{font-size:.9rem;font-weight:700;margin-bottom:16px;
                    color:var(--cyan-d);text-transform:uppercase;letter-spacing:.04em;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .form-group{display:flex;flex-direction:column;gap:6px;}
        .form-group.full{grid-column:1/-1;}
        label{font-weight:600;font-size:.85rem;}
        input,select{
            padding:10px 12px;border:1px solid var(--border);border-radius:8px;
            font-size:.9rem;font-family:inherit;width:100%;
        }
        input:focus,select:focus{outline:none;border-color:var(--cyan);}
        .policy{
            background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;
            padding:10px 14px;font-size:.8rem;color:#0369a1;margin-top:6px;line-height:1.6;
        }
        .btn{padding:10px 22px;border:none;border-radius:8px;cursor:pointer;
             font-size:.88rem;font-weight:600;}
        .btn-primary{background:var(--cyan);color:#fff;}
        .btn-primary:hover{background:var(--cyan-d);}
        .alert{padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:.88rem;}
        .alert-success{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;}
        .alert-warning{background:#fff8e1;border:1px solid #ffe082;color:#e65100;}
        .separator{border:none;border-top:1px solid var(--border);margin:20px 0;}
        @media(max-width:640px){.form-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<h2><i class="bi bi-person-plus"></i> Crear Nuevo Usuario</h2>

<?php if ($status === 'success'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> Usuario creado exitosamente.</div>
<?php elseif ($status === 'duplicate'): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> El nombre de usuario ya existe. Elige uno diferente.</div>
<?php endif; ?>

<form method="POST" action="../guardar.php">
    <input type="hidden" name="tabla" value="usuarios">

    <!-- ─── Datos del usuario ───────────────────────────── -->
    <div class="card">
        <div class="card-title"><i class="bi bi-person"></i> Datos del usuario</div>
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre de usuario <span style="color:red">*</span></label>
                <input type="text" name="nombre_usuario" required placeholder="Ingrese Nombre de Usuario">
            </div>
            <div class="form-group">
                <label>Nombre completo <span style="color:red">*</span></label>
                <input type="text" name="nombre_completo" required placeholder="Ingrese Nombre Completo">
            </div>
            <div class="form-group">
                <label>Contraseña <span style="color:red">*</span></label>
                <input type="password" name="contrasena" id="contrasena" required
                       placeholder="Ingrese Contraseña"
                       minlength="8" maxlength="16">
                <div class="policy">
                    <strong>Política:</strong> 8–16 caracteres &bull; Solo letras y números &bull;
                    Al menos 1 mayúscula, 1 minúscula y 1 dígito.
                </div>
            </div>
            <div class="form-group">
                <label>Nivel de acceso <span style="color:red">*</span></label>
                <select name="nivel_acceso" required>
                    <option value="usuario">Usuario</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ─── Preguntas de seguridad ───────────────────────── -->
    <div class="card">
        <div class="card-title"><i class="bi bi-shield-lock"></i> Preguntas de seguridad (obligatorias)</div>
        <p style="font-size:.83rem;color:var(--muted);margin-bottom:16px;">
            Estas preguntas se usarán para verificar la identidad del usuario si olvida su contraseña.
            Las respuestas <strong>no distinguen mayúsculas</strong>.
        </p>

        <!-- Pregunta 1 -->
        <div class="form-grid" style="margin-bottom:14px;">
            <div class="form-group full">
                <label>Pregunta 1 <span style="color:red">*</span></label>
                <select name="pregunta_1" required>
                    <option value="">-- Seleccione una pregunta --</option>
                    <?php foreach ($preguntasSugeridas as $pq): ?>
                        <option value="<?= htmlspecialchars($pq) ?>"><?= htmlspecialchars($pq) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Respuesta 1 <span style="color:red">*</span></label>
                <input type="text" name="respuesta_1" required
                       placeholder="Escribe la respuesta (sin importar mayúsculas)">
            </div>
        </div>

        <hr class="separator">

        <!-- Pregunta 2 -->
        <div class="form-grid">
            <div class="form-group full">
                <label>Pregunta 2 <span style="color:red">*</span></label>
                <select name="pregunta_2" required>
                    <option value="">-- Seleccione una pregunta diferente --</option>
                    <?php foreach ($preguntasSugeridas as $pq): ?>
                        <option value="<?= htmlspecialchars($pq) ?>"><?= htmlspecialchars($pq) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Respuesta 2 <span style="color:red">*</span></label>
                <input type="text" name="respuesta_2" required
                       placeholder="Escribe la respuesta (sin importar mayúsculas)">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-person-check"></i> Crear usuario
    </button>
</form>

</body>
</html>
