<?php
session_start();
require_once 'conexion.php';


// Establecer el conjunto de caracteres
$conn->set_charset("utf8mb4");

// Inicializar variables
$mensaje = '';
$tipoMensaje = '';
$periodoSeleccionado = 'Semanal';
$nominaId = 0;
$totalGeneral = 0;
$totalSalariosBase = 0;
$totalHorasExtras = 0;
$totalDeducciones = 0;

// Función para obtener el período actual o crear uno nuevo
function obtenerPeriodo($tipo = 'Semanal', $offset = 0) {
    switch ($tipo) {
        case 'Semanal':
            $inicio = date('Y-m-d', strtotime("monday this week $offset weeks"));
            $fin = date('Y-m-d', strtotime("sunday this week $offset weeks"));
            break;
        case 'Quincenal':
            $dia = date('d');
            if ($dia <= 15) {
                $inicio = date('Y-m-01', strtotime("$offset months"));
                $fin = date('Y-m-15', strtotime("$offset months"));
            } else {
                $inicio = date('Y-m-16', strtotime("$offset months"));
                $fin = date('Y-m-t', strtotime("$offset months"));
            }
            break;
        case 'Mensual':
            $inicio = date('Y-m-01', strtotime("$offset months"));
            $fin = date('Y-m-t', strtotime("$offset months"));
            break;
        default:
            $inicio = date('Y-m-d', strtotime("monday this week $offset weeks"));
            $fin = date('Y-m-d', strtotime("sunday this week $offset weeks"));
    }
    
    return [
        'inicio' => $inicio,
        'fin' => $fin,
        'nombre' => $tipo
    ];
}

// Función para formatear fechas
function formatearFecha($fecha) {
    $timestamp = strtotime($fecha);
    return date('d/m/Y', $timestamp);
}

// Procesar formulario si se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Crear nueva liquidación
    if (isset($_POST['nueva_liquidacion'])) {
        $periodoSeleccionado = $_POST['tipo_periodo'];
        $periodo = obtenerPeriodo($periodoSeleccionado);
        $fechaInicio = $periodo['inicio'];
        $fechaFin = $periodo['fin'];
        
        // Verificar si ya existe una nómina para este período
        $sqlCheckNomina = "SELECT id FROM nomina WHERE fecha_inicio = '$fechaInicio' AND fecha_fin = '$fechaFin'";
        $resultCheckNomina = $conn->query($sqlCheckNomina);
        
        if ($resultCheckNomina->num_rows > 0) {
            $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> Ya existe una liquidación para este período.</div>";
            $tipoMensaje = "warning";
            $rowNomina = $resultCheckNomina->fetch_assoc();
            $nominaId = $rowNomina['id'];
        } else {
            // Crear nueva nómina
            $sqlCrearNomina = "INSERT INTO nomina (periodo, fecha_inicio, fecha_fin, total_general, total_salarios_base, total_horas_extras, total_deducciones, fecha_creacion) 
                              VALUES ('$periodoSeleccionado', '$fechaInicio', '$fechaFin', 0, 0, 0, 0, NOW())";
            
            if ($conn->query($sqlCrearNomina) === TRUE) {
                $nominaId = $conn->insert_id;
                $mensaje = "<div class='mensaje-exito'><i class='fas fa-check-circle'></i> Nueva liquidación creada correctamente.</div>";
                $tipoMensaje = "success";
                
                // Inicializar detalles para todos los empleados
                $sqlEmpleados = "SELECT id FROM empleado";
                $resultEmpleados = $conn->query($sqlEmpleados);
                
                while ($rowEmpleado = $resultEmpleados->fetch_assoc()) {
                    $empleadoId = $rowEmpleado['id'];
                    $sqlInsertDetalle = "INSERT INTO detalle_nomina (nomina_id, empleado_id, sueldo_base, horas_extras, deducciones, total, estado)
                                        VALUES ($nominaId, $empleadoId, 0, 0, 0, 0, 'Pendiente')";
                    $conn->query($sqlInsertDetalle);
                }
            } else {
                $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> Error al crear la liquidación: " . $conn->error . "</div>";
                $tipoMensaje = "danger";
            }
        }
    }
    
    // Guardar cambios en la nómina
    if (isset($_POST['guardar_nomina'])) {
        $nominaId = $_POST['nomina_id'];
        
        // Recorrer los empleados y actualizar sus detalles de nómina
        foreach ($_POST['empleado'] as $empleadoId => $datos) {
            $sueldoBase = floatval($datos['sueldo_base']);
            $horasExtras = floatval($datos['horas_extras']);
            $deducciones = floatval($datos['deducciones']);
            $total = $sueldoBase + $horasExtras - $deducciones;
            
            // Verificar si ya existe un detalle para este empleado en esta nómina
            $sqlCheckDetalle = "SELECT id FROM detalle_nomina WHERE nomina_id = $nominaId AND empleado_id = $empleadoId";
            $resultCheckDetalle = $conn->query($sqlCheckDetalle);
            
            if ($resultCheckDetalle->num_rows > 0) {
                // Actualizar detalle existente
                $rowDetalle = $resultCheckDetalle->fetch_assoc();
                $detalleId = $rowDetalle['id'];
                
                $sqlUpdateDetalle = "UPDATE detalle_nomina SET 
                                    sueldo_base = $sueldoBase,
                                    horas_extras = $horasExtras,
                                    deducciones = $deducciones,
                                    total = $total,
                                    estado = '{$datos['estado']}'
                                    WHERE id = $detalleId";
                
                $conn->query($sqlUpdateDetalle);
            } else {
                // Crear nuevo detalle
                $sqlInsertDetalle = "INSERT INTO detalle_nomina (nomina_id, empleado_id, sueldo_base, horas_extras, deducciones, total, estado)
                                    VALUES ($nominaId, $empleadoId, $sueldoBase, $horasExtras, $deducciones, $total, '{$datos['estado']}')";
                
                $conn->query($sqlInsertDetalle);
            }
        }
        
        // Recalcular totales
        $sqlTotales = "SELECT 
                        SUM(sueldo_base) as total_base,
                        SUM(horas_extras) as total_extras,
                        SUM(deducciones) as total_deducciones,
                        SUM(total) as total_general
                        FROM detalle_nomina
                        WHERE nomina_id = $nominaId";
        
        $resultTotales = $conn->query($sqlTotales);
        
        if ($resultTotales->num_rows > 0) {
            $rowTotales = $resultTotales->fetch_assoc();
            
            $totalSalariosBase = $rowTotales['total_base'] ?: 0;
            $totalHorasExtras = $rowTotales['total_extras'] ?: 0;
            $totalDeducciones = $rowTotales['total_deducciones'] ?: 0;
            $totalGeneral = $rowTotales['total_general'] ?: 0;
            
            // Actualizar nómina con los nuevos totales
            $sqlUpdateNomina = "UPDATE nomina SET 
                                total_general = $totalGeneral,
                                total_salarios_base = $totalSalariosBase,
                                total_horas_extras = $totalHorasExtras,
                                total_deducciones = $totalDeducciones
                                WHERE id = $nominaId";
            
            $conn->query($sqlUpdateNomina);
        }
        
        $mensaje = "<div class='mensaje-exito'><i class='fas fa-check-circle'></i> Cambios guardados correctamente.</div>";
        $tipoMensaje = "success";
    }
    
    // Procesar nómina completa
    if (isset($_POST['procesar_nomina'])) {
        $nominaId = $_POST['nomina_id'];
        
        // Marcar todos los detalles como pagados
        $sqlProcesarNomina = "UPDATE detalle_nomina SET estado = 'Pagado' WHERE nomina_id = $nominaId";
        
        if ($conn->query($sqlProcesarNomina) === TRUE) {
            $mensaje = "<div class='mensaje-exito'><i class='fas fa-check-circle'></i> Nómina procesada correctamente. Todos los pagos han sido marcados como pagados.</div>";
            $tipoMensaje = "success";
        } else {
            $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> Error al procesar la nómina: " . $conn->error . "</div>";
            $tipoMensaje = "danger";
        }
    }
    
    // Cargar liquidación existente
    if (isset($_POST['cargar_liquidacion'])) {
        $nominaId = $_POST['liquidacion_id'];
    }
}

// Obtener la nómina actual o la más reciente
if (!$nominaId) {
    $sqlNominaReciente = "SELECT id, periodo, fecha_inicio, fecha_fin, total_general, total_salarios_base, total_horas_extras, total_deducciones 
                         FROM nomina 
                         ORDER BY fecha_creacion DESC 
                         LIMIT 1";
    $resultNominaReciente = $conn->query($sqlNominaReciente);
    
    if ($resultNominaReciente->num_rows > 0) {
        $rowNomina = $resultNominaReciente->fetch_assoc();
        $nominaId = $rowNomina['id'];
        $periodoSeleccionado = $rowNomina['periodo'];
        $fechaInicio = $rowNomina['fecha_inicio'];
        $fechaFin = $rowNomina['fecha_fin'];
        $totalGeneral = $rowNomina['total_general'];
        $totalSalariosBase = $rowNomina['total_salarios_base'];
        $totalHorasExtras = $rowNomina['total_horas_extras'];
        $totalDeducciones = $rowNomina['total_deducciones'];
    } else {
        // No hay nóminas, crear una nueva
        $periodo = obtenerPeriodo($periodoSeleccionado);
        $fechaInicio = $periodo['inicio'];
        $fechaFin = $periodo['fin'];
        
        $sqlCrearNomina = "INSERT INTO nomina (periodo, fecha_inicio, fecha_fin, total_general, total_salarios_base, total_horas_extras, total_deducciones, fecha_creacion) 
                          VALUES ('$periodoSeleccionado', '$fechaInicio', '$fechaFin', 0, 0, 0, 0, NOW())";
        
        if ($conn->query($sqlCrearNomina) === TRUE) {
            $nominaId = $conn->insert_id;
        } else {
            $mensaje = "<div class='mensaje-error'><i class='fas fa-exclamation-circle'></i> Error al crear la nómina inicial: " . $conn->error . "</div>";
            $tipoMensaje = "danger";
        }
    }
}

// Obtener todas las liquidaciones para el selector
$sqlLiquidaciones = "SELECT id, periodo, fecha_inicio, fecha_fin FROM nomina ORDER BY fecha_inicio DESC";
$resultLiquidaciones = $conn->query($sqlLiquidaciones);

// Obtener empleados y sus detalles de nómina
$sqlEmpleados = "SELECT e.id, e.nombre, dn.sueldo_base, dn.horas_extras, dn.deducciones, dn.total, dn.estado
                FROM empleado e
                LEFT JOIN detalle_nomina dn ON e.id = dn.empleado_id AND dn.nomina_id = $nominaId
                ORDER BY e.nombre";

$resultEmpleados = $conn->query($sqlEmpleados);

// Obtener historial de nóminas
$sqlHistorial = "SELECT id, periodo, fecha_inicio, fecha_fin, total_general 
                FROM nomina 
                WHERE id != $nominaId
                ORDER BY fecha_inicio DESC 
                LIMIT 4";

$resultHistorial = $conn->query($sqlHistorial);

// Contar empleados
$sqlCountEmpleados = "SELECT COUNT(*) as total FROM empleado";
$resultCountEmpleados = $conn->query($sqlCountEmpleados);
$rowCountEmpleados = $resultCountEmpleados->fetch_assoc();
$totalEmpleados = $rowCountEmpleados['total'];

// Obtener información de la nómina actual
$sqlNominaActual = "SELECT periodo, fecha_inicio, fecha_fin FROM nomina WHERE id = $nominaId";
$resultNominaActual = $conn->query($sqlNominaActual);
if ($resultNominaActual->num_rows > 0) {
    $rowNominaActual = $resultNominaActual->fetch_assoc();
    $periodoSeleccionado = $rowNominaActual['periodo'];
    $fechaInicio = $rowNominaActual['fecha_inicio'];
    $fechaFin = $rowNominaActual['fecha_fin'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Nómina - Abby's</title>
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

        /* Sidebar styles */
        .sidebar {
            width: 60px;
            height: 100vh;
            background-color: rgba(60, 40, 35, 0.9);
            transition: all 0.3s ease;
            overflow: hidden;
            position: fixed;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar:hover, .sidebar.open {
            width: 250px;
        }

        .sidebar-toggle {
            display: none;
            position: fixed;
            left: 10px;
            top: 10px;
            z-index: 101;
            background: rgba(160, 100, 80, 0.8);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
        }

        .sidebar-menu {
            padding-top: 20px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .menu-item:hover {
            background-color: rgba(160, 100, 80, 0.8);
            color: white;
        }

        .menu-item.active {
            background-color: rgba(160, 100, 80, 0.8);
        }

        .menu-icon {
            font-size: 20px;
            margin-right: 15px;
            min-width: 20px;
        }

        .menu-text {
            font-size: 15px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .sidebar:hover .menu-text, .sidebar.open .menu-text {
            opacity: 1;
        }

        .menu-container {
            max-width: 1600px;
            width: calc(100% - 60px);
            margin-left: 60px;
            background-color: rgba(60, 40, 35, 0.5);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            transition: all 0.3s;
        }

        .sidebar:hover ~ .menu-container, .sidebar.open ~ .menu-container {
            margin-left: 250px;
            width: calc(100% - 250px);
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
            text-align: left;
        }

        .slogan {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 300;
            letter-spacing: 0.8px;
            margin-bottom: 15px;
            text-align: left;
        }

        .user-info {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 20px;
        }

        .user-email {
            color: rgba(255, 255, 255, 0.9);
            margin-right: 15px;
            font-size: 13px;
        }

        .notification-btn {
            background: rgba(160, 100, 80, 0.8);
            border: none;
            color: white;
            cursor: pointer;
            font-size: 13px;
            padding: 10px 12px;
            border-radius: 6px;
            transition: all 0.3s;
            margin-right: 10px;
            position: relative;
        }

        .notification-btn:hover {
            background-color: rgba(180, 120, 100, 0.9);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #e74c3c;
            color: white;
            font-size: 10px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(60, 40, 35, 0.8);
        }

        .logout-btn {
            background: rgba(160, 100, 80, 0.8);
            border: none;
            color: white;
            cursor: pointer;
            font-size: 13px;
            padding: 10px 18px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background-color: rgba(180, 120, 100, 0.9);
        }

        /* Estilos para mensajes */
        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Estilos para la nómina */
        .form-container {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 25px;
            color: #5a4a42;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(160, 100, 80, 0.2);
        }

        .form-title {
            font-size: 18px;
            font-weight: 500;
            color: rgba(60, 40, 35, 0.8);
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(160, 100, 80, 0.3);
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: rgba(160, 100, 80, 0.8);
            box-shadow: 0 0 0 2px rgba(160, 100, 80, 0.2);
        }

        .form-select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(160, 100, 80, 0.3);
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            transition: all 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
        }

        .form-select:focus {
            outline: none;
            border-color: rgba(160, 100, 80, 0.8);
            box-shadow: 0 0 0 2px rgba(160, 100, 80, 0.2);
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

        .btn-primary {
            background: rgba(160, 100, 80, 0.8);
            color: white;
        }

        .btn-primary:hover {
            background: rgba(180, 120, 100, 0.9);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            color: rgba(60, 40, 35, 0.8);
            border: 1px solid rgba(160, 100, 80, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        /* Tabla de nómina */
        .nomina-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.9);
        }

        .nomina-table th,
        .nomina-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid rgba(160, 100, 80, 0.1);
        }

        .nomina-table th {
            background-color: rgba(160, 100, 80, 0.2);
            color: rgba(60, 40, 35, 0.9);
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nomina-table tr:last-child td {
            border-bottom: none;
        }

        .nomina-table tr:hover {
            background-color: rgba(160, 100, 80, 0.05);
        }

        .badge-pagado {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: normal;
            display: inline-block;
        }

        .badge-pendiente {
            background-color: #FFC107;
            color: #333;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: normal;
            display: inline-block;
        }

        /* Resumen de nómina */
        .summary-card {
            padding: 20px;
        }

        .summary-total {
            font-size: 32px;
            font-weight: bold;
            color: #5a4a42;
            margin-bottom: 10px;
        }

        .summary-label {
            color: rgba(60, 40, 35, 0.7);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .summary-item-label {
            color: rgba(60, 40, 35, 0.8);
        }

        .summary-item-value {
            font-weight: 500;
        }

        .summary-divider {
            height: 1px;
            background-color: rgba(160, 100, 80, 0.2);
            margin: 15px 0;
        }

        .summary-footer {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            font-size: 16px;
            color: #5a4a42;
            margin-top: 10px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(160, 100, 80, 0.2);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 500;
            color: rgba(60, 40, 35, 0.9);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: rgba(60, 40, 35, 0.6);
        }

        .modal-close:hover {
            color: rgba(60, 40, 35, 0.9);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid rgba(160, 100, 80, 0.2);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            body {
                background-size: cover;
                background-position: center center;
            }

            .sidebar {
                width: 0;
                z-index: 1000;
            }

            .sidebar.open {
                width: 250px;
            }

            .sidebar-toggle {
                display: block;
            }

            .menu-container {
                width: 100%;
                margin-left: 0;
                border-radius: 0;
                padding: 20px;
            }

            .sidebar:hover ~ .menu-container, .sidebar.open ~ .menu-container {
                margin-left: 0;
                width: 100%;
            }

            .nomina-grid {
                grid-template-columns: 1fr;
            }

            .nomina-table th,
            .nomina-table td {
                padding: 10px;
            }

            .nomina-table {
                font-size: 14px;
            }
        }

        /* Grid para la nómina */
        .nomina-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            margin: 0 12px;
            font-size: 13px;
            font-weight: 400;
            transition: all 0.3s;
            padding: 5px 12px;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-menu">
            <a href="empleado.php" class="menu-item">
                <i class="fas fa-home menu-icon"></i>
                <span class="menu-text">Inicio</span>
            </a>
            <a href="Empleados.php" class="menu-item">
                <i class="fas fa-users menu-icon"></i>
                <span class="menu-text">Empleados</span>
            </a>
            <a href="agregarempleado.php" class="menu-item">
                <i class="fas fa-user-plus menu-icon"></i>
                <span class="menu-text">Nuevo Empleado</span>
            </a>
            <a href="turnos.php" class="menu-item">
                <i class="fas fa-calendar-alt menu-icon"></i>
                <span class="menu-text">Turnos</span>
            </a>
            <a href="rolesypermisos.php" class="menu-item">
                <i class="fas fa-user-shield menu-icon"></i>
                <span class="menu-text">Roles y Permisos</span>
            </a>
            <a href="Actividades.php" class="menu-item">
                <i class="fas fa-tasks menu-icon"></i>
                <span class="menu-text">Actividades</span>
            </a>
            <a href="nomina.php" class="menu-item active">
                <i class="fas fa-file-invoice-dollar menu-icon"></i>
                <span class="menu-text">Nómina</span>
            </a>
        </div>
    </div>

    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="menu-container">
        <header class="menu-header">
            <h1 class="logo">Abby's Cookies & Cakes</h1>
            <div class="user-info">
                <p class="user-email">usuario@abby.com</p>
                <button class="notification-btn" onclick="window.location.href='Alertas y notificaciones.php'">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <button class="logout-btn" onclick="cerrarSesion()">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </button>
            </div>
        </header>

        <h2 class="page-title">Gestión de Nómina</h2>
        <p class="slogan">Administra los pagos y liquidaciones de tu equipo</p>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-header">
                <h3 class="form-title">Liquidaciones de Nómina</h3>
                <div class="d-flex">
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="me-2" style="margin-right: 10px;">
                        <select name="liquidacion_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Seleccionar liquidación...</option>
                            <?php
                            if ($resultLiquidaciones->num_rows > 0) {
                                $resultLiquidaciones->data_seek(0);
                                while($row = $resultLiquidaciones->fetch_assoc()) {
                                    $selected = ($row['id'] == $nominaId) ? 'selected' : '';
                                    echo "<option value='" . $row['id'] . "' $selected>" . $row['periodo'] . ": " . formatearFecha($row['fecha_inicio']) . " - " . formatearFecha($row['fecha_fin']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <input type="hidden" name="cargar_liquidacion" value="1">
                    </form>
                    <button class="btn btn-primary" onclick="openModal('nuevaLiquidacionModal')">
                        <i class="fas fa-plus"></i> Nueva Liquidación
                    </button>
                </div>
            </div>

            <div class="nomina-grid">
                <div>
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="formNomina">
                        <input type="hidden" name="nomina_id" value="<?php echo $nominaId; ?>">
                        <table class="nomina-table">
                            <thead>
                                <tr>
                                    <th>Empleado</th>
                                    <th>Sueldo Base ($)</th>
                                    <th>Horas Extras ($)</th>
                                    <th>Deducciones ($)</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($resultEmpleados->num_rows > 0) {
                                    while($row = $resultEmpleados->fetch_assoc()) {
                                        $sueldoBase = $row['sueldo_base'] ?: 0;
                                        $horasExtras = $row['horas_extras'] ?: 0;
                                        $deducciones = $row['deducciones'] ?: 0;
                                        $total = $row['total'] ?: ($sueldoBase + $horasExtras - $deducciones);
                                        $estado = $row['estado'] ?: 'Pendiente';
                                        
                                        echo "<tr>";
                                        echo "<td>" . $row['nombre'] . "</td>";
                                        echo "<td><input type='number' class='form-input sueldo-input' name='empleado[" . $row['id'] . "][sueldo_base]' value='$sueldoBase'></td>";
                                        echo "<td><input type='number' class='form-input horas-input' name='empleado[" . $row['id'] . "][horas_extras]' value='$horasExtras'></td>";
                                        echo "<td><input type='number' class='form-input deducciones-input' name='empleado[" . $row['id'] . "][deducciones]' value='$deducciones'></td>";
                                        echo "<td class='total-cell'>$" . number_format($total, 2) . "</td>";
                                        echo "<td>";
                                        
                                        if ($estado == 'Pagado') {
                                            echo "<span class='badge-pagado'>Pagado</span>";
                                            echo "<input type='hidden' name='empleado[" . $row['id'] . "][estado]' value='Pagado'>";
                                        } else {
                                            echo "<span class='badge-pendiente'>Pendiente</span>";
                                            echo "<input type='hidden' name='empleado[" . $row['id'] . "][estado]' value='Pendiente'>";
                                        }
                                        
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>No hay empleados registrados</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" name="guardar_nomina" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <div>
                    <div class="form-container" style="margin-top: 0;">
                        <div class="form-header">
                            <h3 class="form-title">Resumen de Nómina</h3>
                        </div>
                        <div class="summary-card">
                            <div class="summary-total">$<?php echo number_format($totalGeneral, 2); ?></div>
                            <div class="summary-label">Total a Pagar (Periodo Actual)</div>
                            
                            <div class="summary-item">
                                <div class="summary-item-label">Empleados:</div>
                                <div class="summary-item-value"><?php echo $totalEmpleados; ?></div>
                            </div>
                            
                            <div class="summary-item">
                                <div class="summary-item-label">Período:</div>
                                <div class="summary-item-value"><?php echo formatearFecha($fechaInicio) . ' - ' . formatearFecha($fechaFin); ?></div>
                            </div>
                            
                            <div class="summary-divider"></div>
                            
                            <h4 style="margin: 15px 0; font-size: 16px; color: #5a4a42;">Desglose de Pagos</h4>
                            
                            <div class="summary-item">
                                <div class="summary-item-label">Total salarios base:</div>
                                <div class="summary-item-value">$<?php echo number_format($totalSalariosBase, 2); ?></div>
                            </div>
                            
                            <div class="summary-item">
                                <div class="summary-item-label">Total horas extras:</div>
                                <div class="summary-item-value">$<?php echo number_format($totalHorasExtras, 2); ?></div>
                            </div>
                            
                            <div class="summary-item">
                                <div class="summary-item-label">Total deducciones:</div>
                                <div class="summary-item-value">-$<?php echo number_format($totalDeducciones, 2); ?></div>
                            </div>
                            
                            <div class="summary-divider"></div>
                            
                            <div class="summary-footer">
                                <div>Total neto a pagar:</div>
                                <div>$<?php echo number_format($totalGeneral, 2); ?></div>
                            </div>
                        </div>
                        <div style="padding: 0 20px 20px 20px;">
                            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                <input type="hidden" name="nomina_id" value="<?php echo $nominaId; ?>">
                                <button type="submit" name="procesar_nomina" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-file-invoice-dollar"></i> Procesar Nómina Completa
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="form-container" style="margin-top: 20px;">
                        <div class="form-header">
                            <h3 class="form-title">Reportes Históricos</h3>
                            <div style="display: flex; gap: 10px;">
                                <select class="form-select" style="width: 150px;">
                                    <option>Bimestral</option>
                                    <option>Trimestral</option>
                                    <option>Anual</option>
                                </select>
                                <button class="btn btn-secondary">
                                    <i class="fas fa-file-export"></i> Generar Reporte
                                </button>
                            </div>
                        </div>
                        <table class="nomina-table">
                            <tbody>
                                <?php
                                if ($resultHistorial->num_rows > 0) {
                                    while($row = $resultHistorial->fetch_assoc()) {
                                        $periodoFecha = date('F Y', strtotime($row['fecha_inicio']));
                                        echo "<tr>";
                                        echo "<td>" . $periodoFecha . ":</td>";
                                        echo "<td style='text-align: right;'>$" . number_format($row['total_general'], 2) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='2' style='text-align: center;'>No hay historial disponible</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-links">
            <a href="empleado.php"><i class="fas fa-arrow-left"></i> Volver a Empleados</a>
            <a href="Ayuda.html"><i class="fas fa-question-circle"></i> Ayuda</a>
        </div>
    </div>

    <!-- Modal Nueva Liquidación -->
    <div id="nuevaLiquidacionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Nueva Liquidación</h3>
                <button class="modal-close" onclick="closeModal('nuevaLiquidacionModal')">&times;</button>
            </div>
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <div class="form-group">
                    <label for="tipo_periodo" class="form-label">Tipo de Período</label>
                    <select class="form-select" id="tipo_periodo" name="tipo_periodo">
                        <option value="Semanal">Semanal</option>
                        <option value="Quincenal">Quincenal</option>
                        <option value="Mensual">Mensual</option>
                    </select>
                </div>
                <p style="margin-top: 15px; color: #666; font-size: 14px;">Se creará una nueva liquidación para el período actual según el tipo seleccionado.</p>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('nuevaLiquidacionModal')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="nueva_liquidacion" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Liquidación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 900 && 
                !sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target) && 
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });

        // Función para cerrar sesión
        function cerrarSesion() {
            if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
                window.location.href = 'login.html';
            }
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Script para calcular automáticamente los totales
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const sueldoInput = row.querySelector('.sueldo-input');
                const horasInput = row.querySelector('.horas-input');
                const deduccionesInput = row.querySelector('.deducciones-input');
                const totalCell = row.querySelector('.total-cell');
                
                if (sueldoInput && horasInput && deduccionesInput && totalCell) {
                    const updateTotal = function() {
                        const sueldo = parseFloat(sueldoInput.value) || 0;
                        const horas = parseFloat(horasInput.value) || 0;
                        const deducciones = parseFloat(deduccionesInput.value) || 0;
                        const total = sueldo + horas - deducciones;
                        totalCell.textContent = '$' + total.toFixed(2);
                    };
                    
                    sueldoInput.addEventListener('input', updateTotal);
                    horasInput.addEventListener('input', updateTotal);
                    deduccionesInput.addEventListener('input', updateTotal);
                }
            });
        });
    </script>
</body>
</html>
