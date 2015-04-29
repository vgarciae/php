<?php
// YA LO REVISE
function sec_session_start(){
	$session_name='sec_session_id';
	$secure=false;
	
	if(ini_set('session.use_only_cookies',1)===FALSE){
		header("Location: ../login.php?err=No puedo iniciar una sesion segura");
		exit();
	}
	$cookieParams=session_get_cookie_params();
	session_set_cookie_params($cookieParams['lifetime'],$cookieParams['path'],$cookieParams['domain'],$secure,true);

	session_name($session_name);
	session_start();
	session_regenerate_id();
}
function login($email,$password,$mysqli){
	if($stmt=$mysqli->prepare("select id,username,password,salt from members where email=? LIMIT 1")){
		$stmt->bind_param('s',$email);
		$stmt->execute();
		$stmt->store_result();
		
		$stmt->bind_result($user_id,$username,$password_bd,$salt);
		$stmt->fetch();
		
		$password=hash('sha512',$password.$salt);
		
		if($stmt->num_rows==1){
			
			if(check_brute($user_id,$mysqli)==TRUE){
				set_message('warning','INTENTOS');
				header("Location: ../login.php");
				exit();		
			}			
			else{
				if($password_bd==$password){
					$user_agent_browser=$_SERVER['HTTP_USER_AGENT'];					
					// La idea es que no se repitan
					$user_id=preg_replace("/^[0-9]+/","",$user_id);
					$_SESSION['user_id']=$user_id;
					// El filtrado es para sanear los datos que voy a guardar en mi arreglo
					$username=preg_replace("/[^a-zA-Z0-9_\-]+/","",$username);				
					$_SESSION['username']=$username;
					return true;
				}
				else{
					$now=time();
					// Extrapolación de variables, en el momento que lo encuentra lo sustituye
					if(!$mysqli->query("INSERT INTO login_attempts(user_id,time) values ('$user_id','$now')")){
						set_message('warning','Demasiados intentos de inicio de sesión, por favor intente más tarde.');
						header("Location: ../login.php");
						exit();
					}
					return false;
				}
			}
		}
		else{
			return false;
		}
	}
	else{
		set_message('warning','Error DB: no puede lanzar statement.');
		header("Location: ../login.php");
		exit();
	}
}
			
function check_brute($user_id,$mysqli){
	$now=time();
	$intentos=$now-(2*60*60);
	if($stmt=$mysqli->prepare("select time from login_attempts where user_id=? and time < '$intentos'")){
	//if($stmt=$mysqli->prepare("select time from login_attempts where user_id=1")){
		$stmt->bind_param('i',$user_id);
		$stmt->execute();
		$stmt->store_result();
		if($stmt->num_rows>10){	
			return TRUE;			
		}
		else{
			return false;
		}
	}
	else{		
		set_message('warning','Error DB: no puede lanzar statement.');
		header("Location: ../login.php");
		exit();
	}
}

function login_check($mysqli){
	if(isset($_SESSION['user_id'],$_SESSION['username'],$_SESSION['login_string'])){
		$user_id=$_SESSION['user_id'];
		$login_string=$_SESSION['login_string'];
		$username=$_SESSION['username'];
		
		$user_agent=$_SERVER['HTTP_USER_AGENT'];
		
		if($stmt=$mysqli->prepare("SELECT password FROM members WHERE id=? LIMIT 1")){
			
			$stmt->bind_param('i',$user_id);
			$stmt->execute();
			$stmt->store_result();
			
			if($stmt->num_rows==1){
				$stmt->bind_result($password);
				$stmt->fetch();
				$login_check=hash('sha512',$password.$user_agent);
				
				if($login_check==$login_string){
					return true;
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}
		else{
			header("Location: ../login.php?err=Error DB: no puedo lanzar statement");
			exit();
		}
	}
	else{
		// Usuario no logeado
		return false;
	}
}

function esc_url($url){
	if(''==$url){
		// saneamos la url
		$url=preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i','',$url);
		// La idea es que no tengamos este tipo de entidades
		$strip=array('%0d','$0a','%0D','%0A');
		
		$url=(string)$url;
		$cont=1;
		// regresa un valor positivo o negativo, 
		while($cont){
			$url=str_replace($strip,'',$url,$cont);
		}
		// reemplazo directo
		$url=str_replace(';//','://',$url);
		$url=htmlentities($url);
		$url=str_replace('&amp;','&#038;',$url);
		$url=str_replace("'",'&#039;',$url);
		
		if($url[0]!='/'){
			return '';
		}
		else{
			return $url;
		}
	}
}
function set_message($type='warning',$message){
	$_SESSION['message']=array('type'=>$type,'message'=>$message);
}
function get_message(){
	if(isset($_SESSION['message'])){
		$msg=sprintf("<div class='alert alert-%s' role='alert'>%s</div>",$_SESSION['message']['type'],$_SESSION['message']['message']);		
		unset($_SESSION['message']);
		return $msg;
	}		
	else{
		return '';
	}
}
function register_user($mysqli,$username,$email,$password){
	$msg=''; 
	$username=filter_input(INPUT_POST,'username',FILTER_SANITIZE_STRING);
	$email=filter_input(INPUT_POST,'email',FILTER_SANITIZE_EMAIL);		
	$email=filter_var($email,FILTER_VALIDATE_EMAIL);
	//$password=filter_input(INPUT_POST,'password',FILTER_SANITIZE_STRING);
	if($email == FALSE){
		$msg.='<p>La dirección de correo no es válida, por favor revisa los datos.</p>';
		set_message('danger',$msg);
		header("Location: ../login.php");
		exit();
	}
	if(strlen($password) != 128){
		$msg.='<p>Password no válido.</p>';
	}
	$pre_stmt="SELECT id FROM members WHERE email=? LIMIT 1";	
	$stmt = $mysqli->prepare($pre_stmt);
	
	if($stmt){
		$stmt->bind_param('s',$email);
		$stmt->execute();
		$stmt->store_result();
		if($stmt->num_rows == 1){			
			$msg.='<p>Ya existe un usuario con esta dirección de correo.</p>';		
		}
	}
	else{
		$msg.='<p>Error de Base de Datos</p>';
	}
	if(empty($msg)){										   
		$random_salt= hash('sha512',uniqid(openssl_random_pseudo_bytes(16),true));
		$password = hash('sha512',$password.$random_salt);
		if($insert_stmt = $mysqli->prepare("INSERT INTO members(username,email,password,salt) VALUES (?,?,?,?) ")){
			$insert_stmt->bind_param('ssss',$username,$email,$password,$random_salt);
			if(!$insert_stmt->execute()){
				$msg.='<p>No se pudo introducir el registro en la BD </p>';
				set_message('danger',$msg);
				header("Location: ../login.php");
				exit();				
			}			
		}
		$msg.="<p>Usuario registrado!</p>";
		set_message('success',$msg);
		header("Location: ../login.php");
		exit();				
	}
	else{
		set_message('danger',$msg);
		header("Location: ../login.php");
		exit();
	}
}
?>