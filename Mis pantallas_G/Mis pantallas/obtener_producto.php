<?php
require_once 'conexion.php';

// Obtener el JSON enviado
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verificar si se recibió el ID del producto
if (isset($data['producto_id'])) {
    $producto_id = $data['producto_id'];
    
    // Consulta para obtener el producto específico con su estado
    $query = "SELECT p.*, e.descripcion as estado_desc FROM producto p 
              LEFT JOIN estadopro e ON p.estado = e.id 
              WHERE p.id = ?";
    
    // Preparar la consulta
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    
    // Obtener el resultado
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $producto = $resultado->fetch_assoc();
        
        // Devolver el producto en formato JSON
        header('Content-Type: application/json');
        echo json_encode($producto);
    } else {
        // No se encontró el producto
        header('Content-Type: application/json');
        echo json_encode(null);
    }
    
    $stmt->close();
} else {
    // No se recibió el ID del producto
    header('Content-Type: application/json');
    echo json_encode(null);
}
?>