<?
//
// "$Id: links.php,v 1.1 2004/05/20 12:31:54 mike Exp $"
//
// Hierarchical link interface.
//
// Contents:
//
//


//
// Include necessary headers...
//

include "data/html.php";
include "data/common.php";


//
// 'get_category()' - Get the category path.
//

function				// O - Category path
get_category($id,			// I - Category ID
             $with_links = 2)		// I - 0 = no links, 1 = all links, 2 = all but root
{
  global $PHP_SELF;


  if ($id == 0)
  {
    if ($with_links == 1)
      return "<a href='$PHP_SELF?L+P0'>Root</a>";
    else
      return "Root";
  }
  else if ($id < 0)
  {
    return "All";
  }

  $result   = db_query("SELECT name, id, parent_id FROM link WHERE id = $id");
  $category = "";

  if ($result)
  {
    $row = db_next($result);

    if ($row)
    {
      if ($with_links || $row['parent_id'] > 0)
        $category = get_category($row['parent_id'], 1) . "/";

      if ($with_links == 1)
        $category .= "<a href='$PHP_SELF?L+P$row[id]'>"
	           . htmlspecialchars($row[name]) . "</a>";
      else
        $category .= htmlspecialchars($row['name']);
    }

    db_free($result);
  }

  return ($category);
}


//
// 'select_category()' - Get a list of all categories.
//

function
select_category($parent_id = 0,		// I - Parent ID
                $is_category = 0)	// I - Selecting for category?
{
  // Scan the table for categories... We add "C" to the ID to
  // avoid PHP thinking we want an actual index in the array.
  $result = db_query("SELECT name,id FROM link "
                    ."WHERE is_published != 0 AND is_category != 0 "
		    ."ORDER BY name");

  $cats = array();

  while ($row = db_next($result))
    $cats["C$row[id]"] = get_category($row['id'], 0);

  db_free($result);

  // Add the Root category if we are adding or modifying a category.
  if ($is_category)
    $cats["C0"] = "Root";

  // Sort the category list...
  asort($cats);

  // List the categories for selection...
  print("<select name='PARENT_ID'>");

  reset($cats);
  while (list($cat_id, $cat_name) = each($cats))
  {
    $cat_id   = (int)substr($cat_id, 1);
    $cat_name = htmlspecialchars($cat_name);

    if ($cat_id == $parent_id)
      print("<option value='$cat_id' selected>$cat_name</option>");
    else
      print("<option value='$cat_id'>$cat_name</option>");
  }

  print("</select>");
}


// Set globals...
$id        = 0;
$parent_id = 0;
$query     = '';

if ($LOGIN_LEVEL >= AUTH_DEVEL)
{
  $op = 'Z';
}
else
{
  $op = 'L';
}

// Check command-line...
$redirect = 0;

for ($i = 0; $i < $argc; $i ++)
{
  switch ($argv[$i][0])
  {
    case 'F' : // Form
    case 'U' : // Update/add
        $op   = $argv[$i][0];
	$type = $argv[$i][1];
	$id   = (int)substr($argv[$i], 2);
	break;

    case 'L' : // List or search
        $op = 'L';
	if (strlen($argv[$i]) > 1 && $argv[$i][1] == 'A')
	  $parent_id = -1;
	break;

    case 'P' : // Parent
	$parent_id = (int)substr($argv[$i], 1);
	break;

    case 'V' : // View
        $op = 'V';
	$id = (int)substr($argv[$i], 1);
	break;

    case 'X' : // Delete
        $op = 'X';
	$id = (int)substr($argv[$i], 1);
	break;

    case 'Z' : // List new entries
        $op = 'Z';
	break;

    case 'r' : // Rate
        $op       = $argv[$i][0];
	$id       = (int)substr($argv[$i], 1);
	$redirect = 1;
	break;

    case 'S' : // Show web or download page
        if (strncmp($argv[$i], "SEARCH", 6))
	{
	  // Don't treat SEARCH as a show command...
          $op       = $argv[$i][0];
	  $type     = $argv[$i][1];
	  $id       = (int)substr($argv[$i], 2);
	  $redirect = 1;
	}
	break;

    default :
        header("Location: $PHP_SELF");
	exit();
  }
}

// Check for form search data...
if (array_key_exists("SEARCH", $_GET))
  $SEARCH = $_GET["SEARCH"];
else if (array_key_exists("SEARCH", $_POST))
  $SEARCH = $_POST["SEARCH"];
else
  $SEARCH = "";

if (!$redirect)
{
  html_header("Links");
  print("<h1>Links</h1>\n");
  print("<form method='GET' action='$PHP_SELF'>\n"
       ."<center>"
       ."<input type='text' name='SEARCH' size='40' value='$SEARCH'/>"
       ."<input type='submit' value='Search'/>"
       ."</center>\n"
       ."</form>\n"
       ."<hr noshade/>\n");
}

if ($SEARCH)
{
  // Yes, construct a query...
  $op            = 'L';
  $search_string = $SEARCH;
  $search_string = str_replace("'", " ", $search_string);
  $search_string = str_replace("\"", " ", $search_string);
  $search_string = str_replace("\\", " ", $search_string);
  $search_string = str_replace("%20", " ", $search_string);
  $search_string = str_replace("%27", " ", $search_string);
  $search_string = str_replace("  ", " ", $search_string);
  $search_words  = explode(' ', $search_string);

  // Loop through the array of words, adding them to the 
  $prefix = "";
  $next   = "OR";

  reset($search_words);
  while ($keyword = current($search_words))
  {
    next($search_words);
    $keyword = ltrim(rtrim($keyword));

    if (strcasecmp($keyword, 'or') == 0)
    {
      $next = 'OR';
      if ($prefix != '')
        $prefix = 'OR';
    }
    else if (strcasecmp($keyword, 'and') == 0)
    {
      $next = 'AND';
      if ($prefix != '')
        $prefix = 'AND';
    }
    else
    {
      $query  = "$query $prefix name LIKE '%$keyword%'";
      $prefix = $next;
    }
  }
}

switch ($op)
{
  case 'F' : // Form...
      if ($type == 'C')
        $typename = 'Category';
      else
        $typename = 'Listing';

      if ($id > 0)
        $opname = 'Update';
      else
        $opname = 'Add';

      print("<h2>$opname $typename</h2>\n");

      if ($id > 0)
      {
        $result = db_query("SELECT * FROM link WHERE id = $id");
	$row    = db_next($result);

        $parent_id      = $row['parent_id'];
	$is_category    = $row['is_category'];
	$is_published   = $row['is_published'];
	$name           = htmlspecialchars($row['name'], ENT_QUOTES);
	$version        = htmlspecialchars($row['version'], ENT_QUOTES);
	$license        = htmlspecialchars($row['license'], ENT_QUOTES);
	$author         = htmlspecialchars($row['author'], ENT_QUOTES);
	$email          = htmlspecialchars($row['email'], ENT_QUOTES);
	$homepage       = htmlspecialchars($row['homepage'], ENT_QUOTES);
	$download       = htmlspecialchars($row['download'], ENT_QUOTES);
	$description    = htmlspecialchars($row['description'], ENT_QUOTES);
	$create_date    = $row['create_date'];
	$modify_date    = $row['modify_date'];

        db_free($result);
      }
      else
      {
        if ($type == 'C')
	  $is_category = 1;
	else
	  $is_category = 0;

	$is_published   = 0;
	$name           = "";
	$version        = "";
	$license        = "";
	$author         = "";
	$owner_email    = "";
	$owner_password = "";
	$email          = "";
	$homepage       = "http://";
	$download       = "ftp://";
	$description    = "";
	$create_date    = time();
	$modify_date    = time();
      }

      print("<form method='POST' action='$PHP_SELF?U$type$id+P$parent_id'>\n"
           ."<center><table border='0'>\n");

      if ($LOGIN_LEVEL >= AUTH_DEVEL)
      {
	print("<tr><th align='right'>Published:</th><td>");
	select_is_published($is_published);
	print("</td></tr>\n");
      }
      else
      {
        print("<input type='hidden' name='IS_PUBLISHED' "
	     ."value='$is_published'>\n");
      }

      print("<tr>"
           ."<th align='right'>Name:</th>"
	   ."<td><input type='text' name='NAME' value='$name' size='40'></td>"
	   ."</tr>\n");

      print("<tr><th align='right'>Category:</th><td>");
      select_category($parent_id, $is_category);
      print("</td></tr>\n");

      if (!$is_category)
      {
	print("<tr>"
             ."<th align='right'>Version:</th>"
	     ."<td><input type='text' name='VERSION' value='$version' "
	     ."size='32'></td>"
	     ."</tr>\n");

	print("<tr>"
             ."<th align='right'>License:</th>"
	     ."<td><input type='text' name='LICENSE' value='$license' "
	     ."size='32'></td>"
	     ."</tr>\n");

	print("<tr>"
             ."<th align='right'>Author:</th>"
	     ."<td><input type='text' name='AUTHOR' value='$author' "
	     ."size='32'></td>"
	     ."</tr>\n");

	print("<tr>"
             ."<th align='right'>EMail:</th>"
	     ."<td><input type='text' name='EMAIL' value='$email' "
	     ."size='40'></td>"
	     ."</tr>\n");

	print("<tr>"
             ."<th align='right'>Home Page URL:</th>"
	     ."<td><input type='text' name='HOMEPAGE' value='$homepage' "
	     ."size='40'></td>"
	     ."</tr>\n");

	print("<tr>"
             ."<th align='right'>Download URL:</th>"
	     ."<td><input type='text' name='DOWNLOAD' value='$download' "
	     ."size='40'></td>"
	     ."</tr>\n");

	print("<tr>"
             ."<th align='right' valign='top'>Description:</th>"
	     ."<td><textarea name='DESCRIPTION' wrap='virtual' cols='60' rows='10'>"
	     ."$description</textarea></td>"
	     ."</tr>\n");
      }

      print("<tr>"
           ."<th align='right' valign='top'>Announcment:</th>"
           ."<td><textarea name='NEWS' wrap='virtual' cols='60' rows='10'>"
	   ."</textarea></td>"
	   ."</tr>\n");
      print("<tr>"
           ."<th></th>"
	   ."<td><input type='submit' value='$opname $typename'></td>"
	   ."</tr>\n");

      print("</table></center>\n");
      print("</form>");
      break;

  case 'L' : // List...
      print("<p>[&nbsp;<a href='$PHP_SELF?LA'>Show&nbsp;All&nbsp;Listings</a> | "
	   ."<a href='$PHP_SELF?LC'>Show&nbsp;Listings&nbsp;by&nbsp;"
	   ."Category</a>&nbsp;]</p>\n");

      if ($SEARCH == "")
        $category = get_category($parent_id);
      else
        $category = "Search";

      // Show the categories...
      if ($query != "")
        $result = db_query("SELECT * FROM link "
                             ."WHERE is_published = 1 AND is_category = 1 AND "
			     ."($query) "
			     ."ORDER BY name");
      else if ($parent_id >= 0)
        $result = db_query("SELECT * FROM link "
                             ."WHERE is_published = 1 AND is_category = 1 AND "
			     ."parent_id = $parent_id "
			     ."ORDER BY name");
      else
        $result = db_query("SELECT * FROM link "
                             ."WHERE is_published = 1 AND is_category = 1 "
			     ."ORDER BY name");

      if ($parent_id < 0)
      {
	print("<h2>All Categories</h2><ul>\n");
      }
      else
      {
	print("<h2>Categories in $category</h2><ul>\n");
      }

      while ($row = db_next($result))
      {
        print("<li><a href='$PHP_SELF?L+P$row['id'>$row['name</a>");

        if ($LOGIN_USER)
	{
	  print(" [&nbsp;<a href='$PHP_SELF?FC$row['id+P$parent_id'>Edit</a> |"
	       ." <a href='$PHP_SELF?X$row['id+P$parent_id'>Delete</a>&nbsp;]");
	}

	print("</li>\n");
      }

      print("<p>[ <a href='$PHP_SELF?FC+P$parent_id'>Add New Category</a> ]</p>\n");

      print("</ul>\n");

      db_free($result);

      // Then show the listings...
      if ($query != "")
        $result = db_query("SELECT * FROM link "
                             ."WHERE is_published = 1 AND is_category = 0 AND "
			     ."($query) "
			     ."ORDER BY name");
      else if ($parent_id >= 0)
        $result = db_query("SELECT * FROM link "
                             ."WHERE is_published = 1 AND is_category = 0 AND "
			     ."parent_id = $parent_id "
			     ."ORDER BY name");
      else
        $result = db_query("SELECT * FROM link "
                             ."WHERE is_published = 1 AND is_category = 0 "
			     ."ORDER BY name");

      if ($parent_id < 0)
      {
	print("<h2>All Listings</h2><ul>\n");
      }
      else
      {
	print("<h2>Listings in $category</h2><ul>\n");
      }

      while ($row = db_next($result))
      {
        if ($row['is_category)
	  continue;

        $age = (int)((time() - $row['modify_date) / 86400);

        print("<li><b><a href='$PHP_SELF?V$row['id'>$row['name $row['version</a></b>");

	if ($SEARCH != "")
	{
	  $category = get_category($row['parent_id, 1);
	  print(" in $category");
	}

        if ($LOGIN_USER)
	{
	  print(" [&nbsp;<a href='$PHP_SELF?FL$row['id+P$parent_id'>Edit</a>"
	       ." | <a href='$PHP_SELF?X$row['id+P$parent_id'>Delete</a>&nbsp;]\n");
	}

        if ($age < 30)
          print(" <I>[Updated $age day(s) ago]</I>");

        print("<br />$row['description<br />&nbsp;</li>\n");
      }

      print("<p>[&nbsp;<a href='$PHP_SELF?FL+P$parent_id'>Add&nbsp;New&nbsp;"
           ."Listing</a>&nbsp;]</p>\n");

      print("</ul>\n");

      db_free($result);
      break;

  case 'U' : // Add or update category or listing...
      global $IS_PUBLISHED;
      global $PARENT_ID;
      global $NAME;
      global $OWNER_EMAIL;
      global $OWNER_PASSWORD;
      global $NEW_PASSWORD;
      global $NEW_PASSWORD2;
      global $VERSION;
      global $LICENSE;
      global $EMAIL;
      global $HOMEPAGE;
      global $DOWNLOAD;
      global $DESCRIPTION;
      global $NEWS;
      global $AUTHOR;

      $parent_id   = (int)$PARENT_ID;
      $name        = mysql_escape_string($NAME);
      $version     = mysql_escape_string($VERSION);
      $license     = mysql_escape_string($LICENSE);
      $author      = mysql_escape_string($AUTHOR);
      $owner_email = mysql_escape_string($OWNER_EMAIL);
      $email       = mysql_escape_string($EMAIL);
      $homepage    = mysql_escape_string($HOMEPAGE);
      $download    = mysql_escape_string($DOWNLOAD);
      $description = mysql_escape_string($DESCRIPTION);
      $date        = time();

      if ($type == 'C')
        $typename = 'Category';
      else
        $typename = 'Listing';

      if ($id > 0)
        $opname = 'Updated';
      else
        $opname = 'Added';

      if ($id > 0)
      {
        $result = db_query("SELECT * FROM link WHERE id = $id");
	$row    = db_next($result);

	$is_category    = $row['is_category;
	$owner_password = $row['owner_password;

        db_free($result);
      }
      else
      {
        if ($type == 'C')
	  $is_category = 1;
	else
	  $is_category = 0;

	$owner_password = "";
      }

      if ($owner_email == "")
      {
        print("<h2>$typename '$NAME' Not $opname</h2>\n");
	print("<p>The owner email address cannot be empty!</p>\n");
	break;
      }

      if ($owner_password != "" && $owner_password != $OWNER_PASSWORD &&
	  !$LOGIN_USER)
      {
        print("<h2>$typename '$NAME' Not $opname</h2>\n");
	print("<p>The password you supplied does not match the "
	     ."current password!</p>\n");
	break;
      }

      if ($NEW_PASSWORD != "" && $NEW_PASSWORD != $NEW_PASSWORD2)
      {
        print("<h2>$typename '$NAME' Not $opname</h2>\n");
	print("<p>The passwords you supplied do not match!</p>\n");
	break;
      }

      if ($NEW_PASSWORD == "" && $owner_password == "")
      {
        print("<h2>$typename '$NAME' Not $opname</h2>\n");
	print("<p>You must supply a password!</p>\n");
	break;
      }

      if ($NEW_PASSWORD != "")
      {
        $owner_password = $NEW_PASSWORD;
      }

      if ($id == 0)
      {
        // Insert a new record...
	db_query("INSERT INTO link VALUES(0,$parent_id,"
	           ."$is_category,$IS_PUBLISHED,"
		   ."'$name','$version','$license',"
		   ."'$author','$owner_email','$owner_password',"
		   ."'$email','$homepage','$download',"
		   ."'$description',$date,$date,5,1,0,0)");

        $id = db_insertID();
      }
      else
      {
        // Modify the existing record...
	db_query("UPDATE link SET is_published=$IS_PUBLISHED,"
	           ."parent_id=$parent_id,"
		   ."name='$name',version='$version',license='$license',"
		   ."author='$author',owner_email='$owner_email',"
		   ."owner_password='$owner_password',email='$email',"
		   ."homepage='$homepage',download='$download',"
		   ."description='$description',modify_date=$date "
		   ."WHERE id=$id");
      }

      if ($NEWS != "")
      {
        $news = mysql_escape_string($NEWS);

        if ($homepage)
	  $nhp = "links.php?SH$id";
	else
	  $nhp = "";

        if ($download)
	  $ndl = "links.php?SD$id";
	else
	  $ndl = "";

	db_query("INSERT INTO news VALUES(0,$id,'$name $version',$date,"
                   ."'$author','$news','$nhp','$ndl',$date,'$email',"
                   ."0,'','PENDING')");
      }

      print("<h2>$typename '$NAME' $opname</h2>\n");

      if ($opname == "Added")
      {
        // Send email to moderators...
	mail("cups-link", "New $typename Added to CUPS Links",
	     "'$name' has been added to the CUPS links page\n"
	    ."and requires your approval before it will be made visible on\n"
	    ."the CUPS site.\n"
	    ."\n"
	    ."    http://www.cups.org/private/links.php\n");

        // Let the user know that the moderator must approve it...
        print("<p>Your addition will be made visible as soon as one of "
             ."moderators approves it.</p>\n");
      }

      if ($NEWS != "")
      {
        // Send email to moderators...
	mail("cups-link", "$name $version Posted to CUPS News",
	     "An announcement for '$name $version' has been posted\n"
            ."from the CUPS links page and requires your approval before it\n"
	    ."will be made visible on the CUPS site.\n"
	    ."\n"
	    ."    http://www.cups.org/private/news.php\n");

        // Let the user know that the moderator must approve it...
        print("<p>Your news announcement will be made visible as soon as one of "
             ."moderators approves it.</p>\n");
      }

      print("<p><a href='$PHP_SELF?L+P$parent_id'>Return to listing.</a></p>\n");
      break;

  case 'V' : // View a listing...
      $result = db_query("SELECT * FROM link WHERE id = $id");
      $row    = db_next($result);

      $create_date = date("M d, Y", $row['create_date);
      $modify_date = date("M d, Y", $row['modify_date);
      $category    = get_category($row['parent_id);
      $rating      = (int)(100 * $row['rating_total / $row['rating_count) * 0.01;
      $email       = sanitize_email($row['email);

      if (($row['homepage_visits + $row['download_visits) > 0)
      {
        $visits     = db_query("SELECT MAX(homepage_visits), "
	                         ."MAX(download_visits) FROM link");
	$visrow     = db_next($visits);

        $maxhpv     = "MAX(homepage_visits)";
        $maxdlv     = "MAX(download_visits)";

        $popularity = (int)(100 *
	                    ($row['homepage_visits + $row['download_visits) /
	                    ($visrow->$maxhpv + $visrow->$maxdlv));

        if ($popularity < 0)
	  $popularity = 0;

	db_free($visits);
      }
      else
      {
        $popularity = "???";
      }


      print("<P align='CENTER'>[&nbsp;"
           ."<a href='$PHP_SELF?P$row['parent_id'>Return to Listings</a>"
	   ." | "
           ."<a href='#_USER_COMMENTS'>Comments</a>"
	   ." | "
	   ."<a href='$PHP_SELF?FL$row['id+P$row['parent_id'>Edit&nbsp;This&nbsp;Listing</a>"
	   ." | "
	   ."<a href='$PHP_SELF?X$row['id+P$row['parent_id'>Delete&nbsp;This&nbsp;Listing</a>&nbsp;]</p>\n");

      print("<table width='100%' border='0'>\n");
      print("<tr>"
           ."<th align='right'>Category:</th>"
	   ."<td>$category</td>"
           ."<th align='right'>Rating:</th>"
	   ."<td><form method='POST' action='$PHP_SELF?r$id'>$rating&nbsp;"
	   ."<select name='RATING'>"
	   ."<option value='0'>0 - Worst</option>"
	   ."<option value='1'>1</option>"
	   ."<option value='2'>2</option>"
	   ."<option value='3'>3</option>"
	   ."<option value='4'>4</option>"
	   ."<option value='5' SELECTED>5 - Average</option>"
	   ."<option value='6'>6</option>"
	   ."<option value='7'>7</option>"
	   ."<option value='8'>8</option>"
	   ."<option value='9'>9</option>"
	   ."<option value='10'>10 - Best</option>"
	   ."</select>"
	   ."<input type='submit' value='Rate It!'></form>"
	   ."</td>"
	   ."</tr>\n");
      print("<tr>"
           ."<th align='right'>Name:</th>"
	   ."<td>$row['name</td>"
           ."<th align='right'>Popularity:</th>"
	   ."<td>$popularity%</td>"
	   ."</tr>\n");
      print("<tr>"
           ."<th align='right'>Version:</th>"
	   ."<td>$row['version</td>"
           ."<th align='right'>License:</th>"
	   ."<td>$row['license</td>"
	   ."</tr>\n");
      print("<tr>"
           ."<th align='right'>Author:</th>"
	   ."<td>$row['author</td>"
           ."<th align='right'>EMail:</th>"
	   ."<td>$email</td>"
	   ."</tr>\n");
      print("<tr>"
           ."<th align='right'>Home Page:</th>"
	   ."<td colspan='3'><a href='$PHP_SELF?SH$id'>$row['homepage</a> ($row['homepage_visits visits)</td>"
	   ."</tr>\n");
      print("<tr>"
           ."<th align='right'>Download:</th>"
	   ."<td colspan='3'><a href='$PHP_SELF?SD$id'>$row['download</a> ($row['download_visits visits)</td>"
	   ."</tr>\n");
      print("<tr>"
           ."<th align='right' valign='top'>Description:</th>"
	   ."<td colspan='3'>$row['description</td>"
	   ."</tr>\n");
      print("</table>\n");

      db_free($result);

      print("<hr noshade/>\n"
           ."<H2><A NAME='_USER_COMMENTS'>User Comments</a>"
	   ." [&nbsp;<a href='comment.php?r0+plinks.php_V$id'>Add&nbsp;Comment</a>&nbsp;]</H2>\n");

      show_comments("links.php_V$id");
      break;

  case 'X' : // Delete listing...
      global $OWNER_EMAIL;
      global $OWNER_PASSWORD;

      if ($id <= 0)
      {
        print("<h2>Error</h2>\n"
	     ."<p>No link ID provided...</p>\n");
	break;
      }

      $result = db_query("SELECT * FROM link WHERE id = $id");

      if (!$result)
      {
        print("<h2>Error</h2>\n"
	     ."<p>Link $id does not exist.</p>\n");
	break;
      }

      $row = db_next($result);

      if (!$row)
      {
        print("<h2>Error</h2>\n"
	     ."<p>Link $id does not exist.</p>\n");
	break;
      }

      $name           = $row['name;
      $owner_email    = $row['owner_email;
      $owner_password = $row['owner_password;

      db_free($result);

      if (!$LOGIN_USER && !($OWNER_EMAIL && $OWNER_PASSWORD))
      {
        print("<h2>Delete $name</h2>\n");

	print("<form method='POST' action='$PHP_SELF?X$id+P$parent_id'>\n"
             ."<center><table border='0'>\n");

	print("<tr>"
             ."<th align='right'>Owner Email:</th>"
	     ."<td><input type='text' name='OWNER_EMAIL' value='$owner_email' "
	     ."size='40' maxlength='128'></td>"
	     ."</tr>\n");

	print("<tr>"
             ."<th align='right'>Owner Password:</th>"
	     ."<td><input type='password' name='OWNER_PASSWORD' "
	     ."size='32' maxlength='32'></td>"
	     ."</tr>\n");

	print("<tr>"
             ."<th></th>"
	     ."<td><input type='submit' value='Delete'></td>"
	     ."</tr>\n");

	print("</table></center>\n");
	print("</form>");
	break;
      }
      else if (!$LOGIN_USER &&
               ($OWNER_EMAIL != $owner_email ||
	        $OWNER_PASSWORD != $owner_password))
      {
        print("<h2>Error</h2>\n"
	     ."<p>Owner email or password doesn't match!</p>\n");
	break;
      }

      db_query("DELETE FROM link WHERE id=$id");

      print("<h2>Deleted $name</h2>\n");
      print("<p><a href='$PHP_SELF?P$parent_id'>Return to listing.</a></p>\n");
      break;

  case 'Z' : // List new...
      print("<p><a href='$PHP_SELF?L+P$parent_id'>[&nbsp;Show&nbsp;Listings&nbsp;]</a></p>\n");

      // Show the categories...
      $result = db_query("SELECT * FROM link "
                           ."WHERE is_published = 0 AND is_category = 1 "
			   ."ORDER BY name");

      print("<h2>New Categories</h2><ul>\n");

      while ($row = db_next($result))
      {
        $create_date = date("M d, Y", $row['create_date);
        $category    = get_category($row['parent_id, 1);

        print("<li><a href='$PHP_SELF?V$row['id'>$row['name</a>"
             ." in $category"
	     ."<br />(Created $create_date)"
	     ." [&nbsp;<a href='$PHP_SELF?FC$row['id'>Edit</a>"
	     ." | <a href='$PHP_SELF?X$row['id'>Delete</a>"
	     ."&nbsp;]</li>\n");
      }

      print("</ul>\n");

      db_free($result);

      // Then show the listings...
      $result = db_query("SELECT * FROM link "
                	."WHERE is_published = 0 AND is_category = 0 "
			."ORDER BY name");

      print("<h2>New Listings</h2>\n"
           ."<ul>\n");

      while ($row = db_next($result))
      {
        if ($row['is_category)
	  continue;

        $create_date = date("M d, Y", $row['create_date);
        $category    = get_category($row['parent_id, 1);

        print("<li><b><a href='$PHP_SELF?V$row['id'>$row['name</a></b>"
             ." in $category"
	     ."<br />(Created $create_date)"
	     ." [&nbsp;<a href='$PHP_SELF?FL$row['id'>Edit</a>"
	     ." | <a href='$PHP_SELF?X$row['id'>Delete</a>"
	     ."&nbsp;]</li>\n");
      }

      print("</ul>\n");

      db_free($result);
      break;

  case 'r' : // Rate this entry...
      global $RATING;

      if ($RATING != "")
	if (db_query("INSERT INTO vote VALUES('link_${id}_${REMOTE_ADDR}')"))
          db_query("UPDATE link SET rating_count = rating_count + 1, "
	          ."rating_total = rating_total + $RATING WHERE id = $id");

      header("Location: $PHP_SELF?V$id");
      break;
        
  case 'S' : // Show home or download page...
      $result = db_query("SELECT * FROM link WHERE id = $id");
      $row    = db_next($result);

      if ($type == 'H')
      {
	db_query("UPDATE link SET homepage_visits = homepage_visits + 1 "
	        ."WHERE id = $id");

        header("Location: $row[homepage]");
      }
      else if ($type == 'D')
      {
	db_query("UPDATE link SET download_visits = download_visits + 1 "
	        ."WHERE id = $id");

        header("Location: $row[download]");
      }
      else
        header("Location: $PHP_SELF?V$id");

      db_free($result);
      break;
}

db_close();

if (!$redirect)
  html_footer();
?>
