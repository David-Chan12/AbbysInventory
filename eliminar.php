<?php
session_start();
    require_once 'conexion.php'; // Conexión a la base de datos
        //DELETE FROM insumo WHERE id = $id
        $id = $_GET['id'];
    $sql = "DELETE FROM insumo WHERE id = '".$id."'";
    $resultado = mysqli_query($conn,$sql);

    if($resultado){
        echo "<script language='JavaScript'>
                alert('¡¡Los datos se han eliminado correctamente!!');
                location.assign('Ginsumos.php');
                </script>
            ";
    }else{
        echo "<script language='JavaScript'>
                alert('¡¡ERROR!! Los datos no se pudieron eliminar');
                location.assign('/Ginsumos.php');
                </script>
            ";
    }
?>