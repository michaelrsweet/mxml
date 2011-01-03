<?php
//
// "$Id$"
//
// Mini-XML documentation page...
//

//
// Include necessary headers...
//

include_once "phplib/html.php";
include_once "phplib/common.php";


//
// Get the web server path information and serve the named file as needed...
//

if (array_key_exists("PATH_INFO", $_SERVER) &&
    $_SERVER["PATH_INFO"] != "/" &&
    $_SERVER["PATH_INFO"] != "")
{
  $path = "$_SERVER[PATH_INFO]";

  if (fnmatch("*.gif", $path))
    $type = "gif";
  else if (fnmatch("*.jpg", $path))
    $type = "jpeg";
  else if (fnmatch("*.png", $path))
    $type = "png";
  else
    $type = "html";

  if (strstr($path, ".."))
  {
    if ($type == "html")
    {
      html_header("Documentation Error");

      print("<p>The path '$path' is bad.</p>\n");

      html_footer();
    }
  }
  else
  {
    $fp = fopen("docfiles$path", "rb");
    if (!$fp)
    {
      if ($type == "html")
      {
	html_header("Documentation Error");

	print("<p>Unable to open path '$path'.</p>\n");

	html_footer();
      }
    }
    else if ($type == "html")
    {
      html_header("Documentation");

      $saw_body = 0;
      $last_nav = 0;

      while ($line = fgets($fp, 1024))
      {
        if (strstr($line, "<BODY") || strstr($line, "<body"))
	{
	  $saw_body = 1;
	}
	else if (strstr($line, "</BODY>") || strstr($line, "</body>"))
	{
	  break;
	}
	else if ($saw_body)
	{
	  if (strstr($line, "<A HREF=\"index.html\">Contents</A") ||
	      strstr($line, ">Previous</A>") ||
	      strstr($line, ">Next</A>"))
	  {
	    if ($last_nav)
	      print("&middot\n");
	    else
	      print("<p class='links'><A HREF='#_USER_COMMENTS'>Comments</a> "
	           ."&middot;\n");

            $last_nav = 1;
	  }
	  else if (strstr($line, "<HR"))
	  {
	    if ($last_nav)
	      print("</p>\n");

	    $last_nav = 0;
	    $line     = "";
	  }

	  print($line);
	}
      }

      fclose($fp);

      if ($last_nav)
        print("</p>\n");

      print("<h1><a name='_USER_COMMENTS'>User Comments</a></h1>\n"
	   ."<p><a href='$html_path/comment.php?r0+pdocumentation.php$path'>"
	   ."Add&nbsp;Comment</a></p>\n");

      $num_comments = show_comments("documentation.php$path");

      if ($num_comments == 0)
        print("<p>No comments for this page.</p>\n");

      html_footer();
    }
    else
    {
      header("Content-Type: image/$type");
      
      print(fread($fp, filesize("docfiles$path")));

      fclose($fp);
    }
  }
}
else
{
  html_header("Documentation");

  if (array_key_exists("CLEAR", $_GET))
    $q = "";
  else if (array_key_exists("Q", $_GET))
    $q = $_GET["Q"];
  else
    $q = "";

  $html = htmlspecialchars($q, ENT_QUOTES);

  if (stripos($_SERVER["HTTP_USER_AGENT"], "webkit") !== FALSE)
  {
    // Use Safari search box...
    $search = "<input type='search' name='Q' value='$html' size='50' "
	     ."autosave='com.easysw.mxml.search' results='5' "
             ."placeholder='Search'>";
  }
  else
  {
    // Use standard HTML text field...
    $search = "<input type='text' name='Q' value='$html' size='40' "
             ."title='Search'> "
	     ."<input type='submit' value='Search'> "
	     ."<input type='submit' name='CLEAR' value='Clear'>";
  }

  print("<form action='$PHP_SELF' method='GET'>\n"
       ."<p align='center'>$search</p>\n"
       ."</form>\n");

  if ($q != "")
  {
    // Run htmlsearch to search the documentation...
    $matches  = array();
    $scores   = array();
    $maxscore = 0;
    $fp       = popen("/usr/local/bin/websearch docfiles " . escapeshellarg($q),
                      "r");

    fgets($fp, 1024);

    while ($line = fgets($fp, 1024))
    {
      $data              = explode("|", $line);
      $matches[$data[1]] = $data[2];
      $scores[$data[1]]  = $data[0];

      if ($maxscore == 0)
        $maxscore = $data[0];
    }

    pclose($fp);

    // Show the results...
    if (sizeof($matches) == 1)
      $total = "1 match";
    else
      $total = sizeof($matches) . " matches";

    print("<p>$total found:</p>\n"
         ."<table symmary=\"Search Results\">\n");

    reset($matches);
    foreach ($matches as $file => $text)
    {
      $link  = "$PHP_SELF/$file";
      $score = str_repeat("&#x2605;",
                          (int)(4 * $scores[$file] / $maxscore) + 1);

      print("<tr><td style=\"font-size: 50%;\">$score&nbsp;&nbsp;&nbsp;</td>"
           ."<td><a href='$link'>$text</a></td></tr>\n");
    }

    print("</table>\n");
  }
  else
  {
?>

<p>You can view the Mini-XML documentation in a single HTML file or in
multiple files with comments on-line:</p>

<ul>

	<li><a href='mxml.html'>HTML in one file (169k)</a></li>

	<li><a href='documentation.php/index.html'>HTML in
	separate files with Comments</a>

	<ul>

		<li><a
		href='documentation.php/intro.html'>Introduction</a></li>

		<li><a
		href='documentation.php/install.html'>Building,
		Installing, and Packaging Mini-XML</a></li>

		<li><a href='documentation.php/basics.html'>Getting
		Started with Mini-XML</a></li>

		<li><a href='documentation.php/advanced.html'>More
		Mini-XML Programming Techniques</a></li>

		<li><a href='documentation.php/mxmldoc.html'>Using
		the mxmldoc Utility</a></li>

		<li><a
		href='documentation.php/license.html'>Mini-XML
		License</a></li>

		<li><a
		href='documentation.php/relnotes.html'>Release
		Notes</a></li>

		<li><a href='documentation.php/reference.html'>Library
		Reference</a></li>

	</ul></li>

</ul>

<p>You can also get a printed version of the Mini-XML documentation on
<a href="http://www.lulu.com/content/820838">Lulu.com</a>.</p>

<?php
  }

  html_footer();
}

//
// End of "$Id$".
//
?>
