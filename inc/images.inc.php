<?php
class ImageHandler{
	public $save_dir; //folder to save images
	
	public function __construct($save_dir){
		$this->save_dir=$save_dir;
	}
	
	/**
	 * Resizes/resamples an images uploaded via a web form
	 * 
	 * @param array $upload the array contained in $_FILES
	 * @return string the path to the resized uploaded file
	 */
	public function processUploadedImage($file,$rename=TRUE){
		//seperate the file array into 5 pieces
		list($name,$type,$tmp,$err,$size)=array_values($file);
		
		//throws an exception if error orrurs
		if($err!=UPLOAD_ERR_OK){
			throw new Exception('An error occured with the upload');
			return;
		}
		//check if the directory exists
		$this->checkSaveDir();
		
		//rename the file if the flag is set to true
		if($rename===TRUE){
			$img_ext=$this->getImageExtension($type);
			$name=$this->renameFile($img_ext);
		}
		
		//create the full path to the image for saving
		$filepath=$this->save_dir.$name;
		
		//store the absolute path to move the image
		$absolute=$_SERVER['DOCUMENT_ROOT'].$filepath;
		
		
		//save the image
		if(!move_uploaded_file($tmp, $absolute)){
			throw new Exception("Couldn't save the uploaded file!");
		}
		
		return $filepath;
		
	}
	
	/**
	 * Ensures that the save directory exists
	 * 
	 * check for the existence of the supplied save directory, and creates the directory if it doesn't exist.
	 * Creation is  recursive.
	 * 
	 * @param void
	 * @return void
	 */
	private function checkSaveDir(){
		//determines the path to check
		$path=$_SERVER['DOCUMENT_ROOT'].$this->save_dir;
		
		//check if the directory exists
		if(!is_dir($path)){
			//creates the directory
			if(!mkdir($path,0777,TRUE)){
				throw new Exception("Can't create the directory");
			}
		}
		
	}
	
	/**
	 * Generates a unique name for a file
	 * 
	 * Uses a current timestamp and a randomly generated number to create a unique name to be used for an uploaded file.
	 * This helps prevents a new file upload from overwriting a existing file with a same name.
	 * 
	 * @param string $ext the file extension for the upload
	 * @return string the new filename
	 */
	private function renameFile($ext){
		//append the file name with the current time stamp and a random number
		return time().'_'.mt_rand(1000,9999).$ext;
		
		
	}
	
	/**
	 * Determines the filetype and extension of an image
	 * 
	 * @param string $type the MIME type of the image
	 * @return string the extension to be used with the file
	 */
	private function getImageExtension($type){
		switch($type){
			case 'image/gif':
				return '.gif';
			case 'image/jpeg':
			case 'image/pjpeg':
				return '.jpg';
			case 'image/png':
				return '.png';
			default:
				throw new Exception ("File type not supported!");
		}
	}
	
}