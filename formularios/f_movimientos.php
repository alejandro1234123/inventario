<?php
/*
 * formularios/f_movimientos.php
 * Registro de Traslados de Bienes  (tabla: traslados)
 * Compatible con PHP 5.6 / XAMPP 3.2.2
 */
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}
require '../db.php';

$status = isset($_GET['status']) ? $_GET['status'] : '';

// Bienes activos con su departamento actual
$bienes = $pdo->query("
    SELECT b.id_articulo, b.serial, b.nombre,
           d.nombre_departamento AS dpto_actual
    FROM   bienes b
    JOIN   departamentos d ON b.id_departamento = d.id_departamento
    WHERE  b.activo = 1
    ORDER  BY b.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$departamentos = $pdo->query("
    SELECT id_departamento, nombre_departamento
    FROM   departamentos
    WHERE  activo = 1
    ORDER  BY nombre_departamento ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Últimos traslados (tabla: traslados)
$traslados = $pdo->query("
    SELECT t.id_movimiento, t.fecha_movimiento, t.motivo,
           b.serial, b.nombre AS nombre_bien,
           d1.nombre_departamento AS origen,
           d2.nombre_departamento AS destino,
           u.nombre_completo AS responsable
    FROM   traslados t
    JOIN   bienes b        ON t.id_articulo           = b.id_articulo
    JOIN   departamentos d1 ON t.id_departamento_origen  = d1.id_departamento
    JOIN   departamentos d2 ON t.id_departamento_destino = d2.id_departamento
    JOIN   usuarios u      ON t.id_usuario_responsable  = u.id_usuario
    ORDER  BY t.fecha_movimiento DESC
    LIMIT  50
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Traslados</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root{--cyan:#00bcd4;--cyan-d:#0097a7;--border:#e2e8f0;--text:#1a202c;--muted:#718096;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',sans-serif;background:#f4f7f6;padding:24px;color:var(--text);}
        h2{font-size:1.3rem;font-weight:700;margin-bottom:20px;}
        .card{background:#fff;border-radius:12px;border:1px solid var(--border);
              padding:20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.04);}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .form-group{display:flex;flex-direction:column;gap:6px;}
        .form-group.full{grid-column:1/-1;}
        label{font-weight:600;font-size:.85rem;}
        select,textarea{
            padding:10px 12px;border:1px solid var(--border);border-radius:8px;
            font-size:.9rem;font-family:inherit;width:100%;
        }
        select:focus,textarea:focus{outline:none;border-color:var(--cyan);}
        textarea{resize:vertical;min-height:72px;}
        .btn{padding:10px 22px;border:none;border-radius:8px;cursor:pointer;
             font-size:.88rem;font-weight:600;}
        .btn-primary{background:var(--cyan);color:#fff;}
        .btn-primary:hover{background:var(--cyan-d);}
        .alert-success{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;
                       padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:.88rem;}
        table{width:100%;border-collapse:collapse;font-size:.84rem;}
        th{background:#f7fafc;padding:10px 12px;text-align:left;
           border-bottom:2px solid var(--border);font-size:.78rem;text-transform:uppercase;
           letter-spacing:.05em;color:var(--muted);}
        td{padding:10px 12px;border-bottom:1px solid #f0f4f8;vertical-align:middle;}
        tr:last-child td{border-bottom:none;}
        .arrow{color:var(--cyan);font-weight:700;padding:0 6px;}
        .empty{text-align:center;padding:32px;color:var(--muted);}
        @media(max-width:640px){.form-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<h2><i class="bi bi-arrow-left-right"></i> Traslados de Bienes</h2>

<?php if ($status === 'success'): ?>
    <div class="alert-success"><i class="bi bi-check-circle"></i> Traslado registrado correctamente.</div>
<?php endif; ?>

<!-- Formulario nuevo traslado -->
<div class="card">
    <h3 style="font-size:.95rem;margin-bottom:16px;">Registrar nuevo traslado</h3>
    <form method="POST" action="../guardar.php">
        <!-- Nombre de tabla actualizado: 'traslados' -->
        <input type="hidden" name="tabla" value="traslados">

        <div class="form-grid">
            <div class="form-group full">
                <label>Bien a trasladar <span style="color:red">*</span></label>
                <select name="id_articulo" required>
                    <option value="">-- Seleccione un bien --</option>
                    <?php foreach ($bienes as $b): ?>
                        <option value="<?= $b['id_articulo'] ?>">
                            [<?= htmlspecialchars($b['serial']) ?>] <?= htmlspecialchars($b['nombre']) ?>
                            (Actual: <?= htmlspecialchars($b['dpto_actual']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Departamento destino <span style="color:red">*</span></label>
                <select name="id_departamento_destino" required>
                    <option value="">-- Seleccione destino --</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= $d['id_departamento'] ?>">
                            <?= htmlspecialchars($d['nombre_departamento']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Motivo del traslado</label>
                <textarea name="motivo" placeholder="Razón del traslado..."></textarea>
            </div>
        </div>

        <div style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Registrar Traslado</button>
        </div>
    </form>
</div>

<!-- Listado de traslados -->
<div class="card">
    <h3 style="font-size:.95rem;margin-bottom:16px;">Últimos 50 traslados</h3>
    <?php if (empty($traslados)): ?>
        <div class="empty"><i class="bi bi-inbox" style="font-size:1.8rem;"></i><br>Sin traslados registrados.</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bien / Serial</th>
                    <th>Ruta</th>
                    <th>Motivo</th>
                    <th>Responsable</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($traslados as $t): ?>
                <tr>
                    <td style="color:var(--muted)"><?= $t['id_movimiento'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($t['nombre_bien']) ?></strong><br>
                        <small style="color:var(--muted)"><?= htmlspecialchars($t['serial']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($t['origen']) ?>
                        <span class="arrow">→</span>
                        <?= htmlspecialchars($t['destino']) ?>
                    </td>
                    <td><?= htmlspecialchars($t['motivo']) ?></td>
                    <td><?= htmlspecialchars($t['responsable']) ?></td>
                    <td style="white-space:nowrap"><?= date('d/m/Y H:i', strtotime($t['fecha_movimiento'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
