<?

function plural($count, $one, $more) {
  return ($count == 1) ? "$count $one" : "$count $more";
}

function username($username) {
  include "config.inc";
  return ($username) ? $username : $anonymous;
}

function discussion_num_replies($id, $count = 0) {
  $result = db_query("SELECT COUNT(cid) FROM comments WHERE pid = $id");
  return ($result) ? mysql_result($result, 0) : 0;
}

?>