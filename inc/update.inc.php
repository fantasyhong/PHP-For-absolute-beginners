<?php

//Start the session
session_start();
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

//If a comment is being posted, handle it here
else if($_SERVER['REQUEST_METHOD']=='POST'
		&&$_POST['submit']=='Post Comment'
		&&!empty($_POST['name'])
		&&!empty($_POST['email'])
		&&!empty($_POST['comment'])){
	//Include and instantiate the comment class
	include_once 'comments.inc.php';
	$comments=new Comments();
	
	//Save the comment
	$comments->saveComment($_POST); //essentially a lazy way to pass all the info, could be handled better
		//If available, store the entry the user came from
		if(isset($_SERVER['HTTP_REFERER'])){
			$loc=$_SERVER['HTTP_REFERER'];
		}
		else{
			$loc='../';
		}
		//send the user back to the entry
		header('Location: '.$loc);
		exit;
	
	
	
}

//If the delete link is clicked on a comment, confirm it here
else if($_GET['action']=='comment_delete'){
	
	//Include and instantiate the Comments class
	include_once 'comments.inc.php';
	$comments=new Comments();
	echo $comments->confirmDelete($_GET['id']);
	exit;
}
//If the comfirmDelete() form was submitted, handle it here
else if($_SERVER['REQUEST_METHOD']=='POST'
		&&$_POST['action']=='comment_delete'){
	//If set, store the entry from which we came
	$loc=isset($_POST['url'])?$_POST['url']:'../';
	
	//If the user clicked 'yes', continue with the deletion
	if($_POST['confirm']=="Yes"){
		//Include and instantiate the Comments class
		include_once 'comments.inc.php';
		$comments=new Comments();
		
		//Delete the comment and return to the entry
		if($comments->deleteComment($_POST['id'])){
			header('Location: '.$loc);
			exit;
		}
		
		//If deleting fails, output an error message
		else{
			exit('Could not delete the comment.');
		}
	}
	//If the user clicked "No", do nothing and return to the entry
	else{
		header('Location: '.$loc);
		exit;
	}
}

//If a user is trying to log in,check it here
else if($_SERVER['REQUEST_METHOD']=='POST'
		&&$_POST['action']=='login'
		&&!empty($_POST['username'])
		&&!empty($_POST['password']))
{
		//Include database credentials and connect to the database
		include_once 'db.inc.php';
		$db=new PDO(DB_INFO,DB_USER,DB_PASS);
		$sql="SELECT password
				FROM admin
				WHERE username=?";
		$stmt=$db->prepare($sql);
		//Retrieve the encrypted password from database
		$stmt->execute(array($_POST['username']));
		
		/* Note: The following statements to fetch the admin password only works when 
		 * there's only one admin in the database,
		 * if there are mutliple admins, need a while loop instead
		 */
		$response=$stmt->fetch();
		$check_pass=$response['password'];
		
		//Get the non-encrypted password from user
		$pass=$_POST['password'];
		
		if(password_verify($pass, $check_pass)){
			$_SESSION['loggedin']=1;
			header('Location: /simple_blog/');
		}
		else{
			$_SESSION['loggedin']=0;
			header('Location: /simple_blog/admin');
		}
		//header('Location: /simple_blog/');
		exit;
}

//If an admin is being created, save it here
else if($_SERVER['REQUEST_METHOD']=='POST'
		&&$_POST['action']=='createuser'
		&&!empty($_POST['username'])
		&&!empty($_POST['password']))
{
		//Include database credentials and connect to the database
		include_once 'db.inc.php';
		$db=new PDO(DB_INFO,DB_USER,DB_PASS);
		$sql="INSERT INTO admin(username,password)
				VALUES(?,?)";
		$stmt=$db->prepare($sql);
		
		//Use a strong encryption algorithm to encrypt the admin password
		$password = password_hash($_POST['password'], PASSWORD_DEFAULT, ['cost' => 12]);
		$stmt->execute(array($_POST['username'],$password));
		header('Location: /simple_blog/');
		exit;
}

//If the user has chosen to log out, process it here
else if($_GET['action']=='logout'){
	unset($_SESSION['loggedin']);
	header('Location:../');
	exit;
}
else{
	unset($_SESSION['c_name'],$_SESSION['c_email'],
			$_SESSION['c_comment'],$_SESSION['error']);
	$page=htmlentities(strip_tags($_POST['page']));
	header('Location:/simple_blog/'.$page);
	exit;
}