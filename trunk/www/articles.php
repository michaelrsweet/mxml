<?php
//
// "$Id: articles.php,v 1.4 2004/05/18 21:26:52 mike Exp $"
//
// Web form for the article table...
//


//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";


//
// Maximum number of articles per page...
//

$ARTICLE_PAGE_MAX = 10;


// Get command-line options...
//
// Usage: article.php [operation] [options]
//
// Operations:
//
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

  if ($op != 'D' && $op != 'L' && $op != 'M' && $op != 'N')
  {
    html_header("Article Error");
    print("<p>Bad command '$op'!\n");
    html_footer();
    exit();
  }

  if (($op == 'D' || $op == 'M') && !$id)
  {
    html_header("Article Error");
    print("<p>Command '$op' requires an ID!\n");
    html_footer();
    exit();
  }

  if (($op == 'D' || $op == 'M') && $LOGIN_USER == "")
  {
    html_header("Article Error");
    print("<p>Command '$op' requires a login!\n");
    html_footer();
    exit();
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
	html_link("View Article #$id</A>", "$PHP_SELF?L$id$options");
	html_link("Modify Article #$id</A>", "$PHP_SELF?M$id$options");
	html_end_links();

        print("<h1>Delete Article #$id</h1>\n");
        print("<form method='post' action='$PHP_SELF?D$id'>"
	     ."<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

        if (!$row['is_published'])
	  print("<tr><th align='center' colspan='2'>This Article is "
	       ."currently hidden from public view.</td></tr>\n");

        $temp = htmlspecialchars($row["title"]);
        print("<tr><th align='right'>Title:</th><td class='left'>$temp</td></tr>\n");

        $temp = htmlspecialchars($row["abstract"]);
        print("<tr><th align='right'>Abstract:</th><td class='left'>$temp</td></tr>\n");

        $temp = htmlspecialchars($row["contents"]);
        print("<tr><th align='right'>Contents:</th><td class='left'>$temp</td></tr>\n");

        print("<tr><th colspan='2'>"
	     ."<input type='submit' value='Confirm Delete Article'></th></tr>\n");
        print("</table></p></form>\n");

        html_footer();
      }
      break;

  case 'L' : // List (all) Article(s)
      if ($id)
      {
        html_header("Article #$id");

        $result = db_query("SELECT * FROM article WHERE id = $id");
	if (db_count($result) != 1)
	{
	  print("<p><b>Error:</b> Article #$id was not found!</p>\n");
	  html_footer();
	  exit();
	}

        $row = db_next($result);

	html_start_links(1);
	html_link("Return to Articles", "$PHP_SELF?L$options");
	html_link("Show Comments", "#_USER_COMMENTS");
	if ($LOGIN_USER)
	{
	  html_link("Modify Article</A>", "$PHP_SELF?M$id$options");
	  html_link("Delete Article #$id</A>", "$PHP_SELF?D$id$options");
	}
	html_end_links();

        print("<h1>Article #$id</h1>\n");
        print("<p><table width='100%' cellpadding='5' cellspacing='0' "
	     ."border='0'>\n");

        if (!$row['is_published'])
	  print("<tr><th align='center' colspan='2'>This Article is "
	       ."currently hidden from public view.</td></tr>\n");

        $temp = htmlspecialchars($row['title']);
        print("<tr><th align='right' valign='top'>Title:</th><td class='left'>$temp</td></tr>\n");

        $temp = htmlspecialchars($row['abstract']);
        print("<tr><th align='right' valign='top'>Abstract:</th><td class='left'>$temp</td></tr>\n");

        $temp = format_text($row['contents']);
        print("<tr><th align='right' valign='top'>Contents:</th><td class='left'>$temp</td></tr>\n");

        print("</table></p>\n");

        db_free($result);

        print("<hr noshade/>\n"
	     ."<h2><a name='_USER_COMMENTS'>Comments</a> "
	     ."[&nbsp;<a href='comment.php?r0+particles.php_L$id'>"
	     ."Add&nbsp;Comment</a>&nbsp;]</h2>\n");

	show_comments("articles.php_L$id");
      }
      else
      {
        html_header("Articles");

	html_start_links(1);
	html_link("Post New Article", "$PHP_SELF?N$options");
	html_end_links();

        print("<h1>Articles</h1>\n");

        print("<form method='POST' action='$PHP_SELF'><p align='center'>"
	     ."Search&nbsp;Words: &nbsp;<input type='text' size='60' "
	     ."name='SEARCH' value='$search'>"
	     ."<input type='submit' value='Search Articles'></p></form>\n");

	print("<hr noshade/>\n");

        $query = "";
	$prefix = "WHERE ";

        if (!$LOGIN_USER)
	{
	  $query .= "${prefix}is_published = 1";
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
	      $query  .= "$prefix$logic (title LIKE \"%$keyword%\""
	                ." OR abstract LIKE \"%$keyword%\""
	                ." OR contents LIKE \"%$keyword%\")";
	      $prefix = $next;
	      $logic  = '';
	    }
          }

	  $query  .= ")";
	}

        $result = db_query("SELECT * FROM article $query "
	                  ."ORDER BY modify_date");
        $count  = db_count($result);

        if ($count == 0)
	{
	  print("<p>No Articles found.</p>\n");

	  html_footer();
	  exit();
	}

        if ($index >= $count)
	  $index = $count - ($count % $ARTICLE_PAGE_MAX);
	if ($index < 0)
	  $index = 0;

        $start = $index + 1;
        $end   = $index + $ARTICLE_PAGE_MAX;
	if ($end > $count)
	  $end = $count;

        $prev = $index - $ARTICLE_PAGE_MAX;
	if ($prev < 0)
	  $prev = 0;
	$next = $index + $ARTICLE_PAGE_MAX;

        print("<p>$count article(s) found, showing $start to $end:</p>\n");

        if ($count > $ARTICLE_PAGE_MAX)
	{
          print("<p><table border='0' cellspacing='0' cellpadding='0' "
	       ."width='100%'>\n");

          print("<tr><td>");
	  if ($index > 0)
	    print("[&nbsp;<a href='$PHP_SELF?L+I$prev+Q" . urlencode($search)
		 ."'>Previous&nbsp;$ARTICLE_PAGE_MAX</a>&nbsp;]");
          print("</td><td align='right'>");
	  if ($end < $count)
	  {
	    $next_count = min($ARTICLE_PAGE_MAX, $count - $end);
	    print("[&nbsp;<a href='$PHP_SELF?L+I$next+Q" . urlencode($search)
		 ."'>Next&nbsp;$next_count</a>&nbsp;]");
          }
          print("</td></tr>\n");
	  print("</table></p>\n");
        }

        html_start_table(array("ID","Title","Last Modified", "Comment(s)"));

	db_seek($result, $index);
	for ($i = 0; $i < $ARTICLE_PAGE_MAX && $row = db_next($result); $i ++)
	{
          html_start_row();

          $id = $row['id'];

          print("<td align='center'><a href='$PHP_SELF?L$id$options' "
	       ."alt='Article #$id'>"
	       ."$id</a></td>");

          $temp = htmlspecialchars($row['title']);
          print("<td align='center' width='67%'><a href='$PHP_SELF?L$id$options' "
	       ."alt='Article #$id'>"
	       ."$temp</a></td>");

          $temp = date("M d, Y", $row['modify_date']);
          print("<td align='center'><a href='$PHP_SELF?L$id$options' "
	       ."alt='Article #$id'>"
	       ."$temp</a></td>");

          $count = count_comments("articles.php_L$id");
          print("<td align='center'><a href='$PHP_SELF?L$id$options' "
	       ."alt='Article #$id'>"
	       ."$count</a></td>");

          html_end_row();

          html_start_row();
          $temp = htmlspecialchars($row['abstract']);
          print("<td></td><td colspan='3'>$temp</td>");
          html_end_row();
	}

        html_end_table();

        if ($count > $ARTICLE_PAGE_MAX)
	{
          print("<p><table border='0' cellspacing='0' cellpadding='0' "
	       ."width='100%'>\n");

          print("<tr><td>");
	  if ($index > 0)
	    print("[&nbsp;<a href='$PHP_SELF?L+I$prev+Q" . urlencode($search)
		 ."'>Previous&nbsp;$ARTICLE_PAGE_MAX</a>&nbsp;]");
          print("</td><td align='right'>");
	  if ($end < $count)
	  {
	    $next_count = min($ARTICLE_PAGE_MAX, $count - $end);
	    print("[&nbsp;<a href='$PHP_SELF?L+I$next+Q" . urlencode($search)
		 ."'>Next&nbsp;$next_count</a>&nbsp;]");
          }
          print("</td></tr>\n");
	  print("</table></p>\n");
        }
      }

      html_footer();
      break;

  case 'M' : // Modify Article
      if ($REQUEST_METHOD == "POST")
      {
        $date = time();
	$is_published = db_escape($_POST["IS_PUBLISHED"]);
	$title = db_escape($_POST["TITLE"]);
	$abstract = db_escape($_POST["ABSTRACT"]);
	$contents = db_escape($_POST["CONTENTS"]);

        db_query("UPDATE article SET "
	        ."is_published = $is_published, "
	        ."title = '$title', "
	        ."abstract = '$abstract', "
	        ."contents = '$contents', "
	        ."modify_date = $date, "
	        ."modify_user = '$LOGIN_USER' "
	        ."WHERE id = $id");

	header("Location: $PHP_SELF?L$id$options");
      }
      else
      {
        html_header("Modify Article #$id");

	html_start_links(1);
	html_link("Return to Articles", "$PHP_SELF?L$options");
	html_link("Article #$id", "$PHP_SELF?L$id$options");
	html_end_links();

        print("<h1>Modify Article #$id</h1>\n");
        $result = db_query("SELECT * FROM article WHERE id = $id");
	if (db_count($result) != 1)
	{
	  print("<p><b>Error:</b> Article #$id was not found!</p>\n");
	  html_footer();
	  exit();
	}

        $row = db_next($result);

        print("<form method='post' action='$PHP_SELF?M$id$options'>"
	     ."<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

        print("<tr><th align='right'>Published:</th><td>");
	select_is_published($row['is_published']);
	print("</td></tr>\n");

        $temp = htmlspecialchars($row['title'], ENT_QUOTES);
        print("<tr><th align='right'>Title:</th>"
	     ."<td><input type='text' name='TITLE' "
	     ."value='$temp' size='40'></td></tr>\n");

        $temp = htmlspecialchars($row['abstract'], ENT_QUOTES);
        print("<tr><th align='right'>Abstract:</th>"
	     ."<td><input type='text' name='ABSTRACT' "
	     ."value='$temp' size='40'></td></tr>\n");

        $temp = htmlspecialchars($row['contents'], ENT_QUOTES);
        print("<tr><th align='right'>Contents:</th>"
	     ."<td><textarea name='CONTENTS' "
	     ."cols='80' rows='10' wrap='virtual'>"
	     ."$temp</textarea></td></tr>\n");

        print("<tr><th colspan='2'>"
	     ."<input type='submit' value='Update Article'></th></tr>\n");
        print("</table></p></form>\n");

        html_footer();
      }
      break;

  case 'N' : // Post new Article
      if ($REQUEST_METHOD == "POST")
      {
        $date         = time();
	$is_published = db_escape($_POST["IS_PUBLISHED"]);
	$title = db_escape($_POST["TITLE"]);
	$abstract = db_escape($_POST["ABSTRACT"]);
	$contents = db_escape($_POST["CONTENTS"]);

        db_query("INSERT INTO article VALUES(NULL,"
	        ."$is_published,"
	        ."'$title',"
	        ."'$abstract',"
	        ."'$contents',"
		."$date,'$LOGIN_USER',$date,'$LOGIN_USER')");

	$id = db_insert_id();

	header("Location: $PHP_SELF?L$id$options");
	break;
      }

      html_header("Post New Article");

      html_start_links(1);
      html_link("Return to Articles", "$PHP_SELF?L$options");
      html_end_links();

      print("<h1>Post New Article</h1>\n");
      print("<form method='post' action='$PHP_SELF?N$options'>"
	   ."<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

      if ($LOGIN_USER != "")
      {
        print("<tr><th align='right'>Published:</th><td>");
        select_is_published();
        print("</td></tr>\n");
      }
      else
        print("<input type='hidden' name='IS_PUBLISHED' value='0'/>\n");

      print("<tr><th align='right'>Title:</th>"
	   ."<td><input type='text' name='TITLE' "
	   ."size='40'></td></tr>\n");

      print("<tr><th align='right'>Abstract:</th>"
	   ."<td><input type='text' name='ABSTRACT' "
	   ."size='40'></td></tr>\n");

      print("<tr><th align='right'>Contents:</th>"
	   ."<td><textarea name='CONTENTS' "
	   ."cols='80' rows='10' wrap='virtual'>"
	   ."</textarea></td></tr>\n");

      print("<tr><th colspan='2'>"
	   ."<input type='submit' value='Create Article'></th></tr>\n");
      print("</table></p></form>\n");

      html_footer();
      break;
}


//
// End of "$Id: articles.php,v 1.4 2004/05/18 21:26:52 mike Exp $".
//
?>
