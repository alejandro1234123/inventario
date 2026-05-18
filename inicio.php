<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$partes   = explode(' ', $_SESSION['nombre_completo']);
$es_admin = $_SESSION['nivel_acceso'] === 'admin';
require 'db.php';

// Conteos rápidos  (tabla renombrada: articulos → bienes)
$totalBienes        = $pdo->query("SELECT COUNT(*) FROM bienes WHERE activo = 1")->fetchColumn();
$totalDepartamentos = $pdo->query("SELECT COUNT(*) FROM departamentos WHERE activo = 1")->fetchColumn();
$totalCategorias    = $pdo->query("SELECT COUNT(*) FROM categorias WHERE activo = 1")->fetchColumn();
$totalUsuarios      = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn();

// Últimos traslados  (tablas renombradas: movimientos → traslados, articulos → bienes)
$stmtTras = $pdo->query("
    SELECT t.fecha_movimiento,
           b.serial,
           d1.nombre_departamento AS origen,
           d2.nombre_departamento AS destino
    FROM   traslados t
    JOIN   bienes b        ON t.id_articulo           = b.id_articulo
    JOIN   departamentos d1 ON t.id_departamento_origen  = d1.id_departamento
    JOIN   departamentos d2 ON t.id_departamento_destino = d2.id_departamento
    ORDER  BY t.fecha_movimiento DESC
    LIMIT  5
");
$traslados = $stmtTras->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Sistema de Inventario</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --cyan:  #00bcd4; --cyan-d: #0097a7; --cyan-l: #e0f7fa;
            --bg:    #f4f7f6; --surface: #fff;
            --border:#e2e8f0; --text: #1a202c; --muted: #718096;
            --green: #43a047; --amber: #fb8c00; --red: #e53935; --purple: #7c3aed;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); padding: 28px; }

        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 28px; padding-bottom: 20px; border-bottom: 1px solid var(--border);
        }
        .header-left h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 4px; }
        .header-left p { color: var(--muted); font-size: 0.85rem; }
        .header-right a {
            padding: 10px 20px; background: var(--red); color: white;
            border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600;
            transition: background 0.2s;
        }
        .header-right a:hover { background: #c62828; }

        .greeting { margin-bottom: 24px; }
        .greeting h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 2px; }
        .greeting p  { color: var(--muted); font-size: 0.88rem; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .stat-card {
            background: var(--surface); border-radius: 14px;
            border: 1px solid var(--border);
            padding: 18px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            display: flex; align-items: center; gap: 14px;
        }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .stat-icon.cyan   { background: var(--cyan-l);  color: var(--cyan-d); }
        .stat-icon.green  { background: #e8f5e9;         color: var(--green); }
        .stat-icon.amber  { background: #fff3e0;         color: var(--amber); }
        .stat-icon.purple { background: #ede9fe;         color: var(--purple); }
        .stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 0.78rem; color: var(--muted); margin-top: 3px; }

        .bottom-grid { display: grid; grid-template-columns: 1fr; gap: 14px; max-width: 600px; }

        .panel {
            background: var(--surface); border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .panel-head {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 0.88rem; font-weight: 700;
            display: flex; align-items: center; gap: 8px; color: var(--text);
        }
        .panel-head i { color: var(--cyan); }
        .panel-body { padding: 16px 20px; }

        .mov-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0; border-bottom: 1px solid #f7fafc;
            font-size: 0.83rem;
        }
        .mov-item:last-child { border-bottom: none; padding-bottom: 0; }
        .mov-dot {
            width: 32px; height: 32px; border-radius: 9px;
            background: var(--cyan-l); color: var(--cyan-d);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; flex-shrink: 0;
        }
        .mov-serial { font-weight: 600; color: var(--text); }
        .mov-ruta   { color: var(--muted); font-size: 0.78rem; }
        .mov-fecha  { margin-left: auto; font-size: 0.75rem; color: var(--muted); white-space: nowrap; }

        .empty { text-align: center; padding: 28px; color: var(--muted); font-size: 0.85rem; }
        .empty i { font-size: 2rem; display: block; margin-bottom: 6px; opacity: 0.4; }

        @media (max-width: 768px) {
            body { padding: 16px; }
            .header { flex-direction: column; gap: 16px; align-items: flex-start; }
            .header-right { width: 100%; }
            .header-right a { display: block; text-align: center; }
            .stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <h1><i class="bi bi-box-seam"></i> Inventario</h1>
        <p>Sistema de gestión de inventario</p>
    </div>
    <div class="header-right">
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>
</div>

<div class="greeting">
    <h2>¡Bienvenido, <?= htmlspecialchars($partes[0]) ?>! 👋</h2>
    <p><?= $es_admin ? '(Administrador)' : '(Usuario)' ?> • Última conexión: <?= date('d/m/Y H:i') ?></p>
</div>

<!-- Stats -->
<div class="stats">
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="bi bi-box-seam"></i></div>
        <div>
            <div class="stat-value"><?= $totalBienes ?></div>
            <div class="stat-label">Bienes activos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-building"></i></div>
        <div>
            <div class="stat-value"><?= $totalDepartamentos ?></div>
            <div class="stat-label">Departamentos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="bi bi-tags"></i></div>
        <div>
            <div class="stat-value"><?= $totalCategorias ?></div>
            <div class="stat-label">Categorías</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-people"></i></div>
        <div>
            <div class="stat-value"><?= $totalUsuarios ?></div>
            <div class="stat-label">Usuarios activos</div>
        </div>
    </div>
</div>

<!-- Últimos Traslados -->
<div class="bottom-grid">
    <div class="panel">
        <div class="panel-head"><i class="bi bi-clock-history"></i> Últimos Traslados</div>
        <div class="panel-body">
            <?php if (empty($traslados)): ?>
                <div class="empty"><i class="bi bi-inbox"></i> Sin traslados registrados aún.</div>
            <?php else: ?>
                <?php foreach ($traslados as $t): ?>
                <div class="mov-item">
                    <div class="mov-dot"><i class="bi bi-arrow-left-right"></i></div>
                    <div>
                        <div class="mov-serial"><?= htmlspecialchars($t['serial']) ?></div>
                        <div class="mov-ruta"><?= htmlspecialchars($t['origen']) ?> → <?= htmlspecialchars($t['destino']) ?></div>
                    </div>
                    <div class="mov-fecha"><?= date('d/m/y', strtotime($t['fecha_movimiento'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
