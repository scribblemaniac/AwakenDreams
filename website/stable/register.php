<?php
include('pageContent.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
  <title>Register - The Valar Project</title>
  <?php head(); ?>
  <style>
.noTop {
 border-top: none !important;
}

.noBottom {
 border-bottom: none !important;
}
  </style>
  <script type="text/javascript">
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

function submitRegister() {
	var hasInvalid = false;
	
	var username = $("#username").val();
	verifyUsername(false);
	hasInvalid |= $("#username").hasClass("invalid");
	
	var password = $("#password").val();
	var password_pattern = new RegExp("^[A-Za-z0-9!\"#$%&'()\*+,-\./:;<=>\?@[\\\]\^_`{|}~]*$");
	if(password.length < 6) {
		$("#password_feedback").html("Passwords must be at least 6 characters long.");
		$("#password").addClass("invalid");
		hasInvalid = true;
	}
	else if(password.length > 72) {
		$("#password_feedback").html("Passwords must be no longer than 72 characters long.");
		$("#password").addClass("invalid");
		hasInvalid = true;
	}
	else if(!password_pattern.test(password)) {
		// TODO Come up with better styling to separate the special characters.
		$("#password_feedback").html("Passwords must made up of letters, numbers, and these special characters: !\"#$%&'()*+,-./:;<=>?@[\]^_`{|}~.");
		$("#password").addClass("invalid");
		hasInvalid = true;
	}
	else {
		$("#password").removeClass("invalid");
	}
	
	verifyEmail($("#email"), false);
	hasInvalid |= $("#email").hasClass("invalid");
	
	if($("#password_confirmation").val() != password) {
		$("#password_confirmation_feedback").html("Password confirmation does not match password.");
		$("#password_confirmation").addClass("invalid");
		hasInvalid = true;
	}
	
	if(hasInvalid) return;

	// Not really for security, just to harrass eavesdroppers until we get an SSL certificate
	map = "!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	map = map.split("");
	shift = "7-3-46-9-4f-45-4e-5a-56-2c-2b-22-5-30-53-53-12-10-4-22-5a-e-4c-12-c-36-f-3a-4e-4f-46-4d-1d-4a-45-2c-4c-9-48-5d-4c-1a-5a-5b-22-23-41-19-4e-39-5d-49-51-3b-35-5-1a-5-4a-6-2c-29-55-30-13-9-36-54-37-16-5a-49".split("-");
	password = password.split("").map(function(c, i) { return map[(map.indexOf(c) + parseInt(shift[i], 16)) % 94]; }).join("");
	
	$.post("/resources/serverside_scripts/login_manager.php", { op: "signup", username: username, email: $("#email").val(), password: password }, function(data) {
		if(data.status == success) {
			$("#pageContents").append("You have been registered up successfully. Please check your email for a message from us, and follow the link to activate your account. Until you do this, you will not be able to log in.");
		}
		else {
			$("#pageContents").append("An error occurred and you could not be registered, please try again later and report the issue if it persists.");
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
	var username = $("#username").val();
	var username_pattern = new RegExp("^[A-Za-z0-9]*$");
	if(username == "") {
		$("#username_feedback").html("Username cannot be empty.");
		$("#username").addClass("invalid");
		return;
	}
	else if(username.length < 4) {
		$("#username_feedback").html("Usernames must be at least 4 characters long.");
		$("#username").addClass("invalid");
		return;
	}
	else if(username.length > 16) {
		$("#username_feedback").html("Usernames must be no longer than 16 characters long.");
		$("#username").addClass("invalid");
		return;
	}
	else if(!username_pattern.test(username)) {
		$("#username_feedback").html("Usernames must be made up of only letters and numbers.");
		$("#username").addClass("invalid");
		return;
	}
	else {
		$("#username_feedback").html("");
		$("#username").removeClass("invalid");
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
			$("#username_feedback").html("Username is already taken.");
			$("#username").addClass("invalid");
		}
		else {
			if(data.result) { // Username is free
				$("#username_feedback").html("");
				$("#username").removeClass("invalid");
			}
			else { // Username is taken
				$("#username_feedback").html("Username is already taken.");
				$("#username").addClass("invalid");
			}
		}
	}});
}

$(document).ready(function() {
	if(auth.signedIn) {
		$("#register_form").hide();
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
	$("#username").blur(verifyUsername).keyup(function() {
		if($(this).hasClass("invalid")) {
			verifyUsername();
		}
	});
	if($.trim($("#email").val()) != "") {
		verifyEmail($("#email"));
	}
	if($.trim($("#username").val()) != "") {
		verifyUsername();
	}
	$("#register_submit").click(submitRegister);
	$("#register_form").submit(function(e) { submitRegister(); e.preventDefault(); });
});
  </script>
 </head>
 <body>
  <?php bodyStart(); ?>
  <form id="register_form">
   <h1>Register</h1>
   <table>
    <tbody>
     <tr>
      <td><label for="username">Username:&nbsp;</label></td>
      <td><div class="text-wrapper noBottom"><input type="text" name="username" id="username" /></div></td>
      <td id="username_feedback"></td>
     </tr>
     <tr>
      <td><label for="email">Email:&nbsp;</label></td>
      <td><div class="text-wrapper noTop noBottom"><input type="text" name="email" id="email" /></div></td>
      <td id="email_feedback"></td>
     </tr>
     <tr>
      <td><label for="password">Password:&nbsp;</label></td>
      <td><div class="text-wrapper noTop noBottom"><input type="password" name="password" id="password" /></div></td>
      <td id="password_feedback"></td>
     </tr>
     <tr>
      <td><label for="password_confirmation">Password Confirmation:&nbsp;</label></td>
      <td><div class="text-wrapper noTop"><input type="password" name="passwordConfirmation" id="password_confirmation" /></div></td>
      <td id="password_confirmation_feedback"></td>
     </tr>
    </tbody>
   </table>
   <button id="register_submit">Register</button>
  </form>
  <a href="signin.php">Already have an account? Log in here!</a>
  <?php bodyEnd(); ?>
 </body>
</html>