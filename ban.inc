<?

$type = array("addresses" => 0x01,
              "profanity" => 0x02,
              "hostnames" => 0x03,
              "usernames" => 0x04);

function ban_match($mask, $category) {
  ### Perform query:
  $result = db_query("SELECT * FROM bans WHERE type = $category AND LOWER('$mask') LIKE LOWER(mask)");
  
  ### Return result:
  return db_fetch_object($result);
}

function ban_add($mask, $category, $reason, $message = "") {
  if (empty($mask)) {
    $message = "Failed: empty banmasks are not allowed.<P>\n";
  }
  else if ($ban = db_fetch_object(db_query("SELECT * FROM bans WHERE type = $category AND '$mask' LIKE mask"))) {
    $message = "Failed: ban is already matched by '$ban->mask'.<P>\n";
  }
  else {
    $result = db_query("INSERT INTO bans (mask, type, reason, timestamp) VALUES ('$mask', '$category', '$reason', '". time() ."')");
    $message = "Added new ban with mask `$mask'.<P>\n";
  }
}
  
function ban_delete($id) {
  ### Perform query:
  $result = db_query("DELETE FROM bans WHERE id = $id");
}

?>
