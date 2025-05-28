<?php
session_start();
require_once 'conexion.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: productos.php");
    exit();
}

$producto_id = intval($_GET['id']);

// Obtener información del producto
$producto_query = "SELECT p.*, c.nombre as categoria_nombre, e.descripcion as estado_descripcion 
                  FROM producto p 
                  LEFT JOIN categoria c ON p.categoria_id = c.id
                  LEFT JOIN estadopro e ON p.estado = e.id
                  WHERE p.id = $producto_id";
$producto_result = mysqli_query($conn, $producto_query);

if (!$producto_result || mysqli_num_rows($producto_result) == 0) {
    header("Location: productos.php");
    exit();
}

$producto = mysqli_fetch_assoc($producto_result);

// Obtener insumos del producto
$insumos_query = "SELECT i.*, pi.cantidad as cantidad_necesaria 
                 FROM insumo i 
                 JOIN producto_insumo pi ON i.id = pi.insumo_id 
                 WHERE pi.producto_id = $producto_id";
$insumos_result = mysqli_query($conn, $insumos_query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/Ginsumos.css">
    <title>Insumos del Producto - Abby Cookies & Cakes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .main-panel {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        
        .report-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .product-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .product-info h3 {
            margin-top: 0;
            color: #f8a5c2;
        }
        
        .product-info p {
            margin: 5px 0;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        th {
            background-color: #f8a5c2;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .btn-back {
            background-color: #f8a5c2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        .btn-back:hover {
            background-color: #e58aa7;
        }
        
        .btn-edit {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        
        .btn-edit:hover {
            background-color: #388e3c;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-unavailable {
            background-color: #ffebee;
            color: #c62828;
        }
    </style>
</head>
<body>
    <!-- sidebar -->
    <div id="sidebar-placeholder"></div>

    <script>
        fetch('sidebar_i.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('sidebar-placeholder').innerHTML = data;
            });
    </script>
    
    <div class="panel-background">
        <div class="main-panel">
            <header class="menu-header">
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
                    <a href="productos.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
                </div>
            </header>

            <h1 class="page-title">Insumos del Producto</h1>
            
            <div class="report-container">
                <div class="product-info">
                    <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                    <p><strong>Categoría:</strong> <?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría') ?></p>
                    <p><strong>Precio:</strong> $<?= htmlspecialchars($producto['precio']) ?></p>
                    <p><strong>Estado:</strong> 
                        <span class="status-badge <?= $producto['estado'] == 1 ? 'status-available' : 'status-unavailable' ?>">
                            <?= htmlspecialchars($producto['estado_descripcion'] ?? ($producto['estado'] == 1 ? 'DISPONIBLE' : 'NO DISPONIBLE')) ?>
                        </span>
                    </p>
                    <p><strong>Descripción:</strong> <?= htmlspecialchars($producto['descripcion']) ?></p>
                </div>
                
                <h2 class="section-title">INSUMOS NECESARIOS</h2>
                
                <?php if (mysqli_num_rows($insumos_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Cantidad Necesaria</th>
                                <th>Unidad de Medida</th>
                                <th>Disponible</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($insumo = mysqli_fetch_assoc($insumos_result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($insumo['nombre']) ?></td>
                                    <td><?= htmlspecialchars($insumo['cantidad_necesaria']) ?></td>
                                    <td><?= htmlspecialchars($insumo['unidad_medida']) ?></td>
                                    <td><?= htmlspecialchars($insumo['cantidad_disponible']) ?></td>
                                    <td>
                                        <?php if ($insumo['cantidad_disponible'] >= $insumo['cantidad_necesaria']): ?>
                                            <span class="status-badge status-available">Suficiente</span>
                                        <?php else: ?>
                                            <span class="status-badge status-unavailable">Insuficiente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Este producto no tiene insumos registrados.</p>
                <?php endif; ?>
                
                <a href="productos.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver a Productos
                </a>
                
                <a href="editar_insumos_producto.php?id=<?= $producto_id ?>" class="btn-edit">
                    <i class="fas fa-edit"></i> Editar Insumos
                </a>
            </div>
        </div>
    </div>
</body>
</html>