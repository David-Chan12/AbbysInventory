<?php
require_once 'conexion.php';

// Obtener el JSON enviado
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verificar si se recibió el ID del producto
if (isset($data['producto_id'])) {
    $producto_id = $data['producto_id'];
    
    // Consulta para obtener los insumos asociados al producto
    $query = "SELECT pi.insumo_id, pi.cantidad_requerida, i.nombre, i.unidad_medida, i.cantidad_disponible 
              FROM producto_insumo pi 
              JOIN insumo i ON pi.insumo_id = i.id 
              WHERE pi.producto_id = ?";
    
    // Preparar la consulta
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    
    // Obtener el resultado
    $resultado = $stmt->get_result();
    
    $insumos = array();
    
    if ($resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            $insumos[] = $row;
        }
        
        // Devolver los insumos en formato JSON
        header('Content-Type: application/json');
        echo json_encode($insumos);
    } else {
        // No se encontraron insumos para este producto
        header('Content-Type: application/json');
        echo json_encode([]);
    }
    
    $stmt->close();
} else {
    // No se recibió el ID del producto
    header('Content-Type: application/json');
    echo json_encode(["error" => "No se proporcionó el ID del producto"]);
}
?>