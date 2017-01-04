<?php
class ImageHandler{
	private $save_dir; //folder to save images
	private $max_dims;
	
	public function __construct($save_dir,$max_dims=array(350,240)){
		$this->save_dir=$save_dir;
		$this->max_dims=$max_dims;
	}
	
	/**
	 * Determines new dimensions for an image
	 * 
	 * @param string $img the path to the upload
	 * @return array the new and original image dimensions
	 */
	private function getNewDims($img){
		// Assemble the necessary variables for processing
		list($src_w,$src_h)=getimagesize($img);
		list($max_w,$max_h)=$this->max_dims;
		
		// Check that the image is larger thann the maximun dimensions
		if($src_w>$max_w||$src_h>$max_h){
			//Determine the scale to which the image will be resized
			$s=min($max_w/$src_w,$max_h/$src_h);
		}
		else{
			//Keep the original dimensions if the image size is smaller tham 350*240 
			$s=1;
		}
		
		//Get the new dimensions
		$new_w=round($src_w*$s);
		$new_h=round($src_h*$s);
		
		return array($new_w,$new_h,$src_w,$src_h);
	}
	
	/**
	 * Resizes/resamples an images uploaded via a web form
	 * 
	 * @param array $upload the array contained in $_FILES
	 * @param bool $rename whether or not the image should be renamed
	 * @return string the path to the resized uploaded file
	 */
	public function processUploadedImage($file,$rename=TRUE){
		//seperate the file array into 5 pieces
		list($name,$type,$tmp,$err,$size)=array_values($file);
		
		//throws an exception if error orrurs
		if($err!=UPLOAD_ERR_OK){
			throw new Exception('An error occured with the upload');
			exit;
		}
		//check if the directory exists
		$this->checkSaveDir();
		
		//Generate a resized image
		$this->doImageResize($tmp);
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
	
	/**
	 * Determines how to process images
	 * 
	 * Use the MIME type of the provided image to determine what image handling functions should be
	 * used. This increases the pereormance of the script versus using imagecreatefromstring().
	 * 
	 * @param string $img the path to the upload
	 * @return array the image type-specific functions
	 */
	private function getImageFunctions($img){
		$info=getimagesize($img);
		
		switch($info['mime']){
			case 'image/jpeg':
			case 'image/pjpeg':
				return array('imagecreatefromjpeg','imagejpeg');
				break;
			case 'image/gif':
				return array('imagecreatefromgif','imagegif');
				break;
			case 'image/png':
				return array('imagecreatefrompng','imagepng');
				break;
			default:
				return FALSE;
				break;  //-->useless statement for default
		}
	}
	
	/**
	 * Generates a resampled and resized image
	 * 
	 * Creates and saves a new image based on the new dimensions and image type-specific functions determined by
	 * other class methods.
	 * 
	 * @param array $img the path to the upload
	 * @return void
	 */
	private function doImageResize($img){
		//Determine the new dimensions
		$d=$this->getNewDims($img);
		
		//Determine which function to use
		$funcs=$this->getImageFunctions($img);
		
		//Determine the image type
		$src_img=$funcs[0]($img);
		
		//Determine the new image size
		$new_img=imagecreatetruecolor($d[0], $d[1]);
		
		if(imagecopyresampled
				($new_img, $src_img, 0, 0, 0, 0, $d[0],$d[1] , $d[2], $d[3])){
			imagedestroy($src_img);
			//check if the new image has the same file type as the original one
			if($new_img && $funcs[1]($new_img,$img)){
				imagedestroy($new_img);
			}
			else{
				throw new Exception('Failed to save the new image!');
			}
			
		}
		else{
			throw new Exception("Could not resample the image!");
		}
		
	}
	
}