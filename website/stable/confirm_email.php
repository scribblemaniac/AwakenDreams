<?php
include('pageContent.php');

try {
	require_once(realpath($_SERVER['DOCUMENT_ROOT']).'/resources/serverside_scripts/global.php');
	require($GLOBALS['root'].'/resources/serverside_scripts/database_manager.php');
}
catch(Exception $e) {
	http_response_code(500);
	exit('Internal error occured');
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
  <title>Email Confirmation - The Valar Project</title>
  <?php head(); ?>
  <style type="text/css">
  </style>
 </head>
 <body>
  <?php bodyStart(); ?>
  <h1>Email Confirmation</h1>
  <div>
   <?php

function confirmEmail() {
	$errorMsg = 'We cannot confirm your email address. Please make sure that you have entered the URL correctly and that you are using the link from the latest email that we have sent you.';
	if(!isset($_GET['token'])) {
		return $errorMsg;
	}
	$token = strtolower($_GET['token']);
	
	$dbm = new DBManager('arphen', 'logindb');
	
	$email = $dbm->confirmUserEmail($token);
	if(is_null($email)) {
		return $errorMsg;
	}
	
	return 'Thank you! The email address <span style="font-style:italic;">'.$email.'</span> has been confirmed.';
}

echo confirmEmail();

   ?>
  </div>
  <?php bodyEnd(); ?>
 </body>
</html>