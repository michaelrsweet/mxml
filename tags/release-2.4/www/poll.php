<?php
//
// "$Id: poll.php,v 1.3 2004/05/20 15:45:55 mike Exp $"
//
// Poll page...
//

//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/poll.php";
include_once "phplib/common.php";


// Get the operation code:
//
// cN = show poll N
// eN = edit poll N
// l  = list all polls
// n  = new poll
// rN = show results of poll N
// uN = update poll N (POST)
// vN = vote for poll N (POST)

$poll = 0;

if ($argc > 0)
{
  $op         = $argv[0][0];
  $argv[0][0] = ' ';
  $poll       = (int)$argv[0];
}
else if ($LOGIN_LEVEL >= AUTH_DEVEL)
  $op = 'l';
else
  $op = 'c';

if ($poll == 0 && $op != 'u' && $op != 'n')
  $poll = get_recent_poll();

// Do it!
switch ($op)
{
  case 'c' : // Show a poll
      html_header("Poll #$poll");
      print("<h1>Poll #$poll</h1>\n");
      show_poll($poll);
      html_footer();
      break;

  case 'l' : // List all polls
      html_header("Polls");

      if ($LOGIN_LEVEL > AUTH_USER)
      {
        // Show all polls and allow poll creation...
        $result = db_query("SELECT * FROM poll ORDER BY id DESC");

        html_start_links(1);
	html_link("Add New Poll", "$PHP_SELF?n");
	html_end_links(1);
      }
      else
      {
        // Only show published polls...
        $result = db_query("SELECT * FROM poll WHERE is_published = 1 "
	                  ."ORDER BY id DESC");
      }

      print("<h1>Polls</h1>\n");

      if (db_count($result) == 0)
      {
        print("<p>No polls found.</p>\n");
      }
      else
      {
	html_start_table(array("ID", "Question::2"));

	while ($row = db_next($result))
	{
          $id       = $row['id'];
          $votes    = $row['votes'];
	  $question = htmlspecialchars($row['question']);
          $ccount   = count_comments("poll.php_r$id");

          if ($ccount == 1)
	    $ccount .= " comment";
	  else
	    $ccount .= " comments";

          html_start_row();
          print("<td align='center'>#$row[id]</td>"
	       ."<td align='center' width='67%'>$question");
	  if (!$row['is_published'])
	    print(" <img src='images/private.gif' width='16' height='16' "
		 ."align='middle' alt='private'/>");
	  print("</td><td nowrap><a href='$PHP_SELF?c$id'>Vote</a> | "
	       ."<a href='$PHP_SELF?r$id'>Results</a>");

          if ($LOGIN_LEVEL > AUTH_USER)
	    print(" | <a href='$PHP_SELF?e$id'>Edit</a>");

	  print(" ($votes total votes, $ccount)</td>");
	  html_end_row();
	}

	html_end_table();
      }

      db_free($result);

      html_footer();
      break;

  case 'r' : // Show results
      html_header("Poll #$poll");

      html_start_links(1);
      html_link("Show All Polls", "$PHP_SELF?l");
      html_link("Show Comments", "#_USER_COMMENTS");
      html_link("Submit Comment", "comment.php?r0+ppoll.php_r$poll");
      html_end_links(1);

      print("<h1>Poll #$poll</h1>\n");

      $result = db_query("SELECT * FROM poll WHERE id = $poll");
      $row    = db_next($result);

      $votes = $row['votes'];

      for ($max_count = 0, $i = 0; $i < 10; $i ++)
      {
        if ($row["count$i"] > $max_count)
	  $max_count = $row["count$i"];
      }

      if ($votes == 0)
        print("<p>No votes for this poll yet...</p>\n");
      else
      {
        $question = htmlspecialchars($row['question']);

	print("<center><table>\n");
	print("<tr><td></td><th align='left'>$question</th></tr>\n");

        for ($i = 0; $i < 10; $i ++)
	{
	  if ($row["answer$i"] != "")
	  {
	    $percent = (int)(100 * $row["count$i"] / $votes);
	    $size    = (int)(300 * $row["count$i"] / $max_count);
	    $answer  = htmlspecialchars($row["answer$i"]);
	    $count   = $row["count$i"];

	    print("<tr><td align='right'>$answer</td><td>"
		 ."<img src='${rootpath}images/graph.gif' width='$size' "
		 ."height='12'> $count / $percent%</td></tr>\n");
          }
        }

	print("<tr><td></td><th align='right'>$votes total votes.</th></tr>\n");
	print("</table></center>\n");
      }

      print("<hr noshade/>\n"
           ."<h2><a name='_USER_COMMENTS'>User Comments</a></h2>\n");
      html_start_links();
      html_link("Submit Comment", "comment.php?r0+ppoll.php_r$poll");
      html_end_links();

      show_comments("poll.php_r$poll");

      db_free($result);

      html_footer();
      break;

  case 'v' : // Vote on a poll
      $answers = "";

      if ($REQUEST_METHOD == "POST")
      {
	if (array_key_exists("ANSWER", $_POST))
	{
	  $answer  = (int)$_POST["ANSWER"];
	  $answers = ",count$answer=count$answer+1";
	}
	else
	{
	  for ($i = 0; $i < 10; $i ++)
	  {
	    if (array_key_exists("ANSWER$i", $_POST))
	      $answers .= ",count$i=count$i+1";
	  }
	}
      }

      if ($answers != "")
      {
	if (!db_query("INSERT INTO vote VALUES('poll_${poll}_${REMOTE_ADDR}')")
	    && $LOGIN_LEVEL < AUTH_ADMIN)
	{
	  html_header("Poll Error");
	  print("<h1>Poll Error</h1>\n");
	  print("<p>Sorry, it appears that you or someone else using your IP "
	       ."address has already voted for "
	       ."<a href='$PHP_SELF?r$poll'>poll #$poll</a>.\n");
	  html_footer();
	}
	else
	{
          db_query("UPDATE poll SET votes=votes+1$answers WHERE id = $poll");

          header("Location: $PHP_SELF?r$poll");
	}
      }
      else
      {
        header("Location: $PHP_SELF?c$poll");
      }
      break;

  case 'n' : // New poll
  case 'e' : // Edit poll
      if (!$LOGIN_USER)
      {
        header("Location:$PHP_SELF?r$poll");
	break;
      }

      if ($poll)
      {
        html_header("Poll #$poll");

        print("<h1>Poll #$poll</h1>\n");

	$result = db_query("SELECT * FROM poll WHERE id = $poll");
	$row    = db_next($result);

        $question  = htmlspecialchars($row['question']);
	$poll_type = $row['poll_type'];

        for ($i = 0; $i < 10; $i ++)
	{
	  if ($row["answer$i"])
	    $answer[$i] = htmlspecialchars($row["answer$i"], ENT_QUOTES);
	  else
	    $answer[$i] = "";
	}

        $is_published = $row['is_published'];

        db_free($result);
      }
      else
      {
        html_header("New Poll");

        print("<h1>New Poll</h1>\n");

	$question  = "";
	$poll_type = $POLL_TYPE_PICKONE;
	$answer[0] = "";
	$answer[1] = "";
	$answer[2] = "";
	$answer[3] = "";
	$answer[4] = "";
	$answer[5] = "";
	$answer[6] = "";
	$answer[7] = "";
	$answer[8] = "";
	$answer[9] = "";
	$is_published = 0;
      }

      print("<form method='POST' action='$PHP_SELF?u$poll'>\n");
      print("<center><table>\n"
           ."<tr><th align='right' valign='top'>Question:</th><td>"
	   ."<textarea name='QUESTION' wrap='virtual' cols='40' rows='4'>"
	   ."$question</textarea></td></tr>\n");

      print("<tr><th align='right'>Type:</th><td>"
	   ."<select name='POLLTYPE'>");

      print("<option value='$POLL_TYPE_PICKONE'");
      if ($poll_type == $POLL_TYPE_PICKONE)
        print(" selected");
      print(">Pick One</option>");

      print("<option value='$POLL_TYPE_PICKMANY'");
      if ($poll_type == $POLL_TYPE_PICKMANY)
        print(" selected");
      print(">Pick Many</option>");

      print("</select></td></tr>\n");
      
      print("<tr><th align='right'>Published:</th><td>");
      select_is_published($is_published);
      print("</td></tr>\n");
      
      for ($i = 0; $i < 10; $i ++)
      {
        $number = $i + 1;

	print("<tr><TH ALIGN='RIGHT'>Answer #$number</th><td>"
	     ."<INPUT NAME='ANSWER$i' SIZE='45' MAXLENGTH='255' "
	     ."VALUE='$answer[$i]'></td></tr>\n");
      }

      if ($poll)
        print("<tr><th></th><td><input type='SUBMIT' VALUE='Update Poll'></td></tr>\n");
      else
        print("<tr><th></th><td><input type='SUBMIT' VALUE='Create Poll'></td></tr>\n");

      print("</table></center>\n");
      print("</form>\n");

      html_footer();
      break;

  case 'u' : // Update poll
      header("Location:$PHP_SELF?l");

      if ($LOGIN_LEVEL < AUTH_DEVEL)
	break;

      $is_published   = (int)$_POST["IS_PUBLISHED"];
      $question       = db_escape($_POST["QUESTION"]);
      $poll_type      = (int)$_POST["POLLTYPE"];
      $answer0        = db_escape($_POST["ANSWER0"]);
      $answer1        = db_escape($_POST["ANSWER1"]);
      $answer2        = db_escape($_POST["ANSWER2"]);
      $answer3        = db_escape($_POST["ANSWER3"]);
      $answer4        = db_escape($_POST["ANSWER4"]);
      $answer5        = db_escape($_POST["ANSWER5"]);
      $answer6        = db_escape($_POST["ANSWER6"]);
      $answer7        = db_escape($_POST["ANSWER7"]);
      $answer8        = db_escape($_POST["ANSWER8"]);
      $answer9        = db_escape($_POST["ANSWER9"]);
      $date           = time();

      if ($poll)
      {
        // Update an existing poll...
	db_query("UPDATE poll SET "
	        ."question='$question',"
		."is_published=$is_published,"
		."poll_type=$poll_type,"
	        ."answer0='$answer0',"
	        ."answer1='$answer1',"
	        ."answer2='$answer2',"
	        ."answer3='$answer3',"
	        ."answer4='$answer4',"
	        ."answer5='$answer5',"
	        ."answer6='$answer6',"
	        ."answer7='$answer7',"
	        ."answer8='$answer8',"
	        ."answer9='$answer9',"
		."modify_date=$date,"
		."modify_user='$LOGIN_USER' "
		."WHERE id = $poll");
      }
      else
      {
        // Create a new poll...
	db_query("INSERT INTO poll VALUES(NULL,"
	        ."$is_published,"
	        ."$poll_type,"
	        ."'$question',"
	        ."'$answer0',0,"
	        ."'$answer1',0,"
	        ."'$answer2',0,"
	        ."'$answer3',0,"
	        ."'$answer4',0,"
	        ."'$answer5',0,"
	        ."'$answer6',0,"
	        ."'$answer7',0,"
	        ."'$answer8',0,"
	        ."'$answer9',0,"
		."0,"
		."$date,'$LOGIN_USER',"
		."$date,'$LOGIN_USER')");
      }
      break;
}

db_close();

//
// End of "$Id: poll.php,v 1.3 2004/05/20 15:45:55 mike Exp $".
//
?>
