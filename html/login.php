<?php
	require_once("config.php");
	require_once(CUR_LIB_PATH."db.php");
	require_once(CUR_LIB_PATH."helper.php");
	require_once(CUR_LIB_PATH."template.php");

	if(login_check($mysqli)==TRUE){
		$logged='in';
	}
	else{
		$logged='out';
	}

	if(isset($_POST['email'],$_POST['password'])){
		sec_session_start();
		$email=filter_input(INPUT_POST,'email',FILTER_SANITIZE_EMAIL);
		$password=$_POST['password'];
		if(login($email,$password,$mysqli)){
			header("Location: index.php");			
			exit();
		}
		else{
			header("Location: login.php?err=1");
			exit();
		}
	}
	else{	
		$tmpl=new Template("login");
		$tmpl->set('title',getPageName('Inicio de sesión'));
		$tmpl->set('wpath',getWebPath());
			
		if(isset($_GET['err'])){
			$tmpl->set('err',$_GET['err']);		
		}
		
		echo $tmpl->render();
	}
?>