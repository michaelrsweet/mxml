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

?>

<h1><img src='images/logo-large.gif' style='margin-left: 20px; float: right;'
width='150' height='150' alt='Mini-XML logo'>Mini-XML: Lightweight XML
Library</h1>

<p>Mini-XML is a small XML library that you can use to read and write XML and
XML-like data files in your application without requiring large non-standard
libraries. Mini-XML only requires an ANSI C compatible compiler (GCC works, as
do most vendors' ANSI C compilers) and a 'make' program.</p>

<p>Mini-XML provides the following functionality:</p>

<ul>

	<li>Reading of UTF-8 and UTF-16 and writing of UTF-8 encoded
	XML files and strings.</li>

	<li>Data is stored in a linked-list tree structure,
	preserving the XML data hierarchy.</li>

	<li>Supports arbitrary element names, attributes, and
	attribute values with no preset limits, just available
	memory.</li>

	<li>Supports integer, real, opaque ("cdata"), and text data
	types in "leaf" nodes.</li>

	<li>Functions for creating, indexing, and managing trees of
	data.</li>

	<li>"Find" and "walk" functions for easily locating and
	navigating trees of data.</li>

</ul>

<table width='100%' cellpadding='0' cellspacing='0' border='0' summary=''>
<tr><td valign='top'>

<h2>Documentation</h2>

<p><a href='documentation.php/intro.html'>Introduction</a></p>

<p><a href='documentation.php/install.html'>Building, Installing, and Packaging
Mini-XML</a></p>

<p><a href='documentation.php/basics.html'>Getting Started with Mini-XML</a></p>

<p><a href='documentation.php/advanced.html'>More Mini-XML Programming
Techniques</a></p>

<p><a href='documentation.php/mxmldoc.html'>Using the mxmldoc Utility</a></p>

<p><a href='documentation.php/license.html'>Mini-XML License</a></p>

<p><a href='documentation.php/relnotes.html'>Release Notes</a></p>

<p><a href='documentation.php/reference.html'>Library Reference</a></p>

</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td valign='top'>

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
