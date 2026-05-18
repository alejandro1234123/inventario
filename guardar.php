<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

require 'db.php';

$id_usuario_sesion = $_SESSION['id_usuario'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tabla = $_POST['tabla'];

    try {
        switch ($tabla) {
            case 'categorias':
                $sql = "INSERT INTO categorias (nombre_categoria, descripcion) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_POST['nombre_categoria'], $_POST['descripcion']]);
                break;

            case 'departamentos':
                $sql = "INSERT INTO departamentos (nombre_departamento, descripcion, responsable) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['nombre_departamento'],
                    isset($_POST['descripcion']) ? $_POST['descripcion'] : '',
                    $_POST['responsable']
                ]);
                break;

            case 'usuarios':
                // Verificar si el nombre de usuario ya existe
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ?");
                $stmtCheck->execute([$_POST['nombre_usuario']]);
                if ($stmtCheck->fetchColumn() > 0) {
                    header("Location: formularios/f_usuarios.php?status=duplicate");
                    exit();
                }
                $pass = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
                $sql = "INSERT INTO usuarios (nombre_usuario, `contraseña`, nombre_completo, nivel_acceso) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['nombre_usuario'],
                    $pass,
                    $_POST['nombre_completo'],
                    $_POST['nivel_acceso']
                ]);
                break;

            case 'articulos':
                $sql = "INSERT INTO articulos (nombre, id_categoria, id_departamento, serial, estado, fecha_adquisicion, valor_adquisicion, moneda, observaciones, id_usuario_registro) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    isset($_POST['nombre']) ? $_POST['nombre'] : '',
                    $_POST['id_categoria'],
                    $_POST['id_departamento'],
                    $_POST['serial'],
                    $_POST['estado'],
                    $_POST['fecha_adquisicion'] !== '' ? $_POST['fecha_adquisicion'] : null,
                    (isset($_POST['valor_adquisicion']) && $_POST['valor_adquisicion'] !== '') ? $_POST['valor_adquisicion'] : null,
                    isset($_POST['moneda']) ? $_POST['moneda'] : '$',
                    isset($_POST['observaciones']) ? $_POST['observaciones'] : '',
                    $id_usuario_sesion
                ]);
                break;

            case 'movimientos':
                $stmtOrigen = $pdo->prepare("SELECT id_departamento FROM articulos WHERE id_articulo = ?");
                $stmtOrigen->execute([$_POST['id_articulo']]);
                $origen = $stmtOrigen->fetchColumn();

                $sql = "INSERT INTO movimientos (id_articulo, id_departamento_origen, id_departamento_destino, motivo, id_usuario_responsable) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['id_articulo'],
                    $origen,
                    $_POST['id_departamento_destino'],
                    $_POST['motivo'],
                    $id_usuario_sesion
                ]);

                $stmtUpdate = $pdo->prepare("UPDATE articulos SET id_departamento = ? WHERE id_articulo = ?");
                $stmtUpdate->execute([$_POST['id_departamento_destino'], $_POST['id_articulo']]);
                break;

            case 'historial':
                $stmtEst = $pdo->prepare("SELECT estado FROM articulos WHERE id_articulo = ?");
                $stmtEst->execute([$_POST['id_articulo']]);
                $estadoAnterior = $stmtEst->fetchColumn();

                $sql = "INSERT INTO historial_estados (id_articulo, estado_anterior, estado_nuevo, motivo, id_usuario_cambio) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['id_articulo'],
                    $estadoAnterior,
                    $_POST['estado_nuevo'],
                    isset($_POST['motivo']) ? $_POST['motivo'] : '',
                    $id_usuario_sesion
                ]);

                $stmtUpdate = $pdo->prepare("UPDATE articulos SET estado = ? WHERE id_articulo = ?");
                $stmtUpdate->execute([$_POST['estado_nuevo'], $_POST['id_articulo']]);
                break;
        }

        header("Location: formularios/f_" . $tabla . ".php?status=success");
        exit();

    } catch (PDOException $e) {
        echo "<div style='color:red; font-family:sans-serif; padding:20px;'>
                <strong>Error al guardar:</strong> " . $e->getMessage() . "
              </div>";
    }
}
?>
