<?php
// Se incluye al inicio de TODAS las páginas que requieren estar logueado.
// Si no hay sesión, manda al login.

session_start();

if (!isset($_SESSION["idUsuario"])) {
    header("Location: login.php");
    exit;
}
