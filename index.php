<?

include_once "includes/common.inc";

// Initialize/pre-process variables:
$number = ($user->stories) ? $user->stories : 10;
$date = ($date > 0) ? $date : time();

// Perform query:
$result = db_query("SELECT stories.*, users.userid, COUNT(comments.lid) AS comments FROM stories LEFT JOIN comments ON stories.id = comments.lid LEFT JOIN users ON stories.author = users.id WHERE stories.status = 2 ". ($section ? "AND section = '$section' " : "") ."AND stories.timestamp <= $date GROUP BY stories.id ORDER BY stories.timestamp DESC LIMIT $number");

// Display stories:
$theme->header();
while ($story = db_fetch_object($result)) $theme->story($story);
$theme->footer();

?>
