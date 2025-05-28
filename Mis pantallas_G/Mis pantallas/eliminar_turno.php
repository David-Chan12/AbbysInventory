<?php
// eliminar_turno.php
require_once 'conexion.php';
session_start();

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Gturnos.php');
    exit;
}

$id_turno = $_GET['id'];

// Verificar que el turno existe
$sql_verificar = "SELECT id FROM turnos WHERE id = ?";
$stmt = $conn->prepare($sql_verificar);
$stmt->bind_param("i", $id_turno);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "El turno que intentas eliminar no existe.";
    header('Location: Gturnos.php');
    exit;
}

// Eliminar el turno
$sql_eliminar = "DELETE FROM turnos WHERE id = ?";
$stmt = $conn->prepare($sql_eliminar);
$stmt->bind_param("i", $id_turno);

if ($stmt->execute()) {
    $_SESSION['mensaje'] = "Turno eliminado correctamente.";
} else {
    $_SESSION['error'] = "Error al eliminar el turno: " . $conn->error;
}

// Cerrar conexión
$conn->close();

// Redireccionar a la página de turnos
header('Location: Gturnos.php');
exit;
?>
