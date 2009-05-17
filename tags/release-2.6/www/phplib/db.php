<?php
//
// "$Id: db.php,v 1.5 2004/05/21 03:59:17 mike Exp $"
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
$DB_ADMIN    = "webmaster@easysw.com";
$DB_HOST     = "localhost";
$DB_NAME     = "mxml";
$DB_USER     = "mike";
$DB_PASSWORD = "";

//
// Connect to the MySQL server using DB_HOST, DB_USER, DB_PASSWORD
// that are set above...
//

$DB_CONN = mysql_connect($DB_HOST, $DB_USER, $DB_PASSWORD);

if ($DB_CONN)
{
  // Connected to server; select the database...
  mysql_select_db($DB_NAME, $DB_CONN);
}
else
{
  // Unable to connect; display an error message...
  $sqlerrno = mysql_errno();
  $sqlerr   = mysql_error();

  print("<p>Database error $sqlerrno: $sqlerr</p>\n");
  print("<p>Please report the problem to <a href='mailto:webmaster@easysw.com'>"
       ."webmaster@easysw.com</a>.</p>\n");
}


//
// 'db_count()' - Return the number of rows in a query result.
//

function				// O - Number of rows in result
db_count($result)			// I - Result of query
{
  if ($result)
    return (mysql_num_rows($result));
  else
    return (0);
}


//
// 'db_escape()' - Escape special chars in string for query.
//

function				// O - Quoted string
db_escape($str)				// I - String
{
  return (mysql_escape_string($str));
}


//
// 'db_free()' - Free a database query result...
//

function
db_free($result)			// I - Result of query
{
  if ($result)
    mysql_free_result($result);
}


//
// 'db_insert_id()' - Return the ID of the last inserted record.
//

function				// O - ID number
db_insert_id()
{
  global $DB_CONN;

  return (mysql_insert_id($DB_CONN));
}


//
// 'db_next()' - Fetch the next row of a result set and return it as an object.
//

function				// O - Row object or NULL at end
db_next($result)			// I - Result of query
{
  if ($result)
    return (mysql_fetch_array($result));
  else
    return (NULL);
}


//
// 'db_query()' - Run a SQL query and return the result or 0 on error.
//

function				// O - Result of query or NULL
db_query($SQL_QUERY)			// I - SQL query string
{
  global $DB_NAME, $DB_CONN;

  return (mysql_query($SQL_QUERY, $DB_CONN));

//  print("<p>SQL_QUERY: $SQL_QUERY</p>\n");
//
//  $result = mysql_query($SQL_QUERY, $DB_CONN);
//  $count  = db_count($result);
//  print("<p>Result = $count rows...</p>\n");
//
//  return ($result);
}


//
// 'db_seek()' - Seek to a specific row within a result.
//

function				// O - TRUE on success, FALSE otherwise
db_seek($result,			// I - Result of query
        $index = 0)			// I - Row number (0 = first row)
{
  if ($result)
    return (mysql_data_seek($result, $index));
  else
    return (FALSE);
}


//
// End of "$Id: db.php,v 1.5 2004/05/21 03:59:17 mike Exp $".
//
?>
