<?php
require_once 'config/db.php';

$correo = 'admin@tecnostock.cl';
$clave_plana = 'admin123';
$hash = password_hash($clave_plana, PASSWORD_DEFAULT);

// Actualiza el primer usuario y asegura que estado sea 1 (activo)
$stmt = $pdo->prepare("UPDATE usuario SET correo = ?, clave = ?, estado = 1 WHERE id_usuario = 1");
$stmt->execute([$correo, $hash]);

if ($stmt->rowCount() > 0) {
    echo "Listo. Correo: <b>$correo</b> | Clave: <b>$clave_plana</b>";
} else {
    // Si no existía id_usuario = 1, lo crea
    $stmtIns = $pdo->prepare("INSERT INTO usuario (nombre, correo, clave, estado) VALUES ('Administrador', ?, ?, 1)");
    $stmtIns->execute([$correo, $hash]);
    echo "Usuario creado. Correo: <b>$correo</b> | Clave: <b>$clave_plana</b>";
}