<?php
// Archivo de funciones para el sistema de permisos

/**
 * Verifica si un usuario tiene un permiso específico
 * @param int $usuario_id ID del usuario
 * @param string $permiso Nombre del permiso a verificar
 * @return bool True si tiene permiso, False si no
 */
function tienePermiso($usuario_id, $permiso) {
    global $conn;
    
    // Primero verificamos si el usuario es administrador
    $query = "SELECT es_admin FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Si es administrador, tiene todos los permisos
        if (isset($user['es_admin']) && $user['es_admin'] == 1) {
            return true;
        }
    }
    
    // Si no es administrador, verificamos el permiso específico
    // Primero obtenemos el ID del empleado asociado al usuario
    $query = "SELECT e.id FROM empleado e 
              INNER JOIN usuarios u ON e.correo = u.email 
              WHERE u.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false; // No se encontró empleado asociado
    }
    
    $empleado = $result->fetch_assoc();
    $empleado_id = $empleado['id'];
    
    // Ahora verificamos si el empleado tiene el permiso
    $query = "SELECT p.id FROM permisos p 
              INNER JOIN empleado_permisos ep ON p.id = ep.permiso_id 
              WHERE ep.empleado_id = ? AND p.nombre = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $empleado_id, $permiso);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Guarda los permisos asignados a un empleado
 * @param int $empleado_id ID del empleado
 * @param array $permisos Array con los nombres de los permisos
 * @return bool True si se guardaron correctamente, False si hubo error
 */
function guardarPermisos($empleado_id, $permisos) {
    global $conn;
    
    // Primero eliminamos todos los permisos actuales del empleado
    $query = "DELETE FROM empleado_permisos WHERE empleado_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $empleado_id);
    $stmt->execute();
    
    // Si no hay permisos para asignar, terminamos
    if (empty($permisos)) {
        return true;
    }
    
    // Preparamos la consulta para insertar los nuevos permisos
    $query = "INSERT INTO empleado_permisos (empleado_id, permiso_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    
    // Para cada permiso, obtenemos su ID y lo insertamos
    foreach ($permisos as $permiso) {
        // Obtenemos el ID del permiso
        $permiso_query = "SELECT id FROM permisos WHERE nombre = ?";
        $permiso_stmt = $conn->prepare($permiso_query);
        $permiso_stmt->bind_param("s", $permiso);
        $permiso_stmt->execute();
        $result = $permiso_stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Si el permiso no existe, lo creamos
            $insert_permiso = "INSERT INTO permisos (nombre, modulo) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_permiso);
            $modulo = $permiso; // Usamos el mismo nombre como módulo por defecto
            $insert_stmt->bind_param("ss", $permiso, $modulo);
            $insert_stmt->execute();
            $permiso_id = $conn->insert_id;
        } else {
            $permiso_row = $result->fetch_assoc();
            $permiso_id = $permiso_row['id'];
        }
        
        // Insertamos la relación empleado-permiso
        $stmt->bind_param("ii", $empleado_id, $permiso_id);
        $stmt->execute();
    }
    
    return true;
}

/**
 * Obtiene los permisos asignados a un empleado
 * @param int $empleado_id ID del empleado
 * @return array Array con los nombres de los permisos
 */
function obtenerPermisos($empleado_id) {
    global $conn;
    
    $permisos = [];
    
    $query = "SELECT p.nombre FROM permisos p 
              INNER JOIN empleado_permisos ep ON p.id = ep.permiso_id 
              WHERE ep.empleado_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $empleado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $permisos[] = $row['nombre'];
    }
    
    return $permisos;
}

/**
 * Verifica si el usuario actual tiene permiso para acceder a una sección
 * Si no tiene permiso, redirige a una página de acceso denegado
 * @param string $permiso_requerido Nombre del permiso requerido
 */
function verificarAcceso($permiso_requerido) {
    // Si no hay sesión iniciada, redirigir al login
    if (!isset($_SESSION['usuario'])) {
        header("Location: login.php");
        exit();
    }
    
    // Si el usuario es administrador, permitir acceso a todo
    $query = "SELECT es_admin FROM usuarios WHERE id = ?";
    $stmt = $GLOBALS['conn']->prepare($query);
    $stmt->bind_param("i", $_SESSION['usuario']['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (isset($user['es_admin']) && $user['es_admin'] == 1) {
            return true;
        }
    }
    
    // Verificar si tiene el permiso específico
    if (!tienePermiso($_SESSION['usuario']['id'], $permiso_requerido)) {
        header("Location: acceso_denegado.php");
        exit();
    }
    
    return true;
}

/**
 * Crea un usuario administrador
 * @param string $username Nombre de usuario
 * @param string $password Contraseña (se hasheará automáticamente)
 * @param string $email Correo electrónico
 * @return int|bool ID del usuario creado o False si hubo error
 */
function crearAdministrador($username, $password, $email) {
    global $conn;
    
    // Hashear la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar el usuario con privilegios de administrador
    $query = "INSERT INTO usuarios (username, password, email, es_admin) VALUES (?, ?, ?, 1)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $hashed_password, $email);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    } else {
        return false;
    }
}
?>
