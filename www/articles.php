<?php
//
// "$Id: articles.php,v 1.2 2004/05/18 12:02:02 mike Exp $"
//
// Web form for the article table...
//


//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";


// Get command-line options...
//
// Usage: article.php [operation]
//
// Operations:
//
// D#        - Delete Article
// L         = List all 
// L#        = List Article #
// M#        = Modify Article #
// N         = Create new Article


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

  if ($op == 'N' && $id)
  {
    html_header("Article Error");
    print("<p>Command '$op' may not have an ID!\n");
    html_footer();
    exit();
  }
}
else
{
  $op = 'L';
  $id = 0;
}

switch ($op)
{
  case 'D' : // Delete Article
      if ($REQUEST_METHOD == "POST")
      {
        db_query("DELETE FROM article WHERE id = $id");

        header("Location: $PHP_SELF?L");
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
	html_link("Return to Article List", "$PHP_SELF?L");
	html_link("View Article #$id</A>", "$PHP_SELF?L$id");
	html_link("Modify Article #$id</A>", "$PHP_SELF?M$id");
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
	html_link("Return to Article List", "$PHP_SELF?L");
	html_link("Modify Article</A>", "$PHP_SELF?M$id");
	html_link("Delete Article #$id</A>", "$PHP_SELF?D$id");
	html_end_links();

        print("<h1>Article #$id</h1>\n");
        print("<p><table width='100%' cellpadding='5' cellspacing='0' "
	     ."border='0'>\n");

        if (!$row['is_published'])
	  print("<tr><th align='center' colspan='2'>This Article is "
	       ."currently hidden from public view.</td></tr>\n");

        $temp = htmlspecialchars($row['title']);
        print("<tr><th align='right'>Title:</th><td class='left'>$temp</td></tr>\n");

        $temp = htmlspecialchars($row['abstract']);
        print("<tr><th align='right'>Abstract:</th><td class='left'>$temp</td></tr>\n");

        $temp = htmlspecialchars($row['contents']);
        print("<tr><th align='right'>Contents:</th><td class='left'>$temp</td></tr>\n");

        print("</table></p>\n");
        db_free($result);
      }
      else
      {
        html_header("Article List");

	html_start_links(1);
	html_link("New Article", "$PHP_SELF?N");
	html_end_links();

        $result = db_query("SELECT * FROM article");
        $count  = db_count($result);

        print("<h1>Article List</h1>\n");
        if ($count == 0)
	{
	  print("<p>No Articles found.</p>\n");

	  html_footer();
	  exit();
	}

        html_start_table(array("Title","Abstract","Contents"));

	while ($row = db_next($result))
	{
          html_start_row();

          $id = $row['id'];

          $temp = htmlspecialchars($row['title']);
          print("<td class='center'><a href='$PHP_SELF?L$id' "
	       ."alt='Article #$id'>"
	       ."$temp</a></td>");

          $temp = htmlspecialchars($row['abstract']);
          print("<td class='center'><a href='$PHP_SELF?L$id' "
	       ."alt='Article #$id'>"
	       ."$temp</a></td>");

          $temp = htmlspecialchars($row['contents']);
          print("<td class='center'><a href='$PHP_SELF?L$id' "
	       ."alt='Article #$id'>"
	       ."$temp</a></td>");

          html_end_row();
	}

        html_end_table();
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

	header("Location: $PHP_SELF?L$id");
      }
      else
      {
        html_header("Modify Article #$id");

	html_start_links(1);
	html_link("Return to Article List", "$PHP_SELF?L");
	html_link("Article #$id", "$PHP_SELF?L$id");
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

        print("<form method='post' action='$PHP_SELF?M$id'>"
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

	header("Location: $PHP_SELF?L$id");
	break;
      }

      html_header("New Article");

      html_start_links(1);
      html_link("Return to Article List", "$PHP_SELF?L");
      html_end_links();

      print("<h1>New Article</h1>\n");
      print("<form method='post' action='$PHP_SELF?N'>"
	   ."<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\n");

      print("<tr><th align='right'>Published:</th><td>");
      select_is_published();
      print("</td></tr>\n");

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
// End of "$Id: articles.php,v 1.2 2004/05/18 12:02:02 mike Exp $".
//
?>
