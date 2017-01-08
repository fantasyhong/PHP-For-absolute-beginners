<?php
function retrieveEntries($db,$page,$url=NULL){
	//$e=array();
	//check if id is present in the parameter
	if(isset($url)){
		$sql="SELECT id,page,title,image,entry,created
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
		$sql="SELECT id,page,title,image,entry,url,created
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
			$e=array('title'=>"No entries yet",
					'entry'=>'This page does not have an entry yet!');
		}
	}
	//Return the fulldisp flag alongside with the e array
	array_push($e, $fulldisp);
	return $e;
}

function adminlinks($page, $url){
	//Fotmat the links for edit & delete
	$editURL="/simple_blog/admin/$page/$url";
	$deleteURL="/simple_blog/admin/delete/$url";
	
	//Make hyperlinks for both options
	$admin['edit']="<a href=\"$editURL\">edit</a>";
	$admin['delete']="<a href=\"$deleteURL\">delete</a>";
	
	return $admin;
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

/*
 * this function confirms whether the user wants to delete the entry form database
 */
function confirmDelete($db,$url){
	$e=retrieveEntries($db,'', $url);
	return <<<FORM
	<form method="post" action="/simple_blog/admin.php">
 		<fieldset>
		  <legend>Are You Sure?</legend>
		  <p>Are you sure you want to delete the entry "$e[title]"?</p>
		  <input type="submit" name="submit" value="Yes" />
		  <input type="submit" name="submit" value="No" />
		  <input type="hidden" name="action" value="delete" />
		  <input type="hidden" name="url" value="$url" />
 		</fieldset>
	</form>
FORM;
}

function deleteEntry($db,$url){
	$sql="DELETE FROM entries
			WHERE url=?
			LIMIT 1";
	$stmt=$db->prepare($sql);
	return $stmt->execute(array($url));
	
}

function formatImage($img=NULL,$alt=NULL){
	if($img){
		return '<img src="'.$img.'" alt="'.$alt.'" />';
	}
	else {
		return NULL;
	}
}

/*
 * this function helps setting up an account
 */

function createUserForm(){
	return <<<FORM
	<form method="post" action="/simple_blog/inc/update.inc.php">
 		<fieldset>
		  <legend>Create a New Administrator</legend>
		  <label>Username
			<input type="text" name="username" maxlength="75" />
		  </label>
		  <label>Password
			<input type="password" name="password"  />
		  </label>
		  <input type="submit" name="submit" value="Create" />
		  <input type="submit" name="submit" value="Cancel" />
		  <input type="hidden" name="action" value="createuser" />
 		</fieldset>
	</form>
FORM;
}
	
//create shortUrl
function shortenUrl($url,$format='txt'){
	//Format a call to the bit.ly API
	$api='http://api.bit.ly/v3/shorten';
	$param='login=fantasyhong'.'&apiKey=R_551e8eef82c24c229908fe488e7b2503'.'&uri='.urlencode($url).'&format='.$format;
	
	//Open a connection and load the response
	$uri=$api."?".$param;
	return curl_get_result($uri);
}

/* returns a result form url 
 * Source: https://davidwalsh.name/bitly-api-php
 * */
function curl_get_result($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

//Post the current link to Twitter
function postToTwitter($title){
	// Replace lvh.me to $_SERVER['HTTP_HOST'] when the blog goes online
	$full='http://'.'lvh.me'.$_SERVER['REQUEST_URI']; 
	$short=shortenUrl($full);
	$status=$title.' '.$short;
	return 'https://twitter.com/?status='.urldecode($status);
}