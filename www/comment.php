<?php
//
// "$Id: comment.php,v 1.4 2004/05/19 03:26:36 mike Exp $"
//
// Comment and moderation interface for PHP pages...
//

//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";


//
// Parse arguments...
//

$op       = "";
$link     = "";
$path     = "";
$refer_id = 0;
$id       = 0;

for ($i = 0; $i < $argc; $i ++)
{
  switch ($argv[$i][0])
  {
    case 'L' : // List all comments...
    case 'l' : // List unapproved comments...
        $op       = $argv[$i][0];
	$listpath = substr($argv[$i], 1);
	break;

    case 'r' : // Respond/add comment
        $op       = "r";
	$refer_id = (int)substr($argv[$i], 1);
	break;

    case 'd' : // Delete comment
    case 'D' : // Delete comment (confirmed)
    case 'e' : // Edit comment
        $op = $argv[$i][0];
	$id = (int)substr($argv[$i], 1);
	break;

    case 'm' : // Moderate comment
        $op  = "m";
	$dir = $argv[$i][1];
	$id  = (int)substr($argv[$i], 2);
	break;

    case 'p' : // Set path
        $path = substr($argv[$i], 1);
	break;
  }
}

if ($op == "" || ($path == "" && $op != "l" && $op != "L") ||
    (($op == 'd' || $op == 'D' || $op == 'l') && !$LOGIN_USER))
{
  header("Location: index.php");
}
else
{
  switch ($op)
  {
    case 'd' : // Delete comment
        html_header("Delete Comment #$id");
	print("<h1>Delete Comment #$id</h1>\n");
	print("<p>Click the button below to confirm the deletion.</p>\n"
	     ."<form method='POST' action='$PHP_SELF?D$id+p$path'>"
	     ."<center><input type='submit' value='Delete Comment'/></center>"
	     ."</form>\n");
	html_footer();
	break;

    case 'D' : // Delete comment (confirmed)
        db_query("DELETE FROM comment WHERE id = $id");
	header("Location: $PHP_SELF");
	break;

    case 'e' : // Edit comment
    case 'r' : // New comment
        $havedata = 0;

        if ($REQUEST_METHOD == "POST")
	{
	  if (array_key_exists("AUTHOR", $_POST))
            $create_user = trim($_POST["AUTHOR"]);
	  else
	    $create_user = "";

	  if (array_key_exists("FILE", $_POST))
            $file = $_POST["FILE"];
	  else
	    $file = "";

	  if (array_key_exists("STATUS", $_POST))
	    $status = (int)$_POST["STATUS"];
	  else
	    $status = 2;

	  if (array_key_exists("MESSAGE", $_POST))
	    $contents = trim($_POST["MESSAGE"]);
	  else
	    $contents = "";

          if ($create_user != "" && $contents != "" && $file != "")
	    $havedata = 1;

          if ($create_user != "" && $id == 0 && !$LOGIN_USER)
            setcookie("FROM", $create_user, time() + 90 * 86400, "/");
	}
	else
	{
          if ($id)
	  {
	    $result = db_query("SELECT * FROM comment WHERE id = $id");
	    if (db_count($result) > 0)
	    {
	      $row         = db_next($result);
              $create_user = $row['create_user'];
	      $contents    = $row['contents'];
              $status      = $row['status'];
	    }
	    else
	    {
	      if (array_key_exists("FROM", $_COOKIE))
        	$create_user = $_COOKIE["FROM"];
	      else
		$create_user = "Anonymous <anonymous@easysw.com>";

	      $contents = "";
	      $status   = 2;
	    }

	    db_free($result);
	  }
	  else
	  {
	    if (array_key_exists("FROM", $_COOKIE))
              $create_user = $_COOKIE["FROM"];
	    else
	      $create_user = "Anonymous <anonymous@easysw.com>";

	    $contents = "";
	    $status   = 2;
	  }
	}

        if ($havedata)
	{
          $create_user = db_escape($create_user);
          $file        = db_escape($file);
	  $contents    = db_escape($contents);

          if ($id)
	  {
	    // Update existing record.
	    db_query("UPDATE comment SET create_user='$create_user',file='$file',"
	            ."status=$status,contents='$contents' WHERE id = $id");
	  }
	  else
	  {
	    // Add new record.
	    $create_date = time();
	    db_query("INSERT INTO comment VALUES(NULL,$refer_id,2,'$file',"
	            ."'$contents',$create_date,'$create_user')");
            $id = db_insert_id();
	  }

          $location = str_replace("_", "?", $path);
	  header("Location: $location#_USER_COMMENT_$id");
        }
	else
	{
          if ($id)
	  {
            html_header("Edit Comment");
	    print("<h1>Edit Comment</h1>\n");
	  }
	  else
	  {
            html_header("Add Comment");
	    print("<h1>Add Comment</h1>\n");
	  }

          if ($REQUEST_METHOD == "POST")
	  {
	    print("<p>Your comment posting is missing required information. "
	         ."Please fill in all fields marked in "
		 ."<font color='red'>red</font> and resubmit your comments.</p>\n");
	    $hstart = "<font color='red'>";
	    $hend   = "</font>";
	  }
	  else
	  {
	    $hstart = "";
	    $hend   = "";
	  }

          if ($op == "e")
            print("<form method='POST' action='$PHP_SELF?e$id+p$path'>\n"
		 ."<center><table border='0'>\n");
	  else
            print("<form method='POST' action='$PHP_SELF?r$refer_id+p$path'>\n"
		 ."<center><table border='0'>\n");

          $create_user = htmlspecialchars($create_user);
	  if ($create_user == "")
	    print("<tr><th align='right'>${hstart}Author:${hend}</th>"
		 ."<td><input type='text' name='AUTHOR' value='$create_user' "
		 ."size='40'/></td></tr>\n");
	  else
	    print("<tr><th align='right'>Author:</th>"
		 ."<td><input type='text' name='AUTHOR' value='$create_user' "
		 ."size='40'/></td></tr>\n");

          $contents = htmlspecialchars($contents);
	  if ($contents == "")
	    print("<tr><th align='right' valign='top'>${hstart}Message:${hend}</th>"
	         ."<td><textarea name='MESSAGE' cols='70' rows='8' "
		 ."wrap='virtual'>$contents</textarea></td></tr>\n");
	  else
	    print("<tr><th align='right' valign='top'>Message:</th>"
	         ."<td><textarea name='MESSAGE' cols='70' rows='8' "
		 ."wrap='virtual'>$contents</textarea></td></tr>\n");

          if ($LOGIN_USER)
	  {
	    print("<tr><th align='right'>File Path:</th>"
		 ."<td><input type='text' name='FILE' value='$path' "
		 ."size='40'/></td></tr>\n");
	    print("<tr><th align='right'>Score:</th>"
		 ."<td><select name='STATUS'>");
            for ($i = 0; $i <= 5; $i ++)
	      if ($i == $status)
		print("<option value='$i' selected>$i</option>");
	      else
		print("<option value='$i'>$i</option>");
	    print("</select></td></tr>\n");
          }
	  else
	  {
	    print("<input type='hidden' name='FILE' value='$path'/>\n");
	    print("<input type='hidden' name='STATUS' value='2'/>\n");
	  }

          if ($id)
            print("<tr><th></th><td><input type='submit' value='Update'/></td></tr>\n");
          else
            print("<tr><th></th><td><input type='submit' value='Add'/></td></tr>\n");

          print("</table></center>\n"
	       ."</form>\n");

          html_footer();
	}
        break;

    case 'L' : // List all comments...
    case 'l' : // List unapproved comments...
        html_header("Comments");
	print("<h1>Comments</h1>\n");

        if (!$LOGIN_USER)
	{
	  $result = db_query("SELECT * FROM comment WHERE status = 1 AND "
	                    ."url LIKE '${listpath}%' ORDER BY id");
        }
	else
	{
          if ($op == 'L')
	  {
	    $result = db_query("SELECT * FROM comment WHERE "
	                      ."url LIKE '${listpath}%' ORDER BY id");
	    print("<p>[ <a href='$PHP_SELF?l'>Show Hidden Comments</a> ]</p>\n");
	  }
          else
	  {
	    $result = db_query("SELECT * FROM comment WHERE status = 0 AND "
                              ."url LIKE '${listpath}%' ORDER BY id");
	    print("<p>[ <a href='$PHP_SELF?L'>Show All Comments</a> ]</p>\n");
	  }
        }

	if (db_count($result) == 0)
	{
	  if ($LOGIN_USER && $op == 'l')
            print("<p>No hidden comments.</p>\n");
	  else
            print("<p>No visible comments.</p>\n");
	}
	else
	{
          print("<ul>\n");

          while ($row = db_next($result))
	  {
	    $create_date  = date("M d, Y", $row['date']);
	    $create_user  = sanitize_email($row['create_user']);
	    $contents     = sanitize_text($row['contents']);
            $location     = str_replace("_", "?", $row['url']);

	    print("<li><a href='${link}$location'>$row[url]</a> "
	         ." by $create_user on $create_date "
	         ."[ <a href='$PHP_SELF?e$row[id]+p$row[url]'>Edit</a> "
	         ."| <a href='$PHP_SELF?d$row[id]+p$row[url]'>Delete</a> "
		 ."]<br /><tt>$contents</tt></li>\n");
	  }

          print("</ul>\n");
        }

        db_free($result);

        html_footer();
	break;

    case 'm' : // Moderate
        if (array_key_exists("MODPOINTS", $_COOKIE))
  	  $modpoints = $_COOKIE["MODPOINTS"];
	else
	  $modpoints = 5;

	if ($modpoints > 0)
	{
	  $modpoints --;
	  
          setcookie("MODPOINTS", $modpoints, time() + 2 * 86400, "/");

          $result = db_query("SELECT status FROM comment WHERE id=$id");
	  $row    = db_next($result);

          if ($dir == 'd')
	  {
	    // Moderate down...
	    if ($row['status'] > 0)
	      db_query("UPDATE comment SET status = status - 1 WHERE id=$id");
	  }
	  else
	  {
	    // Moderate down...
	    if ($row['status'] < 5)
	      db_query("UPDATE comment SET status = status + 1 WHERE id=$id");
	  }

	  db_free($result);
        }

        $location = str_replace("_", "?", $path);
	header("Location: $location#_USER_COMMENT_$id");
	break;      
  }
}

//
// End of "$Id: comment.php,v 1.4 2004/05/19 03:26:36 mike Exp $".
//
?>
