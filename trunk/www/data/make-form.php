#!/usr/bin/php -q
<?php

// Make sure that the module is loaded...
if (!extension_loaded("sqlite"))
{
  dl("sqlite.so");
}


// Validate args...
global $_SERVER;

$argc = $_SERVER["argc"];
$argv = $_SERVER["argv"];

if ($argc != 4)
{
  $fp = fopen("php://stderr", "w");
  fwrite($fp, "Usage: ./make-form.php filename.db tablename \"Table Name\"\n");
  exit();
}

// Open the database connection...
$db    = sqlite_open($argv[1]);
$table = $argv[2];
$tname = $argv[3];

$result = sqlite_query($db, "PRAGMA table_info('$table')");

//sqlite_seek($result, 0);
//while ($row = sqlite_fetch_array($result))
//  print_r($row);
//exit();

print("<?php\n");
print("//\n");
print("// \"\$Id\$\"\n");
print("//\n");
print("// Web form for the $table table...\n");
print("//\n");
print("\n");
print("\n");
print("//\n");
print("// Include necessary headers...\n");
print("//\n");
print("\n");
print("include_once \"phplib/html.php\";\n");
print("include_once \"phplib/common.php\";\n");
print("\n");
print("\n");
print("// Get command-line options...\n");
print("//\n");
print("// Usage: $table.php [operation]\n");
print("//\n");
print("// Operations:\n");
print("//\n");
print("// D#        - Delete $tname\n");
print("// L         = List all $tnames\n");
print("// L#        = List $tname #\n");
print("// M#        = Modify $tname #\n");
print("// N         = Create new $tname\n");
print("\n");
print("\n");
print("if (\$argc)\n");
print("{\n");
print("  \$op = \$argv[0][0];\n");
print("  \$id = (int)substr(\$argv[0], 1);\n");
print("\n");
print("  if (\$op != 'D' && \$op != 'L' && \$op != 'M' && \$op != 'N')\n");
print("  {\n");
print("    html_header(\"$tname Error\");\n");
print("    print(\"<p>Bad command '\$op'!\\n\");\n");
print("    html_footer();\n");
print("    exit();\n");
print("  }\n");
print("\n");
print("  if ((\$op == 'D' || \$op == 'M') && !\$id)\n");
print("  {\n");
print("    html_header(\"$tname Error\");\n");
print("    print(\"<p>Command '\$op' requires an ID!\\n\");\n");
print("    html_footer();\n");
print("    exit();\n");
print("  }\n");
print("\n");
print("  if ((\$op == 'D' || \$op == 'M') && \$LOGIN_USER == \"\")\n");
print("  {\n");
print("    html_header(\"$tname Error\");\n");
print("    print(\"<p>Command '\$op' requires a login!\\n\");\n");
print("    html_footer();\n");
print("    exit();\n");
print("  }\n");
print("\n");
print("  if (\$op == 'N' && \$id)\n");
print("  {\n");
print("    html_header(\"$tname Error\");\n");
print("    print(\"<p>Command '\$op' may not have an ID!\\n\");\n");
print("    html_footer();\n");
print("    exit();\n");
print("  }\n");
print("}\n");
print("else\n");
print("{\n");
print("  \$op = 'L';\n");
print("  \$id = 0;\n");
print("}\n");
print("\n");
print("switch (\$op)\n");
print("{\n");
print("  case 'D' : // Delete $tname\n");
print("      if (\$REQUEST_METHOD == \"POST\")\n");
print("      {\n");
print("        db_query(\"DELETE FROM $table WHERE id = \$id\");\n");
print("\n");
print("        header(\"Location: \$PHP_SELF?L\");\n");
print("      }\n");
print("      else\n");
print("      {\n");
print("        \$result = db_query(\"SELECT * FROM $table WHERE id = \$id\");\n");
print("	if (db_count(\$result) != 1)\n");
print("	{\n");
print("	  print(\"<p><b>Error:</b> $tname #\$id was not found!</p>\\n\");\n");
print("	  html_footer();\n");
print("	  exit();\n");
print("	}\n");
print("\n");
print("        \$row = db_next(\$result);\n");
print("\n");
print("        html_header(\"Delete $tname #\$id\");\n");
print("\n");
print("	html_start_links(1);\n");
print("	html_link(\"Return to $tname List\", \"\$PHP_SELF?L\");\n");
print("	html_link(\"View $tname #\$id</A>\", \"\$PHP_SELF?L\$id\");\n");
print("	html_link(\"Modify $tname #\$id</A>\", \"\$PHP_SELF?M\$id\");\n");
print("	html_end_links();\n");
print("\n");
print("        print(\"<h1>Delete $tname #\$id</h1>\\n\");\n");
print("        print(\"<form method='post' action='\$PHP_SELF?D\$id'>\"\n");
print("	     .\"<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\\n\");\n");
print("\n");

print("        if (!\$row['is_published'])\n");
print("	  print(\"<tr><th align='center' colspan='2'>This $tname is \"\n");
print("	       .\"currently hidden from public view.</td></tr>\\n\");\n");
print("\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
{
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_date" :
    case "modify_user" :
    case "is_published" :
        break;

    default :
	$name = ucwords(str_replace('_', ' ', $row['name']));

        print("        \$temp = htmlspecialchars(\$row[\"$row[name]\"]);\n");
        print("        print(\"<tr><th align='right'>$name:</th>"
	     ."<td class='left'>\$temp</td></tr>\\n\");\n");
	print("\n");
        break;
  }
}

print("        print(\"<tr><th colspan='2'>\"\n");
print("	     .\"<input type='submit' value='Confirm Delete $tname'></th></tr>\\n\");\n");
print("        print(\"</table></p></form>\\n\");\n");
print("\n");
print("        html_footer();\n");
print("      }\n");
print("      break;\n");
print("\n");
print("  case 'L' : // List (all) $tname(s)\n");
print("      if (\$id)\n");
print("      {\n");
print("        html_header(\"$tname #\$id\");\n");
print("\n");
print("        \$result = db_query(\"SELECT * FROM $table WHERE id = \$id\");\n");
print("	if (db_count(\$result) != 1)\n");
print("	{\n");
print("	  print(\"<p><b>Error:</b> $tname #\$id was not found!</p>\\n\");\n");
print("	  html_footer();\n");
print("	  exit();\n");
print("	}\n");
print("\n");
print("        \$row = db_next(\$result);\n");
print("\n");
print("	html_start_links(1);\n");
print("	html_link(\"Return to $tname List\", \"\$PHP_SELF?L\");\n");
print("	html_link(\"Modify $tname</A>\", \"\$PHP_SELF?M\$id\");\n");
print("	html_link(\"Delete $tname #\$id</A>\", \"\$PHP_SELF?D\$id\");\n");
print("	html_end_links();\n");
print("\n");
print("        print(\"<h1>$tname #\$id</h1>\\n\");\n");
print("        print(\"<p><table width='100%' cellpadding='5' cellspacing='0' \"\n");
print("	     .\"border='0'>\\n\");\n");
print("\n");
print("        if (!\$row['is_published'])\n");
print("	  print(\"<tr><th align='center' colspan='2'>This $tname is \"\n");
print("	       .\"currently hidden from public view.</td></tr>\\n\");\n");
print("\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
{
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_date" :
    case "modify_user" :
    case "is_published" :
        break;

    default :
	$name = ucwords(str_replace('_', ' ', $row['name']));

        if ($row['type'] == "TEXT")
          print("        \$temp = format_text(\$row['$row[name]']);\n");
        else
          print("        \$temp = htmlspecialchars(\$row['$row[name]']);\n");
        print("        print(\"<tr><th align='right' valign='top'>$name:</th>"
	     ."<td class='left'>\$temp</td></tr>\\n\");\n");
	print("\n");
        break;
  }
}

print("        print(\"</table></p>\\n\");\n");
print("        db_free(\$result);\n");
print("      }\n");
print("      else\n");
print("      {\n");
print("        html_header(\"$tname List\");\n");
print("\n");
print("	html_start_links(1);\n");
print("	html_link(\"New $tname\", \"\$PHP_SELF?N\");\n");
print("	html_end_links();\n");
print("\n");
print("        \$result = db_query(\"SELECT * FROM $table\");\n");
print("        \$count  = db_count(\$result);\n");
print("\n");
print("        print(\"<h1>$tname List</h1>\\n\");\n");
print("        if (\$count == 0)\n");
print("	{\n");
print("	  print(\"<p>No ${tname}s found.</p>\\n\");\n");
print("\n");
print("	  html_footer();\n");
print("	  exit();\n");
print("	}\n");
print("\n");
print("        html_start_table(array(\"ID\"");

sqlite_seek($result, 0);

$list_columns = 0;
while ($row = sqlite_fetch_array($result))
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_user" :
    case "is_published" :
    case "abstract" :
    case "contents" :
        break;

    case "modify_date" :
	print(",\"Last Modified\"");
	$list_columns ++;
        break;

    default :
	$name = ucwords(str_replace('_', ' ', $row['name']));
	print(",\"$name\"");
	$list_columns ++;
        break;
  }

print("));\n");
print("\n");
print("	while (\$row = db_next(\$result))\n");
print("	{\n");
print("          html_start_row();\n");
print("\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
  switch ($row['name'])
  {
    case "id" :
        print("          \$id = \$row['id'];\n\n");
	print("          print(\"<td align='center'><a href='\$PHP_SELF?L\$id' \"\n");
	print("	       .\"alt='$tname #\$id'>\"\n");
	print("	       .\"\$id</a></td>\");\n");
        print("\n");
        break;

    case "modify_date" :
	print("          \$temp = date(\"M d, Y\", \$row['modify_date']);\n");
	print("          print(\"<td align='center'><a href='\$PHP_SELF?L\$id' \"\n");
	print("	       .\"alt='$tname #\$id'>\"\n");
	print("	       .\"\$temp</a></td>\");\n");
        print("\n");
        break;

    case "create_date" :
    case "create_user" :
    case "modify_user" :
    case "is_published" :
    case "contents" :
    case "abstract" :
        break;

    default :
	print("          \$temp = htmlspecialchars(\$row['$row[name]']);\n");
	print("          print(\"<td align='center'><a href='\$PHP_SELF?L\$id' \"\n");
	print("	       .\"alt='$tname #\$id'>\"\n");
	print("	       .\"\$temp</a></td>\");\n");
        print("\n");
        break;
  }

print("          html_end_row();\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
  if ($row['name'] == "abstract")
  {
    print("\n");
    print("          html_start_row();\n");
    print("          \$temp = htmlspecialchars(\$row['abstract']);\n");
    print("          print(\"<td></td><td colspan='$list_columns'>\$temp</td>\");\n");
    print("          html_end_row();\n");
  }

print("	}\n");
print("\n");
print("        html_end_table();\n");
print("      }\n");
print("\n");
print("      html_footer();\n");
print("      break;\n");
print("\n");

print("  case 'M' : // Modify $tname\n");
print("      if (\$REQUEST_METHOD == \"POST\")\n");
print("      {\n");
print("        \$date = time();\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_date" :
    case "modify_user" :
        break;

    default :
        $form = strtoupper($row['name']);
	
	print("	\$$row[name] = db_escape(\$_POST[\"$form\"]);\n");
        break;
  }

print("\n");
print("        db_query(\"UPDATE $table SET \"\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_date" :
    case "modify_user" :
        break;

    default :
	if ($row['type'] == "INTEGER")
          print("	        .\"$row[name] = \$$row[name], \"\n");
	else
          print("	        .\"$row[name] = '\$$row[name]', \"\n");
	break;
  }

print("	        .\"modify_date = \$date, \"\n");
print("	        .\"modify_user = '\$LOGIN_USER' \"\n");
print("	        .\"WHERE id = \$id\");\n");
print("\n");
print("	header(\"Location: \$PHP_SELF?L\$id\");\n");
print("      }\n");
print("      else\n");
print("      {\n");
print("        html_header(\"Modify $tname #\$id\");\n");
print("\n");
print("	html_start_links(1);\n");
print("	html_link(\"Return to $tname List\", \"\$PHP_SELF?L\");\n");
print("	html_link(\"$tname #\$id\", \"\$PHP_SELF?L\$id\");\n");
print("	html_end_links();\n");
print("\n");
print("        print(\"<h1>Modify $tname #\$id</h1>\\n\");\n");
print("        \$result = db_query(\"SELECT * FROM $table WHERE id = \$id\");\n");
print("	if (db_count(\$result) != 1)\n");
print("	{\n");
print("	  print(\"<p><b>Error:</b> $tname #\$id was not found!</p>\\n\");\n");
print("	  html_footer();\n");
print("	  exit();\n");
print("	}\n");
print("\n");
print("        \$row = db_next(\$result);\n");
print("\n");
print("        print(\"<form method='post' action='\$PHP_SELF?M\$id'>\"\n");
print("	     .\"<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\\n\");\n");
print("\n");
print("        print(\"<tr><th align='right'>Published:</th><td>\");\n");
print("	select_is_published(\$row['is_published']);\n");
print("	print(\"</td></tr>\\n\");\n");
print("\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_date" :
    case "modify_user" :
    case "is_published" :
        break;

    default :
        $form = strtoupper($row['name']);
	$name = ucwords(str_replace('_', ' ', $row['name']));
	print("        \$temp = htmlspecialchars(\$row['$row[name]'], ENT_QUOTES);\n");
	print("        print(\"<tr><th align='right'>$name:</th>\"\n");

        if ($row['type'] == "TEXT")
	{
	  print("	     .\"<td><textarea name='$form' \"\n");
	  print("	     .\"cols='80' rows='10' wrap='virtual'>\"\n");
	  print("	     .\"\$temp</textarea></td></tr>\\n\");\n");
        }
	else
	{
	  print("	     .\"<td><input type='text' name='$form' \"\n");
	  print("	     .\"value='\$temp' size='40'></td></tr>\\n\");\n");
	}

	print("\n");
        break;
  }

print("        print(\"<tr><th colspan='2'>\"\n");
print("	     .\"<input type='submit' value='Update $tname'></th></tr>\\n\");\n");
print("        print(\"</table></p></form>\\n\");\n");
print("\n");
print("        html_footer();\n");
print("      }\n");
print("      break;\n");
print("\n");
print("  case 'N' : // Post new $tname\n");
print("      if (\$REQUEST_METHOD == \"POST\")\n");
print("      {\n");
print("        \$date         = time();\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_date" :
    case "modify_user" :
        break;

    default :
        $form = strtoupper($row['name']);
	if ($row['type'] == "INTEGER")
	  print("	\$$row[name] = db_escape(\$_POST[\"$form\"]);\n");
	else
	  print("	\$$row[name] = db_escape(\$_POST[\"$form\"]);\n");
	break;
  }

print("\n");
print("        db_query(\"INSERT INTO $table VALUES(NULL,\"\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_date" :
    case "modify_user" :
        break;

    default :
	if ($row['type'] == "INTEGER")
          print("	        .\"\$$row[name],\"\n");
	else
          print("	        .\"'\$$row[name]',\"\n");
	break;
  }

print("		.\"\$date,'\$LOGIN_USER',\$date,'\$LOGIN_USER')\");\n");
print("\n");
print("	\$id = db_insert_id();\n");
print("\n");
print("	header(\"Location: \$PHP_SELF?L\$id\");\n");
print("	break;\n");
print("      }\n");
print("\n");
print("      html_header(\"New $tname\");\n");
print("\n");
print("      html_start_links(1);\n");
print("      html_link(\"Return to $tname List\", \"\$PHP_SELF?L\");\n");
print("      html_end_links();\n");
print("\n");
print("      print(\"<h1>New $tname</h1>\\n\");\n");
print("      print(\"<form method='post' action='\$PHP_SELF?N'>\"\n");
print("	   .\"<p><table width='100%' cellpadding='5' cellspacing='0' border='0'>\\n\");\n");
print("\n");
print("      if (\$LOGIN_USER != \"\")\n");
print("      {\n");
print("        print(\"<tr><th align='right'>Published:</th><td>\");\n");
print("        select_is_published();\n");
print("        print(\"</td></tr>\\n\");\n");
print("      }\n");
print("      else\n");
print("        print(\"<input type='hidden' name='IS_PUBLISHED' value='0'/>\\n\");\n");
print("\n");

sqlite_seek($result, 0);
while ($row = sqlite_fetch_array($result))
  switch ($row['name'])
  {
    case "id" :
    case "create_date" :
    case "create_user" :
    case "modify_date" :
    case "modify_user" :
    case "is_published" :
        break;

    default :
        $form = strtoupper($row['name']);
	$name = ucwords(str_replace('_', ' ', $row['name']));

	print("      print(\"<tr><th align='right'>$name:</th>\"\n");

        if ($row['type'] == "TEXT")
	{
	  print("	   .\"<td><textarea name='$form' \"\n");
	  print("	   .\"cols='80' rows='10' wrap='virtual'>\"\n");
	  print("	   .\"</textarea></td></tr>\\n\");\n");
        }
	else
	{
	  print("	   .\"<td><input type='text' name='$form' \"\n");
	  print("	   .\"size='40'></td></tr>\\n\");\n");
	}

	print("\n");
        break;
  }

print("      print(\"<tr><th colspan='2'>\"\n");
print("	   .\"<input type='submit' value='Create $tname'></th></tr>\\n\");\n");
print("      print(\"</table></p></form>\\n\");\n");
print("\n");
print("      html_footer();\n");
print("      break;\n");
print("}\n");
print("\n");
print("\n");
print("//\n");
print("// End of \"\$Id\$\".\n");
print("//\n");
print("?>\n");

?>
