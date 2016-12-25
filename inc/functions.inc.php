<?php
function retrieveEntries($db,$page,$url=NULL){
	//$e=array();
	//check if id is present in the parameter
	if(isset($url)){
		$sql="SELECT id,page,title,entry
				FROM entries
				WHERE url=?
				LIMIT 1";
		$stmt=$db->prepare($sql);
		$stmt->execute(array($_GET['url']));
		//$row=$stmt->fetch();
		//echo $row;
		//echo hi;


		$e=$stmt->fetch();
		//echo $e;
		$fulldisp=1;
	}
	else{
		$sql="SELECT id,page,title,entry,url
				FROM entries
				WHERE page=?
				ORDER BY created DESC";
		$stmt=$db->prepare($sql);
		$stmt->execute(array($page));
		$e=NULL;//declare variable to avoid errors
		/*foreach($db->query($sql) as $row){
			$e[]=array('id'=>$row['id'],'title'=>$row['title']);
		}*/
		while($row=$stmt->fetch()){
			if($page=='blog'){
				$e[]=$row;
				$fulldisp=0; //flg for mutli entries, tell the presentation layer NOT to display everything
			}
			else{  //not a good way to check, needs improvement
				$e=$row;
				$fulldisp=1;
			}
		}
		/*
		* If no entry is created ($e[] is null), display a default message and tell the presentation to display
		* the message
		*/
		if(!is_array($e)){
			$fulldisp=1;
			$e=array('title'=>"No entries yet",'entry'=>'This page does not have an entry yet!');
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

/*
 * This function gets rid of the whitespace in the title and replaces it with hyphen, it also removes any speical characters
 */

function makekUrl($title){
	$patterns=array('/\s+/','/(?!-)\W+/');
	$replacements=array('-','');
	return preg_replace($patterns, $replacements, strtolower($title));
}

