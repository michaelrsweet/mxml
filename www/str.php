<?php

// Standard stuff...
include_once "phplib/html.php";
include_once "phplib/common.php";
include_once "phplib/db.php";

// STR constants...
$STR_PAGE_MAX = 10; // Max STRs per page

$STR_STATUS_RESOLVED   = 1;
$STR_STATUS_UNRESOLVED = 2;
$STR_STATUS_ACTIVE     = 3;
$STR_STATUS_PENDING    = 4;
$STR_STATUS_NEW        = 5;

$STR_PRIORITY_RFE      = 1;
$STR_PRIORITY_LOW      = 2;
$STR_PRIORITY_MODERATE = 3;
$STR_PRIORITY_HIGH     = 4;
$STR_PRIORITY_CRITICAL = 5;

$STR_SCOPE_UNIT        = 1;
$STR_SCOPE_FUNCTION    = 2;
$STR_SCOPE_SOFTWARE    = 3;

// String definitions for various things...
$managers = array(
  "mike" => "Michael Sweet <mike@easysw.com>"
);

$messages = array(
  "Fixed in CVS" =>
      "Fixed in CVS - the anonymous CVS repository will be updated at "
     ."midnight EST.",
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
  "2.0cvs",
  "1.3",
  "1.2",
  "1.1.2",
  "1.1.1",
  "1.1",
  "1.0",
  "Web Site"
);

$status_text = array(
  1 => "Resolved",
  2 => "Unresolved",
  3 => "Active",
  4 => "Pending",
  5 => "New"
);

$status_long = array(
  1 => "1 - Closed w/Resolution",
  2 => "2 - Closed w/o Resolution",
  3 => "3 - Active",
  4 => "4 - Pending",
  5 => "5 - New"
);

$priority_text = array(
  1 => "RFE",
  2 => "LOW",
  3 => "MODERATE",
  4 => "HIGH",
  5 => "CRITICAL"
);

$priority_long = array(
  1 => "1 - Request for Enhancement, e.g. asking for a feature",
  2 => "2 - Low, e.g. a documentation error or undocumented side-effect",
  3 => "3 - Moderate, e.g. unable to compile the software",
  4 => "4 - High, e.g. key functionality not working",
  5 => "5 - Critical, e.g. nothing working at all"
);

$scope_text = array(
  1 => "M/P",
  2 => "OS",
  3 => "ALL"
);

$scope_long = array(
  1 => "1 - Specific to a machine",
  2 => "2 - Specific to an operating system",
  3 => "3 - Applies to all machines and operating systems"
);

// Global web vars...
global $_COOKIE, $_FILES, $_POST, $_SERVER;

$argc           = $_SERVER["argc"];
$argv           = $_SERVER["argv"];
$PHP_SELF       = $_SERVER["PHP_SELF"];
$REMOTE_USER    = $_SERVER["PHP_AUTH_USER"];
$REQUEST_METHOD = $_SERVER["REQUEST_METHOD"];
$SERVER_NAME    = $_SERVER["SERVER_NAME"];

// Function to abbreviate long strings...
function abbreviate($text, $maxlen = 32)
{
  if (strlen($text) > $maxlen)
    return (substr($text, 0, $maxlen) . "...");
  else
    return ($text);
}


// Function to notify creator of an STR of changes...
function notify_creator($id, $what = "updated", $contents = "")
{
  global $priority_long;
  global $scope_long;
  global $status_long;

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

    if ($row['create_email'] != $row['modify_email'] &&
        $row['create_email'] != $manager)
      mail($row['create_email'], "Mini-XML STR #$id $what",
	   "Your software trouble report #$id has been $what.  You can check\n"
	  ."the status of the report and add additional comments and/or files\n"
	  ."at the following URL:\n"
	  ."\n"
	  ."    http://www.easysw.com/str.php?L$id\n"
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
	  ."Thank you for using the Mini-XML Software Trouble Report page!",
	   "From: noreply@easysw.com\r\n");

    $ccresult = db_query("SELECT email FROM strcc WHERE str_id = $id");
    if ($ccresult)
    {
      while ($ccrow = db_next($ccresult))
      {
	mail($ccrow->email, "Mini-XML STR #$id $what",
	     "Software trouble report #$id has been $what.  You can check\n"
	    ."the status of the report and add additional comments and/or files\n"
	    ."at the following URL:\n"
	    ."\n"
	    ."    http://www.easysw.com/str.php?L$id\n"
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
	    ."Thank you for using the Mini-XML Software Trouble Report page!",
	     "From: noreply@easysw.com\r\n");
      }

      db_free($ccresult);
    }

    if ($row['manager_email'] != "")
      $manager = $row['manager_email'];
    else
      $manager = "mxml";

    if ($row['modify_email'] != $manager)
      mail($manager, "Mini-XML STR #$id $what",
	   "The software trouble report #$id assigned to you has been $what.\n"
	  ."You can manage the report and add additional comments and/or files\n"
	  ."at the following URL:\n"
	  ."\n"
	  ."    http://www.easysw.com/private/str.php?L$id\n"
	  ."\n"
	  ."    Summary: $row[summary]\n"
	  ."    Version: $row[str_version]\n"
	  ."     Status: $sttext\n"
	  ."   Priority: $prtext\n"
	  ."      Scope: $sctext\n"
	  ."  Subsystem: $subsystem\n"
	  ."Fix Version: $fix_version\n"
	  ."\n$contents",
	   "From: noreply@easysw.com\r\n");

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
    html_header("STR Error");
    print("<p>Bad command '$op'!</p>\n");
    html_footer();
    exit();
  }

  if (($op == 'M' || $op == 'B') && !$REMOTE_USER)
  {
    html_header("STR Error");
    print("<p>The '$op' command is not available to you!</p>\n");
    html_footer();
    exit();
  }

  if (($op == 'M' || $op == 'T' || $op == 'F') && !$id)
  {
    html_header("STR Error");
    print("<p>Command '$op' requires an STR number!</p>\n");
    html_footer();
    exit();
  }

  if ($op == 'N' && $id)
  {
    html_header("STR Error");
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
          $search = $option;
	  $i ++;
	  while ($i < $argc)
	  {
	    $search .= " $argv[$i]";
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
	  html_header("STR Error");
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
        $modify_email  = db_escape($managers[$REMOTE_USER]);
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

        $query = "modify_date = $time, modify_email = '$modify_email'";

	if ($_POST["STATUS"] != "")
	  $query .= ", status = $_POST[STATUS]";
	if ($_POST["PRIORITY"] != "")
	  $query .= ", priority = $_POST[PRIORITY]";
	if ($manager_email != "")
	  $query .= ", manager_email = '$manager_email'";

        reset($_POST);
        while (list($key, $val) = each($_POST))
          if (substr($key, 0, 3) == "ID_")
	  {
	    $id = (int)substr($key, 3);

            db_query("UPDATE str SET $query WHERE id = $id");

            if ($contents != "")
	    {
              db_query("INSERT INTO strtext VALUES(NULL,$id,1,$time,"
	              ."'$modify_email','$contents')");

	      notify_creator($id, "updated", $mailmsg);
	    }
	  }
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

        print("<p align='center'>"
	     ."[&nbsp;<a href='$PHP_SELF?L$options'>Return&nbsp;to&nbsp;STR&nbsp;List</a>");

        if ($row['status'] >= $STR_STATUS_ACTIVE)
	  print(" | <a href='$PHP_SELF?T$id$options'>Post&nbsp;Text</a>"
	       ." | <a href='$PHP_SELF?F$id$options'>Post&nbsp;File</a>");

	if ($REMOTE_USER)
	  print(" | <a href='$PHP_SELF?M$id$options'>Modify&nbsp;STR</a>");

        print("&nbsp;]</p><hr />\n");

	$create_email  = sanitize_email($row['create_email']);
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

        print("<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

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
	print("<tr><th align='right'>Created By:</th><td>$create_email</td></tr>\n");
	print("<tr><th align='right'>Assigned To:</th><td>$manager_email</td></tr>\n");
	print("<tr><th align='right'>Fix Version:</th><td>$fix_version</td></tr>\n");

	if ($REMOTE_USER)
	  $email = htmlspecialchars($managers[$REMOTE_USER]);
	else if (array_key_exists("FROM", $_COOKIE))
          $email = htmlspecialchars($_COOKIE["FROM"]);
	else
	  $email = "";

        print("<tr><th align='right' valign='top'>Update Notification:</th><td>"
	     ."<form method='POST' action='$PHP_SELF?U$id$options'>"
	     ."<input type='text' size='40' maxsize='128' name='EMAIL' value='$email'>"
	     ."<input type='submit' value='Change Notification Status'>"
	     ."<br /><input type='radio' name='NOTIFICATION' checked value='ON'>Receive EMails "
	     ."<input type='radio' name='NOTIFICATION' value='OFF'>Don't Receive EMails"
	     ."</form>"
	     ."</td></tr>\n");
        print("</table></p>\n");

        db_free($result);

	print("<hr /><p><b>Trouble Report Files:</b>");
        if ($row['status'] >= $STR_STATUS_ACTIVE)
	  print(" [&nbsp;<a href='$PHP_SELF?F$id$options'>Post&nbsp;File</a>&nbsp;]");
        print("</p>\n");

	$result = db_query("SELECT * FROM strfile WHERE "
	                  ."str_id = $id AND is_published = 1");

        if (db_count($result) == 0)
	  print("<p><i>No files</i></p>\n");
	else
	{
	  print("<p><table width='100%' border='0' cellpadding='5' "
	       ."cellspacing='0'>\n"
	       ."<tr class='header'><th>Name/Time/Date</th>"
	       ."<th>Filename</th></tr>\n");

          $line = 0;
	  while ($row = db_next($result))
	  {
            $date     = date("M d, Y", $row['date']);
            $time     = date("H:m", $row['date']);
	    $email    = sanitize_email($row['email']);
	    $filename = htmlspecialchars($row['filename']);

	    print("<tr class='data$line'>"
	         ."<td align='center' valign='top'>$email<br />$time $date</td>"
		 ."<td align='center' valign='top'>"
		 ."<a href='strfiles/$id/$filename'>$filename</a></td>"
		 ."</tr>\n");

            $line = 1 - $line;
	  }
          print("</table></p>\n");
        }

	db_free($result);

	print("<hr /><p><b>Trouble Report Dialog:</b>");
        if ($row['status'] >= $STR_STATUS_ACTIVE)
	  print(" [&nbsp;<a href='$PHP_SELF?T$id$options'>Post&nbsp;Text</a>&nbsp;]");
	print("</p>\n");

	$result = db_query("SELECT * FROM strtext WHERE "
	                  ."str_id = $id AND is_published = 1");

        if (db_count($result) == 0)
	  print("<p><i>No text</i></p>\n");
	else
	{
	  print("<p><Table width='100%' border='0' cellpadding='5' "
	       ."cellspacing='0'>\n"
	       ."<tr class='header'><th>Name/Time/Date</th>"
	       ."<th>Text</th></tr>\n");

          $line = 0;

	  while ($row = db_next($result))
	  {
            $date     = date("M d, Y", $row['date']);
            $time     = date("H:m", $row['date']);
	    $email    = sanitize_email($row['email']);
	    $contents = quote_text($row['contents']);

	    print("<tr class='data$line'>"
	         ."<td align='center' valign='top'>$email<br />$time $date</td>"
		 ."<td valign='top'><tt>$contents</tt></td>"
		 ."</tr>\n");

            $line = 1 - $line;
	  }
          print("</table></p>\n");
	}

	db_free($result);
      }
      else
      {
        html_header("STR List");

	print("<p align='center'>[ <a href='$PHP_SELF?N$options'>Post "
	     ."New Software Trouble Report</a> ]</p>\n");

        print("<form method='POST' action='$PHP_SELF'><p align='center'>"
	     ."Search&nbsp;Words: &nbsp;<input type='text' size='60' name='SEARCH' value='$search'>"
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

        if ($REMOTE_USER || array_key_exists("FROM", $_COOKIE))
	{
	  print("Show:&nbsp;<select name='FEMAIL'>");
          print("<option value='0'");
	  if (!$femail)
            print(" selected");
	  print(">All</option>");
          print("<option value='1'");
	  if ($femail)
            print(" selected");
          if ($REMOTE_USER)
	    print(">Mine + Unassigned</option>");
	  else
	    print(">Only Mine</option>");
          print("</select>\n");
        }

        print("</p></form>\n");
	print("<hr />\n");

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

        if (!$REMOTE_USER)
	{
	  $query .= "${prefix}is_published = 1";
	  $prefix = " AND ";
	}

        if ($femail)
	{
	  if ($REMOTE_USER)
	  {
	    $query .= "${prefix}(manager_email = '' OR "
	             ." manager_email = '$managers[$REMOTE_USER]')";
	    $prefix = " AND ";
	  }
	  else if (array_key_exists("FROM", $_COOKIE))
	  {
            $email = db_escape($_COOKIE["FROM"]);
	    $query .= "${prefix}create_email = '$email'";
	    $prefix = " AND ";
	  }
	}

        if ($search)
	{
	  $search_string = str_replace("'", " ", $search);
	  $search_string = str_replace("\"", " ", $search_string);
	  $search_string = str_replace("\\", " ", $search_string);
	  $search_string = str_replace("%20", " ", $search_string);
	  $search_string = str_replace("%27", " ", $search_string);
	  $search_string = str_replace("  ", " ", $search_string);
	  $search_words  = explode(' ', $search_string);

	  // Loop through the array of words, adding them to the 
	  $query  .= "${prefix}(";
	  $prefix = "";
	  $next   = " OR";
	  $logic  = "";

	  reset($search_words);
	  while ($keyword = current($search_words))
	  {
	    next($search_words);
	    $keyword = db_escape(ltrim(rtrim($keyword)));

	    if (strcasecmp($keyword, 'or') == 0)
	    {
	      $next = ' OR';
	      if ($prefix != '')
        	$prefix = ' OR';
	    }
	    else if (strcasecmp($keyword, 'and') == 0)
	    {
	      $next = ' AND';
	      if ($prefix != '')
        	$prefix = ' AND';
	    }
	    else if (strcasecmp($keyword, 'not') == 0)
	    {
	      $logic = ' NOT';
	    }
	    else
	    {
              if ($keyword == (int)$keyword)
	        $idsearch = " OR id = " . (int)$keyword;
              else
	        $idsearch = "";

	      $query  .= "$prefix$logic (summary LIKE \"%$keyword%\"$idsearch"
	                ." OR subsystem LIKE \"%$keyword%\""
	                ." OR str_version LIKE \"%$keyword%\""
	                ." OR fix_version LIKE \"%$keyword%\""
	                ." OR manager_email LIKE \"%$keyword%\""
	                ." OR create_email LIKE \"%$keyword%\")";
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
	    print("<p>[ <a href='$PHP_SELF?L+S0+Q" . urlencode($search)
	         ."'>Search for \"<i>$search</i>\" in all STRs</a> ]</p>\n");

	  html_footer();
	  exit();
	}

        if ($index >= $count)
	  $index = $count - ($count % $STR_PAGE_MAX);
	if ($index < 0)
	  $index = 0;

        $start = $index + 1;
        $end   = $index + $STR_PAGE_MAX;
	if ($end > $count)
	  $end = $count;

        $prev = $index - $STR_PAGE_MAX;
	if ($prev < 0)
	  $prev = 0;
	$next = $index + $STR_PAGE_MAX;

        print("<p>$count STR(s) found, showing $start to $end:</p>\n");

        if ($REMOTE_USER)
	  print("<form method='POST' action='$PHP_SELF?B$options'>\n");

        print("<p><table border='0' cellspacing='0' cellpadding='5' "
	     ."width='100%'>\n");

        if ($count > $STR_PAGE_MAX)
	{
          print("<tr><td colspan='4'>");
	  if ($index > 0)
	    print("[ <a href='$PHP_SELF?L+P$priority+S$status+C$scope+I$prev+"
		 ."E$femail+Q" . urlencode($search) . "'>Previous $STR_PAGE_MAX</a> ]");
          if ($REMOTE_USER)
            print("</td><td colspan='4' align='right'>");
          else
            print("</td><td colspan='3' align='right'>");
	  if ($end < $count)
	    print("[ <a href='$PHP_SELF?L+P$priority+S$status+C$scope+I$next+"
		 ."E$femail+Q" . urlencode($search) . "'>Next $STR_PAGE_MAX</a> ]");
          print("</td></tr>\n");
        }

	print("<tr class='header'><th>Id</th><th>Priority</th>"
	     ."<th>Status</th><th>Scope</th><th>Summary</th>"
	     ."<th>Version</th><th>Last Updated</th>");
        if ($REMOTE_USER)
	  print("<th>Assigned To</th>");
	print("</tr>\n");

        $line = 0;

	if ($REMOTE_USER)
	  $sumlen = 80;
	else 
	  $sumlen = 40;

	db_seek($result, $index);
	for ($i = 0; $i < $STR_PAGE_MAX && $row = db_next($result); $i ++)
	{
	  $date     = date("M d, Y", $row['modify_date']);
          $summary  = htmlspecialchars($row['summary'], ENT_QUOTES);
	  $summabbr = htmlspecialchars(abbreviate($row['summary'], $sumlen), ENT_QUOTES);
	  $prtext   = $priority_text[$row['priority']];
          $sttext   = $status_text[$row['status']];
          $sctext   = $scope_text[$row['scope']];

          if ($row['is_published'])
	    print("<tr class='data$line'>");
	  else
	    print("<tr class='priv$line'>");
          print("<td nowrap>");
          if ($REMOTE_USER)
	    print("<input type='checkbox' name='ID_$row[id]'>");
	  print("<a href='$PHP_SELF?L$row[id]$options' alt='STR #$row[id]: $summary'>"
	       ."$row[id]</a></td>"
	       ."<td align='center'>$prtext</td>"
	       ."<td align='center'>$sttext</td>"
	       ."<td align='center'>$sctext</td>"
	       ."<td align='center'><a href='$PHP_SELF?L$row[id]$options' "
	       ."alt='STR #$row[id]: $summary'>$summabbr</a></td>"
	       ."<td align='center'>$row[str_version]</td>"
	       ."<td align='center'>$date</td>");
          if ($REMOTE_USER)
	  {
	    if ($row['manager_email'] != "")
	      $email = sanitize_email($row['manager_email']);
	    else
	      $email = "<i>Unassigned</i>";

	    print("<td align='center'>$email</td>");
	  }
	  print("</tr>\n");

          if ($REMOTE_USER && $row['status'] >= $STR_STATUS_PENDING)
	  {
            $textresult = db_query("SELECT * FROM strtext "
	                          ."WHERE str_id = $row[id] "
	                          ."ORDER BY id DESC LIMIT 1");
            if ($textresult && db_count($textresult) > 0)
	    {
	      $textrow = db_next($textresult);

              if ($row['is_published'])
		print("<tr class='data$line'>");
	      else
		print("<tr class='priv$line'>");

	      $email    = sanitize_email($textrow->email);
	      $contents = quote_text(abbreviate($textrow->contents, 128));

	      print("<td align='center' valign='top' colspan='2'>$email</td>"
		   ."<td valign='top' colspan='6' width='100%'><tt>$contents</tt></td>"
		   ."</tr>\n");

	      db_free($textresult);
	    }
          }

	  $line = 1 - $line;
	}

        db_free($result);

        if ($REMOTE_USER)
	{
	  print("<tr class='header'><th colspan='8'>");

          print("Status:&nbsp;<select name='STATUS'>"
	       ."<option value=''>No Change</option>");
	  for ($i = 1; $i <= 5; $i ++)
	    print("<option value='$i'>$status_text[$i]</option>");
          print("</select>\n");

	  print("Priority:&nbsp;<select name='PRIORITY'>"
	       ."<option value=''>No Change</option>");
          for ($i = 1; $i <= 5; $i ++)
	    print("<option value='$i'>$priority_text[$i]</option>");
          print("</select>\n");

	  print("Assigned To:&nbsp;<select name='MANAGER_EMAIL'>"
	       ."<option value=''>No Change</option>");
	  reset($managers);
	  while (list($key, $val) = each($managers))
	  {
	    $temail = htmlspecialchars($val, ENT_QUOTES);
	    $temp   = sanitize_email($val);
	    print("<option value='$temail'>$temp</option>");
	  }
          print("</select>\n");

	  print("<br />Text:&nbsp;<select name='MESSAGE'>"
	       ."<option value=''>No Message</option>");
	  reset($messages);
	  while (list($key, $val) = each($messages))
	  {
	    $temp = abbreviate($val);
	    print("<option value='$key'>$temp</option>");
	  }
          print("</select>\n");

	  print("<input type='submit' value='Modify Selected STRs'>");
	  print("</th><tr>\n");
        }
        else
	  print("<tr class='header'><th colspan='7'>"
	       ."<spacer width='1' height='1'></th><tr>\n");

        if ($count > $STR_PAGE_MAX)
	{
          print("<tr><td colspan='4'>");
	  if ($index > 0)
	    print("[ <a href='$PHP_SELF?L+P$priority+S$status+C$scope+I$prev+"
		 ."E$femail+Q" . urlencode($search) . "'>Previous $STR_PAGE_MAX</a> ]");
          if ($REMOTE_USER)
            print("</td><TD COLSPAN='4' ALIGN='RIGHT'>");
          else
            print("</td><TD COLSPAN='3' ALIGN='RIGHT'>");
	  if ($end < $count)
	    print("[ <a href='$PHP_SELF?L+P$priority+S$status+C$scope+I$next+"
		 ."E$femail+Q" . urlencode($search) . "'>Next $STR_PAGE_MAX</a> ]");
          print("</td></tr>\n");
        }

        print("</table>");

	if ($REMOTE_USER)
	  print("</form>");

	print("<p>"
	     ."M/P = Machine/Printer, "
	     ."OS = Operating System."
	     ."</p>\n");
      }

      html_footer();
      break;

  case 'M' : // Modify STR
      if ($REQUEST_METHOD == "POST")
      {
        if (array_key_exists("STATUS", $_POST))
	{
          $time          = time();
	  $master_id     = (int)$_POST["MASTER_ID"];
	  $summary       = db_escape($_POST["SUMMARY"]);
	  $subsystem     = db_escape($_POST["SUBSYSTEM"]);
          $create_email  = db_escape($_POST["CREATE_EMAIL"]);
          $manager_email = db_escape($_POST["MANAGER_EMAIL"]);
          $modify_email  = db_escape($managers[$REMOTE_USER]);
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
	          ."create_email = '$create_email', "
	          ."manager_email = '$manager_email', "
	          ."modify_date = $time, "
	          ."modify_email = '$modify_email' "
		  ."WHERE id = $id");
          
          if ($contents != "")
	  {
            db_query("INSERT INTO strtext VALUES(NULL,$id,1,$time,"
	            ."'$modify_email','$contents')");
            $contents = trim($_POST["CONTENTS"]) . "\n\n";
	  }

          if ($message != "")
	  {
	    $contents = db_escape($messages[$message]);

            db_query("INSERT INTO strtext VALUES(NULL,$id,1,$time,"
	            ."'$modify_email','$contents')");

            $contents = $messages[$message] . "\n\n";
	  }

	  header("Location: $PHP_SELF?L$id$options");

	  notify_creator($id, "updated", $contents);
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
        html_header("STR #$id");

	print("<p align='center'>"
	     ."[&nbsp;<a href='$PHP_SELF?L$options'>Return&nbsp;to&nbsp;STR&nbsp;List</a>"
	     ." | <a href='$PHP_SELF?L$id$options'>Return&nbsp;to&nbsp;STR&nbsp;#$id</a>"
	     ." | <a href='$PHP_SELF?T$id$options'>Post&nbsp;Text</a>"
	     ." | <a href='$PHP_SELF?F$id$options'>Post&nbsp;File</a>"
	     ."&nbsp;]</p><hr />\n");

        $result = db_query("SELECT * FROM str WHERE id = $id");
	if (db_count($result) != 1)
	{
	  print("<p><b>Error:</b> STR #$id was not found!</p>\n");
	  html_footer();
	  exit();
	}

        $row = db_next($result);

	$create_email  = htmlspecialchars($row['create_email']);
	$manager_email = htmlspecialchars($row['manager_email']);
	$summary       = htmlspecialchars($row['summary'], ENT_QUOTES);

        print("<form method='POST' action='$PHP_SELF?M$id$options'>"
	     ."<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

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
	     ."<td><input type='text' name='STR_VERSION' size='16' maxsize='16' "
	     ."value='$row[str_version]'></td></tr>\n");

	print("<tr><th align='right'>Created By:</th>"
	     ."<td><input type='text' name='CREATE_EMAIL' maxsize='128' "
	     ."value='$create_email' size='40'></td></tr>\n");

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
        print("</select><br />\n");

	print("<textarea name='CONTENTS' COLS='72' ROWS='12' WRAP='VIRTUAL'>"
             ."</textarea></td></tr>\n");

        print("<tr><TH ALIGN='CENTER' COLSPAN='2'>"
	     ."<INPUT type='SUBMIT' value='Update Trouble Report'></th></tr>\n");
        print("</table></p></form>\n");

	print("<hr /><p><b>Trouble Report Files:</b> "
	     ."[ <a href='$PHP_SELF?F$id$options'>Post&nbsp;File</a> ]"
	     ."</p>\n");

	$result = db_query("SELECT * FROM strfile WHERE str_id = $id");

        if (db_count($result) == 0)
	  print("<p><i>No files</i></p>\n");
	else
	{
	  print("<p><table width='100%' border='0' cellpadding='5' "
	       ."cellspacing='0'>\n"
	       ."<tr class='header'><th>Name/Time/Date</th>"
	       ."<th>Filename</th></tr>\n");

          $line = 0;
	  while ($row = db_next($result))
	  {
            $date     = date("M d, Y", $row['date']);
            $time     = date("H:m", $row['date']);
	    $email    = sanitize_email($row['email']);
	    $filename = htmlspecialchars($row['filename']);

	    print("<tr class='data$line'>"
	         ."<td align='center' valign='top'>$email<br />$time $date<br />"
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
          print("</table></p>\n");
        }

	db_free($result);

	print("<hr /><p><b>Trouble Report Dialog:</b> "
	     ."[ <a href='$PHP_SELF?T$id$options'>Post&nbsp;Text</a> ]"
	     ."</p>\n");

	$result = db_query("SELECT * FROM strtext WHERE "
	                  ."str_id = $id");

        if (db_count($result) == 0)
	  print("<p><i>No text</i></p>\n");
	else
	{
	  print("<p><table width='100%' border='0' cellpadding='5' "
	       ."cellspacing='0'>\n"
	       ."<tr class='header'><th>Name/Time/Date</th>"
	       ."<th>Text</th></tr>\n");

          $line = 0;

	  while ($row = db_next($result))
	  {
            $date     = date("M d, Y", $row['date']);
            $time     = date("H:m", $row['date']);
	    $email    = sanitize_email($row['email']);
	    $contents = quote_text($row['contents']);

	    print("<tr class='data$line'>"
	         ."<td align='center' valign='top'>$email<br />$time $date<br />"
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
          print("</table></p>\n");
	}

	db_free($result);

        html_footer();
      }
      break;

  case 'T' : // Post text for STR #
      if ($REQUEST_METHOD == "POST")
      {
	$contents = $_POST["CONTENTS"];

	if (array_key_exists("EMAIL", $_POST))
	{
	  $email = $_POST["EMAIL"];
	  setcookie("FROM", "$email", time() + 57600, $PHP_SELF, $SERVER_NAME);
	}
	else if ($REMOTE_USER)
	  $email = $managers[$REMOTE_USER];
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
	if ($REMOTE_USER)
	  $email = $managers[$REMOTE_USER];
	else
	  $email = $_COOKIE["FROM"];

	$contents = "";

	if (ereg("Anonymous.*", $email))
	  $email = "";
      }

      if ($REQUEST_METHOD == "POST" && $havedata)
      {
        $time      = time();
	$temail    = db_escape($email);
	$tcontents = db_escape($contents);

        db_query("INSERT INTO strtext VALUES(NULL,$id,1,$time,'$temail',"
	        ."'$tcontents')");

        db_query("UPDATE str SET modify_date=$time, modify_email='$temail' "
	        ."WHERE id = $id");
        db_query("UPDATE str SET status=$STR_STATUS_PENDING WHERE "
	        ."id = $id AND status >= $STR_STATUS_ACTIVE AND "
		."status < $STR_STATUS_NEW");

	header("Location: $PHP_SELF?L$id$options");

        notify_creator($id, "updated", "$contents\n\n");
      }
      else
      {
        html_header("Post Text For STR #$id");

	print("<p align='center'>[ <a href='$PHP_SELF?L$id$options'>Return to "
	     ."STR #$id</a> ]</p>\n");

        if ($REQUEST_METHOD == "POST")
	{
	  print("<p><b>Error:</b> Please fill in the fields marked in "
	       ."<b><font color='red'>bold red</font></b> below and resubmit "
	       ."your trouble report.</p><hr />\n");

	  $hstart = "<font color='red'>";
	  $hend   = "</font>";
	}
	else
	{
	  print("<hr />\n");

	  $hstart = "";
	  $hend   = "";
	}

        print("<form method='POST' action='$PHP_SELF?T$id$options'>"
	     ."<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

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
        print("</table></p></form>\n");
        html_footer();
      }
      break;

  case 'F' : // Post file for STR #
      if ($REQUEST_METHOD == "POST")
      {
	if (array_key_exists("EMAIL", $_POST))
	{
	  $email = $_POST["EMAIL"];
	  setcookie("FROM", "$email", time() + 57600, $PHP_SELF, $SERVER_NAME);
	}
	else if ($REMOTE_USER)
	  $email = $managers[$REMOTE_USER];
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
	if ($REMOTE_USER)
	  $email = $managers[$REMOTE_USER];
	else
	  $email = $_COOKIE["FROM"];

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

        db_query("INSERT INTO strfile VALUES(NULL,$id,1,$time,'$temail',"
	        ."'$tname')");

        db_query("UPDATE str SET modify_date=$time, modify_email='$temail' "
	        ."WHERE id = $id");
        db_query("UPDATE str SET status=$STR_STATUS_PENDING WHERE "
	        ."id = $id AND status >= $STR_STATUS_ACTIVE AND "
		."status < $STR_STATUS_NEW");

	header("Location: $PHP_SELF?L$id$options");

        notify_creator($id, "updated", "Added file $name\n\n");
      }
      else
      {
        html_header("Post File For STR #$id");

	print("<p align='center'>[ <a href='$PHP_SELF?L$id$options'>Return to "
	     ."STR #$id</a> ]</p>\n");

        if ($REQUEST_METHOD == "POST")
	{
	  print("<p><b>Error:</b> Please fill in the fields marked in "
	       ."<b><font color='red'>bold red</font></b> below and resubmit "
	       ."your trouble report.</p><hr />\n");

	  $hstart = "<font color='red'>";
	  $hend   = "</font>";
	}
	else
	{
	  print("<hr />\n");

	  $hstart = "";
	  $hend   = "";
	}

        print("<form method='POST' action='$PHP_SELF?F$id$options' "
	     ."enctype='multipart/form-data'>"
	     ."<input type='hidden' name='MAX_FILE_SIZE' value='10000000'>");

	print("<p><table width='100%' cellpadding='5' cellspacing='0' "
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
        print("</table></p></form>\n");
        html_footer();
      }
      break;

  case 'N' : // Post new STR
      $havedata = 0;

      if ($REQUEST_METHOD == "POST")
      {
	$npriority = $_POST["PRIORITY"];
	$nscope    = $_POST["SCOPE"];
	$summary   = $_POST["SUMMARY"];
	$version   = $_POST["VERSION"];
	$contents  = $_POST["CONTENTS"];

	if (array_key_exists("EMAIL", $_POST))
	{
	  $email = $_POST["EMAIL"];
	  setcookie("FROM", "$email", time() + 57600, $PHP_SELF, $SERVER_NAME);
	}
	else if ($REMOTE_USER)
	  $email = $managers[$REMOTE_USER];
	else if (array_key_exists("FROM", $_COOKIE))
          $email = $_COOKIE["FROM"];
	else
	  $email = "";

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
	if ($REMOTE_USER)
	  $email = $managers[$REMOTE_USER];
	else
	  $email = $_COOKIE["FROM"];

        $npriority = 0;
	$nscope    = 0;
	$summary   = "";
	$version   = "";
	$contents  = "";
	$filename  = "";
      }

      if (ereg("Anonymous.*", $email))
	$email = "";

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

        db_query("INSERT INTO strtext VALUES(NULL,$id,1,$time,'$temail',"
	        ."'$tcontents')");

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

          db_query("INSERT INTO strfile VALUES(NULL,$id,1,$time,'$temail',"
	          ."'$tname')");
        }

	header("Location: $PHP_SELF?L$id$options");
        notify_creator($id, "created", "$contents\n\n");
      }
      else
      {
        html_header("New STR");

	print("<p align='center'>[ <a href='$PHP_SELF?L$options'>Return to "
	     ."STR List</a> ]</p>\n");

        if ($REQUEST_METHOD == "POST")
	{
	  print("<p><b>Error:</b> Please fill in the fields marked in "
	       ."<b><font color='red'>bold red</font></b> below and resubmit "
	       ."your trouble report.</p><hr />\n");

	  $hstart = "<font color='red'>";
	  $hend   = "</font>";
	}
	else
	{
	  print("<p>Please use this form to report all bugs and request "
	       ."features in the Mini-XML software. Be sure to include "
	       ."the operating system, compiler, sample programs and/or "
	       ."files, and any other information you can about your "
	       ."problem. <i>Thank you</i> for helping us to make Mini-XML "
	       ."a better library!</p><hr />\n");

	  $hstart = "";
	  $hend   = "";
	}

        print("<form method='POST' action='$PHP_SELF?N$options' "
	     ."enctype='multipart/form-data'>"
	     ."<input type='hidden' name='MAX_FILE_SIZE' value='10000000'>");

        print("<p><table width='100%' cellpadding='5' cellspacing='0' "
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
	  print(">$priority_long[$i]<br />");
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
	  print(">$scope_long[$i]<br />");
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
	  print("<option value='$val'");
	  if ($version == $val)
	    print(" selected");
	  print(">$val</option>");
	}

        print("</select></td></tr>\n");

	print("<tr><th align='right'>");
	if ($email != "")
	  print("EMail:</th><td>");
	else
	  print("${hstart}EMail:$hend</th><td>");

        $temp = htmlspecialchars($email);
        print("<input type='text' name='EMAIL' value='$temp' size='40' "
	     ."maxsize='128'></td></tr>\n");

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
	     ."<input type='submit' value='Submit Trouble Report'></th></tr>\n");
        print("</table></p></form>\n");
        html_footer();
      }
      break;

  case 'U' : // Update notification status
      // EMAIL and NOTIFICATION variables hold status; add/delete from strcc...
      $havedata = 0;

      if ($REQUEST_METHOD != "POST")
      {
	html_header("STR Error");
	print("<p>The '$op' command requires a POST request!\n");
	html_footer();
	exit();
      }

      $notification = $_POST["NOTIFICATION"];
      $email        = $_POST["EMAIL"];

      if (($notification != "ON" && $notification != "OFF") || $email == "")
      {
	html_header("STR Error");
	print("<p>Please press your browsers back button and enter an "
	     ."EMail address and choose whether to receive notification "
	     ."messages.</p>\n");
	html_footer();
	exit();
      }

      setcookie("FROM", "$email", time() + 57600, $PHP_SELF, $SERVER_NAME);

      $result = db_query("SELECT * FROM strcc WHERE str_id = $id AND email = '$email'");

      html_header("STR #$id Notifications");

      if ($notification == "ON")
      {
        if ($result && db_count($result) > 0)
	  print("<p>Your email address has already been added to the "
	       ."notification list for STR #$id!</p>\n");
        else
	{
          db_query("INSERT INTO strcc VALUES(NULL,$id,'$email')");
	
	  print("<p>Your email address has been added to the notification list "
               ."for STR #$id.</p>\n");
        }
      }
      else if ($result && db_count($result) > 0)
      {
        db_query("DELETE FROM strcc WHERE str_id = $id AND email = '$email'");

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

      print("<p>[ <a href='$PHP_SELF?L$id$options'>Return to STR #$id</a> ]</p>\n");

      html_footer();
      break;
}

?>
