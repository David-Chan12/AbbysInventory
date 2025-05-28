<?php
require_once 'conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['empleado_id']) && isset($input['trabajos'])) {
    $empleado_id = intval($input['empleado_id']);
    $trabajos = $input['trabajos'];

    // Eliminar trabajos actuales del empleado
    $delete = "DELETE FROM reportes_trabajo WHERE id_empleado = $empleado_id";
    $conn->query($delete);

    // Insertar nuevos trabajos
    foreach ($trabajos as $trabajo) {
        $descripcion = $conn->real_escape_string($trabajo['descripcion']);
        $categoria = $conn->real_escape_string($trabajo['categoria']);
        $fecha_inicio = $conn->real_escape_string($trabajo['fecha_inicio']);
        $fecha_fin = $conn->real_escape_string($trabajo['fecha_fin']);

        $insert = "INSERT INTO reportes_trabajo (id_empleado, descripcion, categoria, fecha_inicio, fecha_fin) 
                   VALUES ($empleado_id, '$descripcion', '$categoria', '$fecha_inicio', '$fecha_fin')";
        $conn->query($insert);
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
