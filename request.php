<?php
function ObtenerHtmlIngredientesOpenFood($i){
    // @ Objetivo :
    // Formatear el array de ingredientes, para poder mostrarlos.
    // Problema con los (( ya que son grupos... y no se como hacerlo )
    
        $ingr = implode($i);
        $array =  explode(",", $ingr);
        foreach ($array as $k=>$a){
            // Si contiene _ es negrita.. sustituimos <b>
            $array[$k] = str_replace('_','',$a);
        }
        return $array;

}

function NameBrandsOpenFood($p){
    // @ Objetivo:
    // Es obtener el titulo del nombre que es name y brands , pero en princio obtengo el _es sino el que viene por defecto.
    if (is_null($p['product_name_es'] )){
        $nombre = $p['product_name'];
    } else  {
        $nombre = $p['product_name_es'];
    }
   return $nombre.'-'.$p['brands'];
    
}


function ComprobarBarCode($code){
  // Comprobamos que tenga 13 digitos, ya que es sistema que vamos utilizar.
    $respuesta = 'Ok';
    if (strlen($code) == 13){
        // Ahora comprobamos que no empiece con  20 a 29 y tampos 97 a 99
        $inicio = mb_substr($code,0,2);
        if (ComprobarInicioBarCode($inicio) == true){
            // Ahora deberíamos comprobar el digito de control.
            $barcodeSinDG = mb_substr($code,0,12);
            //~ echo $barcodeSinDG;
        } else {
            $respuesta = 'El codigo de barras empieza por 2 o 97,98,99 que no son validos.';
        }

    } else {
        $respuesta = 'No tiene 13 digitos, no es correcto para nuestro sistema.';
    }
    return $respuesta;


}

function ComprobarInicioBarCode($i){
    $i = (int)$i;
    $respuesta = true;
    if ($i >=20 && $i<=29){
        $respuesta = false;
    }
    if ($i >=97 && $i<=99){
        $respuesta = false;
    }
    return $respuesta;
}





// Default parameters by case
$country = 'es'; // Country by using OFF
$productSlug = 'productos'; // Product by language (producto in spanish or product in english)

// Format URL
$url = 'https://{country}.openfoodfacts.org/api/v0/{product}/{scan}.json';


// Lo primero que deberíamos hacer es validar el codigo ean.
// ya que :
// - Si empieza por 20 a 29 son utilizados como codigo barras propios.
// - Si empieza por 977 en adelante tampoco ya que son publicaciones, libros o cupones.
// Otra cosa deberíamos comprobar el digito de control si es correcto, ya que si no lo es, esta mal codbarras, no debemos hacer consulta.
// Mas info en: https://es.wikipedia.org/wiki/Anexo:Prefijos_de_C%C3%B3digo_GS1_por_pa%C3%ADses
$comprobación = ComprobarBarCode($_GET['ean13']);
if ($comprobación !== 'Ok' ){
    echo '<pre>';
    echo $comprobación;
    echo '</pre>';
    exit;
}
// Where we will set the value of the scan
$barcode = (int) $_GET['ean13'];




$ruta = str_replace(['{country}','{product}','{scan}'],[$country,$productSlug,$barcode],$url);

$ch = curl_init($ruta);
curl_setopt($ch, CURLOPT_URL, $ruta); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
curl_setopt($ch, CURLOPT_HEADER, 0); 
$resultado = curl_exec($ch);

$p = json_decode($resultado,true);

//o el error, por si falla
$error = curl_error($ch);
if ($p['status'] === 0) {
    $error = $p['status_verbose'];
}
if ($error !='' || $p['status'] === 0){
echo '<pre>';
print_r($error);
echo '</pre>';
exit;
}


$producto =$p['product'];
$ingredientes = $producto['ingredients_text'];
//~ $ingredientes =ObtenerHtmlIngredientesOpenFood($i);

// Obtener titulo del producto.
$titulo = NameBrandsOpenFood($producto);

$nutrientes = $producto['nutriments'];
if (is_null($producto['nutriscore_grade'])){
    $imagen_nutriscore='nutriscore-unknown.svg';
} else {
    $imagen_nutriscore= 'https://world.openfoodfacts.org/images/misc/nutriscore-'.$producto['nutriscore_grade'].'.svg';
}

echo '<h1>'.$titulo.'</h1>';
echo '<a href="https://es.openfoodfacts.org/producto/'.$barcode.'" target="_blank" >Link a Open Food Facts</a>';
 echo '<pre>';
 print_r($ingredientes);
 print_r($nutrientes);

 echo '</pre>';
 echo '<img src="'.$imagen_nutriscore.'">';
 echo '<pre>';
 print_r($producto);
 echo '</pre>';
$info=curl_getinfo($ch);
//y finalmente cerramos curl
curl_close ($ch);
