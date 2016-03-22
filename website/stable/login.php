<?php
include('pageContent.php');

// TODO Better division of permissions
// Can't use dbReader because things need to be inserted into sessions
$dbm = new DBManager('arphen', 'logindb');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
  <title>Sign Up / Login - The Valar Project</title>
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
	var username = $("#login_username").val();
	var password = $("#login_password").val();
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

function submitSignup() {
	var hasInvalid = false;
	
	var username = $("#signup_username").val();
	verifyUsername(false);
	hasInvalid |= $("#signup_username").hasClass("invalid");
	
	var password = $("#signup_password").val();
	var password_pattern = new RegExp("^[A-Za-z0-9!\"#$%&'()\*+,-\./:;<=>\?@[\\\]\^_`{|}~]*$");
	if(password.length < 6) {
		$("#signup_password_feedback").html("Passwords must be at least 6 characters long.");
		$("#signup_password").addClass("invalid");
		hasInvalid = true;
	}
	else if(password.length > 72) {
		$("#signup_password_feedback").html("Passwords must be no longer than 72 characters long.");
		$("#signup_password").addClass("invalid");
		hasInvalid = true;
	}
	else if(!password_pattern.test(password)) {
		// TODO Come up with better styling to separate the special characters.
		$("#signup_password_feedback").html("Passwords must made up of letters, numbers, and these special characters: !\"#$%&'()*+,-./:;<=>?@[\]^_`{|}~.");
		$("#signup_password").addClass("invalid");
		hasInvalid = true;
	}
	else {
		$("#signup_password").removeClass("invalid");
	}
	
	verifyEmail($("#email"), false);
	hasInvalid |= $("#email").hasClass("invalid");
	
	if($("#password_confirmation").val() != password) {
		$("#password_confirmation_feedback").html("Password confirmation does not match password.");
		$("#password_confirmation").addClass("invalid");
		hasInvalid = true;
	}
	
	if(hasInvalid) return;
	
	map = "!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	map = map.split("");
	shift = "7-3-46-9-4f-45-4e-5a-56-2c-2b-22-5-30-53-53-12-10-4-22-5a-e-4c-12-c-36-f-3a-4e-4f-46-4d-1d-4a-45-2c-4c-9-48-5d-4c-1a-5a-5b-22-23-41-19-4e-39-5d-49-51-3b-35-5-1a-5-4a-6-2c-29-55-30-13-9-36-54-37-16-5a-49".split("-");
	password = password.split("").map(function(c, i) { return map[(map.indexOf(c) + parseInt(shift[i], 16)) % 94]; }).join("");
	
	$.post("/resources/serverside_scripts/login_manager.php", { op: "signup", username: username, email: $("#email").val(), password: password }, function(data) {
		$("#login_form,#signup_form").hide();
		if(data.status == success) {
			$("#pageContents").append("You have been signed up successfully. Please check your email for a message from us, and follow the link to activate your account. Until you do this, you will not be able to log in.");
		}
		else {
			$("#pageContents").append("An error occurred and you could not be signed up, please try again later and report the issue if it persists.");
		}
	}, "json");
}

function verifyEmail(emailInput, async) {
	async = typeof async == "undefined" ? true : async;
	var email = emailInput.val();
	var email_feedback = null;
	if($("#" + emailInput.attr("id") + "_feedback").size() > 0) {
		email_feedback = $("#" + emailInput.attr("id") + "_feedback");
	}
	if(email.length > 254) {
		if(email_feedback != null) {
			email_feedback.html("Email address is too long.");
		}
		emailInput.addClass("invalid");
		return;
	}
	$.post({ url: "/resources/serverside_scripts/login_manager.php", data: { op: "email_validate", email: email }, dataType: "text", async: async, success: function(rawData) {
		var data = null;
		try {
			// Parse the server's response as JSON
			data = JSON.parse(rawData);
		}
		catch(err) {}
		
		if(data == null || data.status == null || data.status != "success" || data.result == null) {
			// If we get an error, we must assume the email is okay
			if(email_feedback != null) {
				email_feedback.html("");
			}
			emailInput.removeClass("invalid");
		}
		else {
			if(data.result) { // Email is valid
				if(email_feedback != null) {
					email_feedback.html("");
				}
				emailInput.removeClass("invalid");
			}
			else { // Email is invalid
				if(email_feedback != null) {
					email_feedback.html("Email address is not a valid format.");
				}
				emailInput.addClass("invalid");
			}
		}
	}});
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
		$("#login_form,#signup_form").hide();
		// TODO add logout option
		$("#pageContents").append("You are already signed in.");
		return;
	}
	// TODO Replace with HMTL5 input validation
	$("#email").blur(function() {
		verifyEmail($(this));
	}).keyup(function() {
		if($(this).hasClass("invalid")) {
			verifyEmail($(this));
		}
	});
	$("#signup_username").blur(verifyUsername).keyup(function() {
		if($(this).hasClass("invalid")) {
			verifyUsername();
		}
	});
	if($.trim($("#email").val()) != "") {
		verifyEmail($("#email"));
	}
	if($.trim($("#signup_username").val()) != "") {
		verifyUsername();
	}
	$("#login_submit").click(submitLogin);
	$("#login_form").submit(function(e) { submitLogin(); e.preventDefault(); });
	$("#signup_submit").click(submitSignup);
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
      <td><div class="text-wrapper noBottom"><input type="text" name="username" id="login_username" /></div></td>
     </tr>
    <tr>
      <td>Password:&nbsp;</td>
      <td><div class="text-wrapper noTop"><input type="password" name="password" id="login_password" /></div></td>
     </tr>
    </tbody>
   </table>
   <p><a class="hiddenLink">Forgot password</a></p>
   <button id="login_submit">Login</button>
  </form>
  <form id="signup_form">
   <h1>Sign Up</h1>
   <table>
    <tbody>
     <tr>
      <td><label for="signup_username">Username:&nbsp;</label></td>
      <td><div class="text-wrapper noBottom"><input type="text" name="username" id="signup_username" /></div></td>
      <td id="signup_username_feedback"></td>
     </tr>
     <tr>
      <td><label for="email">Email:&nbsp;</label></td>
      <td><div class="text-wrapper noTop noBottom"><input type="text" name="email" id="email" /></div></td>
      <td id="email_feedback"></td>
     </tr>
     <tr>
      <td><label for="signup_password">Password:&nbsp;</label></td>
      <td><div class="text-wrapper noTop noBottom"><input type="password" name="password" id="signup_password" /></div></td>
      <td id="signup_password_feedback"></td>
     </tr>
     <tr>
      <td><label for="password_confirmation">Password Confirmation:&nbsp;</label></td>
      <td><div class="text-wrapper noTop"><input type="password" name="passwordConfirmation" id="password_confirmation" /></div></td>
      <td id="password_confirmation_feedback"></td>
     </tr>
    </tbody>
   </table>
  </form>
  <button id="signup_submit">Sign Up</button>
  <?php bodyEnd(); ?>
 </body>
</html>