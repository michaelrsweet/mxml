<?
//
// "$Id: auth.php,v 1.8 2004/05/20 02:04:45 mike Exp $"
//
// Authentication functions for PHP pages...
//
// Contents:
//
//   auth_current()    - Return the currently logged in user...
//   auth_login()      - Log a user into the system.
//   auth_logout()     - Logout of the current user by clearing the session ID.
//   auth_user_email() - Return the email address of a user...
//

//
// Include necessary headers...
//

include_once "db.php";


//
// Define authorization levels...
//

define("AUTH_USER", 0);
define("AUTH_DEVEL", 50);
define("AUTH_ADMIN", 100);


//
// Store the current user in the global variable LOGIN_USER...
//

$LOGIN_LEVEL = 0;
$LOGIN_USER  = "";
$LOGIN_EMAIL = "";

auth_current();


//
// 'auth_current()' - Return the currently logged in user...
//

function				// O - Current username or ""
auth_current()
{
  global $_COOKIE, $_SERVER, $LOGIN_EMAIL, $LOGIN_LEVEL, $LOGIN_USER;


  // See if the SID cookie is set; if not, the user is not logged in...
  if (!array_key_exists("SID", $_COOKIE))
    return ("");

  // Extract the "username:hash" from the SID string...
  $cookie = explode(':', $_COOKIE["SID"]);

  // Don't allow invalid values...
  if (count($cookie) != 2)
    return ("");

  // Lookup the username in the users table and compare...
  $result = db_query("SELECT * FROM users WHERE "
                    ."name='".db_escape($cookie[0])."' AND "
		    ."is_published = 1");
  if (db_count($result) == 1 && ($row = db_next($result)))
  {
    // Compute the session ID...
    $sid = md5("$_SERVER[REMOTE_ADDR]:$row[hash]");

    // See if it matches the cookie value...
    if ($cookie[1] == $sid)
    {
      // Refresh the cookies so they don't expire...
      setcookie("SID", "$cookie[0]:$sid", time() + 90 * 86400, "/");
      setcookie("FROM", $row['email'], time() + 90 * 86400, "/");

      // Set globals...
      $LOGIN_USER      = $cookie[0];
      $LOGIN_LEVEL     = $row["level"];
      $LOGIN_EMAIL     = $row["email"];
      $_COOKIE["FROM"] = $row["email"];

      // Return the current user...
      return ($cookie[0]);
    }
  }

  return ("");
}


//
// 'auth_login()' - Log a user into the system.
//

function				// O - Current username or ""
auth_login($name,			// I - Username
           $password)			// I - Password
{
  global $_COOKIE, $_SERVER, $LOGIN_EMAIL, $LOGIN_LEVEL, $LOGIN_USER;


  // Reset the user...
  $LOGIN_USER = "";

  // Lookup the username in the database...
  $result = db_query("SELECT * FROM users WHERE "
                    ."name='".db_escape($name)."' AND "
		    ."is_published = 1");
  if (db_count($result) == 1 && ($row = db_next($result)))
  {
    // Compute the hash of the name and password...
    $hash = md5("$name:$password");

    // See if they match...
    if ($row["hash"] == $hash)
    {
      // Update the username and email...
      $LOGIN_USER      = $name;
      $LOGIN_LEVEL     = $row["level"];
      $LOGIN_EMAIL     = $row["email"];
      $_COOKIE["FROM"] = $row["email"];

      // Compute the session ID...
      $sid = "$name:" . md5("$_SERVER[REMOTE_ADDR]:$hash");

      // Save the SID and email address cookies...
      setcookie("SID", $sid, time() + 90 * 86400, "/");
      setcookie("FROM", $row['email'], time() + 90 * 86400, "/");
    }
  }

  return ($LOGIN_USER);
}


//
// 'auth_logout()' - Logout of the current user by clearing the session ID.
//

function
auth_logout()
{
  global $LOGIN_EMAIL, $LOGIN_LEVEL, $LOGIN_USER;


  $LOGIN_USER  = "";
  $LOGIN_EMAIL = "";
  $LOGIN_LEVEL = 0;

  setcookie("SID", "", time() + 90 * 86400, "/");
}


//
// 'auth_user_email()' - Return the email address of a user...
//

function				// O - Email address
auth_user_email($username)		// I - Username
{
  $result = db_query("SELECT * FROM users WHERE "
                    ."name = '" . db_escape($username) . "'");
  if (db_count($result) == 1)
  {
    $row = db_next($result);
    $email = $row["email"];
  }
  else
    $email = "";

  db_free($result);

  return ($email);
}


//
// End of "$Id: auth.php,v 1.8 2004/05/20 02:04:45 mike Exp $".
//
?>
