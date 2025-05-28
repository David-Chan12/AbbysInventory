<?php
require_once 'conexion.php';

// Obtener el JSON enviado
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verificar si se recibió el ID de la venta
if (isset($data['venta_id'])) {
    $venta_id = $data['venta_id'];
    
    // Obtener información de la venta
    $query_venta = "SELECT * FROM ventas WHERE id = ?";
    $stmt_venta = $conn->prepare($query_venta);
    $stmt_venta->bind_param("i", $venta_id);
    $stmt_venta->execute();
    $resultado_venta = $stmt_venta->get_result();
    $venta = $resultado_venta->fetch_assoc();
    
    // Obtener detalles de la venta
    $query_detalles = "SELECT dv.*, p.nombre as nombre_producto 
                      FROM detalle_venta dv 
                      JOIN producto p ON dv.producto_id = p.id 
                      WHERE dv.venta_id = ?";
    $stmt_detalles = $conn->prepare($query_detalles);
    $stmt_detalles->bind_param("i", $venta_id);
    $stmt_detalles->execute();
    $resultado_detalles = $stmt_detalles->get_result();
    
    $detalles = array();
    while ($row = $resultado_detalles->fetch_assoc()) {
        $detalles[] = $row;
    }
    
    // Devolver los datos en formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'venta' => $venta,
        'detalles' => $detalles
    ]);
} else {
    // No se recibió el ID de la venta
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No se proporcionó el ID de la venta']);
}
?>