<?php
	/*
	 *  @Aplicacion    Ricardo Lau
	 *  @license    GNU General Public License version 2 or later; see LICENSE.txt
	 * */
?>

<!DOCTYPE html>
<html>
<head>
<?php
	include_once 'head.php';?>
</head>
<body>
	
<div class="container">
    <div class="row">
        <h2 class="text-center">Buscar codigos barras EAN13</h2>
        <div class="col-md-6">
            <form class="row g-3" action="./request.php" method="get">
                <div class="form-group">
                    <input type="text" placeholder="barcode EAN13" name="ean13">
                </div>
                <div class="col-md-12"> 
                <button class="btn btn-primary" type="submit" name="Ok"> Busca codigo barras en OpenFoods</button>
                </div>
            </form>
        </div>
        <div class="col-md-6">
        <?php
        if (!isset($thisTpv->usuario)){
        // Si no existe usuario , ponemos formulario de usuario.
        ?>
            <p>Si eres usuario registrado puede realizar cosas como :</p>
            <ul>
                <li>Añadir nuevos codigos barras y sus caracteristicas.</li>
            </ul>
            <form action="" method="post" name="form">
            <div class="form-group">
                <label for="usr">Usuario:</label>
                <input type="text" class="form-control" id="usr" name="usr" required>
            </div>
            <div class="form-group">
                <label for="pwd">Contraseña:</label>
                <input type="password" class="form-control" id="pwd" name="pwd" required>
            </div> 
            <input type="submit" value="Aceptar">
            </form>
        <?php
        } else {
            // Pintamos nombre usuario.
            echo 'usuario:';
        }
        ?>
        </div>
    </div>
</div>
</body>
</html>
