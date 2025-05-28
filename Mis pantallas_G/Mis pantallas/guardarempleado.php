<?php
session_start();
require_once 'conexion.php';

// Verificar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger y sanitizar los datos del formulario
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
    $correo = mysqli_real_escape_string($conn, $_POST['correo']);
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    
    // Establecer horario por defecto (puedes cambiarlo según tus necesidades)
    
    // Preparar la consulta SQL
    $sql = "INSERT INTO empleado (nombre, telefono, correo, descripcion) 
            VALUES ('$nombre', '$telefono', '$correo', '$descripcion')";
    
    // Ejecutar la consulta
    if (mysqli_query($conn, $sql)) {
        // Redirigir a la página de empleados con mensaje de éxito
        $_SESSION['mensaje'] = "Empleado agregado correctamente";
        header("Location: empleado.php");
        exit();
    } else {
        // Mostrar error si la consulta falla
        die("Error al guardar el empleado: " . mysqli_error($conn));
    }
    
    // Cerrar conexión
    mysqli_close($conn);
} else {
    // Si no es POST, redirigir al formulario
    header("Location: agregarempleado.php");
    exit();
}
?>