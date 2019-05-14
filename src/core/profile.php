<?php
namespace Ziki\Core;
use Ziki\Core\FileSystem;

class Profile {


        private function filterString($string)
        {
            $string=htmlspecialchars($string);
            $string=strip_tags($string);
            $string = stripslashes($string);

            return $string;
        }

    public function updateProfile($request){
        //check if the name is not empty.
       
        $error=[];
        if(empty(trim($request['name'])))
        {
            $error['nameError']= 'This is a required field';
        }
        else
        {
            $name = $this->filterString($request['name']);
        }
        //check if bio aint empty
        if(empty(trim($request['bio'])))
        {
            $error['bioError']= 'This is a required field';
        }
        else
        {
            $bio = $this->filterString($request['bio']);
        }
        //checks if email is not empty
        if(empty(trim($request['email'])))
        {
            $error['emailError']= 'This is a required field';
        }
        else
        {
            if(filter_var($request['email'],FILTER_VALIDATE_EMAIL) === false)
            {
                $error['emailError'] = 'Please input a valid email address';
                //$email = $request['email'];
            }
            else
            {
                $email= $this->filterString($request['email']);
            }
        }
            // Get Image Dimension
            $fileinfo = getimagesize($_FILES["image"]["tmp_name"]);
            
            
            $allowed_image_extension = array(
                "png",
                "jpg",
                "jpeg"
            );
            
            // Get image file extension
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            
            // Validate file input to check if is not empty
            if (! file_exists($_FILES["image"]["tmp_name"])) {
                $response ="Choose image file to upload.";
            
            }   
             // Validate file input to check if is with valid extension
            else if (! in_array($file_extension, $allowed_image_extension)) {
                $response =  "Upload valid images. Only PNG, JPG and JPEG are allowed.";
            
            }    // Validate image file size
            else if (($_FILES["image"]["size"] > 1000000)) {
                $response = "Image size exceeds 1MB";
                
            }    // Validate image file dimension
            else {
                $target = basename($_FILES["image"]["name"]);
                if (move_uploaded_file($_FILES["file-input"]["tmp_name"], $target)) {
                    $response =  "Image uploaded successfully.";
                    
                } else {
                    $response = "Problem in uploading image files.";
                    
                }
            }
            $error['imageError']= $response;
     
        $data['name'] = $name;
        $data['bio'] = $bio;
        $data['email'] = $email;
        $data['image'] = $target;
        $data['error'] = $error;
        $result = json_encode($data);
        return $result;
    }
}


     


    
