<?PHP

include "functions.inc";
include "theme.inc";

$theme->header();

### Initialize variables:
$number = ($user->storynum) ? $user->storynum : 10;
$date = ($date) ? $date : time();

### Perform query:
$result = db_query("SELECT * FROM stories WHERE status = 1 AND time <= $date ORDER BY sid DESC LIMIT $number");

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
  $query = db_query("SELECT sid FROM comments WHERE sid = $story->sid");
  if (!$query) { $count = 0; } else { $count = mysql_num_rows($query); }
  $morelink .= "<A HREF=\"article.php?sid=$story->sid";
  if (isset($user->umode)) { $morelink .= "&mode=$user->umode"; } else { $morelink .= "&mode=threaded"; }
  if (isset($user->uorder)) { $morelink .= "&order=$user->uorder"; } else { $morelink .= "&order=0"; }
  if (isset($user->thold)) { $morelink .= "&thold=$user->thold"; } else { $morelink .= "&thold=0"; }
  $morelink .= "\"><FONT COLOR=\"$theme->hlcolor2\">$count comments</FONT></A> ]";

  ### Display story:
  $theme->abstract($story->aid, $story->informant, $story->time, stripslashes($story->subject), stripslashes($story->abstract), stripslashes($story->comments), $story->category, $story->department, $morelink);
}

$theme->footer();

?>
