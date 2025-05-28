<?php
header('Content-Type: application/json');
include("conexion.php");

// Obtener el JSON enviado
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verificar si se recibió el ID de la venta
if (isset($data['venta_id'])) {
    $venta_id = $data['venta_id'];
    
    // Consulta para obtener la venta específica
    $query = "SELECT * FROM ventas WHERE id = ?";
    
    // Preparar la consulta
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    
    // Obtener el resultado
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $venta = $resultado->fetch_assoc();
        
        // Devolver la venta en formato JSON
        echo json_encode($venta);
    } else {
        // No se encontró la venta
        echo json_encode(null);
    }
    
    $stmt->close();
} else {
    // No se recibió el ID de la venta
    echo json_encode(null);
}
?>