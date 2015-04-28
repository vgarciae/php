<?php
/**
* Comentario
*/
require_once('lib/template.php');
echo "Hola, bienvenido al curso!";

class App{

	public $url=null;
	public $filePath=null;

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
$tmpl=new Template("default");

$tmpl->set('title',getPageName('Dashboard'));
$tmpl->set('wpath',getWebPath());
$tmpl->set('box1','Alta de usuarios');
$tmpl->set('box2','Baja de usuarios');
$tmpl->set('box3','Configuración');

echo $tmpl->render();
?>

