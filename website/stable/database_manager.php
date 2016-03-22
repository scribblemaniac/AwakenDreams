<?php

class DBManager {
	// Temporarily public for testing purposes, should be private during deployment
	public $db;

	function __construct($accessLevel, $databaseLocation) {
		if(gettype($accessLevel) != "string" || gettype($databaseLocation) != "string") {
			// TODO throw error
		}
		$accessLevel = strtolower($accessLevel);
		$databaseLocation = strtolower($databaseLocation);
		switch($accessLevel) {
			case "ohtar": // Read permissions only
				$password = '8mADhFud/6d73A8+TSQ2vVSAjPVGRhJWyZTfYmjfPcM=';
				break;
			case "arphen": // Read and write permissions
				$password = '6H7GMLpz/TLCjIXPqf2I3OhsBMFG3wYEOyUOe6Edma4=';
				break;
			case "maia": // Full permissions (unimplemented until necessary)
				// Unimplemented until necessary
			default:
				// TODO throw error
		}
		if(!($databaseLocation == 'logindb' || $databaseLocation == 'forum')) {
			// TODO throw error
		}
		
		try {
			$this->db = new PDO('mysql:host=localhost;dbname=tvpx10ho_'.$databaseLocation, 'tvpx10ho_'.$accessLevel, mcrypt_decrypt(MCRYPT_RIJNDAEL_256, 'obfuscate', base64_decode($password), MCRYPT_MODE_ECB), array(PDO::ATTR_PERSISTENT => true));
		} catch (PDOException $e) {
			// TODO throw error
		}
	}
	
	function __destruct() {
		$this->db = null;
	}
	
	function getUserId($username) {
		if(strlen($username) > 16 || strlen($username) < 4 || !ctype_alnum($username)) {
			return -1;
		}
		$stmt = $this->db->prepare('SELECT * FROM loginData WHERE username = ?');
		$stmt->bindParam(1, $username, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return -1;
		}
		return $row['userid'];
	}
	
	function verifyPassword($userid, $password) {
		$stmt = $this->db->prepare('SELECT passwordHash FROM loginData WHERE userid = :userid');
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return false;
		}
		return password_verify($password, $row['passwordHash']);
	}
	
	function getUserEmail($userid) {
		$stmt = $this->db->prepare('SELECT email FROM loginData WHERE userid = :userid');
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return null;
		}
		return $row['email'];
	}
	
	function addUser($username, $password, $emailAddress) {
		$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 13]);
		$stmt = $this->db->prepare('INSERT INTO loginData (username, passwordHash) VALUES (:username, :passwordHash)');
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->bindParam(':passwordHash', $hash, PDO::PARAM_STR);
		$stmt->execute();
		$id = getUserId($username);
		setUserEmail($id, $emailAddress);
		return $id;
	}
	
	function setUserEmail($userid, $address) {
		if(strlen($address) > 254 || !filter_var($address, FILTER_VALIDATE_EMAIL) || getUserId($userid) < 0) {
			return false;
		}
		if(getUserEmail($userid) == $address) {
			return true;
		}
		$encrypted_address = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, 'i7bV4xDRxPXFFpkk4KIyzh9OERCzm1su', $address, MCRYPT_MODE_ECB));
		if(!$encrypted_address) {
			return false;
		}
		$stmt = $this->db->prepare('UPDATE loginData SET email=:email WHERE userid=:userid');
		$stmt->bindParam(':email', $encrypted_address, PDO::PARAM_STR);
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		$result = $stmt->execute();
		$username = getUsername($userid);
		$token = bin2hex(random_bytes(16));
		$stmt = $this->db->prepare('INSERT INTO emailConfirmations (userid, token) VALUES (:userid, :token)');
		$stmt->bindParam(':userid', $userid);
		$stmt->bindParam(':token', $token);
		$result &= $stmt->execute();
		// TODO make message prettier
		// TODO implement email schema (http://www.bruceclay.com/blog/6-things-you-need-to-know-about-email-schema/)
		mail($address, 'Please activate your account', '<html><head><title>Please activate your account</title></head><body>Mae govannen, '.$username.'!\nPlease click the link below to confirm your email address.<br /><a href="https://tvp.elementfx.com/confirm_email.php?token='.$token.'">http://tvp.elementfx.com/confirm_email.php?token='.$token.'</a></body></html>', 'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset=iso-8859-1'."\r\n".'From: The Valar Project <noreply@tvp.elementfx.com>');
		return $result;
	}
}

?>