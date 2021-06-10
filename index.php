<?php
session_start();
$url_array = explode('?', 'http://'.$_SERVER ['HTTP_HOST'].$_SERVER['REQUEST_URI']);
$url = $url_array[0];

require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_DriveService.php';
$client = new Google_Client();
$client->setClientId('Clinet Id');
$client->setClientSecret('Secret Key');
$client->setRedirectUri($url);
$client->setScopes(array('https://www.googleapis.com/auth/drive'));
if (isset($_GET['code'])) 
{
    $_SESSION['accessToken'] = $client->authenticate($_GET['code']);
    header('location:'.$url);exit;
} 
elseif(!isset($_SESSION['accessToken']))
{
    $client->authenticate();
}
$files= array();
$dir = dir('files');
while ($file = $dir->read())
{
    if($file != '.' && $file != '..')
	{
        $files[] = $file;
    }
}
$dir->close();

// after submit file then upload to google drive

if(isset($_POST['submit']))
{
	if(empty($_FILES["file"]['tmp_name']))
	{
        echo "Go back and Select file to upload.";
        exit;
    }
	else
	{
	   $client->setAccessToken($_SESSION['accessToken']);
       $service = new Google_DriveService($client);
       $finfo = finfo_open(FILEINFO_MIME_TYPE);
       $file = new Google_DriveFile();
	   $file_name = basename($_FILES["file"]["name"]);
	   $target_dir = "files/";
	
       $target_file = $target_dir . basename($_FILES["file"]["name"]);
	   if(move_uploaded_file($_FILES["file"]["tmp_name"],$target_file))
	   {
            echo "The file ". basename( $_FILES["file"]["name"])
                        . " has been uploaded.<br>";
          $mime_type = finfo_file($finfo,$target_file);
          $file->setTitle($file_name);
          $file->setDescription('This is a '.$mime_type.' document');
          $file->setMimeType($mime_type);
          $service->files->insert(
            $file,
            array(
                'data' => file_get_contents($target_file),
                'mimeType' => $mime_type
            )
          );  
            
       }
	   else
	   {
            echo "Sorry, there was an error uploading your file.<br>";
       } 
    }
}
include 'index.phtml';
