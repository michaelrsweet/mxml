<?php
//
// "$Id$"
//
// Forum page...
//
// Contents:
//
//   sanitize_email()     - Convert an email address to something a SPAMbot
//                          can't read...
//   format_date()        - Format a RFC 2822 date string.
//   nntp_close()         - Close a news server thing...
//   nntp_command()       - Send a command and get the response...
//   nntp_connect()       - Connect to the news server.
//   nntp_error()         - Show an error message.
//   nntp_search()        - Do a header search...
//   show_prevnext_page() - Show the prev/next links for the messages list...
//   show_messages()      - Show messages in the named group...
//   show_groups()        - Show groups...
//   show_prevnext_msg()  - Show the prev/next links for the messages list...
//   show_message()       - Show a single message...
//   post_message()       - Post a message...
//   reply_message()      - Reply to a message...
//   new_message()        - Post a new message...
//


include_once "phplib/html.php";
include_once "phplib/common.php";

// News server...
//$NNTPSERVER = "localhost";
//$NNTPPORT = 8119;
$NNTPSERVER = "news.easysw.com";
$NNTPPORT = 119;

// Cookie stuff...
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
else if (array_key_exists("${PROJECT_MODULE}FROM", $_COOKIE))
  $from = $_COOKIE["${PROJECT_MODULE}FROM"];
else
  $from = "Anonymous <anonymous@minixml.org>";

if ($from == "" || $from == "Anonymous")
  $from = "Anonymous <anonymous@minixml.org>";


//
// 'sanitize_email()' - Convert an email address to something a SPAMbot
//                      can't read...
//

function				// O - Sanitized email
sanitize_email($email,			// I - Email address
               $html = 1)		// I - HTML format?
{
  $nemail = "";
  $len    = strlen($email);

  for ($i = 0; $i < $len; $i ++)
  {
    switch ($email[$i])
    {
      case '@' :
          if ($i > 0)
	    $i = $len;
          else if ($html)
            $nemail .= " <I>at</I> ";
	  else
            $nemail .= " at ";
	  break;

      case '<' :
          if ($i > 0)
	    $i = $len;
          break;

      case '>' :
          break;

      case '&' ;
          $nemail .= "&amp;";
	  break;

      default :
          $nemail .= $email[$i];
	  break;
    }
  }

  return (trim($nemail));
}


//
// 'format_date()' - Format a RFC 2822 date string.
//

function				// O - Date/time in human format
format_date($rfc2822)			// I - Date/time in RFC 2822 format
{
  if (($time = strtotime($rfc2822)) < 0)
    $date = htmlspecialchars($rfc2822, ENT_QUOTES);
  else
  {
    $diff = abs(time() - $time);

    if ($diff < 604800)
      $date = date("H:i D", $time);
    else if ($diff < 31536000)
      $date = date("H:i M d", $time);
    else
      $date = date("M d, Y", $time);
  }

  return ($date);
}


//
// 'nntp_header()' - Show the standard header and nav links.
//

function
nntp_header($title,			// I - Title
            $links)			// I - Links
{
  html_header($title);
  html_start_links(TRUE);
  html_links($links);
  html_end_links();
}


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

//  if ((int)$status != $expect)
//  {
//    print("<p><b>Error:</b> $status</p>\n");
//    return (NULL);
//  }
//  else
    return ($status);
}


//
// 'nntp_connect()' - Connect to the news server.
//

function				// O - Socket stream
nntp_connect()
{
  global $NNTPSERVER, $NNTPPORT;


  $errno  = 0;
  $errstr = "";
  $stream = fsockopen($NNTPSERVER, $NNTPPORT, $errno, $errstr);

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
// 'nntp_error()' - Show an error message.
//

function
nntp_error($text,			// I - Human-readable message
           $status,			// I - NNTP status message
	   $group = "")			// I - Current group, if any
{
  $links = array();

  $links["All Forums"] = "forums.php";
  if ($group != "")
    $links["Back to $group"] = "forums.php?g$group";

  nntp_header("Error", $links);

  print("<p>$text</p>\n"
       ."<blockquote>$status</blockquote>\n");

  html_footer();
}


//
// 'nntp_search()' - Do a header search...
//

function				// O - Matching message headers...
nntp_search($stream,			// I - Socket stream
            $group,			// I - NNTP group
            $search,			// I - Search text
	    $threaded = TRUE)		// I - Thread messages?
{
//  print("<p>nntp_search(stream=$stream, group='$group', search='$search'</p>\n");

  // Get the start and end messages in the group...
  $status = nntp_command($stream, "GROUP $group", 211);
  if ((int)$status != 211)
  {
    nntp_error("We were unable to open the forum '$group' for the following "
              ."reason:", $status, $group);
    return (NULL);
  }

  // Read the messages in the group...
  $fields = explode(" ", $status);
  $status = nntp_command($stream, "XOVER $fields[2]-$fields[3]", 224);
  if ((int)$status != 224)
  {
    nntp_error("We were unable to search the forum '$group' for the following "
              ."reason:", $status, $group);
    return (NULL);
  }

  $words       = html_search_words($search);
  $num_matches = 0;
  $matches     = NULL;

  while ($line = fgets($stream, 1024))
  {
    $line = rtrim($line);

    if ($line == ".")
      break;

//    print("<pre>" . htmlspecialchars($line) . "</pre>\n");

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

      $fields = explode("\t", $line);

      foreach ($words as $word)
      {
	if (stristr($fields[1], $word) || stristr($fields[2], $word))
	{
          $matches[$num_matches] = $line;
	  $num_matches ++;
	  break;
	}
      }
    }
  }

  if ($threaded)
  {
    // Thread the articles...
    $threads = array();
    $parents = array();

    for ($i = 0; $i < sizeof($matches); $i ++)
    {
      $fields  = explode("\t", $matches[$i]);
      $subject = eregi_replace("(re:|\\[[a-z]+\\.[a-z]+\\]) ", "", $fields[1]);

      if (array_key_exists($subject, $parents))
        $threads[$i] = sprintf("%06d%06d", $parents[$subject], $i);
      else
      {
        $parents["$subject"] = $i;
        $threads[$i]         = sprintf("%06d%06d", $i, $i);
      }
    }

    array_multisort($threads, SORT_NUMERIC, $matches);
  }

  // Return the matches...
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
		   $count,		// I - Number of messages
		   $threaded)		// I - Thread messages?
{
  global $PHP_SELF, $PAGE_MAX, $options;


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
    html_link("Show Messages $i - $j", "$PHP_SELF?s$i+g$group$options");
    html_end_links();
  }

  print("</td><td align='center' width='50%'>");
  html_start_links();
  if (!ereg(".*\.announce", $group) && !ereg(".*\.commit", $group))
    html_link("New Message", "$PHP_SELF?s$start+g$group+n$options");
  if ($threaded)
    html_link("Sort by Date",
              "$PHP_SELF?s$start+g$group+T0" . substr($options, 3));
  else
    html_link("Sort by Thread",
              "$PHP_SELF?s$start+g$group+T1" . substr($options, 3));
  html_end_links();
  print("</td><td align='right' width='25%'>");

  if ($end < $count)
  {
    $i = $start + $PAGE_MAX;
    $j = $i + $PAGE_MAX - 1;
    if ($j > $count)
      $j = $count;

    html_start_links();
    html_link("Show Messages $i - $j", "$PHP_SELF?s$i+g$group$options");
    html_end_links();
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
	      $search,			// I - Search string
	      $threaded)		// I - Threaded view?
{
  global $PHP_SELF, $PAGE_MAX, $_COOKIE, $options;


  // Figure out which messages to show...
  $error = "";

  $stream  = nntp_connect();
  $matches = nntp_search($stream, $group, $search, $threaded);

  nntp_close($stream);

  if (!$matches)
  {
    $count = 0;

    if ($search == "")
      $error = "No messages in group.";
    else
      $error  = "No matches found for '" .
                htmlspecialchars($search, ENT_QUOTES) . "'...";
  }
  else
    $count = count($matches);

  if ($start == 0)
  {
    if ($search == "")
    {
      $cookie = str_replace(".", "_", $group);

      if (array_key_exists($cookie, $_COOKIE))
      {
        $msgnum = (int)$_COOKIE[$cookie];

	for ($i = 0; $i < $count; $i ++)
	{
          $fields  = explode("\t", $matches[$i]);
	  if ((int)$fields[0] == $msgnum)
	    break;
        }

        $start = $i + 1;
      }
      else
        $start = $count - $PAGE_MAX + 1;
    }
    else
      $start = 1;
  }

  if ($start > ($count - $PAGE_MAX + 1))
    $start = $count - $PAGE_MAX + 1;
  if ($start < 1)
    $start = 1;

  $end = $start + $PAGE_MAX - 1;
  if ($end > $count)
    $end = $count;

  // Show the standard header...
  nntp_header("$group ($start - $end of $count)",
              array("All Forums" => "forums.php?g$options"));

  $temp = htmlspecialchars($search, ENT_QUOTES);
  print("<form method='post' action='$PHP_SELF?g$group'>\n"
       ."<p align='center'>Search Words: <input type='text' name='SEARCH' value='$temp' "
       ."size='60'/><input type='submit' value='Search $group'/></p>\n"
       ."</form>\n");

  if ($error != "")
    print("<p align='center'><i>$error</i></p>\n");
  else
  {
    show_prevnext_page($group, $group_filter, $start, $end, $count, $threaded);

    html_start_table(array("Subject", "Author", "Date/Time"));

    for ($i = $start; $i <= $end; $i ++)
    {
      $fields  = explode("\t", $matches[$i - 1]);
      $subject = htmlspecialchars(eregi_replace("\\[[a-z]+\\.[a-z]+\\] ", "",
                                                $fields[1]), ENT_QUOTES);
      $author  = sanitize_email($fields[2]);
      $date    = format_date($fields[3]);

      if ($subject == "")
        $subject = "(No Subject)";

      html_start_row();
      print("<td width='80%'><a href='$PHP_SELF?s$start+g$group+v$i$options'>"
           ."$subject</a></td>"
           ."<td nowrap>&nbsp;&nbsp;$author&nbsp;&nbsp;</td>"
	   ."<td nowrap>$date</td>");
      html_end_row();
    }

    html_end_table();

    show_prevnext_page($group, $group_filter, $start, $end, $count, $threaded);
  }

  html_footer();
}


//
// 'show_groups()' - Show groups...
//

function
show_groups($group_filter,		// I - Group filter
            $search)			// I - Search string
{
  global $PHP_SELF, $_COOKIE, $options;


  nntp_header("Forums",
              array("All Forums" => "forums.php?g$options"));

  // Figure out which messages to show...
  $stream = nntp_connect();

  // Search stuff...
  print("<form method='post' action='$PHP_SELF'>\n"
       ."<p align='center'>Search Words: <input type='text' name='SEARCH' value='$search' "
       ."size='60'/><input type='submit' value='Search All Forums'/></p>\n"
       ."</form>\n");

  // Show the standard header...
  html_start_table(array("Forum", "Messages", ""));

  $status     = nntp_command($stream, "LIST", 215);
  $num_groups = 0;
  $groups     = array();

  if ((int)$status == 215)
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
    if ((int)$status != 211)
      continue;

    $fields = explode(" ", $status);
    $total  = (int)$fields[1];

    if ($search != "")
    {
      $matches = nntp_search($stream, $group, $search);
      $mcount  = count($matches);
      $count   = "$total total, $mcount match";
    }
    else
    {
      $cookie = str_replace(".", "_", $group);

      if (array_key_exists($cookie, $_COOKIE))
      {
        $newcount = (int)$fields[3] - (int)$_COOKIE[$cookie];

        $count = "$total total, $newcount unread";
      }
      else
        $count = "$total total";
    }

    html_start_row();
    print("<td align='center'>$group</td>"
         ."<td align='center'>$count");

    if ($search != "")
      print("/$total");

    print("</td><td>");
    html_start_links();
    html_link("View", "$PHP_SELF?g$group$options");
    if (!ereg(".*\.announce", $group) && !ereg(".*\.commit", $group))
      html_link("New Message", "$PHP_SELF?g$group+n$options");
    html_end_links();
    print("</td>");
    html_end_row();
  }

  html_start_row("header");
  print("<th colspan='3'>Forums and Mailing Lists</th>");
  html_end_row();
  html_start_row();
  print("<td colspan='3'>"
       ."<p>Point your news reader at <a href='news://news.easysw.com'>"
       ."news.easysw.com</a> to view these forums directly.</p>\n"
       ."<p>Go to <a href='http://lists.easysw.com/mailman/listinfo'>"
       ."http://lists.easysw.com/mailman/listinfo</a> "
       ."to subscribe to or unsubcribe from the mailing lists that mirror "
       ."these forums.</p>"
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
		  $msg,			// I - Current message
		  $threaded)		// I - Thread messages?
{
  global $PHP_SELF, $options;


  print("<p><table width='100%' border='0' cellpadding='0' cellspacing='0'>\n"
       ."<tr><td width='25%'>");

  if ($msg > 1)
  {
    $i = $msg - 1;

    html_start_links();
    html_link("Previous Message", "$PHP_SELF?s$start+g$group+v$i$options");
    html_end_links();
  }

  print("</td><td align='center' width='50%'>");
  if (!ereg(".*\.announce", $group) && !ereg(".*\.commit", $group))
  {
    html_start_links();
    html_link("New Message", "$PHP_SELF?s$start+g$group+n$options");
    html_link("Reply", "$PHP_SELF?s$start+g$group+r$msg$options");
    html_end_links();
  }

  print("</td><td align='right' width='25%'>");

  if ($msg < $count)
  {
    $i = $msg + 1;
    html_start_links();
    html_link("Next Message", "$PHP_SELF?s$start+g$group+v$i$options");
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
	     $search,			// I - Search string
	     $threaded)			// I - Thread messages?
{
  global $PHP_SELF, $_COOKIE, $options;


//  print("<!-- show_message(group='$group', group_filter='$group_filter', "
//       ."start=$start, msg=$msg, search='$search', threaded=$threaded) -->\n");

  // Figure out which messages to show...
  $stream  = nntp_connect();
  $matches = nntp_search($stream, $group, $search, $threaded);
  $count   = count($matches);

  if ($msg[0] == ':')
  {
    // Lookup a specific message ID...
    $msg = (int)substr($msg, 1);

    for ($i = 0; $i < $count; $i ++)
    {
      $fields = explode("\t", $matches[$i]);

      if ($msg == $fields[0])
        break;
    }

    if ($i >= $count)
    {
      nntp_error("We were unable to show the requested message for the following "
        	."reason:", "The message number ($msg) is out of range.", $group);
      nntp_close($stream);
      return;
    }

    $msg = $i;
  }
  else
  {
    // Lookup index into search...
    if ($msg < 1 || $msg > $count)
    {
      nntp_error("We were unable to show the requested message for the following "
        	."reason:", "The message number is out of range.", $group);
      nntp_close($stream);
      return;
    }

    $fields = explode("\t", $matches[$msg - 1]);
  }

//  print("<!-- fields =");
//  print_r($fields);
//  print("-->\n");

  $msgnum  = (int)$fields[0];
  $subject = htmlspecialchars(eregi_replace("\\[[a-z]+\\.[a-z]+\\] ", "",
                                            $fields[1]), ENT_QUOTES);
  $author  = sanitize_email($fields[2]);
  $date    = format_date($fields[3]);

  if ($subject == "")
    $subject = "(No Subject)";

  // Save last message read...
  $cookie = str_replace(".", "_", $group);
  if ($search == "" &&
      (!array_key_exists($group, $_COOKIE) || (int)$_COOKIE[$cookie] < $msgnum))
    setcookie($cookie, $msgnum, time() + 90 * 86400, "/");

  $status = nntp_command($stream, "BODY $msgnum", 222);
  if ((int)$status != 222)
  {
    nntp_close($stream);
    nntp_error("We were unable to show the requested message for the following "
              ."reason:", $status, $group);
    return (NULL);
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

  nntp_header("$subject",
              array("All Forums" => "forums.php?g$options",
	            "Back to $group" => "forums.php?g$group+s$start$options"));

  show_prevnext_msg($group, $group_filter, $start, $count, $msg, $threaded);

  html_start_table(array($subject, $author, $date), "", "", TRUE);
  html_start_row();
  print("<td colspan='3'><tt>$body</tt><br />\n"
       ."[&nbsp;<a href='$PHP_SELF?g$group+v:$msgnum'>Direct&nbsp;Link"
       ."&nbsp;to&nbsp;Message</a>&nbsp;]</td>");
  html_end_row();
  html_end_table();

  show_prevnext_msg($group, $group_filter, $start, $count, $msg, $threaded);

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
	     $search,			// I - Search string
	     $threaded)			// I - Thread messages?
{
  global $LOGIN_USER, $PHP_SELF, $PROJECT_URL, $_POST, $options;


  // Get form data...
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

  // Validate form data...
  if (!validate_email($from) || $subject == "" || $body == "")
  {
    new_message($group, $group_filter, $start, $from, $subject, $body);
    return;
  }

  // Connect to the news server and get the reply-to message ID...
  $stream = nntp_connect();
  if (!$stream)
  {
    return;
  }

  $id = "";

  if ($msg > 0)
  {
    $matches = nntp_search($stream, $group, $search, $threaded);
    $count   = count($matches);

    if ($msg <= $count)
    {
      $fields = explode("\t", $matches[$msg - 1]);
      $id     = $fields[4];
    }
  }

  // Create the message body...
  $message = "From: $from\r\n"
            ."Subject: $subject\r\n"
            ."Newsgroups: $group\r\n";

  if ($id != "")
    $message .= "In-Reply-To: $id\r\n";

  $message .= "X-Login-Name: $LOGIN_USER\r\n"
             ."X-Site-URL: $PROJECT_URL\r\n"
             ."\r\n";

  $lines = explode("\n", $body);
  $count = count($lines);

  for ($i = 0; $i < $count; $i ++)
  {
    $line = rtrim($lines[$i]);

    if ($line == ".")
      $message .= ". \r\n";
    else
      $message .= "$line\r\n";
  }

  // Run the message by spamc to see if it thinks the message is
  // spam...
  $p = popen("spamc -c >/dev/null", "w");
  if ($p)
  {
    fwrite($p, $message);
    if (pclose($p))
    {
      // Message is spam...
      nntp_header("$group Error",
        	  array("All Forums" => "forums.php?g$options",
	        	"Back to $group" => "forums.php?g$group+s$start$options"));

      print("<p>Your message could not be posted for the following reason:</p>\n"
           ."<blockquote>The anti-spam filters determined that your message "
	   ."is most likely an unsolicited commercial message that is not "
	   ."allowed on this group. If this is not the case, please press your "
	   ."browser's <var>Back</var> button and check that the message does "
	   ."not contain common spam phrases like 'an offer for you' and so "
	   ."forth.</blockquote>\n");

      html_footer();
      return;
    }
  }

  // Post the message...
  $status = nntp_command($stream, "POST", 340);

  if ((int)$status != 340)
  {
    nntp_close($stream);
    nntp_error("We were unable to post the requested message for the following "
              ."reason:", $status, $group);
    return;
  }

  fwrite($stream, $message);

  // Get the posting status...
  $status = nntp_command($stream, ".", 240);

  if ((int)$status == 240)
  {
    if ($msg == 0)
      header("Location: $PHP_SELF?s$start+g$group$options");
    else
      header("Location: $PHP_SELF?s$start+g$group+v$msg$options");
  }
  else
    nntp_error("We were unable to post the requested message for the following "
              ."reason:", $status, $group);

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
	      $threaded,		// I - Thread messages?
	      $sender)			// I - Sender address
{
  // Figure out which messages to show...
  $stream  = nntp_connect();
  $matches = nntp_search($stream, $group, $search, $threaded);
  $count   = count($matches);

  if ($msg < 1 || $msg > $count)
  {
    nntp_close($stream);
    return;
  }

  $fields  = explode("\t", $matches[$msg - 1]);
  $msgnum  = (int)$fields[0];
  $subject = eregi_replace("\\[[a-z]+\\.[a-z]+\\] ", "", $fields[1]);
  $author  = sanitize_email($fields[2]);
  $date    = htmlspecialchars($fields[3], ENT_QUOTES);

  if (strncasecmp($subject, "re:", 3))
    $subject = "Re: " . $subject;

  $status = nntp_command($stream, "BODY $msgnum", 222);
  if ((int)$status != 222)
  {
    nntp_close($stream);
    nntp_error("We were unable to reply to the requested message for the following "
              ."reason:", $status, $group);
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
  global $PHP_SELF, $NNTPSPEC, $options;


  $subject = htmlspecialchars($subject, ENT_QUOTES);
  $sender  = htmlspecialchars($sender, ENT_QUOTES);
  $body    = htmlspecialchars($body, ENT_QUOTES);

  nntp_header("Post Message to $group",
              array("All Forums" => "forums.php?g$options",
	            "Back to $group" => "forums.php?g$group+s$start$options"));

  print("<h2>Post Message to $group</h2>");

  print("<form action='$PHP_SELF?s$start+g$group+p0$options' method='POST'>\n");

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
$start    = 0;
$group    = "";
$op       = 'l';
$msg      = "";
$groups   = "minixml";

if (array_key_exists("THREADED", $_COOKIE))
  $threaded = $_COOKIE["THREADED"] != 0;
else
  $threaded = FALSE;

if (array_key_exists("SEARCH", $_POST))
  $search = $_POST["SEARCH"];
else
  $search = "";

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
        $op  = $argv[$i][0];
        $msg = (int)substr($argv[$i], 1);
	break;

    case 'v' :
        $op  = $argv[$i][0];
        $msg = substr($argv[$i], 1);
	break;

    case 's' :
        $start = (int)substr($argv[$i], 1);
	break;

    case 'T' : // Set threading
        $threaded = (int)substr($argv[$i], 1);
	break;

    case 'Q' : // Set search text
        $search = urldecode(substr($argv[$i], 1));
	$i ++;
	while ($i < $argc)
	{
	  $search .= urldecode(" $argv[$i]");
	  $i ++;
	}
	break;
  }
}

setcookie("THREADED", $threaded, time() + 90 * 86400, "/");

if ($search != "")
  $options = "+T$threaded+Q" . urlencode($search);
else
  $options = "+T$threaded";

// Now handle the request...
switch ($op)
{
  case 'l' : // List
      if ($group)
        show_messages($group, $groups, $start, $search, $threaded);
      else
        show_groups($groups, $search);
      break;

  case 'n' : // New message
      if ($LOGIN_USER == "")
      {
        $options = str_replace("+", "%2B", "+g" . urlencode($group) . $options);
        header("Location: login.php?PAGE=$PHP_SELF?n$options");
	return;
      }

      new_message($group, $groups, $start, "", $from, "");
      break;

  case 'p' : // Post message
      if ($LOGIN_USER == "")
      {
        $options = str_replace("+", "%2B", "+g" . urlencode($group) . $options);
        header("Location: login.php?PAGE=$PHP_SELF?l$options");
	return;
      }

      if (ereg(".*\.announce", $group) || ereg(".*\.commit", $group))
      {
	nntp_header("Forum Posting Error",
        	    array("All Forums" => "forums.php?g$options",
	        	  "Back to $group" => "forums.php?g$group+s$start$options"));

	print("<p>We are sorry, but we could not post your message for the "
             ."following reason:\n"
	     ."<blockquote>Forum $group is read-only.</blockquote>\n");

	html_footer();
        
      }
      else
        post_message($group, $groups, $start, $msg, $search, $threaded);
      break;

  case 'r' : // Reply message
      if ($LOGIN_USER == "")
      {
        $options = str_replace("+", "%2B", "+g" . urlencode($group) . $options);
        header("Location: login.php?PAGE=$PHP_SELF?r$msg$options");
	return;
      }

      reply_message($group, $groups, $start, $msg, $search, $threaded,
                    $from);
      break;

  case 'v' : // View message
      show_message($group, $groups, $start, $msg, $search, $threaded);
      break;
}


//
// End of "$Id$".
//
?>
