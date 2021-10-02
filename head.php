<?php
include_once 'inicial.php';
?>
<meta name="language" content="es">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Buscador EAN13-<?php echo $thisTpv->getNombreFichero($_SERVER['REQUEST_URI']) ?></title>
<link href="<?php echo $HostNombre;?>/css/img/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon" />
<link rel="stylesheet" href="<?php echo $HostNombre;?>/css/bootstrap.min.css" type="text/css">
<link rel="stylesheet" href="<?php echo $HostNombre;?>/css/template.css" type="text/css">

<script src="<?php echo $HostNombre;?>/jquery/jquery-2.2.5-pre.min.js"></script>
<script src="<?php echo $HostNombre;?>/css/bootstrap.min.js"></script>
<?php
	
	if ($_SESSION['estadoSession'] != "Correcto"){
		// Controlamos si no hay session correcta mostramos formulario de acceso.
		echo 'No hay session';
		if ($thisTpv->debug === 'OK'){
			echo '<pre>';
            echo 'Estoy en head<br/>';
			print_r($_SESSION);
			echo '</pre>';

		}
	}

?>
