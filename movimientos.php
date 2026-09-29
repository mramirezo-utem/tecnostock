<?php
require "verificarSesion.php";
require "conexion.php";

$errores     = [];
$idProducto  = "";
$tipo        = "";
$cantidad    = "";
$observacion = "";

// Este bloque se ejecuta al presionar "Registrar movimiento"
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idProducto  = trim($_POST["idProducto"] ?? "");
    $tipo        = trim($_POST["tipo"] ?? "");
    $cantidad    = trim($_POST["cantidad"] ?? "");
    $observacion = trim($_POST["observacion"] ?? "");

    // ----- Validaciones en PHP -----
    if ($tipo !== "entrada" && $tipo !== "salida") {
        $errores[] = "Debes elegir el tipo de movimiento.";
    }

    if (filter_var($cantidad, FILTER_VALIDATE_INT) === false || $cantidad <= 0) {
        $errores[] = "La cantidad debe ser un número entero mayor que 0.";
    }

    // El producto tiene que existir y estar activo
    $consulta = $conexion->prepare("SELECT stockActual FROM producto WHERE idProducto = ? AND estado = 1");
    $consulta->execute([$idProducto]);
    $productoElegido = $consulta->fetch();

    if (!$productoElegido) {
        $errores[] = "Debes elegir un producto activo.";
    }

    // Regla de negocio: una salida no puede dejar el stock bajo cero
    if (count($errores) === 0 && $tipo === "salida" && $cantidad > $productoElegido["stockActual"]) {
        $errores[] = "Stock insuficiente: solo hay " . $productoElegido["stockActual"] . " unidades disponibles.";
    }

    // ----- Si todo está bien, guardamos -----
    if (count($errores) === 0) {
        $cantidad = (int)$cantidad;

        try {
            // Transacción: o se hacen las DOS cosas (actualizar stock + guardar movimiento)
            // o no se hace ninguna. Así el stock nunca queda descuadrado.
            $conexion->beginTransaction();

            if ($tipo === "entrada") {
                $actualizar = $conexion->prepare(
                    "UPDATE producto SET stockActual = stockActual + ? WHERE idProducto = ? AND estado = 1"
                );
                $actualizar->execute([$cantidad, $idProducto]);
            } else {
                // La condición "stockActual >= ?" es un seguro extra: no deja bajar de cero
                $actualizar = $conexion->prepare(
                    "UPDATE producto SET stockActual = stockActual - ?
                     WHERE idProducto = ? AND estado = 1 AND stockActual >= ?"
                );
                $actualizar->execute([$cantidad, $idProducto, $cantidad]);
            }

            // Si no se modificó ninguna fila, algo falló: cancelamos todo
            if ($actualizar->rowCount() === 0) {
                throw new Exception("No se pudo actualizar el stock.");
            }

            // Guardamos el movimiento con el usuario que tiene la sesión iniciada
            $registrar = $conexion->prepare(
                "INSERT INTO movimiento (tipo, cantidad, observacion, idProducto, idUsuario)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $registrar->execute([$tipo, $cantidad, $observacion, $idProducto, $_SESSION["idUsuario"]]);

            $conexion->commit();   // confirmamos los cambios

            header("Location: movimientos.php?mensaje=registrado");
            exit;

        } catch (Exception $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();   // deshacemos todo
            }
            $errores[] = "No se pudo registrar el movimiento. Intenta de nuevo.";
        }
    }
}

// Productos activos para el desplegable (mostramos su stock para orientar al usuario)
$productos = $conexion->query(
    "SELECT idProducto, codigo, nombre, stockActual FROM producto WHERE estado = 1 ORDER BY nombre"
)->fetchAll();

// Últimos 20 movimientos, con el nombre del producto y del usuario
$historial = $conexion->query(
    "SELECT m.fechaHora, m.tipo, m.cantidad, m.observacion,
            p.codigo, p.nombre AS producto, u.nombre AS usuario
     FROM movimiento m
     INNER JOIN producto p ON m.idProducto = p.idProducto
     INNER JOIN usuario u ON m.idUsuario = u.idUsuario
     ORDER BY m.fechaHora DESC, m.idMovimiento DESC
     LIMIT 20"
)->fetchAll();

$mensaje = (($_GET["mensaje"] ?? "") === "registrado") ? "Movimiento registrado correctamente." : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - Movimientos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require "menu.php"; ?>

    <div class="container mt-4 mb-5">
        <h4 class="mb-3">Movimientos de inventario</h4>

        <?php if ($mensaje !== "") { ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php } ?>

        <?php if (count($errores) > 0) { ?>
            <div class="alert alert-danger">
                <?php foreach ($errores as $error) { ?>
                    <div><?php echo $error; ?></div>
                <?php } ?>
            </div>
        <?php } ?>

        <div id="errorJs" class="alert alert-danger d-none"></div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="POST" class="row g-3" onsubmit="return validarMovimiento()">
                    <div class="col-md-5">
                        <label class="form-label">Producto</label>
                        <select class="form-select" id="idProducto" name="idProducto">
                            <option value="">Seleccione...</option>
                            <?php foreach ($productos as $producto) { ?>
                                <option value="<?php echo $producto["idProducto"]; ?>"
                                    <?php echo $producto["idProducto"] == $idProducto ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($producto["codigo"] . " - " . $producto["nombre"]); ?>
                                    (stock: <?php echo $producto["stockActual"]; ?>)
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="">Seleccione...</option>
                            <option value="entrada" <?php echo $tipo === "entrada" ? "selected" : ""; ?>>Entrada</option>
                            <option value="salida" <?php echo $tipo === "salida" ? "selected" : ""; ?>>Salida</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" step="1"
                               value="<?php echo htmlspecialchars($cantidad); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observación (opcional)</label>
                        <input type="text" class="form-control" id="observacion" name="observacion" maxlength="255"
                               value="<?php echo htmlspecialchars($observacion); ?>">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Registrar movimiento</button>
                    </div>
                </form>
            </div>
        </div>

        <h5>Últimos movimientos</h5>
        <div class="table-responsive">
            <table class="table table-bordered bg-white align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Observación</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($historial) === 0) { ?>
                        <tr><td colspan="6" class="text-center text-muted">Aún no hay movimientos.</td></tr>
                    <?php } ?>

                    <?php foreach ($historial as $movimiento) { ?>
                        <tr>
                            <td><?php echo date("d-m-Y H:i", strtotime($movimiento["fechaHora"])); ?></td>
                            <td><?php echo htmlspecialchars($movimiento["codigo"] . " - " . $movimiento["producto"]); ?></td>
                            <td>
                                <?php if ($movimiento["tipo"] === "entrada") { ?>
                                    <span class="badge bg-success">Entrada</span>
                                <?php } else { ?>
                                    <span class="badge bg-danger">Salida</span>
                                <?php } ?>
                            </td>
                            <td><?php echo $movimiento["cantidad"]; ?></td>
                            <td><?php echo htmlspecialchars($movimiento["observacion"] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($movimiento["usuario"]); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Validación en el navegador (PHP vuelve a validar todo al recibirlo)
        function validarMovimiento() {
            const producto = document.getElementById("idProducto").value;
            const tipo = document.getElementById("tipo").value;
            const cantidad = document.getElementById("cantidad").value;
            const errorJs = document.getElementById("errorJs");

            let mensajes = [];

            if (producto === "") mensajes.push("Debes seleccionar un producto.");
            if (tipo === "") mensajes.push("Debes seleccionar el tipo de movimiento.");
            if (cantidad === "" || Number(cantidad) <= 0 || !Number.isInteger(Number(cantidad))) {
                mensajes.push("La cantidad debe ser un número entero mayor que 0.");
            }

            if (mensajes.length > 0) {
                errorJs.innerHTML = mensajes.join("<br>");
                errorJs.classList.remove("d-none");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
