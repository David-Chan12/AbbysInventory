<?php
session_start();
require_once 'conexion.php';

// Verificar si se ha proporcionado un ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    
    // Si se ha confirmado la eliminación
    if (isset($_GET['confirmar']) && $_GET['confirmar'] == 'si') {
        // Comenzar una transacción para asegurar que todas las operaciones se completen o ninguna
        $conn->begin_transaction();
        
        try {
            // Primero eliminamos los turnos asociados
            $stmt_turnos = $conn->prepare("DELETE FROM turnos WHERE empleado_id = ?");
            $stmt_turnos->bind_param("i", $id);
            $stmt_turnos->execute();
            $turnos_eliminados = $stmt_turnos->affected_rows;
            $stmt_turnos->close();
            
            // Luego eliminamos el empleado
            $stmt_empleado = $conn->prepare("DELETE FROM empleado WHERE id = ?");
            $stmt_empleado->bind_param("i", $id);
            $stmt_empleado->execute();
            $empleado_eliminado = $stmt_empleado->affected_rows;
            $stmt_empleado->close();
            
            // Confirmar la transacción
            $conn->commit();
            
            // Redirigir con mensaje de éxito
            header("Location: agregarempleado.php?mensaje=eliminado&turnos=" . $turnos_eliminados);
            exit;
        } catch (Exception $e) {
            // Si hay un error, revertir la transacción
            $conn->rollback();
            
            // Redirigir con mensaje de error
            header("Location: agregarempleado.php?mensaje=error&error=" . urlencode($e->getMessage()));
            exit;
        }
    } else {
        // Obtener información del empleado para mostrar en la confirmación
        $stmt = $conn->prepare("SELECT * FROM empleado WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // El empleado no existe
            header("Location: agregarempleado.php?mensaje=error&error=" . urlencode("El empleado no existe"));
            exit;
        }
        
        $empleado = $result->fetch_assoc();
        $stmt->close();
        
        // Contar turnos asociados
        $stmt_turnos = $conn->prepare("SELECT COUNT(*) as total FROM turnos WHERE empleado_id = ?");
        $stmt_turnos->bind_param("i", $id);
        $stmt_turnos->execute();
        $result_turnos = $stmt_turnos->get_result();
        $turnos = $result_turnos->fetch_assoc()['total'];
        $stmt_turnos->close();
        
        // Mostrar página de confirmación
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
          <title>Confirmar Eliminación - Abby's</title>
          <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
          <style>
            * {
              margin: 0;
              padding: 0;
              box-sizing: border-box;
              font-family: 'Montserrat', 'Segoe UI', sans-serif;
            }

            body {
              background-color: #fff9f7;
              color: #5a4a42;
              min-height: 100vh;
              background-image: url('Imagenes/sweet-composition-with-breakfast-blank-space-father-s-day.jpg');
              background-size: cover;
              background-position: center center;
              background-attachment: fixed;
              display: flex;
              padding: 0;
              position: relative;
            }

            body::before {
              content: "";
              position: fixed;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background-color: rgba(0, 0, 0, 0.5);
              z-index: -1;
              pointer-events: none;
            }

            .menu-container {
              max-width: 600px;
              width: 90%;
              margin: 50px auto;
              background-color: rgba(60, 40, 35, 0.5);
              border-radius: 12px;
              padding: 40px;
              box-shadow: 0 5px 25px rgba(0, 0, 0, 0.4);
              backdrop-filter: blur(5px);
              border: 1px solid rgba(255, 255, 255, 0.1);
              color: #fff;
            }

            .menu-header {
              text-align: center;
              margin-bottom: 20px;
              border-bottom: 1px solid rgba(255, 255, 255, 0.15);
              padding-bottom: 20px;
            }

            .logo {
              font-size: 28px;
              font-weight: 500;
              color: #fff;
              margin-bottom: 15px;
            }

            .page-title {
              font-size: 22px;
              font-weight: 400;
              color: #fff;
              margin-bottom: 10px;
              text-align: center;
            }

            .confirmation-container {
              background: rgba(255, 255, 255, 0.9);
              border-radius: 8px;
              padding: 25px;
              color: #5a4a42;
              margin-top: 20px;
              box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

            .confirmation-header {
              display: flex;
              align-items: center;
              margin-bottom: 20px;
              padding-bottom: 15px;
              border-bottom: 1px solid rgba(160, 100, 80, 0.2);
            }

            .confirmation-icon {
              font-size: 24px;
              color: #e74c3c;
              margin-right: 15px;
            }

            .confirmation-title {
              font-size: 18px;
              font-weight: 500;
              color: #e74c3c;
            }

            .confirmation-content {
              margin-bottom: 25px;
            }

            .employee-info {
              background-color: rgba(160, 100, 80, 0.1);
              padding: 15px;
              border-radius: 8px;
              margin-bottom: 20px;
            }

            .employee-info p {
              margin-bottom: 8px;
            }

            .employee-info strong {
              font-weight: 500;
              color: rgba(60, 40, 35, 0.8);
            }

            .warning {
              background-color: rgba(231, 76, 60, 0.1);
              border-left: 4px solid #e74c3c;
              padding: 15px;
              margin-bottom: 20px;
              border-radius: 0 8px 8px 0;
            }

            .warning-title {
              font-weight: 500;
              color: #e74c3c;
              margin-bottom: 8px;
            }

            .confirmation-actions {
              display: flex;
              justify-content: flex-end;
              gap: 15px;
              margin-top: 30px;
            }

            .btn {
              padding: 12px 25px;
              border: none;
              border-radius: 6px;
              font-size: 14px;
              font-weight: 500;
              cursor: pointer;
              transition: all 0.3s;
              display: flex;
              align-items: center;
              gap: 8px;
            }

            .btn-danger {
              background: #e74c3c;
              color: white;
            }

            .btn-danger:hover {
              background: #c0392b;
            }

            .btn-secondary {
              background: rgba(255, 255, 255, 0.8);
              color: rgba(60, 40, 35, 0.8);
              border: 1px solid rgba(160, 100, 80, 0.3);
            }

            .btn-secondary:hover {
              background: rgba(255, 255, 255, 0.9);
            }

            @media (max-width: 768px) {
              .menu-container {
                width: 95%;
                padding: 20px;
              }
              
              .confirmation-actions {
                flex-direction: column;
              }
              
              .btn {
                width: 100%;
                justify-content: center;
              }
            }
          </style>
          <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
        </head>
        <body>
          <div class="menu-container">
            <header class="menu-header">
              <h1 class="logo">Abby's Cookies & Cakes</h1>
            </header>

            <h2 class="page-title">Confirmar Eliminación</h2>

            <div class="confirmation-container">
              <div class="confirmation-header">
                <i class="fas fa-exclamation-triangle confirmation-icon"></i>
                <h3 class="confirmation-title">¿Estás seguro de eliminar este empleado?</h3>
              </div>

              <div class="confirmation-content">
                <div class="employee-info">
                  <p><strong>ID:</strong> <?php echo htmlspecialchars($empleado['id']); ?></p>
                  <p><strong>Nombre:</strong> <?php echo htmlspecialchars($empleado['nombre']); ?></p>
                  <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($empleado['telefono']); ?></p>
                  <p><strong>Correo:</strong> <?php echo htmlspecialchars($empleado['correo']); ?></p>
                </div>

                <div class="warning">
                  <p class="warning-title">¡Atención!</p>
                  <p>Esta acción eliminará permanentemente al empleado y todos sus registros asociados:</p>
                  <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Se eliminarán <?php echo $turnos; ?> turnos asociados a este empleado.</li>
                    <li>Esta acción no se puede deshacer.</li>
                  </ul>
                </div>
              </div>

              <div class="confirmation-actions">
                <a href="agregarempleado.php" class="btn btn-secondary">
                  <i class="fas fa-times"></i> Cancelar
                </a>
                <a href="eliminar_empleado.php?id=<?php echo $id; ?>&confirmar=si" class="btn btn-danger">
                  <i class="fas fa-trash"></i> Sí, Eliminar Empleado
                </a>
              </div>
            </div>
          </div>
        </body>
        </html>
        <?php
        exit;
    }
} else {
    // Redirigir si no se proporcionó un ID válido
    header("Location: agregarempleado.php");
    exit;
}
?>