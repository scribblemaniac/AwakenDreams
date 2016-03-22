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
#recovery_feedback {
 display: none;
}
  </style>
  <script>
$(document).ready(function() {

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
      <td><label for="password">New password:&nbsp;</label></td>
      <td><div class="text-wrapper noBottom"><input type="text" name="password" id="password" /></div></td>
      <td id="password_feedback"></td>
     </tr>
     <tr>
      <td><label for="password_confirmation">Confirm new password:&nbsp;</label></td>
      <td><div class="text-wrapper noBottom"><input type="text" id="password_confirmation" /></div></td>
      <td id="password_confirmation_feedback"></td>
     </tr>
     <tr>
      <td><button id="recovery_submit">Set New Password</button></td>
     </tr>
    </tbody>
   </table>
  </form>
  <?php bodyEnd(); ?>
 </body>
</html>