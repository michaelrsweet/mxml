<?php
//
// "$Id$"
//
// Login/registration form...
//


//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";

$usererror = "";

if (array_key_exists("PAGE", $_GET))
  $page = $_GET["PAGE"];
else if (array_key_exists("PAGE", $_POST))
  $page = $_POST["PAGE"];
else
  $page = "account.php";

if ($REQUEST_METHOD == "POST")
{
  if (array_key_exists("USERNAME", $_POST))
    $username = $_POST["USERNAME"];
  else
    $username = "";

  if (array_key_exists("PASSWORD", $_POST))
    $password = $_POST["PASSWORD"];
  else
    $password = "";

  if (array_key_exists("PASSWORD2", $_POST))
    $password2 = $_POST["PASSWORD2"];
  else
    $password2 = "";

  if (array_key_exists("EMAIL", $_POST))
    $email = $_POST["EMAIL"];
  else
    $email = "";

  if (array_key_exists("REGISTER", $_POST))
    $register = $_POST["REGISTER"];
  else
    $register = "";

  if ($username != "" && !eregi("^[-a-z0-9._]+\$", $username))
    $usererror = "Bad username - only letters, numbers, '.', '-', and '_' "
                ."are allowed!";
  else if ($argc == 1 && $argv[0] == "A" && $username != "" &&
           $password != "" && $password == $password2 &&
           $email != "" && validate_email($email))
  {
    // Good new account request so far; see if account already
    // exists...
    $name   = db_escape($username);
    $result = db_query("SELECT * FROM users WHERE name='$name'");
    if (db_count($result) == 0)
    {
      // Nope, add unpublished user account and send registration email.
      db_free($result);

      $hash   = md5("$username:$password");
      $demail = db_escape($email);
      $date   = time();

      db_query("INSERT INTO users VALUES(NULL, 0, '$name', '$demail', '$hash', "
              ."0, $date, '$name', $date, '$name')");

      $userid = db_insert_id();
      $hash   = md5("$userid:$hash");

      mail($email, "$PROJECT_NAME User Registration",
           wordwrap("Thank you for requesting an account on the $PROJECT_NAME "
	           ."home page.  To complete your registration, go to the "
		   ."following URL:\n\n"
		   ."    $PHP_URL?E\n\n"
		   ."and enter your username ($username), password, and the "
		   ."following registration code:\n\n"
		   ."    $hash\n\n"
		   ."You will then be able to access your account.\n"),
	   "From: $PROJECT_EMAIL\r\n");

      html_header("Login Registration");

      html_start_links(1);
      html_link("Enable Account", "$PHP_SELF?E");
      html_end_links();

      print("Thank you for requesting an account.  You should receive an "
	   ."email from $PROJECT_EMAIL shortly with instructions on "
	   ."completing your registration.</p>\n");
      html_footer();
      exit();
    }

    db_free($result);

    $usererror = "Username already exists!";
  }
  else if ($argc == 1 && $argv[0] == "E" && $username != "" &&
           $password != "" && $register != "")
  {
    // Check that we have an existing user account...
    $name   = db_escape($username);
    $result = db_query("SELECT * FROM users WHERE name='$name'");
    if (db_count($result) == 1)
    {
      // Yes, now check the registration code...
      $row  = db_next($result);
      $hash = md5("$row[id]:$row[hash]");
      
      if ($hash == $register)
      {
        // Good code, enable the account and login...
	db_query("UPDATE users SET is_published = 1 WHERE name='$name'");

	if (auth_login($username, $password) == "")
	{
	  db_query("UPDATE users SET is_published = 0 WHERE name='$name'");
	  $usererror = "Login failed!";
	}
      }
      else
        $usererror = "Bad registration code!";
    }
    else
      $usererror = "Username not found!";

    db_free($result);
  }
  else if ($argc == 0 && $username != "" && $password != "")
    if (auth_login($username, $password) == "")
      $usererror = "Login failed!";
}
else
{
  $username  = "";
  $password  = "";
  $password2 = "";
  $email     = "";
  $register  = "";
}

if ($LOGIN_USER != "")
  header("Location: $page");
else if ($argc == 0 || $argv[0] != "E")
{
  // Header + start of table...
  html_header("Login");

  print("<table width='100%' height='100%' border='0' cellpadding='0' "
       ."cellspacing='0'>\n"
       ."<tr><td valign='top'>\n");

  // Existing users...
  print("<h1>Current Users</h1>\n");

  if ($argc == 0 && $usererror != "")
    print("<p><b>$usererror</b></p>\n");

  $page = htmlspecialchars($page, ENT_QUOTES);

  print("<p>If you are a registered $PROJECT_NAME developer, please enter "
       ."your username and password to login:</p>\n"
       ."<form method='POST' action='$PHP_SELF'>"
       ."<input type='hidden' name='PAGE' value='$page'>"
       ."<table width='100%'>\n"
       ."<tr><th align='right'>Username:</th>"
       ."<td><input type='text' name='USERNAME' size='16' maxsize='255'");

  if (array_key_exists("USERNAME", $_POST))
    print(" value='" . htmlspecialchars($_POST["USERNAME"], ENT_QUOTES) . "'");

  print("></td></tr>\n"
       ."<tr><th align='right'>Password:</th>"
       ."<td><input type='password' name='PASSWORD' size='16' maxsize='255'>"
       ."</td></tr>\n"
       ."<tr><th></th><td><input type='submit' value='Login'></td></tr>\n"
       ."</table></form>\n");

  // Separator...
  print("</td>"
       ."<td>&nbsp;&nbsp;&nbsp;&nbsp;"
       ."<img src='images/black.gif' width='1' height='80%' alt=''>"
       ."&nbsp;&nbsp;&nbsp;&nbsp;</td>"
       ."<td valign='top'>\n");

  // New users...
  print("<h1>New Users</h1>\n");

  if ($argc == 1 && $usererror != "")
    print("<p><b>$usererror</b></p>\n");

  $username = htmlspecialchars($username, ENT_QUOTES);
  $email    = htmlspecialchars($email, ENT_QUOTES);

  print("<p>If you are a not registered $PROJECT_NAME developer, please fill "
       ."in the form below to register. An email will be sent to the "
       ."address you supply to confirm the registration:</p>\n"
       ."<form method='POST' action='$PHP_SELF?A'>"
       ."<input type='hidden' name='PAGE' value='$page'>"
       ."<table width='100%'>\n"
       ."<tr><th align='right'>Username:</th>"
       ."<td><input type='text' name='USERNAME' size='16' maxsize='255' "
       ." value='$username'></td></tr>\n"
       ."<tr><th align='right'>EMail:</th>"
       ."<td><input type='text' name='EMAIL' size='16' maxsize='255' "
       ." value='$email'></td></tr>\n"
       ."<tr><th align='right'>Password:</th>"
       ."<td><input type='password' name='PASSWORD' size='16' maxsize='255'>"
       ."</td></tr>\n"
       ."<tr><th align='right'>Password Again:</th>"
       ."<td><input type='password' name='PASSWORD2' size='16' maxsize='255'>"
       ."</td></tr>\n"
       ."<tr><th></th><td><input type='submit' value='Request Account'></td></tr>\n"
       ."</table></form>\n");

  // End table
  print("</td></tr>\n"
       ."</table>\n");

  html_footer();
}
else
{
  html_header("Enable Account");

  if ($usererror != NULL)
    print("<p><b>$usererror</b></p>\n");

  $username = htmlspecialchars($username, ENT_QUOTES);
  $register = htmlspecialchars($register, ENT_QUOTES);

  print("<p>Please enter the registration code that was emailed to you "
       ."with your username and password to enable your account and login:</p>\n"
       ."<form method='POST' action='$PHP_SELF?E'>"
       ."<center><table width='100%'>\n"
       ."<tr><th align='right'>Registration Code:</th>"
       ."<td><input type='text' name='REGISTER' size='32' maxsize='32' "
       ."value = '$register'>"
       ."</td></tr>\n"
       ."<tr><th align='right'>Username:</th>"
       ."<td><input type='text' name='USERNAME' size='16' maxsize='255' "
       ."value='$username'></td></tr>\n"
       ."<tr><th align='right'>Password:</th>"
       ."<td><input type='password' name='PASSWORD' size='16' maxsize='255'>"
       ."</td></tr>\n"
       ."<tr><th></th><td><input type='submit' value='Enable Account'></td></tr>\n"
       ."</table></center></form>\n");

  html_footer();
}


//
// End of "$Id$".
//
?>
