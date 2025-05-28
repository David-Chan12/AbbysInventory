<?php
require_once 'conexion.php';

// Consulta para obtener todos los productos con su estado
$query = "SELECT p.*, e.descripcion as estado_desc FROM producto p 
          LEFT JOIN estadopro e ON p.estado = e.id";
$resultado = $conn->query($query);

$productos = array();

if ($resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Devolver los datos en formato JSON
header('Content-Type: application/json');
echo json_encode($productos);
?>