<?php
//
// "$Id: account.php,v 1.1 2004/05/17 20:28:52 mike Exp $"
//
//

//
// Include necessary headers...
//

include_once "phplib/html.php";

if ($argc == 1 && $argv[0] == "X")
  auth_logout();

if ($LOGIN_USER == "")
{
  header("Location: login.php");
  exit(0);
}

if ($argc == 1)
  $op = "$argv[0]";
else
  $op = "L";

switch ($op)
{
  case 'L' :
      // List
      html_header("New/Pending");

      html_start_links(1);
      html_link("New/Pending", "$PHP_SELF?L");
      html_link("Change Password", "$PHP_SELF?P");
      html_link("Logout", "$PHP_SELF?X");
      html_end_links();

      print("<h1>New/Pending</h1>\n");

      print("<h2>New/Pending Articles:</h2>\n");

      print("<h2>New/Pending STRs:</h2>\n");

      html_footer();
      break;

  case 'P' :
      // Change password
      html_header("Change Password");

      html_start_links(1);
      html_link("New/Pending", "$PHP_SELF?L");
      html_link("Change Password", "$PHP_SELF?P");
      html_link("Logout", "$PHP_SELF?X");
      html_end_links();

      print("<h1>Change Password</h1>\n");

      if ($REQUEST_METHOD == "POST" &&
          array_key_exists("PASSWORD", $_POST) &&
	  array_key_exists("PASSWORD2", $_POST) &&
	  $_POST["PASSWORD"] == $_POST["PASSWORD2"])
      {
        // Store new password and re-login...
	print("<p>Password changed successfully!</p>\n");
      }
      else
      {
	print("<form method='POST' action='$PHP_SELF?P'>"
	     ."<p><table width='100%'>\n"
	     ."<tr><th align='right'>Password:</th>"
	     ."<td><input type='password' name='PASSWORD' size='16' "
	     ."maxsize='255'/></td></tr>\n"
	     ."<tr><th align='right'>Password Again:</th>"
	     ."<td><input type='password' name='PASSWORD2' size='16' "
	     ."maxsize='255'/></td></tr>\n"
	     ."<tr><th></th><td><input type='submit' value='Change Password'/>"
	     ."</td></tr>\n"
             ."</table></p></form>\n");
      }

      html_footer();
      break;
}


//
// End of "$Id: account.php,v 1.1 2004/05/17 20:28:52 mike Exp $".
//
?>
