<?

function t($string) {
  $result = db_query("SELECT id FROM locales WHERE english = '". addslashes($string) ."'");
  if (!db_fetch_object($result)) db_query("INSERT INTO locales (english, location) VALUES ('". addslashes($string) ."', '". check_input(getenv("REQUEST_URI")) ."')");
  return $string;
}

?>