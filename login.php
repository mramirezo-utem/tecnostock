<?php
session_start();
require "conexion.php";

// Si ya inició sesión, lo mandamos directo al inicio
if (isset($_SESSION["idUsuario"])) {
    header("Location: inicio.php");
    exit;
}

$mensajeError = "";

// Este bloque se ejecuta solo cuando se envía el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? "");
    $clave  = $_POST["clave"] ?? "";

    // Validación en PHP (la de JavaScript se puede saltar, esta no)
    if ($correo === "" || $clave === "") {
        $mensajeError = "Debes ingresar correo y clave.";
    } else {
        // Consulta preparada: el ? se reemplaza de forma segura por el correo
        $consulta = $conexion->prepare(
            "SELECT idUsuario, nombre, clave FROM usuario WHERE correo = ? AND estado = 1"
        );
        $consulta->execute([$correo]);
        $usuario = $consulta->fetch();

        // password_verify compara la clave escrita con la clave encriptada de la base
        if ($usuario && password_verify($clave, $usuario["clave"])) {
            session_regenerate_id(true);
            $_SESSION["idUsuario"]     = $usuario["idUsuario"];
            $_SESSION["nombreUsuario"] = $usuario["nombre"];
            header("Location: inicio.php");
            exit;
        } else {
            $mensajeError = "Correo o clave incorrectos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TecnoStock - Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">TecnoStock</h3>

                        <?php if ($mensajeError !== "") { ?>
                            <div class="alert alert-danger"><?php echo $mensajeError; ?></div>
                        <?php } ?>

                        <form method="POST" onsubmit="return validarLogin()">
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo</label>
                                <input type="email" class="form-control" id="correo" name="correo">
                            </div>
                            <div class="mb-3">
                                <label for="clave" class="form-label">Clave</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="clave" name="clave">
                                    <button type="button" class="btn btn-outline-secondary" onclick="mostrarOcultarClave()">
                                        <i id="iconoOjo" class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="errorJs" class="text-danger mb-3"></div>
                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Cambia el campo entre "password" (puntitos) y "text" (se ve la clave)
        function mostrarOcultarClave() {
            const campoClave = document.getElementById("clave");
            const iconoOjo = document.getElementById("iconoOjo");

            if (campoClave.type === "password") {
                campoClave.type = "text";
                iconoOjo.className = "bi bi-eye-slash";
            } else {
                campoClave.type = "password";
                iconoOjo.className = "bi bi-eye";
            }
        }

        // Validación en el navegador: avisa al tiro, sin ir al servidor
        function validarLogin() {
            const correo = document.getElementById("correo").value.trim();
            const clave = document.getElementById("clave").value;
            const errorJs = document.getElementById("errorJs");

            if (correo === "" || clave === "") {
                errorJs.textContent = "Debes ingresar correo y clave.";
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
