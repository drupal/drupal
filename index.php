<?PHP

include "functions.inc";
include "theme.inc";

$theme->header();

### Initialize variables:
$number = ($user->storynum) ? $user->storynum : 10;
$date = ($date) ? $date : time();

### Perform query:
$result = db_query("SELECT stories.*, COUNT(comments.sid) AS comments FROM stories LEFT JOIN comments ON stories.sid = comments.sid WHERE stories.status = 1 AND stories.time <= $date GROUP BY stories.sid ORDER BY stories.sid DESC LIMIT $number");
  // Note: we use a LEFT JOIN to retrieve the number of comments associated 
  //       with each story.  By retrieving this data now, we elimate a *lot* 
  //       of individual queries that would otherwise be required inside the 
  //       while-loop.  If there is no matching record for the right table in 
  //       the ON-part of the LEFT JOIN, a row with all columns set to NULL 
  //       is used for the right table.  This is required, as not every story 
  //       has a counterpart in the comments table (at a given time).

### Display stories:
while ($story = db_fetch_object($result)) {

  ### Compose more-link:
  $morelink = "[ ";
  if ($story->article) {
    $morelink .= "<A HREF=\"article.php?sid=$story->sid";
    if (isset($user->umode)) { $morelink .= "&mode=$user->umode"; } else { $morelink .= "&mode=threaded"; }
    if (isset($user->uorder)) { $morelink .= "&order=$user->uorder"; } else { $morelink .= "&order=0"; }
    $bytes = strlen($story->article);
    $morelink .= "\"><FONT COLOR=\"$theme->hlcolor2\"><B>read more</B></FONT></A> | $bytes bytes in body | "; 
  }
  $morelink .= "<A HREF=\"article.php?sid=$story->sid";
  if (isset($user->umode)) { $morelink .= "&mode=$user->umode"; } else { $morelink .= "&mode=threaded"; }
  if (isset($user->uorder)) { $morelink .= "&order=$user->uorder"; } else { $morelink .= "&order=0"; }
  if (isset($user->thold)) { $morelink .= "&thold=$user->thold"; } else { $morelink .= "&thold=0"; }
  $morelink .= "\"><FONT COLOR=\"$theme->hlcolor2\">$story->comments comments</FONT></A> ]";

  ### Display story:
  $theme->abstract($story->aid, $story->informant, $story->time, stripslashes($story->subject), stripslashes($story->abstract), stripslashes($story->comments), $story->category, $story->department, $morelink);
}

$theme->footer();

?>
