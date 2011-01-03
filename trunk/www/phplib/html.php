<?php
//
// "$Id$"
//
// PHP functions for standardized HTML output...
//
// This file should be included using "include_once"...
//
// Contents:
//
//   html_header()              - Show the standard page header and navbar...
//   html_footer()              - Show the standard footer for a page.
//   html_start_links()         - Start of series of hyperlinks.
//   html_end_links()           - End of series of hyperlinks.
//   html_link()                - Show a single hyperlink.
//   html_links()               - Show an array of links.
//   html_start_box()           - Start a rounded, shaded box.
//   html_end_box()             - End a rounded, shaded box.
//   html_start_table()         - Start a rounded, shaded table.
//   html_end_table()           - End a rounded, shaded table.
//   html_start_row()           - Start a table row.
//   html_end_row()             - End a table row.
//   html_search_words()        - Generate an array of search words.
//   html_select_is_published() - Do a <select> for the "is published" field...
//


//
// Include necessary headers...
//

include_once "globals.php";
include_once "auth.php";


// Check for a logout on the command-line...
if ($argc == 1 && $argv[0] == "logout")
{
  auth_logout();
  $argc = 0;
}


// Search keywords...
$html_keywords = array(
  "documentation",
  "functions",
  "library",
  "linux",
  "macos x",
  "mini-xml",
  "mxml",
  "mxmldoc",
  "software",
  "unix",
  "windows",
  "xml"
);

// Figure out the base path...
$html_path = dirname($PHP_SELF);

if (array_key_exists("PATH_INFO", $_SERVER))
{
  $i = -1;
  while (($i = strpos($_SERVER["PATH_INFO"], "/", $i + 1)) !== FALSE)
    $html_path = dirname($html_path);
}

if ($html_path == "/")
  $html_path = "";


//
// 'html_header()' - Show the standard page header and navbar...
//

function				// O - User information
html_header($title = "",		// I - Additional document title
	    $refresh = "")		// I - Refresh URL
{
  global $html_keywords, $html_path, $_GET, $LOGIN_USER, $PHP_SELF, $_SERVER;


  // Common stuff...
//  header("Cache-Control: no-cache");

  print("<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN' "
       ."'http://www.w3.org/TR/REC-html40/loose.dtd'>\n");
  print("<html>\n");
  print("<head>\n");

  // Title...
  if ($title != "")
    $html_title = "$title -";
  else
    $html_title = "";

  print("  <title>$html_title Mini-XML</title>\n"
       ."  <meta http-equiv='Pragma' content='no-cache'>\n"
       ."  <meta http-equiv='Content-Type' content='text/html; "
       ."charset=utf-8'>\n"
       ."  <link rel='stylesheet' type='text/css' href='$html_path/style.css'>\n"
       ."  <link rel='shortcut icon' href='$html_path/images/logo.gif' "
       ."type='image/x-icon'>\n");

  // If refresh URL is specified, add the META tag...
  if ($refresh != "")
    print("  <meta http-equiv='refresh' content='3; $refresh'>\n");

  // Search engine keywords...
  reset($html_keywords);

  list($key, $val) = each($html_keywords);
  print("<meta name='keywords' content='$val");

  while (list($key, $val) = each($html_keywords))
    print(",$val");

  print("'>\n");

  print("</head>\n"
       ."<body>\n");

  // Standard navigation stuff...
  if (array_key_exists("Q", $_GET))
    $q = htmlspecialchars($_GET["Q"], ENT_QUOTES);
  else
    $q = "";

  if (stripos($_SERVER["HTTP_USER_AGENT"], "webkit") !== FALSE)
  {
    // Use Safari search box...
    $search = "<input type='search' name='Q' value='$q' size='25' "
	     ."autosave='com.easysw.mxml.search' results='5' "
             ."placeholder='Search'>";
  }
  else
  {
    // Use standard HTML text field...
    $search = "<input type='text' name='Q' value='$q' size='15' "
             ."title='Search'><input type='submit' value='Search'>";
  }

  $classes = array("unsel", "unsel", "unsel", "unsel", "unsel", "unsel");
  if (strpos($PHP_SELF, "/account.php") !== FALSE ||
      strpos($PHP_SELF, "/login.php") !== FALSE)
    $classes[0] = "sel";
  else if (strpos($PHP_SELF, "/str.php") !== FALSE)
    $classes[2] = "sel";
  else if (strpos($PHP_SELF, "/documentation.php") !== FALSE ||
           strpos($PHP_SELF, "/account.php") !== FALSE)
    $classes[3] = "sel";
  else if (strpos($PHP_SELF, "/software.php") !== FALSE)
    $classes[4] = "sel";
  else if (strpos($PHP_SELF, "/forums.php") !== FALSE)
    $classes[5] = "sel";
  else
    $classes[1] = "sel";

  print("<table width='100%' style='height: 100%;' border='0' cellspacing='0' "
       ."cellpadding='0' summary=''>\n"
       ."<tr>"
       ."<td class='unsel'><img src='$html_path/images/logo.gif' width='32' "
       ."height='32' alt=''></td>"
       ."<td class='$classes[0]'>");

  if ($LOGIN_USER)
    print("<a href='$html_path/account.php'>$LOGIN_USER</a>");
  else
    print("<a href='$html_path/login.php'>Login</a>");

  print("</td>"
       ."<td class='$classes[1]'><a href='$html_path/index.php'>Home</a></td>"
       ."<td class='$classes[2]'><a href='$html_path/str.php'>Bugs&nbsp;&amp;&nbsp;Features</a></td>"
       ."<td class='$classes[3]'><a href='$html_path/documentation.php'>Documentation</a></td>"
       ."<td class='$classes[4]'><a href='$html_path/software.php'>Download</a></td>"
       ."<td class='$classes[5]'><a href='$html_path/forums.php'>Forums</a></td>"
       ."<td class='unsel' align='right' width='100%'>"
       ."<form action='$html_path/documentation.php' method='GET'>"
       ."$search</form></td>"
       ."</tr>\n"
       ."<tr><td class='page' colspan='8'>");
}


//
// 'html_footer()' - Show the standard footer for a page.
//

function
html_footer()
{
  print("</td></tr>\n"
       ."<td class='footer' colspan='8'>"
       ."Copyright 2003-2011 by Michael Sweet. This library is free "
       ."software; you can redistribute it and/or modify it "
       ."under the terms of the <a href='$html_path/docfiles/license.html'>"
       ."Mini-XML License</a>.</td></tr>\n"
       ."</table>\n"
       ."</body>\n"
       ."</html>\n");
}


//
// 'html_start_links()' - Start of series of hyperlinks.
//

function
html_start_links($center = FALSE)	// I - Center links?
{
  global $html_firstlink;

  $html_firstlink = 1;

  if ($center)
    print("<p class='links'>");
  else
    print("<p>");
}


//
// 'html_end_links()' - End of series of hyperlinks.
//

function
html_end_links()
{
  print("</p>\n");
}


//
// 'html_link()' - Show a single hyperlink.
//

function
html_link($text,			// I - Text for hyperlink
          $link)			// I - URL for hyperlink
{
  global $html_firstlink;

  if ($html_firstlink)
    $html_firstlink = 0;
  else
    print(" &middot; ");

  $safetext = str_replace(" ", "&nbsp;", $text);

  print("<a href='$link'>$safetext</a>");
}


//
// 'html_links()' - Show an array of links.
//

function
html_links($links)			// I - Associated array of hyperlinks
{
  reset($links);
  while (list($key, $val) = each($links))
    html_link($key, $val);
}


//
// 'html_start_table()' - Start a rounded, shaded table.
//

function
html_start_table($headings,		// I - Array of heading strings
                 $width = "100%",	// I - Width of table
		 $height = "")		// I - Height of table
{
  global $html_row;


  print("<table class='standard'");
  if ($width != "")
    print(" width='$width'");
  if ($height != "")
    print(" style='height: $height'");
  print(" summary=''>"
       ."<tr class='header'>");

  $html_row = 0;

  reset($headings);
  for ($i = 0; $i < count($headings); $i ++)
  {
    //
    //  Headings can be in the following forms:
    //
    //    Mix and match as needed:
    //
    //    "xxxxxxxx"            -- Just a column heading.
    //    "xxxxxxxx:aa"         -- Heading with align.
    //    "xxxxxxxx::cc"        -- Heading with a colspan.
    //    "xxxxxxxx:::ww"       -- Heading with a width.
    //    "xxxxxxxx::cc:ww"     -- Heading with colspan and width.
    //    "xxxxxxxx:aa:cc:ww"   -- Heading with align, colspan and width.
    //
    //    etc, etc.
    //

    $s_header  = "";
    $s_colspan = "";
    $s_width   = "";
    $s_align   = "";

    if (strstr($headings[$i], ":"))
    {
      $data     = explode(":", $headings[$i]);
      $s_header = $data[0];

      if ($data[1] != "")
        $s_align = "align=$data[1]";

      if ($data[2] > 1)
        $s_colspan = "colspan=$data[2]";

      if ($data[3] > 0)
        $s_width = "width=$data[3]%";
    }
    else
      $s_header = $headings[$i];

    if (strlen($s_header))
      print("<th $s_align $s_colspan $s_width>$s_header</th>");
    else
      print("<th $s_colspan $s_width>&nbsp;</th>");
  }

  print("</tr>\n");
}


//
// 'html_end_table()' - End a rounded, shaded table.
//

function
html_end_table()
{
  print("</table>\n");
}


//
// 'html_start_row()' - Start a table row.
//

function
html_start_row($classname = "")		// I - HTML class to use
{
  global $html_row;

  if ($classname == "")
    $classname = "data$html_row";
  else
    $html_row = 1 - $html_row;

  print("<tr class='$classname'>");
}


//
// 'html_end_row()' - End a table row.
//

function
html_end_row()
{
  global $html_row;

  $html_row = 1 - $html_row;

  print("</tr>\n");
}


//
// 'html_search_words()' - Generate an array of search words.
//

function				// O - Array of words
html_search_words($search = "")		// I - Search string
{
  $words = array();
  $temp  = "";
  $len   = strlen($search);

  for ($i = 0; $i < $len; $i ++)
  {
    switch ($search[$i])
    {
      case "\"" :
          if ($temp != "")
	  {
	    $words[sizeof($words)] = strtolower($temp);
	    $temp = "";
	  }

	  $i ++;

	  while ($i < $len && $search[$i] != "\"")
	  {
	    $temp .= $search[$i];
	    $i ++;
	  }

	  $words[sizeof($words)] = strtolower($temp);
	  $temp = "";
          break;

      case " " :
      case "\t" :
      case "\n" :
          if ($temp != "")
	  {
	    $words[sizeof($words)] = strtolower($temp);
	    $temp = "";
	  }
	  break;

      default :
          $temp .= $search[$i];
	  break;
    }
  }

  if ($temp != "")
    $words[sizeof($words)] = strtolower($temp);

  return ($words);
}


//
// 'html_select_is_published()' - Do a <select> for the "is published" field...
//

function
html_select_is_published($is_published = 1)
					// I - Default state
{
  print("<select name='is_published'>");
  if ($is_published)
  {
    print("<option value='0'>No</option>");
    print("<option value='1' selected>Yes</option>");
  }
  else
  {
    print("<option value='0' selected>No</option>");
    print("<option value='1'>Yes</option>");
  }
  print("</select>");
}


?>
