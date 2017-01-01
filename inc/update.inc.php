<?php

include_once 'functions.inc.php';

include_once 'images.inc.php';

if($_SERVER['REQUEST_METHOD']=='POST'
		&&$_POST['submit']=='Save Entry'
		&&!empty($_POST['title'])
		&&!empty($_POST['page'])
		&&!empty($_POST['entry']))
{
	//create a url based on the entry
	$url=makekUrl($_POST['title']);
	
	
	//save image
	if($_FILES['image']['tmp_name']){
		try{
			
			//instatiate the class and set a save path
			$img=new ImageHandler("/simple_blog/images/");
			
			//process the file and store the returned path
			$img_path=$img->processUploadedImage($_FILES['image']);
			
			
		}
		catch(Exception $e){
			//output error message 
			die($e->getMessage());
		}
	}
	else{
		//if no image is uploaded
		$img_path=null;
	}
	//Output the saved image path
	//echo "Image path: ",$img_path,"<br />";
	//exit;
	
	//make connection to the sql server
	include_once 'db.inc.php';
	$db=new PDO(DB_INFO,DB_USER,DB_PASS);
	
	/*
	 * Reason why !empty() is used instead of isset(): 
	 * At this point, the connection is made with the database, so we need to check 
	 * if the id exists in the database, and !empty() does exactly what we want.
	 * If we use isset() here, the if statement will always return true since id has to be
	 * set at this stage, thus the wrong sql statement is executed, and new entries can't be
	 * saved.
	 */
	
	//Update existing entries
	if(!empty($_POST['id'])){
		$sql="UPDATE entries 
			  SET title=?,image=?,entry=?,url=?
			  WHERE id=?
			  LIMIT 1";
		$stmt=$db->prepare($sql);
		$stmt->execute(
				array($_POST['title'],$img_path,$_POST['entry'], $url,$_POST['id'])
				);
		$stmt->closeCursor();
	}
	//Save new entries
	else{
	$sql = "INSERT INTO entries (page, title, image, entry, url)
			VALUES (?, ?, ?, ?, ?)";
	$stmt=$db->prepare($sql);
	$stmt->execute(
			array($_POST['page'], $_POST['title'], $img_path,$_POST['entry'], $url)
			);
	$stmt->closeCursor();
	//process the page info
	}
	$page=htmlentities(strip_tags($_POST['page']));
	//get the ID of the entry
	//$id_obj=$db->query("SELECT LAST_INSERT_ID()");
	//$id=$id_obj->fetch();
	//$id_obj->closeCursor();
	
	//send the user to the new entry
	header('Location:/simple_blog/'.$page.'/'.$url);
	exit;
}

else{
	$page=htmlentities(strip_tags($_POST['page']));
	header('Location:/simple_blog/'.$page);
	exit;
}