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
//   html_header()      - Show the standard page header and navbar...
//   html_footer()      - Show the standard footer for a page.
//   html_start_links() - Start of series of hyperlinks.
//   html_end_links()   - End of series of hyperlinks.
//   html_link()        - Show a single hyperlink.
//   html_links()       - Show an array of links.
//   html_start_box()   - Start a rounded, shaded box.
//   html_end_box()     - End a rounded, shaded box.
//   html_start_table() - Start a rounded, shaded table.
//   html_end_table()   - End a rounded, shaded table.
//   html_start_row()   - Start a table row.
//   html_end_row()     - End a table row.
//


//
// Include necessary headers...
//

include_once "globals.php";
include_once "auth.php";


//
// Search keywords...
//

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


//
// 'html_header()' - Show the standard page header and navbar...
//

function				// O - User information
html_header($title = "",		// I - Additional document title
            $path = "",			// I - Relative path to root
	    $refresh = "")		// I - Refresh URL
{
  global $html_keywords, $argc, $argv, $PHP_SELF, $LOGIN_USER;


  // Check for a logout on the command-line...
  if ($argc == 1 && $argv[0] == "logout")
  {
    auth_logout();
    $argc = 0;
  }

  // Common stuff...
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
       ."  <meta http-equiv='Pragma' content='no-cache'/>\n"
       ."  <meta http-equiv='Content-Type' content='text/html; "
       ."charset=iso-8859-1'/>\n"
       ."  <link rel='stylesheet' type='text/css' href='${path}style.css'/>\n");

  // If refresh URL is specified, add the META tag...
  if ($refresh != "")
    print("  <meta http-equiv='refresh' content='3; $refresh'/>\n");

  // Search engine keywords...
  reset($html_keywords);

  list($key, $val) = each($html_keywords);
  print("<meta name='keywords' content='$val");

  while (list($key, $val) = each($html_keywords))
    print(",$val");

  print("'/>\n");

  print("</head>\n"
       ."<body>\n");

  // Standard navigation stuff...
  print("<p><table width='100%' height='100%' border='0' cellspacing='0' "
       ."cellpadding='0'>\n"
       ."<tr class='header' height='40'>"
       ."<td valign='top'><img src='${path}images/top-left.gif' width='15' "
       ."height='15' alt=''/></td>"
       ."<td><img src='${path}images/logo.gif' width='39' height='32' "
       ."alt='Mini-XML' align='middle'/>&nbsp;&nbsp;&nbsp;</td>"
       ."<td width='100%'>[&nbsp;<a href='${path}index.php'>Home</a> | "
       ."<a href='${path}articles.php'>Articles</a> | "
       ."<a href='${path}str.php'>Bugs &amp; Features</a> | "
       ."<a href='${path}documentation.php'>Documentation</a> | "
       ."<a href='${path}software.php'>Download</a> | "
       ."<a href='${path}links.php'>Links</a>&nbsp;]</td>"
       ."<td align='right'>[&nbsp;");


  if ($LOGIN_USER)
    print("<a href='${path}account.php'>$LOGIN_USER</a>");
  else
    print("<a href='${path}login.php'>Login</a>");

  print("&nbsp;]</td>"
       ."<td valign='top'><img src='${path}images/top-right.gif' width='15' "
       ."height='15' alt=''/></td>"
       ."</tr>\n");

  print("<tr class='page' height='100%'><td></td>"
       ."<td colspan='3' valign='top'>"
       ."<table width='100%' height='100%' border='0' cellpadding='5' "
       ."cellspacing='0'><tr><td valign='top'>");
}


//
// 'html_footer()' - Show the standard footer for a page.
//

function
html_footer($path = "")			// I - Relative path to root
{
  print("</td></tr></table></td><td></td></tr>\n");
  print("<tr class='page'><td colspan='5'>&nbsp;</td></tr>\n");
  print("<tr class='header'>"
       ."<td valign='bottom'><img src='${path}images/bottom-left.gif' "
       ."width='15' height='15' alt=''/></td>"
       ."<td colspan='3'><small> <br />"
       ."Copyright 2003-2005 by Michael Sweet. This library is free "
       ."software; you can redistribute it and/or modify it "
       ."under the terms of the GNU Library General Public "
       ."License as published by the Free Software Foundation; "
       ."either version 2 of the License, or (at your option) "
       ."any later version.<br />&nbsp;</small></td>"
       ."<td valign='bottom'><img src='${path}images/bottom-right.gif' "
       ."width='15' height='15' alt=''/></td>"
       ."</tr>\n");
  print("</table></p>\n");
  print("</body>\n"
       ."</html>\n");
}


//
// 'html_start_links()' - Start of series of hyperlinks.
//

function
html_start_links($center = 0)		// I - 1 for centered, 0 for in-line
{
  global $html_firstlink;

  $html_firstlink = 1;

  if ($center)
    print("<p class='center' align='center'>[&nbsp;");
  else
    print("<p>[&nbsp;");
}


//
// 'html_end_links()' - End of series of hyperlinks.
//

function
html_end_links()
{
  print("&nbsp;]</p>\n");
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
    print(" | ");

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
  global $html_row, $html_cols;


  print("<p><table");
  if ($width != "")
    print(" width='$width'");
  if ($height != "")
    print(" height='$height'");
  print(" border='0' cellpadding='0' cellspacing='0'>"
       ."<tr class='header'><th align='left' valign='top'>"
       ."<img src='images/hdr-top-left.gif' width='16' height='16' "
       ."alt=''/></th>");

  $html_row  = 0;
  $html_cols = count($headings);

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
      {
        $s_colspan = "colspan=$data[2]";

        if ($data[2] > 1)
          $html_cols += $data[2] - 1;
      }

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

  print("<th align='right' valign='top'>"
       ."<img src='images/hdr-top-right.gif' "
       ."width='16' height='16' alt=''/></th></tr>\n");
}


//
// 'html_end_table()' - End a rounded, shaded table.
//

function
html_end_table()
{
  global $html_cols;

  print("<tr class='header'><th align='left' valign='bottom'>"
       ."<img src='images/hdr-bottom-left.gif' width='16' height='16' "
       ."alt=''/></th>"
       ."<th colspan='$html_cols'>&nbsp;</th>"
       ."<th align='right' valign='bottom'><img src='images/hdr-bottom-right.gif' "
       ."width='16' height='16' alt=''/></th></tr>\n"
       ."</table></p>\n");
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

  print("<tr class='$classname'><td>&nbsp;</td>");
}


//
// 'html_end_row()' - End a table row.
//

function
html_end_row()
{
  global $html_row;

  $html_row = 1 - $html_row;

  print("</td><td>&nbsp;</td></tr>\n");
}


?>
