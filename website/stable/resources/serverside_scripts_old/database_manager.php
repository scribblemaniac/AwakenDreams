<?php

class DBManager {
	private $db;
	private $realm = 'TVP';

	function __construct($accessLevel, $databaseLocation) {
		if(gettype($accessLevel) != 'string' || gettype($databaseLocation) != 'string') {
			// TODO throw error
		}
		$accessLevel = strtolower($accessLevel);
		$databaseLocation = strtolower($databaseLocation);
		switch($accessLevel) {
			case 'ohtar': // Read permissions only
				$password = 'TQIQJ4Kfwu34NInH4WA5tEqEdwxsz8ETh+Hf3wze2jI=';
				break;
			case 'arphen': // Read and write permissions
				$password = '6LRX/gqjqvDiGd2MWezSV1l16tw8p//miQ8YwtAZu3I=';
				break;
			case 'maia': // Full permissions (unimplemented until necessary)
				// Unimplemented until necessary
			default:
				// TODO throw error
		}
		if(!($databaseLocation == 'logindb' || $databaseLocation == 'forum')) {
			// TODO throw error
		}
		
		try {
			$this->db = new PDO('mysql:host=localhost;dbname=tvpx10ho_'.$databaseLocation, 'tvpx10ho_'.$accessLevel, trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, 'obfuscate', base64_decode($password), MCRYPT_MODE_ECB)), array(PDO::ATTR_PERSISTENT => true));
		} catch (PDOException $e) {
			//echo $e->getMessage();
			//echo mcrypt_decrypt(MCRYPT_RIJNDAEL_256, 'obfuscate', base64_decode($password), MCRYPT_MODE_ECB);
			// TODO throw error
		}
	}
	
	function __destruct() {
		$this->db = null;
	}
	
	// Equal function to prevent timing attacks
	private function slowEqual($a, $b) {
		$alength = strlen($a);
		$blength = strlen($b);
		$diff = $alength ^ $blength;
		for($i = 0; $i < $alength && $i < $blength; $i++)
			$diff |= $a[$i] ^ $b[$i];
		return $diff == 0;
	}
	
	function getUserId($username) {
		if(strlen($username) > 16 || strlen($username) < 4 || !ctype_alnum($username)) {
			return -1;
		}
		$stmt = $this->db->prepare('SELECT `userid` FROM `loginData` WHERE `username` = :username');
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return -1;
		}
		return $row['userid'];
	}
	
	function getUsername($userid) {
		$stmt = $this->db->prepare('SELECT `username` FROM `loginData` WHERE `userid` = :userid');
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return NULL;
		}
		return $row['username'];
	}
	
	// Asymmetric implementation
	function verifyPassword($opaque, $hmacHash, $userid = -1) {
		// Get login data
		$stmt = $this->db->prepare('SELECT `hmacHash`, `nonce`, `cnonce` FROM `loginData` WHERE `opaque` = :opaque AND `lastAccess`+24*60*60 < NOW()');
		$stmt->bindParam(':opaque', $opaque, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return null;
		}
		if($row['cnonce'] == 0 && $userid < 0) {
			// A userid must be specified if logging in, and cnonce is only zero when logging in
			return false;
		}
		
		// Generate hash
		$serverHmacHash = hash_hmac('sha512', $row['nonce'].$row['cnonce'], $row['hmacHash']);

		// Check password
		$isCorrect = slowEqual($hmacHash, $serverHmacHash);
		
		if($row['cnonce'] == 0) {
			if($isCorrect) {
				// Update cnonce and userid since this is a login
				$stmt = $this->db->prepare('UPDATE `session` SET `cnonce` = `cnonce`+1, `userid` = :userid WHERE `opaque` = :opaque');
				$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
				$stmt->bindParam(':opaque', $opaque, PDO::PARAM_STR);
				if(!$stmt->execute()) {
					// Technically verification was successful, but the user needs to know that login failed
					return false;
				}
			}
			else {
				// Only update failed attempt, cnonce should remain at 0 when logging in
				$stmt = $this->db->prepare('UPDATE `session` SET `failedAttempts` = `failedAttepts`+1 WHERE `opaque` = :opaque');
				$stmt->bindParam(':opaque', $opaque, PDO::PARAM_STR);
				$stmt->execute();
			}
		}
		else {
			// Update cnonce
			$stmt = $this->db->prepare('UPDATE `session` SET `cnonce` = `cnonce`+1 WHERE `opaque` = :opaque');
			$stmt->bindParam(':opaque', $opaque, PDO::PARAM_STR);
			$stmt->execute();
		}

		return $isCorrect;
	}
	
	// This function is provided in case the client needs to synchronize the cnonce
	function getCnonce($userid) {
		$stmt = $this->db->prepare('SELECT `cnonce` FROM `loginData` WHERE `userid` = :userid');
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return null;
		}
		
		return $row['cnonce'];
	}
	
	/* Symmetric impelmentation
	function verifyPassword($userid, $password) {
		$stmt = $this->db->prepare('SELECT passwordHash FROM loginData WHERE userid = :userid');
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return false;
		}
		return password_verify($password, $row['passwordHash']);
	}*/
	
	function getUserEmail($userid) {
		$stmt = $this->db->prepare('SELECT `email` FROM `loginData` WHERE `userid` = :userid AND `emailVerified` = 1');
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return null;
		}
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, 'i7bV4xDRxPXFFpkk4KIyzh9OERCzm1su', base64_decode($row['email']), MCRYPT_MODE_ECB));
	}
	
	function addUser($username, $password, $emailAddress) {
		$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 13]);
		
		$message = $username.$this->realm.$password;
		$hmacHash = hash('sha512', $message);
		for($i = 1; $i < 4096; $i++) {
			$hmacHash = hash_hmac('sha512', hex2bin($hmacHash), $message);
		}
		
		$stmt = $this->db->prepare('INSERT INTO `loginData` (`username`, `passwordHash`, `hmacHash`) VALUES (:username, :passwordHash, :hmacHash)');
		$stmt->bindParam(':username', $username, PDO::PARAM_STR);
		$stmt->bindParam(':passwordHash', $hash, PDO::PARAM_STR);
		$stmt->bindParam(':hmacHash', $hmacHash, PDO::PARAM_STR);
		$stmt->execute();
		$uid = $this->getUserId($username);
		$emailRes = $this->setUserEmail($uid, $emailAddress);
		return $emailRes ? -1 : $uid;
	}
	
	function setUserEmail($userid, $address) {
		if(strlen($address) > 254 || !filter_var($address, FILTER_VALIDATE_EMAIL) || $userid < 0) {
			return false;
		}
		if($this->getUserEmail($userid) === $address) {
			return true;
		}
		$encrypted_address = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, 'i7bV4xDRxPXFFpkk4KIyzh9OERCzm1su', $address, MCRYPT_MODE_ECB));
		if(!$encrypted_address) {
			return false;
		}
		$stmt = $this->db->prepare('UPDATE `loginData` SET `email` = :email, `emailVerified` = 0 WHERE `userid` = :userid');
		$stmt->bindParam(':email', $encrypted_address, PDO::PARAM_STR);
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		if(!$stmt->execute()) {
			return false;
		}
		$username = $this->getUsername($userid);
		if(is_null($username)) {
			return false;
		}
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$stmt = $this->db->prepare('INSERT INTO `emailConfirmations` (`userid`, `token`) VALUES (:userid, :token) ON DUPLICATE KEY UPDATE `token` = :token');
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		$stmt->bindParam(':token', $token, PDO::PARAM_STR);
		if(!$stmt->execute()) {
			return false;
		}
		// TODO make message prettier
		// TODO implement email schema (http://www.bruceclay.com/blog/6-things-you-need-to-know-about-email-schema/)
		// Broken encrypted email code
		/*$start = uniqid();
		if(!file_put_contents(getenv('APP_ROOT_PATH').'tmp/'.$start.'.txt', '<html><head><title>Please activate your account</title></head><body>Mae govannen, '.$username.'!<br />Please click the link below to confirm your email address:<br /><a href="https://tvp.elementfx.com/confirm_email.php?token='.$token.'">http://tvp.elementfx.com/confirm_email.php?token='.$token.'</a></body></html>')) {
			return false;
		}
		
		$out = uniqid();
		// TODO move certs to a better place
		if (openssl_pkcs7_sign(getenv('APP_ROOT_PATH').'tmp/'.$start.'.txt', getenv('APP_ROOT_PATH').'tmp/'.$out.'.txt', getenv('APP_ROOT_PATH').'secret_stuff/webmaster@tvp.elementfx.com.crt', array(.getenv('APP_ROOT_PATH').'secret_stuff/webmaster@tvp.elementfx.com.pem', 'o8yLbhQydS3LkJj8Aak5jL8lIaxan8bC'),
			array('To' => $address,
				'From' => 'The Valar Project <noreply@tvp.elementfx.com>',
				'Subject' => 'Please activate your account',
				'MIME-Version' => '1.0',
				'Content-type' => 'text/html; charset=iso-8859-1'))) {
			exec(ini_get('sendmail_path').' < '.getenv('APP_ROOT_PATH').'tmp/'.$out.'.txt');
			return true;
		}
		return false;*/
		return mail($address, 'Please activate your account', '<html><head><title>Please activate your account</title></head><body>Mae govannen, '.$username.'!<br />Please click the link below to confirm your email address:<br /><a href="http://tvp.elementfx.com/confirm_email.php?token='.$token.'">http://tvp.elementfx.com/confirm_email.php?token='.$token.'</a></body></html>', 'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset=iso-8859-1'."\r\n".'From: The Valar Project <noreply@tvp.elementfx.com>');
	}
	
	function confirmUserEmail($token) {
		if(strlen($token) != 32 || !ctype_alnum($token)) {
			return null;
		}
		
		$stmt = $this->db->prepare('SELECT `userid` FROM `emailConfirmations` WHERE `token` = :token');
		$stmt->bindParam(':token', $token, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return null;
		}
		$userid = $row['userid'];
		
		$stmt = $this->db->prepare('UPDATE `loginData` SET `emailVerified` = 1 WHERE `userid` = :userid');
		$stmt->bindParam(':userid', $userid, PDO::PARAM_INT);
		if(!$stmt->execute()) {
			return null;
		}
		
		$stmt = $this->db->prepare('DELETE FROM `emailConfirmations` WHERE `token` = :token');
		$stmt->bindParam(':token', $token, PDO::PARAM_STR);
		if(!$stmt->execute()) {
			return null;
		}
		
		return $this->getUserEmail($userid);
	}
	
	// Gets the number of recent attempts an ip has made to login
	function getAttempts($ipAddress) {
		$stmt = $this->db->prepare('SELECT SUM(`failedAttempts`) AS attempts FROM `sessions` WHERE `ipAddress` = :ip AND `lastAttemptTime`+5*60 < NOW()');
		$stmt->bindParam(':ip', $ipAddress, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row) {
			return 0;
		}
		
		return $row['attempts'];
	}
	
	// Get nonce and stuff for logging in
	function getAuth($ipAddress) {
		if(!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
			return null;
		}
		
		$stmt = $this->db->prepare('SELECT `userid`, `opaque`, `nonce`, `cnonce` FROM `sessions` WHERE `ipAddress` = :ip');
		$stmt->bindParam(':ip', $ipAddress, PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!$row || $row['cnonce'] != 0) {
			// Generate opaque
			$opaque = hash('sha256', uniqid());
			// Generate nonce
			$nonce = hash('sha256', uniqid());
			
			// Add entry to database
			$stmt = $this->db->prepare('INSERT INTO `sessions` (`opaque`, `nonce`, `ipAddress`) VALUES (:opaque, :nonce, :ip)');
			$stmt->bindParam(':opaque', $opaque, PDO::PARAM_STR);
			$stmt->bindParam(':nonce', $nonce, PDO::PARAM_STR);
			$stmt->bindParam(':ip', $ipAddress, PDO::PARAM_STR);
			$result = $stmt->execute();
			
			$res = ['opaque' => $opaque, 'nonce' => $nonce, 'cnonce' => 0, 'signedIn' => FALSE];
		}
		else {
			$res = ['opaque' => $row['opaque'], 'nonce' => $row['nonce'], 'cnonce' => $row['cnonce'], 'signedIn' => (!is_null($row['userid']) && !$row['userid'] < 0)];
		}
		
		return $res;
	}
	
	function createNewRecoveryToken($userid, $address) {
		if(!$this->slowEqual($address, $this->getUserEmail($userid))) {
			return false;
		}
		
		// TODO Generate token
		
		// Add entry to database
		// TODO finish this:
		$stmt = $this->db->prepare('INSERT INTO `token` (`opaque`, `nonce`, `ipAddress`) VALUES (:opaque, :nonce, :ip)');
		$stmt->bindParam(':opaque', $opaque, PDO::PARAM_STR);
		$stmt->bindParam(':nonce', $nonce, PDO::PARAM_STR);
		$stmt->bindParam(':ip', $ipAddress, PDO::PARAM_STR);
		if(!$stmt->execute()) {
			return false;
		}
		
		return true;
	}
	
	function logout() {
		
	}
}

?>