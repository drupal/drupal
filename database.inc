<?
/*
 * These functions build the foundation for accessing the database:
 * it is a general database abstraction layer suitable for several
 * databases.  Currently, the only supported database is MySQL but
 * it should be straightforward to port it to any other database:
 * just adjust the handlers to your needs.
 */

///////////////////////////////////////////////////////////////////////////
// NOTE: these functions are under construction and in no way finilized! //
///////////////////////////////////////////////////////////////////////////

function db_connect() {
  include "config.inc";
  mysql_pconnect($dbhost, $dbuname, $dbpass) or die(mysql_Error());
  mysql_select_db("$dbname") or die ("Unable to select database");
   // Note: we are using a persistent connection!
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

function db_num_rows($qid) {
  if ($qid) return mysql_num_rows($qid);
}

function db_fetch_row($qid) {
  if ($qid) return mysql_fetch_row($qid);
}

function db_fetch_array($qid) {
  if ($qid) return mysql_fetch_array($qid);
}

function db_result($qid, $field) {
  if ($qid) return mysql_result($qid, $field);
}

#
# Automatically connect to database:
#
db_connect();

?>