<?php
require "verificarSesion.php";
require "conexion.php";

// Texto que el usuario escribió en el buscador (vacío si no buscó nada)
$buscar = trim($_GET["buscar"] ?? "");

// Consulta base: une producto con categoria para mostrar el nombre de la categoría
$sql = "SELECT p.idProducto, p.codigo, p.nombre, p.precio, p.stockActual,
               p.stockMinimo, p.estado, c.nombre AS categoria
        FROM producto p
        INNER JOIN categoria c ON p.idCategoria = c.idCategoria";

if ($buscar !== "") {
    $sql .= " WHERE p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?";
}
$sql .= " ORDER BY p.nombre";

$consulta = $conexion->prepare($sql);

if ($buscar !== "") {
    $texto = "%" . $buscar . "%";   // los % significan "que contenga este texto"
    $consulta->execute([$texto, $texto, $texto]);
} else {
    $consulta->execute();
}
$productos = $consulta->fetchAll();

// Regla de alerta: stock actual MENOR que el stock mínimo (solo productos activos)
$cantidadBajoStock = 0;
foreach ($productos as $producto) {
    if ($producto["estado"] == 1 && $producto["stockActual"] < $producto["stockMinimo"]) {
        $cantidadBajoStock++;
    }
}

// Mensajes de confirmación (llegan por la URL desde otras páginas)
$mensajes = [
    "creado"      => "Producto registrado correctamente.",
    "editado"     => "Producto actualizado correctamente.",
    "desactivado" => "Producto desactivado correctamente."
];
$claveMensaje = $_GET["mensaje"] ?? "";
$mensaje = $mensajes[$claveMensaje] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require "menu.php"; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Productos</h4>
            <a href="productoForm.php" class="btn btn-success">+ Nuevo producto</a>
        </div>

        <?php if ($mensaje !== "") { ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php } ?>

        <?php if ($cantidadBajoStock > 0) { ?>
            <div class="alert alert-warning">
                <strong>Atención:</strong> hay <?php echo $cantidadBajoStock; ?> producto(s) con stock bajo el mínimo.
            </div>
        <?php } ?>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-8">
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar por nombre, código o categoría"
                       value="<?php echo htmlspecialchars($buscar); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="productos.php" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered bg-white align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Mínimo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($productos) === 0) { ?>
                        <tr><td colspan="8" class="text-center text-muted">No se encontraron productos.</td></tr>
                    <?php } ?>

                    <?php foreach ($productos as $producto) { ?>
                        <?php
                        $bajoStock = $producto["estado"] == 1 && $producto["stockActual"] < $producto["stockMinimo"];
                        $claseFila = "";
                        if ($producto["estado"] == 0) {
                            $claseFila = "table-secondary";
                        } elseif ($bajoStock) {
                            $claseFila = "table-warning";
                        }
                        ?>
                        <tr class="<?php echo $claseFila; ?>">
                            <td><?php echo htmlspecialchars($producto["codigo"]); ?></td>
                            <td><?php echo htmlspecialchars($producto["nombre"]); ?></td>
                            <td><?php echo htmlspecialchars($producto["categoria"]); ?></td>
                            <td>$<?php echo number_format($producto["precio"], 0, ",", "."); ?></td>
                            <td>
                                <?php echo $producto["stockActual"]; ?>
                                <?php if ($bajoStock) { ?>
                                    <span class="badge bg-danger ms-1">Stock bajo</span>
                                <?php } ?>
                            </td>
                            <td><?php echo $producto["stockMinimo"]; ?></td>
                            <td>
                                <?php if ($producto["estado"] == 1) { ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">Desactivado</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($producto["estado"] == 1) { ?>
                                    <a href="productoForm.php?id=<?php echo $producto["idProducto"]; ?>"
                                       class="btn btn-sm btn-outline-primary">Editar</a>

                                    <form method="POST" action="desactivarProducto.php" class="d-inline"
                                          onsubmit="return confirm('¿Seguro que quieres desactivar este producto?')">
                                        <input type="hidden" name="idProducto" value="<?php echo $producto["idProducto"]; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Desactivar</button>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
