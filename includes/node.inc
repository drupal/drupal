<?php

$status = array(dumped => 0, expired => 1, queued => 2, posted => 3, scheduled => 4);

function _node_get($field, $value) {
  $result = db_query("SELECT lid, type FROM node WHERE $field = '$value'");
  if ($node = db_fetch_object($result)) {
    return db_query("SELECT n.*, l.*, u.userid FROM node n LEFT JOIN $node->type l ON n.lid = l.lid AND n.nid = l.nid LEFT JOIN users u ON n.author = u.id WHERE n.$field = '$value' ORDER BY n.timestamp DESC");
  }
}

function node_get_object($field, $value) {
  return db_fetch_object(_node_get($field, $value));
}

function node_get_array($field, $value) {
  return db_fetch_array(_node_get($field, $value));
}

function node_del($field, $value) {
  global $status;
  if ($node = node_get_object($field, $value)) {
    if ($node->status == $status[dumped]) {
      db_query("DELETE FROM node WHERE nid = '$node->nid'");
      db_query("DELETE FROM $node->type WHERE lid = '$node->lid' AND nid = '$node->nid'");
      db_query("DELETE FROM comments WHERE lid = '$node->nid'");
      watchdog("message", "node: deleted '$node->title'");
      return $node;
    }
  }
}

function node_save($node) {
  global $user, $status;

  $rows = array(nid, pid, lid, log, type, title, score, votes, author, status, timestamp);

  if ($node[nid] > 0) {
    $u1 = array();
    $u2 = array();

    foreach ($node as $field=>$value) {
      if (in_array($field, $rows)) {
        array_push($u1, check_input($field) ." = '". check_input($value) ."'");
      }
      else {
        array_push($u2, check_input($field) ." = '". check_input($value) ."'");
      }
    }

    $u1 = implode(", ", $u1);
    $u2 = implode(", ", $u2);

    db_query("UPDATE node SET $u1 WHERE nid = '$node[nid]'");
    db_query("UPDATE $node[type] SET $u2 WHERE nid = '$node[nid]'");

    watchdog("message", "node: modified '$node[title]'");
  }
  else {
    $duplicate = node_get_object("title", $node[title]);

    if ($duplicate && (time() - $duplicate->timestamp < 300)) {
      watchdog("warning", "node: duplicate '$node[title]'");
    }
    else {
      // setup default values:
      $node = array_merge(array(title => "?", author => $user->id, type => "?", pid => 0, log => "node created", status => $status[queued], score => 0, votes => 0, timestamp => time()), $node);

      // prepare queries:
      $f1 = array();
      $v1 = array();
      $f2 = array();
      $v2 = array();

      foreach ($node as $field=>$value) {
        if (in_array($field, $rows)) {
          array_push($f1, check_input($field));
          array_push($v1, "'". check_input($value) ."'");
        }
        else {
          array_push($f2, check_input($field));
          array_push($v2, "'". check_input($value) ."'");
        }
      }

      $f1 = implode(", ", $f1);
      $v1 = implode(", ", $v1);
      $f2 = implode(", ", $f2);
      $v2 = implode(", ", $v2);

      // insert data, try to roll-back when something goes wrong:
      $result = db_query("INSERT INTO node ($f1) VALUES ($v1)");
      if ($result && $nid = db_insert_id()) {
        $result = db_query("INSERT INTO $node[type] ($f2, nid) VALUES ($v2, $nid)");
        if ($result && $lid = db_insert_id()) {
          $result = db_query("UPDATE node SET lid = '$lid' WHERE nid = '$nid'");
          if ($result) {
            watchdog("message", "node: added '$node[title]'");
          }
          else {
            watchdog("warning", "node: added '$node[title]' - failed");
          }
        }
        else {
          db_query("DELETE FROM node WHERE nid = '$nid'");
          watchdog("warning", "node: added '$node[title]' - failed");
        }
      }
      else {
        watchdog("warning", "node: added '$node[title]' - failed");
      }
    }
  }

  if (($node[pid]) && ($node[status] == $status[posted])) {
    db_query("UPDATE node SET status = '$status[expired]' WHERE nid = '$node[pid]'");
  }
}

function node_view($node, $page) {
  if ($node->type) {
    $function = $node->type ."_view";
    return $function($node, $page);
  }
}

function node_form($node) {
  if ($node[type]) {
    $function = $node[type] ."_form";
    return $function($node);
  }
}

function node_info($node) {
  global $REQUEST_URI;

 ?>
  <SCRIPT>
   <!--//
     function visit(site) {
       if (site != "") {
         parent.location = site
       }
     }
   //-->
  </SCRIPT>
 <?php

  $choices = array("node.php?id=$node->nid" => t("view node"), "submit.php?mod=$node->type" => t("add node"), "submit.php?mod=$node->type&op=update&id=$node->nid" => t("update node"),  "node.php?op=history&id=$node->nid" => t("view history"));

  $output .= "<FORM METHOD=\"get\" ACTION=\"\">\n";
  foreach ($choices as $key => $value) $options .= "<OPTION VALUE=\"$key\"". ("/$key" == $REQUEST_URI ? " SELECTED" : "") .">". check_select($value) ."</OPTION>\n";
  $output .= " <SELECT NAME=\"op\" ONCHANGE=\"visit(this.options[this.selectedIndex].value)\">$options</SELECT>\n";
  $output .= "</FORM>\n";

  return $output;
}

function node_visible($node) {
  global $user, $status;
  return ($node->status == $status[posted]) || ($node->status == $status[queued] && $user->id) || user_access($user, "node");
}

function node_post_threshold($node, $threshold = 5) {
  return 3;
}

function node_dump_threshold($node, $threshold = - 3) {
  return -2;
}

function node_timout_threshold($node, $threshold = 10) {
  return 9;
}

?>
