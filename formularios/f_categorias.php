<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit(); }
include '../db.php';

$es_admin = $_SESSION['nivel_acceso'] === 'admin';

$stmtCat = $pdo->query("
    SELECT c.id_categoria, c.nombre_categoria, c.descripcion,
           COUNT(a.id_articulo) AS total_bienes
    FROM categorias c
    LEFT JOIN articulos a ON a.id_categoria = c.id_categoria AND a.activo = 1
    WHERE c.activo = 1
    GROUP BY c.id_categoria
    ORDER BY c.nombre_categoria
");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$cat_seleccionada = isset($_GET['ver']) ? (int)$_GET['ver'] : 0;
$cat_info   = null;
$bienes_cat = array();

if ($cat_seleccionada > 0) {
    $stmtInfo = $pdo->prepare("SELECT * FROM categorias WHERE id_categoria = ? AND activo = 1");
    $stmtInfo->execute(array($cat_seleccionada));
    $cat_info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if ($cat_info) {
        $stmtBienes = $pdo->prepare("
            SELECT a.nombre, a.serial, a.estado, a.valor_adquisicion, a.moneda,
                   a.fecha_adquisicion, c.nombre_categoria
            FROM articulos a
            JOIN categorias c ON a.id_categoria = c.id_categoria
            WHERE c.id_categoria = ? AND a.activo = 1
            ORDER BY a.nombre
        ");
        $stmtBienes->execute(array($cat_seleccionada));
        $bienes_cat = $stmtBienes->fetchAll(PDO::FETCH_ASSOC);
    }
}

$est_cfg = array(
    'excelente' => array('bg' => '#e8f5e9', 'color' => '#43a047'),
    'bueno'     => array('bg' => '#e0f7fa', 'color' => '#0097a7'),
    'regular'   => array('bg' => '#fff3e0', 'color' => '#fb8c00'),
    'malo'      => array('bg' => '#ffebee', 'color' => '#e53935'),
    'baja'      => array('bg' => '#f5f5f5', 'color' => '#9e9e9e'),
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Categorías</title>
    <link rel="stylesheet" href="../bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cyan: #00bcd4; --cyan-d: #0097a7; --cyan-l: #e0f7fa;
            --bg: #f4f7f6; --surface: #fff; --border: #e2e8f0;
            --text: #1a202c; --muted: #718096;
            --green: #43a047; --red: #e53935;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); padding: 28px; }

        /* Header */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-header-left { display: flex; align-items: center; gap: 12px; }
        .icon-box {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            border-radius: 13px; display: flex; align-items: center;
            justify-content: center; color: white; font-size: 20px;
            box-shadow: 0 4px 14px rgba(0,188,212,0.30);
        }
        .page-header h4 { margin: 0; font-weight: 700; font-size: 1.25rem; }
        .page-header small { color: var(--muted); font-size: 0.82rem; }

        /* Alerta */
        .alert-ok {
            background: #e8f5e9; border: 1px solid rgba(67,160,71,0.3);
            border-radius: 10px; padding: 12px 16px; margin-bottom: 20px;
            font-size: 0.85rem; color: var(--green);
            display: flex; align-items: center; gap: 8px;
        }

        /* Botón nuevo */
        .btn-toggle-form {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 18px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            color: white; font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem; font-weight: 600; cursor: pointer;
            box-shadow: 0 3px 12px rgba(0,188,212,0.28); transition: transform 0.15s;
        }
        .btn-toggle-form:hover { transform: translateY(-1px); }

        /* Formulario colapsable */
        .admin-section { margin-bottom: 24px; }
        .form-panel {
            display: none; background: var(--surface); border-radius: 16px;
            border: 1px solid var(--border); box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            padding: 24px; margin-top: 14px; max-width: 560px;
        }
        .form-panel.open { display: block; }
        .form-panel h5 { font-size: 0.95rem; font-weight: 700; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .form-panel h5 i { color: var(--cyan); }
        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block; font-size: 0.74rem; font-weight: 600; color: var(--muted);
            margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .form-group input {
            width: 100%; background: var(--bg); border: 1.5px solid var(--border);
            border-radius: 10px; color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem; padding: 10px 12px; outline: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .form-group input:focus { border-color: var(--cyan); background: var(--cyan-l); }
        .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-save {
            padding: 11px 22px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            color: white; font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px;
            box-shadow: 0 3px 12px rgba(0,188,212,0.25); transition: transform 0.15s;
        }
        .btn-save:hover { transform: translateY(-1px); }

        /* Grid tarjetas */
        .dep-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px; margin-bottom: 28px;
        }
        .dep-card {
            background: var(--surface); border: 1.5px solid var(--border);
            border-radius: 16px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            cursor: pointer; transition: all 0.18s;
            position: relative; overflow: hidden;
            text-decoration: none; display: block; color: var(--text);
        }
        .dep-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--cyan), var(--cyan-d));
            opacity: 0; transition: opacity 0.18s;
        }
        .dep-card:hover { border-color: var(--cyan); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,188,212,0.12); color: var(--text); }
        .dep-card:hover::before { opacity: 1; }
        .dep-card.active { border-color: var(--cyan); box-shadow: 0 6px 20px rgba(0,188,212,0.15); }
        .dep-card.active::before { opacity: 1; }

        .dep-icon {
            width: 42px; height: 42px; border-radius: 12px;
            background: var(--cyan-l); color: var(--cyan-d);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; margin-bottom: 12px;
        }
        .dep-nombre { font-weight: 700; font-size: 0.98rem; margin-bottom: 4px; }
        .dep-desc { font-size: 0.78rem; color: var(--muted); margin-bottom: 12px; }
        .dep-stats { display: flex; align-items: center; justify-content: space-between; }
        .dep-count {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 20px; padding: 4px 10px;
            font-size: 0.76rem; font-weight: 600; color: var(--text);
        }
        .dep-count i { color: var(--cyan); }
        .dep-arrow { color: var(--muted); font-size: 0.9rem; transition: color 0.15s; }
        .dep-card:hover .dep-arrow { color: var(--cyan); }

        /* Empty state */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; opacity: 0.3; }
        .empty-state p { font-size: 0.9rem; }

        /* Panel bienes */
        .bienes-panel {
            background: var(--surface); border-radius: 16px;
            border: 1px solid var(--border); overflow: hidden;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
        }
        .bienes-header {
            padding: 18px 22px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .bienes-header-left { display: flex; align-items: center; gap: 10px; }
        .bienes-header h5 { margin: 0; font-weight: 700; font-size: 1rem; }
        .bienes-header small { color: var(--muted); font-size: 0.8rem; }
        .count-badge {
            background: var(--cyan); color: white; font-size: 0.7rem;
            font-weight: 700; padding: 2px 9px; border-radius: 20px; margin-left: 6px;
        }
        .btn-cerrar {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 14px; border-radius: 9px;
            border: 1.5px solid var(--border); background: white;
            color: var(--muted); font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem; text-decoration: none; transition: all 0.15s;
        }
        .btn-cerrar:hover { border-color: var(--red); color: var(--red); }

        /* Tabla */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--muted); font-weight: 700; padding: 12px 16px;
            border-bottom: 2px solid var(--border); text-align: left;
            white-space: nowrap; background: #fafbfc;
        }
        tbody tr { border-bottom: 1px solid #f7fafc; transition: background 0.14s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f7fbfc; }
        tbody td { padding: 12px 16px; font-size: 0.86rem; color: var(--muted); vertical-align: middle; }

        .bien-nombre { font-weight: 700; color: var(--text); font-size: 0.88rem; }
        .bien-serial { font-size: 0.74rem; color: var(--muted); font-family: monospace; }
        .cat-sub { font-size: 0.75rem; color: var(--muted); }
        .badge-estado {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 20px; font-size: 0.73rem; font-weight: 700;
        }
        .valor-text { font-weight: 600; color: var(--text); white-space: nowrap; }
        .valor-vacio { color: var(--muted); font-style: italic; }

        @media (max-width: 600px) {
            .dep-grid { grid-template-columns: 1fr; }
            .form-row-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<div class="alert-ok"><i class="bi bi-check-circle-fill"></i> Categoría guardada con éxito.</div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <div class="icon-box"><i class="bi bi-tags"></i></div>
        <div>
            <h4>Categorías</h4>
            <small><?= count($categorias) ?> categoría<?= count($categorias) != 1 ? 's' : '' ?> registrada<?= count($categorias) != 1 ? 's' : '' ?></small>
        </div>
    </div>
    <?php if ($es_admin): ?>
    <button class="btn-toggle-form" onclick="toggleForm()">
        <i class="bi bi-plus-lg" id="btnIcon"></i>
        <span id="btnText">Nueva Categoría</span>
    </button>
    <?php endif; ?>
</div>

<?php if ($es_admin): ?>
<div class="admin-section">
    <div class="form-panel" id="formPanel">
        <h5><i class="bi bi-tags"></i> Registrar Nueva Categoría</h5>
        <form action="../guardar.php" method="POST">
            <input type="hidden" name="tabla" value="categorias">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Nombre de la Categoría</label>
                    <input type="text" name="nombre_categoria" placeholder="Ej: Electrónicos" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" placeholder="Descripción breve">
                </div>
            </div>
            <button type="submit" class="btn-save">
                <i class="bi bi-save2"></i> Guardar Categoría
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (empty($categorias)): ?>
<div class="empty-state">
    <i class="bi bi-tags"></i>
    <p>No hay categorías registradas aún.</p>
</div>
<?php else: ?>
<div class="dep-grid">
    <?php foreach ($categorias as $cat): ?>
    <a href="f_categorias.php?ver=<?= $cat['id_categoria'] ?>"
       class="dep-card <?= $cat_seleccionada == $cat['id_categoria'] ? 'active' : '' ?>">
        <div class="dep-icon"><i class="bi bi-tags-fill"></i></div>
        <div class="dep-nombre"><?= htmlspecialchars($cat['nombre_categoria']) ?></div>
        <div class="dep-desc">
            <?= $cat['descripcion'] ? htmlspecialchars($cat['descripcion']) : 'Sin descripción' ?>
        </div>
        <div class="dep-stats">
            <span class="dep-count">
                <i class="bi bi-box-seam"></i>
                <?= $cat['total_bienes'] ?> bien<?= $cat['total_bienes'] != 1 ? 'es' : '' ?>
            </span>
            <i class="bi bi-chevron-right dep-arrow"></i>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($cat_seleccionada > 0 && $cat_info): ?>
<div class="bienes-panel">
    <div class="bienes-header">
        <div class="bienes-header-left">
            <div class="icon-box" style="width:38px;height:38px;font-size:16px;">
                <i class="bi bi-tags-fill"></i>
            </div>
            <div>
                <h5>
                    <?= htmlspecialchars($cat_info['nombre_categoria']) ?>
                    <span class="count-badge"><?= count($bienes_cat) ?></span>
                </h5>
                <?php if ($cat_info['descripcion']): ?>
                <small><?= htmlspecialchars($cat_info['descripcion']) ?></small>
                <?php endif; ?>
            </div>
        </div>
        <a href="f_categorias.php" class="btn-cerrar">
            <i class="bi bi-x-lg"></i> Cerrar
        </a>
    </div>

    <div class="table-wrap">
        <?php if (empty($bienes_cat)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Esta categoría no tiene bienes asignados.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Bien</th>
                    <th>Estado</th>
                    <th>Valor</th>
                    <th>Fecha Adquisición</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bienes_cat as $b):
                $est = isset($est_cfg[$b['estado']]) ? $est_cfg[$b['estado']] : $est_cfg['baja'];
            ?>
            <tr>
                <td>
                    <div class="bien-nombre"><?= htmlspecialchars($b['nombre'] ?: '—') ?></div>
                    <div class="bien-serial"><?= htmlspecialchars($b['serial']) ?></div>
                </td>
                <td>
                    <span class="badge-estado" style="background:<?= $est['bg'] ?>;color:<?= $est['color'] ?>;">
                        <?= ucfirst($b['estado']) ?>
                    </span>
                </td>
                <td>
                    <?php if ($b['valor_adquisicion']): ?>
                    <span class="valor-text"><?= htmlspecialchars($b['moneda'] ?: '$') ?> <?= number_format($b['valor_adquisicion'], 2) ?></span>
                    <?php else: ?>
                    <span class="valor-vacio">—</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.78rem;white-space:nowrap;">
                    <?= $b['fecha_adquisicion'] ? date('d/m/Y', strtotime($b['fecha_adquisicion'])) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function toggleForm() {
    var panel = document.getElementById('formPanel');
    var icon  = document.getElementById('btnIcon');
    var text  = document.getElementById('btnText');
    var open  = panel.classList.contains('open');
    if (open) {
        panel.classList.remove('open');
        icon.className = 'bi bi-plus-lg';
        text.textContent = 'Nueva Categoría';
    } else {
        panel.classList.add('open');
        icon.className = 'bi bi-x-lg';
        text.textContent = 'Cancelar';
    }
}
</script>
</body>
</html>
