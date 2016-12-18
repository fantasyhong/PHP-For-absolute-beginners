<?php 
/*
 * Include all external files
 */
	include_once 'inc/functions.inc.php';
	include_once 'inc/db.inc.php';
	
	$db=new PDO(DB_INFO,DB_USER,DB_PASS);
	
	//check if ID is passed in the url
	$id=(isset($_GET['id']))?(int)$_GET['id']:NULL;
	
	//load the entries
	$e=retrieveEntries($db,$id);
	
	//get the fulldisplay flag and remove it from the array
	$fulldisp=array_pop($e);
	
	//Sanitize the data
	$e=sanitizeData($e);
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="css/default.css" type="text/css" />
<title>Simple Blog</title>

</head>
<body>
<h1> Simple Blog Application</h1>
<div id="entries">
<?php
//show the entry is the flag is set
if($fulldisp==1){
	?>
	<h2><?php echo $e['title']?></h2>
	<p><?php echo $e['entry']?></p>
	<p class="blacklink">
	<a href="./">Back to Latest Entries</a>
	</p>
<?php 
}

//show the links when flag is 0
else{
	foreach($e as $entry){
?>  <p>
	<a href="?id=<?php echo $entry['id'] ?>">
		<?php echo $entry['title']?>
		</a>
		</p>
<?php 				
	}
}
?>
	<p class="blacklink"> <a href="admin.php">Post a New Entry</a>
	</p>
</div>	
</body>
</html>