<?php

$type2index = array("addresses" => 0x01,
                    "profanity" => 0x02,
                    "hostnames" => 0x03,
                    "usernames" => 0x04);

$index2type = array(0x01 => "addresses",
                    0x02 => "profanity",
                    0x03 => "hostnames",
                    0x04 => "usernames");

function ban_match($mask, $category) {
  // Perform query:
  $result = db_query("SELECT * FROM bans WHERE type = '$category' AND LOWER('$mask') LIKE LOWER(mask)");

  // Return result:
  return db_fetch_object($result);
}

function ban_add($mask, $category, $reason, $message = "") {
  global $index2type;

  if (empty($mask)) {
    $message = "failed: empty banmasks are not allowed.<P>\n";
  }
  else if ($ban = db_fetch_object(db_query("SELECT * FROM bans WHERE type = '$category' AND '$mask' LIKE mask"))) {
    $message = "failed: ban is already matched by '$ban->mask'.<P>\n";
  }
  else {
    $result = db_query("INSERT INTO bans (mask, type, reason, timestamp) VALUES ('$mask', '$category', '$reason', '". time() ."')");
    $message = "added new ban with mask '$mask'.<P>\n";

    // Add log entry:
    watchdog("message", "added new ban '$mask' to category '". $index2type[$category] ."' with reason '$reason'.");
  }
}

function ban_delete($id) {
  global $index2type;

  $result = db_query("SELECT * FROM bans WHERE id = '$id'");

  if ($ban = db_fetch_object($result)) {
    // Perform query:
    $result = db_query("DELETE FROM bans WHERE id = '$id'");

    // Deleted log entry:
    watchdog("message", "removed ban '$ban->mask' from category '". $index2type[$ban->type] ."'.");
  }
}

?>
