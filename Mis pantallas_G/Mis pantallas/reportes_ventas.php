<?php
session_start();
require_once 'conexion.php';

// Establecer zona horaria
date_default_timezone_set('America/Mexico_City');

// Inicializar variables de filtro
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01'); // Primer día del mes actual
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d'); // Día actual
$metodo_pago = isset($_GET['metodo_pago']) ? $_GET['metodo_pago'] : '';
$producto_id = isset($_GET['producto_id']) ? $_GET['producto_id'] : '';

// Consulta para obtener métodos de pago únicos
$query_metodos = "SELECT DISTINCT metodo_pago FROM ventas ORDER BY metodo_pago";
$resultado_metodos = $conn->query($query_metodos);

// Consulta para obtener productos
$query_productos = "SELECT id, nombre FROM producto ORDER BY nombre";
$resultado_productos = $conn->query($query_productos);

// Construir la consulta base para ventas
$query_ventas = "SELECT v.id, v.fecha, v.total, v.metodo_pago, v.descripcion 
                FROM ventas v 
                WHERE v.fecha BETWEEN ? AND ? ";

$params = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];
$types = "ss";

if (!empty($metodo_pago)) {
    $query_ventas .= "AND v.metodo_pago = ? ";
    $params[] = $metodo_pago;
    $types .= "s";
}

if (!empty($producto_id)) {
    $query_ventas .= "AND v.id IN (SELECT venta_id FROM detalle_venta WHERE producto_id = ?) ";
    $params[] = $producto_id;
    $types .= "i";
}

$query_ventas .= "ORDER BY v.fecha DESC";

// Preparar y ejecutar la consulta
$stmt_ventas = $conn->prepare($query_ventas);
$stmt_ventas->bind_param($types, ...$params);
$stmt_ventas->execute();
$resultado_ventas = $stmt_ventas->get_result();

// Consulta para obtener estadísticas generales
$query_stats = "SELECT 
                COUNT(v.id) as total_ventas,
                SUM(v.total) as ingresos_totales,
                AVG(v.total) as promedio_venta,
                MAX(v.total) as venta_maxima,
                MIN(v.total) as venta_minima
                FROM ventas v 
                WHERE v.fecha BETWEEN ? AND ? ";

$params_stats = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];
$types_stats = "ss";

if (!empty($metodo_pago)) {
    $query_stats .= "AND v.metodo_pago = ? ";
    $params_stats[] = $metodo_pago;
    $types_stats .= "s";
}

if (!empty($producto_id)) {
    $query_stats .= "AND v.id IN (SELECT venta_id FROM detalle_venta WHERE producto_id = ?) ";
    $params_stats[] = $producto_id;
    $types_stats .= "i";
}

$stmt_stats = $conn->prepare($query_stats);
$stmt_stats->bind_param($types_stats, ...$params_stats);
$stmt_stats->execute();
$resultado_stats = $stmt_stats->get_result();
$stats = $resultado_stats->fetch_assoc();

// Consulta para obtener productos más vendidos
$query_top_productos = "SELECT 
                        p.id,
                        p.nombre,
                        SUM(dv.cantidad) as cantidad_total,
                        SUM(dv.subtotal) as ingresos_total
                        FROM detalle_venta dv
                        JOIN producto p ON dv.producto_id = p.id
                        JOIN ventas v ON dv.venta_id = v.id
                        WHERE v.fecha BETWEEN ? AND ? ";

$params_productos = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];
$types_productos = "ss";

if (!empty($metodo_pago)) {
    $query_top_productos .= "AND v.metodo_pago = ? ";
    $params_productos[] = $metodo_pago;
    $types_productos .= "s";
}

if (!empty($producto_id)) {
    $query_top_productos .= "AND p.id = ? ";
    $params_productos[] = $producto_id;
    $types_productos .= "i";
}

$query_top_productos .= "GROUP BY p.id, p.nombre
                        ORDER BY cantidad_total DESC
                        LIMIT 10";

$stmt_productos = $conn->prepare($query_top_productos);
$stmt_productos->bind_param($types_productos, ...$params_productos);
$stmt_productos->execute();
$resultado_top_productos = $stmt_productos->get_result();

// Consulta para obtener ventas por día (para gráfico)
$query_ventas_diarias = "SELECT 
                        DATE(v.fecha) as fecha,
                        COUNT(v.id) as num_ventas,
                        SUM(v.total) as total_ventas
                        FROM ventas v
                        WHERE v.fecha BETWEEN ? AND ? ";

$params_diarias = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];
$types_diarias = "ss";

if (!empty($metodo_pago)) {
    $query_ventas_diarias .= "AND v.metodo_pago = ? ";
    $params_diarias[] = $metodo_pago;
    $types_diarias .= "s";
}

if (!empty($producto_id)) {
    $query_ventas_diarias .= "AND v.id IN (SELECT venta_id FROM detalle_venta WHERE producto_id = ?) ";
    $params_diarias[] = $producto_id;
    $types_diarias .= "i";
}

$query_ventas_diarias .= "GROUP BY DATE(v.fecha)
                        ORDER BY DATE(v.fecha)";

$stmt_diarias = $conn->prepare($query_ventas_diarias);
$stmt_diarias->bind_param($types_diarias, ...$params_diarias);
$stmt_diarias->execute();
$resultado_ventas_diarias = $stmt_diarias->get_result();

// Preparar datos para el gráfico
$fechas = [];
$totales = [];
$num_ventas = [];

while ($row = $resultado_ventas_diarias->fetch_assoc()) {
    $fechas[] = $row['fecha'];
    $totales[] = $row['total_ventas'];
    $num_ventas[] = $row['num_ventas'];
}

// Consulta para obtener ventas por método de pago
$query_metodos_pago = "SELECT 
                      v.metodo_pago,
                      COUNT(v.id) as num_ventas,
                      SUM(v.total) as total_ventas
                      FROM ventas v
                      WHERE v.fecha BETWEEN ? AND ? ";

$params_metodos = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];
$types_metodos = "ss";

if (!empty($metodo_pago)) {
    $query_metodos_pago .= "AND v.metodo_pago = ? ";
    $params_metodos[] = $metodo_pago;
    $types_metodos .= "s";
}

if (!empty($producto_id)) {
    $query_metodos_pago .= "AND v.id IN (SELECT venta_id FROM detalle_venta WHERE producto_id = ?) ";
    $params_metodos[] = $producto_id;
    $types_metodos .= "i";
}

$query_metodos_pago .= "GROUP BY v.metodo_pago
                      ORDER BY total_ventas DESC";

$stmt_metodos = $conn->prepare($query_metodos_pago);
$stmt_metodos->bind_param($types_metodos, ...$params_metodos);
$stmt_metodos->execute();
$resultado_metodos_pago = $stmt_metodos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Ventas - Abby Cookies & Cakes</title>
    <link rel="stylesheet" href="css/ventas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Incluir Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #a87165;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #775B53;
            font-size: 0.9em;
        }
        
        .chart-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .filter-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #775B53;
        }
        
        .filter-input {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .filter-button {
            background-color: #a87165;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .filter-button:hover {
            background-color: #8c5b50;
        }
        
        .export-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }
        
        .export-button:hover {
            background-color: #218838;
        }
        
        .print-button {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }
        
        .print-button:hover {
            background-color: #138496;
        }
        
        .top-products-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .top-products-title {
            color: #a87165;
            margin-bottom: 15px;
            border-bottom: 2px solid #f0e6e3;
            padding-bottom: 10px;
        }
        
        .product-bar {
            height: 30px;
            background-color: #a87165;
            margin-bottom: 10px;
            border-radius: 5px;
            position: relative;
            transition: width 1s ease-in-out;
        }
        
        .product-label {
            position: absolute;
            left: 10px;
            top: 5px;
            color: white;
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 70%;
        }
        
        .product-value {
            position: absolute;
            right: 10px;
            top: 5px;
            color: white;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .payment-methods-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        @media print {
            .sidebar, .menu-header, .filter-container, .no-print {
                display: none !important;
            }
            
            body, .panel-background, .main-panel {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .report-container {
                box-shadow: none !important;
            }
            
            .chart-container, .dashboard-container, .top-products-container, .payment-methods-container {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- sidebar -->
    <div id="sidebar-placeholder" class="no-print"></div>

    <script>
        fetch('sidebar_i.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('sidebar-placeholder').innerHTML = data;
            });
    </script>
    
    <div class="panel-background">
        <div class="main-panel">
            <header class="menu-header no-print">
                <h1 class="logo">Abby's Cookies & Cakes</h1>
                <div class="user-info">
                    <p class="user-email">usuario@abby.com</p>
                    <button class="notification-btn" onclick="window.location.href='Alertas_y_notificaciones.php'">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <button class="logout-btn" onclick="cerrarSesion()">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </button>
                </div>

                <div class="top-bar">
                    <a href="ventas.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
                </div>
            </header>

            <h1 class="page-title">Reportes de Ventas</h1>
            
            <!-- Filtros -->
            <div class="filter-container no-print">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" id="filtroForm">
                    <div class="filter-group">
                        <label for="fecha_inicio" class="filter-label">Fecha Inicio:</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" class="filter-input" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="fecha_fin" class="filter-label">Fecha Fin:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" class="filter-input" value="<?php echo $fecha_fin; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="metodo_pago" class="filter-label">Método de Pago:</label>
                        <select id="metodo_pago" name="metodo_pago" class="filter-input">
                            <option value="">Todos</option>
                            <?php 
                            while ($metodo = $resultado_metodos->fetch_assoc()): 
                                $selected = ($metodo['metodo_pago'] == $metodo_pago) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $metodo['metodo_pago']; ?>" <?php echo $selected; ?>>
                                    <?php echo ucfirst($metodo['metodo_pago']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="producto_id" class="filter-label">Producto:</label>
                        <select id="producto_id" name="producto_id" class="filter-input">
                            <option value="">Todos</option>
                            <?php 
                            while ($producto = $resultado_productos->fetch_assoc()): 
                                $selected = ($producto['id'] == $producto_id) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $producto['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo $producto['nombre']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <button type="submit" class="filter-button">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button type="button" class="export-button" onclick="exportarCSV()">
                            <i class="fas fa-file-csv"></i> Exportar CSV
                        </button>
                        <button type="button" class="print-button" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="report-container" id="reporteContenido">
                <!-- Dashboard de estadísticas -->
                <h2 class="section-title">RESUMEN DE VENTAS</h2>
                <p>Periodo: <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></p>
                
                <div class="dashboard-container">
                    <div class="stat-card">
                        <div class="stat-label">Total de Ventas</div>
                        <div class="stat-value"><?php echo $stats['total_ventas']; ?></div>
                        <div class="stat-label">transacciones</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Ingresos Totales</div>
                        <div class="stat-value">$<?php echo number_format($stats['ingresos_totales'], 2); ?></div>
                        <div class="stat-label">pesos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Promedio por Venta</div>
                        <div class="stat-value">$<?php echo number_format($stats['promedio_venta'], 2); ?></div>
                        <div class="stat-label">pesos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Venta Máxima</div>
                        <div class="stat-value">$<?php echo number_format($stats['venta_maxima'], 2); ?></div>
                        <div class="stat-label">pesos</div>
                    </div>
                </div>
                
                <!-- Gráfico de ventas por día -->
                <div class="chart-container">
                    <h2 class="section-title">VENTAS POR DÍA</h2>
                    <canvas id="ventasDiariasChart"></canvas>
                </div>
                
                <!-- Top productos vendidos -->
                <div class="top-products-container">
                    <h2 class="section-title">PRODUCTOS MÁS VENDIDOS</h2>
                    <div id="topProductsChart">
                        <?php 
                        $max_cantidad = 0;
                        $resultado_top_productos->data_seek(0);
                        while ($producto = $resultado_top_productos->fetch_assoc()) {
                            if ($producto['cantidad_total'] > $max_cantidad) {
                                $max_cantidad = $producto['cantidad_total'];
                            }
                        }
                        
                        $resultado_top_productos->data_seek(0);
                        while ($producto = $resultado_top_productos->fetch_assoc()): 
                            $porcentaje = ($max_cantidad > 0) ? ($producto['cantidad_total'] / $max_cantidad) * 100 : 0;
                        ?>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span><?php echo $producto['nombre']; ?></span>
                                    <span><?php echo $producto['cantidad_total']; ?> unidades - $<?php echo number_format($producto['ingresos_total'], 2); ?></span>
                                </div>
                                <div class="product-bar" style="width: <?php echo $porcentaje; ?>%;">
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Ventas por método de pago -->
                <div class="payment-methods-container">
                    <h2 class="section-title">VENTAS POR MÉTODO DE PAGO</h2>
                    <canvas id="metodoPagoChart"></canvas>
                </div>
                
                <!-- Tabla de ventas -->
                <h2 class="section-title">DETALLE DE VENTAS</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Método de pago</th>
                            <th>Descripción</th>
                            <th class="no-print">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado_ventas->num_rows > 0): ?>
                            <?php while ($venta = $resultado_ventas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $venta['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></td>
                                    <td>$<?php echo number_format($venta['total'], 2); ?></td>
                                    <td><?php echo ucfirst($venta['metodo_pago']); ?></td>
                                    <td><?php echo $venta['descripcion']; ?></td>
                                    <td class="no-print">
                                        <a href="javascript:void(0)" class="btn-ver-detalles" onclick="cargarDetalleVenta(<?php echo $venta['id']; ?>)">
                                            <i class="fas fa-eye"></i> Ver Detalles
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No se encontraron ventas en el período seleccionado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal para detalles de venta -->
    <div id="detalleModal" class="modal no-print">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2>Detalle de Venta</h2>
            <div id="detalle-contenido"></div>
        </div>
    </div>
    
    <script>
        // Gráfico de ventas por día
        const ctxVentas = document.getElementById('ventasDiariasChart').getContext('2d');
        const ventasDiariasChart = new Chart(ctxVentas, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($fecha) { return date('d/m/Y', strtotime($fecha)); }, $fechas)); ?>,
                datasets: [
                    {
                        label: 'Total de Ventas ($)',
                        data: <?php echo json_encode($totales); ?>,
                        borderColor: '#a87165',
                        backgroundColor: 'rgba(168, 113, 101, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Número de Ventas',
                        data: <?php echo json_encode($num_ventas); ?>,
                        borderColor: '#5a4a42',
                        backgroundColor: 'rgba(90, 74, 66, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total de Ventas ($)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Número de Ventas'
                        }
                    }
                }
            }
        });
        
        // Gráfico de métodos de pago
        const ctxMetodos = document.getElementById('metodoPagoChart').getContext('2d');
        const metodoPagoChart = new Chart(ctxMetodos, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    $resultado_metodos_pago->data_seek(0);
                    while ($metodo = $resultado_metodos_pago->fetch_assoc()) {
                        echo "'" . ucfirst($metodo['metodo_pago']) . "', ";
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        $resultado_metodos_pago->data_seek(0);
                        while ($metodo = $resultado_metodos_pago->fetch_assoc()) {
                            echo $metodo['total_ventas'] . ", ";
                        }
                        ?>
                    ],
                    backgroundColor: [
                        '#a87165',
                        '#5a4a42',
                        '#d4a373',
                        '#e9c46a',
                        '#2a9d8f'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                let percentage = Math.round((value / total) * 100);
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Función para cargar los detalles de una venta
        function cargarDetalleVenta(ventaId) {
            fetch('obtener_detalle_venta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ venta_id: ventaId })
            })
            .then(response => response.json())
            .then(data => {
                const detalleContenido = document.getElementById('detalle-contenido');
                
                if (data.venta && data.detalles) {
                    let html = `
                        <div style="margin-bottom: 20px;">
                            <p><strong>Venta ID:</strong> ${data.venta.id}</p>
                            <p><strong>Fecha:</strong> ${data.venta.fecha}</p>
                            <p><strong>Método de Pago:</strong> ${data.venta.metodo_pago}</p>
                            <p><strong>Descripción:</strong> ${data.venta.descripcion}</p>
                        </div>
                        
                        <h3>Productos</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Producto</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Cantidad</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Precio Unitario</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    data.detalles.forEach(detalle => {
                        html += `
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;">${detalle.nombre_producto}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">${detalle.cantidad}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">$${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                                <td style="border: 1px solid #ddd; padding: 8px;">$${parseFloat(detalle.subtotal).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px; text-align: right;">
                            <h3>Total: $${parseFloat(data.venta.total).toFixed(2)}</h3>
                        </div>
                    `;
                    
                    detalleContenido.innerHTML = html;
                } else {
                    detalleContenido.innerHTML = '<p>No se encontraron detalles para esta venta.</p>';
                }
                
                // Mostrar el modal
                document.getElementById('detalleModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error al cargar detalles:', error);
                alert('Error al cargar los detalles de la venta.');
            });
        }

        // Función para cerrar el modal
        function cerrarModal() {
            document.getElementById('detalleModal').style.display = 'none';
        }

        // Cerrar el modal si se hace clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('detalleModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Función para exportar a CSV
        function exportarCSV() {
            // Obtener los datos de la tabla
            const table = document.querySelector('table');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length - 1; j++) { // Excluir la columna de opciones
                    // Limpiar el texto (quitar $, etc.)
                    let text = cols[j].innerText.replace(/\$/g, '').trim();
                    // Escapar comillas dobles
                    text = text.replace(/"/g, '""');
                    // Añadir comillas alrededor del texto
                    row.push('"' + text + '"');
                }
                csv.push(row.join(','));
            }
            
            // Descargar CSV
            const csvString = csv.join('\n');
            const filename = 'reporte_ventas_' + new Date().toISOString().slice(0, 10) + '.csv';
            const link = document.createElement('a');
            link.style.display = 'none';
            link.setAttribute('target', '_blank');
            link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvString));
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>