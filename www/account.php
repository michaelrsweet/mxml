<?php
//
// "$Id: account.php,v 1.6 2004/05/19 01:39:04 mike Exp $"
//
// Account management page...
//

//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";
include_once "phplib/str.php";


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
      html_link("Manage Comments", "comment.php?l");
      html_link("Change Password", "$PHP_SELF?P");
      html_link("Logout", "$PHP_SELF?X");
      html_end_links();

      print("<h1>New/Pending</h1>\n");

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
	             ."border='0' align='middle' alt='Private'/>";
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
	                ."border='0' align='middle' alt='Private'/>";

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
// End of "$Id: account.php,v 1.6 2004/05/19 01:39:04 mike Exp $".
//
?>
