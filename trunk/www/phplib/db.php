<?php
//
// "$Id: db.php,v 1.2 2004/05/17 03:23:06 mike Exp $"
//
// Common database include file for PHP web pages.  This file can be used
// to abstract the specific database in use...
//
// This version is for the SQLite database and module.
//
// This file should be included using "include_once"...
//
// Contents:
//
//   db_close()     - Close the database.
//   db_count()     - Return the number of rows in a query result.
//   db_escape()    - Escape special chars in string for query.
//   db_free()      - Free a database query result...
//   db_insert_id() - Return the ID of the last inserted record.
//   db_next()      - Fetch the next row of a result set and return it as
//                    an object.
//   db_query()     - Run a SQL query and return the result or 0 on error.
//   db_seek()      - Seek to a specific row within a result.
//

// Some static database access info.
$DB_HOST     = "";
$DB_NAME     = "mxml";
$DB_USER     = "";
$DB_PASSWORD = "";


// Make sure that the module is loaded...
if (!extension_loaded("sqlite"))
{
  dl("sqlite.so");
}


// Open the SQLite database defined above...
if ($DB_CONN = sqlite_open("data/$DB_NAME.db", 0666, $sqlerr))
{
  if (0) {
    // Opened the database, create all tables...
    sqlite_query($DB_CONN,
		 "CREATE TABLE str ("
		."id INTEGER PRIMARY KEY,"
		."master_id INTEGER,"
		."is_published INTEGER,"
		."status INTEGER,"
		."priority INTEGER,"
		."scope INTEGER,"
		."summary text,"
		."subsystem VARCHAR(128),"
		."str_version VARCHAR(16),"
		."fix_version VARCHAR(16),"
		."manager_email VARCHAR(128),"
		."create_date INTEGER,"
		."create_email VARCHAR(128),"
		."modify_date INTEGER,"
		."modify_email VARCHAR(128)"
		.")");

    sqlite_query($DB_CONN,
		 "CREATE TABLE strcc ("
		."id INTEGER PRIMARY KEY,"
		."str_id INTEGER,"
		."email VARCHAR(128)"
		.")");

    sqlite_query($DB_CONN,
		 "CREATE TABLE strfile ("
		."id INTEGER PRIMARY KEY,"
		."str_id INTEGER,"
		."is_published INTEGER,"
		."date INTEGER,"
		."email VARCHAR(128),"
		."filename VARCHAR(128)"
		.")");

    sqlite_query($DB_CONN,
		 "CREATE TABLE strtext ("
		."id INTEGER PRIMARY KEY,"
		."str_id INTEGER,"
		."is_published INTEGER,"
		."date INTEGER,"
		."email VARCHAR(128),"
		."contents TEXT"
		.")");
  }
}
else
{
  // Unable to open, display an error message...
  print("<p>Database error $sqlerr</p>\n");
  print("<p>Please report the problem to <a href='mailto:webmaster@easysw.com'>"
       ."webmaster@easysw.com</a>.</p>\n");
  exit(1);
}


//
// 'db_close()' - Close the database.
//

function
db_close()
{
  global $DB_CONN;


  sqlite_close($DB_CONN);
  $DB_CONN = false;
}


//
// 'db_count()' - Return the number of rows in a query result.
//

function				// O - Number of rows in result
db_count($result)			// I - Result of query
{
  if ($result)
    return (sqlite_num_rows($result));
  else
    return (0);
}


//
// 'db_escape()' - Escape special chars in string for query.
//

function				// O - Quoted string
db_escape($str)				// I - String
{
  return (sqlite_escape_string($str));
}


//
// 'db_free()' - Free a database query result...
//

function
db_free($result)			// I - Result of query
{
  // Nothing to do, as SQLite doesn't free results...
}


//
// 'db_insert_id()' - Return the ID of the last inserted record.
//

function				// O - ID number
db_insert_id()
{
  global $DB_CONN;

  return (sqlite_last_insert_rowid($DB_CONN));
}


//
// 'db_next()' - Fetch the next row of a result set and return it as an object.
//

function				// O - Row object or NULL at end
db_next($result)			// I - Result of query
{
  if ($result)
    return (sqlite_fetch_array($result, SQLITE_ASSOC));
  else
    return (NULL);
}


//
// 'db_query()' - Run a SQL query and return the result or 0 on error.
//

function				// O - Result of query or NULL
db_query($SQL_QUERY)			// I - SQL query string
{
  global $DB_CONN;

//  print("<p>$SQL_QUERY</p>\n");

  return (sqlite_query($DB_CONN, $SQL_QUERY));
}


//
// 'db_seek()' - Seek to a specific row within a result.
//

function				// O - TRUE on success, FALSE otherwise
db_seek($result,			// I - Result of query
        $index = 0)			// I - Row number (0 = first row)
{
  if ($result)
    return (sqlite_seek($result, $index));
  else
    return (FALSE);
}


//
// End of "$Id: db.php,v 1.2 2004/05/17 03:23:06 mike Exp $".
//
?>
