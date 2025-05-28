<?php
session_start();
require_once 'conexion.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: productos.php?error=4");
    exit();
}

$producto_id = $_GET['id'];

// Verificar si el producto existe
$query_check = "SELECT id FROM producto WHERE id = ?";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param("i", $producto_id);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows == 0) {
    header("Location: productos.php?error=5");
    exit();
}

// Iniciar transacción
$conn->begin_transaction();

try {
    // Obtener los insumos necesarios para el producto
    $query_insumos = "SELECT pi.insumo_id, pi.cantidad_requerida, i.cantidad_disponible 
                      FROM producto_insumo pi 
                      JOIN insumo i ON pi.insumo_id = i.id 
                      WHERE pi.producto_id = ?";
    $stmt_insumos = $conn->prepare($query_insumos);
    $stmt_insumos->bind_param("i", $producto_id);
    $stmt_insumos->execute();
    $resultado_insumos = $stmt_insumos->get_result();
    
    $insumos_insuficientes = [];
    
    // Verificar si hay suficientes insumos
    while ($insumo = $resultado_insumos->fetch_assoc()) {
        if ($insumo['cantidad_disponible'] < $insumo['cantidad_requerida']) {
            // Obtener el nombre del insumo
            $query_nombre = "SELECT nombre FROM insumo WHERE id = ?";
            $stmt_nombre = $conn->prepare($query_nombre);
            $stmt_nombre->bind_param("i", $insumo['insumo_id']);
            $stmt_nombre->execute();
            $resultado_nombre = $stmt_nombre->get_result();
            $nombre_insumo = $resultado_nombre->fetch_assoc()['nombre'];
            
            $insumos_insuficientes[] = [
                'nombre' => $nombre_insumo,
                'disponible' => $insumo['cantidad_disponible'],
                'requerido' => $insumo['cantidad_requerida']
            ];
        }
    }
    
    if (!empty($insumos_insuficientes)) {
        // Hay insumos insuficientes, redirigir con error
        $_SESSION['insumos_insuficientes'] = $insumos_insuficientes;
        header("Location: productos.php?error=6");
        exit();
    }
    
    // Hay suficientes insumos, proceder con la preparación
    
    // Actualizar el inventario de insumos
    $stmt_insumos->data_seek(0); // Reiniciar el puntero del resultado
    
    $sql_update_insumo = "UPDATE insumo SET cantidad_disponible = cantidad_disponible - ? WHERE id = ?";
    $stmt_update_insumo = $conn->prepare($sql_update_insumo);
    
    while ($insumo = $resultado_insumos->fetch_assoc()) {
        $stmt_update_insumo->bind_param("di", $insumo['cantidad_requerida'], $insumo['insumo_id']);
        $stmt_update_insumo->execute();
    }
    
    // Incrementar la cantidad disponible del producto
    $sql_update_producto = "UPDATE producto SET cantidad_disponible = cantidad_disponible + 1 WHERE id = ?";
    $stmt_update_producto = $conn->prepare($sql_update_producto);
    $stmt_update_producto->bind_param("i", $producto_id);
    $stmt_update_producto->execute();
    
    // Registrar la preparación en un historial (opcional)
    $sql_historial = "INSERT INTO historial_preparacion (producto_id, fecha_preparacion, cantidad) VALUES (?, NOW(), 1)";
    $stmt_historial = $conn->prepare($sql_historial);
    $stmt_historial->bind_param("i", $producto_id);
    $stmt_historial->execute();
    
    // Confirmar la transacción
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    header("Location: productos.php?success=4");
    exit();
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    header("Location: productos.php?error=7");
    exit();
}
?>