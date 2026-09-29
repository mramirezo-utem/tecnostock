<?php
// Barra de navegación compartida. Se incluye en todas las páginas con: require "menu.php";
?>
<nav class="navbar navbar-expand navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="inicio.php">TecnoStock</a>
    <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="productos.php">Productos</a></li>
        <li class="nav-item"><a class="nav-link" href="movimientos.php">Movimientos</a></li>
    </ul>
    <span class="text-white">
        <?php echo htmlspecialchars($_SESSION["nombreUsuario"]); ?>
        <a href="cerrarSesion.php" class="btn btn-outline-light btn-sm ms-3">Cerrar sesión</a>
    </span>
</nav>
