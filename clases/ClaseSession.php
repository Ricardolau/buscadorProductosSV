<?php


/* Propiedades en minuscula.
 * Metodos en UpperCamelCase
 * */
$rutaCompleta = $RutaServidor.$HostNombre;
include ($rutaCompleta.'/clases/ClaseConexion.php');
class ClaseSession extends ClaseConexion{
	public $BDTpv ; 					// (object) Conexion a BD tpv.
	private $session ;					// (array) Datos de $_SESSION actuales.
	public $Usuario =array(); 					// (array) Contiene array con los datos del Usuario de la session actual
    public $SessionesAll;               // Array con los datos de todas las sessiones abiertas.
    public $comprobaciones = array(); 	// (array) Errores o advertencias.
    public $tokenapp;                   // Generamos un token para esta aplicacion
	public function __construct()
	{
		parent::__construct();
		$this->BDTpv	= parent::getConexion();
        if (session_status() !== PHP_SESSION_ACTIVE) {
			// Hay que tener en cuenta que la session no tenemos porque iniciar nosotros, 
			// otra api del servidor la puede abrir.
            // Si la reiniciamos, mantiene variables $_SESSION
			session_start();
		} 
		$this->sessionSV(); 
	}
	
	public function GetSession(){
		// Objetivo devolver la session
		return $this->session;
	}
	
	public function GetComprobaciones(){	
		// Obtener las comprobaciones, errores, advertencias o informacion
		return $this->comprobaciones;
	}
	
	public function sessionSV(){
		// @ Objetivo :
		// Crear, comprobar, advertir , registrar y incluso destruir:
        //   - Revisar y crear advertencias.
        //   - Si su estadoSession es OK, de lo contrario se destruye y reinicia.
        //   - 
        //   - Crear , modificar , registrar si fuera nueva o ya existiera.
        $accion = array();
        $control = $this->controlArraySession();
        $suma_control = $this->sumarControl($control) ;
        if ($suma_control === 0) {
            // Es nuevo
            // Ahora comprobamos si viene formulario registro con tokenApp, entonces
            // comprobamos si el usuario tiene una session abierta.
            if (isset($_POST[''])){
                $accion[] = 'Debería comprobar POST y actualizar registro si fuera necesario.';

            } else {
                // Entonces creamos nuevo.
                $this->crearArraySession();
                $accion[] = 'Cree nueva y registre nueva session';
            }
        } else {
            // Existe algun dato en SESSION.
            if (isset($_SESSION['tokenSV']) && $_SESSION['tokenSV'] === $this->getTokenApp()){
                // Sabemos que los datos que exisen SESSION son esta aplicacion.
                // Ahora comprobamos que no esta apunto de caducar session ( una hora)... jeje
                $ahora = new DateTime('NOW');
                $max_fecha_fin = $ahora->modify('+1 hours');
                if ($_SESSION['fechafin'] < $max_fecha_fin->format("Y-m-d H:i:s")){
                    // Esta apunto de cumplir 8 horas una session , por lo que debemos reiniciar session.
                    // El sistema por defecto 180 minuto.
                    $accion[] = 'Apunto de caducar'; // Debo reiniciar id session';
                }
            }
        }
        $this->session = $_SESSION;
    
	}
    public function insertSession(){
        // Obtenemos ip
        $ip_cliente = $this->GetClienteIp();
        $BDTpv = $this->BDTpv;
        $date =new DateTime('NOW');
        $date->modify('+8 hours');
        
        $sql = 'INSERT INTO `sessiones`(`idsession`,ipcliente,idusuario, estadosession,`fechainicio`, `fechafin`) VALUES ("'
                .$_SESSION['idsession'].'","'.$ip_cliente.'","0","'.$_SESSION['estadosession']
                .'", NOW(),"'.$date->format("Y-m-d H:i:s").'")';
        $res = $BDTpv->query($sql);
        if (mysqli_error($BDTpv)){
			$this->SetComprobaciones(array('tipo'=>'danger',
											 'mensaje' => 'Error en la consulta de usuario:'.$sql,
											 'dato'	  => $BDTpv->error_list
											)
										);
            $respuesta['error'] = $this->comprobaciones;
        } else {
            $respuesta['affected_rows']  =$BDTpv->affected_rows; // devolvemos cuantos fueron afectados
            $respuesta['insert_id'] =$BDTpv->insert_id;
        }
        return $respuesta;
    }
   

	
	public function comprobarUser($usuario,$pwd){
		// Objetivo
		// Comprobar que los datos metidos en el formulario acceso son correctos.
        echo '<pre>';
        echo 'Estoy metodo comprobarUser.<br/>';
        print_r($_POST);
        echo '</pre>';
		$BDTpv = $this->BDTpv;
		$encriptada = md5($pwd);// Encriptamos contraseña puesta en formulario.
		$sql = 'SELECT password,nombre,id,group_id FROM usuarios WHERE username="'.$usuario.'"';
		$res = $BDTpv->query($sql);
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$this->SetComprobaciones(array('tipo'=>'danger',
											 'mensaje' => 'Error en la consulta de usuario:'.$sql,
											 'dato'	  => $BDTpv->error_list
											)
										);
			$_SESSION['estadoSession']= 'ErrorConsulta';
		} else {
			$pwdBD = $res->fetch_assoc();
			$pwdBD['login'] = $usuario; 	
			if ($encriptada === $pwdBD['password']){
				// Quiere decir que usuario y password son correcto.
				
            } else {
				$_SESSION['estadoSession']= 'Error';
				$this->SetComprobaciones(array('tipo'=>'warning',
											 'mensaje' => 'Usuario o contraseña incorrecta',
											 'dato'	  => ''
											)
										);
			}
		}
		return ;
	 } 
	 
	public function controlArraySession(){
		// Objetivo:
		// Esto se ejecuta siempre para comprobar que si $_SESSION tiene:
        //   - fechafin, fechamodify y fechainicio
        //   - comprueba que fechafin no este a menos de una hora actual.
		// No debemos hacer consultas, ni cambios en valor en esos parametros, solo registrar su estado:
        // 0 ->  No existe
        // 1 ->  Existe
        // 2 ->  Correcto

        $c = array( 'token' => 0, 'idsession'=> 0, 'fechas' => 0, 'idusuario' => 0, 'estadosession'=> 0); // inicializo variable respuesta.

        if (isset($_SESSION['tokenSV'])){
            $c['token'] = 1;
            if ($_SESSION['tokenSV'] == $this->getTokenApp()){
                $c['token'] = 2;
            }
        }       
        if (isset($_SESSION['idsession'])){
            // Debo crearlo si session_id() tiene valor
            $c['token'] = 1;
            if ($_SESSION['idsession'] !== '' && $_SESSION['idsession'] !== session_id()){
                $c['idsession'] = 2; // Ponemos correcta, ya que existe y es el mismo session_id()
            }
        }
        if (isset($_SESSION['fechainicio']) && isset($_SESSION['fechamodify']) && isset($_SESSION['fechafin']) ){
            $ahora =new DateTime('NOW');
            $max_fecha_fin = $ahora->modify('+1 hours');
            if ($_SESSION['fechafin'] < $max_fecha_fin->format("Y-m-d H:i:s")){
                $c['fechas'] = 1; // Existe , pero no es correcta.
                
                // Esta apunto de cumplir las 8 horas una session , por lo que debemos informar.
                // El sistema por defecto pone duración de session 180 minuto.
                // Incluso podríamos forzar a loguearse de nuevo.
            } else {
                 $c['fechas'] = 2; // Ponemos correcta, pero ahora comprobamos.
            }
        }
        if (isset($_SESSION['idusuario'])){
            $c['idusuario'] = 1;
        }
        if (isset($_SESSION['estadosession'])) {
            $c['estadosession'] = 1;
            if ($_SESSION['estadosession'] ==='OK') {
                $c['estadosession'] = 2;
            }
        }
    return $c;
	}

    public function getTokenApp(){
        // Objetivo:
        // Creamos un token para aplicacion, este toque es la ruta completa, extraño que pueda volver repetir en otro sitio.
        $token = hash('sha256', $rutaCompleta);
        return $token;
    }

    public function crearArraySession($datos = array()){
        // @ Objetivo:
        // Crear array de la variable $_SESSION
        // @ Parametros
        //  Puede traer un array con los datos, ya validados.
        if (session_id() !== ''){
            // Si existe session continuamos ,si no tiene sentido.
            if ( count($datos) === 0){
                // Datos que me faltan:
                $ipcliente = $this->GetClienteIp();
                $ahora =new DateTime('NOW');
                $fechaini = $ahora->format("Y-m-d H:i:s");
                $max_fecha_fin = $ahora->modify('+8 hours');
                // Guardamos en session.
                $_SESSION['tokenSV']        = $this->getTokenApp();
                $_SESSION['idsession']      = session_id();
                $_SESSION['estadosession']  = 'OK';
                $_SESSION['fechaini']       = $fechaini;
                $_SESSION['fechafin']       = $max_fecha_fin->format("Y-m-d H:i:s");
                $insertar_registro = $this->insertSession();
                echo '<pre>';
                echo ' Insertamos nuevo registro:<br/>';
                print_r($insertar_registro);
                echo '</pre>';
                // No creamos , fecha de modify , ni usuario.. ya que no tiene sentido.
            } else {
                // Por logica si tiene datos existe registro.
                

            }
        }
    }


    public function obtenerTodasSessiones(){
        // @ Objetivo:
        // Obtener array con todas las sessiones que tiene php en el servidor.
        // fuente: https://www.it-swarm-es.com/es/php/recorriendo-todas-las-sesiones-de-un-servidor-en-php/957837477/
        // pero el scandir de path de session no funciona ,ya que no suele tener permisos el usuario para leer el directorio,
        // entonces obtenermos las sessiones que tenemos abiertas , con la consulta base datos.
        $BDTpv = $this->BDTpv;
        $sql = 'SELECT * FROM sessiones ';
        $smt = $BDTpv->query($sql);
		//compruebo error en consulta
		if (mysqli_error($BDTpv)){
			$this->SetComprobaciones(array('tipo'=>'danger',
											 'mensaje' => 'Error en la consulta de sessiones:'.$sql,
											 'dato'	  => $BDTpv->error_list
											)
										);
			$_SESSION['estadoSession']= 'ErrorConsulta';
		} else {
            
            while ($sessionesName = $smt->fetch_assoc()){
                echo '<pre>';
                echo 'Estoy en metodo obtenerTodasSessiones.<br/>';
                print_r($sessionName['idsession']);
                echo '</pre>';
                session_id($sessionName['idsession']);
                session_start();
                $allSessions[] = $_SESSION;
                session_destroy();
            }

        }

    return $allSessions;
    }


    public function buscarSession($id_session= false){
        // @ Objetivo :
        // Buscar si la session
        // @ Parametros .
        // id_session -> No tengo porque pasarlo , si no lo paso , lo busco, sino busco el actual.
        // @ Devuelvo
        // Array con datos de Registro  o num_rows ( si es 0 ó mas 1 ) o error de consulta, no session abierta.

        if ($id_session === false){
            $id_session = session_id();
        }
        if ($id_session === ''){
            // Error , no esta activa la session.
            $this->SetComprobaciones(array( 'tipo'=>'danger',
                                                'mensaje' => 'Error la session no esta activa.',
                                                'dato'	  => 'La funcion session_id() viene como string vacia'
                                                )
                                        );
            $datos_session = array('Error' =>'Error la session no esta activa.');
        } else {
            $BDTpv = $this->BDTpv;
            $sql = 'SELECT * FROM sessiones WHERE idsession="'.$id_session.'"';
            $smt = $BDTpv->query($sql);
            if (isset($smt->num_rows)) {
                if ($smt->num_rows == 1) {
                    $datos_session = $smt->fetch_assoc();
                } else {
                    // O no hay session o hay mas de una.
                    $this->SetComprobaciones(array( 'tipo'=>'warning',
                                                    'mensaje' => 'Error en la cantidad registros encontrado para la consulta:'.$sql,
                                                    'dato'	  =>'Registros encontrados:'.$smt->num_rows
											)
										);
                    $datos_session = array('num_rows' =>'Registros encontrados:'.$smt->num_rows);
                }
            } else {
                $this->SetComprobaciones(array( 'tipo'=>'danger',
                                                'mensaje' => 'Error en la consulta de sessiones:'.$sql,
                                                'dato'	  => $BDTpv->error_list
                                                )
                                        );
                $datos_session = array('Error' =>'Error en consulta de session:'.$BDTpv->error_list);
            }
        }
        return $datos_session;  


    }
	
	public function cerrarSession(){
		session_unset();
		session_destroy();
	}

    // --------------------   METODOS QUE PODRÍAN SER ESTANDARES  ---------------------------------  //

    public function sumarControl($c){
        // @ Objetivo
        // Revimos un array con valores numericos y lo sumamos.
        // devolviendo la suma.
        $suma = 0;
        foreach ($c as $v){
            $suma = $suma + $v;
        }
        return $suma;
    }

     public function GetClienteIp() {
        // Obtenemos la ip  para registrarlo en registro session.
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
           $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function SetComprobaciones($error){
		// Objetivo 
		// Añadir al array una comprobacion advertencias y errores
		if (gettype($error) === 'array'){
			// Es un array , ahora deberíamos comprobar que el tipo es corecto...:-)
			// De momento no lo hago..
			array_push($this->comprobaciones,$error);
		}
	}
	 
}
?>
