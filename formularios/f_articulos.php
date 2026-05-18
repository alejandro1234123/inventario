<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit(); }
include '../db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Bien</title>
    <link rel="stylesheet" href="../bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --cyan: #00bcd4; --cyan-d: #0097a7; --border: #cbd5e1; --bg: #f4f7f6; }
        *, *::before, *::after { box-sizing: border-box; }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; padding: 20px; color: #334155; }
        .form-container {
            max-width: 850px; margin: 0 auto; background: #fff;
            padding: 25px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .alert-success {
            max-width: 850px; margin: 0 auto 20px; padding: 15px;
            background: #d1fae5; color: #065f46; border-radius: 8px; border: 1px solid #a7f3d0;
        }
        .form-section-title {
            font-weight: 600; color: var(--cyan-d); margin: 20px 0 15px;
            border-bottom: 2px solid #e0f7fa; padding-bottom: 8px; font-size: 1.1rem;
        }
        .form-row { display: flex; flex-wrap: wrap; margin-right: -10px; margin-left: -10px; }
        .form-group { flex: 0 0 50%; width: 50%; padding: 0 10px; margin-bottom: 15px; }
        .full-width  { flex: 0 0 100%; width: 100%; padding: 0 10px; margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.85rem; }
        .input-wrap {
            display: flex; align-items: center; border: 1px solid var(--border);
            border-radius: 6px; background: #fff; height: 42px; width: 100%; overflow: hidden;
        }
        .input-wrap i {
            background: #f8fafc; color: #64748b; min-width: 40px; height: 100%;
            display: flex; align-items: center; justify-content: center;
            border-right: 1px solid var(--border); flex-shrink: 0;
        }
        .input-wrap input, .input-wrap select {
            border: none; outline: none; padding: 0 12px; width: 100%;
            height: 100%; font-size: 0.9rem; background: transparent; font-family: inherit;
        }
        /* Valor con moneda */
        .valor-wrap {
            display: flex; align-items: center; border: 1px solid var(--border);
            border-radius: 6px; background: #fff; height: 42px; overflow: hidden;
        }
        .valor-wrap i {
            background: #f8fafc; color: #64748b; min-width: 40px; height: 100%;
            display: flex; align-items: center; justify-content: center;
            border-right: 1px solid var(--border); flex-shrink: 0;
        }
        .moneda-select {
            border: none; border-right: 1px solid var(--border); outline: none;
            background: #f1f5f9; color: #334155; font-family: inherit;
            font-size: 0.85rem; font-weight: 700; height: 100%;
            padding: 0 10px; cursor: pointer; min-width: 68px; flex-shrink: 0;
        }
        .valor-input {
            border: none; outline: none; padding: 0 12px; width: 100%;
            height: 100%; font-size: 0.9rem; background: transparent; font-family: inherit;
        }
        textarea {
            width: 100%; border: 1px solid var(--border); border-radius: 6px;
            padding: 10px; min-height: 80px; outline: none; font-family: inherit; resize: vertical;
        }
        .btn-save {
            background: var(--cyan); color: white; border: none; padding: 12px 30px;
            border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: 0.3s;
        }
        .btn-save:hover { background: var(--cyan-d); transform: translateY(-1px); }
    </style>
</head>
<body>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert-success">✅ Artículo registrado correctamente en el inventario.</div>
<?php endif; ?>

<div class="form-container">
    <form action="../guardar.php" method="POST">
        <input type="hidden" name="tabla" value="articulos">

        <div class="form-section-title"><i class="bi bi-box-seam"></i> Datos del Artículo</div>
        <div class="form-row">
            <div class="form-group">
                <label>Nombre del Bien</label>
                <div class="input-wrap">
                    <i class="bi bi-tag"></i>
                    <input type="text" name="nombre" placeholder="Ej: Monitor LED 24'" required>
                </div>
            </div>
            <div class="form-group">
                <label>Serial</label>
                <div class="input-wrap">
                    <i class="bi bi-hash"></i>
                    <input type="text" name="serial" placeholder="Ingrese número de serie" required>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Categoría</label>
                <div class="input-wrap">
                    <i class="bi bi-layers"></i>
                    <select name="id_categoria" required>
                        <option value="">Seleccione...</option>
                        <?php
                        $resCat = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias WHERE activo = 1 ORDER BY nombre_categoria ASC");
                        while ($c = $resCat->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$c['id_categoria']}'>" . htmlspecialchars($c['nombre_categoria']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Ubicación (Departamento)</label>
                <div class="input-wrap">
                    <i class="bi bi-building"></i>
                    <select name="id_departamento" required>
                        <option value="">Seleccione...</option>
                        <?php
                        $resDep = $pdo->query("SELECT id_departamento, nombre_departamento FROM departamentos WHERE activo = 1 ORDER BY nombre_departamento ASC");
                        while ($d = $resDep->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$d['id_departamento']}'>" . htmlspecialchars($d['nombre_departamento']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section-title"><i class="bi bi-cash-coin"></i> Adquisición y Estado</div>
        <div class="form-row">
            <div class="form-group">
                <label>Fecha de Adquisición</label>
                <div class="input-wrap">
                    <i class="bi bi-calendar3"></i>
                    <input type="date" name="fecha_adquisicion"
                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Estado Inicial</label>
                <div class="input-wrap">
                    <i class="bi bi-check-circle"></i>
                    <select name="estado" required>
                        <option value="excelente">Excelente</option>
                        <option value="bueno" selected>Bueno</option>
                        <option value="regular">Regular</option>
                        <option value="malo">Malo</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Valor de Adquisición</label>
                <div class="valor-wrap">
                    <i class="bi bi-coin"></i>
                    <select name="moneda" class="moneda-select" title="Moneda">
                        <option value="$">$</option>
                        <option value="EUR">EUR</option>
                        <option value="Bs.S">Bs.S</option>
                    </select>
                    <input type="number" name="valor_adquisicion" class="valor-input"
                           step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
        </div>

        <div class="form-section-title"><i class="bi bi-chat-left-text"></i> Observaciones</div>
        <div class="full-width">
            <textarea name="observaciones" placeholder="Detalles adicionales, marca, modelo o especificaciones..."></textarea>
        </div>

        <div style="text-align:right; margin-top:10px; padding:0 10px;">
            <button type="submit" class="btn-save"><i class="bi bi-save"></i> Registrar Artículo</button>
        </div>
    </form>
</div>
</body>
</html>
