<?php
//
// "$Id$"
//
// Mini-XML home page...
//

include_once "phplib/html.php";
include_once "phplib/common.php";
include_once "phplib/poll.php";

html_header();

print("<h1 align='center'>Mini-XML Home Page</h1>");

print("<p><table width='100%' height='100%' border='0' cellpadding='0' "
     ."cellspacing='0'>\n"
     ."<tr><td valign='top' width='30%'>");

html_start_table(array("Quick Info"), "100%", "100%");
html_start_row();
print("<td>"
     ."<p align='center'>"
     ."Stable Release: <a href='software.php?2.2.2'>v2.2.2</a></p>"
     ."<small><p>Mini-XML is a small XML parsing library that you can use to "
     ."read XML and XML-like data files in your application without "
     ."requiring large non-standard libraries. Mini-XML only requires "
     ."an ANSI C compatible compiler (GCC works, as do most vendors' "
     ."ANSI C compilers) and a 'make' program.</p>\n"
     ."<p>Mini-XML provides the following functionality:</p>\n"
     ."<ul>\n"
     ."<li>Reading of UTF-8 and UTF-16 and writing of UTF-8 encoded "
     ."XML files and strings.</li>\n"
     ."<li>Data is stored in a linked-list tree structure, "
     ."preserving the XML data hierarchy.</li>\n"
     ."<li>Supports arbitrary element names, attributes, and "
     ."attribute values with no preset limits, just available "
     ."memory.</li>\n"
     ."<li>Supports integer, real, opaque (\"cdata\"), and text "
     ."data types in \"leaf\" nodes.</li>\n"
     ."<li>Functions for creating, indexing, and managing trees of data.</li>\n"
     ."<li>\"Find\" and \"walk\" functions for easily locating and "
     ."navigating trees of data.</li>\n"
     ."</ul></small>\n"
     ."</td>");
html_end_row();
html_end_table();

$poll = get_recent_poll();
if ($poll > 0)
{
  html_start_table(array("Current Poll [&nbsp;<a href='poll.php'>"
                	."Show&nbsp;All</a>&nbsp;]"));
  html_start_row();
  print("<td>");
  show_poll($poll);
  print("</td>");
  html_end_row();
  html_end_table();
}

print("</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td>"
     ."<td valign='top' width='70%'>"
     ."<h2>Recent Articles [&nbsp;<a href='articles.php'>Show&nbsp;All</a>"
     ."&nbsp;]</h2>\n");

$result = db_query("SELECT * FROM article WHERE is_published = 1 "
	          ."ORDER BY modify_date DESC LIMIT 8");
$count  = db_count($result);

if ($count == 0)
  print("<p>No articles found.</p>\n");
else
{
  while ($row = db_next($result))
  {
    $id          = $row['id'];
    $title       = htmlspecialchars($row['title'], ENT_QUOTES);
    $abstract    = htmlspecialchars($row['abstract'], ENT_QUOTES);
    $create_user = sanitize_email($row['create_user']);
    $date        = date("H:i M d, Y", $row['modify_date']);
    $count       = count_comments("articles.php_L$id");

    if ($count == 1)
      $count .= " comment";
    else
      $count .= " comments";

    print("<h3><a href='articles.php?L$id'>$title</a></h3>\n"
         ."<p><i>$date by $create_user, $count</i><br />$abstract [&nbsp;"
	 ."<a href='articles.php?L$id'>Read</a>&nbsp;]</p>\n");
  }
}

db_free($result);

print("</td></tr>\n"
     ."</table></p>\n");

html_footer();

//
// End of "$Id$".
//
?>
