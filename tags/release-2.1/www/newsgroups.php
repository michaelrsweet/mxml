<?php
//
// "$Id: newsgroups.php,v 1.1 2004/06/10 02:40:05 mike Exp $"
//
// Mini-XML newsgroup page...
//

include_once "phplib/html.php";
include_once "phplib/common.php";

// News server...
$NNTPSERVER = "localhost";
$NNTPPORT = 8119;
//$NNTPSERVER = "news.easysw.com";
//$NNTPPORT = 119;

// Cookie stuff...
if (array_key_exists("SEARCH", $_POST))
{
  $search = $_POST["SEARCH"];
  setcookie("SEARCH", "$search", 0, "/");
}
else if (array_key_exists("SEARCH", $_COOKIE))
  $search = $_COOKIE["SEARCH"];
else
  $search = "";

if (array_key_exists("FROM", $_POST))
{
  $from = $_POST["FROM"];
  setcookie("FROM", "$from", time() + 90 * 86400, "/");
}
else if ($LOGIN_EMAIL != "")
{
  $from = $LOGIN_EMAIL;
  setcookie("FROM", "$from", time() + 90 * 86400, "/");
}
else if (array_key_exists("FROM", $_COOKIE))
  $from = $_COOKIE["FROM"];
else
  $from = "Anonymous <anonymous@easysw.com>";

if ($from == "" || $from == "Anonymous")
  $from = "Anonymous <anonymous@easysw.com>";


//
// 'nntp_close()' - Close a news server thing...
//

function
nntp_close($stream)			// I - Socket stream
{
  nntp_command($stream, "QUIT", 205);

  fclose($stream);
}


//
// 'nntp_command()' - Send a command and get the response...
//

function				// O - NNTP response
nntp_command($stream,			// I - Socket stream
             $command = "QUIT",		// I - NNTP command
	     $expect = 200)		// I - Expected status

{
//  print("<p>nntp_command(stream=$stream, command='$command', expect=$expect)</p>\n");

  fwrite($stream, "$command\r\n");

  $status = fgets($stream, 1024);

//  print("<p>status='$status'</p>\n");

  if ((int)$status != $expect)
  {
    print("<p><b>Error:</b> $status</p>\n");
    return (NULL);
  }
  else
    return ($status);
}


//
// 'nntp_connect()' - Connect to the news server.
//

function				// O - Socket stream
nntp_connect()
{
  global $NNTPSERVER, $NNTPPORT;


  $stream = fsockopen($NNTPSERVER, $NNTPPORT, &$errno, &$errstr);

  if ($stream)
  {
    if ($line = fgets($stream, 1024))
    {
      if ((int)$line != 200)
      {
	print("<p><b>Error:</b> $line</p>\n");
	fclose($stream);
	return (FALSE);
      }
    }
    else
    {
      print("<p><b>Error:</b> No response from NNTP server!</p>\n");
      fclose($stream);
      return (FALSE);
    }
  }
  else
    print("<p><b>Error:</b> $errstr ($errno)</p>\n");

  return ($stream);
}


//
// 'nntp_search()' - Do a header search...
//

function				// O - Matching message headers...
nntp_search($stream,			// I - Socket stream
            $group,			// I - NNTP group
            $search)			// I - Search text
{
//  print("<p>nntp_search(stream=$stream, group='$group', search='$search'</p>\n");

  $status = nntp_command($stream, "GROUP $group", 211);
  if (!$status)
    return (NULL);

  $fields = explode(" ", $status);
  $status = nntp_command($stream, "XOVER $fields[2]-$fields[3]", 224);
  if (!$status)
    return (NULL);

  $words       = explode(" ", $search);
  $num_matches = 0;
  $matches     = NULL;

  while ($line = fgets($stream, 1024))
  {
    $line = rtrim($line);

    if ($line == ".")
      break;

    if ($search == "")
    {
      // Return all matches...
      $matches[$num_matches] = $line;
      $num_matches ++;
    }
    else
    {
      // Search for words...
      reset($words);

      while (list($key, $word) = each($words))
      {
	if (stristr($line, $word))
	{
          $matches[$num_matches] = $line;
	  $num_matches ++;
	  break;
	}
      }
    }
  }

//  print("<p>num_matches=$num_matches</p>\n");

  return ($matches);
}


//
// 'show_prevnext_page()' - Show the prev/next links for the messages list...
//

function
show_prevnext_page($group,		// I - Group
                   $group_filter,	// I - Group filter
                   $start,		// I - Start message
		   $end,		// I - End message
		   $count)		// I - Number of messages
{
  global $PHP_SELF, $PAGE_MAX;


  print("<p><table width='100%' border='0' cellpadding='0' cellspacing='0'>\n"
       ."<tr><td width='25%'>");

  if ($start > 1)
  {
    $i = $start - $PAGE_MAX;
    if ($i < 1)
      $i = 1;

    $j = $i + $PAGE_MAX - 1;
    if ($j > $count)
      $j = $count;

    html_start_links();
    html_link("Show Messages $i - $j", "$PHP_SELF?s$i+g$group+G$group_filter");
    html_end_links();
  }

  print("</td><td align='center' width='50%'>");
  html_start_links();
  html_link("All Groups", "$PHP_SELF?G$group_filter");

  if (!ereg(".*\.announce", $group) && !ereg(".*\.cvs", $group))
    html_link("New Message", "$PHP_SELF?s$i+g$group+G$group_filter+n");
  html_end_links();
  print("</td><td align='right' width='25%'>");

  if ($end < $count)
  {
    $i = $start + $PAGE_MAX;
    $j = $i + $PAGE_MAX - 1;
    if ($j > $count)
      $j = $count;

    html_start_links();
    html_link("Show Messages $i - $j", "$PHP_SELF?s$i+g$group+G$group_filter");
  }

  print("</td></tr>\n"
       ."</table></p>\n");
}


//
// 'show_messages()' - Show messages in the named group...
//

function
show_messages($group,			// I - Group
              $group_filter,		// I - Group filter
              $start,			// I - Start message
	      $search)			// I - Search string
{
  global $PHP_SELF, $PAGE_MAX;


  // Figure out which messages to show...
  $error = "";

  $stream  = nntp_connect();
  $matches = nntp_search($stream, $group, $search);
  $count   = count($matches);

  nntp_close($stream);

  if (!$matches)
  {
    $count  = 0;
    $error  = "No matches found for '" .
              htmlspecialchars($search, ENT_QUOTES) . "'...";
  }

  if ($start == 0)
    $start = $count - 9;
  if ($start > ($count - $PAGE_MAX + 1))
    $start = $count - $PAGE_MAX + 1;
  if ($start < 1)
    $start = 1;

  $end = $start + $PAGE_MAX - 1;
  if ($end > $count)
    $end = $count;

  // Show the standard header...
  html_header("$group ($start - $end of $count)");

  $temp = htmlspecialchars($search, ENT_QUOTES);
  print("<form method='post' action='$PHP_SELF?g$group+G$group_filter'>\n"
       ."<p align='center'><input type='text' name='SEARCH' value='$temp' "
       ."size='60'/><input type='submit' value='Search $group'/></p>\n"
       ."</form>\n");

  if ($error != "")
    print("<p align='center'><i>$error</i></p>\n");

  show_prevnext_page($group, $group_filter, $start, $end, $count);

  html_start_table(array("Subject", "Author", "Date"));

  for ($i = $start; $i <= $end; $i ++)
  {
    $fields  = explode("\t", $matches[$i - 1]);
    $msg     = (int)$fields[0];
    $subject = htmlspecialchars($fields[1], ENT_QUOTES);
    $author  = sanitize_email($fields[2]);
    $date    = htmlspecialchars($fields[3], ENT_QUOTES);

    html_start_row();
    print("<td><a href='$PHP_SELF?s$start+g$group+G$group_filter+v$i'>"
         ."$subject</a></td>"
         ."<td align='center'>$author</td>"
	 ."<td align='right'>$date</td>");
    html_end_row();
  }

  html_end_table();

  show_prevnext_page($group, $group_filter, $start, $end, $count);

  html_footer();
}


//
// 'show_groups()' - Show groups...
//

function
show_groups($group_filter,		// I - Group filter
            $search)			// I - Search string
{
  global $PHP_SELF;


  html_header("Newsgroups");

  // Figure out which messages to show...
  $stream = nntp_connect();

  // Search stuff...
  print("<form method='post' action='$PHP_SELF?G$group_filter'>\n"
       ."<p align='center'><input type='text' name='SEARCH' value='$search' "
       ."size='60'/><input type='submit' value='Search All Groups'/></p>\n"
       ."</form>\n");

  // Show the standard header...
  html_start_table(array("Group", "Messages", ""));

  $status     = nntp_command($stream, "LIST", 215);
  $num_groups = 0;
  $groups     = array();

  if ($status)
  {
    while ($line = fgets($stream, 1024))
    {
      $line = rtrim($line);
      if ($line == ".")
        break;

      $fields              = explode(" ", $line);
      $groups[$num_groups] = $fields[0];
      $num_groups ++;
    }
  }

  sort($groups);

  while (list($key, $group) = each($groups))
  {
    if (ereg("(linuxprinting|private)\\..*", $group))
      continue;

    if ($group_filter && !ereg("${group_filter}\\.*", $group))
      continue;

    $status = nntp_command($stream, "GROUP $group", 211);
    if (!$status)
      continue;

    $fields = explode(" ", $status);
    $total  = (int)$fields[1];

    if ($search != "")
    {
      $matches = nntp_search($stream, $group, $search);
      $count   = count($matches);
    }
    else
      $count = $total;

    html_start_row();
    print("<td align='center'>$group</td>"
         ."<td align='center'>$count");

    if ($search != "")
      print("/$total");

    print("</td><td>");
    html_start_links();
    html_link("View", "$PHP_SELF?g$group+G$group_filter");
    if (!ereg(".*\.announce", $group) && !ereg(".*\.cvs", $group))
      html_link("New Message", "$PHP_SELF?g$group+G$group_filter+n");
    html_end_links();
    print("</td>");
    html_end_row();
  }

  html_start_row("header");
  print("<th colspan='3'>Newsgroups and Mailing Lists</th>");
  html_end_row();

  html_start_row();
  print("<td colspan='3'>"
       ."<p>Point your news reader at <a href='news://news.easysw.com'>"
       ."news.easysw.com</a> to view these groups directly.</p>\n"
       ."<p>Go to <a href='http://lists.easysw.com/mailman/listinfo'>"
       ."http://lists.easysw.com/mailman/listinfo</a> "
       ."to subscribe to or unsubcribe from the mailing lists that mirror "
       ."these groups.</p>"
       ."</td>");
  html_end_row();
  html_end_table();

  nntp_close($stream);

  html_footer();
}


//
// 'show_prevnext_msg()' - Show the prev/next links for the messages list...
//

function
show_prevnext_msg($group,		// I - Group
                  $group_filter,	// I - Group filter
                  $start,		// I - Start message
		  $count,		// I - Number of messages
		  $msg)			// I - Current message
{
  global $PHP_SELF;


  print("<p><table width='100%' border='0' cellpadding='0' cellspacing='0'>\n"
       ."<tr><td width='25%'>");

  if ($msg > 1)
  {
    $i = $msg - 1;

    html_start_links();
    html_link("Previous Message", "$PHP_SELF?s$start+g$group+G$group_filter+v$i");
    html_end_links();
  }

  print("</td><td align='center' width='50%'>");
  html_start_links();
  html_link("All Groups", "$PHP_SELF?G$group_filter");
  html_link("Back to $group", "$PHP_SELF?s$msg+g$group+G$group_filter");

  if (!ereg(".*\.announce", $group) && !ereg(".*\.cvs", $group))
  {
    html_link("Reply", "$PHP_SELF?s$start+g$group+G$group_filter+r$msg");
    html_link("New Message", "$PHP_SELF?s$i+g$group+G$group_filter+n");
  }

  html_end_links();

  print("</td><td align='right' width='25%'>");

  if ($msg < $count)
  {
    $i = $msg + 1;
    html_start_links();
    html_link("Next Message", "$PHP_SELF?s$start+g$group+G$group_filter+v$i");
    html_end_links();
  }

  print("</td></tr>\n"
       ."</table></p>\n");
}


//
// 'show_message()' - Show a single message...
//

function
show_message($group,			// I - Group
             $group_filter,		// I - Group filter
             $start,			// I - Start message
	     $msg,			// I - Current message
	     $search)			// I - Search string
{
  global $PHP_SELF;


  // Figure out which messages to show...
  $stream  = nntp_connect();
  $matches = nntp_search($stream, $group, $search);
  $count   = count($matches);

  if ($msg < 1 || $msg > $count)
  {
    nntp_close($stream);
    return;
  }

  $fields  = explode("\t", $matches[$msg - 1]);
  $msgnum  = (int)$fields[0];
  $subject = str_replace(":", "&#58;", htmlspecialchars($fields[1], ENT_QUOTES));
  $author  = str_replace(":", "&#58;", sanitize_email($fields[2]));
  $date    = str_replace(":", "&#58;", htmlspecialchars($fields[3], ENT_QUOTES));

  $status = nntp_command($stream, "BODY $msgnum", 222);
  if (!$status)
  {
    nntp_close($stream);
    return;
  }

  $body = "";
  while ($line = fgets($stream, 1024))
  {
    $line = rtrim($line);

    if ($line == ".")
      break;

    $body = $body . $line . "\n";
  }

  nntp_close($stream);

  $body = quote_text($body);

  html_header("$subject");

  show_prevnext_msg($group, $group_filter, $start, $count, $msg);

  html_start_table(array($subject, $author, $date));
  html_start_row();
  print("<td colspan='3'><tt>$body</tt></td>");
  html_end_row();
  html_end_table();

  show_prevnext_msg($group, $group_filter, $start, $count, $msg);

  html_footer();
}


//
// 'post_message()' - Post a message...
//

function
post_message($group,			// I - Group
             $group_filter,		// I - Group filter
             $start,			// I - Start message
	     $msg,			// I - Current message
	     $search)			// I - Search string
{
  global $PHP_SELF, $_POST;


  if (array_key_exists("FROM", $_POST))
    $from = $_POST["FROM"];
  else
    $from = "";

  if (array_key_exists("SUBJECT", $_POST))
    $subject = $_POST["SUBJECT"];
  else
    $subject = "";

  if (array_key_exists("BODY", $_POST))
    $body = $_POST["BODY"];
  else
    $body = "";

  if (!validate_email($from) || $subject == "" || $body == "")
  {
    new_message($group, $group_filter, $start, $from, $subject, $body);
    return;
  }

  $stream = nntp_connect();
  if (!$stream)
  {
    return;
  }

  $id = "";

  if ($msg > 0)
  {
    $matches = nntp_search($stream, $group, $search);
    $count   = count($matches);

    if ($msg <= $count)
    {
      $fields = explode("\t", $matches[$msg - 1]);
      $id     = $fields[4];
    }
  }

  $status = nntp_command($stream, "POST", 340);

  if (!$status)
  {
    nntp_close($stream);
    return;
  }

  fwrite($stream, "From: $from\r\n");
  fwrite($stream, "Subject: $subject\r\n");
  fwrite($stream, "Newsgroups: $group\r\n");

  if ($id != "")
    fwrite($stream, "In-Reply-To: $id\r\n");

  fwrite($stream, "\r\n");

  $lines = explode("\n", $body);
  $count = count($lines);

  for ($i = 0; $i < $count; $i ++)
  {
    $line = rtrim($lines[$i]);

    if ($line == ".")
      fwrite($stream, ". \r\n");
    else
      fwrite($stream, "$line\r\n");
  }

  $status = nntp_command($stream, ".", 240);

  if ($status)
  {
    if ($msg == 0)
      header("Location: $PHP_SELF?s$start+g$group+G$group_filter");
    else
      header("Location: $PHP_SELF?s$start+g$group+G$group_filter+v$msg");
  }

  nntp_close($stream);
}


//
// 'reply_message()' - Reply to a message...
//

function
reply_message($group,			// I - Group to reply to
              $group_filter,		// I - Group filter
              $start,			// I - First message in the display
	      $msg,			// I - Message to reply to
	      $search,			// I - Search string
	      $sender)			// I - Sender address
{
  global $PHP_SELF;


  // Figure out which messages to show...
  $stream  = nntp_connect();
  $matches = nntp_search($stream, $group, $search);
  $count   = count($matches);

  if ($msg < 1 || $msg > $count)
  {
    nntp_close($stream);
    return;
  }

  $fields  = explode("\t", $matches[$msg - 1]);
  $msgnum  = (int)$fields[0];
  $subject = htmlspecialchars($fields[1], ENT_QUOTES);
  $author  = sanitize_email($fields[2]);
  $date    = htmlspecialchars($fields[3], ENT_QUOTES);

  if (strncasecmp($subject, "re:", 3))
    $subject = "Re: " . $subject;

  $status = nntp_command($stream, "BODY $msgnum", 222);
  if (!$status)
  {
    nntp_close($stream);
    return;
  }

  $body = "";
  while ($line = fgets($stream, 1024))
  {
    $line = rtrim($line);

    if ($line == ".")
      break;

    $body = $body . "> " . $line . "\n";
  }

  nntp_close($stream);

  new_message($group, $group_filter, $start, $subject, $sender, $body);
}


//
// 'new_message()' - Post a new message...
//

function
new_message($group,			// I - Group to post to
            $group_filter,		// I - Group filter
            $start,			// I - First message
	    $subject,			// I - Subject of message
	    $sender,			// I - Sender address
	    $body)			// I - Message body
{
  global $PHP_SELF, $NNTPSPEC;


  $subject = htmlspecialchars($subject, ENT_QUOTES);
  $sender  = htmlspecialchars($sender, ENT_QUOTES);
  $body    = htmlspecialchars($body, ENT_QUOTES);

  html_header("Post Message to $group");

  html_start_links(1);
  html_link("All Groups", "$PHP_SELF?G$group_filter");
  html_link("Back to $group", "$PHP_SELF?s$start+g$group+G$group_filter");
  html_end_links(1);

  print("<h2>Post Message to $group</h2>");

  print("<form action='$PHP_SELF?s$start+g$group+G$group_filter+p0' method='POST'>\n");

  print("<center><table width='100%' border='0' cellpadding='5' cellspacing='0'>\n");
  print("<tr><th align='right' valign='top'>Subject:</th>"
       ."<td><input type='text' name='SUBJECT' value='$subject' size='40'/></td></tr>\n");

  print("<tr><th align='right' valign='top'>From:</th>"
       ."<td><input type='text' name='FROM' value='$sender' size='40'/></td></tr>\n");

  print("<tr><th align='right' valign='top'>Body:</th>"
       ."<td><textarea name='BODY' cols='72' rows='24'>$body</textarea></td></tr>\n");

  print("<tr><th></th>"
       ."<td><input type='submit' value='Post Message'/></td></tr>\n");
  print("</table></center>\n");

  print("</form>\n");

  html_footer();
}


// Parse command-line options...
$start = 0;
$group = "";
$op    = 'l';
$msg   = "";

for ($i = 0; $i < $argc; $i ++)
{
  switch ($argv[$i][0])
  {
    case 'g' :
        $group = substr($argv[$i], 1);
	break;

    case 'G' :
        $groups = substr($argv[$i], 1);
	break;

    case 'n' :
    case 'p' :
    case 'r' :
    case 'v' :
        $op  = $argv[$i][0];
        $msg = (int)substr($argv[$i], 1);
	break;

    case 's' :
        $start = (int)substr($argv[$i], 1);
	break;
  }
}

// Now handle the request...
switch ($op)
{
  case 'l' : // List
      if ($group)
        show_messages($group, $PROJECT_MODULE, $start, $search);
      else
        show_groups($PROJECT_MODULE, $search);
      break;

  case 'n' : // New message
      new_message($group, $PROJECT_MODULE, $start, "", $from, "");
      break;

  case 'p' : // Post message
      if (ereg(".*\.announce", $group) || ereg(".*\.cvs", $group))
      {
	html_header("Newsgroup Posting Error");

	print("<p>We are sorry, but we could not post your message for the "
             ."following reason:\n"
	     ."<blockquote>Group $group is read-only.</blockquote>\n");

	html_footer();
        
      }
      else
        post_message($group, $PROJECT_MODULE, $start, $msg, $search);
      break;

  case 'r' : // Reply message
      reply_message($group, $PROJECT_MODULE, $start, $msg, $search, $from);
      break;

  case 'v' : // View message
      show_message($group, $PROJECT_MODULE, $start, $msg, $search);
      break;
}


//
// End of "$Id: newsgroups.php,v 1.1 2004/06/10 02:40:05 mike Exp $".
//
?>
