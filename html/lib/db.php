<?php
//require_once('../config.php');
$mysqli=new mysqli(CUR_HOST,CUR_USR,CUR_PWD,CUR_DB);

if($mysqli->conect_error){
	header("Location: ../login.php?err=No hay conexion a BD");
	exit();
}

?>
