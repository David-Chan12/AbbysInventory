<?php
// Este script verifica específicamente los insumos próximos a caducar
// Se puede ejecutar mediante un cron job diario

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../conexion.php';

// Función para crear una notificación
function crearNotificacion($conn, $tipo, $mensaje, $descripcion = "") {
    $fecha = date('Y-m-d H:i:s');
    $estado = 'no leida';
    
    // Verificar si ya existe una notificación similar en las últimas 24 horas
    $sql_check = "SELECT id FROM notificacion WHERE mensaje = ? AND fecha > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $mensaje);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows == 0) {
        // No existe una notificación similar, crear una nueva
        $sql = "INSERT INTO notificacion (fecha, tipo, mensaje, estado, descripcion) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $fecha, $tipo, $mensaje, $estado, $descripcion);
        $stmt->execute();
        return true;
    }
    
    return false;
}

// Verificar insumos próximos a caducar (7 días)
$sql_caducidad = "SELECT id, nombre, fecha_caducidad FROM insumo WHERE fecha_caducidad BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)";
$result_caducidad = $conn->query($sql_caducidad);

if ($result_caducidad && $result_caducidad->num_rows > 0) {
    while ($row = $result_caducidad->fetch_assoc()) {
        $mensaje = "El insumo '{$row['nombre']}' caducará el " . date('d/m/Y', strtotime($row['fecha_caducidad']));
        $descripcion = "Insumo ID: {$row['id']} - Nombre: {$row['nombre']} - Fecha de caducidad: " . date('d/m/Y', strtotime($row['fecha_caducidad']));
        crearNotificacion($conn, 'alerta', $mensaje, $descripcion);
    }
}

echo "Verificación de caducidad completada: " . date('Y-m-d H:i:s');
?>