<?php

$host = 'localhost';
$db   = 'inventarioconcejomunicipal';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    
    // Configura PDO para que lance excepciones si hay errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Eliminamos el header() de aquí. Las redirecciones van en guardar.php
    
} catch (PDOException $e) {
    echo "Error crítico de conexión: " . $e->getMessage();
    exit;
}
?>