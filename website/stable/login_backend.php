<?php

include_once('/global.php');
include('/database_manager.php');

// TODO create a serializable class for response array, and add some of the functions below to it

class Response {
	private $array = array();
	
	// Sets status to an error message, sets response code, and calls finish
	// This is necessary to maintain uniform responses across various situations
	function setErrorMessage($errorType=0) {
		switch($errorType) {
		case 0:
			http_response_code(500);
			$this->array['status'] = 'Internal error occured';
		case 1:
			http_response_code(400);
			$this->array['status'] = 'Invalid request';
		case 2:
			http_response_code(403);
			$this->array['status'] = 'Invalid credentials';
		}
		
		$this->finish();
	}
	
	// Addes a debug tag, but only if debugging is enabled on the server
	function addDebugMessage($debugMessage) {
		if($GLOBALS['debug']) {
			if(is_null($this->array['debug'])) {
				$this->array['debug'] = array((string)$debugMessage);
			}
			$this->array['debug'] .= $debugMessage;
		}
		return $this;
	}
	
	function finish() {
		exit(json_encode($this->array));
	}
}

$response = new Response();

// Determine the type of request and handle it accordingly
if(isset($_POST['login'])) {
	// Check to make sure username and password were passed with the request
	if(!isset($_POST['username']) || !isset($_POST['password'])) {
		$response->addDebugMessage('Username or password has not been posted')->setErrorMessage(1);
	}
	// Check to make sure the username is the right length and contains only alphanumeric characters
	if(strlen($_POST['username']) > 16 || strlen($_POST['username']) < 4 || !ctype_alnum($_POST['username'])) {
		$response->addDebugMessage('Incorrect username format')->setErrorMessage(2);
	}
	// Check to make sure the password is the right length
	if(strlen($_POST['password']) > 72 || strlen($_POST['password']) < 6) {
		$response->addDebugMessage('Incorrect password format')->setErrorMessage(2);
	}
	
	$dbmanager = new DBManager('ohtar', 'logindb');
	
	// Check if username is in database
	$id = $dbmanager->getUserId($_POST['username']);
	if($id < 0) {
		$response->addDebugMessage('Username does not exist')->setErrorMessage(2);
	}
	// Verify password
	if($dbmanager->verifyPassword($id, $_POST['password'])) {
		$response->addDebugMessage('Incorrect password')->setErrorMessage(2);
	}
	
	$dbmanager = null;
	
	echo 'Success';
}
else if(isset($_POST['signup'])) {
	// Check to make sure all necessary variables were passed with the request
	if(!isset($_POST['username']) || !isset($_POST['email']) || !isset($_POST['password'])) {
		$response->addDebugMessage('Username, email, or password has not been posted')->setErrorMessage(1);
	}
	// Check to make sure the username is the right length and contains only alphanumeric characters
	if(strlen($_POST['username']) > 16 || strlen($_POST['username']) < 4 || !ctype_alnum($_POST['username'])) {
		$response->addDebugMessage('Incorrect username format')->setErrorMessage(2);
	}
	// Check to make sure the email is a proper format
	if(strlen($_POST['email']) > 254 || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$response->addDebugMessage('Incorrect email')->setErrorMessage(2);
	}
	// Check to make sure the password is the right length
	if(strlen($_POST['password']) > 72 || strlen($_POST['password']) < 6) {
		$response->addDebugMessage('Incorrect password format')->setErrorMessage(2);
	}
	
	$dbmanager = new DBManager('ohtar', 'logindb');
	
	// Check if username already exists
	if($dbmanager->getUserId($_POST['username']) >= 0) {
		$response->addDebugMessage('Username already exists')->setErrorMessage(2);
	}
	
	// Add user to database
	if(addUser($_POST['username'], $_POST['password'], $_POST['email']) < 0) {
		$response->addDebugMessage('Adding entry failed')->setErrorMessage(0);
	}
	
	$dbmanager = null;
	
	echo 'Success';
}
else if(isset($_POST['validate_email'])) {
	if(!isset($_POST['email'])) {
		$response->addDebugMessage('No email provided')->setErrorMessage(1);
	}
	echo filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
}
else {
	$response->addDebugMessage('Invalid request type')->setErrorMessage(1);
}

$response->finish();

?>