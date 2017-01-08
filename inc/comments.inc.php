<?php
include_once 'db.inc.php';
class Comments{
	private $db; //database connection
	
	//An array for containing the entries
	private $comments;
	
	//establish a database connection in the constructor
	public function __construct(){
		$this->db=new PDO(DB_INFO,DB_USER,DB_PASS);
	}
	
	//Display a form for users to enter new comments
	public function showCommentsForm($blog_id){
		
		$errors=array(
			1=>'<p class="error">Something went wrong while saving your comment. Please try again!</p>',
			2=>'<p class="error">Please provide a valid email address!</p>',
			3=>'<p class="error">Please answer the Math question correctly!</p>'
		);
		if(isset($_SESSION['error'])){
			$error=$errors[$_SESSION['error']];
		}
		else 
			$error=NULL;
		//Check if session variable exist
		if(isset($_SESSION['c_name'])){
			$n=$_SESSION['c_name'];
		}
		else 
			$n=NULL;
		if(isset($_SESSION['c_email'])){
			$e=$_SESSION['c_email'];
		}
		else
			$e=NULL;
		if(isset($_SESSION['c_comment'])){
			$c=$_SESSION['c_comment'];
		}
		else
			$c=NULL;
		
			
		//Generate a challenge question
		$challenge=$this->generateChallenge();
		return <<<FORM
	<form method="post" action="/simple_blog/inc/update.inc.php" id="comment-form">
 		<fieldset>
		  <legend>Post a Comment</legend>$error
		  <label>Name
	  		<input type="text" name="name" maxlength="75" value="$n"/>
	  	  </label>
		  <label>Email
		  	<input type="text" name="email" maxlength="150" value="$e"/>
		  </label>
		  <label>Comment
		  	<textarea name="comment" cols="45" rows="10">$c</textarea>
		  </label>$challenge
		  <input type="submit" name="submit" value="Post Comment" />
		  <input type="submit" name="submit" value="Cancel" />
		  <input type="hidden" name="blog_id" value=$blog_id />
 		</fieldset>
	</form>
FORM;
	}
		
	//Save comments to the database
	public function saveComment($p){
		//Save the comment info in a session
		$_SESSION['c_name']=htmlentities($p['name'],ENT_QUOTES);
		$_SESSION['c_email']=htmlentities($p['email'],ENT_QUOTES);
		$_SESSION['c_comment']=htmlentities($p['comment'],ENT_QUOTES);
		//Check if email address is valid
		if($this->validateEmail($p['email'])==FALSE){
			$_SESSION['error']=2;
			return ;
		}
		
		//Check if the Math quesiton is properly answered
		if(!$this->verifyResponse($p['s_q'])){
			$_SESSION['error']=3;
			return;
		}
		//Sanitize the data and store in variables
		$blog_id=htmlentities(strip_tags($p['blog_id']),ENT_QUOTES);
		$name=htmlentities(strip_tags($p['name']),ENT_QUOTES);
		$email=htmlentities(strip_tags($p['email']),ENT_QUOTES);
		$comment=htmlentities(strip_tags($p['comment']),ENT_QUOTES);
		
		//Keep formatting of comments and remove extra whitespace
		$comment=nl2br(trim($comment));
		
		//Generate and prepare the sql command
		$sql="INSERT INTO comments (blog_id,name,email,comment) 
			  VALUES (?,?,?,?)";
		if($stmt=$this->db->prepare($sql)){	
			$stmt->execute(array($blog_id,$name,$email,$comment));
			$stmt->closeCursor();
			
			//Destory the comment info to empty the form
			unset($_SESSION['c_name'],$_SESSION['c_email'],
					$_SESSION['c_comment'],$_SESSION['error']);
			return ;
		}
		else{
			$_SESSION['error']=1;
			return;
		}
	}
	
	//Load all comments for a blog entry into memory
	public function retrieveComments($blog_id){
		//Get all the comments for the entry
		$sql="SELECT id, name, email, comment, date
				FROM comments
				WHERE blog_id=?
				ORDER BY DATE DESC";
		$stmt=$this->db->prepare($sql);
		$stmt->execute(array($blog_id));
		
		// Loop through returned rows
		while($comment=$stmt->fetch()){
			//Store in memory for later use
			$this->comments[]=$comment;
		}
		
		//Set up a default response if no comments exist
		if(empty($this->comments)){
			$this->comments[]=array(
					'id'=>NULL,
					'name'=>NULL,
					'email'=>NULL,
					'comment'=>"There are no comments on this entry.",
					'date'=>NULL
			);
		}
	}
	
	//Generate HTML markup for displaying comments
	public function showComments($blog_id){
		//Initialize the variable in case no comments exist
		$display=NULL;
		
		//Load the comments for the entry
		$this->retrieveComments($blog_id);
		
		//Loop through the stored comments
		foreach($this->comments as $c){
			//Prevent empty field if no comments exist
			if(!empty($c['date'])&&!empty($c['name'])){
				//Output similar to: January 4, 2017 at 10:11PM
				$format="F j, Y \a\\t g:iA";
				
				//Convert $c['date'] to a timestamp, then format
				$date=date($format,strtotime($c['date']));
				
				//Generate a byline for the comment
				$byline="<span><strong>$c[name]</strong>
						[Posted on $date]</span>";
				
				if(isset($_SESSION['loggedin'])
						&&$_SESSION['loggedin']==1){
				//Generate a delete link for the comment display
				$admin="<a href=\"/simple_blog/inc/update.inc.php"
						."?action=comment_delete&id=$c[id]\""
						."class=\"admin\">delete</a>";}
				else 
					$admin=NULL;
			}
			else{
				//If we get here, no comment exist
				$byline=NULL;
				$admin=NULL;
			}
			
			//Assemble the pieces into a formatted comment
			$display.="<p class=\"comment\">$byline$c[comment]$admin</p>";
			
			
		}
		//Return all the formatted comments as a string
		return $display;
	}
	
	//Ensure the user really wants to delete the comment
	public function confirmDelete($id){
		//Store the entry url if available
		if(isset($_SERVER['HTTP_REFERER'])){
			$url=$_SERVER['HTTP_REFERER'];
		}
		
		//Otherwise use the default view
		else{
			$url='../';
		}
		return <<<FORM
	<html>
	<head>
	<title>Please Confirm Your Decision</title>
	<link rel="stylesheet" href="/simple_blog/css/default.css" type="text/css" />
	<form method="post" action="/simple_blog/inc/update.inc.php">
 		<fieldset>
		  <legend>Are You Sure?</legend>
		  <p>Are you sure you want to delete this comment?</p>
		  <input type="submit" name="confirm" value="Yes" />
		  <input type="submit" name="confirm" value="No" />
		  <input type="hidden" name="action" value="comment_delete" />
		  <input type="hidden" name="url" value="$url" />
		  <input type="hidden" name="id" value="$id" />
 		</fieldset>
	</form>
FORM;
	}
		
	//Removes the comment corresponding to the $id from the database
	public function deleteComment($id){
		$sql="DELETE FROM comments
				WHERE id=?
				LIMIT 1";
		if($stmt=$this->db->prepare($sql)){
			$stmt->execute(array($id));
			$stmt->closeCursor();
			return TRUE;
		}
		else 
			return FALSE;
	}
	
	//Checking if an email is valid
	/*
	 * Note: This method utilizes internal PHP method to validate email address as opposed to manually checking it
	 */
	private function validateEmail($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	
	//Add basic bot protection
	private function generateChallenge(){
		//Store two random numbers in an array
		$numbers=array(mt_rand(1,4),mt_rand(1,4));
		
		//Store the answer in a session
		$_SESSION['challenge']=$numbers[0]+$numbers[1];
		
		//Convert the numbers to their ACSII codes
		$converted=array_map('ord',$numbers);
		
		//Generate a math question as HTML markup
		return "
		<label>&#87;&#104;&#97;&#116;&#32;&#105;&#115;&#32;
		&#$converted[0];&#32;&#43;&#32;&#$converted[1];&#63;
		<input type=\"text\" name=\"s_q\" />
		</label>";
	}
	
	//Check user input for the Math question
	private function verifyResponse($resp){
		//Grab the session value then destory it
		$val=$_SESSION['challenge'];
		unset($_SESSION['challenge']);
		
		//Return true if reral
		return $val==$resp;
	}
}