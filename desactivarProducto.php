<?php
require "verificarSesion.php";
require "conexion.php";

// Nunca borramos el producto: solo cambiamos su estado a 0 (desactivado)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idProducto = (int)($_POST["idProducto"] ?? 0);

    if ($idProducto > 0) {
        $consulta = $conexion->prepare("UPDATE producto SET estado = 0 WHERE idProducto = ?");
        $consulta->execute([$idProducto]);
    }
}

header("Location: productos.php?mensaje=desactivado");
exit;
