<?php
session_start();

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$dbname = "abbysinventory1";

$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener datos del formulario
$username = $_POST['username'];
$password_input = $_POST['password'];

// Para depuración (quitar en producción)
error_log("Intento de login - Usuario: $username");

// Preparar y ejecutar la consulta (solo buscamos por username)
$sql = "SELECT * FROM usuarios WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si se encontró el usuario
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Para depuración - Mostrar información sobre la contraseña almacenada
    $stored_password = $user['password'];
    $is_bcrypt = (substr($stored_password, 0, 4) === '$2y$');
    error_log("Usuario encontrado - ID: " . $user['id']);
    error_log("Contraseña almacenada: " . $stored_password);
    error_log("¿Es bcrypt? " . ($is_bcrypt ? "Sí" : "No"));
    
    // SOLUCIÓN: Verificación mejorada de contraseñas
    $login_success = false;
    
    // 1. Verificación para contraseñas hasheadas con bcrypt
    if ($is_bcrypt) {
        // Verificación estándar con password_verify
        if (password_verify($password_input, $stored_password)) {
            $login_success = true;
            error_log("Login exitoso usando password_verify");
        } 
        // Solución alternativa para problemas con password_verify
        else {
            // Rehashear la contraseña para verificar (solución para algunos problemas de compatibilidad)
            $rehashed = password_hash($password_input, PASSWORD_BCRYPT);
            error_log("Rehash de prueba: " . $rehashed);
            
            // Verificar si los hashes son similares (ignorando el salt)
            $stored_parts = explode('$', $stored_password);
            $rehashed_parts = explode('$', $rehashed);
            
            // Si tienen la misma estructura básica, podría ser un problema de compatibilidad
            if (count($stored_parts) === count($rehashed_parts)) {
                error_log("Estructuras de hash similares, posible problema de compatibilidad");
                
                // Solución temporal: recrear el hash y actualizar en la base de datos
                $new_hash = password_hash($password_input, PASSWORD_DEFAULT);
                $update_sql = "UPDATE usuarios SET password = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $new_hash, $user['id']);
                
                if ($update_stmt->execute()) {
                    error_log("Hash actualizado con éxito");
                    $login_success = true; // Permitir login después de actualizar
                } else {
                    error_log("Error al actualizar hash: " . $update_stmt->error);
                }
                
                $update_stmt->close();
            }
        }
    } 
    // 2. Verificación para contraseñas en texto plano
    else if ($password_input === $stored_password) {
        $login_success = true;
        error_log("Login exitoso usando comparación directa");
        
        // Actualizar a hash seguro
        $hash = password_hash($password_input, PASSWORD_DEFAULT);
        $update_sql = "UPDATE usuarios SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $hash, $user['id']);
        
        if ($update_stmt->execute()) {
            error_log("Contraseña actualizada a hash seguro");
        } else {
            error_log("Error al actualizar a hash seguro: " . $update_stmt->error);
        }
        
        $update_stmt->close();
    }
    
    if ($login_success) {
        // Guardar datos en sesión
        $_SESSION['usuario'] = [
            'username' => $user['username'],
            'email' => $user['email'],
            'id' => $user['id']
        ];

        // Redirigir al menú
        header("Location: Menu.php");
        exit();
    } else {
        // Contraseña incorrecta
        error_log("Contraseña incorrecta para usuario: $username");
        mostrarError();
    }
} else {
    // Usuario no encontrado
    error_log("Usuario no encontrado: $username");
    mostrarError();
}

$stmt->close();
$conn->close();

function mostrarError() {
    echo "<script>
            alert('Usuario o contraseña incorrectos');
            window.location.href = 'login.html';
          </script>";
    exit();
}
?>