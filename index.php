<?PHP

include "functions.inc";
include "theme.inc";

### Initialize/pre-process variables:
$number = ($user->storynum) ? $user->storynum : 10;
$date = ($date) ? $date : time();

### Perform query:
$result = db_query("SELECT stories.*, users.userid, COUNT(comments.sid) AS comments FROM stories LEFT JOIN comments ON stories.id = comments.sid LEFT JOIN users ON stories.author = users.id WHERE stories.status = 2 AND stories.timestamp <= $date GROUP BY stories.id ORDER BY stories.id DESC LIMIT $number");
  // Note on performance: 
  //       we use a LEFT JOIN to retrieve the number of comments associated 
  //       with each story.  By retrieving this data now (outside the while-
  //       loop), we elimate a *lot* of individual queries that would other-
  //       wise be required (inside the while-loop). If there is no matching 
  //       record for the right table in the ON-part of the LEFT JOIN, a row 
  //       with all columns set to NULL is used for the right table. This is 
  //       required, as not every story has a counterpart in the comments 
  //       table (at a given time).

### Display stories:
$theme->header();
while ($story = db_fetch_object($result)) $theme->abstract($story);
$theme->footer();

?>
