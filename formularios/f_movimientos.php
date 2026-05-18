<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit(); }
include '../db.php';

// Artículos con nombre, serial y departamento actual
$stmtArt = $pdo->query("
    SELECT a.id_articulo, a.nombre, a.serial, a.estado,
           d.nombre_departamento AS dep_actual
    FROM articulos a
    JOIN departamentos d ON a.id_departamento = d.id_departamento
    WHERE a.activo = 1
    ORDER BY a.nombre, a.serial
");
$articulos = $stmtArt->fetchAll(PDO::FETCH_ASSOC);

$stmtDep = $pdo->query("SELECT id_departamento, nombre_departamento FROM departamentos WHERE activo = 1 ORDER BY nombre_departamento");
$departamentos = $stmtDep->fetchAll(PDO::FETCH_ASSOC);

// Últimos traslados para mostrar como referencia
$stmtRecientes = $pdo->query("
    SELECT m.fecha_movimiento, a.nombre AS nombre_bien, a.serial,
           d1.nombre_departamento AS origen,
           d2.nombre_departamento AS destino,
           m.motivo
    FROM movimientos m
    JOIN articulos    a  ON m.id_articulo             = a.id_articulo
    JOIN departamentos d1 ON m.id_departamento_origen  = d1.id_departamento
    JOIN departamentos d2 ON m.id_departamento_destino = d2.id_departamento
    ORDER BY m.fecha_movimiento DESC
    LIMIT 5
");
$recientes = $stmtRecientes->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Traslados</title>
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
        .page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
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

        /* Layout dos columnas */
        .layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }

        /* Tarjeta genérica */
        .card {
            background: var(--surface); border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .card-header {
            padding: 18px 22px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }
        .card-header h5 { margin: 0; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .card-header h5 i { color: var(--cyan); }
        .card-body { padding: 22px; }

        /* Formulario */
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block; font-size: 0.74rem; font-weight: 600; color: var(--muted);
            margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .input-wrap { position: relative; }
        .input-wrap i.ico {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%); color: #b0bec5;
            font-size: 0.92rem; pointer-events: none; transition: color 0.2s;
        }
        .input-wrap select,
        .input-wrap textarea {
            width: 100%; background: var(--bg);
            border: 1.5px solid var(--border); border-radius: 10px;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem; padding: 10px 12px 10px 36px; outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
            appearance: auto;
        }
        .input-wrap textarea { padding-top: 10px; resize: vertical; min-height: 88px; }
        .input-wrap select:focus,
        .input-wrap textarea:focus {
            border-color: var(--cyan); background: var(--cyan-l);
            box-shadow: 0 0 0 3px rgba(0,188,212,0.12);
        }
        .input-wrap:focus-within i.ico { color: var(--cyan); }

        /* Info del bien seleccionado */
        .bien-info {
            display: none; margin-top: 10px;
            background: var(--cyan-l); border: 1px solid rgba(0,188,212,0.2);
            border-radius: 10px; padding: 12px 14px;
            font-size: 0.82rem;
        }
        .bien-info.show { display: block; }
        .bien-info-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .bien-info-row:last-child { margin-bottom: 0; }
        .bien-info-row i { color: var(--cyan-d); width: 14px; }
        .bien-info-row span { color: var(--text); font-weight: 500; }

        /* Botones */
        .form-actions { display: flex; gap: 10px; margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--border); }
        .btn-save {
            padding: 11px 24px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            color: white; font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px;
            box-shadow: 0 3px 14px rgba(0,188,212,0.28); transition: transform 0.15s;
        }
        .btn-save:hover { transform: translateY(-1px); }
        .btn-cancel {
            padding: 11px 20px; border-radius: 10px;
            border: 1.5px solid var(--border); background: white;
            color: var(--muted); font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem; font-weight: 500; cursor: pointer; transition: all 0.15s;
        }
        .btn-cancel:hover { border-color: var(--cyan); color: var(--cyan); }

        /* Tabla recientes */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.07em;
            color: var(--muted); font-weight: 700; padding: 11px 16px;
            border-bottom: 2px solid var(--border); text-align: left;
            white-space: nowrap; background: #fafbfc;
        }
        tbody tr { border-bottom: 1px solid #f7fafc; transition: background 0.14s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f7fbfc; }
        tbody td { padding: 11px 16px; font-size: 0.84rem; color: var(--muted); vertical-align: middle; }

        .bien-nombre { font-weight: 700; color: var(--text); font-size: 0.86rem; }
        .bien-serial { font-size: 0.72rem; color: var(--muted); font-family: monospace; }

        .depto-flow { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
        .depto-tag {
            display: inline-flex; align-items: center; gap: 3px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 6px; padding: 2px 8px; font-size: 0.76rem; font-weight: 500; color: var(--text);
        }
        .depto-tag i { color: var(--cyan); font-size: 0.72rem; }
        .depto-tag.dest { border-color: var(--cyan); background: var(--cyan-l); }
        .arrow-sm { color: var(--muted); font-size: 0.72rem; }

        .motivo-cell { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.78rem; }
        .fecha-cell { font-size: 0.76rem; white-space: nowrap; }

        .empty-state { text-align: center; padding: 36px 20px; color: var(--muted); }
        .empty-state i { font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3; }
        .empty-state p { font-size: 0.84rem; }

        .count-badge {
            background: var(--cyan); color: white; font-size: 0.68rem;
            font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-left: 6px;
        }

        @media (max-width: 860px) { .layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<div class="alert-ok"><i class="bi bi-check-circle-fill"></i> Traslado registrado con éxito.</div>
<?php endif; ?>

<div class="page-header">
    <div class="icon-box"><i class="bi bi-arrow-left-right"></i></div>
    <div>
        <h4>Traslados</h4>
        <small>Registra el traslado de un bien entre departamentos</small>
    </div>
</div>

<div class="layout">

    <!-- Formulario de traslado -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-arrow-left-right"></i> Nuevo Traslado</h5>
        </div>
        <div class="card-body">
            <form action="../guardar.php" method="POST">
                <input type="hidden" name="tabla" value="movimientos">

                <div class="form-group">
                    <label>Bien a Trasladar <span style="color:var(--red)">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-box-seam ico"></i>
                        <select name="id_articulo" required onchange="mostrarInfoBien(this)">
                            <option value="">Seleccione un bien...</option>
                            <?php foreach ($articulos as $art):
                                $label = $art['nombre'] ? htmlspecialchars($art['nombre']) . ' — ' . htmlspecialchars($art['serial']) : htmlspecialchars($art['serial']);
                            ?>
                            <option value="<?= $art['id_articulo'] ?>"
                                    data-nombre="<?= htmlspecialchars($art['nombre'] ?: $art['serial']) ?>"
                                    data-serial="<?= htmlspecialchars($art['serial']) ?>"
                                    data-depto="<?= htmlspecialchars($art['dep_actual']) ?>"
                                    data-estado="<?= $art['estado'] ?>">
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Info del bien seleccionado -->
                    <div class="bien-info" id="bienInfo">
                        <div class="bien-info-row">
                            <i class="bi bi-building"></i>
                            <span>Ubicación actual: <strong id="infoDepto">—</strong></span>
                        </div>
                        <div class="bien-info-row">
                            <i class="bi bi-activity"></i>
                            <span>Estado: <strong id="infoEstado">—</strong></span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Departamento Destino <span style="color:var(--red)">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-building ico"></i>
                        <select name="id_departamento_destino" required>
                            <option value="">Seleccione destino...</option>
                            <?php foreach ($departamentos as $dep): ?>
                            <option value="<?= $dep['id_departamento'] ?>">
                                <?= htmlspecialchars($dep['nombre_departamento']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Motivo del Traslado <span style="color:var(--red)">*</span></label>
                    <div class="input-wrap">
                        <i class="bi bi-chat-left-text ico"></i>
                        <textarea name="motivo" required placeholder="Ej: Reasignación por mantenimiento, cambio de área..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="bi bi-arrow-left-right"></i> Registrar Traslado
                    </button>
                    <button type="reset" class="btn-cancel" onclick="ocultarInfo()">
                        <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Traslados recientes -->
    <div class="card">
        <div class="card-header">
            <h5>
                <i class="bi bi-clock-history"></i> Traslados Recientes
                <span class="count-badge"><?= count($recientes) ?></span>
            </h5>
        </div>
        <div class="table-wrap">
            <?php if (empty($recientes)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No hay traslados registrados aún.</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Bien</th>
                        <th>Traslado</th>
                        <th>Motivo</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recientes as $r): ?>
                <tr>
                    <td>
                        <div class="bien-nombre"><?= htmlspecialchars($r['nombre_bien'] ?: '—') ?></div>
                        <div class="bien-serial"><?= htmlspecialchars($r['serial']) ?></div>
                    </td>
                    <td>
                        <div class="depto-flow">
                            <span class="depto-tag">
                                <i class="bi bi-building"></i>
                                <?= htmlspecialchars($r['origen']) ?>
                            </span>
                            <i class="bi bi-arrow-right arrow-sm"></i>
                            <span class="depto-tag dest">
                                <i class="bi bi-building"></i>
                                <?= htmlspecialchars($r['destino']) ?>
                            </span>
                        </div>
                    </td>
                    <td class="motivo-cell" title="<?= htmlspecialchars($r['motivo']) ?>">
                        <?= htmlspecialchars($r['motivo'] ?: '—') ?>
                    </td>
                    <td class="fecha-cell">
                        <?= date('d/m/Y', strtotime($r['fecha_movimiento'])) ?><br>
                        <span style="font-size:0.7rem;color:var(--muted)"><?= date('h:i A', strtotime($r['fecha_movimiento'])) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
var estColores = {
    'excelente': { bg: '#e8f5e9', color: '#43a047' },
    'bueno':     { bg: '#e0f7fa', color: '#0097a7' },
    'regular':   { bg: '#fff3e0', color: '#fb8c00' },
    'malo':      { bg: '#ffebee', color: '#e53935' },
    'baja':      { bg: '#f5f5f5', color: '#9e9e9e' },
};

function mostrarInfoBien(sel) {
    var opt   = sel.options[sel.selectedIndex];
    var info  = document.getElementById('bienInfo');
    if (!opt.value) { info.classList.remove('show'); return; }

    document.getElementById('infoDepto').textContent  = opt.dataset.depto  || '—';
    var estado = opt.dataset.estado || '';
    var cfg = estColores[estado] || { bg: '#eee', color: '#999' };
    var estEl = document.getElementById('infoEstado');
    estEl.textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
    estEl.style.background = cfg.bg;
    estEl.style.color      = cfg.color;
    estEl.style.padding    = '1px 8px';
    estEl.style.borderRadius = '20px';
    estEl.style.fontSize   = '0.78rem';
    info.classList.add('show');
}

function ocultarInfo() {
    document.getElementById('bienInfo').classList.remove('show');
}
</script>
</body>
</html>
