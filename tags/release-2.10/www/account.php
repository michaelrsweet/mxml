<?php
//
// "$Id$"
//
// Account management page...
//

//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";
include_once "phplib/str.php";


//
// Access levels...
//

$levels = array(
  AUTH_USER => "User",
  AUTH_DEVEL => "Devel",
  AUTH_ADMIN => "Admin"
);


//
// 'account_header()' - Show standard account page header...
//

function
account_header($title)
{
  global $PHP_SELF, $LOGIN_USER, $LOGIN_LEVEL;

  html_header("$title");

  html_start_links(1);
  html_link("$LOGIN_USER", "$PHP_SELF");
  html_link("Change Password", "$PHP_SELF?P");
  if ($LOGIN_LEVEL == AUTH_ADMIN)
    html_link("Manage Accounts", "$PHP_SELF?A");
  if ($LOGIN_LEVEL > AUTH_USER)
    html_link("New/Pending", "$PHP_SELF?L");
  html_link("Logout", "$PHP_SELF?X");
  html_end_links();
}


if ($argc == 1 && $argv[0] == "X")
  auth_logout();

if ($LOGIN_USER == "")
{
  header("Location: login.php");
  exit(0);
}

if ($argc >= 1)
{
  $op   = $argv[0][0];
  $data = substr($argv[0], 1);
}
else
  $op = "";

switch ($op)
{
  case 'A' :
      // Manage accounts...
      if ($LOGIN_LEVEL < AUTH_ADMIN)
      {
        header("Location: $PHP_SELF");
	exit();
      }

      if ($data == "add")
      {
	if ($REQUEST_METHOD == "POST")
	{
	  // Get data from form...
	  if (array_key_exists("IS_PUBLISHED", $_POST))
	    $is_published = (int)$_POST["IS_PUBLISHED"];
	  else
	    $is_published = 1;

          if (array_key_exists("NAME", $_POST))
	    $name = $_POST["NAME"];
	  else
	    $name = "";

          if (array_key_exists("EMAIL", $_POST))
	    $email = $_POST["EMAIL"];
	  else
	    $email = "";

	  if (array_key_exists("PASSWORD", $_POST))
	    $password = $_POST["PASSWORD"];
	  else
	    $password = "";

	  if (array_key_exists("PASSWORD2", $_POST))
	    $password2 = $_POST["PASSWORD2"];
	  else
	    $password2 = "";

          if (array_key_exists("LEVEL", $_POST))
	    $level = (int)$_POST["LEVEL"];
	  else
	    $level = AUTH_USER;

          if ($name != "" && $email != "" &&
	      (($password == "" && $password2 == "") ||
	       $password == $password2))
	    $havedata = 1;
	  else
	    $havedata = 0;
	}
	else
	{
	  // Use blank account info...
	  $name         = "";
	  $is_published = 0;
	  $email        = $row["email"];
	  $level        = $row["level"];
	  $password     = "";
	  $password2    = "";
	  $havedata     = 0;
	}

	account_header("Add Account");

	if ($havedata)
	{
          // Store new data...
	  $hash  = md5("$name:$password");
	  $name  = db_escape($name);
	  $email = db_escape($email);
	  $date  = time();

	  db_query("INSERT INTO users VALUES(NULL,$is_published,"
	          ."'$name','$email','$hash',$level,$date,'$LOGIN_USER',"
		  ."$date,'$LOGIN_USER')");

	  print("<p>Account added successfully!</p>\n");

	  html_start_links(1);
	  html_link("Return to Manage Accounts", "$PHP_SELF?A");
	  html_end_links();
	}
	else
	{
	  $name  = htmlspecialchars($name, ENT_QUOTES);
	  $email = htmlspecialchars($email, ENT_QUOTES);

	  print("<form method='POST' action='$PHP_SELF?Aadd'>"
	       ."<table width='100%'>\n"
	       ."<tr><th align='right'>Published:</th>"
	       ."<td>");
          select_is_published($is_published);
	  print("</td></tr>\n"
	       ."<tr><th align='right'>Username:</th>"
	       ."<td><input type='text' name='NAME' size='40' "
	       ."maxsize='255' value='$name'></td></tr>\n"
	       ."<tr><th align='right'>EMail:</th>"
	       ."<td><input type='text' name='EMAIL' size='40' "
	       ."maxsize='255' value='$email'></td></tr>\n"
	       ."<tr><th align='right'>Access Level:</th>"
	       ."<td><select name='LEVEL'>");

          reset($levels);
	  while (list($key, $val) = each($levels))
	  {
	    if ($level == $key)
	      print("<option value='$key' selected>$val</option>");
	    else
	      print("<option value='$key'>$val</option>");
          }

	  print("</select></td></tr>\n"
	       ."<tr><th align='right'>Password:</th>"
	       ."<td><input type='password' name='PASSWORD' size='16' "
	       ."maxsize='255'></td></tr>\n"
	       ."<tr><th align='right'>Password Again:</th>"
	       ."<td><input type='password' name='PASSWORD2' size='16' "
	       ."maxsize='255'></td></tr>\n"
	       ."<tr><th></th><td><input type='submit' value='Add Account'>"
	       ."</td></tr>\n"
               ."</table></form>\n");
	}

	html_footer();
      }
      else if ($data == "batch")
      {
        // Disable/enable/expire/etc. accounts...
	if ($REQUEST_METHOD == "POST" && array_key_exists("OP", $_POST))
	{
	  $op = $_POST["OP"];

          db_query("BEGIN TRANSACTION");

          reset($_POST);
          while (list($key, $val) = each($_POST))
            if (substr($key, 0, 3) == "ID_")
	    {
	      $id = (int)substr($key, 3);

              if ($op == "disable")
        	db_query("UPDATE users SET is_published = 0 WHERE id = $id");
              else if ($op == "enable")
        	db_query("UPDATE users SET is_published = 1 WHERE id = $id");
	    }

          db_query("COMMIT TRANSACTION");
	}

	header("Location: $PHP_SELF?A");
      }
      else if ($data == "modify")
      {
        // Modify account...
        if ($argc != 2 || $argv[1] == "")
	{
	  header("Location: $PHP_SELF?A");
	  exit();
	}

        $name = $argv[1];

	if ($REQUEST_METHOD == "POST")
	{
	  // Get data from form...
	  if (array_key_exists("IS_PUBLISHED", $_POST))
	    $is_published = (int)$_POST["IS_PUBLISHED"];
	  else
	    $is_published = 1;

          if (array_key_exists("EMAIL", $_POST))
	    $email = $_POST["EMAIL"];
	  else
	    $email = "";

	  if (array_key_exists("PASSWORD", $_POST))
	    $password = $_POST["PASSWORD"];
	  else
	    $password = "";

	  if (array_key_exists("PASSWORD2", $_POST))
	    $password2 = $_POST["PASSWORD2"];
	  else
	    $password2 = "";

          if (array_key_exists("LEVEL", $_POST))
	    $level = (int)$_POST["LEVEL"];
	  else
	    $level = AUTH_USER;

          if ($email != "" &&
	      (($password == "" && $password2 == "") ||
	       $password == $password2))
	    $havedata = 1;
	  else
	    $havedata = 0;
	}
	else
	{
	  // Get data from existing account...
	  $result = db_query("SELECT * FROM users WHERE "
	                    ."name='" . db_escape($name) ."'");
          if (db_count($result) != 1)
	  {
	    header("Location: $PHP_SELF?A");
	    exit();
	  }

	  $row          = db_next($result);
	  $is_published = $row["is_published"];
	  $email        = $row["email"];
	  $level        = $row["level"];
	  $password     = "";
	  $password2    = "";
	  $havedata     = 0;

	  db_free($result);
	}

	account_header("Modify $name");

	if ($havedata)
	{
          // Store new data...
	  if ($password != "")
	    $hash = ", hash='" . md5("$name:$password") . "'";
	  else
	    $hash = "";

	  $name  = db_escape($name);
	  $email = db_escape($email);
	  $date  = time();

	  db_query("UPDATE users SET "
	          ."email='$email'$hash, level='$level', "
		  ."is_published=$is_published, modify_user='$LOGIN_USER', "
		  ."modify_date = $date WHERE name='$name'");

	  print("<p>Account modified successfully!</p>\n");

	  html_start_links(1);
	  html_link("Return to Manage Accounts", "$PHP_SELF?A");
	  html_end_links();
	}
	else
	{
	  $name  = htmlspecialchars($name, ENT_QUOTES);
	  $email = htmlspecialchars($email, ENT_QUOTES);

	  print("<form method='POST' action='$PHP_SELF?Amodify+$name'>"
	       ."<table width='100%'>\n"
	       ."<tr><th align='right'>Published:</th>"
	       ."<td>");
          select_is_published($is_published);
	  print("</td></tr>\n"
	       ."<tr><th align='right'>Username:</th>"
	       ."<td>$name</td></tr>\n"
	       ."<tr><th align='right'>EMail:</th>"
	       ."<td><input type='text' name='EMAIL' size='40' "
	       ."maxsize='255' value='$email'></td></tr>\n"
	       ."<tr><th align='right'>Access Level:</th>"
	       ."<td>");

          if ($LOGIN_USER == $name)
	    print("<input type='hidden' name='LEVEL' value='$level'>"
	         . $levels[$level]);
	  else
	  {
	    print("<select name='LEVEL'>");

            reset($levels);
	    while (list($key, $val) = each($levels))
	    {
	      if ($level == $key)
		print("<option value='$key' selected>$val</option>");
	      else
		print("<option value='$key'>$val</option>");
            }

	    print("</select>");
	  }

	  print("</td></tr>\n"
	       ."<tr><th align='right'>Password:</th>"
	       ."<td><input type='password' name='PASSWORD' size='16' "
	       ."maxsize='255'></td></tr>\n"
	       ."<tr><th align='right'>Password Again:</th>"
	       ."<td><input type='password' name='PASSWORD2' size='16' "
	       ."maxsize='255'></td></tr>\n"
	       ."<tr><th></th><td><input type='submit' value='Modify Account'>"
	       ."</td></tr>\n"
               ."</table></form>\n");
	}

	html_footer();
      }
      else
      {
        // List accounts...
	account_header("Manage Accounts");

	$result = db_query("SELECT * FROM users ORDER BY name");

        print("<form method='POST' action='$PHP_SELF?Abatch'>\n");

        html_start_table(array("Username", "EMail", "Level"));

	while ($row = db_next($result))
	{
	  $name  = htmlspecialchars($row["name"], ENT_QUOTES);
	  $email = htmlspecialchars($row["email"], ENT_QUOTES);
	  $level = $levels[$row["level"]];

          if ($row["is_published"] == 0)
	    $email .= " <img src='images/private.gif' width='16' height='16' "
	             ."border='0' align='middle' alt='Private'>";

	  html_start_row();
	  print("<td nowrap><input type='checkbox' name='ID_$row[id]'>"
	       ."<a href='$PHP_SELF?Amodify+$name'>$name</a></td>"
	       ."<td align='center'><a href='$PHP_SELF?Amodify+$name'>"
	       ."$email</a></td>"
	       ."<td align='center'><a href='$PHP_SELF?Amodify+$name'>"
	       ."$level</a></td>");
	  html_end_row();
	}

	html_end_table();

	print("<p align='center'><select name='OP'>"
	     ."<option value='disable'>Disable</option>"
	     ."<option value='enable'>Enable</option>"
	     ."</select>"
	     ."<input type='submit' value='Checked Accounts'></p>");

	html_start_links(1);
	html_link("Add Account", "$PHP_SELF?Aadd");
	html_end_links();

        html_footer();
      }
      break;

  case 'L' :
      // List
      if ($LOGIN_LEVEL < AUTH_DEVEL)
      {
        header("Location: $PHP_SELF");
	exit();
      }

      account_header("New/Pending");

      $email = db_escape($_COOKIE["FROM"]);

      print("<h2>New/Pending Articles:</h2>\n");

      $result = db_query("SELECT * FROM article WHERE is_published = 0 "
	                ."ORDER BY modify_date");
      $count  = db_count($result);

      if ($count == 0)
	print("<p>No new/pending articles found.</p>\n");
      else
      {
        html_start_table(array("Id", "Title", "Last Updated"));

	while ($row = db_next($result))
	{
	  $id       = $row['id'];
          $title    = htmlspecialchars($row['title'], ENT_QUOTES) .
	              " <img src='images/private.gif' width='16' height='16' "
	             ."border='0' align='middle' alt='Private'>";
          $abstract = htmlspecialchars($row['abstract'], ENT_QUOTES);
	  $date     = date("M d, Y", $row['modify_date']);

          html_start_row();

          print("<td align='center' nowrap><a "
	       ."href='articles.php?L$id$options'>$id</a></td>"
	       ."<td width='67%' align='center'><a "
	       ."href='articles.php?L$id$options'>$title</a></td>"
	       ."<td align='center'><a "
	       ."href='articles.php?L$id$options'>$date</a></td>");

	  html_end_row();

          html_start_row();

	  print("<td></td><td colspan='2'>$abstract</td>");

	  html_end_row();
	}

        html_end_table();
      }

      db_free($result);

      print("<h2>New/Pending Links:</h2>\n");

      $result = db_query("SELECT * FROM link WHERE is_published = 0 "
	                ."ORDER BY modify_date");
      $count  = db_count($result);

      if ($count == 0)
	print("<p>No new/pending links found.</p>\n");
      else
      {
        html_start_table(array("Id", "Name/Version", "Last Updated"));

	while ($row = db_next($result))
	{
	  $id       = $row['id'];
          $title    = htmlspecialchars($row['name'], ENT_QUOTES) . " " .
	              htmlspecialchars($row['version'], ENT_QUOTES) .
	              " <img src='images/private.gif' width='16' height='16' "
	             ."border='0' align='middle' alt='Private'>";
	  $date     = date("M d, Y", $row['modify_date']);

          if ($row["is_category"])
	    $link = "<a href='links.php?UC$id'>";
	  else
	    $link = "<a href='links.php?UL$id'>";

          html_start_row();

          print("<td align='center' nowrap>$link$id</a></td>"
	       ."<td width='67%' align='center'>$link$title</a></td>"
	       ."<td align='center'>$link$date</a></td>");

	  html_end_row();
	}

        html_end_table();
      }

      db_free($result);

      print("<h2>New/Pending STRs:</h2>\n");

      $result = db_query("SELECT * FROM str WHERE status >= $STR_STATUS_PENDING "
	                ."AND (manager_email == '' OR manager_email = '$email') "
	                ."ORDER BY status DESC, priority DESC, scope DESC, "
			."modify_date");
      $count  = db_count($result);

      if ($count == 0)
	print("<p>No new/pending STRs found.</p>\n");
      else
      {
        html_start_table(array("Id", "Priority", "Status", "Scope",
	                       "Summary", "Version", "Last Updated",
			       "Assigned To"));

	while ($row = db_next($result))
	{
	  $date     = date("M d, Y", $row['modify_date']);
          $summary  = htmlspecialchars($row['summary'], ENT_QUOTES);
	  $summabbr = htmlspecialchars(abbreviate($row['summary'], 80), ENT_QUOTES);
	  $prtext   = $priority_text[$row['priority']];
          $sttext   = $status_text[$row['status']];
          $sctext   = $scope_text[$row['scope']];

          if ($row['is_published'] == 0)
	    $summabbr .= " <img src='images/private.gif' width='16' height='16' "
	                ."border='0' align='middle' alt='Private'>";

          html_start_row();

          print("<td nowrap>"
	       ."<a href='str.php?L$row[id]$options' alt='STR #$row[id]: $summary'>"
	       ."$row[id]</a></td>"
	       ."<td align='center'>$prtext</td>"
	       ."<td align='center'>$sttext</td>"
	       ."<td align='center'>$sctext</td>"
	       ."<td align='center'><a href='str.php?L$row[id]$options' "
	       ."alt='STR #$row[id]: $summary'>$summabbr</a></td>"
	       ."<td align='center'>$row[str_version]</td>"
	       ."<td align='center'>$date</td>");

	  if ($row['manager_email'] != "")
	    $email = sanitize_email($row['manager_email']);
	  else
	    $email = "<i>Unassigned</i>";

	  print("<td align='center'>$email</td>");

	  html_end_row();
	}

        html_end_table();
      }

      db_free($result);

      // Show hidden comments...
      print("<h2>Hidden Comments:</h2>\n");

      $result = db_query("SELECT * FROM comment WHERE status = 0 ORDER BY id");

      if (db_count($result) == 0)
        print("<p>No hidden comments.</p>\n");
      else
      {
        print("<ul>\n");

        while ($row = db_next($result))
	{
	  $create_date  = date("M d, Y", $row['create_date']);
	  $create_user  = sanitize_email($row['create_user']);
	  $contents     = sanitize_text($row['contents']);
          $location     = str_replace("_", "?", $row['url']);

	  print("<li><a href='$location'>$row[url]</a> "
	       ." by $create_user on $create_date "
	       ."<a href='comment.php?e$row[id]+p$row[url]'>Edit</a> "
	       ."&middot; <a href='comment.php?d$row[id]+p$row[url]'>Delete</a>"
	       ."<br><tt>$contents</tt></li>\n");
	}

        print("</ul>\n");
      }

      db_free($result);

      html_footer();
      break;

  case 'P' :
      // Change password
      account_header("Change Password");

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
	     ."<table width='100%'>\n"
	     ."<tr><th align='right'>Password:</th>"
	     ."<td><input type='password' name='PASSWORD' size='16' "
	     ."maxsize='255'></td></tr>\n"
	     ."<tr><th align='right'>Password Again:</th>"
	     ."<td><input type='password' name='PASSWORD2' size='16' "
	     ."maxsize='255'></td></tr>\n"
	     ."<tr><th></th><td><input type='submit' value='Change Password'>"
	     ."</td></tr>\n"
             ."</table></form>\n");
      }

      html_footer();
      break;

  default :
      // Show account info...
      account_header($LOGIN_USER);

      if (array_key_exists("FROM", $_COOKIE))
        $email = htmlspecialchars($_COOKIE["FROM"]);
      else
        $email = "<i>unknown</i>";

      print("<center><table border='0'>\n"
           ."<tr><th align='right'>Username:</th><td>$LOGIN_USER</td></tr>\n"
	   ."<tr><th align='right'>EMail:</th><td>$email</td></tr>\n"
	   ."<tr><th align='right'>Access Level:</th>"
	   ."<td>$levels[$LOGIN_LEVEL]</td></tr>\n"
	   ."</table></center>\n");

      html_footer();
      break;
}


//
// End of "$Id$".
//
?>
