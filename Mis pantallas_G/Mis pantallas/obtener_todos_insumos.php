<?php
header('Content-Type: application/json');
include("conexion.php");

$sql = "SELECT * FROM insumo";
$resultado = mysqli_query($conn, $sql);

$datos = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $datos[] = $fila;
}

echo json_encode($datos);
?>
