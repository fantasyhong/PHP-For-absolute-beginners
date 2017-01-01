

<!--<?php
if($_SERVER['REQUEST_METHOD']=='POST'){
$dbinfo='mysql:host=localhost;dbname=test';
$user='root';
$pass='';
$link=new PDO($dbinfo,$user,$pass);

$sql="SELECT album_name FROM albums WHERE artist_id=?";
$stmt=$link->prepare($sql);
	if($stmt->execute(array($_POST['artist']))){
	
	while($row=$stmt->fetch()){
		printf("Album: %s<br />",$row['album_name']);
	}
	$stmt->closeCursor();
}

}
else{
?>
<form  method="post">
 		<label for="artist">Select an Artist:</label>
 		<select name="artist">
 		 <option value="1">Bon Iver</option>
 		 <option value="2">Feist</option>
 		 </select>
 		<input type="submit"  />
 	</form>	
<?php }?>
-->

<?php 

$tom=new ToyRobot("Tom");

$tom->writeName();

$jim=new ToyRobot("Jim");

$jim->writeName();

class ToyRobot
{
	private $_name;
	
	public function __construct($name){
		$this->_name=$name;
	}
	
	public function writeName(){
		echo 'My name is ',$this->_name,'.<br />';
	}
}
?>