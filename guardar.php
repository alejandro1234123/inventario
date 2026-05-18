<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

require 'db.php';

$id_usuario_sesion = $_SESSION['id_usuario'];

// ============================================================
//  Helper: validar política de contraseñas (FASE 3)
//  - 8..16 caracteres
//  - Solo alfanuméricos (letras + dígitos)
//  - Al menos 1 mayúscula y 1 minúscula
// ============================================================
function validarContrasena($pass) {
    if (strlen($pass) < 8 || strlen($pass) > 16) {
        return 'La contraseña debe tener entre 8 y 16 caracteres.';
    }
    if (!preg_match('/^[A-Za-z0-9]+$/', $pass)) {
        return 'La contraseña solo puede contener letras y números (sin símbolos ni espacios).';
    }
    if (!preg_match('/[A-Z]/', $pass)) {
        return 'La contraseña debe incluir al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-z]/', $pass)) {
        return 'La contraseña debe incluir al menos una letra minúscula.';
    }
    if (!preg_match('/[0-9]/', $pass)) {
        return 'La contraseña debe incluir al menos un número.';
    }
    return ''; // sin error
}

// ============================================================
//  Helper: mostrar error con botón de regreso
// ============================================================
function mostrarError($mensaje, $ruta = 'javascript:history.back()') {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
    <title>Error</title>
    <style>
        body{font-family:sans-serif;background:#f4f7f6;display:flex;align-items:center;
             justify-content:center;min-height:100vh;margin:0;}
        .box{background:#fff;border-radius:12px;padding:32px 40px;
             box-shadow:0 4px 20px rgba(0,0,0,.1);max-width:480px;text-align:center;}
        h2{color:#e53935;margin-bottom:12px;}
        p{color:#4a5568;margin-bottom:20px;line-height:1.5;}
        a{display:inline-block;padding:10px 22px;background:#00bcd4;color:#fff;
          border-radius:8px;text-decoration:none;font-weight:600;}
        a:hover{background:#0097a7;}
    </style></head><body>
    <div class="box">
        <h2>&#9888; Error de validación</h2>
        <p>' . htmlspecialchars($mensaje) . '</p>
        <a href="' . $ruta . '">&#8592; Volver</a>
    </div></body></html>';
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tabla = isset($_POST['tabla']) ? $_POST['tabla'] : '';

    try {
        switch ($tabla) {

            // ── Categorías ───────────────────────────────────────────
            case 'categorias':
                $sql  = "INSERT INTO categorias (nombre_categoria, descripcion) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_POST['nombre_categoria'], $_POST['descripcion']]);
                break;

            // ── Departamentos ────────────────────────────────────────
            case 'departamentos':
                $sql  = "INSERT INTO departamentos (nombre_departamento, descripcion, responsable) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['nombre_departamento'],
                    isset($_POST['descripcion']) ? $_POST['descripcion'] : '',
                    $_POST['responsable']
                ]);
                break;

            // ── Usuarios (FASE 3 + FASE 4) ───────────────────────────
            case 'usuarios':
                // Validar política de contraseña
                $errPass = validarContrasena($_POST['contrasena']);
                if ($errPass !== '') {
                    mostrarError($errPass, 'formularios/f_usuarios.php');
                }

                // Verificar duplicado
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ?");
                $stmtCheck->execute([$_POST['nombre_usuario']]);
                if ($stmtCheck->fetchColumn() > 0) {
                    header("Location: formularios/f_usuarios.php?status=duplicate");
                    exit();
                }

                $pass = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
                $sql  = "INSERT INTO usuarios (nombre_usuario, `contraseña`, nombre_completo, nivel_acceso)
                         VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['nombre_usuario'],
                    $pass,
                    $_POST['nombre_completo'],
                    $_POST['nivel_acceso']
                ]);
                $nuevoIdUsuario = $pdo->lastInsertId();

                // ── FASE 4: guardar preguntas de seguridad ────────────
                if (isset($_POST['pregunta_1']) && isset($_POST['respuesta_1']) &&
                    isset($_POST['pregunta_2']) && isset($_POST['respuesta_2'])) {

                    $sqlPS = "INSERT INTO preguntas_seguridad
                              (id_usuario, numero_pregunta, pregunta, respuesta_hash)
                              VALUES (?, ?, ?, ?)";
                    $stmtPS = $pdo->prepare($sqlPS);

                    $resp1 = strtolower(trim($_POST['respuesta_1']));
                    $resp2 = strtolower(trim($_POST['respuesta_2']));

                    $stmtPS->execute([
                        $nuevoIdUsuario, 1,
                        $_POST['pregunta_1'],
                        password_hash($resp1, PASSWORD_DEFAULT)
                    ]);
                    $stmtPS->execute([
                        $nuevoIdUsuario, 2,
                        $_POST['pregunta_2'],
                        password_hash($resp2, PASSWORD_DEFAULT)
                    ]);
                }
                break;

            // ── Bienes (antes: articulos) ────────────────────────────
            case 'bienes':
                $sql  = "INSERT INTO bienes
                            (nombre, id_categoria, id_departamento, serial, estado,
                             fecha_adquisicion, valor_adquisicion, moneda, observaciones, id_usuario_registro)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    isset($_POST['nombre']) ? $_POST['nombre'] : '',
                    $_POST['id_categoria'],
                    $_POST['id_departamento'],
                    $_POST['serial'],
                    $_POST['estado'],
                    ($_POST['fecha_adquisicion'] !== '') ? $_POST['fecha_adquisicion'] : null,
                    (isset($_POST['valor_adquisicion']) && $_POST['valor_adquisicion'] !== '')
                        ? $_POST['valor_adquisicion'] : null,
                    isset($_POST['moneda']) ? $_POST['moneda'] : '$',
                    isset($_POST['observaciones']) ? $_POST['observaciones'] : '',
                    $id_usuario_sesion
                ]);
                break;

            // ── Traslados (antes: movimientos) ────────────────────────
            case 'traslados':
                // Obtener departamento origen actual del bien
                $stmtOrigen = $pdo->prepare("SELECT id_departamento FROM bienes WHERE id_articulo = ?");
                $stmtOrigen->execute([$_POST['id_articulo']]);
                $origen = $stmtOrigen->fetchColumn();

                $sql  = "INSERT INTO traslados
                            (id_articulo, id_departamento_origen, id_departamento_destino,
                             motivo, id_usuario_responsable)
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['id_articulo'],
                    $origen,
                    $_POST['id_departamento_destino'],
                    $_POST['motivo'],
                    $id_usuario_sesion
                ]);

                // Actualizar departamento del bien
                $stmtUpdate = $pdo->prepare("UPDATE bienes SET id_departamento = ? WHERE id_articulo = ?");
                $stmtUpdate->execute([$_POST['id_departamento_destino'], $_POST['id_articulo']]);
                break;

            // ── Estados (antes: historial) ────────────────────────────
            case 'estados':
                // Recuperar estado actual del bien
                $stmtEst = $pdo->prepare("SELECT estado FROM bienes WHERE id_articulo = ?");
                $stmtEst->execute([$_POST['id_articulo']]);
                $estadoAnterior = $stmtEst->fetchColumn();

                $sql  = "INSERT INTO estados
                            (id_articulo, estado_anterior, estado_nuevo, motivo, id_usuario_cambio)
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['id_articulo'],
                    $estadoAnterior,
                    $_POST['estado_nuevo'],
                    isset($_POST['motivo']) ? $_POST['motivo'] : '',
                    $id_usuario_sesion
                ]);

                // Actualizar estado del bien
                $stmtUpdate = $pdo->prepare("UPDATE bienes SET estado = ? WHERE id_articulo = ?");
                $stmtUpdate->execute([$_POST['estado_nuevo'], $_POST['id_articulo']]);
                break;

            default:
                mostrarError('Acción no reconocida: ' . htmlspecialchars($tabla));
        }

        // Redirigir al formulario correspondiente
        $mapaRutas = array(
            'bienes'    => 'formularios/f_articulos.php',
            'traslados' => 'formularios/f_movimientos.php',
            'estados'   => 'formularios/f_historial.php',
        );
        $ruta = isset($mapaRutas[$tabla])
            ? $mapaRutas[$tabla]
            : 'formularios/f_' . $tabla . '.php';

        header("Location: " . $ruta . "?status=success");
        exit();

    } catch (PDOException $e) {
        echo "<div style='color:red; font-family:sans-serif; padding:20px;'>
                <strong>Error al guardar:</strong> " . $e->getMessage() . "
              </div>";
    }
}
?>
