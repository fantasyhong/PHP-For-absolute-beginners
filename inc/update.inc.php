<?php

if($_SERVER['REQUEST_METHOD']=='POST'&&$_POST['submit']=='Save Entry'&&!empty($_POST['title'])&&!empty($_POST['entry'])){
	//make connection to the sql server
	include_once 'db.inc.php';
	$db=new PDO(DB_INFO,DB_USER,DB_PASS);
	//save entry
	$sql="INSERT INTO entries (title, entry) VALUES (?, ?)";
	$stmt=$db->prepare($sql);
	$stmt->execute(array($_POST['title'],$_POST['entry']));
	$stmt->closeCursor();
	//get the ID of the entry
	$id_obj=$db->query("SELECT LAST_INSERT_ID()");
	$id=$id_obj->fetch();
	$id_obj->closeCursor();
	//send the user to the new entry
	header('Location:../index.php?id='.$id[0]);
}

else{
	header('Location:../');
	exit;
}