<?
//
// "$Id: poll.php,v 1.1 2004/05/20 03:38:42 mike Exp $"
//
// Common poll interface functions...
//
// This file should be included using "include_once"...
//
// Contents:
//
//   get_recent_poll() - Get the most recent poll...
//   show_poll()       - Show a poll...
//


//
// Include necessary headers...
//

include_once "db.php";


//
// Constants for poll_type column...
//

$POLL_TYPE_PICKONE  = 0;
$POLL_TYPE_PICKMANY = 1;


//
// 'get_recent_poll()' - Get the most recent poll...
//

function				// O - Poll ID or 0
get_recent_poll()
{
  $result = db_query("SELECT id FROM poll WHERE is_published = 1 "
                    ."ORDER BY id DESC LIMIT 1");
  $row    = db_next($result);
  $id     = (int)$row['id'];

  db_free($result);

  return ($id);
}


//
// 'show_poll()' - Show a poll...
//

function
show_poll($id)				// I - Poll ID
{
  global $PHP_SELF, $POLL_TYPE_PICKONE, $POLL_TYPE_PICKMANY;


  $result = db_query("SELECT * FROM poll WHERE is_published = 1 AND id = $id");

  if (db_count($result) == 1)
  {
    $row      = db_next($result);
    $id       = $row['id'];
    $question = htmlspecialchars($row['question']);

    print("<p><form method='POST' action='poll.php?v$row[id]'>"
	 ."<b>$question</b>\n");

    if ($row['poll_type'] == $POLL_TYPE_PICKONE)
      print("(please pick one)\n");
    else
      print("(pick all that apply)\n");

    for ($i = 0; $i < 10; $i ++)
    {
      $answer = htmlspecialchars($row["answer$i"]);

      if ($answer != "")
      {
	if ($row['poll_type'] == $POLL_TYPE_PICKONE)
          print("<br /><input type='radio' name='ANSWER'");
	else
          print("<br /><input type='checkbox' name='ANSWER$i'");

	print(" value='$i'/>$answer\n");
      }
    }

    $votes = $row['votes'];
    if ($votes == 1)
      $votes .= "&nbsp;vote";
    else
      $votes .= "&nbsp;votes";

    $ccount = count_comments("poll.php_r$id");
    if ($ccount == 1)
      $ccount .= "&nbsp;comment";
    else
      $ccount .= "&nbsp;comments";

    print("<br /><input type='submit' value='Vote'/>\n"
	 ."[&nbsp;<a href='poll.php?r$id'>Results</a>&nbsp;]\n");
    print("<br />($votes, $ccount)</form></p>\n");
  }

  db_free($result);
}


//
// End of "$Id: poll.php,v 1.1 2004/05/20 03:38:42 mike Exp $".
//
?>
