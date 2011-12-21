<?php
//
// "$Id$"
//
// Web form for the article table...
//
// Contents:
//
//   notify_users() - Notify users of new/updated articles...
//


//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";


//
// 'notify_users()' - Notify users of new/updated articles...
//

function
notify_users($id,			// I - Article #
             $what = "created")		// I - Reason for notification
{
  global $PHP_URL, $PROJECT_EMAIL, $PROJECT_NAME;


  $result = db_query("SELECT * FROM article WHERE id = $id");
  if (db_count($result) == 1)
  {
    $row = db_next($result);

    mail($PROJECT_EMAIL, "$PROJECT_NAME Article #$id $what",
	 wordwrap("$row[create_user] has $what an article titled, "
	         ."'$row[title]' with the following abstract:\n\n"
		 ."    $row[abstract]\n\n"
		 ."Please approve or delete this article via the following "
		 ."page:\n\n"
		 ."    $PHP_URL?L$id\n"),
	 "From: $PROJECT_EMAIL\r\n");
  }
}


// Get command-line options...
//
// Usage: article.php [operation] [options]
//
// Operations:
//
// B         - Batch update selected articles
// D#        - Delete article
// L         = List all 
// L#        = List article #
// M#        = Modify article #
// N         = Create new article
//
// Options:
//
// I#        = Set first article
// Qtext     = Set search text

$search = "";
$index  = 0;

if ($argc)
{
  $op = $argv[0][0];
  $id = (int)substr($argv[0], 1);

  if ($op != 'D' && $op != 'L' && $op != 'M' && $op != 'N' && $op != 'B')
  {
    html_header("Article Error");
    print("<p>Bad command '$op'!\n");
    html_footer();
    exit();
  }

  if ($op != 'L' && $LOGIN_USER == "")
  {
    header("Location: login.php");
    exit();
  }

  if (($op == 'D' || $op == 'M') && !$id)
  {
    html_header("Article Error");
    print("<p>Command '$op' requires an ID!\n");
    html_footer();
    exit();
  }

  if ($op == 'B' && $LOGIN_LEVEL < AUTH_DEVEL)
  {
    html_header("Article Error");
    print("<p>You don't have permission to use command '$op'!\n");
    html_footer();
    exit();
  }

  if (($op == 'D' || $op == 'M') && $LOGIN_LEVEL < AUTH_DEVEL)
  {
    $result = db_query("SELECT * FROM article WHERE id = $id");
    if (db_count($result) != 1)
    {
      db_free($result);

      html_header("Article Error");
      print("<p>Article #$id does not exist!\n");
      html_footer();
      exit();
    }

    $row = db_next($result);

    if ($row['create_user'] != $LOGIN_USER &&
        $row['create_user'] != $LOGIN_EMAIL)
    {
      db_free($result);

      html_header("Article Error");
      print("<p>You don't have permission to use command '$op'!\n");
      html_footer();
      exit();
    }

    db_free($result);
  }

  if ($op == 'N' && $id)
  {
    html_header("Article Error");
    print("<p>Command '$op' may not have an ID!\n");
    html_footer();
    exit();
  }

  for ($i = 1; $i < $argc; $i ++)
  {
    $option = substr($argv[$i], 1);

    switch ($argv[$i][0])
    {
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
      default :
	  html_header("Article Error");
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
  if (array_key_exists("SEARCH", $_POST))
    $search = $_POST["SEARCH"];
}

$options = "+I$index+Q" . urlencode($search);

switch ($op)
{
  case 'B' : // Batch update selected articles
      if ($REQUEST_METHOD != "POST")
      {
        header("Location: $PHP_SELF?L$options");
        break;
      }

      if (array_key_exists("IS_PUBLISHED", $_POST) &&
          $_POST["IS_PUBLISHED"] != "")
      {
        $modify_date  = time();
        $modify_user  = db_escape($LOGIN_USER);
	$is_published = (int)$_POST["IS_PUBLISHED"];

        $query = "is_published = $is_published, modify_date = $modify_date, "
	        ."modify_user = '$modify_user'";

        db_query("BEGIN TRANSACTION");

        reset($_POST);
        while (list($key, $val) = each($_POST))
          if (substr($key, 0, 3) == "ID_")
	  {
	    $id = (int)substr($key, 3);

            db_query("UPDATE article SET $query WHERE id = $id");
	  }

        db_query("COMMIT TRANSACTION");
      }

      header("Location: $PHP_SELF?L$options");
      break;

  case 'D' : // Delete Article
      if ($REQUEST_METHOD == "POST")
      {
        db_query("DELETE FROM article WHERE id = $id");

        header("Location: $PHP_SELF?L$options");
      }
      else
      {
        $result = db_query("SELECT * FROM article WHERE id = $id");
	if (db_count($result) != 1)
	{
	  print("<p><b>Error:</b> Article #$id was not found!</p>\n");
	  html_footer();
	  exit();
	}

        $row = db_next($result);

        html_header("Delete Article #$id");

	html_start_links(1);
	html_link("Return to Articles", "$PHP_SELF?L$options");
	html_link("View Article #$id", "$PHP_SELF?L$id$options");
	html_link("Modify Article #$id", "$PHP_SELF?M$id$options");
	html_end_links();

        print("<form method='post' action='$PHP_SELF?D$id'>"
	     ."<table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

        if (!$row['is_published'])
	  print("<tr><th align='center' colspan='2'>This article is "
	       ."currently hidden from public view.</td></tr>\n");

        $temp = htmlspecialchars($row["title"]);
        print("<tr><th align='right'>Title:</th><td class='left'>$temp</td></tr>\n");

        $temp = htmlspecialchars($row["abstract"]);
        print("<tr><th align='right'>Abstract:</th><td class='left'>$temp</td></tr>\n");

        $temp = format_text($row["contents"]);
        print("<tr><th align='right'>Contents:</th><td class='left'>$temp</td></tr>\n");

        print("<tr><th colspan='2'>"
	     ."<input type='submit' value='Confirm Delete Article'></th></tr>\n");
        print("</table></form>\n");

        html_footer();
      }
      break;

  case 'L' : // List (all) Article(s)
      if ($id)
      {
        $result = db_query("SELECT * FROM article WHERE id = $id");
	if (db_count($result) != 1)
	{
          html_header("Article Error");
	  print("<p><b>Error:</b> Article #$id was not found!</p>\n");
	  html_footer();
	  exit();
	}

        $row         = db_next($result);
        $title       = htmlspecialchars($row['title']);
        $abstract    = htmlspecialchars($row['abstract']);
	$contents    = format_text($row['contents']);
	$create_user = sanitize_email($row['create_user']);
        $date        = date("H:i M d, Y", $row['modify_date']);

        html_header("Article #$id: $title");

	html_start_links(1);
	html_link("Return to Articles", "$PHP_SELF?L$options");
	html_link("Show Comments", "#_USER_COMMENTS");
	html_link("Submit Comment", "comment.php?r0+particles.php_L$id");

	if ($LOGIN_LEVEL >= AUTH_DEVEL ||
	    $row['create_user'] == $LOGIN_USER)
	{
	  html_link("Modify Article", "$PHP_SELF?M$id$options");
	  html_link("Delete Article", "$PHP_SELF?D$id$options");
	}
	html_end_links();

        if (!$row['is_published'])
	  print("<p align='center'><b>This article is currently hidden from "
	       ."public view.</b></p>\n");

        print("<p><i>$date by $create_user</i><br>$abstract</p>\n"
	     ."$contents\n"
	     ."<h1><a name='_USER_COMMENTS'>Comments</a></h1>\n");

	html_start_links();
	html_link("Submit Comment", "comment.php?r0+particles.php_L$id");
	html_end_links();

	show_comments("articles.php_L$id");

        db_free($result);
      }
      else
      {
        html_header("Articles");

	html_start_links(1);
	html_link("Submit Article", "$PHP_SELF?N$options");
	html_end_links();

	$htmlsearch = htmlspecialchars($search, ENT_QUOTES);

        print("<form method='POST' action='$PHP_SELF'><p align='center'>"
	     ."Search&nbsp;Words: &nbsp;<input type='text' size='60' "
	     ."name='SEARCH' value='$htmlsearch'>"
	     ."<input type='submit' value='Search Articles'></p></form>\n");

        $query = "";
	$prefix = "WHERE ";

        if ($LOGIN_LEVEL < AUTH_DEVEL)
	{
	  $query .= "${prefix}(is_published = 1 OR create_user = '"
	           . db_escape($LOGIN_USER) . "')";
	  $prefix = " AND ";
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
	      $keyword = db_escape($keyword);

              if ($keyword == (int)$keyword)
	        $idsearch = " OR id = " . (int)$keyword;
              else
	        $idsearch = "";

	      $query  .= "$prefix$logic (title LIKE \"%$keyword%\"$idsearch"
	                ." OR abstract LIKE \"%$keyword%\""
	                ." OR contents LIKE \"%$keyword%\")";
	      $prefix = $next;
	      $logic  = '';
	    }
          }

	  $query  .= ")";
	}

        $result = db_query("SELECT * FROM article $query "
	                  ."ORDER BY modify_date DESC");
        $count  = db_count($result);

        if ($count == 0)
	{
	  print("<p>No Articles found.</p>\n");

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

        print("<p>$count article(s) found, showing $start to $end:</p>\n");

        if ($LOGIN_LEVEL >= AUTH_DEVEL)
	  print("<form method='POST' action='$PHP_SELF?B$options'>\n");

        if ($count > $PAGE_MAX)
	{
          print("<table border='0' cellspacing='0' cellpadding='0' "
	       ."width='100%'>\n");

          print("<tr><td>");
	  if ($index > 0)
	    print("<a href='$PHP_SELF?L+I$prev+Q" . urlencode($search)
		 ."'>Previous&nbsp;$PAGE_MAX</a>");
          print("</td><td align='right'>");
	  if ($end < $count)
	  {
	    $next_count = min($PAGE_MAX, $count - $end);
	    print("<a href='$PHP_SELF?L+I$next+Q" . urlencode($search)
		 ."'>Next&nbsp;$next_count</a>");
          }
          print("</td></tr>\n");
	  print("</table>\n");
        }

        html_start_table(array("ID","Title","Last Modified", "Comment(s)"));

	db_seek($result, $index);
	for ($i = 0; $i < $PAGE_MAX && $row = db_next($result); $i ++)
	{
          html_start_row();

          $id   = $row['id'];
	  $link = "<a href='$PHP_SELF?L$id$options' alt='Article #$id'>";

          print("<td nowrap>");
          if ($LOGIN_LEVEL >= AUTH_DEVEL)
	    print("<input type='checkbox' name='ID_$row[id]'>");
          print("$link$id</a></td>");

          $temp = htmlspecialchars($row['title']);
          if ($row['is_published'] == 0)
	    $temp .= " <img src='images/private.gif' width='16' height='16' "
	            ."border='0' align='absmiddle' alt='Private'>";

          print("<td align='center' width='67%'>$link$temp</a></td>");

          $temp = date("M d, Y", $row['modify_date']);
          print("<td align='center'>$link$temp</a></td>");

          $ccount = count_comments("articles.php_L$id");
          print("<td align='center'>$link$ccount</a></td>");

          html_end_row();

          html_start_row();
          $temp = htmlspecialchars($row['abstract']);
          print("<td></td><td colspan='3'>$temp</td>");
          html_end_row();
	}

        html_end_table();

        if ($LOGIN_LEVEL > 0)
	{
	  print("<p>Published:&nbsp;");
	  select_is_published();
	  print("<input type='submit' value='Modify Selected Articles'></p>\n");
        }

        if ($count > $PAGE_MAX)
	{
          print("<table border='0' cellspacing='0' cellpadding='0' "
	       ."width='100%'>\n");

          print("<tr><td>");
	  if ($index > 0)
	    print("<a href='$PHP_SELF?L+I$prev+Q" . urlencode($search)
		 ."'>Previous&nbsp;$PAGE_MAX</a>");
          print("</td><td align='right'>");
	  if ($end < $count)
	  {
	    $next_count = min($PAGE_MAX, $count - $end);
	    print("<a href='$PHP_SELF?L+I$next+Q" . urlencode($search)
		 ."'>Next&nbsp;$next_count</a>");
          }
          print("</td></tr>\n");
	  print("</table>\n");
        }

        print("<p><img src='images/private.gif' width='16' height='16' "
	     ."align='absmiddle' alt='private'> = hidden from public view</p>\n");
      }

      html_footer();
      break;

  case 'M' : // Modify Article
      if ($REQUEST_METHOD == "POST")
      {
        if ($LOGIN_LEVEL < AUTH_DEVEL)
	  $is_published = 0;
	else if (array_key_exists("IS_PUBLISHED", $_POST))
	  $is_published = (int)$_POST["IS_PUBLISHED"];
	else
	  $is_published = 0;

        if (array_key_exists("TITLE", $_POST))
	  $title = $_POST["TITLE"];
	else
	  $title = "";

        if (array_key_exists("ABSTRACT", $_POST))
	  $abstract = $_POST["ABSTRACT"];
	else
	  $abstract = "";

        if (array_key_exists("CONTENTS", $_POST))
	  $contents = $_POST["CONTENTS"];
	else
	  $contents = "";

        if (($is_published == 0 || $LOGIN_LEVEL >= AUTH_DEVEL) &&
	    $title != "" && $abstract != "" && $contents != "")
          $havedata = 1;
	else
          $havedata = 0;
      }
      else
      {
        $result = db_query("SELECT * FROM article WHERE id = $id");
	if (db_count($result) != 1)
	{
	  print("<p><b>Error:</b> Article #$id was not found!</p>\n");
	  html_footer();
	  exit();
	}

        $row = db_next($result);

        $is_published = $row["is_published"];
	$title        = $row["title"];
	$abstract     = $row["abstract"];
	$contents     = $row["contents"];

        db_free($row);

        $havedata = 0;
      }

      if ($havedata)
      {
	$title       = db_escape($title);
	$abstract    = db_escape($abstract);
	$contents    = db_escape($contents);
        $modify_date = time();

        db_query("UPDATE article SET "
	        ."is_published = $is_published, "
	        ."title = '$title', "
	        ."abstract = '$abstract', "
	        ."contents = '$contents', "
	        ."modify_date = $modify_date, "
	        ."modify_user = '$LOGIN_USER' "
	        ."WHERE id = $id");

        if (!$is_published)
	  notify_users($id, "modified");

	header("Location: $PHP_SELF?L$id$options");
      }
      else
      {
        html_header("Modify Article #$id");

	html_start_links(1);
	html_link("Return to Articles", "$PHP_SELF?L$options");
	html_link("Article #$id", "$PHP_SELF?L$id$options");
	html_end_links();

	if ($REQUEST_METHOD == "POST")
	{
	  print("<p><b>Error:</b> Please fill in the fields marked in "
	       ."<b><font color='red'>bold red</font></b> below and resubmit "
	       ."your article.</p>\n");

	  $hstart = "<font color='red'>";
	  $hend   = "</font>";
	}
	else
	{
	  $hstart = "";
	  $hend   = "";
	}

        print("<form method='post' action='$PHP_SELF?M$id$options'>"
	     ."<table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

	if ($LOGIN_LEVEL >= AUTH_DEVEL)
	{
          print("<tr><th align='right'>Published:</th><td>");
          select_is_published($is_published);
          print("</td></tr>\n");
	}
	else
          print("<input type='hidden' name='IS_PUBLISHED' value='0'>\n");

	$title = htmlspecialchars($title, ENT_QUOTES);

	if ($title == "")
	  print("<tr><th align='right'>${hstart}Title:${hend}</th>");
	else
	  print("<tr><th align='right'>Title:</th>");
	print("<td><input type='text' name='TITLE' "
	     ."size='80' value='$title'></td></tr>\n");

	$abstract = htmlspecialchars($abstract, ENT_QUOTES);

	if ($abstract == "")
	  print("<tr><th align='right'>${hstart}Abstract:${hend}</th>");
	else
	  print("<tr><th align='right'>Abstract:</th>");
	print("<td><input type='text' name='ABSTRACT' "
	     ."size='80' value='$abstract'></td></tr>\n");

	$contents = htmlspecialchars($contents, ENT_QUOTES);

	if ($contents == "")
	  print("<tr><th align='right' valign='top'>${hstart}Contents:${hend}</th>");
	else
	  print("<tr><th align='right' valign='top'>Contents:</th>");
	print("<td><textarea name='CONTENTS' "
	     ."cols='72' rows='12' wrap='virtual'>"
	     ."$contents</textarea>\n"
	     ."<p>The contents of the article may contain the following "
	     ."HTML elements: <tt>A</tt>, <tt>B</tt>, <tt>BLOCKQUOTE</tt>, "
	     ."<tt>CODE</tt>, <tt>EM</tt>, <tt>H1</tt>, <tt>H2</tt>, "
	     ."<tt>H3</tt>, <tt>H4</tt>, <tt>H5</tt>, <tt>H6</tt>, <tt>I</tt>, "
	     ."<tt>IMG</tt>, <tt>LI</tt>, <tt>OL</tt>, <tt>P</tt>, <tt>PRE</tt>, "
	     ."<tt>TT</tt>, <tt>U</tt>, <tt>UL</tt></p></td></tr>\n");

        print("<tr><th colspan='2'>"
	     ."<input type='submit' value='Motify Article'></th></tr>\n");
        print("</table></form>\n");

        html_footer();
      }
      break;

  case 'N' : // Post new Article
      if ($REQUEST_METHOD == "POST")
      {
        if ($LOGIN_LEVEL < AUTH_DEVEL)
	  $is_published = 0;
	else if (array_key_exists("IS_PUBLISHED", $_POST))
	  $is_published = (int)$_POST["IS_PUBLISHED"];
	else
	  $is_published = 0;

        if (array_key_exists("TITLE", $_POST))
	  $title = $_POST["TITLE"];
	else
	  $title = "";

        if (array_key_exists("ABSTRACT", $_POST))
	  $abstract = $_POST["ABSTRACT"];
	else
	  $abstract = "";

        if (array_key_exists("CONTENTS", $_POST))
	  $contents = $_POST["CONTENTS"];
	else
	  $contents = "";

        if ($LOGIN_USER != "" && $LOGIN_LEVEL < AUTH_DEVEL)
	  $create_user = $LOGIN_USER;
        else if (array_key_exists("CREATE_USER", $_POST))
	  $create_user = $_POST["CREATE_USER"];
	else
	  $create_user = "";

        if (($is_published == 0 || $LOGIN_LEVEL >= AUTH_DEVEL) &&
	    $title != "" && $abstract != "" && $contents != "" &&
	    $create_user != "")
          $havedata = 1;
	else
          $havedata = 0;
      }
      else
      {
        $is_published = 0;
	$title        = "";
	$abstract     = "";
	$contents     = "";

	if ($LOGIN_USER != "")
	  $create_user = $LOGIN_USER;
	else if (array_key_exists("FROM", $_COOKIE))
	  $create_user = $_COOKIE["FROM"];
	else
	  $create_user = "";

        $havedata = 0;
      }

      if ($havedata)
      {
	$title       = db_escape($title);
	$abstract    = db_escape($abstract);
	$contents    = db_escape($contents);
        $create_date = time();
	$create_user = db_escape($create_user);

        db_query("INSERT INTO article VALUES(NULL,"
	        ."$is_published,'$title','$abstract','$contents',"
		."$create_date,'$create_user',$create_date,'$create_user')");

	$id = db_insert_id();

        if (!$is_published)
	  notify_users($id);
	  
	header("Location: $PHP_SELF?L$id$options");
	break;
      }

      html_header("Submit Article");

      html_start_links(1);
      html_link("Return to Articles", "$PHP_SELF?L$options");
      html_end_links();

      if ($REQUEST_METHOD == "POST")
      {
	print("<p><b>Error:</b> Please fill in the fields marked in "
	     ."<b><font color='red'>bold red</font></b> below and resubmit "
	     ."your article.</p>\n");

	$hstart = "<font color='red'>";
	$hend   = "</font>";
      }
      else
      {
	print("<p>Please use this form to post announcements, how-to's, "
             ."examples, and case studies showing how you use $PROJECT_NAME. "
	     ."We will proofread your article, and if we determine it is "
	     ."appropriate for the site, we will make the article public "
	     ."on the site. <i>Thank you</i> for supporting $PROJECT_NAME!</p>\n");

	$hstart = "";
	$hend   = "";
      }

      print("<form method='post' action='$PHP_SELF?N$options'>"
	   ."<table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

      if ($LOGIN_LEVEL >= AUTH_DEVEL)
      {
        print("<tr><th align='right'>Published:</th><td>");
        select_is_published($is_published);
        print("</td></tr>\n");
      }
      else
        print("<input type='hidden' name='IS_PUBLISHED' value='0'>\n");

      $title = htmlspecialchars($title, ENT_QUOTES);

      if ($title == "")
	print("<tr><th align='right'>${hstart}Title:${hend}</th>");
      else
	print("<tr><th align='right'>Title:</th>");
      print("<td><input type='text' name='TITLE' "
	   ."size='80' value='$title'></td></tr>\n");

      $abstract = htmlspecialchars($abstract, ENT_QUOTES);

      if ($abstract == "")
	print("<tr><th align='right'>${hstart}Abstract:${hend}</th>");
      else
	print("<tr><th align='right'>Abstract:</th>");
      print("<td><input type='text' name='ABSTRACT' "
	   ."size='80' value='$abstract'></td></tr>\n");

      $create_user = htmlspecialchars($create_user, ENT_QUOTES);

      if ($create_user == "")
	print("<tr><th align='right'>${hstart}Author:${hend}</th>");
      else
	print("<tr><th align='right'>Author:</th>");

      if ($LOGIN_USER != "" && $LOGIN_LEVEL < AUTH_DEVEL)
	print("<td><input type='hidden' name='CREATE_USER' "
	     ."value='$create_user'>$create_user</td></tr>\n");
      else
	print("<td><input type='text' name='CREATE_USER' "
	     ."size='40' value='$create_user'></td></tr>\n");

      $contents = htmlspecialchars($contents, ENT_QUOTES);

      if ($contents == "")
	print("<tr><th align='right' valign='top'>${hstart}Contents:${hend}</th>");
      else
	print("<tr><th align='right' valign='top'>Contents:</th>");
      print("<td><textarea name='CONTENTS' "
	   ."cols='72' rows='12' wrap='virtual'>"
	   ."$contents</textarea>\n"
	   ."<p>The contents of the article may contain the following "
	   ."HTML elements: <tt>A</tt>, <tt>B</tt>, <tt>BLOCKQUOTE</tt>, "
	   ."<tt>CODE</tt>, <tt>EM</tt>, <tt>H1</tt>, <tt>H2</tt>, "
	   ."<tt>H3</tt>, <tt>H4</tt>, <tt>H5</tt>, <tt>H6</tt>, <tt>I</tt>, "
	   ."<tt>IMG</tt>, <tt>LI</tt>, <tt>OL</tt>, <tt>P</tt>, <tt>PRE</tt>, "
	   ."<tt>TT</tt>, <tt>U</tt>, <tt>UL</tt></p></td></tr>\n");

      print("<tr><th colspan='2'>"
	   ."<input type='submit' value='Submit Article'></th></tr>\n");
      print("</table></form>\n");

      html_footer();
      break;
}


//
// End of "$Id$".
//
?>
