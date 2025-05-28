<?php
require_once 'conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['empleado_id'])) {
    $empleado_id = intval($input['empleado_id']);
    
    $query = "SELECT descripcion, categoria, fecha_inicio, fecha_fin FROM reportes_trabajo WHERE id_empleado = $empleado_id";
    $resultado = $conn->query($query);

    if (!$resultado) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al ejecutar la consulta: ' . $conn->error]);
        exit;
    }

    $tareas = [];
    while ($tarea = $resultado->fetch_assoc()) {
        $tareas[] = $tarea;
    }

    echo json_encode($tareas);
}
?>
