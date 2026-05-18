<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit(); }
include '../db.php';

// Filtros
$filtro_serial = isset($_GET['serial']) ? trim($_GET['serial']) : '';
$filtro_depto  = isset($_GET['depto'])  ? (int)$_GET['depto']  : 0;
$filtro_desde  = isset($_GET['desde'])  ? trim($_GET['desde'])  : '';
$filtro_hasta  = isset($_GET['hasta'])  ? trim($_GET['hasta'])  : '';

// ── Movimientos de departamento ─────────────────────────
$where_m  = ["1=1"];
$params_m = [];

if ($filtro_serial !== '') {
    $where_m[] = "(a.serial LIKE ? OR a.nombre LIKE ?)";
    $params_m[] = '%' . $filtro_serial . '%';
    $params_m[] = '%' . $filtro_serial . '%';
}
if ($filtro_depto > 0) {
    $where_m[] = "(m.id_departamento_origen = ? OR m.id_departamento_destino = ?)";
    $params_m[] = $filtro_depto;
    $params_m[] = $filtro_depto;
}
if ($filtro_desde !== '') {
    $where_m[] = "DATE(m.fecha_movimiento) >= ?";
    $params_m[] = $filtro_desde;
}
if ($filtro_hasta !== '') {
    $where_m[] = "DATE(m.fecha_movimiento) <= ?";
    $params_m[] = $filtro_hasta;
}

$sqlMov = "SELECT
               m.fecha_movimiento  AS fecha,
               a.nombre            AS nombre_bien,
               a.serial,
               d1.nombre_departamento AS dep_origen,
               d2.nombre_departamento AS dep_destino,
               m.motivo,
               u.nombre_completo   AS responsable
           FROM movimientos m
           JOIN articulos    a  ON m.id_articulo             = a.id_articulo
           JOIN departamentos d1 ON m.id_departamento_origen  = d1.id_departamento
           JOIN departamentos d2 ON m.id_departamento_destino = d2.id_departamento
           JOIN usuarios      u  ON m.id_usuario_responsable  = u.id_usuario
           WHERE " . implode(' AND ', $where_m) . "
           ORDER BY m.fecha_movimiento DESC";

$stmtM = $pdo->prepare($sqlMov);
$stmtM->execute($params_m);
$movimientos = $stmtM->fetchAll(PDO::FETCH_ASSOC);

// ── Cambios de estado ───────────────────────────────────
$where_h  = ["1=1"];
$params_h = [];

if ($filtro_serial !== '') {
    $where_h[] = "(a.serial LIKE ? OR a.nombre LIKE ?)";
    $params_h[] = '%' . $filtro_serial . '%';
    $params_h[] = '%' . $filtro_serial . '%';
}
if ($filtro_desde !== '') {
    $where_h[] = "DATE(h.fecha_cambio) >= ?";
    $params_h[] = $filtro_desde;
}
if ($filtro_hasta !== '') {
    $where_h[] = "DATE(h.fecha_cambio) <= ?";
    $params_h[] = $filtro_hasta;
}

$sqlHist = "SELECT
                h.fecha_cambio   AS fecha,
                a.nombre         AS nombre_bien,
                a.serial,
                h.estado_anterior,
                h.estado_nuevo,
                h.motivo,
                u.nombre_completo AS responsable
            FROM historial_estados h
            JOIN articulos a ON h.id_articulo      = a.id_articulo
            JOIN usuarios  u ON h.id_usuario_cambio = u.id_usuario
            WHERE " . implode(' AND ', $where_h) . "
            ORDER BY h.fecha_cambio DESC";

$stmtH = $pdo->prepare($sqlHist);
$stmtH->execute($params_h);
$cambios_estado = $stmtH->fetchAll(PDO::FETCH_ASSOC);

// Combinar y ordenar por fecha desc
$registros = [];
foreach ($movimientos as $r) {
    $r['tipo'] = 'movimiento';
    $registros[] = $r;
}
foreach ($cambios_estado as $r) {
    $r['tipo'] = 'estado';
    $registros[] = $r;
}
usort($registros, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

$departamentos = $pdo->query("SELECT id_departamento, nombre_departamento FROM departamentos WHERE activo = 1 ORDER BY nombre_departamento")->fetchAll(PDO::FETCH_ASSOC);

$est_cfg = [
    'excelente' => ['bg' => '#e8f5e9', 'color' => '#43a047'],
    'bueno'     => ['bg' => '#e0f7fa', 'color' => '#0097a7'],
    'regular'   => ['bg' => '#fff3e0', 'color' => '#fb8c00'],
    'malo'      => ['bg' => '#ffebee', 'color' => '#e53935'],
    'baja'      => ['bg' => '#f5f5f5', 'color' => '#9e9e9e'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial</title>
    <link rel="stylesheet" href="../bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cyan: #00bcd4; --cyan-d: #0097a7; --cyan-l: #e0f7fa;
            --bg: #f4f7f6; --surface: #fff; --border: #e2e8f0;
            --text: #1a202c; --muted: #718096;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); padding: 28px; }

        /* Header */
        .page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
        .icon-box {
            width: 46px; height: 46px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            border-radius: 13px; display: flex; align-items: center;
            justify-content: center; color: white; font-size: 20px;
            box-shadow: 0 4px 14px rgba(0,188,212,0.30);
        }
        .page-header h4 { margin: 0; font-weight: 700; font-size: 1.25rem; }
        .page-header small { color: var(--muted); font-size: 0.82rem; }
        .count-badge {
            background: var(--cyan); color: white; font-size: 0.7rem;
            font-weight: 700; padding: 2px 9px; border-radius: 20px; margin-left: 8px;
        }

        /* Filtros */
        .filters-card {
            background: var(--surface); border-radius: 14px;
            border: 1px solid var(--border); padding: 16px 20px;
            margin-bottom: 18px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 10px; align-items: end;
        }
        .fg label {
            display: block; font-size: 0.71rem; font-weight: 600; color: var(--muted);
            margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .fg input, .fg select {
            width: 100%; background: var(--bg); border: 1.5px solid var(--border);
            border-radius: 9px; color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem; padding: 9px 12px; outline: none;
            transition: border-color 0.2s;
        }
        .fg input:focus, .fg select:focus { border-color: var(--cyan); }
        .btn-filter {
            padding: 9px 16px; border: none; border-radius: 9px;
            background: var(--cyan); color: white; font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 6px; white-space: nowrap;
        }
        .btn-filter:hover { background: var(--cyan-d); }
        .btn-clear {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 0.78rem; color: var(--muted); text-decoration: none; margin-top: 8px;
        }
        .btn-clear:hover { color: var(--cyan); }

        /* Tabla */
        .table-card {
            background: var(--surface); border-radius: 16px;
            border: 1px solid var(--border); overflow: hidden;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
        }
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
        tbody td { padding: 13px 16px; font-size: 0.86rem; color: var(--muted); vertical-align: middle; }

        .badge-tipo {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 11px; border-radius: 20px; font-size: 0.72rem; font-weight: 700;
            white-space: nowrap;
        }
        .badge-mov { background: #e0f7fa; color: #0097a7; }
        .badge-est { background: #f3e8ff; color: #7c3aed; }

        .badge-estado {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 700;
        }

        .bien-nombre { font-weight: 700; color: var(--text); font-size: 0.9rem; }
        .bien-serial { font-size: 0.74rem; color: var(--muted); font-family: monospace; }

        .depto-flow { display: flex; align-items: center; gap: 6px; font-size: 0.83rem; flex-wrap: wrap; }
        .depto-tag {
            display: inline-flex; align-items: center; gap: 4px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 7px; padding: 3px 9px; font-size: 0.78rem; font-weight: 500; color: var(--text);
        }
        .depto-tag i { color: var(--cyan); font-size: 0.8rem; }
        .arrow-icon { color: var(--muted); font-size: 0.78rem; }

        .fecha-cell { font-size: 0.78rem; white-space: nowrap; }
        .motivo-cell {
            max-width: 200px; font-size: 0.8rem;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; opacity: 0.3; }
        .empty-state p { font-size: 0.9rem; }

        @media (max-width: 900px) { .filters-grid { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>

<div class="page-header">
    <div class="icon-box"><i class="bi bi-clock-history"></i></div>
    <div>
        <h4>Historial <span class="count-badge"><?= count($registros) ?></span></h4>
        <small>Registro de movimientos entre departamentos y cambios de estado</small>
    </div>
</div>

<!-- Filtros -->
<div class="filters-card">
    <form method="GET" action="f_historial.php">
        <div class="filters-grid">
            <div class="fg">
                <label><i class="bi bi-search"></i> Buscar</label>
                <input type="text" name="serial" placeholder="Nombre o serial del bien..."
                       value="<?= htmlspecialchars($filtro_serial) ?>">
            </div>
            <div class="fg">
                <label>Departamento</label>
                <select name="depto">
                    <option value="0">Todos</option>
                    <?php foreach ($departamentos as $dep): ?>
                    <option value="<?= $dep['id_departamento'] ?>"
                        <?= $filtro_depto == $dep['id_departamento'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dep['nombre_departamento']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg">
                <label>Desde</label>
                <input type="date" name="desde" value="<?= htmlspecialchars($filtro_desde) ?>">
            </div>
            <div class="fg">
                <label>Hasta</label>
                <input type="date" name="hasta" value="<?= htmlspecialchars($filtro_hasta) ?>">
            </div>
            <div>
                <button type="submit" class="btn-filter">
                    <i class="bi bi-funnel-fill"></i> Filtrar
                </button>
            </div>
        </div>
        <?php if ($filtro_serial || $filtro_depto || $filtro_desde || $filtro_hasta): ?>
        <a href="f_historial.php" class="btn-clear">
            <i class="bi bi-x-circle"></i> Limpiar filtros
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla -->
<div class="table-card">
    <div class="table-wrap">
        <?php if (empty($registros)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>No hay registros en el historial<?= ($filtro_serial || $filtro_depto || $filtro_desde || $filtro_hasta) ? ' con los filtros aplicados' : ' aún' ?>.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Bien</th>
                    <th>Detalle</th>
                    <th>Motivo</th>
                    <th>Responsable</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($registros as $r): ?>
            <tr>
                <td class="fecha-cell">
                    <?= date('d/m/Y', strtotime($r['fecha'])) ?><br>
                    <span style="color:var(--muted);font-size:0.72rem;">
                        <?= date('h:i A', strtotime($r['fecha'])) ?>
                    </span>
                </td>
                <td>
                    <?php if ($r['tipo'] === 'movimiento'): ?>
                        <span class="badge-tipo badge-mov">
                            <i class="bi bi-arrow-left-right"></i> Movimiento
                        </span>
                    <?php else: ?>
                        <span class="badge-tipo badge-est">
                            <i class="bi bi-activity"></i> Estado
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="bien-nombre"><?= htmlspecialchars($r['nombre_bien'] ?: '—') ?></div>
                    <div class="bien-serial"><?= htmlspecialchars($r['serial']) ?></div>
                </td>
                <td>
                    <?php if ($r['tipo'] === 'movimiento'): ?>
                        <div class="depto-flow">
                            <span class="depto-tag">
                                <i class="bi bi-building"></i>
                                <?= htmlspecialchars($r['dep_origen']) ?>
                            </span>
                            <i class="bi bi-arrow-right arrow-icon"></i>
                            <span class="depto-tag" style="border-color:var(--cyan);background:var(--cyan-l);">
                                <i class="bi bi-building"></i>
                                <?= htmlspecialchars($r['dep_destino']) ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <?php
                        $ea = isset($est_cfg[$r['estado_anterior']]) ? $est_cfg[$r['estado_anterior']] : ['bg'=>'#eee','color'=>'#999'];
                        $en = isset($est_cfg[$r['estado_nuevo']])    ? $est_cfg[$r['estado_nuevo']]    : ['bg'=>'#eee','color'=>'#999'];
                        ?>
                        <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                            <?php if ($r['estado_anterior']): ?>
                            <span class="badge-estado" style="background:<?= $ea['bg'] ?>;color:<?= $ea['color'] ?>">
                                <?= ucfirst($r['estado_anterior']) ?>
                            </span>
                            <i class="bi bi-arrow-right arrow-icon"></i>
                            <?php endif; ?>
                            <span class="badge-estado" style="background:<?= $en['bg'] ?>;color:<?= $en['color'] ?>">
                                <?= ucfirst($r['estado_nuevo']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="motivo-cell" title="<?= htmlspecialchars($r['motivo']) ?>">
                    <?= htmlspecialchars($r['motivo'] ?: '—') ?>
                </td>
                <td style="font-size:0.83rem;color:var(--text);font-weight:500;">
                    <?= htmlspecialchars($r['responsable']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
