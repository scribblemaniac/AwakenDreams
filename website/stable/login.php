<?php
include('pageContent.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
  <title>Login - The Valar Project</title>
  <?php head(); ?>
  <style>
.noTop {
 border-top: none !important;
}

.noBottom {
 border-bottom: none !important;
}
  </style>
  <script type="text/javascript" src="/resources/scripts/sha512.js"></script>
  <script type="text/javascript">
var auth = JSON.parse('<?php echo json_encode($dbm->getAuth($_SERVER['REMOTE_ADDR'])); ?>');

function hasSessionStorage() {
	try {
		var storage = window["sessionStorage"],	x = '__storage_test__';
		storage.setItem(x, x);
		storage.removeItem(x);
		return true;
	}
	catch(e) {
		return false;
	}
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1,c.length);
		}
		if (c.indexOf(nameEQ) == 0) {
			return c.substring(nameEQ.length,c.length);
		}
	}
	return null;
}

function setPrivate(key, value) {
	if(hasSessionStorage()) {
		sessionStorage.setItem(key, value);
	}
	else {
		document.cookie = key + "=" + value;
	}
}

function getPrivate(key) {
	if(hasSessionStorage()) {
		return sessionStorage.getItem(key);
	}
	else {
		return readCookie(key);
	}
}

function submitLogin() {
	var realm = "TVP";
	var username = $("#username").val();
	var password = $("#password").val();
	var message = username + realm + password;
	
	var shaObj = new jsSHA("SHA-512", "TEXT");
	shaObj.update(message);
	var subhash = "";
	subhash = shaObj.getHash("BYTES");
	for(var i = 1; i < 4095; i++) {
		shaObj = new jsSHA("SHA-512", "BYTES");
		shaObj.setHMACKey(message, "TEXT");
		shaObj.update(subhash);
		subhash = shaObj.getHMAC("BYTES");
	}
	shaObj = new jsSHA("SHA-512", "BYTES");
	shaObj.setHMACKey(message, "TEXT");
	shaObj.update(subhash);
	subhash = shaObj.getHMAC("HEX");
	
	shaObj = new jsSHA("SHA-512", "TEXT");
	shaObj.setHMACKey(subhash, "TEXT");
	shaObj.update(auth.nonce + auth.cnonce);
	var hash = shaObj.getHMAC("HEX");
	
	$.post("/resources/serverside_scripts/login_manager.php", { op: "login", username: username, opaque: auth.opaque, hmac: hash }, function(data) {
		if(data.status == "success") {
			document.cookie = "opaque=" + auth.opaque;
			document.cookie = "cnonce=1";
			setPrivate("username", username);
			setPrivate("hmac", hash);
			setPrivate("nonce", auth.nonce);
			$("#login_feedback").html("You are now logged in as " + username);
		}
		else {
			$("#login_feedback").html("Sorry, login has failed. Please try again.");
		}
	});
	
	//$("#login_form,#signup_form").hide();
	$("#login_feedback").html("Logging in, please be patient as this can take many seconds.").show();
}

function verifyUsername(async) {
	async = typeof async == "undefined" ? true : async;
	var username_input = $("#signup_username");
	var username = username_input.val();
	var username_feedback = $("#signup_username_feedback");
	var username_pattern = new RegExp("^[A-Za-z0-9]*$");
	if(username == "") {
		username_feedback.html("Username cannot be empty.");
		username_input.addClass("invalid");
		return;
	}
	else if(username.length < 4) {
		username_feedback.html("Usernames must be at least 4 characters long.");
		username_input.addClass("invalid");
		return;
	}
	else if(username.length > 16) {
		username_feedback.html("Usernames must be no longer than 16 characters long.");
		username_input.addClass("invalid");
		return;
	}
	else if(!username_pattern.test(username)) {
		username_feedback.html("Usernames ust be made up of only letters and numbers.");
		username_input.addClass("invalid");
		return;
	}
	else {
		username_feedback.html("");
		username_input.removeClass("invalid");
	}
	$.post({ url: "/resources/serverside_scripts/login_manager.php", data: { op: "username_validate", username: username }, dataType: "text", async: async, success: function(rawData) {
		var data = null;
		try {
			// Parse the server's response as JSON
			data = JSON.parse(rawData);
		}
		catch(err) {}
		
		if(data == null || data.status == null || data.status != "success" || data.result == null) {
			// If we get an error, we must assume the username is not okay
			username_feedback.html("Username is already taken.");
			username_input.addClass("invalid");
		}
		else {
			if(data.result) { // Username is free
				username_feedback.html("");
				username_input.removeClass("invalid");
			}
			else { // Username is taken
				if(username_feedback != null) {
					username_feedback.html("Username is already taken.");
				}
				username_input.addClass("invalid");
			}
		}
	}});
}

$(document).ready(function() {
	if(auth.signedIn) {
		$("#login_form").hide();
		// TODO add logout option
		$("#pageContents").append("You are already signed in.");
		return;
	}
	$("#login_submit").click(submitLogin);
	$("#login_form").submit(function(e) { submitLogin(); e.preventDefault(); });
});
  </script>
 </head>
 <body>
  <?php bodyStart(); ?>
  <form id="login_form">
   <h1>Login</h1>
   <p id="login_feedback"></p>
   <table>
    <tbody>
     <tr>
      <td>Username:&nbsp;</td>
      <td><div class="text-wrapper noBottom"><input type="text" name="username" id="username" /></div></td>
     </tr>
    <tr>
      <td>Password:&nbsp;</td>
      <td><div class="text-wrapper noTop"><input type="password" name="password" id="password" /></div></td>
     </tr>
    </tbody>
   </table>
   <p><a class="hiddenLink">Forgot password</a></p>
   <button id="login_submit">Login</button>
  </form>
  <?php bodyEnd(); ?>
 </body>
</html>