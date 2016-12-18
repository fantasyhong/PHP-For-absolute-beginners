<?php
function retrieveEntries($db,$id=NULL){
	//$e=array();
	//check if id is present in the parameter
	if(isset($id)){
		$sql="SELECT title,entry
				FROM entries
				WHERE id=?
				LIMIT 1";
		$stmt=$db->prepare($sql);
		$stmt->execute(array($_GET['id']));
		//$row=$stmt->fetch();
		//echo $row;
		//echo hi;


		$e=$stmt->fetch();
		//echo $e;
		$fulldisp=1;
	}
	else{
		$sql="SELECT id,title
				FROM entries
				ORDER BY created DESC";
		foreach($db->query($sql) as $row){
			$e[]=array('id'=>$row['id'],'title'=>$row['title']);
		}
		$fulldisp=0; //flg for mutli entries, tell the presentation layer NOT to display everything
		/*
		* If no entry is created ($e[] is null), display a default message and tell the presentation to display
		* the message
		*/
		if(!is_array($e)){
			$fulldisp=1;
			$e=array('title'=>"No entries yet",'entry'=>'<a href="/admin.php">Post a new entry</a>');
		}
	}
	//Return the fulldisp flag alongside with the e array
	array_push($e, $fulldisp);
	return $e;
}

function sanitizeData($data){
	//if data is an entry, remove all tags except the <a> tag
	if(!is_array($data)){
		return strip_tags($data,"<a>");
	}
	//if it is an array, process it recursively
	else{
		return array_map('sanitizeData', $data);

	}
}