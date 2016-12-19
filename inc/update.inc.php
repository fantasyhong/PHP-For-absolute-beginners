<?php

if($_SERVER['REQUEST_METHOD']=='POST'&&$_POST['submit']=='Save Entry'&&!empty($_POST['title'])&&!empty($_POST['page'])&&!empty($_POST['entry'])){
	//make connection to the sql server
	include_once 'db.inc.php';
	$db=new PDO(DB_INFO,DB_USER,DB_PASS);
	//save entry
	$sql="INSERT INTO entries (page, title, entry) VALUES (?, ?, ?)";
	$stmt=$db->prepare($sql);
	$stmt->execute(array($_POST['page'],$_POST['title'],$_POST['entry']));
	$stmt->closeCursor();
	//process the page info
	$page=htmlentities(strip_tags($_POST['page']));
	//get the ID of the entry
	$id_obj=$db->query("SELECT LAST_INSERT_ID()");
	$id=$id_obj->fetch();
	$id_obj->closeCursor();
	//send the user to the new entry
	header('Location:../index.php?page='.$page.'&id='.$id[0]);
}

else{
	header('Location:../');
	exit;
}