<?

$type = array("addresses" => 0x01,
              "profanity" => 0x02,
              "hostnames" => 0x03,
              "usernames" => 0x04);

function ban_match($mask, $category) {
  ### Connect to database:
  db_connect();

  ### Perform query:
  $result = db_query("SELECT * FROM bans WHERE type = $category AND '$mask' LIKE mask");
  
  ### Return result:
  return db_fetch_object($result);
}

?>
