<?php
include('pageContent.php');

// TODO Better division of permissions
// Can't use dbReader because things need to be inserted into sessions
$dbm = new DBManager('arphen', 'logindb');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
 <head>
  <title>Password Recovery - The Valar Project</title>
  <?php head(); ?>
  <style>
.noTop {
 border-top: none !important;
}

.noBottom {
 border-bottom: none !important;
}

#recovery_feedback {
 display: none;
}
  </style>
  <script>
function submitRecovery() {
	var hasInvalid = false;
	
	var username = $("#username").val();
	verifyUsername(false);
	hasInvalid |= $("#username").hasClass("invalid");
	
	verifyEmail($("#email"), false);
	hasInvalid |= $("#email").hasClass("invalid");
	
	if(hasInvalid) return;
	
	$.post({ url: "/resources/serverside_scripts/login_manager.php", data: { op: "password_recovery", username: username, email: email }, success: function(data) {
		if(data.status == "success") {
			$("#recovery_feedback").html("The email has been sent! Please check your inbox for further instructions.").show();
		}
		else {
			$("#recovery_feedback").html("An error occurred. Please make sure you typed your username and email correctly.").show();
		}
	}, error: function() {
		$("#recovery_feedback").html("An error occurred. Please make sure you typed your username and email correctly.").show();
	}});
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
	var username_input = $("#username");
	var username = username_input.val();
	var username_feedback = $("#username_feedback");
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
	$("#recovery").submit(function(e) {
		submitRecovery();
		e.preventDefault();
	});
	$("#recovery_submit").click(function() {
		submitRecovery();
	}

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
});
  </script>
 </head>
 <body>
  <?php bodyStart(); ?>
  <h1>Password Recovery</h1>
  <p>Have you forgot your password? No worries, just put your username and the email address associated with your account below and we'll send you a link to reset your password.</p>
  <form id="recovery">
   <div id="recovery_feedback"></div>
   <table>
    <tbody>
     <tr>
      <td><label for="username">Username:&nbsp;</label></td>
      <td><div class="text-wrapper noBottom"><input type="text" name="username" id="username" /></div></td>
      <td id="username_feedback"></td>
     </tr>
     <tr>
      <td><label for="email">Email:&nbsp;</label></td>
      <td><div class="text-wrapper noTop"><input type="text" name="email" id="email" /></div></td>
      <td id="email_feedback"></td>
     </tr>
     <tr>
      <td><button id="recovery_submit">Recover</button></td>
     </tr>
    </tbody>
   </table>
  </form>
  <?php bodyEnd(); ?>
 </body>
</html>