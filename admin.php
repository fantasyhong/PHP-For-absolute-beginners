<!DOCTYPE html>
<?php 

session_start();

//If the user is logged in, we can continue
if(isset($_SESSION['loggedin'])&& $_SESSION['loggedin']==1):
/*
 * Include all external files
 */
include_once 'inc/functions.inc.php';
include_once 'inc/db.inc.php';

$db=new PDO(DB_INFO,DB_USER,DB_PASS);


//check if page is set
if(isset($_GET['page'])){
	$page=htmlentities(strip_tags($_GET['page']));
}
else{
	$page='blog';
}

//confirm remove
if(isset($_POST['action'])&&$_POST['action']=='delete'){
	if($_POST['submit']=='Yes'){
		$url=htmlentities(strip_tags($_POST['url']));
		if(deleteEntry($db,$url)){
			header("Location:/simple_blog/");
			exit;
		}
		else{
			exit("Error deleting the entry");
		}
	}
	else{
		$url=htmlentities(strip_tags($_POST['url'])); //fixed the redirecting error in the book
		header("Location:/simple_blog/blog/$url");
		exit;
	}
}

//check if url is set, and load the existing entry when it is
if(isset($_GET['url']))
{
	$url=htmlentities(strip_tags($_GET['url']));
	
	//check if the entry should be deleted
	if($page=='delete'){
		$confirm=confirmDelete($db,$url);
	}
	
	//set the legend
	$legend="Edit This Entry";
	
	//load the entry to be edited
	$e=retrieveEntries($db, $page,$url);
	
	//save the entry info 
	$id=$e['id'];
	$title=$e['title'];
	$img = $e['image'];
	$entry=$e['entry'];
}
else{
	//Check if we're creating a new user
	if($page=='createuser'){
		$create=createUserForm();
	}
	$legend="New Entry Submission";
	
	$id=$title=$img=$entry=NULL;
}
?>

<html>
<head>
	<link rel="stylesheet" href="/simple_blog/css/default.css" type="text/css" />
	<title>Simple Blog</title>
	
</head>
<body>
<h1>Simple Blog Application</h1>

<?php 
	if($page=='delete'):
	{
		echo $confirm;
	}
	elseif($page=='createuser'):
	{
		echo $create;
	}
	else:
?>
<form method="post" action="/simple_blog/inc/update.inc.php" enctype="multipart/form-data">
 <fieldset>
	  <legend><?php echo $legend?></legend>
	  <label>Title
	  	<input type="text" name="title" maxlength="150" value="<?php echo htmlentities($title)?>"/>
	  </label>
	  <label>Image
	  	<input type="file" name="image" />
	  </label>
	  <label>Entry
	  	<textarea name="entry" cols="45" rows="10"><?php echo sanitizeData($entry)?></textarea>
	  </label>
	  <input type="hidden" name="id" value="<?php echo $id?>" />
	  <input type="hidden" name="page" value="<?php echo $page?>" />
	  <input type="submit" name="submit" value="Save Entry" />
	  <input type="submit" name="submit" value="Cancel" />
 </fieldset>
</form>
<?php endif;?>
</body>
</html>
<?php 

/*
 * If we get here, the user is not logged in. Display a form and ask them to log in.
 */
else:
?> 

<html>
<head>
	<link rel="stylesheet" href="/simple_blog/css/default.css" type="text/css" />
	<title>Please Log In</title>
</head>
<body>
	<form method="post" action="/simple_blog/inc/update.inc.php" enctype="mutlipart/form-data">
	 		<fieldset>
			  <legend>Please Log In To Continue</legend>
			  <label>Username
				<input type="text" name="username" maxlength="75" />
			  </label>
			  <label>Password
				<input type="password" name="password"  />
			  </label>
			  <input type="submit" name="submit" value="Log In" />
			  <input type="hidden" name="action" value="login" />
	 		</fieldset>
	</form>
</body>
</html>
<?php endif;?>