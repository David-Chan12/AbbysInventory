<?php
session_start();
require_once 'conexion.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: productos.php");
    exit();
}

$id = $_GET['id'];

// Verificar si el producto existe
$query_check = "SELECT id FROM producto WHERE id = ?";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$resultado_check = $stmt_check->get_result();

if ($resultado_check->num_rows == 0) {
    header("Location: productos.php");
    exit();
}

// Eliminar el producto
$query_delete = "DELETE FROM producto WHERE id = ?";
$stmt_delete = $conn->prepare($query_delete);
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    // Redirigir a la página de productos con mensaje de éxito
    header("Location: productos.php?success=3");
} else {
    // Redirigir con mensaje de error
    header("Location: productos.php?error=1");
}

$stmt_check->close();
$stmt_delete->close();
?>