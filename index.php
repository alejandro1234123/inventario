<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$partes    = explode(' ', $_SESSION['nombre_completo']);
$iniciales = strtoupper(substr($partes[0], 0, 1));
if (isset($partes[1])) $iniciales .= strtoupper(substr($partes[1], 0, 1));
$es_admin  = $_SESSION['nivel_acceso'] === 'admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario — Concejo Municipal Libertador</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --cyan:   #00bcd4;
            --cyan-d: #0097a7;
            --cyan-l: #e0f7fa;
            --bg:     #f4f7f6;
            --surface:#ffffff;
            --border: #e2e8f0;
            --text:   #1a202c;
            --muted:  #718096;
            --sidebar-w: 250px;
            --topbar-h:  60px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            overflow: hidden; /* sin scroll en el body, el iframe scrollea */
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--cyan);
            display: flex; flex-direction: column;
            z-index: 200;
        }
        .sidebar-header {
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            flex-shrink: 0;
        }
        .sidebar-header h4 {
            font-size: 1rem; font-weight: 700; color: white; margin: 0;
            display: flex; align-items: center; gap: 10px;
        }
        .logo-icon {
            width: 30px; height: 30px; border-radius: 8px;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0;
        }
        .sidebar-nav { padding: 12px 10px; flex: 1; overflow-y: auto; }

        .nav-section {
            font-size: 0.63rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.1em; color: rgba(255,255,255,0.5);
            padding: 0 8px; margin: 14px 0 5px;
        }
        .nav-section:first-child { margin-top: 0; }

        .nav-link {
            display: flex; align-items: center; gap: 9px;
            padding: 9px 10px; border-radius: 10px;
            color: rgba(255,255,255,0.88);
            font-size: 0.87rem; font-weight: 500;
            text-decoration: none; margin-bottom: 1px;
            transition: background 0.15s, color 0.15s;
            cursor: pointer; border: none; background: none;
            width: 100%; text-align: left;
            font-family: 'DM Sans', sans-serif;
        }
        .nav-link i { font-size: 1rem; width: 18px; text-align: center; flex-shrink: 0; }
        .nav-link:hover  { background: rgba(255,255,255,0.16); color: white; }
        .nav-link.active { background: rgba(255,255,255,0.24); color: white; font-weight: 600; }

        /* ── Topbar ── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 24px;
            z-index: 199;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        }

        /* Breadcrumb */
        .topbar-breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.85rem; color: var(--muted);
        }
        .topbar-breadcrumb .current {
            color: var(--text); font-weight: 600;
        }
        .topbar-breadcrumb i { font-size: 0.75rem; }

        /* Perfil btn */
        .profile-btn {
            margin-left: auto;
            display: flex; align-items: center; gap: 8px;
            padding: 5px 10px 5px 5px;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: var(--bg);
            cursor: pointer; transition: all 0.18s;
            position: relative; user-select: none;
        }
        .profile-btn:hover, .profile-btn.open {
            border-color: var(--cyan); background: var(--cyan-l);
        }
        .p-avatar {
            width: 30px; height: 30px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.72rem; color: white; flex-shrink: 0;
        }
        .p-avatar.admin   { background: linear-gradient(135deg, #e53935, #b71c1c); }
        .p-avatar.usuario { background: linear-gradient(135deg, var(--cyan), var(--cyan-d)); }
        .p-name { font-size: 0.82rem; font-weight: 600; color: var(--text); }
        .p-role { font-size: 0.68rem; color: var(--muted); }
        .p-chevron { font-size: 0.72rem; color: var(--muted); transition: transform 0.2s; }
        .profile-btn.open .p-chevron { transform: rotate(180deg); }

        /* Dropdown */
        .profile-dropdown {
            position: absolute;
            top: calc(100% + 8px); right: 0;
            width: 240px;
            background: var(--surface);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            overflow: hidden;
            opacity: 0; transform: translateY(-8px) scale(0.97);
            pointer-events: none;
            transition: opacity 0.16s, transform 0.16s;
            z-index: 300;
        }
        .profile-dropdown.show {
            opacity: 1; transform: translateY(0) scale(1); pointer-events: all;
        }
        .dd-header {
            padding: 14px 16px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-d));
            display: flex; align-items: center; gap: 10px;
        }
        .dd-av {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255,255,255,0.25);
            border: 2px solid rgba(255,255,255,0.4);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem; color: white; flex-shrink: 0;
        }
        .dd-fullname { font-size: 0.85rem; font-weight: 700; color: white; }
        .dd-role-line { font-size: 0.7rem; color: rgba(255,255,255,0.82); margin-top: 1px; }

        .dd-section { padding: 6px; }
        .dd-section + .dd-section { border-top: 1px solid var(--border); }

        .dd-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: 9px;
            text-decoration: none; font-size: 0.84rem; font-weight: 500;
            color: var(--text); transition: background 0.14s;
            cursor: pointer; border: none; background: none;
            width: 100%; font-family: 'DM Sans', sans-serif; text-align: left;
        }
        .dd-item-icon {
            width: 28px; height: 28px; border-radius: 8px;
            background: var(--bg); display: flex; align-items: center;
            justify-content: center; font-size: 0.88rem;
            color: var(--muted); flex-shrink: 0; transition: all 0.14s;
        }
        .dd-item:hover { background: var(--bg); }
        .dd-item:hover .dd-item-icon { background: var(--cyan-l); color: var(--cyan); }
        .dd-item.danger { color: #e53935; }
        .dd-item.danger .dd-item-icon { color: #e53935; }
        .dd-item.danger:hover { background: #fff5f5; }
        .dd-item.danger:hover .dd-item-icon { background: #ffebee; }
        .dd-sub { font-size: 0.7rem; color: var(--muted); font-weight: 400; display: block; }
        .dd-item.danger .dd-sub { color: rgba(229,57,53,0.6); }

        /* ── Área de contenido (iframe) ── */
        .content-area {
            position: fixed;
            top: var(--topbar-h);
            left: var(--sidebar-w);
            right: 0;
            bottom: 0;
        }
        .content-frame {
            width: 100%; height: 100%;
            border: none;
            display: block;
            background: var(--bg);
        }

        /* Loading overlay sobre el iframe */
        .frame-loader {
            position: absolute;
            inset: 0;
            background: var(--bg);
            display: flex; align-items: center; justify-content: center;
            z-index: 10;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
        }
        .frame-loader.show { opacity: 1; pointer-events: all; }
        .spinner {
            width: 36px; height: 36px; border-radius: 50%;
            border: 3px solid var(--border);
            border-top-color: var(--cyan);
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<!-- ── Sidebar ── -->
<div class="sidebar">
    <div class="sidebar-header">
        <h4><span class="logo-icon">📦</span> Inventario</h4>
    </div>
    <div class="sidebar-nav">

        <button class="nav-link" onclick="loadPage('inicio.php', this, 'Inicio')">
            <i class="bi bi-house-fill"></i> Inicio
        </button>

        <div class="nav-section">Inventario</div>
        <button class="nav-link" onclick="loadPage('formularios/inventario.php', this, 'Inventario')">
            <i class="bi bi-archive"></i> Inventario
        </button>
        <button class="nav-link" onclick="loadPage('formularios/f_articulos.php', this, 'Registrar Bien')">
            <i class="bi bi-box"></i> Registrar Bien
        </button>
        <button class="nav-link" onclick="loadPage('formularios/f_movimientos.php', this, 'Traslados')">
            <i class="bi bi-arrow-left-right"></i> Traslados
        </button>
        <button class="nav-link" onclick="loadPage('formularios/f_historial.php', this, 'Historial de Estados')">
            <i class="bi bi-clock-history"></i> Historial
        </button>

        <div class="nav-section">Catálogos</div>
        <button class="nav-link" onclick="loadPage('formularios/f_categorias.php', this, 'Categorías')">
            <i class="bi bi-tags"></i> Categorías
        </button>
        <button class="nav-link" onclick="loadPage('formularios/f_departamentos.php', this, 'Departamentos')">
            <i class="bi bi-building"></i> Departamentos
        </button>

        <?php if ($es_admin): ?>
        <div class="nav-section">Administración</div>
        <button class="nav-link" onclick="loadPage('formularios/f_usuarios.php', this, 'Gestión de Usuarios')">
            <i class="bi bi-people"></i> Gestión de Usuarios
        </button>
        <?php endif; ?>

    </div>
</div>

<!-- ── Topbar ── -->
<div class="topbar">
    <div class="topbar-breadcrumb">
        <span>Panel</span>
        <i class="bi bi-chevron-right"></i>
        <span class="current" id="pageTitle">Inicio</span>
    </div>

    <!-- Botón perfil -->
    <div class="profile-btn" id="profileBtn" onclick="toggleDropdown(event)">
        <div class="p-avatar <?= $es_admin ? 'admin' : 'usuario' ?>"><?= $iniciales ?></div>
        <div>
            <div class="p-name"><?= htmlspecialchars($partes[0]) ?></div>
            <div class="p-role"><?= $es_admin ? 'Administrador' : 'Usuario' ?></div>
        </div>
        <i class="bi bi-chevron-down p-chevron"></i>

        <div class="profile-dropdown" id="profileDropdown">
            <div class="dd-header">
                <div class="dd-av"><?= $iniciales ?></div>
                <div>
                    <div class="dd-fullname"><?= htmlspecialchars($_SESSION['nombre_completo']) ?></div>
                    <div class="dd-role-line">
                        <i class="bi bi-<?= $es_admin ? 'shield-fill' : 'person-fill' ?>"></i>
                        <?= $es_admin ? 'Administrador' : 'Usuario' ?>
                    </div>
                </div>
            </div>

            <div class="dd-section">
                <button class="dd-item" onclick="loadFromDD('formularios/perfil.php', 'Mi Perfil')">
                    <div class="dd-item-icon"><i class="bi bi-person-lines-fill"></i></div>
                    <div>Mi Perfil <span class="dd-sub">Ver y editar información</span></div>
                </button>
                <button class="dd-item" onclick="loadFromDD('formularios/perfil.php?tab=password', 'Cambiar Contraseña')">
                    <div class="dd-item-icon"><i class="bi bi-key-fill"></i></div>
                    <div>Cambiar Contraseña <span class="dd-sub">Actualiza tu contraseña</span></div>
                </button>
                <?php if ($es_admin): ?>
                <button class="dd-item" onclick="loadFromDD('formularios/f_usuarios.php', 'Gestión de Usuarios')">
                    <div class="dd-item-icon"><i class="bi bi-people-fill"></i></div>
                    <div>Gestión de Usuarios <span class="dd-sub">Administrar cuentas</span></div>
                </button>
                <?php endif; ?>
            </div>

            <div class="dd-section">
                <a class="dd-item danger" href="logout.php">
                    <div class="dd-item-icon"><i class="bi bi-box-arrow-left"></i></div>
                    <div>Cerrar Sesión <span class="dd-sub">Salir del sistema</span></div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Área de contenido ── -->
<div class="content-area">
    <div class="frame-loader" id="frameLoader">
        <div class="spinner"></div>
    </div>
    <iframe class="content-frame" id="contentFrame" src="inicio.php"></iframe>
</div>

<script>
var activeLink = null;

function loadPage(url, linkEl, title) {
    // Marcar activo en sidebar
    if (activeLink) activeLink.classList.remove('active');
    if (linkEl) { linkEl.classList.add('active'); activeLink = linkEl; }

    // Breadcrumb
    document.getElementById('pageTitle').textContent = title || url;

    // Loader y carga
    showLoader();
    document.getElementById('contentFrame').src = url;

    // Cerrar dropdown si está abierto
    closeDropdown();
}

function loadFromDD(url, title) {
    // Desmarcar sidebar al navegar desde dropdown
    if (activeLink) activeLink.classList.remove('active');
    activeLink = null;

    document.getElementById('pageTitle').textContent = title || url;
    showLoader();
    document.getElementById('contentFrame').src = url;
    closeDropdown();
}

function showLoader() {
    document.getElementById('frameLoader').classList.add('show');
}
function hideLoader() {
    document.getElementById('frameLoader').classList.remove('show');
}

// Ocultar loader cuando el iframe termina de cargar
document.getElementById('contentFrame').addEventListener('load', function() {
    hideLoader();
});

// Dropdown
function toggleDropdown(e) {
    e.stopPropagation();
    var btn = document.getElementById('profileBtn');
    var dd  = document.getElementById('profileDropdown');
    var open = dd.classList.contains('show');
    if (open) { closeDropdown(); } else { openDropdown(); }
}
function openDropdown() {
    document.getElementById('profileDropdown').classList.add('show');
    document.getElementById('profileBtn').classList.add('open');
}
function closeDropdown() {
    document.getElementById('profileDropdown').classList.remove('show');
    document.getElementById('profileBtn').classList.remove('open');
}
document.addEventListener('click', function(e) {
    var btn = document.getElementById('profileBtn');
    if (btn && !btn.contains(e.target)) closeDropdown();
});
</script>
</body>
</html>
