<?

include "includes/common.inc";

### Security check:
if (strstr($number, " ") || strstr($date, " ")) {
  watchdog("error", "main page: attempt to provide malicious input through URI");
  exit();
}

### Initialize/pre-process variables:
$number = ($user->stories) ? $user->stories : 10;
$date = ($date) ? $date : time();

### Perform query:
$result = db_query("SELECT stories.*, users.userid, COUNT(comments.sid) AS comments FROM stories LEFT JOIN comments ON stories.id = comments.sid LEFT JOIN users ON stories.author = users.id WHERE stories.status = 2 AND stories.timestamp <= $date GROUP BY stories.id ORDER BY stories.timestamp DESC LIMIT $number");

### Display stories:
$theme->header();
while ($story = db_fetch_object($result)) $theme->abstract($story);
$theme->footer();

?>
