<?php
// conexion.php
$servername = "localhost";
$username = "root"; // Reemplaza con tu usuario real de MySQL
$password = ""; // Reemplaza con tu contraseña real de MySQL
$dbname = "abbysinventory1";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>