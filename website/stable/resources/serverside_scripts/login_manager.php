<?php

header('MIME-Version: 1.0');
header('Content-Type: application/json');

// Import files
try {
	require_once(realpath($_SERVER['DOCUMENT_ROOT']).'/resources/serverside_scripts/global.php');
	require($GLOBALS['root'].'/resources/serverside_scripts/database_manager.php');
}
catch(Exception $e) {
	exit('{ "status": "error" }');
}

// TODO serialize this class
class Response {
	private $array = array();
	
	// Sets status to an error message, sets response code, and calls finish
	// This is necessary to maintain uniform responses across various situations
	function setErrorMessage($errorType=0) {
		$this->array['status'] = 'error';
		switch($errorType) {
		case 1:
			http_response_code(400);
			$this->array['result'] = 'Invalid request';
			break;
		case 2:
			http_response_code(403);
			$this->array['result'] = 'Invalid credentials';
			break;
		case 3:
			http_response_code(429);
			$this->array['result'] = 'Too many requests';
		case 0:
		default:
			http_response_code(500);
			$this->array['result'] = 'Internal error occurred';
		}
		
		$this->finish();
	}
	
	function getResult() {
		return $this->array['result'];
	}
	
	function setResult($res) {
		$this->array['result'] = $res;
		return $this;
	}
	
	function setStatus($status) {
		$this->array['status'] = $status;
		return $this;
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

if(!isset($_POST['op'])) {
	$response->addDebugMessage('Operation has not been posted')->setErrorMessage(1);
}

// Determine the type of request and handle it accordingly
switch($_POST['op']) {
	case 'email_validate':
		if(!isset($_POST['email'])) {
			$response->addDebugMessage('No email provided')->setErrorMessage(1);
		}
		
		$response->setResult(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))->setStatus("success");
		break;
	case 'username_validate': // Returns true if no username exists by that name
		if(!isset($_POST['username'])) {
			$response->addDebugMessage('Username has not been posted')->setErrorMessage(1);
		}
		
		if(strlen($_POST['username']) > 16 || strlen($_POST['username']) < 4 || !ctype_alnum($_POST['username'])) {
			return $response->setResult(FALSE)->setStatus("success");
		}
		
		$dbm = new DBManager('ohtar', 'logindb');
		
		$response->setResult($dbm->getUserId($_POST['username']) < 0)->setStatus("success");
		break;
	case 'signup':
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
		
		$dbm = new DBManager('arphen', 'logindb');
		
		// Check if username already exists
		if($dbm->getUserId($_POST['username']) >= 0) {
			$response->addDebugMessage('Username already exists')->setErrorMessage(2);
		}
		
		// Decrpyt password
		global $map, $shift;
		$map = str_split("!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789");
		$shift = explode('-', '7-3-46-9-4f-45-4e-5a-56-2c-2b-22-5-30-53-53-12-10-4-22-5a-e-4c-12-c-36-f-3a-4e-4f-46-4d-1d-4a-45-2c-4c-9-48-5d-4c-1a-5a-5b-22-23-41-19-4e-39-5d-49-51-3b-35-5-1a-5-4a-6-2c-29-55-30-13-9-36-54-37-16-5a-49');
		$password = str_split($_POST['password']);
		$password = implode(array_map(function($i, $c) {
			global $map, $shift;
			return $map[(94 + array_search($c, $map) - hexdec($shift[$i])) % 94];
		}, array_keys($password), $password));
		
		// Add user to database
		if($dbm->addUser($_POST['username'], $password, $_POST['email']) < 0) {
			$response->addDebugMessage('Adding entry failed')->setErrorMessage(0);
		}
		
		$response->setStatus('success');
		break;
	case 'login':
		// Check to make sure username, opaque and hmac were passed with the request
		if(!isset($_POST['username']) || !isset($_POST['opaque']) || !isset($_POST['hmac'])) {
			$response->addDebugMessage('Username, opaque or hmac has not been posted')->setErrorMessage(1);
		}
		// Check to make sure the hmac is the right format
		if(strlen($_POST['hmac']) != 128 || !ctype_xdigit($_POST['hmac'])) {
			$response->addDebugMessage('Incorrect hmac format')->setErrorMessage(1);
		}
		// Check to make sure the opaque is the right format
		if(strlen($_POST['opaque']) != 64 || !ctype_xdigit($_POST['opaque'])) {
			$response->addDebugMessage('Incorrect opaque format')->setErrorMessage(1);
		}
		
		$dbm = new DBManager('ohtar', 'logindb');
		
		// Check if number of attempts has been exceeded
		if($dbm->getAttempts($_SERVER['REMOTE_ADDR']) > 5) {
			$response->addDebugMessage('Number of attempts exceeded')->setErrorMessage(3);
		}
		
		// Check if username is valid and is in database
		$id = $dbm->getUserId($_POST['username']);
		if($id < 0) {
			$response->addDebugMessage('Username does not exist or is invalid')->setErrorMessage(2);
		}
		// Verify password
		if($dbm->verifyPassword($_POST['opaque'], $_POST['hmac'], $id)) {
			$response->addDebugMessage('Incorrect password')->setErrorMessage(2);
		}
		
		$response->setStatus('success');
		break;
	case 'password_recovery':
		if(!isset($_POST['username']) || !isset($_POST['email'])) {
			$response->addDebugMessage('Username or email has not been posted')->setErrorMessage(1);
		}
		
		$dbm = new DBManager('arphen', 'logindb');
		
		// Check if username is valid and is in database
		$uid = $dbm->getUserId($_POST['username']);
		if($uid < 0) {
			$response->addDebugMessage('Username does not exist or is invalid')->setErrorMessage(2);
		}
		// Check to make sure the email is a proper format
		if(strlen($_POST['email']) > 254 || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$response->addDebugMessage('Incorrect email')->setErrorMessage(2);
		}
		
		if(!$dbm->createNewRecoveryToken($uid, $_POST['email'])) {
			$response->addDebugMessage('Could not create recovery token')->setErrorMessage(2);
		}
		
		$response->setStatus('success');
		break;
	/*case 'cnonce_sync':
		if(!isset($_POST['username'])) {
			$response->addDebugMessage('No username provided')->setErrorMessage(1);
		}
		
		$dbm = new DBManager('ohtar', 'logindb');
		$uid = $dbm->getUserId($_POST['username']);
		if($id < 0) {
			$cnonce = $dbm->getCnonce($uid);
		}
		$response->setStatus('success');
		if(is_null($cnonce)) {
			$reponse->setResult(0);
		}
		else {
			$response->setResult((int)$cnonce);
		}
		break;
	case 'logout':
		// Check to make sure all necessary variables were passed with the request
		if(!isset($_COOKIE['username']) || !isset($_COOKIE['hmac'])) {
			$response->addDebugMessage('Username or hmac has not been posted')->setErrorMessage(1);
		}
		// Check to make sure the hmac is the right format
		if(strlen($_POST['hmac']) == 128 || !ctype_xdigit($_POST['hmac'])) {
			$response->addDebugMessage('Incorrect hmac format')->setErrorMessage(1);
		}
		
		$dbm = new DBManager('ohtar', 'logindb');
		
		// Check if username is valid and is in database
		$id = $dbm->getUserId($_POST['username']);
		if($id < 0) {
			$response->addDebugMessage('Username does not exist or is invalid')->setErrorMessage(2);
		}
		// Verify password
		if($dbm->verifyPassword($id, $_POST['hmac'])) {
			$response->addDebugMessage('Incorrect password')->setErrorMessage(2);
		}
		
		if($dbm->
		
		$response->setStatus('success');
		break;
	*/
	default:
		$response->addDebugMessage('Invalid request type')->setErrorMessage(1);
}

$response->finish();

?>