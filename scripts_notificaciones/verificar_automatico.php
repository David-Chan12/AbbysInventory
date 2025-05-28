<?php
// Este script verifica automáticamente la base de datos y genera notificaciones
// Se puede ejecutar mediante un cron job o incluirlo en otros archivos PHP

// Incluir la conexión a la base de datos si se ejecuta de forma independiente
if (!isset($conn)) {
    require_once __DIR__ . '/../conexion.php';
}

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

// 1. Verificar insumos próximos a caducar (7 días)
$sql_caducidad = "SELECT id, nombre, fecha_caducidad FROM insumo WHERE fecha_caducidad BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)";
$result_caducidad = $conn->query($sql_caducidad);

if ($result_caducidad && $result_caducidad->num_rows > 0) {
    while ($row = $result_caducidad->fetch_assoc()) {
        $mensaje = "El insumo '{$row['nombre']}' caducará el " . date('d/m/Y', strtotime($row['fecha_caducidad']));
        $descripcion = "Insumo ID: {$row['id']} - Nombre: {$row['nombre']} - Fecha de caducidad: " . date('d/m/Y', strtotime($row['fecha_caducidad']));
        crearNotificacion($conn, 'alerta', $mensaje, $descripcion);
    }
}

// 2. Verificar insumos con bajo stock (menos de 10 unidades)
$sql_stock = "SELECT id, nombre, cantidad_disponible FROM insumo WHERE cantidad_disponible <= 10";
$result_stock = $conn->query($sql_stock);

if ($result_stock && $result_stock->num_rows > 0) {
    while ($row = $result_stock->fetch_assoc()) {
        $mensaje = "Nivel bajo de stock: '{$row['nombre']}' - Quedan {$row['cantidad_disponible']} unidades";
        $descripcion = "Insumo ID: {$row['id']} - Nombre: {$row['nombre']} - Stock actual: {$row['cantidad_disponible']}";
        crearNotificacion($conn, 'advertencia', $mensaje, $descripcion);
    }
}

$sql_stock = "SELECT id, nombre, cantidad_disponible FROM producto WHERE cantidad_disponible <= 10";
$result_stock = $conn->query($sql_stock);

if ($result_stock && $result_stock->num_rows > 0) {
    while ($row = $result_stock->fetch_assoc()) {
        $mensaje = "Nivel bajo de stock: '{$row['nombre']}' - Quedan {$row['cantidad_disponible']} unidades";
        $descripcion = "producto ID: {$row['id']} - Nombre: {$row['nombre']} - Stock actual: {$row['cantidad_disponible']}";
        crearNotificacion($conn, 'advertencia', $mensaje, $descripcion);
    }
}

// 3. Verificar pedidos nuevos (estado = 1 RECIBIDO)
$sql_pedidos_nuevos = "SELECT p.id, p.cliente, p.productos_solicitados, p.fecha 
                       FROM pedido p 
                       WHERE p.estado_pedido = 1 
                       AND p.fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$result_pedidos_nuevos = $conn->query($sql_pedidos_nuevos);

if ($result_pedidos_nuevos && $result_pedidos_nuevos->num_rows > 0) {
    while ($row = $result_pedidos_nuevos->fetch_assoc()) {
        $mensaje = "Nuevo pedido recibido de '{$row['cliente']}' - Pedido #{$row['id']}";
        $descripcion = "Pedido ID: {$row['id']} - Cliente: {$row['cliente']} - Productos: {$row['productos_solicitados']} - Fecha: " . date('d/m/Y H:i', strtotime($row['fecha']));
        crearNotificacion($conn, 'informacion', $mensaje, $descripcion);
    }
}

// 4. Verificar pedidos completados (estado = 3 LISTO)
$sql_pedidos_listos = "SELECT p.id, p.cliente, p.productos_solicitados 
                       FROM pedido p 
                       WHERE p.estado_pedido = 3 
                       AND p.fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$result_pedidos_listos = $conn->query($sql_pedidos_listos);

if ($result_pedidos_listos && $result_pedidos_listos->num_rows > 0) {
    while ($row = $result_pedidos_listos->fetch_assoc()) {
        $mensaje = "Pedido #{$row['id']} de '{$row['cliente']}' está listo para entrega";
        $descripcion = "Pedido ID: {$row['id']} - Cliente: {$row['cliente']} - Productos: {$row['productos_solicitados']}";
        crearNotificacion($conn, 'exito', $mensaje, $descripcion);
    }
}

// 5. Verificar pedidos entregados (estado = 4 ENTREGADO)
$sql_pedidos_entregados = "SELECT p.id, p.cliente, p.productos_solicitados 
                          FROM pedido p 
                          WHERE p.estado_pedido = 4 
                          AND p.fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$result_pedidos_entregados = $conn->query($sql_pedidos_entregados);

if ($result_pedidos_entregados && $result_pedidos_entregados->num_rows > 0) {
    while ($row = $result_pedidos_entregados->fetch_assoc()) {
        $mensaje = "Pedido #{$row['id']} de '{$row['cliente']}' ha sido entregado";
        $descripcion = "Pedido ID: {$row['id']} - Cliente: {$row['cliente']} - Productos: {$row['productos_solicitados']}";
        crearNotificacion($conn, 'exito', $mensaje, $descripcion);
    }
}

// Si se ejecuta directamente, mostrar un mensaje
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    echo "Verificación de notificaciones completada. <a href='../Alertas y notificaciones.php'>Volver a notificaciones</a>";
}
?>