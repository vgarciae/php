<?php
require_once("config.php");

function getPageName($page=null){
return CUR_NAME."-".$page;
}
function getWebPath(){
return CUR_WEBPATH;
}
class Template{
	protected $_file;
	protected $_data=array();
	
	public function __construct($file=null){
		$this->_file=$file;
	}
	public function set($key,$value){
		$this->_data[$key]=$value;
		return $this;
	}
	public function render(){
		// Interpolación de datos
		extract($this->_data);
		// Revisar el inicio de la plantilla (las cabeceras)
		// Para evitar escribir los mismo encabezados (por ejemplo)
		ob_start();
		include($this->_file);
		return ob_get_clean();
	}
}

?>