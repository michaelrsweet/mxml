<?php
//
// "$Id: software.php,v 1.2 2004/05/19 12:15:20 mike Exp $"
//
// Software download page.
//

//
// Include necessary headers...
//

include_once "phplib/html.php";


// Get the list of software files...
$dir   = opendir("swfiles");
$files = array();

while ($file = readdir($dir))
{
  if (fnmatch("*.tar.gz", $file) ||
      fnmatch("*.tar.bz2", $file))
  {
    // Add source file...
    $files[$file] = substr($file, 5, strpos($file, ".tar") - 5);
  }
  else if (fnmatch("*.rpm", $file) ||
	   fnmatch("*.deb", $file) ||
	   fnmatch("*.tgz", $file))
  {
    // Add binary file...
    $data = explode("-", $file);

    $files[$file] = $data[1];
  }
}

arsort($files);

closedir($dir);

// Read MD5 sums for each file...
$fp  = fopen("swfiles/mxml.md5", "r");
$md5 = array();

while ($line = fgets($fp))
{
  $data          = explode(" ", trim($line));
  $md5[$data[2]] = $data[0];
}

//print("<pre>md5 =\n");
//print_r($md5);
//print("</pre>\n");

fclose($fp);

// Show files...
html_header("Download");

print("<h1>Download</h1>");

html_start_table(array("Version", "Filename", "Size", "MD5 Sum"));
$curversion   = "";
$firstversion = current($files);
reset($files);
while (list($file, $version) = each($files))
{
  html_start_row();

  if ($version == $firstversion)
  {
    $cs = "<th>";
    $ce = "</th>";
  }
  else
  {
    $cs = "<td align='center'>";
    $ce = "</td>";
  }

  if ($version != $curversion)
  {
    if ($curversion != "")
    {
      print("<td colspan='4'></td>");
      html_end_row();
      html_start_row();
    }

    $curversion = $version;
    print("$cs<a name='$version'>$version</a>$ce");
  }
  else
    print("$cs$ce");

  $kbytes  = (int)((filesize("swfiles/$file") + 1023) / 1024);
  $filemd5 = $md5["$file"];

  print("$cs<a href='swfiles/$file'><tt>$file</tt></a>$ce"
       ."$cs${kbytes}k$ce"
       ."$cs<tt>$filemd5</tt>$ce");

  html_end_row();
}

html_end_table();

html_footer();

//
// End of "$Id: software.php,v 1.2 2004/05/19 12:15:20 mike Exp $".
//
?>
