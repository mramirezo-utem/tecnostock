<?php
require "verificarSesion.php";
require "conexion.php";

// Cuántos productos activos tienen el stock bajo el mínimo
$consulta = $conexion->query(
    "SELECT COUNT(*) FROM producto WHERE estado = 1 AND stockActual < stockMinimo"
);
$cantidadBajoStock = $consulta->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require "menu.php"; ?>

    <div class="container mt-4">
        <h4>Bienvenido/a, <?php echo htmlspecialchars($_SESSION["nombreUsuario"]); ?></h4>

        <?php if ($cantidadBajoStock > 0) { ?>
            <div class="alert alert-warning mt-3">
                <strong>Atención:</strong> hay <?php echo $cantidadBajoStock; ?> producto(s) con stock bajo el mínimo.
                <a href="productos.php" class="alert-link">Ver productos</a>
            </div>
        <?php } ?>

        <p class="text-muted">¿Qué quieres hacer?</p>
        <a href="productos.php" class="btn btn-primary me-2">Ver productos</a>
        <a href="movimientos.php" class="btn btn-secondary">Registrar movimientos</a>
    </div>
</body>
</html>
