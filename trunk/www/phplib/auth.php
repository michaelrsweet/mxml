<?
//
// "$Id: auth.php,v 1.2 2004/05/17 21:00:42 mike Exp $"
//
// Authentication functions for PHP pages...
//
// Contents:
//
//   auth_current() - Return the currently logged in user...
//   auth_login()   - Log a user into the system.
//   auth_logout()  - Logout of the current user by clearing the session ID.
//

//
// Include necessary headers...
//

include_once "db.php";


//
// Store the current user in the global variable LOGIN_USER...
//

$LOGIN_LEVEL = 0;
$LOGIN_USER  = auth_current();


//
// 'auth_current()' - Return the currently logged in user...
//

function				// O - Current username or ""
auth_current()
{
  global $_COOKIE, $_SERVER, $LOGIN_LEVEL;


  // See if the SID cookie is set; if not, the user is not logged in...
  if (!array_key_exists("SID", $_COOKIE))
    return ("");

  // Extract the "username:hash" from the SID string...
  $cookie = explode(':', $_COOKIE["SID"]);

  // Don't allow invalid values...
  if (count($cookie) != 2)
    return ("");

  // Lookup the username in the users table and compare...
  $result = db_query("SELECT * FROM users WHERE name='".db_escape($cookie[0])."'");
  if (db_count($result) == 1 && ($row = db_next($result)))
  {
    // Compute the session ID...
    $sid = md5("$_SERVER[REMOTE_ADDR]:$row[hash]");

    // See if it matches the cookie value...
    if ($cookie[1] == $sid)
    {
      $LOGIN_LEVEL     = $row["level"];
      $_COOKIE["FROM"] = $row["email"];
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
  global $_COOKIE, $_SERVER, $LOGIN_USER;


  // Reset the user...
  $LOGIN_USER = "";

  // Lookup the username in the database...
  $result = db_query("SELECT * FROM users WHERE name='".db_escape($name)."'");
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
  global $LOGIN_USER;


  $LOGIN_USER  = "";
  $LOGIN_LEVEL = 0;

  setcookie("SID", "", time() + 90 * 86400, "/");
}


//
// End of "$Id: auth.php,v 1.2 2004/05/17 21:00:42 mike Exp $".
//
?>
