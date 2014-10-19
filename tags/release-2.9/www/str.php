<?php
//
// "$Id$"
//
// Software Trouble Report page...
//
// Contents:
//
//   notify_users() - Notify users of STR changes...
//

//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";
include_once "phplib/str.php";


//
// String definitions for various things...
//

$messages = array(
  "Fixed in Repo" =>
      "Fixed in Subversion repository.",
  "Old STR" =>
      "This STR has not been updated by the submitter for two or more weeks "
     ."and has been closed as required by the Mini-XML Configuration Management "
     ."Plan. If the issue still requires resolution, please re-submit a new "
     ."STR.",
  "Unresolvable" =>
      "We are unable to resolve this problem with the information provided. "
     ."If you discover new information, please file a new STR referencing "
     ."this one."
);
    
$subsystems = array(
  "Build Files",
  "Config Files",
  "Core API",
  "Documentation",
  "Multiple",
  "mxmldoc",
  "Sample Programs",
  "Web Site"
);

$versions = array(
  "+Will Not Fix",
  "+None",
  "+Future",
  "Trunk",
  "+3.0",
  "2.7",
  "2.6",
  "2.5",
  "2.4",
  "2.3",
  "2.2.2",
  "2.2.1",
  "2.2",
  "2.1",
  "2.0",
  "2.0rc1",
  "1.3",
  "1.2",
  "1.1.2",
  "1.1.1",
  "1.1",
  "1.0",
  "Web Site"
);


//
// Get the list of valid developers from the users table...
//

$managers = array();

$result = db_query("SELECT * FROM users WHERE is_published = 1 AND "
                  ."level >= " . AUTH_DEVEL);
while ($row = db_next($result))
  $managers[$row["name"]] = $row["email"];

db_free($result);


//
// 'notify_users()' - Notify users of STR changes...
//

function
notify_users($id,			// I - STR #
             $what = "updated",		// I - Reason for notification
	     $contents = "")		// I - Notification message
{
  global $priority_long;
  global $scope_long;
  global $status_long;
  global $PHP_URL, $PROJECT_EMAIL, $PROJECT_NAME;


  $result = db_query("SELECT * FROM str WHERE id = $id");
  if ($result)
  {
    $contents = wordwrap($contents);
    $row      = db_next($result);
    $prtext   = $priority_long[$row['priority']];
    $sttext   = $status_long[$row['status']];
    $sctext   = $scope_long[$row['scope']];

    if ($row['subsystem'] != "")
      $subsystem = $row['subsystem'];
    else
      $subsystem = "Unassigned";

    if ($row['fix_version'] != "")
      $fix_version = $row['fix_version'];
    else
      $fix_version = "Unassigned";

    if (eregi("[a-z0-9_.]+", $row['create_user']))
      $email = auth_user_email($row['create_user']);
    else
      $email = $row['create_user'];

    if ($row['create_user'] != $row['modify_user'] &&
        $row['create_user'] != $manager &&
	$email != "")
      mail($email, "$PROJECT_NAME STR #$id $what",
	   "Your software trouble report #$id has been $what.  You can check\n"
	  ."the status of the report and add additional comments and/or files\n"
	  ."at the following URL:\n"
	  ."\n"
	  ."    $PHP_URL?L$id\n"
	  ."\n"
	  ."    Summary: $row[summary]\n"
	  ."    Version: $row[str_version]\n"
	  ."     Status: $sttext\n"
	  ."   Priority: $prtext\n"
	  ."      Scope: $sctext\n"
	  ."  Subsystem: $subsystem\n"
	  ."Fix Version: $fix_version\n"
	  ."\n$contents"
	  ."________________________________________________________________\n"
	  ."Thank you for using the $PROJECT_NAME Software Trouble Report page!",
	   "From: $PROJECT_EMAIL\r\n");

    $ccresult = db_query("SELECT email FROM carboncopy WHERE url = 'str.php_L$id'");
    if ($ccresult)
    {
      while ($ccrow = db_next($ccresult))
      {
	mail($ccrow['email'], "$PROJECT_NAME STR #$id $what",
	     "Software trouble report #$id has been $what.  You can check\n"
	    ."the status of the report and add additional comments and/or files\n"
	    ."at the following URL:\n"
	    ."\n"
	    ."    $PHP_URL?L$id\n"
	    ."\n"
	    ."    Summary: $row[summary]\n"
	    ."    Version: $row[str_version]\n"
	    ."     Status: $sttext\n"
	    ."   Priority: $prtext\n"
	    ."      Scope: $sctext\n"
	    ."  Subsystem: $subsystem\n"
	    ."Fix Version: $fix_version\n"
	    ."\n$contents"
	    ."________________________________________________________________\n"
	    ."Thank you for using the $PROJECT_NAME Software Trouble Report page!",
	     "From: $PROJECT_EMAIL\r\n");
      }

      db_free($ccresult);
    }

    if ($row['manager_email'] != "")
      $manager = $row['manager_email'];
    else
      $manager = $PROJECT_EMAIL;

    if ($row['modify_user'] != $manager)
      mail($manager, "$PROJECT_NAME STR #$id $what",
	   "The software trouble report #$id assigned to you has been $what.\n"
	  ."You can manage the report and add additional comments and/or files\n"
	  ."at the following URL:\n"
	  ."\n"
	  ."    $PHP_URL?L$id\n"
	  ."\n"
	  ."    Summary: $row[summary]\n"
	  ."    Version: $row[str_version]\n"
	  ."     Status: $sttext\n"
	  ."   Priority: $prtext\n"
	  ."      Scope: $sctext\n"
	  ."  Subsystem: $subsystem\n"
	  ."Fix Version: $fix_version\n"
	  ."\n$contents",
	   "From: $PROJECT_EMAIL\r\n");

    db_free($result);
  }
}


// Get command-line options...
//
// Usage: str.php [operation] [options]
//
// Operations:
//
// B         = Batch update selected STRs
// L         = List all STRs
// L#        = List STR #
// M#        = Modify STR #
// T#        = Post text for STR #
// F#        = Post file for STR #
// N         = Post new STR
// U#        = Update notification for STR #
//
// Options:
//
// I#        = Set first STR
// P#        = Set priority filter
// S#        = Set status filter
// C#        = Set scope filter
// E#        = Set email filter
// Qtext     = Set search text

$priority = 0;
$status   = -2;
$scope    = 0;
$search   = "";
$index    = 0;
$femail   = 0;

if ($argc)
{
  $op = $argv[0][0];
  $id = (int)substr($argv[0], 1);

  if ($op != 'L' && $op != 'M' && $op != 'T' && $op != 'F' &&
      $op != 'N' && $op != 'U' && $op != 'B')
  {
    html_header("Bugs &amp; Features Error");
    print("<p>Bad command '$op'!</p>\n");
    html_footer();
    exit();
  }

  if (($op == 'M' || $op == 'B') && $LOGIN_LEVEL < AUTH_DEVEL)
  {
    html_header("Bugs &amp; Features Error");
    print("<p>The '$op' command is not available to you!</p>\n");
    html_footer();
    exit();
  }

  if (($op == 'M' || $op == 'T' || $op == 'F') && !$id)
  {
    html_header("Bugs &amp; Features Error");
    print("<p>Command '$op' requires an STR number!</p>\n");
    html_footer();
    exit();
  }

  if ($op == 'N' && $id)
  {
    html_header("Bugs &amp; Features Error");
    print("<p>Command '$op' cannot have an STR number!</p>\n");
    html_footer();
    exit();
  }

  for ($i = 1; $i < $argc; $i ++)
  {
    $option = substr($argv[$i], 1);

    switch ($argv[$i][0])
    {
      case 'P' : // Set priority filter
          $priority = (int)$option;
	  break;
      case 'S' : // Set status filter
          $status = (int)$option;
	  break;
      case 'C' : // Set scope filter
          $scope = (int)$option;
	  break;
      case 'Q' : // Set search text
          $search = urldecode($option);
	  $i ++;
	  while ($i < $argc)
	  {
	    $search .= urldecode(" $argv[$i]");
	    $i ++;
	  }
	  break;
      case 'I' : // Set first STR
          $index = (int)$option;
	  if ($index < 0)
	    $index = 0;
	  break;
      case 'E' : // Show only problem reports matching the current email
          $femail = (int)$option;
	  break;
      default :
	  html_header("Bugs &amp; Features Error");
	  print("<p>Bad option '$argv[$i]'!</p>\n");
	  html_footer();
	  exit();
	  break;
    }
  }
}
else
{
  $op = 'L';
  $id = 0;
}

if ($REQUEST_METHOD == "POST")
{
  if (array_key_exists("FPRIORITY", $_POST))
    $priority = (int)$_POST["FPRIORITY"];
  if (array_key_exists("FSTATUS", $_POST))
    $status = (int)$_POST["FSTATUS"];
  if (array_key_exists("FSCOPE", $_POST))
    $scope = (int)$_POST["FSCOPE"];
  if (array_key_exists("FEMAIL", $_POST))
    $femail = (int)$_POST["FEMAIL"];
  if (array_key_exists("SEARCH", $_POST))
    $search = $_POST["SEARCH"];
}

$options = "+P$priority+S$status+C$scope+I$index+E$femail+Q" . urlencode($search);

// B         = Batch update selected STRs
// L         = List all STRs
// L#        = List STR #
// M#        = Modify STR #
// T#        = Post text for STR #
// F#        = Post file for STR #
// N         = Post new STR
// U#        = Update notification for STR #

switch ($op)
{
  case 'B' : // Batch update selected STRs
      if ($REQUEST_METHOD != "POST")
      {
        header("Location: $PHP_SELF?L$options");
        break;
      }

      if (array_key_exists("STATUS", $_POST) &&
          ($_POST["STATUS"] != "" ||
	   $_POST["PRIORITY"] != "" ||
	   $_POST["MANAGER_EMAIL"] != "" ||
	   $_POST["MESSAGE"] != ""))
      {
        $time          = time();
        $manager_email = db_escape($_POST["MANAGER_EMAIL"]);
        $modify_user  = db_escape($_COOKIE["FROM"]);
	$message       = $_POST["MESSAGE"];

        if ($message != "")
	{
	  $contents = db_escape($messages[$message]);
	  $mailmsg  = $messages[$message] . "\n\n";
	}
	else
	{
	  $contents = "";
	  $mailmsg  = "";
	}

        $query = "modify_date = $time, modify_user = '$modify_user'";

	if ($_POST["STATUS"] != "")
	  $query .= ", status = $_POST[STATUS]";
	if ($_POST["PRIORITY"] != "")
	  $query .= ", priority = $_POST[PRIORITY]";
	if ($manager_email != "")
	  $query .= ", manager_email = '$manager_email'";

        db_query("BEGIN TRANSACTION");

        reset($_POST);
        while (list($key, $val) = each($_POST))
          if (substr($key, 0, 3) == "ID_")
	  {
	    $id = (int)substr($key, 3);

            db_query("UPDATE str SET $query WHERE id = $id");

            if ($contents != "")
	    {
              db_query("INSERT INTO strtext VALUES(NULL,$id,1,'$contents',"
	              ."$time,'$modify_user')");

	      notify_users($id, "updated", $mailmsg);
	    }
	  }

        db_query("COMMIT TRANSACTION");
      }

      header("Location: $PHP_SELF?L$options");
      break;

  case 'L' : // List (all) STR(s)
      if ($id)
      {
        html_header("STR #$id");

        $result = db_query("SELECT * FROM str WHERE id = $id");
	if (db_count($result) != 1)
	{
	  print("<p><b>Error:</b> STR #$id was not found!</p>\n");
	  html_footer();
	  exit();
	}

        $row = db_next($result);

        html_start_links(1);
	html_link("Return to Bugs &amp; Features", "$PHP_SELF?L$options");

        if ($row['status'] >= $STR_STATUS_ACTIVE)
	{
	  html_link("Post Text", "$PHP_SELF?T$id$options");
	  html_link("Post File", "$PHP_SELF?F$id$options");
	}

	if ($LOGIN_LEVEL >= AUTH_DEVEL)
	  html_link("Modify STR", "$PHP_SELF?M$id$options");

        html_end_links();

	$create_user  = sanitize_email($row['create_user']);
	$manager_email = sanitize_email($row['manager_email']);
	$subsystem     = $row['subsystem'];
	$summary       = htmlspecialchars($row['summary'], ENT_QUOTES);
	$prtext        = $priority_long[$row['priority']];
        $sttext        = $status_long[$row['status']];
        $sctext        = $scope_long[$row['scope']];
	$str_version   = $row['str_version'];
	$fix_version   = $row['fix_version'];

        if ($manager_email == "")
	  $manager_email = "<i>Unassigned</i>";

        if ($subsystem == "")
	  $subsystem = "<i>Unassigned</i>";

        if ($fix_version == "")
	  $fix_version = "<i>Unassigned</i>";

        print("<table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

        print("<tr><th align='right'>ID:</th><td>$id</td></tr>\n");

        if ($row['master_id'] > 0)
          print("<tr><th align='right'>Duplicate Of:</th>"
	       ."<td><a href='$PHP_SELF?L$row[master_id]$options'>STR "
	       ."#$row[master_id]</a></td></tr>\n");

        if (!$row['is_published'])
	  print("<tr><TH ALIGN='CENTER' COLSPAN='2'>This STR is "
	       ."currently hidden from public view.</td></tr>\n");

        print("<tr><th align='right'>Status:</th><td>$sttext</td></tr>\n");

	print("<tr><th align='right'>Priority:</th><td>$prtext</td></tr>\n");
	print("<tr><th align='right'>Scope:</th><td>$sctext</td></tr>\n");
	print("<tr><th align='right'>Subsystem:</th><td>$subsystem</td></tr>\n");
	print("<tr><th align='right'>Summary:</th><td>$summary</td></tr>\n");
	print("<tr><th align='right'>Version:</th><td>$str_version</td></tr>\n");
	print("<tr><th align='right'>Created By:</th><td>$create_user</td></tr>\n");
	print("<tr><th align='right'>Assigned To:</th><td>$manager_email</td></tr>\n");
	print("<tr><th align='right'>Fix Version:</th><td>$fix_version</td></tr>\n");

	if (array_key_exists("FROM", $_COOKIE))
          $email = htmlspecialchars($_COOKIE["FROM"]);
	else
	  $email = "";

        print("<tr><th align='right' valign='top'>Update Notification:</th><td>"
	     ."<form method='POST' action='$PHP_SELF?U$id$options'>"
	     ."<input type='text' size='40' maxsize='128' name='EMAIL' value='$email'>"
	     ."<input type='submit' value='Change Notification Status'>"
	     ."<br><input type='radio' name='NOTIFICATION' checked value='ON'>Receive EMails "
	     ."<input type='radio' name='NOTIFICATION' value='OFF'>Don't Receive EMails"
	     ."</form>"
	     ."</td></tr>\n");
        print("</table>\n");

        db_free($result);

	print("<p><b>Trouble Report Files:</b></p>\n");
        if ($row['status'] >= $STR_STATUS_ACTIVE)
	{
	  html_start_links();
	  html_link("Post File", "$PHP_SELF?F$id$options");
	  html_end_links();
	}

	$result = db_query("SELECT * FROM strfile WHERE "
	                  ."str_id = $id AND is_published = 1");

        if (db_count($result) == 0)
	  print("<p><i>No files</i></p>\n");
	else
	{
	  html_start_table(array("Name/Time/Date", "Filename"));

	  while ($row = db_next($result))
	  {
            $date     = date("M d, Y", $row['create_date']);
            $time     = date("H:i", $row['create_date']);
	    $email    = sanitize_email($row['create_user']);
	    $filename = htmlspecialchars($row['filename']);

            html_start_row();
	    print("<td align='center' valign='top'>$email<br>$time $date</td>"
		 ."<td align='center' valign='top'>"
		 ."<a href='strfiles/$id/$filename'>$filename</a></td>");
            html_end_row();
	  }

          html_end_table();
        }

	db_free($result);

	print("<p><b>Trouble Report Dialog:</b></p>\n");
        if ($row['status'] >= $STR_STATUS_ACTIVE)
	{
	  html_start_links();
	  html_link("Post Text", "$PHP_SELF?T$id$options");
	  html_end_links();
	}

	$result = db_query("SELECT * FROM strtext WHERE "
	                  ."str_id = $id AND is_published = 1");

        if (db_count($result) == 0)
	  print("<p><i>No text</i></p>\n");
	else
	{
	  html_start_table(array("Name/Time/Date", "Text"));

	  while ($row = db_next($result))
	  {
            $date     = date("M d, Y", $row['create_date']);
            $time     = date("H:i", $row['create_date']);
	    $email    = sanitize_email($row['create_user']);
	    $contents = quote_text($row['contents']);

	    html_start_row();
	    print("<td align='center' valign='top'>$email<br>$time $date</td>"
		 ."<td valign='top'><tt>$contents</tt></td>");
	    html_end_row();
	  }

          html_end_table();
	}

	db_free($result);
      }
      else
      {
        html_header("Bugs &amp; Features");

        html_start_links(1);
	html_link("Submit Bug or Feature Request", "$PHP_SELF?N$options'");
	html_end_links();

	$htmlsearch = htmlspecialchars($search, ENT_QUOTES);

        print("<form method='POST' action='$PHP_SELF'><p align='center'>"
	     ."Search&nbsp;Words: &nbsp;<input type='text' size='60' "
	     ."name='SEARCH' value='$htmlsearch'>"
	     ."<input type='submit' value='Search STRs'></p>\n");

	print("<p align='center'>Priority:&nbsp;<select name='FPRIORITY'>");
	print("<option value='0'>Don't Care</option>");
        for ($i = 1; $i <= 5; $i ++)
	{
	  print("<option value='$i'");
	  if ($priority == $i)
	    print(" selected");
	  print(">$priority_text[$i]</option>");
	}
        print("</select>\n");

	print("Status:&nbsp;<select name='FSTATUS'>");
	print("<option value='0'>Don't Care</option>");
	if ($status == -1)
	  print("<option value='-1' selected>Closed</option>");
	else
	  print("<option value='-1'>Closed</option>");
	if ($status == -2)
	  print("<option value='-2' selected>Open</option>");
	else
	  print("<option value='-2'>Open</option>");
	for ($i = 1; $i <= 5; $i ++)
	{
	  print("<option value='$i'");
	  if ($status == $i)
	    print(" selected");
	  print(">$status_text[$i]</option>");
	}
        print("</select>\n");

	print("Scope:&nbsp;<select name='FSCOPE'>");
	print("<option value='0'>Don't Care</option>");
	for ($i = 1; $i <= 3; $i ++)
	{
	  print("<option value='$i'");
	  if ($scope == $i)
	    print(" selected");
	  print(">$scope_text[$i]</option>");
	}
        print("</select>\n");

        if (array_key_exists("FROM", $_COOKIE))
	{
	  print("Show:&nbsp;<select name='FEMAIL'>");
          print("<option value='0'");
	  if (!$femail)
            print(" selected");
	  print(">All</option>");
          print("<option value='1'");
	  if ($femail)
            print(" selected");
          if ($LOGIN_LEVEL >= AUTH_DEVEL)
	    print(">Mine + Unassigned</option>");
	  else
	    print(">Only Mine</option>");
          print("</select>\n");
        }

        print("</p></form>\n");

        $query = "";
	$prefix = "WHERE ";

	if ($priority > 0)
	{
	  $query .= "${prefix}priority = $priority";
	  $prefix = " AND ";
	}

	if ($status > 0)
	{
	  $query .= "${prefix}status = $status";
	  $prefix = " AND ";
	}
	else if ($status == -1) // Show closed
	{
	  $query .= "${prefix}status <= $STR_STATUS_UNRESOLVED";
	  $prefix = " AND ";
	}
	else if ($status == -2) // Show open
	{
	  $query .= "${prefix}status >= $STR_STATUS_ACTIVE";
	  $prefix = " AND ";
	}

	if ($scope > 0)
	{
	  $query .= "${prefix}scope = $scope";
	  $prefix = " AND ";
	}

        if ($LOGIN_LEVEL < AUTH_DEVEL)
	{
	  $query .= "${prefix}(is_published = 1 OR create_user = '"
	           . db_escape($LOGIN_USER) . "')";
	  $prefix = " AND ";
	}

        if ($femail)
	{
          if (array_key_exists("FROM", $_COOKIE))
            $email = db_escape($_COOKIE["FROM"]);
	  else
	    $email = "";

	  if ($LOGIN_LEVEL >= AUTH_DEVEL)
	  {
	    $query .= "${prefix}(manager_email = '' OR manager_email = '$email')";
	    $prefix = " AND ";
	  }
	  else if ($email != "")
	  {
	    $query .= "${prefix}create_user = '$email'";
	    $prefix = " AND ";
	  }
	}

        if ($search)
	{
	  // Convert the search string to an array of words...
	  $words = html_search_words($search);

	  // Loop through the array of words, adding them to the query...
	  $query  .= "${prefix}(";
	  $prefix = "";
	  $next   = " OR";
	  $logic  = "";

	  reset($words);
	  foreach ($words as $word)
	  {
            if ($word == "or")
            {
              $next = ' OR';
              if ($prefix != '')
        	$prefix = ' OR';
            }
            else if ($word == "and")
            {
              $next = ' AND';
              if ($prefix != '')
        	$prefix = ' AND';
            }
            else if ($word == "not")
              $logic = ' NOT';
            else
            {
              $query .= "$prefix$logic (";
              $subpre = "";
	      $word   = db_escape($word);

              if (ereg("[0-9]+", $word))
              {
        	$query .= "${subpre}id = $word";
        	$subpre = " OR ";
              }

              $query .= "${subpre}summary LIKE \"%$word%\"";
              $subpre = " OR ";
              $query .= "${subpre}subsystem LIKE \"%$word%\"";
              $query .= "${subpre}str_version LIKE \"%$word%\"";
              $query .= "${subpre}fix_version LIKE \"%$word%\"";
              $query .= "${subpre}manager_email LIKE \"%$word%\"";
              $query .= "${subpre}create_user LIKE \"%$word%\"";

              $query .= ")";
              $prefix = $next;
              $logic  = '';
            }
	  }

	  $query  .= ")";
	}

        $result = db_query("SELECT * FROM str $query "
	                  ."ORDER BY status DESC, priority DESC, scope DESC, "
			  ."modify_date");
        $count  = db_count($result);

        if ($count == 0)
	{
	  print("<p>No STRs found.</p>\n");

	  if (($priority || $status || $scope) && $search != "")
	    print("<p><a href='$PHP_SELF?L+S0+Q" . urlencode($search)
	         ."'>Search for \"<i>$htmlsearch</i>\" in all STRs</a></p>\n");

	  html_footer();
	  exit();
	}

        if ($index >= $count)
	  $index = $count - ($count % $PAGE_MAX);
	if ($index < 0)
	  $index = 0;

        $start = $index + 1;
        $end   = $index + $PAGE_MAX;
	if ($end > $count)
	  $end = $count;

        $prev = $index - $PAGE_MAX;
	if ($prev < 0)
	  $prev = 0;
	$next = $index + $PAGE_MAX;

        print("<p>$count STR(s) found, showing $start to $end:\n");

        if ($LOGIN_LEVEL >= AUTH_DEVEL)
	  print("<form method='POST' action='$PHP_SELF?B$options'>\n");

        if ($count > $PAGE_MAX)
	{
          print("<table border='0' cellspacing='0' cellpadding='0' "
	       ."width='100%'>\n");

          print("<tr><td>");
	  if ($index > 0)
	    print("<a href='$PHP_SELF?L+P$priority+S$status+C$scope+I$prev+"
		 ."E$femail+Q"
		 . urlencode($search)
		 ."'>Previous&nbsp;$PAGE_MAX</a>");
          print("</td><td align='right'>");
	  if ($end < $count)
	  {
	    $next_count = min($PAGE_MAX, $count - $end);
	    print("<a href='$PHP_SELF?L+P$priority+S$status+C$scope+I$next+"
		 ."E$femail+Q"
		 . urlencode($search)
		 ."'>Next&nbsp;$next_count</a>");
          }
          print("</td></tr>\n");
	  print("</table>\n");
        }

        html_start_table(array("Id", "Priority", "Status", "Scope",
	                       "Summary", "Version", "Last Updated",
			       "Assigned To"));

	db_seek($result, $index);
	for ($i = 0; $i < $PAGE_MAX && $row = db_next($result); $i ++)
	{
	  $date     = date("M d, Y", $row['modify_date']);
          $summary  = htmlspecialchars($row['summary'], ENT_QUOTES);
	  $summabbr = htmlspecialchars(abbreviate($row['summary'], 80), ENT_QUOTES);
	  $prtext   = $priority_text[$row['priority']];
          $sttext   = $status_text[$row['status']];
          $sctext   = $scope_text[$row['scope']];
	  $link     = "<a href='$PHP_SELF?L$row[id]$options' "
	             ."alt='STR #$row[id]: $summary'>";

          html_start_row();

          if ($row['is_published'] == 0)
	    $summabbr .= " <img src='images/private.gif' width='16' height='16' "
	                ."border='0' align='absmiddle' alt='Private'>";

          print("<td nowrap>");
          if ($LOGIN_LEVEL >= AUTH_DEVEL)
	    print("<input type='checkbox' name='ID_$row[id]'>");
	  print("$link$row[id]</a></td>"
	       ."<td align='center'>$link$prtext</a></td>"
	       ."<td align='center'>$link$sttext</a></td>"
	       ."<td align='center'>$link$sctext</a></td>"
	       ."<td align='center'>$link$summabbr</a></td>"
	       ."<td align='center'>$link$row[str_version]</a></td>"
	       ."<td align='center'>$link$date</a></td>");

	  if ($row['manager_email'] != "")
	    $email = sanitize_email($row['manager_email']);
	  else
	    $email = "<i>Unassigned</i>";

	  print("<td align='center'>$link$email</a></td>");

	  html_end_row();

          if ($row['status'] >= $STR_STATUS_PENDING)
	  {
            $textresult = db_query("SELECT * FROM strtext "
	                          ."WHERE str_id = $row[id] "
	                          ."ORDER BY id DESC LIMIT 1");
            if ($textresult && db_count($textresult) > 0)
	    {
	      $textrow = db_next($textresult);

              html_start_row();

	      $email    = sanitize_email($textrow['create_user']);
	      $contents = abbreviate(quote_text($textrow['contents']), 128);

	      print("<td align='center' valign='top' colspan='2'>$email</td>"
		   ."<td valign='top' colspan='6' width='100%'>"
		   ."<tt>$contents</tt></td>");

	      html_end_row();

	      db_free($textresult);
	    }
          }
	}

        db_free($result);
	html_end_table();

        if ($LOGIN_LEVEL >= AUTH_DEVEL)
	{
	  print("<p><select name='STATUS'>"
	       ."<option value=''>Status</option>");
	  for ($i = 1; $i <= 5; $i ++)
	    print("<option value='$i'>$status_text[$i]</option>");
          print("</select>\n");

	  print("<select name='PRIORITY'>"
	       ."<option value=''>Priority</option>");
          for ($i = 1; $i <= 5; $i ++)
	    print("<option value='$i'>$priority_text[$i]</option>");
          print("</select>\n");

	  print("<select name='MANAGER_EMAIL'>"
	       ."<option value=''>Assigned To</option>");
	  reset($managers);
	  while (list($key, $val) = each($managers))
	  {
	    $temail = htmlspecialchars($val, ENT_QUOTES);
	    $temp   = sanitize_email($val);
	    print("<option value='$temail'>$temp</option>");
	  }
          print("</select>\n");

	  print("<select name='MESSAGE'>"
	       ."<option value=''>Text</option>");
	  reset($messages);
	  while (list($key, $val) = each($messages))
	  {
	    $temp = abbreviate($val);
	    print("<option value='$key'>$temp</option>");
	  }
          print("</select>\n");

	  print("<input type='submit' value='Modify Selected STRs'>");
	  print("</p>\n");
        }

        if ($count > $PAGE_MAX)
	{
          print("<table border='0' cellspacing='0' cellpadding='0' "
	       ."width='100%'>\n");

          print("<tr><td>");
	  if ($index > 0)
	    print("<a href='$PHP_SELF?L+P$priority+S$status+C$scope+I$prev+"
		 ."E$femail+Q"
		 . urlencode($search)
		 ."'>Previous&nbsp;$PAGE_MAX</a>");
          print("</td><td align='right'>");
	  if ($end < $count)
	  {
	    $next_count = min($PAGE_MAX, $count - $end);
	    print("<a href='$PHP_SELF?L+P$priority+S$status+C$scope+I$next+"
		 ."E$femail+Q"
		 . urlencode($search)
		 ."'>Next&nbsp;$next_count</a>");
          }
          print("</td></tr>\n");
	  print("</table>\n");
        }

	if ($LOGIN_LEVEL >= AUTH_DEVEL)
	  print("</form>");

	print("<p>"
	     ."MACH = Machine, "
	     ."OS = Operating System, "
	     ."STR = Software Trouble Report, "
	     ."<img src='images/private.gif' width='16' height='16' "
	     ."align='absmiddle' alt='private'> = hidden from public view</p>\n");
      }

      html_footer();
      break;

  case 'M' : // Modify STR
      if ($LOGIN_USER == "")
      {
	header("Location: login.php?PAGE=$PHP_SELF?M$id$options");
	return;
      }

      if ($REQUEST_METHOD == "POST")
      {
        if (array_key_exists("STATUS", $_POST))
	{
          $time          = time();
	  $master_id     = (int)$_POST["MASTER_ID"];
	  $summary       = db_escape($_POST["SUMMARY"]);
	  $subsystem     = db_escape($_POST["SUBSYSTEM"]);
          $manager_email = db_escape($_POST["MANAGER_EMAIL"]);
          $modify_user   = db_escape($_COOKIE["FROM"]);
          $contents      = db_escape(trim($_POST["CONTENTS"]));
	  $message       = $_POST["MESSAGE"];

          db_query("UPDATE str SET "
	          ."master_id = $master_id, "
	          ."is_published = $_POST[IS_PUBLISHED], "
	          ."status = $_POST[STATUS], "
	          ."priority = $_POST[PRIORITY], "
	          ."scope = $_POST[SCOPE], "
	          ."summary = '$summary', "
	          ."subsystem = '$subsystem', "
	          ."str_version = '$_POST[STR_VERSION]', "
	          ."fix_version = '$_POST[FIX_VERSION]', "
	          ."manager_email = '$manager_email', "
	          ."modify_date = $time, "
	          ."modify_user = '$modify_user' "
		  ."WHERE id = $id");
          
          if ($contents != "")
	  {
            db_query("INSERT INTO strtext VALUES(NULL,$id,1,'$contents',"
	            ."$time,'$modify_user')");
            $contents = trim($_POST["CONTENTS"]) . "\n\n";
	  }

          if ($message != "")
	  {
	    $contents = db_escape($messages[$message]);

            db_query("INSERT INTO strtext VALUES(NULL,$id,1,'$contents',"
	            ."$time,'$modify_user')");

            $contents = $messages[$message] . "\n\n";
	  }

	  header("Location: $PHP_SELF?L$id$options");

	  notify_users($id, "updated", $contents);
	}
	else if (array_key_exists("FILE_ID", $_POST))
	{
          db_query("UPDATE strfile SET "
	          ."is_published = $_POST[IS_PUBLISHED] "
		  ."WHERE id = $_POST[FILE_ID]");

	  header("Location: $PHP_SELF?M$id$options");
        }	  
	else if (array_key_exists("TEXT_ID", $_POST))
	{
          db_query("UPDATE strtext SET "
	          ."is_published = $_POST[IS_PUBLISHED] "
		  ."WHERE id = $_POST[TEXT_ID]");

	  header("Location: $PHP_SELF?M$id$options");
        }	  
	else
	  header("Location: $PHP_SELF?M$id$options");
      }
      else
      {
        html_header("Modify STR #$id");

        html_start_links(1);
	html_link("Return to Bugs &amp; Features", "$PHP_SELF?L$options");
	html_link("Return to STR #$id", "$PHP_SELF?L$id$options");
	html_link("Post Text", "$PHP_SELF?T$id$options");
	html_link("Post File", "$PHP_SELF?F$id$options");
	html_end_links();

        $result = db_query("SELECT * FROM str WHERE id = $id");
	if (db_count($result) != 1)
	{
	  print("<p><b>Error:</b> STR #$id was not found!</p>\n");
	  html_footer();
	  exit();
	}

        $row = db_next($result);

	$create_user   = htmlspecialchars($row['create_user']);
	$manager_email = htmlspecialchars($row['manager_email']);
	$summary       = htmlspecialchars($row['summary'], ENT_QUOTES);

        print("<form method='POST' action='$PHP_SELF?M$id$options'>"
	     ."<table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

        print("<tr><th align='right'>ID:</th><td>$id</td></tr>\n");

        print("<tr><th align='right'>Duplicate Of:</th>"
	     ."<td><input type='text' name='MASTER_ID' "
	     ."value='$row[master_id]' size='6'></td></tr>\n");

        print("<tr><th align='right'>Published:</th><td>");
	print("<select name='IS_PUBLISHED'>");
	if ($row['is_published'])
	{
	  print("<option value='0'>No</option>");
	  print("<option value='1' selected>Yes</option>");
	}
	else
	{
	  print("<option value='0' selected>No</option>");
	  print("<option value='1'>Yes</option>");
	}
	print("</select></td></tr>\n");

        print("<tr><th align='right'>Status:</th><td>");
	print("<select name='STATUS'>");
	for ($i = 1; $i <= 5; $i ++)
	{
	  print("<option value='$i'");
	  if ($row['status'] == $i)
	    print(" selected");
	  print(">$status_long[$i]</option>");
	}
        print("</select>\n");
	print("</td></tr>\n");

	print("<tr><th align='right'>Priority:</th><td>");
	print("<select name='PRIORITY'>");
        for ($i = 1; $i <= 5; $i ++)
	{
	  print("<option value='$i'");
	  if ($row['priority'] == $i)
	    print(" selected");
	  print(">$priority_long[$i]</option>");
	}
        print("</select></td></tr>\n");

	print("<tr><th align='right'>Scope:</th><td>");
	print("<select name='SCOPE'>");
	for ($i = 1; $i <= 3; $i ++)
	{
	  print("<option value='$i'");
	  if ($row['scope'] == $i)
	    print(" selected");
	  print(">$scope_long[$i]</option>");
	}
        print("</select></td></tr>\n");

	print("<tr><th align='right'>Subsystem:</th>"
	     ."<td><select name='SUBSYSTEM'>"
	     ."<option value=''>Unassigned</option>");

	reset($subsystems);
	while (list($key, $val) = each($subsystems))
	{
	  print("<option value='$val'");
	  if ($row['subsystem'] == $val)
	    print(" selected");
	  print(">$val</option>");
	}
        print("</select></td></tr>\n");

	print("<tr><th align='right'>Summary:</th>"
	     ."<td><input type='text' name='SUMMARY' size='72' "
	     ."value='$summary'></td></tr>\n");

	print("<tr><th align='right'>Version:</th>"
	     ."<td><select name='STR_VERSION'>"
	     ."<option value=''>Unassigned</option>");

	reset($versions);
	while (list($key, $val) = each($versions))
	{
	  if ($val[0] == '+')
	    $val = substr($val, 1);

	  print("<option value='$val'");
	  if ($row['str_version'] == $val)
	    print(" selected");
	  print(">$val</option>");
	}
        print("</select></td></tr>\n");

	print("<tr><th align='right'>Created By:</th>"
	     ."<td>$create_user</td></tr>\n");

	print("<tr><th align='right'>Assigned To:</th>"
	     ."<td><select name='MANAGER_EMAIL'>"
	     ."<option value=''>Unassigned</option>");

	reset($managers);
	while (list($key, $val) = each($managers))
	{
	  $temail = htmlspecialchars($val, ENT_QUOTES);
	  $temp   = sanitize_email($val);

	  print("<option value='$temail'");
	  if ($row['manager_email'] == $val)
	    print(" selected");
	  print(">$temp</option>");
	}
        print("</select></td></tr>\n");

	print("<tr><th align='right'>Fix Version:</th>"
	     ."<td><select name='FIX_VERSION'>"
	     ."<option value=''>Unassigned</option>"
	     ."<option>Not Applicable</option>");

	reset($versions);
	while (list($key, $val) = each($versions))
	{
	  if ($val[0] == '+')
	    $val = substr($val, 1);

	  print("<option value='$val'");
	  if ($row['fix_version'] == $val)
	    print(" selected");
	  print(">$val</option>");
	}
        print("</select></td></tr>\n");

	print("<tr><th align='right' valign='top'>Text:</th><td>");

	print("<select name='MESSAGE'>"
	     ."<option value=''>--- Pick a Standard Message ---</option>");

	reset($messages);
	while (list($key, $val) = each($messages))
	{
	  $temp = abbreviate($val, 72);
	  print("<option value='$key'>$temp</option>");
	}
        print("</select><br>\n");

	print("<textarea name='CONTENTS' cols='72' rows='12' wrap='virtual'>"
             ."</textarea></td></tr>\n");

        print("<tr><th align='center' colspan='2'>"
	     ."<input type='submit' value='Update Trouble Report'></th></tr>\n");
        print("</table></form>\n");

	print("<p><b>Trouble Report Files:</b> "
	     ."<a href='$PHP_SELF?F$id$options'>Post&nbsp;File</a>"
	     ."</p>\n");

	$result = db_query("SELECT * FROM strfile WHERE str_id = $id");

        if (db_count($result) == 0)
	  print("<p><i>No files</i></p>\n");
	else
	{
	  print("<table width='100%' border='0' cellpadding='5' "
	       ."cellspacing='0'>\n"
	       ."<tr class='header'><th>Name/Time/Date</th>"
	       ."<th>Filename</th></tr>\n");

          $line = 0;
	  while ($row = db_next($result))
	  {
            $date     = date("M d, Y", $row['create_date']);
            $time     = date("H:i", $row['create_date']);
	    $email    = sanitize_email($row['create_user']);
	    $filename = htmlspecialchars($row['filename']);

	    print("<tr class='data$line'>"
	         ."<td align='center' valign='top'>$email<br>$time $date<br>"
		 ."<form method='POST' action='$PHP_SELF?M$id$options'>"
		 ."<input type='hidden' name='FILE_ID' value='$row[id]'>");

            if ($row['is_published'])
	      print("<input type='hidden' name='IS_PUBLISHED' value='0'>"
	           ."<input type='submit' value='Hide'>");
            else
	      print("<input type='hidden' name='IS_PUBLISHED' value='1'>"
	           ."<input type='submit' value='Show'>");

	    print("</form></td>"
		 ."<td align='center' valign='top'>"
		 ."<a href='strfiles/$id/$filename'>$filename</a></td>"
		 ."</tr>\n");

	    $line = 1 - $line;
	  }
          print("</table>\n");
        }

	db_free($result);

	print("<p><b>Trouble Report Dialog:</b> "
	     ."<a href='$PHP_SELF?T$id$options'>Post&nbsp;Text</a>"
	     ."</p>\n");

	$result = db_query("SELECT * FROM strtext WHERE "
	                  ."str_id = $id");

        if (db_count($result) == 0)
	  print("<p><i>No text</i></p>\n");
	else
	{
	  print("<table width='100%' border='0' cellpadding='5' "
	       ."cellspacing='0'>\n"
	       ."<tr class='header'><th>Name/Time/Date</th>"
	       ."<th>Text</th></tr>\n");

          $line = 0;

	  while ($row = db_next($result))
	  {
            $date     = date("M d, Y", $row['create_date']);
            $time     = date("H:i", $row['create_date']);
	    $email    = sanitize_email($row['create_user']);
	    $contents = quote_text($row['contents']);

	    print("<tr class='data$line'>"
	         ."<td align='center' valign='top'>$email<br>$time $date<br>"
		 ."<form method='POST' action='$PHP_SELF?M$id$options'>"
		 ."<input type='hidden' name='TEXT_ID' value='$row[id]'>");

            if ($row['is_published'])
	      print("<input type='hidden' name='IS_PUBLISHED' value='0'>"
	           ."<input type='submit' value='Hide'>");
            else
	      print("<input type='hidden' name='IS_PUBLISHED' value='1'>"
	           ."<input type='submit' value='Show'>");

	    print("</form></td>"
		 ."<td valign='top'><tt>$contents</tt></td>"
		 ."</tr>\n");

	    $line = 1 - $line;
	  }
          print("</table>\n");
	}

	db_free($result);

        html_footer();
      }
      break;

  case 'T' : // Post text for STR #
      if ($LOGIN_USER == "")
      {
	header("Location: login.php?PAGE=$PHP_SELF?T$id$options");
	return;
      }

      if ($REQUEST_METHOD == "POST")
      {
	$contents = $_POST["CONTENTS"];

	if ($LOGIN_USER != "" && $LOGIN_LEVEL < AUTH_DEVEL)
	  $email = $LOGIN_USER;
	else if (array_key_exists("EMAIL", $_POST) &&
	         validate_email($_POST["EMAIL"]))
	{
	  $email = $_POST["EMAIL"];
	  setcookie("FROM", "$email", time() + 90 * 86400, "/");
	}
	else if (array_key_exists("FROM", $_COOKIE))
          $email = $_COOKIE["FROM"];
	else
	  $email = "";

	if (ereg("Anonymous.*", $email))
	  $email = "";

        if ($email != "" && $contents != "")
	  $havedata = 1;
      }
      else
      {
	if ($LOGIN_USER != "")
	  $email = $LOGIN_USER;
	else if (array_key_exists("FROM", $_COOKIE))
          $email = $_COOKIE["FROM"];
	else
	  $email = "";

	$contents = "";

	if (ereg("Anonymous.*", $email))
	  $email = "";
      }

      if ($REQUEST_METHOD == "POST" && $havedata)
      {
        $time      = time();
	$temail    = db_escape($email);
	$tcontents = db_escape($contents);

        db_query("INSERT INTO strtext VALUES(NULL,$id,1,'$tcontents',"
	        ."$time,'$temail')");

        db_query("UPDATE str SET modify_date=$time, modify_user='$temail' "
	        ."WHERE id = $id");
        db_query("UPDATE str SET status=$STR_STATUS_PENDING WHERE "
	        ."id = $id AND status >= $STR_STATUS_ACTIVE AND "
		."status < $STR_STATUS_NEW");

	header("Location: $PHP_SELF?L$id$options");

        notify_users($id, "updated", "$contents\n\n");
      }
      else
      {
        html_header("Post Text For STR #$id");

        html_start_links(1);
	html_link("Return to STR #$id", "$PHP_SELF?L$id$options");
	html_end_links();

        if ($REQUEST_METHOD == "POST")
	{
	  print("<p><b>Error:</b> Please fill in the fields marked in "
	       ."<b><font color='red'>bold red</font></b> below and resubmit "
	       ."your trouble report.</p>\n");

	  $hstart = "<font color='red'>";
	  $hend   = "</font>";
	}
	else
	{
	  $hstart = "";
	  $hend   = "";
	}

        print("<form method='POST' action='$PHP_SELF?T$id$options'>"
	     ."<table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

	print("<tr><th align='right'>");
	if ($email != "")
	  print("EMail:</th><td>");
	else
	  print("${hstart}EMail:$hend</th><td>");

        $temp = htmlspecialchars($email);
        print("<input type='text' name='EMAIL' value='$temp' size='40' "
	     ."maxsize='128'></td></tr>\n");

	print("<tr><th align='right' valign='top'>");
	if ($contents != "")
	  print("Text:</th><td>");
	else
	  print("${hstart}Text:$hend</th><td>");

        $temp = htmlspecialchars($contents);
        print("<textarea name='CONTENTS' cols='72' rows='12' wrap='virtual'>"
             ."$temp</textarea></td></tr>\n");

        print("<tr><th align='center' colspan='2'>"
	     ."<input type='submit' value='Post Text to Trouble Report'></th></tr>\n");
        print("</table></form>\n");
        html_footer();
      }
      break;

  case 'F' : // Post file for STR #
      if ($LOGIN_USER == "")
      {
	header("Location: login.php?PAGE=$PHP_SELF?F$id$options");
	return;
      }

      if ($REQUEST_METHOD == "POST")
      {
        if ($LOGIN_USER != "" && $LOGIN_LEVEL < AUTH_DEVEL)
	  $email = $LOGIN_USER;
	else if (array_key_exists("EMAIL", $_POST) &&
	         validate_email($_POST["EMAIL"]))
	{
	  $email = $_POST["EMAIL"];
	  setcookie("FROM", "$email", time() + 90 * 86400, "/");
	}
	else if (array_key_exists("FROM", $_COOKIE))
          $email = $_COOKIE["FROM"];
	else
	  $email = "";

	if (ereg("Anonymous.*", $email))
	  $email = "";

        if (array_key_exists("STRFILE", $_FILES))
	{
	  $filename = $_FILES['STRFILE']['name'];
	  if ($filename[0] == '.' || $filename[0] == '/')
	    $filename = "";
	}
	else
	  $filename = "";

        if ($email != "" && $filename != "")
	  $havedata = 1;
      }
      else
      {
	if ($LOGIN_USER != "")
	  $email = $LOGIN_USER;
	else if (array_key_exists("FROM", $_COOKIE))
          $email = $_COOKIE["FROM"];
	else
	  $email = "";

	$filename = "";

	if (ereg("Anonymous.*", $email))
	  $email = "";
      }

      if ($REQUEST_METHOD == "POST" && $havedata)
      {
        $time     = time();
	$temail   = db_escape($email);
        $tmp_name = $_FILES['STRFILE']['tmp_name'];
        $name     = $_FILES['STRFILE']['name'];
        $tname    = db_escape($name);

        $infile = fopen($tmp_name, "rb");

	if (!$infile)
	{
	  html_header("Error");
	  print("<p><b>Error!</b> Unable to open file attachment!</p>\n");
	  html_footer();
	  exit();
	}

        mkdir("strfiles/$id");
	$outfile = fopen("strfiles/$id/$name", "wb");

	if (!$outfile)
	{
	  html_header("Error");
	  print("<p><b>Error!</b> Unable to save file attachment!</p>\n");
	  html_footer();
	  exit();
	}

        while ($data = fread($infile, 8192))
	  fwrite($outfile, $data);

        fclose($infile);
	fclose($outfile);

        db_query("INSERT INTO strfile VALUES(NULL,$id,1,'$tname',"
	        ."$time,'$temail')");

        db_query("UPDATE str SET modify_date=$time, modify_user='$temail' "
	        ."WHERE id = $id");
        db_query("UPDATE str SET status=$STR_STATUS_PENDING WHERE "
	        ."id = $id AND status >= $STR_STATUS_ACTIVE AND "
		."status < $STR_STATUS_NEW");

	header("Location: $PHP_SELF?L$id$options");

        notify_users($id, "updated", "Added file $name\n\n");
      }
      else
      {
        html_header("Post File For STR #$id");

        html_start_links(1);
	html_link("Return to STR #$id", "$PHP_SELF?L$id$options");
	html_end_links();

        if ($REQUEST_METHOD == "POST")
	{
	  print("<p><b>Error:</b> Please fill in the fields marked in "
	       ."<b><font color='red'>bold red</font></b> below and resubmit "
	       ."your trouble report.</p>\n");

	  $hstart = "<font color='red'>";
	  $hend   = "</font>";
	}
	else
	{
	  $hstart = "";
	  $hend   = "";
	}

        print("<form method='POST' action='$PHP_SELF?F$id$options' "
	     ."enctype='multipart/form-data'>"
	     ."<input type='hidden' name='MAX_FILE_SIZE' value='10000000'>");

	print("<table width='100%' cellpadding='5' cellspacing='0' "
             ."border='0'>\n");

	print("<tr><th align='right'>");
	if ($email != "")
	  print("EMail:</th><td>");
	else
	  print("${hstart}EMail:$hend</th><td>");

        $temp = htmlspecialchars($email);
        print("<input type='text' name='EMAIL' value='$temp' size='40' "
	     ."maxsize='128'></td></tr>\n");

	print("<tr><th align='right' valign='top'>");
	if (array_key_exists("STRFILE", $_FILES))
	  print("File:</th><td>");
	else
	  print("${hstart}File:$hend</th><td>");

        print("<input name='STRFILE' type='FILE'></td></tr>\n");

        print("<tr><th align='center' colspan='2'>"
	     ."<input type='submit' value='Post File to Trouble Report'></th></tr>\n");
        print("</table></form>\n");
        html_footer();
      }
      break;

  case 'N' : // Post new STR
      if ($LOGIN_USER == "")
      {
	header("Location: login.php?PAGE=$PHP_SELF?N$options");
	return;
      }

      $havedata = 0;

      if ($REQUEST_METHOD == "POST")
      {
	$npriority = $_POST["PRIORITY"];
	$nscope    = $_POST["SCOPE"];
	$summary   = $_POST["SUMMARY"];
	$version   = $_POST["VERSION"];
	$contents  = $_POST["CONTENTS"];
	$email     = $LOGIN_USER;

        if (array_key_exists("STRFILE", $_FILES))
	{
	  $filename = $_FILES['STRFILE']['name'];
	  if ($filename[0] == '.' || $filename[0] == '/')
	    $filename = "";
	}
	else
	  $filename = "";

        if ($npriority && $nscope && $summary != "" && $email != "" &&
	    $version != "" && $contents != "")
	  $havedata = 1;
      }
      else
      {
	$email     = $LOGIN_USER;
        $npriority = 0;
	$nscope    = 0;
	$summary   = "";
	$version   = "";
	$contents  = "";
	$filename  = "";
      }

      if ($REQUEST_METHOD == "POST" && $havedata)
      {
        $time      = time();
	$temail    = db_escape($email);
	$tsummary  = db_escape($summary);
	$tcontents = db_escape($contents);

        db_query("INSERT INTO str VALUES(NULL,0,"
	        ."$_POST[IS_PUBLISHED],$STR_STATUS_NEW,"
	        ."$npriority,$nscope,'$tsummary','','$version','','',"
		."$time,'$temail',$time,'$temail')");

	$id = db_insert_id();

        db_query("INSERT INTO strtext VALUES(NULL,$id,1,'$tcontents',"
	        ."$time,'$temail')");

        if ($filename != "")
	{
          $tmp_name = $_FILES['STRFILE']['tmp_name'];
          $name     = $_FILES['STRFILE']['name'];
          $tname    = db_escape($name);

          $infile = fopen($tmp_name, "rb");

	  if (!$infile)
	  {
	    html_header("Error");
	    print("<p><b>Error!</b> Unable to open file attachment!</p>\n");
	    html_footer();
	    exit();
	  }

          mkdir("strfiles/$id");
	  $outfile = fopen("strfiles/$id/$name", "wb");

	  if (!$outfile)
	  {
	    html_header("Error");
	    print("<p><b>Error!</b> Unable to save file attachment!</p>\n");
	    html_footer();
	    exit();
	  }

          while ($data = fread($infile, 8192))
	    fwrite($outfile, $data);

          fclose($infile);
	  fclose($outfile);

          db_query("INSERT INTO strfile VALUES(NULL,$id,1,'$tname',"
	          ."$time,'$temail')");
        }

	header("Location: $PHP_SELF?L$id$options");
        notify_users($id, "created", "$contents\n\n");
      }
      else
      {
        html_header("Submit Bug or Feature Request");

        html_start_links(1);
	html_link("Return to Bugs &amp; Features", "$PHP_SELF?L$options");
	html_end_links();

        if ($REQUEST_METHOD == "POST")
	{
	  print("<p><b>Error:</b> Please fill in the fields marked "
	       ."<span class='invalid'>like this</span> below and resubmit "
	       ."your trouble report.</p>\n");

	  $hstart = "<span class='invalid'>";
	  $hend   = "</span>";
	}
	else
	{
	  print("<p>Please use this form to report all bugs and request "
	       ."features in the $PROJECT_NAME software. Be sure to include "
	       ."the operating system, compiler, sample programs and/or "
	       ."files, and any other information you can about your "
	       ."problem. <i>Thank you</i> for helping us to improve "
	       ."$PROJECT_NAME!</p>\n");

	  $hstart = "";
	  $hend   = "";

          $recent = time() - 90 * 86400;
          $result = db_query("SELECT master_id, "
                            ."count(master_id) AS count FROM str "
	                    ."WHERE master_id > 0 AND modify_date > $recent "
			    ."GROUP BY master_id "
			    ."ORDER BY count DESC, modify_date DESC");
          if (db_count($result) > 0)
	  {
	    print("<hr noshade>\n"
	         ."<p>Commonly reported bugs:</p>\n"
	         ."<ul>\n");
            $count = 0;
	    while ($row = db_next($result))
	    {
              $count ++;
              if ($count > 10)
                break;

              $common   = db_query("SELECT summary, status, fix_version "
	                          ."FROM str WHERE id=$row[master_id]");
              $crow     = db_next($common);
	      $csummary = htmlspecialchars($crow["summary"], ENT_QUOTES);
	      $cstatus  = $status_text[$crow["status"]];

              if ($crow["fix_version"] != "")
	        $cstatus .= ", $crow[fix_version]";

	      print("<li><a href='$PHP_SELF?L$row[master_id]$options'>"
	           ."STR #$row[master_id]: $csummary ($cstatus)</a></li>\n");
	    }
	    print("</ul>\n");
	  }
        }

        print("<hr noshade>\n"
	     ."<form method='POST' action='$PHP_SELF?N$options' "
	     ."enctype='multipart/form-data'>"
	     ."<input type='hidden' name='MAX_FILE_SIZE' value='10000000'>");

        print("<table width='100%' cellpadding='5' cellspacing='0' "
	      ."border='0'>\n");

        print("<tr><th align='right'>Security Advisory:</th><td>"
	     ."<select name='IS_PUBLISHED'>"
	     ."<option value='1' selected>No, make this STR publicly available.</option>"
	     ."<option value='0'>Yes, hide this STR from public view.</option>"
	     ."</select></td></tr>\n");

        print("<tr><th align='right'>Status:</th><td>5 - New</td></tr>\n");

	print("<tr><th align='right' valign='top'>");
	if ($npriority > 0)
	  print("Priority:</th><td nowrap>");
	else
	  print("${hstart}Priority:$hend</th><td nowrap>");
        for ($i = 1; $i <= 5; $i ++)
	{
	  print("<input type='radio' name='PRIORITY' value='$i'");
          if ($npriority == $i)
	    print(" checked");
	  print(">$priority_long[$i]<br>");
	}
	print("</td></tr>\n");

	print("<tr><th align='right' valign='top'>");
	if ($nscope > 0)
	  print("Scope:</th><td>");
	else
	  print("${hstart}Scope:$hend</th><td>");
        for ($i = 1; $i <= 3; $i ++)
	{
	  print("<input type='radio' name='SCOPE' value='$i'");
          if ($nscope == $i)
	    print(" checked");
	  print(">$scope_long[$i]<br>");
	}
	print("</td></tr>\n");

	print("<tr><th align='right'>Subsystem:</th><td><i>Unassigned</i></td></tr>\n");

	print("<tr><th align='right'>");
	if ($summary != "")
	  print("Summary:</th><td>");
	else
	  print("${hstart}Summary:$hend</th><td>");

        $temp = htmlspecialchars($summary, ENT_QUOTES);
        print("<input type='text' name='SUMMARY' size='72' value='$temp'></td></tr>\n");

	print("<tr><th align='right'>");
	if ($version != "")
	  print("Version:</th><td>");
	else
	  print("${hstart}Version:$hend</th><td>");

        print("<select name='VERSION'>"
	     ."<option value=''>Select a Version Number</option>");

	reset($versions);
	while (list($key, $val) = each($versions))
	{
	  if ($val[0] == '+')
	    continue;

	  print("<option value='$val'");
	  if ($version == $val)
	    print(" selected");
	  print(">$val</option>");
	}

        print("</select></td></tr>\n");

        $temp = htmlspecialchars($email);
	print("<tr><th align='right'>Created By:</th><td>$temp</td></tr>\n");

	print("<tr><th align='right'>Assigned To:</th><td><i>Unassigned</i></td></tr>\n");

	print("<tr><th align='right'>Fix Version:</th><td><i>Unassigned</i></td></tr>\n");

	print("<tr><th align='right' valign='top'>");
	if ($contents != "")
	  print("Detailed Description of Problem:</th><td>");
	else
	  print("${hstart}Detailed Description of Problem:$hend</th><td>");

        $temp = htmlspecialchars($contents);
        print("<textarea name='CONTENTS' cols='72' rows='12' wrap='virtual'>"
             ."$temp</textarea></td></tr>\n");

	print("<tr><th align='right' valign='top'>File:</th><td>");

        print("<input name='STRFILE' type='FILE'></td></tr>\n");

        print("<tr><th align='center' colspan='2'>"
	     ."<input type='submit' value='Submit Bug or Feature Request'></th></tr>\n");
        print("</table></form>\n");
        html_footer();
      }
      break;

  case 'U' : // Update notification status
      // EMAIL and NOTIFICATION variables hold status; add/delete from strcc...
      $havedata = 0;

      if ($REQUEST_METHOD != "POST")
      {
	html_header("Bugs &amp; Features Error");
	print("<p>The '$op' command requires a POST request!\n");
	html_footer();
	exit();
      }

      $notification = $_POST["NOTIFICATION"];
      $email        = $_POST["EMAIL"];

      if (($notification != "ON" && $notification != "OFF") || $email == "" ||
          !validate_email($email))
      {
	html_header("Bugs &amp; Features Error");
	print("<p>Please press your browsers back button and enter a valid "
	     ."EMail address and choose whether to receive notification "
	     ."messages.</p>\n");
	html_footer();
	exit();
      }

      setcookie("FROM", "$email", time() + 90 * 86400, "/");

      $result = db_query("SELECT * FROM carboncopy WHERE "
                        ."url = 'str.php_L$id' AND email = '$email'");

      html_header("STR #$id Notifications");

      html_start_links();
      html_link("Return to STR #$id", "$PHP_SELF?L$id$options");
      html_end_links();

      if ($notification == "ON")
      {
        if ($result && db_count($result) > 0)
	  print("<p>Your email address has already been added to the "
	       ."notification list for STR #$id!</p>\n");
        else
	{
          db_query("INSERT INTO carboncopy VALUES(NULL,'str.php?L$id','$email')");
	
	  print("<p>Your email address has been added to the notification list "
               ."for STR #$id.</p>\n");
        }
      }
      else if ($result && db_count($result) > 0)
      {
        db_query("DELETE FROM carboncopy WHERE "
	        ."url = 'str.php?L$id' AND email = '$email'");

	print("<p>Your email address has been removed from the notification list "
             ."for STR #$id.</p>\n");
      }
      else
      {
	print("<p>Your email address is not on the notification list for "
	     ."STR #$id!</p>\n");
      }

      if ($result)
        db_free($result);

      html_footer();
      break;
}

//
// End of "$Id$".
//
?>
