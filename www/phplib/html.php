<?php
//
// "$Id: html.php,v 1.7 2004/05/18 19:58:35 mike Exp $"
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
html_header($title = "")		// I - Additional document title
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
       ."  <link rel='stylesheet' type='text/css' href='style.css'/>\n");

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
       ."<td valign='top'><img src='images/top-left.gif' width='15' height='15' "
       ."alt=''/></td>"
       ."<td><img src='images/logo.gif' width='39' height='32' "
       ."alt='Mini-XML' align='middle'/>&nbsp;&nbsp;&nbsp;</td>"
       ."<td width='100%'>[&nbsp;<a href='index.php'>Home</a> | "
       ."<a href='articles.php'>Articles</a> | "
       ."<a href='documentation.php'>Documentation</a> | "
       ."<a href='software.php'>Download</a> | "
       ."<a href='str.php'>Support</a>&nbsp;]</td>"
       ."<td align='right'>[&nbsp;");


  if ($LOGIN_USER)
    print("<a href='account.php'>$LOGIN_USER</a>");
  else
    print("<a href='login.php'>Login</a>");

  print("&nbsp;]</td>"
       ."<td valign='top'><img src='images/top-right.gif' width='15' height='15' "
       ."alt=''/></td>"
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
html_footer()
{
  print("</td></tr></table></td><td></td></tr>\n");
  print("<tr class='header'>"
       ."<td valign='bottom'><img src='images/bottom-left.gif' width='15' "
       ."height='15' alt=''/></td>"
       ."<td colspan='3'><small> <br />"
       ."Copyright 2003-2004 by Michael Sweet. This library is free "
       ."software; you can redistribute it and/or modify it "
       ."under the terms of the GNU Library General Public "
       ."License as published by the Free Software Foundation; "
       ."either version 2 of the License, or (at your option) "
       ."any later version.<br />&nbsp;</small></td>"
       ."<td valign='bottom'><img src='images/bottom-right.gif' width='15' "
       ."height='15' alt=''/></td>"
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
html_start_table($headings)		// I - Array of heading strings
{
  global $html_row, $html_cols;

  print("<p><table width='100%' border='0' cellpadding='0' cellspacing='0'>"
       ."<tr class='header'><th align='left' valign='top'>"
       ."<img src='images/top-left.gif' width='16' height='16' "
       ."alt=''/></th>");

  $add_html_cols = 0;   //  Add to html_cols after display if colspan is used.
  $html_row  = 0;
  $html_cols = sizeof($headings);

  reset($headings);
  for ($i = 0; $i < $html_cols; $i ++)
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
    //    "xxxxxxxx:cc:ww"      -- Heading with colspan and width.
    //    "xxxxxxxx:aa:cc:ww"   -- Heading with align, colspan and width.
    //
    //    etc, etc.
    //

    $s_header = "";
    $s_colspan = "";
    $s_width = "";
    $s_align = "";

    if (strstr( $headings[$i], ":" ))
    {
      $data = explode( ":", $headings[$i] );

      $s_header = $data[0];

      if (ISSET($data[1]))
      {
        $align = $data[1];
        $s_align = "align=$align";
      }

      if ($data[2] > 0)
      {
        $colspan = $data[2];
        $s_colspan = "colspan=$colspan";
        if ($colspan > 1)
          $add_html_cols += ($colspan - 1);
      }

      if ($data[3] > 0)
      {
        $width = $data[3];
        $s_width = "width=$width%";
      }
    }
    else
      $s_header = $headings[$i];

    if (strlen($s_header))
    {
      print("<th $s_align $s_colspan $s_width>$s_header</th>");
    }
    else
    {
      print("<th $s_colspan $s_width>&nbsp;</th>");
    }
  }

  $html_cols += $add_html_cols;

  print("<th align='right' valign='top'>"
       ."<img src='images/top-right.gif' "
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
       ."<img src='images/bottom-left.gif' width='16' height='16' "
       ."alt=''/></th>"
       ."<th colspan='$html_cols'>&nbsp;</th>"
       ."<th align='right' valign='bottom'><img src='images/bottom-right.gif' "
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
