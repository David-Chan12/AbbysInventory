<?php
session_start();
require_once 'conexion.php';

// Obtener estadísticas generales
$queryTotalPedidos = "SELECT COUNT(*) as total FROM pedido";
$resultadoTotalPedidos = $conn->query($queryTotalPedidos);
$totalPedidos = $resultadoTotalPedidos->fetch_assoc()['total'];

$queryPedidosPorEstado = "SELECT e.descripcion, COUNT(*) as cantidad 
                          FROM pedido p
                          JOIN estado e ON p.estado_pedido = e.id
                          GROUP BY p.estado_pedido";
$resultadoPedidosPorEstado = $conn->query($queryPedidosPorEstado);

$queryPedidosPorEmpleado = "SELECT e.nombre, COUNT(*) as cantidad 
                            FROM empelado_pedido ep
                            JOIN empleado e ON ep.empleado_id = e.id
                            GROUP BY ep.empleado_id";
$resultadoPedidosPorEmpleado = $conn->query($queryPedidosPorEmpleado);

// Obtener pedidos recientes
$queryPedidosRecientes = "SELECT p.id, p.fecha, p.cliente, e.descripcion as estado
                          FROM pedido p
                          JOIN estado e ON p.estado_pedido = e.id
                          ORDER BY p.fecha DESC
                          LIMIT 5";
$resultadoPedidosRecientes = $conn->query($queryPedidosRecientes);

// Obtener pedidos por mes (para gráfico)
$queryPedidosPorMes = "SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, COUNT(*) as cantidad
                       FROM pedido
                       WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                       GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                       ORDER BY mes";
$resultadoPedidosPorMes = $conn->query($queryPedidosPorMes);

$meses = [];
$cantidades = [];

if ($resultadoPedidosPorMes && $resultadoPedidosPorMes->num_rows > 0) {
    while ($row = $resultadoPedidosPorMes->fetch_assoc()) {
        $fecha = explode('-', $row['mes']);
        $nombreMes = '';
        
        switch($fecha[1]) {
            case '01': $nombreMes = 'Enero'; break;
            case '02': $nombreMes = 'Febrero'; break;
            case '03': $nombreMes = 'Marzo'; break;
            case '04': $nombreMes = 'Abril'; break;
            case '05': $nombreMes = 'Mayo'; break;
            case '06': $nombreMes = 'Junio'; break;
            case '07': $nombreMes = 'Julio'; break;
            case '08': $nombreMes = 'Agosto'; break;
            case '09': $nombreMes = 'Septiembre'; break;
            case '10': $nombreMes = 'Octubre'; break;
            case '11': $nombreMes = 'Noviembre'; break;
            case '12': $nombreMes = 'Diciembre'; break;
        }
        
        $meses[] = $nombreMes . ' ' . $fecha[0];
        $cantidades[] = $row['cantidad'];
    }
}

// Generar datos para el gráfico
$mesesJSON = json_encode($meses);
$cantidadesJSON = json_encode($cantidades);

// Obtener datos para el reporte de pedidos por estado
$estadosPedidos = [];
$cantidadPorEstado = [];

if ($resultadoPedidosPorEstado && $resultadoPedidosPorEstado->num_rows > 0) {
    while ($row = $resultadoPedidosPorEstado->fetch_assoc()) {
        $estadosPedidos[] = $row['descripcion'];
        $cantidadPorEstado[] = $row['cantidad'];
    }
}

$estadosPedidosJSON = json_encode($estadosPedidos);
$cantidadPorEstadoJSON = json_encode($cantidadPorEstado);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reportes de Pedidos</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
      background-image: url('Imagenes/fe09f794b5cf5833818976f9fd1e3522.jpg');
      background-size: cover;
      background-position: center center;
      background-attachment: fixed;
      display: flex;
      padding: 20px;
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

    .container {
      display: flex;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      background-color: rgba(60, 40, 35, 0.4);
      border-radius: 12px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      overflow: hidden;
      color: #fff;
    }

    .sidebar {
      width: 250px;
      background-color: rgba(80, 50, 45, 0.4);
      padding: 30px 20px;
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      flex-direction: column;
    }

    .sidebar-title {
      color: #fff;
      font-size: 22px;
      font-weight: 500;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
      text-align: center;
    }

    .sidebar-menu {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .menu-item {
      background: rgba(76, 51, 47, 0.914);
      border: none;
      color: rgba(255, 255, 255, 0.786);
      cursor: pointer;
      font-size: 14px;
      padding: 12px 15px;
      border-radius: 6px;
      transition: all 0.3s;
      text-align: left;
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .menu-item:hover {
      background-color: rgba(160, 100, 80, 0.8);
    }

    .menu-item.active {
      background-color: rgba(160, 100, 80, 0.8);
      font-weight: 500;
    }

    .main-content {
      flex: 1;
      padding: 30px;
      overflow-y: auto;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      gap: 15px;
    }

    .dashboard-title {
      text-align: center;
      margin-bottom: 30px;
      color: white;
      font-size: 24px;
    }

    .cards-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .card {
      background: rgba(255, 255, 255, 0.1);
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
      text-align: center;
    }

    .card-icon {
      font-size: 30px;
      margin-bottom: 15px;
      color: rgba(160, 100, 80, 0.8);
    }

    .card-title {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 10px;
    }

    .card-value {
      font-size: 24px;
      font-weight: 600;
      color: white;
    }

    .charts-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .chart-card {
      background: rgba(255, 255, 255, 0.1);
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .chart-title {
      text-align: center;
      margin-bottom: 20px;
      color: white;
      font-size: 18px;
    }

    .table-container {
      background: rgba(255, 255, 255, 0.1);
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 30px;
    }

    .table-title {
      text-align: center;
      margin-bottom: 20px;
      color: white;
      font-size: 18px;
    }

    .tabla {
      width: 100%;
      border-collapse: collapse;
    }

    .tabla th,
    .tabla td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .tabla th {
      background-color: rgba(160, 100, 80, 0.8);
      color: white;
      font-weight: 500;
    }

    .tabla tr:hover {
      background-color: rgba(255, 255, 255, 0.05);
    }

    .settings-button {
      background: rgba(160, 100, 80, 0.8);
      border: none;
      color: white;
      cursor: pointer;
      font-size: 16px;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .settings-button:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    .header .settings-button {
      padding: 0;
      width: 36px;
      height: 36px;
      border-radius: 50%;
    }

    .export-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 30px;
    }

    .export-button {
      padding: 10px 20px;
      background-color: rgba(160, 100, 80, 0.8);
      border: none;
      color: white;
      cursor: pointer;
      border-radius: 6px;
      font-weight: 500;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .export-button:hover {
      background-color: rgba(180, 120, 100, 0.9);
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }

      .sidebar-menu {
        flex-direction: row;
        flex-wrap: wrap;
      }

      .menu-item {
        flex: 1 0 calc(50% - 5px);
      }
      
      .main-content {
        padding: 20px;
      }

      .charts-container {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="sidebar">
      <h2 class="sidebar-title">Menú</h2>
      <div class="sidebar-menu">
        <a href="Pedidos.php" class="menu-item">
          <i class="fas fa-calendar-alt"></i> Calendario
        </a>
        <a href="Lista-Pedidos.php" class="menu-item">
          <i class="fas fa-list"></i> Pedidos
        </a>
        <a href="Reportes-pedidos.php" class="menu-item active">
          <i class="fas fa-chart-bar"></i> Reportes
        </a>
        <a href="Menu.php" class="menu-item">
          <i class="fas fa-home"></i> Menu
        </a>
      </div>
    </div>

    <div class="main-content">
      <div class="header">
        <p class="user-email"><i class="fas fa-user-circle"></i> usuario@abby.com</p>
        <button class="settings-button">
          <i class="fas fa-cog"></i>
        </button>
      </div>

      <h1 class="dashboard-title">Dashboard de Pedidos</h1>

      <div class="cards-container">
        <div class="card">
          <div class="card-icon">
            <i class="fas fa-shopping-bag"></i>
          </div>
          <div class="card-title">Total de Pedidos</div>
          <div class="card-value"><?php echo $totalPedidos; ?></div>
        </div>
        
        <?php 
        if ($resultadoPedidosPorEstado && $resultadoPedidosPorEstado->num_rows > 0) {
          $resultadoPedidosPorEstado->data_seek(0);
          while ($row = $resultadoPedidosPorEstado->fetch_assoc()) {
            $iconClass = '';
            switch(strtoupper($row['descripcion'])) {
              case 'RECIBIDO': $iconClass = 'fa-inbox'; break;
              case 'EN PROCESO': $iconClass = 'fa-cogs'; break;
              case 'LISTO': $iconClass = 'fa-check-circle'; break;
              case 'ENTREGADO': $iconClass = 'fa-truck'; break;
              default: $iconClass = 'fa-circle'; break;
            }
            echo '<div class="card">
                    <div class="card-icon">
                      <i class="fas ' . $iconClass . '"></i>
                    </div>
                    <div class="card-title">Pedidos ' . $row['descripcion'] . '</div>
                    <div class="card-value">' . $row['cantidad'] . '</div>
                  </div>';
          }
        }
        ?>
      </div>

      <div class="charts-container">
        <div class="chart-card">
          <h3 class="chart-title">Pedidos por Mes</h3>
          <canvas id="pedidosPorMes"></canvas>
        </div>
        
        <div class="chart-card">
          <h3 class="chart-title">Pedidos por Estado</h3>
          <canvas id="pedidosPorEstado"></canvas>
        </div>
      </div>

      <div class="table-container">
        <h3 class="table-title">Pedidos Recientes</h3>
        <table class="tabla">
          <thead>
            <tr>
              <th>ID</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($resultadoPedidosRecientes && $resultadoPedidosRecientes->num_rows > 0) {
              while ($row = $resultadoPedidosRecientes->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
                echo "<td>" . $row['cliente'] . "</td>";
                echo "<td>" . $row['estado'] . "</td>";
                echo "<td><a href='Lista-Pedidos.php?editar=" . $row['id'] . "' class='menu-item' style='display: inline-block; padding: 5px 10px;'><i class='fas fa-edit'></i> Ver</a></td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='5' style='text-align: center;'>No hay pedidos recientes</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>

      <div class="export-buttons">
        <button class="export-button">
          <i class="fas fa-file-pdf"></i> Exportar a PDF
        </button>
        <button class="export-button">
          <i class="fas fa-file-excel"></i> Exportar a Excel
        </button>
      </div>
    </div>
  </div>

  <script>
    // Gráfico de pedidos por mes
    const ctxMes = document.getElementById('pedidosPorMes').getContext('2d');
    const pedidosPorMes = new Chart(ctxMes, {
      type: 'line',
      data: {
        labels: <?php echo $mesesJSON ?: '[]'; ?>,
        datasets: [{
          label: 'Cantidad de Pedidos',
          data: <?php echo $cantidadesJSON ?: '[]'; ?>,
          backgroundColor: 'rgba(160, 100, 80, 0.2)',
          borderColor: 'rgba(160, 100, 80, 1)',
          borderWidth: 2,
          tension: 0.3,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              color: 'white'
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              color: 'white'
            },
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            }
          },
          x: {
            ticks: {
              color: 'white'
            },
            grid: {
              color: 'rgba(255, 255, 255, 0.1)'
            }
          }
        }
      }
    });

    // Gráfico de pedidos por estado
    const ctxEstado = document.getElementById('pedidosPorEstado').getContext('2d');
    const pedidosPorEstado = new Chart(ctxEstado, {
      type: 'doughnut',
      data: {
        labels: <?php echo $estadosPedidosJSON ?: '[]'; ?>,
        datasets: [{
          data: <?php echo $cantidadPorEstadoJSON ?: '[]'; ?>,
          backgroundColor: [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)'
          ],
          borderColor: [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
            labels: {
              color: 'white'
            }
          }
        }
      }
    });
  </script>

</body>
</html>