<?php
header('Content-Type: application/json');
include("conexion.php");

$input = json_decode(file_get_contents("php://input"), true);
$id = intval($input['insumo_id']);

$sql = "SELECT * FROM insumo WHERE id = $id LIMIT 1";
$resultado = mysqli_query($conn, $sql);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    echo json_encode(mysqli_fetch_assoc($resultado));
} else {
    echo json_encode(null);
}

?>
