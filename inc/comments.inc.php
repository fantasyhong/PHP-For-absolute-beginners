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
		return <<<FORM
	<form method="post" action="/simple_blog/inc/update.inc.php" id="comment-form">
 		<fieldset>
		  <legend>Post a Comment</legend>
		  <label>Name
	  		<input type="text" name="name" maxlength="75"/>
	  	  </label>
		  <label>Email
		  	<input type="text" name="email" maxlength="150"/>
		  </label>
		  <label>Comment
		  	<textarea name="comment" cols="45" rows="10"></textarea>
		  </label>
		  <input type="submit" name="submit" value="Post Comment" />
		  <input type="submit" name="submit" value="Cancel" />
		  <input type="hidden" name="blog_id" value=$blog_id />
 		</fieldset>
	</form>
FORM;
	}
		
	//Save comments to the database
	public function saveComment($p){
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
			return TRUE;
		}
		else{
			return FALSE;
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
				
				//Generate a delete link for the comment display
				$admin="<a href=\"/simple_blog/inc/update.inc.php"
						."?action=comment_delete&id=$c[id]\""
						."class=\"admin\">delete</a>";
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
	
}