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

$fp   = fopen("data/software.md5", "r");
$line = fgets($fp, 255);
$data = explode(" ", $line);
fclose($fp);

$version = $data[1];
$file    = $data[2];

?>

<div style='margin-left: 20px; float: right; line-height: 200%;
text-align: center;'><a
href='software.php?FILE=<?print($file);?>&amp;VERSION=<?print($version);?>'><img
src='images/logo-large.gif' width='150' height='150' alt='Mini-XML logo'><br>
Download v<?print($version);?></a></div>

<h1>Mini-XML: Lightweight XML Library</h1>

<p>Mini-XML is a small XML library that you can use to read and write XML and
XML-like data files in your application without requiring large non-standard
libraries. Mini-XML only requires an ANSI C compatible compiler (GCC works, as
do most vendors' ANSI C compilers) and a 'make' program.</p>

<p>Mini-XML supports reading of UTF-8 and UTF-16 and writing of UTF-8 encoded
XML files and strings. Data is stored in a linked-list tree structure,
preserving the XML data hierarchy, and arbitrary element names, attributes,
and attribute values are supported with no preset limits, just available
memory.</p>

<table width='100%' cellpadding='0' cellspacing='0' border='0' summary=''>
<tr><td valign='top' width='45%'>

<h2>Documentation</h2>

<p><a href='documentation.php/intro.html'>Introduction</a></p>

<p><a href='documentation.php/basics.html'>Getting Started with Mini-XML</a></p>

<p><a href='documentation.php/advanced.html'>More Mini-XML Programming
Techniques</a></p>

<p><a href='documentation.php/license.html'>Mini-XML License</a></p>

<p><a href='documentation.php/reference.html'>Library Reference</a></p>

</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td valign='top' width='55%'>

<h2>Recent News</h2>

<?

$result = db_query("SELECT * FROM article WHERE is_published = 1 "
	          ."ORDER BY modify_date DESC LIMIT 3");
$count  = db_count($result);

while ($row = db_next($result))
{
  $id       = $row['id'];
  $title    = htmlspecialchars($row['title']);
  $abstract = htmlspecialchars($row['abstract']);
  $date     = date("H:i M d, Y", $row['modify_date']);
  $count    = count_comments("articles.php_L$id");

  if ($count == 1)
    $count .= " comment";
  else
    $count .= " comments";

  print("<p><a href='articles.php?L$id'>$title</a> - $abstract<br>\n"
       ."<span class='dateinfo'>$date, $count</span></p>\n");
}

db_free($result);

?>

</td></tr>
</table>

<? html_footer(); ?>
