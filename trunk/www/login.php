<?php
//
// "$Id: login.php,v 1.2 2004/05/19 01:39:04 mike Exp $"
//
//

//
// Include necessary headers...
//

include_once "phplib/html.php";


if ($REQUEST_METHOD == "POST" &&
    array_key_exists("USERNAME", $_POST) &&
    array_key_exists("PASSWORD", $_POST))
  auth_login($_POST["USERNAME"], $_POST["PASSWORD"]);

if ($LOGIN_USER != "")
  header("Location: account.php");
else
{
  html_header("Login");

  print("<h1>Login</h1>\n"
       ."<p>Logins are currently only available to Mini-XML developers. "
       ."If you are not a Mini-XML developer, please return to the "
       ."<a href='index.php'>home page</a> and choose a different "
       ."link.</p>\n"
       ."<form method='POST' action='$PHP_SELF'>"
       ."<p><table width='100%'>\n"
       ."<tr><th align='right'>Username:</th>"
       ."<td><input type='text' name='USERNAME' size='16' maxsize='255'");

  if (array_key_exists("USERNAME", $_POST))
    print(" value='" . htmlspecialchars($_POST["USERNAME"], ENT_QUOTES) . "'");

  print("/></td></tr>\n"
       ."<tr><th align='right'>Password:</th>"
       ."<td><input type='password' name='PASSWORD' size='16' maxsize='255'/>"
       ."</td></tr>\n"
       ."<tr><th></th><td><input type='submit' value='Login'/></td></tr>\n"
       ."</table></p></form>\n");

  html_footer();
}


//
// End of "$Id: login.php,v 1.2 2004/05/19 01:39:04 mike Exp $".
//
?>
