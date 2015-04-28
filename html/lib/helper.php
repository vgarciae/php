<?php
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
		
		$password=hash('sha512',$password_bd.$salt);
		if($stmt->num_rows==1){
			//if(check_brute($user_id,$mysqli)==TRUE){
				//return false;				
			//}
			//else{
				if($password_bd==$password){
					$user_agent_browser=$_SERVER['HTTP_USER_AGENT'];					
					// La idea es que no se repitan
					$user_id=preg_replace("/^[0-9]+/","",$user_id);
					$_SESSION['user_id']=$user_id;
					// El filtrado es para sanear los datos que voy a guardar en mi arreglo
					$username=preg_replace("/[^a-zA-Z0-9_\-]","",$username);				
					$_SESSION['username']=$username;
					return true;
				}
				else{
					$now=time();
					// Extrapolación de variables, en el momento que lo encuentra lo sustituye
					if(!$mysqli->query("INSERT INTO login_attempts(user_id,time) values ('$user_id','$now')")){
						header("Location: ../login.php?err=Error de base de datos: intentos_de_login");
						exit();
					}
					return false;
				}
			//}
		}
		else{
			return false;
		}
	}
	else{
		header("Location: ../login.php?err=Error DB: no puede lanzar statement");
		exit();
	}
}
			
function check_brute($user_id,$mysqli){
	$now=time();
	$intentos=$now() - (2*60*60);
	if($stmt=$mysqli->prepare("select time from login_attemps where user_id=? and time>'$intentos'")){
		$stmt->bind_param('i',$user_id);
		$stmt->execute();
		$stmt->store_result();
		if($stmt->num_rows>5){
			return true;			
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
?>