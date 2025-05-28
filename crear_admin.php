<?php
// Script para crear un usuario administrador
// IMPORTANTE: Eliminar este archivo después de usarlo

session_start();
require_once 'conexion.php';
require_once 'permisos.php';

// Verificar si ya hay un administrador
$admin_exists = false;
$sql = "SELECT * FROM usuarios WHERE es_admin = 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $admin_exists = true;
    $admin_users = $result->fetch_all(MYSQLI_ASSOC);
}

// Procesar el formulario
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    
    // Validaciones básicas
    if (empty($username) || empty($password) || empty($email)) {
        $error = "Todos los campos son obligatorios";
    } else if ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } else if (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres";
    } else {
        // Verificar si el usuario ya existe
        $check_sql = "SELECT * FROM usuarios WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "El nombre de usuario o correo electrónico ya está en uso";
        } else {
            // Crear el administrador
            $admin_id = crearAdministrador($username, $password, $email);
            
            if ($admin_id) {
                $mensaje = "¡Administrador creado con éxito! ID: " . $admin_id;
                
                // Actualizar la lista de administradores
                $admin_exists = true;
                $sql = "SELECT * FROM usuarios WHERE es_admin = 1";
                $result = $conn->query($sql);
                $admin_users = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                $error = "Error al crear el administrador: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Administrador - Abby's</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #ffeeba;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f5c6cb;
        }
        
        form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        button {
            background-color: #4a6fdc;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #3a5fc9;
        }
        
        .admin-list {
            margin-top: 30px;
        }
        
        .admin-list h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: #f5f5f5;
        }
        
        .back-link {
            display: block;
            margin-top: 20px;
            color: #4a6fdc;
            text-decoration: none;
            text-align: center;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crear Usuario Administrador</h1>
        
        <div class="warning">
            <strong>¡Importante!</strong> Este archivo debe ser eliminado después de crear el administrador por razones de seguridad.
        </div>
        
        <?php if (!empty($mensaje)): ?>
            <div class="success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Nombre de Usuario</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">Crear Administrador</button>
        </form>
        
        <?php if ($admin_exists): ?>
            <div class="admin-list">
                <h2>Administradores Existentes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admin_users as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <a href="login.php" class="back-link">Volver a la página de inicio de sesión</a>
    </div>
</body>
</html>
