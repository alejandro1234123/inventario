<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: ../login.php'); exit(); }
include '../db.php';

// Filtros
$filtro_nombre     = isset($_GET['nombre'])     ? trim($_GET['nombre'])     : '';
$filtro_categoria  = isset($_GET['categoria'])  ? (int)$_GET['categoria']  : 0;
$filtro_depto      = isset($_GET['depto'])      ? (int)$_GET['depto']      : 0;
$filtro_estado     = isset($_GET['estado'])     ? trim($_GET['estado'])     : '';

// Construir query con filtros
$where   = ["a.activo = 1"];
$params  = [];

if ($filtro_nombre !== '') {
    $where[]  = "(a.nombre LIKE ? OR a.serial LIKE ?)";
    $params[] = '%' . $filtro_nombre . '%';
    $params[] = '%' . $filtro_nombre . '%';
}
if ($filtro_categoria > 0) {
    $where[]  = "c.id_categoria = ?";
    $params[] = $filtro_categoria;
}
if ($filtro_depto > 0) {
    $where[]  = "a.id_departamento = ?";
    $params[] = $filtro_depto;
}
if ($filtro_estado !== '') {
    $where[]  = "a.estado = ?";
    $params[] = $filtro_estado;
}

$whereSQL = implode(' AND ', $where);

$sql = "SELECT a.id_articulo, a.nombre, a.serial, a.estado,
               a.fecha_adquisicion, a.valor_adquisicion, a.moneda,
               a.observaciones, a.fecha_registro,
               d.nombre_departamento,
               c.nombre_categoria
        FROM articulos a
        JOIN departamentos d  ON a.id_departamento = d.id_departamento
        JOIN categorias c     ON a.id_categoria    = c.id_categoria
        WHERE $whereSQL
        ORDER BY a.fecha_registro DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para los selects de filtro
$categorias   = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias WHERE activo = 1 ORDER BY nombre_categoria")->fetchAll(PDO::FETCH_ASSOC);
$departamentos= $pdo->query("SELECT id_departamento, nombre_departamento FROM departamentos WHERE activo = 1 ORDER BY nombre_departamento")->fetchAll(PDO::FETCH_ASSOC);

$estados_colores = [
    'excelente' => ['bg' => '#e8f5e9', 'color' => '#43a047', 'icon' => 'bi-star-fill'],
    'bueno'     => ['bg' => '#e0f7fa', 'color' => '#0097a7', 'icon' => 'bi-check-circle-fill'],
    'regular'   => ['bg' => '#fff3e0', 'color' => '#fb8c00', 'icon' => 'bi-dash-circle-fill'],
    'malo'      => ['bg' => '#ffebee', 'color' => '#e53935', 'icon' => 'bi-x-circle-fill'],
    'baja'      => ['bg' => '#f5f5f5', 'color' => '#9e9e9e', 'icon' => 'bi-archive-fill'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario</title>
    <link rel="stylesheet" href="../bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cyan:  #00bcd4; --cyan-d: #0097a7; --cyan-l: #e0f7fa;
            --bg:    #f4f7f6; --surface: #fff;
            --border:#e2e8f0; --text: #1a202c; --muted: #718096;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); padding: 28px; }

        /* Header */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
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

        .btn-nuevo {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 18px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            color: white; font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem; font-weight: 600; cursor: pointer;
            box-shadow: 0 3px 12px rgba(0,188,212,0.28);
            transition: transform 0.15s; text-decoration: none;
        }
        .btn-nuevo:hover { transform: translateY(-1px); color: white; }

        /* Filtros */
        .filters-card {
            background: var(--surface); border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            padding: 16px 20px; margin-bottom: 18px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 10px; align-items: end;
        }
        .filter-group label {
            display: block; font-size: 0.71rem; font-weight: 600;
            color: var(--muted); margin-bottom: 5px;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .filter-group input,
        .filter-group select {
            width: 100%; background: var(--bg);
            border: 1.5px solid var(--border); border-radius: 9px;
            color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem; padding: 9px 12px; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(0,188,212,0.12);
        }
        .btn-filter {
            padding: 9px 16px; border: none; border-radius: 9px;
            background: var(--cyan); color: white;
            font-family: 'DM Sans', sans-serif; font-size: 0.88rem;
            font-weight: 600; cursor: pointer; white-space: nowrap;
            display: flex; align-items: center; gap: 6px;
            transition: background 0.15s;
        }
        .btn-filter:hover { background: var(--cyan-d); }
        .btn-clear {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 0.78rem; color: var(--muted); text-decoration: none;
            margin-top: 8px; transition: color 0.15s;
        }
        .btn-clear:hover { color: var(--cyan); }

        /* Stats rápidas */
        .quick-stats {
            display: flex; gap: 10px; margin-bottom: 18px;
        }
        .qs {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; padding: 12px 18px;
            display: flex; align-items: center; gap: 10px;
            font-size: 0.84rem; box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        }
        .qs i { color: var(--cyan); font-size: 1.1rem; }
        .qs strong { font-size: 1.1rem; font-weight: 700; }
        .qs span { color: var(--muted); }

        /* Tabla */
        .table-card {
            background: var(--surface); border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            overflow: hidden;
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

        .bien-cell { display: flex; flex-direction: column; gap: 2px; }
        .bien-nombre { font-weight: 700; color: var(--text); font-size: 0.9rem; }
        .bien-serial { font-size: 0.75rem; color: var(--muted); font-family: monospace; }

        .cat-cell { display: flex; flex-direction: column; gap: 2px; }
        .cat-nombre { font-weight: 500; color: var(--text); }
        .cat-sub { font-size: 0.75rem; color: var(--muted); }

        .badge-estado {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 20px;
            font-size: 0.73rem; font-weight: 700; white-space: nowrap;
        }

        .btn-accion {
            padding: 4px 10px; border-radius: 7px; border: 1.5px solid var(--border);
            background: white; font-size: 0.76rem; font-weight: 500; cursor: pointer;
            font-family: 'DM Sans', sans-serif; display: inline-flex; align-items: center;
            gap: 4px; transition: all 0.15s; color: var(--muted);
        }
        .btn-accion:hover { border-color: var(--cyan); color: var(--cyan); }

        /* Modal actualizar estado */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.4); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: white; border-radius: 16px; padding: 28px;
            width: 100%; max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: popIn 0.2s cubic-bezier(.22,1,.36,1);
        }
        @keyframes popIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }
        .modal-title { font-size: 1rem; font-weight: 700; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .modal-title i { color: var(--cyan); }
        .modal-field { margin-bottom: 14px; }
        .modal-field label { display: block; font-size: 0.74rem; font-weight: 600; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
        .modal-field select, .modal-field textarea {
            width: 100%; background: var(--bg); border: 1.5px solid var(--border);
            border-radius: 10px; color: var(--text); font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem; padding: 10px 12px; outline: none;
        }
        .modal-field textarea { resize: vertical; min-height: 72px; }
        .modal-actions { display: flex; gap: 8px; margin-top: 20px; justify-content: flex-end; }
        .btn-modal-save {
            padding: 10px 20px; border: none; border-radius: 9px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            color: white; font-family: 'DM Sans', sans-serif; font-size: 0.88rem;
            font-weight: 600; cursor: pointer;
        }
        .btn-modal-cancel {
            padding: 10px 16px; border-radius: 9px; border: 1.5px solid var(--border);
            background: white; color: var(--muted); font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem; cursor: pointer;
        }
        .valor-cell.vacio { color: var(--muted); font-weight: 400; font-style: italic; }

        .depto-cell {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 8px; padding: 4px 10px;
            font-size: 0.78rem; font-weight: 500; color: var(--text);
            white-space: nowrap;
        }
        .depto-cell i { color: var(--cyan); font-size: 0.82rem; }

        .fecha-cell { font-size: 0.78rem; white-space: nowrap; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--muted);
        }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; opacity: 0.3; }
        .empty-state p { font-size: 0.9rem; }

        .count-badge {
            background: var(--cyan); color: white;
            font-size: 0.7rem; font-weight: 700;
            padding: 2px 9px; border-radius: 20px; margin-left: 8px;
        }

        @media (max-width: 900px) {
            .filters-grid { grid-template-columns: 1fr 1fr; }
            .quick-stats { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <div class="page-header-left">
        <div class="icon-box"><i class="bi bi-archive"></i></div>
        <div>
            <h4>Inventario <span class="count-badge"><?= count($articulos) ?></span></h4>
            <small>Listado completo de bienes registrados</small>
        </div>
    </div>
    <button class="btn-nuevo" onclick="parent.loadPage('formularios/f_articulos.php', null, 'Registrar Bien')">
        <i class="bi bi-plus-lg"></i> Registrar Bien
    </button>
</div>

<!-- Filtros -->
<div class="filters-card">
    <form method="GET" action="inventario.php">
        <div class="filters-grid">
            <div class="filter-group">
                <label><i class="bi bi-search"></i> Buscar</label>
                <input type="text" name="nombre" placeholder="Nombre o serial..."
                       value="<?= htmlspecialchars($filtro_nombre) ?>">
            </div>
            <div class="filter-group">
                <label>Categoría</label>
                <select name="categoria">
                    <option value="0">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>" <?= $filtro_categoria == $cat['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre_categoria']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Departamento</label>
                <select name="depto">
                    <option value="0">Todos</option>
                    <?php foreach ($departamentos as $dep): ?>
                    <option value="<?= $dep['id_departamento'] ?>" <?= $filtro_depto == $dep['id_departamento'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dep['nombre_departamento']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="">Todos</option>
                    <option value="excelente" <?= $filtro_estado==='excelente' ? 'selected':'' ?>>Excelente</option>
                    <option value="bueno"     <?= $filtro_estado==='bueno'     ? 'selected':'' ?>>Bueno</option>
                    <option value="regular"   <?= $filtro_estado==='regular'   ? 'selected':'' ?>>Regular</option>
                    <option value="malo"      <?= $filtro_estado==='malo'      ? 'selected':'' ?>>Malo</option>
             
                </select>
            </div>
            <div>
                <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrar</button>
            </div>
        </div>
        <?php if ($filtro_nombre || $filtro_categoria || $filtro_depto || $filtro_estado): ?>
        <a href="inventario.php" class="btn-clear"><i class="bi bi-x-circle"></i> Limpiar filtros</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabla -->
<div class="table-card">
    <div class="table-wrap">
        <?php if (empty($articulos)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>No se encontraron bienes<?= ($filtro_nombre || $filtro_categoria || $filtro_depto || $filtro_estado) ? ' con los filtros aplicados' : ' registrados aún' ?>.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bien</th>
                    <th>Categoría</th>
                    <th>Departamento</th>
                    <th>Estado</th>
                    <th>Valor</th>
                    <th>Fecha Adquisición</th>
                    <?php if ($_SESSION['nivel_acceso'] === 'admin'): ?>
                    <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($articulos as $i => $a):
                $est = isset($estados_colores[$a['estado']]) ? $estados_colores[$a['estado']] : $estados_colores['baja'];
            ?>
            <tr>
                <td style="color:var(--muted);font-size:0.78rem;"><?= $i + 1 ?></td>
                <td>
                    <div class="bien-cell">
                        <span class="bien-nombre"><?= htmlspecialchars($a['nombre'] ?: '—') ?></span>
                        <span class="bien-serial"><i class="bi bi-upc" style="font-size:0.7rem"></i> <?= htmlspecialchars($a['serial']) ?></span>
                    </div>
                </td>
                <td>
                    <span class="cat-nombre"><?= htmlspecialchars($a['nombre_categoria']) ?></span>
                </td>
                <td>
                    <span class="depto-cell">
                        <i class="bi bi-building"></i>
                        <?= htmlspecialchars($a['nombre_departamento']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge-estado" style="background:<?= $est['bg'] ?>;color:<?= $est['color'] ?>;">
                        <i class="bi <?= $est['icon'] ?>"></i>
                        <?= ucfirst($a['estado']) ?>
                    </span>
                </td>
                <td>
                    <?php if ($a['valor_adquisicion']): ?>
                    <span class="valor-cell"><?= isset($a['moneda']) ? htmlspecialchars($a['moneda']) : '$' ?> <?= number_format($a['valor_adquisicion'], 2) ?></span>
                    <?php else: ?>
                    <span class="valor-cell vacio">Sin valor</span>
                    <?php endif; ?>
                </td>
                <td class="fecha-cell">
                    <?= $a['fecha_adquisicion'] ? date('d/m/Y', strtotime($a['fecha_adquisicion'])) : '<span style="color:var(--muted);font-style:italic;">—</span>' ?>
                </td>
                <?php if ($_SESSION['nivel_acceso'] === 'admin'): ?>
                <td>
                    <button class="btn-accion"
                        onclick="abrirModalEstado(<?= $a['id_articulo'] ?>, '<?= htmlspecialchars($a['nombre'] ?: $a['serial'], ENT_QUOTES) ?>', '<?= $a['estado'] ?>')">
                        <i class="bi bi-pencil-fill"></i> Estado
                    </button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal actualizar estado (solo admin) -->
<?php if ($_SESSION['nivel_acceso'] === 'admin'): ?>
<div class="modal-overlay" id="modalEstado">
    <div class="modal-box">
        <div class="modal-title"><i class="bi bi-pencil-fill"></i> Actualizar Estado</div>
        <p style="font-size:0.84rem;color:var(--muted);margin-bottom:16px;" id="modalBienNombre"></p>
        <form action="../guardar.php" method="POST">
            <input type="hidden" name="tabla" value="historial">
            <input type="hidden" name="id_articulo" id="modalIdArticulo">
            <div class="modal-field">
                <label>Nuevo Estado</label>
                <select name="estado_nuevo" id="modalEstadoSelect">
                    <option value="excelente">Excelente</option>
                    <option value="bueno">Bueno</option>
                    <option value="regular">Regular</option>
                    <option value="malo">Malo</option>
                    <option value="baja">Baja</option>
                </select>
            </div>
            <div class="modal-field">
                <label>Motivo del cambio</label>
                <textarea name="motivo" placeholder="Describe el motivo del cambio de estado..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-modal-save"><i class="bi bi-check-lg"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>
<script>
function abrirModalEstado(id, nombre, estadoActual) {
    document.getElementById('modalIdArticulo').value  = id;
    document.getElementById('modalBienNombre').textContent = 'Bien: ' + nombre + ' — Estado actual: ' + estadoActual;
    document.getElementById('modalEstadoSelect').value = estadoActual;
    document.getElementById('modalEstado').classList.add('show');
}
function cerrarModal() {
    document.getElementById('modalEstado').classList.remove('show');
}
document.getElementById('modalEstado').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
</script>
<?php endif; ?>

</body>
</html>