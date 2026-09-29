<?php
require "verificarSesion.php";
require "conexion.php";

// Si la URL trae ?id=5 estamos EDITANDO; si no, estamos CREANDO
$idProducto = (int)($_GET["id"] ?? 0);
$esEdicion  = $idProducto > 0;

$errores = [];

// Valores iniciales del formulario (vacíos al crear)
$producto = [
    "codigo"      => "",
    "nombre"      => "",
    "descripcion" => "",
    "precio"      => "",
    "stockActual" => "0",
    "stockMinimo" => "0",
    "idCategoria" => ""
];

// Categorías activas para el desplegable
$categorias = $conexion->query(
    "SELECT idCategoria, nombre FROM categoria WHERE estado = 1 ORDER BY nombre"
)->fetchAll();

// Si editamos, cargamos el producto desde la base
if ($esEdicion) {
    $consulta = $conexion->prepare("SELECT * FROM producto WHERE idProducto = ? AND estado = 1");
    $consulta->execute([$idProducto]);
    $productoBd = $consulta->fetch();

    if (!$productoBd) {
        header("Location: productos.php");
        exit;
    }
    $producto = $productoBd;
}

// Este bloque se ejecuta al presionar "Guardar"
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // El código solo se puede escribir al crear; al editar se conserva el que ya tenía
    if (!$esEdicion) {
        $producto["codigo"] = trim($_POST["codigo"] ?? "");
    }
    $producto["nombre"]      = trim($_POST["nombre"] ?? "");
    $producto["descripcion"] = trim($_POST["descripcion"] ?? "");
    $producto["precio"]      = trim($_POST["precio"] ?? "");
    $producto["stockActual"] = trim($_POST["stockActual"] ?? "");
    $producto["stockMinimo"] = trim($_POST["stockMinimo"] ?? "");
    $producto["idCategoria"] = trim($_POST["idCategoria"] ?? "");

    // ----- Validaciones en PHP -----
    if ($producto["codigo"] === "") {
        $errores[] = "El código es obligatorio.";
    }
    if ($producto["nombre"] === "") {
        $errores[] = "El nombre es obligatorio.";
    }
    if (!is_numeric($producto["precio"]) || $producto["precio"] < 0) {
        $errores[] = "El precio debe ser un número mayor o igual a 0.";
    }
    if (filter_var($producto["stockActual"], FILTER_VALIDATE_INT) === false || $producto["stockActual"] < 0) {
        $errores[] = "El stock actual debe ser un número entero mayor o igual a 0.";
    }
    if (filter_var($producto["stockMinimo"], FILTER_VALIDATE_INT) === false || $producto["stockMinimo"] < 0) {
        $errores[] = "El stock mínimo debe ser un número entero mayor o igual a 0.";
    }

    // La categoría elegida tiene que existir en la lista
    $categoriaValida = false;
    foreach ($categorias as $categoria) {
        if ($categoria["idCategoria"] == $producto["idCategoria"]) {
            $categoriaValida = true;
        }
    }
    if (!$categoriaValida) {
        $errores[] = "Debes seleccionar una categoría válida.";
    }

    // Regla de negocio: el código no puede repetirse (solo se revisa al crear)
    if (!$esEdicion && $producto["codigo"] !== "") {
        $consulta = $conexion->prepare("SELECT COUNT(*) FROM producto WHERE codigo = ?");
        $consulta->execute([$producto["codigo"]]);
        if ($consulta->fetchColumn() > 0) {
            $errores[] = "Ya existe un producto con ese código.";
        }
    }

    // ----- Si todo está bien, guardamos -----
    if (count($errores) === 0) {
        if ($esEdicion) {
            $consulta = $conexion->prepare(
                "UPDATE producto
                 SET nombre = ?, descripcion = ?, precio = ?, stockActual = ?, stockMinimo = ?, idCategoria = ?
                 WHERE idProducto = ?"
            );
            $consulta->execute([
                $producto["nombre"], $producto["descripcion"], $producto["precio"],
                $producto["stockActual"], $producto["stockMinimo"], $producto["idCategoria"],
                $idProducto
            ]);
            header("Location: productos.php?mensaje=editado");
        } else {
            $consulta = $conexion->prepare(
                "INSERT INTO producto (codigo, nombre, descripcion, precio, stockActual, stockMinimo, idCategoria)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $consulta->execute([
                $producto["codigo"], $producto["nombre"], $producto["descripcion"], $producto["precio"],
                $producto["stockActual"], $producto["stockMinimo"], $producto["idCategoria"]
            ]);
            header("Location: productos.php?mensaje=creado");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - <?php echo $esEdicion ? "Editar" : "Nuevo"; ?> producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require "menu.php"; ?>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-4"><?php echo $esEdicion ? "Editar producto" : "Nuevo producto"; ?></h4>

                        <?php if (count($errores) > 0) { ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errores as $error) { ?>
                                    <div><?php echo $error; ?></div>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <div id="errorJs" class="alert alert-danger d-none"></div>

                        <form method="POST" onsubmit="return validarProducto()">
                            <div class="mb-3">
                                <label class="form-label">Código</label>
                                <input type="text" class="form-control" id="codigo" name="codigo"
                                       value="<?php echo htmlspecialchars($producto["codigo"]); ?>"
                                       <?php echo $esEdicion ? "readonly" : ""; ?>>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                       value="<?php echo htmlspecialchars($producto["nombre"]); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Categoría</label>
                                <select class="form-select" id="idCategoria" name="idCategoria">
                                    <option value="">Seleccione...</option>
                                    <?php foreach ($categorias as $categoria) { ?>
                                        <option value="<?php echo $categoria["idCategoria"]; ?>"
                                            <?php echo $categoria["idCategoria"] == $producto["idCategoria"] ? "selected" : ""; ?>>
                                            <?php echo htmlspecialchars($categoria["nombre"]); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?php echo htmlspecialchars($producto["descripcion"] ?? ""); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Precio</label>
                                    <input type="number" class="form-control" id="precio" name="precio" min="0" step="0.01"
                                           value="<?php echo htmlspecialchars($producto["precio"]); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stock actual</label>
                                    <input type="number" class="form-control" id="stockActual" name="stockActual" min="0" step="1"
                                           value="<?php echo htmlspecialchars($producto["stockActual"]); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Stock mínimo</label>
                                    <input type="number" class="form-control" id="stockMinimo" name="stockMinimo" min="0" step="1"
                                           value="<?php echo htmlspecialchars($producto["stockMinimo"]); ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <a href="productos.php" class="btn btn-outline-secondary">Cancelar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validación en el navegador (PHP vuelve a validar todo al recibirlo)
        function validarProducto() {
            const codigo = document.getElementById("codigo").value.trim();
            const nombre = document.getElementById("nombre").value.trim();
            const categoria = document.getElementById("idCategoria").value;
            const precio = document.getElementById("precio").value;
            const stockActual = document.getElementById("stockActual").value;
            const stockMinimo = document.getElementById("stockMinimo").value;
            const errorJs = document.getElementById("errorJs");

            let mensajes = [];

            if (codigo === "") mensajes.push("El código es obligatorio.");
            if (nombre === "") mensajes.push("El nombre es obligatorio.");
            if (categoria === "") mensajes.push("Debes seleccionar una categoría.");
            if (precio === "" || Number(precio) < 0) mensajes.push("El precio debe ser mayor o igual a 0.");
            if (stockActual === "" || Number(stockActual) < 0) mensajes.push("El stock actual debe ser mayor o igual a 0.");
            if (stockMinimo === "" || Number(stockMinimo) < 0) mensajes.push("El stock mínimo debe ser mayor o igual a 0.");

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
