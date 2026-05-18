<?php
/*
 * formularios/f_historial.php
 * Historial de Estados de Bienes  (tabla: estados)
 * Compatible con PHP 5.6 / XAMPP 3.2.2
 */
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}
require '../db.php';

$status = isset($_GET['status']) ? $_GET['status'] : '';

// Cargar bienes activos para el selector
$bienes = $pdo->query("
    SELECT b.id_articulo, b.serial, b.nombre, b.estado,
           d.nombre_departamento
    FROM   bienes b
    JOIN   departamentos d ON b.id_departamento = d.id_departamento
    WHERE  b.activo = 1
    ORDER  BY b.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Cargar historial completo (tabla: estados)
$historial = $pdo->query("
    SELECT e.id_historial, e.estado_anterior, e.estado_nuevo,
           e.fecha_cambio, e.motivo,
           b.serial, b.nombre AS nombre_bien,
           u.nombre_completo AS usuario_cambio
    FROM   estados e
    JOIN   bienes b    ON e.id_articulo    = b.id_articulo
    JOIN   usuarios u  ON e.id_usuario_cambio = u.id_usuario
    ORDER  BY e.fecha_cambio DESC
    LIMIT  50
")->fetchAll(PDO::FETCH_ASSOC);

$badgeColor = array(
    'excelente' => '#43a047',
    'bueno'     => '#00bcd4',
    'regular'   => '#fb8c00',
    'malo'      => '#e53935',
    'baja'      => '#757575',
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Estados</title>
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
        .badge{display:inline-block;padding:3px 10px;border-radius:99px;
               font-size:.75rem;font-weight:600;color:#fff;}
        .empty{text-align:center;padding:32px;color:var(--muted);}
        @media(max-width:640px){.form-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<h2><i class="bi bi-activity"></i> Historial de Estados</h2>

<?php if ($status === 'success'): ?>
    <div class="alert-success"><i class="bi bi-check-circle"></i> Cambio de estado registrado.</div>
<?php endif; ?>

<!-- Formulario cambio de estado -->
<div class="card">
    <h3 style="font-size:.95rem;margin-bottom:16px;">Registrar cambio de estado</h3>
    <form method="POST" action="../guardar.php">
        <!-- Nombre de tabla actualizado: 'estados' -->
        <input type="hidden" name="tabla" value="estados">

        <div class="form-grid">
            <div class="form-group full">
                <label>Bien <span style="color:red">*</span></label>
                <select name="id_articulo" required>
                    <option value="">-- Seleccione un bien --</option>
                    <?php foreach ($bienes as $b): ?>
                        <option value="<?= $b['id_articulo'] ?>">
                            [<?= htmlspecialchars($b['serial']) ?>] <?= htmlspecialchars($b['nombre']) ?>
                            — Estado actual: <?= $b['estado'] ?>
                            (<?= htmlspecialchars($b['nombre_departamento']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Nuevo estado <span style="color:red">*</span></label>
                <select name="estado_nuevo" required>
                    <option value="excelente">Excelente</option>
                    <option value="bueno">Bueno</option>
                    <option value="regular">Regular</option>
                    <option value="malo">Malo</option>
                    <option value="baja">Baja</option>
                </select>
            </div>

            <div class="form-group">
                <label>Motivo</label>
                <textarea name="motivo" placeholder="Describe la razón del cambio..."></textarea>
            </div>
        </div>

        <div style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Registrar Estado</button>
        </div>
    </form>
</div>

<!-- Historial -->
<div class="card">
    <h3 style="font-size:.95rem;margin-bottom:16px;">Últimos 50 cambios</h3>
    <?php if (empty($historial)): ?>
        <div class="empty"><i class="bi bi-inbox" style="font-size:1.8rem;"></i><br>Sin registros aún.</div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Bien / Serial</th>
                    <th>Estado anterior</th>
                    <th>Estado nuevo</th>
                    <th>Motivo</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $h): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($h['nombre_bien']) ?></strong><br>
                        <small style="color:var(--muted)"><?= htmlspecialchars($h['serial']) ?></small>
                    </td>
                    <td>
                        <?php
                        $col = isset($badgeColor[$h['estado_anterior']]) ? $badgeColor[$h['estado_anterior']] : '#aaa';
                        ?>
                        <span class="badge" style="background:<?= $col ?>">
                            <?= htmlspecialchars($h['estado_anterior']) ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $col = isset($badgeColor[$h['estado_nuevo']]) ? $badgeColor[$h['estado_nuevo']] : '#aaa';
                        ?>
                        <span class="badge" style="background:<?= $col ?>">
                            <?= htmlspecialchars($h['estado_nuevo']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($h['motivo']) ?></td>
                    <td><?= htmlspecialchars($h['usuario_cambio']) ?></td>
                    <td style="white-space:nowrap"><?= date('d/m/Y H:i', strtotime($h['fecha_cambio'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
