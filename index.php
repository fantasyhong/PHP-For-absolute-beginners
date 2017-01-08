<?php 

	session_start();
/*
 * Include all external files
 */
	include_once 'inc/functions.inc.php';
	include_once 'inc/db.inc.php';
	
	$db=new PDO(DB_INFO,DB_USER,DB_PASS);
	
	/*
	 * Check if page attribute is present
	 */
	if(isset($_GET['page'])){
		$page=htmlentities(strip_tags($_GET['page']));
	}
	else{
		$page='blog'; //load default
	}
	
	//check if url is passed in the url
	$url=(isset($_GET['url']))?$_GET['url']:NULL;
	
	//load the entries
	$e=retrieveEntries($db,$page,$url);
	
	//get the fulldisplay flag and remove it from the array
	$fulldisp=array_pop($e);
	
	//Sanitize the data
	$e=sanitizeData($e);
?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" href="/simple_blog/css/default.css" type="text/css" />
	<link rel="alternate" type="application/rss+xml" title="My Simple Blog - RSS 2.0" href="/simple_blog/feeds/rss.php"/>
	<title>Simple Blog</title>

</head>
<body>
<h1>Simple Blog Application</h1>
<ul id="menu">
	<li><a href="/simple_blog/blog">Blog</a></li>
	<li><a href="/simple_blog/about">About the Author</a></li>
</ul>

<?php if(isset($_SESSION['loggedin'])&&$_SESSION['loggedin']==1):?>
		<p id="control_panel">
			You are logged in!
			<a href="/simple_blog/inc/update.inc.php?action=logout">Log out</a>
		</p>
<?php endif;?>

<div id="entries">
<?php
//show the entry if the flag is set
if($fulldisp==1){
	//echo $page;
	$url=(isset($url))?$url:$e['url']; //get url if not passed
	
	//Display the admin links when a admin is logged in
	if(isset($_SESSION['loggedin'])&&$_SESSION['loggedin']==1){
	//Build the amdin links
	$admin=adminlinks($page, $url);
	}
	else{
	//Hide the admin control from users
	$admin=array('edit'=>NULL,'delete'=>NULL);
	}
	//Format the image if one exists
	//if($e['image'])
	$img=formatImage($e['image'],$e['title']);
	
	if($page=='blog'){
		//Load the comment object
		include_once 'inc/comments.inc.php';
		$comments=new Comments();
		$comment_disp=$comments->showComments($e['id']);
		$comment_form=$comments->showCommentsForm($e['id']);
		
		//Generate a Post to Twitter
		$twitter=postToTwitter($e['title']);
	}
	else {
		$comment_form=NULL;
		$twitter=NULL;
	}
	?>
	<h2><?php echo $e['title']?></h2>
	<p><?php echo $img?>
	<?php echo $e['entry']?></p>
	<p>
		<?php echo $admin['edit']?>
		<?php if($page=='blog') echo $admin ['delete']?>
	</p>
	<?php if($page=='blog'):?>
	<p class="backlink">
	<a href="<?php echo $twitter?>">Post to Twitter</a><br />
	<a href="/simple_blog/<?php echo $e['page']?>">Back to Latest Entries</a>	
	</p>
	<h3> Comments for This Entry </h3>
	<?php echo $comment_disp,$comment_form;?>
	<?php endif;?>
<?php 
}

//show the links when flag is 0
else{
	foreach($e as $entry){
?>  <p>
	<a href="/simple_blog/<?php echo $entry['page']?>/<?php echo $entry['url'] ?>">
		<?php echo $entry['title']?>
		</a>
		</p>
<?php 				
	}
}
?>
	<?php if($page=='blog'
			&&isset($_SESSION['loggedin'])
			&& $_SESSION['loggedin']==1):?>
	<p class="backlink"> 
		<a href="/simple_blog/admin/<?php echo $page ?>">
		Post a New Entry
		</a>
	</p>
	<?php endif;?>
	<p>
		<a href="/simple_blog/feeds/rss.php">
		Subscribe via RSS!
		</a>
	</p>
</div>	
</body>
</html>