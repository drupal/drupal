<?

function db_connect() {
  include "config.inc";
  mysql_pconnect($dbhost, $dbuname, $dbpass) or die(mysql_Error());
  mysql_select_db("$dbname") or die ("Unable to select database");
}

function db_query($query, $debug = false) {
  ### perform query:
  $qid = mysql_query($query);

  ### debug output (if required):
  if ($debug || empty($qid)) {
    print "<PRE>query: ". htmlspecialchars($query) ."<BR>error message: ". mysql_error() ."</PRE>";
  }
 
  ### return result from query: 
  return $qid;
}

function db_fetch_object($qid) {
  if ($qid) return mysql_fetch_object($qid);
}

?>