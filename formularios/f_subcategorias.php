<?php include '../db.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subcategorías</title>
    <link rel="stylesheet" href="../bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="alert alert-success">✅ Subcategoría guardada con éxito.</div>
<?php endif; ?>

<div class="card shadow-sm p-4" style="max-width: 500px; margin: auto;">
    <h4 class="mb-4"><i class="bi bi-diagram-3"></i> Nueva Subcategoría</h4>

    <form action="../guardar.php" method="POST">
        <input type="hidden" name="tabla" value="subcategorias">
        
        <div class="mb-3">
            <label class="form-label">Categoría Padre:</label>
            <select name="id_categoria" class="form-select" required>
                <option value="">Seleccione una categoría...</option>
                <?php
                $res = $pdo->query("SELECT id_categoria, nombre_categoria FROM categorias WHERE activo = 1");
                while ($c = $res->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='{$c['id_categoria']}'>" . htmlspecialchars($c['nombre_categoria']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Nombre Subcategoría:</label>
            <input type="text" name="nombre_subcategoria" class="form-control" required placeholder="Ej: Laptops">
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción:</label>
            <textarea name="descripcion" class="form-control" rows="2"></textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Subcategoría</button>
            <a href="../index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>

</body>
</html>
