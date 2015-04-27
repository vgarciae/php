<?

class User{

	$nombre=null;
	$username=null;
	$password=null;
	$prile_slug=null;

	public function User(){
		$this->nombre="Usuario";
		$this->username="user";
		$this->password="cursophp";
		$this->prile_slug="user_default";
	}

	public function loadProfile(){
	}

	public function loadData(){
	}

}

?>