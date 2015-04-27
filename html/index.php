<?php

/**
* Comentario
*/

echo "Hola, bienvenido al curso!";

class App{

	$url=null;
	$filePath=null;

	public function App(){
		$this->url="";
		$this->path="";
	}	
	public function loadConfig(){
		require_once('config.php');
	}
	public function getUrl(){
		return $this->url;
	}
	public function getPath(){
		return $this->path;
	}
	public function setUrl($url=null){
		$this->url=$url;
	}
	public function setPath($path=null){
		$this->path=$path;
	}

}

$app=new App();

?>
