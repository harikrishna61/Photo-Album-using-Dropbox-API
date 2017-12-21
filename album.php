<?php

// display all errors on the browser
error_reporting(E_ALL);
ini_set('display_errors','Off');


require_once 'demo-lib.php';
demo_init(); // this just enables nicer output

// if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit( 0 );

require_once 'DropboxClient.php';

/** you have to create an app at @see https://www.dropbox.com/developers/apps and enter details below: */
/** @noinspection SpellCheckingInspection */
$dropbox = new DropboxClient( array(
    'app_key' => "7v77qay8nf8zj6i",      // Put your Dropbox API key here
    'app_secret' => "4gjvf33rfv4mjvj",   // Put your Dropbox API secret here
    'app_full_access' => false,
) );


/**
 * Dropbox will redirect the user here
 * @var string $return_url
 */
$return_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?auth_redirect=1";

// first, try to load existing access token
$bearer_token = demo_token_load( "bearer" );

if ( $bearer_token ) {
    $dropbox->SetBearerToken( $bearer_token );
//    echo "loaded bearer token: " . json_encode( $bearer_token, JSON_PRETTY_PRINT ) . "\n";
} elseif ( ! empty( $_GET['auth_redirect'] ) ) // are we coming from dropbox's auth page?
{
    // get & store bearer token
    $bearer_token = $dropbox->GetBearerToken( null, $return_url );
    demo_store_token( $bearer_token, "bearer" );
} elseif ( ! $dropbox->IsAuthorized() ) {
    // redirect user to Dropbox auth page
    $auth_url = $dropbox->BuildAuthorizeUrl( $return_url );
    die( "Authentication required. <a href='$auth_url'>Continue.</a>" );
}

echo '
<html><body>
<h3>Upload a New Image to Drop box</h3><form action="album.php" method="POST" enctype="multipart/form-data"><p>Select image to upload:</p>
<input type="file" name="fileToUpload" id="fileToUpload"><br/>
<input type="submit" value="Upload Image" name="submit">
</form>';
if(isset($_POST['submit'])) {
    $file_name = basename($_FILES["fileToUpload"]["name"]);
    $target = "C:/xampp/htdocs/project8/" . basename($_FILES["fileToUpload"]["name"]);
    move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target);
    $dropbox->UploadFile($file_name);
    echo $file_name." has been uploaded";
}
echo "<hr>";
echo"<h3>Images in Dropbox Directory</h3>";
echo "<form action='album.php' method='POST'><input type='submit' name='list_files' value='List Files' /> </form>";
$fnames=array();
if(isset($_POST['list_files']))
{
    $files = $dropbox->GetFiles( "", false );
    if(empty($files))
    {
        echo "Directory is empty, Please upload new files.";
    }
    else {
        $fnames = array_keys($files);
        foreach ($fnames as $f => $f_name) {
            echo "<h5>" . $f_name . "</h5>";
            echo "\t--<a  href='album.php?display=" . $f_name . "' >Display & Download " . $f_name . "</a><br>";
            echo "\t--<a  href='album.php?delete=" . $f_name . "' >Delete " . $f_name . "</a><br>";
            echo "</script>";
        }
    }
}


echo "<hr>";
echo "<h3>Image Section</h3>";

if(isset($_GET['display']) ){
    $display_image=(string)$_GET['display'];
    echo $display_image;
    echo "<br>";
    if (strpos($display_image, ".jpg") or strpos($display_image, ".JPG"))
    {
        $jpg_files = $dropbox->Search("/", $display_image, 5);
        $jpg_file = reset( $jpg_files );
        $img_data = base64_encode( $dropbox->GetThumbnail( $jpg_file->path ) );
        echo "<img src=\"data:image/jpeg;base64,$img_data\" alt=\"Generating PDF thumbnail failed!\" style=\"border: 1px solid black;\" />";
            $test_file =  basename( $jpg_file->path );
            $dropbox->DownloadFile( $jpg_file->path, $test_file )   ;
    }
}
if(isset($_GET['delete'])){
    $delete_img=(string)$_GET{'delete'};
    $file=$dropbox->Search("/",$delete_img,5);
    $f=reset($file);
    $del=$dropbox->Delete($dropbox->GetMetadata( $f->path ));
}
echo '</body></html>';
?>
