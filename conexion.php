<?php
// Este archivo crea la conexión a MySQL.
// Todas las demás páginas lo van a incluir con: require "conexion.php";

$servidor  = "localhost";
$baseDatos = "tecnostock";
$usuarioBd = "root";   // usuario por defecto de XAMPP
$claveBd   = "";       // XAMPP viene sin clave por defecto

try {
    $conexion = new PDO(
        "mysql:host=$servidor;dbname=$baseDatos;charset=utf8mb4",
        $usuarioBd,
        $claveBd
    );

    // Si algo falla, PDO lanza un error que podemos capturar
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Los resultados llegan como arreglo con el nombre de la columna: $fila["nombre"]
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    die("No se pudo conectar a la base de datos.");
}
