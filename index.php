<?PHP

include "functions.inc";

### Log valid referers:
if (($url) && (strstr(getenv("HTTP_REFERER"), $url))) {
  addRefer($url);
}


include "theme.inc";
$theme->header();

dbconnect();

if (isset($user->storynum)) $number = $user->storynum; else $number = 10;

$result = mysql_query("SELECT * FROM stories ORDER BY sid DESC LIMIT $number");

while ($story = mysql_fetch_object($result)) {

  ### Compose more-link:
  $morelink = "[ ";
  if ($story->article) {
    $morelink .= "<A HREF=\"article.php?sid=$story->sid";
    if (isset($user->umode)) { $morelink .= "&mode=$user->umode"; } else { $morelink .= "&mode=threaded"; }
    if (isset($user->uorder)) { $morelink .= "&order=$user->uorder"; } else { $morelink .= "&order=0"; }
    $bytes = strlen($story->article);
    $morelink .= "\"><FONT COLOR=\"$theme->hlcolor2\"><B>read more</B></FONT></A> | $bytes bytes in body | "; 
  }

  $query = mysql_query("SELECT sid FROM comments WHERE sid = $story->sid");
  if (!$query) { $count = 0; } else { $count = mysql_num_rows($query); }

  $morelink .= "<A HREF=\"article.php?sid=$story->sid";
  if (isset($user->umode)) { $morelink .= "&mode=$user->umode"; } else { $morelink .= "&mode=threaded"; }
  if (isset($user->uorder)) { $morelink .= "&order=$user->uorder"; } else { $morelink .= "&order=0"; }
  if (isset($user->thold)) { $morelink .= "&thold=$user->thold"; } else { $morelink .= "&thold=0"; }
  $morelink .= "\"><FONT COLOR=\"$theme->hlcolor2\">$count comments</FONT></A> ]";

  $theme->abstract($story->aid, $story->informant, $story->time, stripslashes($story->subject), stripslashes($story->abstract), stripslashes($story->comments), $story->category, $story->department, $morelink);
}

mysql_free_result($result);

$theme->footer();

?>
