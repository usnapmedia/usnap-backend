<?php
header('Access-Control-Allow-Origin: *');
require 'flight/Flight.php';
require_once("libs/idiorm.php");
require_once ('libs/codebird.php');
require 'vendor/autoload.php';
require('libs/Pusher.php');
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphObject;
use Facebook\FacebookRequestException;
FacebookSession::setDefaultApplication('465899996898725', 'aed2212b3009195dd483b560cb0aeb6f');

date_default_timezone_set('America/New_York');

Flight::route('/', function(){
    echo 'uSnap API';
});
Flight::route('/v1/', function(){
 	// echo 'uSnap API';
 	$filename = 'index.php';
	if (file_exists($filename)) {
	    echo "uSnap API v1 was last updated: " . date ("F d Y H:i:s.", filemtime($filename));
	}
});

// test
Flight::route('GET /v1/test', 'test');

// Users
Flight::route('GET /v1/users', 'usersList');
Flight::route('GET /v1/users/me', 'getUserInfo');
Flight::route('POST /v1/register', 'usersRegister');
Flight::route('POST /v1/login', 'usersLogin');
Flight::route('GET /v1/logout', 'usersLogout');

// Connect
Flight::route('POST /v1/connect', 'connectPost');
Flight::route('POST /v1/share', 'saveImage');

// Campaigns 
Flight::route('GET /v1/campaigns', 'getCampaigns');
Flight::route('GET /v1/campaigns/@id', 'getCampaignsById');


// Feed
Flight::route('GET /v1/feed/live/', 'feedLive');
Flight::route('GET /v1/feed/live/@campaign_id', 'feedLiveCampaign');

Flight::route('GET /v1/feed/top', 'feedTop');
Flight::route('GET /v1/feed/top/@campaign_id', 'feedTopCampaign');

Flight::route('GET /v1/feed/single/@id', 'feedSingle');


// Apps
Flight::route('GET /v1/apps/@api_key', 'getApps');

// Share To Facebook
Flight::route('GET /v1/post/facebook', 'startPostToFb');
Flight::route('GET /v1/post/facebook/likes', 'startFbLikes');

// Share To Twitter
Flight::route('GET /v1/post/twitter', 'startPostToTw');
// Flight::route('GET /v1/post/facebook/likes', 'startFbLikes');

// Share To Google Plus
Flight::route('GET /v1/post/gplus', 'postToGp');
// Flight::route('GET /v1/post/facebook/likes', 'startFbLikes');

// Admin
Flight::route('GET /v1/admin/settings/@api_key', 'getSettings');
Flight::route('POST /v1/admin/settings/@api_key', 'updateSettings');
Flight::route('POST /v1/admin/campaigns', 'addCampaign');
Flight::route('POST /v1/admin/moderate/update/@id', 'updateStatus');
Flight::route('GET /v1/admin/moderate/feed/', 'toModerate');

// Admin Analytics
Flight::route('GET /v1/admin/analytics/', 'getAnalytics');


Flight::start();

function db(){
	// Connect to the demo database file
    ORM::configure('mysql:host=localhost;dbname=tanios_usnap');
	ORM::configure('username', 'root');
	ORM::configure('password', '2jCbCAL3DW9T');

    $db = ORM::get_db();
    return $db;
}

// Analytics 
function getAnalytics(){
    $db = db();
    // User Count, Media Count, Social Reach, App Users
    $count_users = ORM::for_table('U_users')->count();
    $count_media = ORM::for_table('U_saved')->count();
    $count_all_shares = ORM::for_table('U_saved')->select_many('fb_likes')->find_array();
    $sums[] = '';
    foreach($count_all_shares as $share){
        $sums[] = $share['fb_likes'];
    }
    $count_all_shares_sum = array_sum($sums);
    $count = array(
        'users' => $count_users,
        'media' => $count_media,
        'social'=>$count_all_shares_sum
        );
    header('Content-Type: application/json');
    echo '{"counts": ' . json_encode($count) . '}';
}

// ADMIN
function toModerate(){
    $db = db();
    $count = ORM::for_table('U_saved')->where('status',0)->count();
    $moderate = ORM::for_table('U_saved')->where('status',0)->select_many('id', 'email','username', 'url','thumb_url','watermark_url','text','fb_likes','campaign_id','usnap_score')->find_array();
    response($moderate,$count);
}
function updateStatus($id){
    $db = db();
    $media = ORM::for_table('U_saved')->find_one($id);
    if ($media){
       if($_POST['status'] == 1){
            $media->status = 1;
        } else if ($_POST['status']==2){
            $media->status = 2;
        } 
    $media->save();
    }
}

function updateSettings($api_key){
    $db = db();
    $app = ORM::for_table('U_apps')->where('api_key',$api_key)->find_one();
    if ($app){
       if($_POST['watermark']){
            $app->watermark = $_POST['watermark'];
            $app->name = $_POST['name'];
        } 
    $app->save();
    }
}

function getSettings($api_key){
    $db = db();
    // Get a list of all users from the database
    $count = ORM::for_table('U_apps')->where('api_key',$api_key)->count();
    $app = ORM::for_table('U_apps')->where('api_key',$api_key)->find_array();
    response($app,$count);
}

function addCampaign(){
    $db = db();
    if($_POST){
        // Create a new contact object
        $campaign = ORM::for_table('U_campaigns')->create();

        // SHOULD BE MORE ERROR CHECKING HERE!

        // Set the properties of the object
        $campaign->name = $_POST['name'];
        $campaign->description = $_POST['description'];
        $campaign->banner_img_url = $_POST['banner_img_url'];
        $campaign->start_date = convertTime($_POST['start_date']);
        $campaign->end_date = convertTime($_POST['end_date']);
        $campaign->prize = $_POST['prize'];
        $campaign->rules = $_POST['rules'];

        // Save the object to the database
        $campaign->save();
        $data = array('name'=>$_POST['name'],'desc'=>$_POST['description'],'image'=>$_POST['banner_img_url']);
        echo 'done';
        pushToAdmin('live-feed','new-campaign',$data);
    }
}

function convertTime($time){
    $phpdate = strtotime( $time );
    $mysqldate = date( 'Y-m-d H:i:s', $phpdate );
    return $mysqldate;
}

function pushToAdmin($channel,$event,$data){


$app_id = '115578';
$app_key = '3cb56cac36379ca8723b';
$app_secret = '94b8aeb831c4110fa687';

$pusher = new Pusher($app_key, $app_secret, $app_id);

$pusher->trigger($channel, $event, $data);
}


// APP

// All Users List
function usersList(){
	$db = db();
    // Get a list of all users from the database
    $count = ORM::for_table('U_users')->count();
    $contact_list = ORM::for_table('U_users')->select_many('email','username','first_name','last_name','dob','profile_pic')->find_array();
    response($contact_list,$count);
    
}

// Login or Register
function usersRegister(){
	// Handle POST submission
	$db = db();
	$email = $_POST['email'];
    $username = $_POST['username'];
	$password  = $_POST['password'];
        
    if (isset($_POST['api_key'])){
         $api_key  = $_POST['api_key'];
    } else {
         $api_key  = "joey1234";
    }
    

	$check = ORM::for_table('U_users')
            ->where(array(
         
                'username'=>$username
            ))
            ->find_many();

    if (!$check) {
        if($_POST){
        
        // Check for the app id using the API Key provided
        $app = ORM::for_table('U_apps')->where('api_key', $api_key)->find_one();

        // Create a user file
        $user = ORM::for_table('U_users')->create();

        if($app){
            $app_id = $app->id;
            $user->app_id = $app_id;
        } else {
             $user->app_id = 1;
        }
        
        $user->password = $password;
        $user->email = $email;
        $user->first_name = $_POST['first_name'];
        $user->last_name = $_POST['last_name'];
        $user->username = $_POST['username'];
        $user->dob = $_POST['dob'];
        // $user->meta = json_encode($_POST['meta']);

        // Save the object to the database
        $data = array('username'=>$_POST['username'],'email'=>$email);
        $user->save();
        pushToAdmin('live-feed','new-user',$data);

        header('Content-Type: application/json');
        echo '{"response": "User Created"}';

        // set_user($email);

        exit;
	    } else {
	    	header('Content-Type: application/json');
	    	header("HTTP/1.1 400");
	    	echo '{"response": "Missing Fields"}';
	    }
    } else {
	    	header('Content-Type: application/json');
	        header("HTTP/1.1 400");
            echo '{"response": "Email or Username already registered"}';
    }
}

function usersLogin(){
    // $email = $_POST['email'];
   
    //if ($_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']) {
    if (array_key_exists('PHP_AUTH_USER',$_SERVER) && array_key_exists('PHP_AUTH_PW',$_SERVER)) {
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
    //} else if ($_POST['username'] && $_POST['password']) {
    } else if ( $_SERVER['REQUEST_METHOD'] == 'PUT' && (array_key_exists('username',$_POST)) && array_key_exists('password',$_POST)) {
         $username = $_POST['username'];
         $password = $_POST['password'];
    }

    //if ($username && $password){
    if (!empty($username) && !empty($password)){
        $user =  check_user($username , $password);
        if ($user) {
            header('Content-Type: application/json');
            echo '{"response": "Logged In"}';   
            // echo getUser($username);
        
        } else {
            header('Content-Type: application/json');
            echo '{"response": "No user Info"}';
        } 
    } 
    // Missing Status 400 for Empty Logins
    
    
}



// Post Social Connect Tokens
function connectPost(){
	$username = current_user();
	if ($username) {
		$db = db();
		$user = ORM::for_table('U_users')->where('username',$username)->find_one();
		foreach($_POST as $key=>$value)
		{
            $value = json_encode($value);
			$user->set($key, $value);
			$user->save();
		}
		echo '{"response": "Connect Added"}';
	} else {
		header("HTTP/1.1 400");
        echo '{"response": "Must Be Logged In"}';
	}
	
}

// Post Social Connect Tokens
function saveImage(){
    $username = current_user();
    if ($username){
        //$imageUrl = "http://d.tanios.ca/usnap/api/uploads/";
        $imageUrl = "http://52.24.195.247/~sleiman/api.usnap.com/uploads/";
        $email = get_email($username);
        $app_id = get_app_id($username);
        $moderation = get_moderation_status($app_id);

        if ($email) {
            if ($_FILES){
            $db = db();
            $saved = ORM::for_table('U_saved')->create();
            // Set the properties of the object
            $image_name = mt_rand().".png";
            $saved->email = $email;
            $saved->username = $username;
            $saved->image_data = $image_name;
            $saved->filename = $_FILES['image_data']['name'];
            $url = $imageUrl.$image_name;
            $watermark_url = $imageUrl."watermark/".$image_name;
            $saved->url = $url;
            $saved->watermark_url = $watermark_url;
            $saved->set_expr('created_at', 'NOW()');
            // $saved->meta = json_encode($_POST['meta']);

            if (isset($_POST['fb'])){
                $saved->fb = $_POST['fb'];
            }
            if (isset($_POST['tw_key']) && isset($_POST['tw_secret'])){
                $saved->tw_key = $_POST['tw_key'];
                $saved->tw_secret = $_POST['tw_secret'];
            }
            if (isset($_POST['gp'])) {
                $saved->gp = $_POST['gp'];
            }
            if (isset($_POST['campaign_id'])){
                $saved->campaign_id = $_POST['campaign_id'];
            } else {
                // Set to App Settings Default Campaign
                $saved->campaign_id = 23;
            }
            if (isset($_POST['text'])) {
                $saved->text = $_POST['text'];
            }
           
            $saved->app_id = $app_id;
            if($moderation){
                $saved->status = 0;
            } else {
                $saved->status = 1;
            }
            // Save the object to the database
            $response = uploadImage($_FILES['image_data'],$image_name);
            if ($response[0] == 1){
            	$saved->fb_likes = 0;
                $saved->save();
                $data = array('image'=>$watermark_url,'email'=>$email);
                pushToAdmin('live-feed','new-image',$data);
                // pushToAdmin('live-feed','new-media');
            }
            
            echo '{"response": "'.$response[1].'"}';
        } else {
            header("HTTP/1.1 400");
            echo '{"response": "Please upload a file."}';
        }
        } else {
            header("HTTP/1.1 400");
            echo '{"response": "Must Be Logged In"}';
        }
    } else {
        header("HTTP/1.1 400");
        echo '{"response": "No User Detected"}';
    }
    
}

function startPostToFb(){
    $db = db();
    $images = ORM::for_table('U_saved')->find_array();
    foreach ($images as $image){
       
       if ($image['fb'] && !$image['fb_image_id']){
            $fb_image_id = postToFb($image['fb'],$image['image_data'],$image['text']);
            saveFbId($image['id'], $fb_image_id);
       } else {
         
       }
    }

}

function startPostToTw(){
    $db = db();
    $images = ORM::for_table('U_saved')->find_array();
    foreach ($images as $image){
       
       if ($image['tw_key'] && $image['tw_secret'] && !$image['tw_image_id'] ){
            $tw_image_id = postToTw($image['tw_key'],$image['tw_secret'],$image['watermark_url'],$image['text']);
            saveTwId($image['id'], $tw_image_id);
       } else {
         
       }
    }

}
function saveFbId($image_id,$fb_image_id){
    $db = db();
    $image = ORM::for_table('U_saved')->find_one($image_id);
    $image->set('fb_image_id', $fb_image_id);
    $image->save();
}
function saveTwId($image_id,$tw_image_id){
    $db = db();
    $image = ORM::for_table('U_saved')->find_one($image_id);
    $image->set('tw_image_id', $tw_image_id);
    $image->save();
}
function saveFbLike($id,$count){
    $db = db();
    $image = ORM::for_table('U_saved')->find_one($id);
    $image->set('fb_likes', $count);
    $image->save();
}
function postToFb($fb_token, $image,$text){
        // $fb_token = tokenByEmail($email);
        if($fb_token){

            $session = new FacebookSession($fb_token);
            if($session) {

                try {

                // Upload to a user's profile. The photo will be in the
                // first album in the profile. You can also upload to
                // a specific album by using /ALBUM_ID as the path     
                $response = (new FacebookRequest(
                  $session, 'POST', '/me/photos', array(
                    'source' => new CURLFile('uploads/watermark/'.$image, 'image/png'),
                    'message' => $text
                  )
                ))->execute()->getGraphObject();

                // If you're not using PHP 5.5 or later, change the file reference to:
                // 'source' => '@/path/to/file.name'

                echo "Posted with id: " . $response->getProperty('id');
                return $response->getProperty('id');

              } catch(FacebookRequestException $e) {

                echo "Exception occured, code: " . $e->getCode();
                echo " with message: " . $e->getMessage();

              }   

            }
        }
}

function postToGp(){
	$token = "eyJhbGciOiJSUzI1NiIsImtpZCI6IjQ3YjkzNzVmYWFmMDMxOWE0NDkwYTNjODM2Mjg1ODgzMGFiMjM5NTgifQ.eyJpc3MiOiJhY2NvdW50cy5nb29nbGUuY29tIiwic3ViIjoiMTEzNTUxNDQ4NDg3MjI3ODA1MzkwIiwiYXpwIjoiNzA1Nzg3OTM5NjQxLXE3dTJjYjl0YnJkMDRrdTRqYzk5aDRiZDVjMWNzN2JrLmFwcHMuZ29vZ2xldXNlcmNvbnRlbnQuY29tIiwiYXRfaGFzaCI6IjRPNnQ4Y05RTER3STZOaGVjbTVud2ciLCJhdWQiOiI3MDU3ODc5Mzk2NDEtcTd1MmNiOXRicmQwNGt1NGpjOTloNGJkNWMxY3M3YmsuYXBwcy5nb29nbGV1c2VyY29udGVudC5jb20iLCJpYXQiOjE0MzAyNTY3NDcsImV4cCI6MTQzMDI2MDM0N30.gDO-f8Y6C1Gre73FyWCugkBQ0I1-oh5LJPTzUQ7ueZmKfHP5ny7DCxEL3Qb1isqC4KqAf4W12yw5tHJUT0-d7WBPgWoKZYoZ6bfxM51tFrY1f_oh7S589Nj6TKpXTCc0Q2dMt1z9nMhzjMvDDHqRjTh5Ba8FsH6uQ7AwZNhqgRk";
	$client = new Google_Client();
	$client->verifyIdToken($token);
	$ticket = $client->verifyIdToken($token);
  	if ($ticket) {
    $data = $ticket->getAttributes();
    return $data['payload']['sub']; // user ID
  }
  return false;
	// var_dump($client);
}

function postToTw($tw_key, $tw_secret,$image_url,$text){

    \Codebird\Codebird::setConsumerKey('xWN8D23Qr6E0gJJFpthMXjbKX', 'CJy9NakTiTNlfFkKByYg2YJe1UzmWw43PN7qrKEixJOWFV2MYM');
    $cb = \Codebird\Codebird::getInstance();

    $cb->setToken($tw_key, $tw_secret);
    // $file = 'http://d.tanios.ca/usnap/api/uploads/1764775395.jpg';
    $reply = $cb->media_upload(array(
        'media' => $image_url
    ));
    // and collect their IDs
    $media_id = $reply->media_id_string;


    $params = array(
    'status' => $text,
     'media_ids' => $media_id
    );
    $reply = $cb->statuses_update($params);
    return $reply->id;
}

function startFbLikes(){
    $db = db();
    $images = ORM::for_table('U_saved')->find_array();
    foreach ($images as $image){
       
       if ($image['fb'] && $image['fb_image_id']){
             getFbLikes($image['id'],$image['fb_image_id'],$image['fb']);
       } else {
         
       }
    }
    updateAnalytics();
}
function updateAnalytics(){
    $count_all_shares = ORM::for_table('U_saved')->select_many('fb_likes')->find_array();
    $sums[] = '';
    foreach($count_all_shares as $share){
        $sums[] = $share['fb_likes'];
    }
    $count_all_shares_sum = array_sum($sums);
    $data = array('sum'=> $count_all_shares_sum);
    pushToAdmin('live-feed','update-social',$data);

}

function getFbLikes($id,$fb_image_id,$fb_token){
    if($fb_token){
        $session = new FacebookSession($fb_token);
        if($session) {

        try {
        $request = new FacebookRequest(
        $session,
        'GET',
        '/'.$fb_image_id.'/likes'
        );
        $response = $request->execute();
        $graphObject = $response->getGraphObject()->asArray();
        $count =  count($graphObject['data']);
        saveFbLike($id,$count);
        echo $count;
            } catch(FacebookRequestException $e) {

            echo "Exception occured, code: " . $e->getCode();
            echo " with message: " . $e->getMessage();

            }   
          }
        
    }
}

function tokenByEmail($email){
    $db = db();
        $user = ORM::for_table('U_users')
        ->where(array(
            'email' => $email
        ))
        ->find_array();
        if($user){

            $data = $user[0]['fb_data'];
            $fb_token = json_decode($data,true);
            $fb_token = $fb_token['token'];
            return $fb_token;
        }
}
function uploadImage($image,$image_name) {
$target_dir = "uploads/";
$target_file = $target_dir . basename($image_name);
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);

// Check if image file is a actual image or fake image
$check = getimagesize($image["tmp_name"]);
if($check !== false) {
    // echo "File is an image - " . $check["mime"] . ".";
    $uploadOk = 1;
} else {
    return array(0,"File is not an image.");
    $uploadOk = 0;
}
// Check if file already exists
if (file_exists($target_file)) {
    return array(0,"Sorry, file already exists.");
    $uploadOk = 0;
}
// Check file size
if ($image["size"] > 5000000) {
    return array(0,"Sorry, your file is too large.");
    $uploadOk = 0;
}
// Allow certain file formats
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
&& $imageFileType != "gif" ) {
    return array(0,"Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
    $uploadOk = 0;
}

    if ($uploadOk == 0) {
        return array(0,"Sorry, your file was not uploaded.");
    } else {
        if (move_uploaded_file($image["tmp_name"], $target_file)) {
            addWatermark($target_file,$image_name);
            return array(1,"The file ". basename($image_name). " has been uploaded.");
        } else {
            return array(0,"Sorry, there was an error uploading your file.");
        }
    }
}

function addWatermark($target_file,$image_name){
    // Load the stamp and the photo to apply the watermark to
    $watermark = findWatermark();
    $stamp = @imagecreatefrompng($watermark);
    $im = @imagecreatefrompng($target_file);
    
    // Set the margins for the stamp and get the height/width of the stamp image
    $marge_right = 20;
    $marge_bottom = 20;
    $sx = @imagesx($stamp);
    $sy = @imagesy($stamp);
    // Copy the stamp image onto our photo using the margin offsets and the photo 
    // width to calculate positioning of the stamp. 
    @imagecopy($im, $stamp, @imagesx($im) - $sx - $marge_right, @imagesy($im) - $sy - $marge_bottom, 0, 0, $sx, $sy);
    // Output and free memory
    @imagepng($im,'uploads/watermark/'.$image_name,0);
    @imagedestroy($im);
}

function findWatermark(){
    $db = db();
    $username = current_user();
    if ($username){
        $app_id = get_app_id($username);
        if ($app_id){
            $app = ORM::for_table('U_apps')->where('id', $app_id)->find_one();
            if($app){
                $watermark = $app->watermark;
                return $watermark;
            }
        }
        
    }
}
// function scaleImage($target_file){
//     // Get new dimensions
//     $percent = 0.25;
//     list($width, $height) = getimagesize($target_file);
//     $new_width = $width * $percent;
//     $new_height = $height * $percent;

//     // Resample
//     $image_p = imagecreatetruecolor($new_width, $new_height);
//     $image = imagecreatefromjpeg('thumb/'.$target_file);
//     imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
// }

// Display The Campaigns
function getCampaigns() {
    $db = db();
    $count = ORM::for_table('U_campaigns')->count();
    $all_campaigns = ORM::for_table('U_campaigns')->find_array();
    response($all_campaigns,$count);
}

// Display The Campaigns
function getCampaignsById($id) {
    $db = db();
    $count = ORM::for_table('U_campaigns')->where('id',$id)->count();
    $all_campaigns = ORM::for_table('U_campaigns')->where('id',$id)->find_array();
    response($all_campaigns,$count);
}



// Display The Current Feed
function feedLive() {
    $db = db();
    $count = ORM::for_table('U_saved')->where(array('status'=>1))->count();
    $all_feed = ORM::for_table('U_saved')->where(array('status'=>1))->order_by_desc('id')->select_many('email','username', 'url','thumb_url','watermark_url','text','fb_likes','campaign_id','usnap_score')->find_array();
    
    response($all_feed,$count);
}

// Display The Current Feed
function feedLiveCampaign($campaign_id) {
    $db = db();
    // Get a list of all users from the database
    if ($campaign_id == 'me'){
        $username = current_user();
        if ($username){
            $count = ORM::for_table('U_saved')->where('username',$username)->count();
            $all_feed = ORM::for_table('U_saved')->where(array('username'=>$username))->select_many('email','username', 'url','thumb_url','watermark_url','text','fb_likes','campaign_id','usnap_score')->find_array();
        }
    } else if ($campaign_id) {
        $count = ORM::for_table('U_saved')->where(array('status'=>1,'campaign_id'=>$campaign_id))->count();
        $all_feed = ORM::for_table('U_saved')->where(array('status'=>1,'campaign_id'=>$campaign_id))->order_by_desc('id')->select_many('email','username', 'url','thumb_url','watermark_url','text','fb_likes','campaign_id','usnap_score')->find_array();
    } 
    
    response($all_feed,$count);
}

// Display The Top Feed (currently same as Live)
function feedTop() {
    $db = db();
    // Get a list of all users from the database
    $count = ORM::for_table('U_saved')->count();
    $all_feed = ORM::for_table('U_saved')->where('status',1)->order_by_desc('fb_likes')->select_many('email','username', 'url','thumb_url','watermark_url','text','fb_likes','campaign_id','usnap_score')->find_array();
    response($all_feed,$count);
}

// Display The Current Feed
function feedTopCampaign($campaign_id) {
    $db = db();
    // Get a list of all users from the database
    if ($campaign_id) {
        $count = ORM::for_table('U_saved')->where(array('status'=>1,'campaign_id'=>$campaign_id))->count();
        $all_feed = ORM::for_table('U_saved')->where(array('status'=>1,'campaign_id'=>$campaign_id))->order_by_desc('fb_likes')->select_many('email','username', 'url','thumb_url','watermark_url','text','fb_likes','campaign_id','usnap_score')->find_array();
    } 
    
    response($all_feed,$count);
}

function feedSingle($id) {
    $db = db();
    $count = ORM::for_table('U_saved')->where(array('status'=>1,'id'=>$id))->count();
    $all_feed = ORM::for_table('U_saved')->where(array('status'=>1,'id'=>$id))->order_by_desc('id')->select_many('email','username', 'url','thumb_url','watermark_url','text','fb_likes','campaign_id','usnap_score')->find_array();
    
    response($all_feed,$count);
}

// Return the data for an App
function getApps($api_key) {
    $db = db();
    // Get a list of all users from the database
    $count = ORM::for_table('U_apps')->where('api_key',$api_key)->count();
    $app = ORM::for_table('U_apps')->where('api_key',$api_key)->find_array();
    response($app,$count);
}

function getUser($username) {
    $db = db();
    // Get a list of all users from the database
    $count = ORM::for_table('U_users')->where('username',$username)->count();
    $app = ORM::for_table('U_users')->where('username',$username)->select_many('email','username','first_name','last_name','dob','profile_pic')->find_array();
    response($app,$count);
}

// Format Response
function response($data,$count) {
    header('Content-Type: application/json');
	echo '{"count": ' . json_encode($count) . ',"response": ' . json_encode($data) . '}';
}

function getUserInfo() {
    $user =  check_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    if ($user) {
        header('Content-Type: application/json');
        // echo '{"response": "Logged In With HTTP Authorization"}';
        echo getUser($_SERVER['PHP_AUTH_USER']);
        // set_user($email);
    } 
}

function current_user(){

       $user =  check_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
       if($user){
            return $_SERVER['PHP_AUTH_USER'];
        } else {
            header("HTTP/1.1 401");
            echo '{"response": "Authorization Required"}';
            exit;
        }
    
	
}

function get_app_id($username){
    $db = db();
    if($username){
        $user = ORM::for_table('U_users')->where('username', $username)->find_one();
        if($user){
            $app_id = $user->app_id;
            return $app_id;
        } else {
            header("HTTP/1.1 400");
            echo '{"response": "No User Found"}';
        }
    }
}

function get_email($username){
    $db = db();
    if($username){
        $user = ORM::for_table('U_users')->where('username', $username)->find_one();
        if($user){
            $email = $user->email;
            return $email;
        } else {
            header("HTTP/1.1 400");
            echo '{"response": "No User Found"}';
        }
    }
}
  
function get_moderation_status($app_id){
    $db = db();
    if($app_id){
        $app = ORM::for_table('U_apps')->where('id', $app_id)->find_one();
        if($app){
            $moderation = $app->moderation;
            return $moderation;
        } else {
            header("HTTP/1.1 400");
            echo '{"response": "No App Found"}';
        }
    }
} 
function check_user($username,$password){
    $db = db();
    if($username && $password){
        $user = ORM::for_table('U_users')
        ->where(array(
            'username' => $username,
            'password'=>$password
        ))
        ->find_many();
        if($user){
            return true;
        } else {
            header('Content-Type: application/json');
            header("HTTP/1.1 401");
            echo '{"response": "Wrong Password or Username"}';
            die();
        }
    } else {
        header('Content-Type: application/json');
        header("HTTP/1.1 401");
        echo '{"response": "Enter Username and Password"}';
        die();
    }
}




?>
