<?php
/*
 * formularios/f_articulos.php
 * Formulario de registro de Bienes  (tabla: bienes, campo tabla POST: 'bienes')
 * Compatible con PHP 5.6 / XAMPP 3.2.2
 */
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}
require '../db.php';

$status    = isset($_GET['status'])    ? $_GET['status']    : '';
$categorias    = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
$departamentos = $pdo->query("SELECT id_departamento, nombre_departamento FROM departamentos WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Bien</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root{--cyan:#00bcd4;--cyan-d:#0097a7;--border:#e2e8f0;--text:#1a202c;--muted:#718096;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:#f4f7f6;padding:24px;color:var(--text);}
        h2{font-size:1.3rem;font-weight:700;margin-bottom:20px;}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .form-group{display:flex;flex-direction:column;gap:6px;}
        .form-group.full{grid-column:1/-1;}
        label{font-weight:600;font-size:.85rem;color:var(--text);}
        input,select,textarea{
            padding:10px 12px;border:1px solid var(--border);border-radius:8px;
            font-size:.9rem;font-family:inherit;width:100%;
        }
        input:focus,select:focus,textarea:focus{outline:none;border-color:var(--cyan);}
        textarea{resize:vertical;min-height:80px;}
        .btn{
            padding:11px 24px;border:none;border-radius:8px;cursor:pointer;
            font-size:.9rem;font-weight:600;
        }
        .btn-primary{background:var(--cyan);color:#fff;}
        .btn-primary:hover{background:var(--cyan-d);}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.88rem;}
        .alert-success{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;}
        @media(max-width:640px){.form-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<h2><i class="bi bi-box-seam"></i> Registrar Bien</h2>

<?php if ($status === 'success'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> Bien registrado exitosamente.</div>
<?php endif; ?>

<form method="POST" action="../guardar.php">
    <!-- Campo oculto: nombre de tabla actualizado -->
    <input type="hidden" name="tabla" value="bienes">

    <div class="form-grid">

        <div class="form-group">
            <label>Nombre del bien <span style="color:red">*</span></label>
            <input type="text" name="nombre" required placeholder="Ej: Monitor LED 24&quot;">
        </div>

        <div class="form-group">
            <label>Serial <span style="color:red">*</span></label>
            <input type="text" name="serial" required placeholder="SN-2026-001">
        </div>

        <div class="form-group">
            <label>Categoría <span style="color:red">*</span></label>
            <select name="id_categoria" required>
                <option value="">-- Seleccione --</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Departamento <span style="color:red">*</span></label>
            <select name="id_departamento" required>
                <option value="">-- Seleccione --</option>
                <?php foreach ($departamentos as $d): ?>
                    <option value="<?= $d['id_departamento'] ?>"><?= htmlspecialchars($d['nombre_departamento']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Estado <span style="color:red">*</span></label>
            <select name="estado" required>
                <option value="excelente">Excelente</option>
                <option value="bueno" selected>Bueno</option>
                <option value="regular">Regular</option>
                <option value="malo">Malo</option>
                <option value="baja">Baja</option>
            </select>
        </div>

        <div class="form-group">
            <label>Fecha de adquisición</label>
            <input type="date" name="fecha_adquisicion">
        </div>

        <div class="form-group">
            <label>Valor de adquisición</label>
            <input type="number" step="0.01" name="valor_adquisicion" placeholder="0.00">
        </div>

        <div class="form-group">
            <label>Moneda</label>
            <select name="moneda">
                <option value="$">$ (Dólar)</option>
                <option value="Bs">Bs (Bolívar)</option>
                <option value="EUR">EUR (Euro)</option>
            </select>
        </div>

        <div class="form-group full">
            <label>Observaciones</label>
            <textarea name="observaciones" placeholder="Detalles adicionales..."></textarea>
        </div>

    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Bien</button>
    </div>
</form>

</body>
</html>
