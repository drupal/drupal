<?php

$status = array(dumped => 0, expired => 1, queued => 2, posted => 3);
$rstatus = array(0 => dumped, 1 => expired, 2 => queued, 3 => posted);

function _node_get($conditions) {
  foreach ($conditions as $key=>$value) {
    $cond[] = "n.". check_query($key) ." = '". check_query($value) ."'";
  }

  $where = implode(" AND ", $cond);

  if ($conditions[type]) {
    $type = $conditions[type];
  }
  else {
    $node = db_fetch_object(db_query("SELECT n.type FROM node n WHERE $where"));
    $type = $node ? $node->type : 0;
  }

  if ($type) {
    return db_query("SELECT n.*, l.*, u.uid, u.name, SUM(m.score) / COUNT(m.cid) AS score, COUNT(m.cid) AS votes FROM node n LEFT JOIN $type l ON n.lid = l.lid AND n.nid = l.nid LEFT JOIN user u ON n.author = u.uid LEFT JOIN moderate m ON m.nid = n.nid WHERE $where GROUP BY n.nid ORDER BY n.timestamp DESC");
  }
}

function node_comment_status($index = -1) {
  $status = array("Disabled", "Enabled");
  return $index < 0 ? $status : $status[$index];
}

function node_promote_status($index = -1) {
  $status = array("Disabled", "Enabled");
  return $index < 0 ? $status : $status[$index];
}

function node_submission_status($index = -1) {
  $status = array("Auto-post new submissions", "Moderate new submissions");
  return $index < 0 ? $status : $status[$index];
}

function node_get_object($conditions) {
  return db_fetch_object(_node_get($conditions));
}

function node_get_array($conditions) {
  return db_fetch_array(_node_get($conditions));
}

function node_del($conditions) {
  global $status;
  if ($node = node_get_object($conditions)) {
    module_invoke($node->type, "delete", $node);
    db_query("DELETE FROM node WHERE nid = '$node->nid'");
    db_query("DELETE FROM $node->type WHERE lid = '$node->lid' AND nid = '$node->nid'");
    db_query("DELETE FROM comments WHERE lid = '$node->nid'");
    db_query("DELETE FROM moderate WHERE nid = '$node->nid'");
    watchdog("special", "node: deleted '$node->title'");
    return $node;
  }
}

function node_get_comments($nid) {
  $comment = db_fetch_object(db_query("SELECT COUNT(c.lid) AS number FROM node n LEFT JOIN comments c ON n.nid = c.lid WHERE n.nid = '$nid' GROUP BY n.nid"));
  return $comment->number ? $comment->number : 0;
}

function node_save($node, $filter) {
  $rows = array(nid, lid, cid, tid, type, title, score, votes, author, status, comment, promote, moderate, attributes, timestamp, timestamp_posted, timestamp_queued, timestamp_hidden);

  if ($node[nid] > 0) {
    $n = node_get_object(array("nid" => $node[nid]));

    foreach ($filter as $field=>$value) {
      $f = check_input(is_numeric($field) ? $value : $field);
      $v = check_input(is_numeric($field) ? $node[$value] : $filter[$field]);

      if (in_array($f, $rows)) {
        $u1[] = check_input($f) ." = '". check_input($v) ."'";
      }
      else {
        $u2[] = check_input($f) ." = '". check_input($v) ."'";
      }
    }

    if ($u1) db_query("UPDATE node SET ". implode(", ", $u1) ." WHERE nid = '$node[nid]'");
    if ($u2) db_query("UPDATE $n->type SET ". implode(", ", $u2) ." WHERE nid = '$node[nid]'");

    if ($node[nid]) module_invoke($n->type, "update", node_get_object(array(nid => $n->nid)));

    return $node[nid];
  }
  else {
    $duplicate = node_get_object(array("title" => $node[title]));

    if ($duplicate && (time() - $duplicate->timestamp < 60)) {
      watchdog("warning", "node: duplicate '$node[title]'");
    }
    else {
      // verify submission rate:
      throttle("node", variable_get("max_node_rate", 900));

      // prepare queries:
      foreach ($filter as $field=>$value) {
        $f = check_input(is_numeric($field) ? $value : $field);
        $v = check_input(is_numeric($field) ? $node[$value] : $filter[$field]);

        if (in_array($f, $rows)) {
          $f1[] = $f;
          $v1[] = "'$v'";
        }
        else {
          $f2[] = $f;
          $v2[] = "'$v'";
        }
      }

      $f1 = implode(", ", $f1);
      $v1 = implode(", ", $v1);
      $f2 = implode(", ", $f2);
      $v2 = implode(", ", $v2);

      // insert data, try to roll-back when something goes wrong:
      $result = db_query("INSERT INTO node ($f1) VALUES ($v1)");
      if ($result && $nid = db_insert_id()) {
        $result = db_query("INSERT INTO $filter[type] ($f2, nid) VALUES ($v2, $nid)");
        if ($result && $lid = db_insert_id()) {
          $result = db_query("UPDATE node SET lid = '$lid' WHERE nid = '$nid'");
          if ($result) {
            watchdog("special", "node: added $filter[type] '$node[title]'");
          }
          else {
            watchdog("warning", "node: added $filter[type] '$node[title]' - failed");
          }
        }
        else {
          db_query("DELETE FROM node WHERE nid = '$nid'");
          watchdog("warning", "node: added $filter[type] '$node[title]' - failed");
        }
      }
      else {
        watchdog("warning", "node: added $filter[type] '$node[title]' - failed");
      }
    }

    if ($nid) module_invoke($filter[type], "insert", node_get_object(array(nid => $nid)));

    return $nid;
  }
}

function node_invoke($node, $name, $arg = 0) {
  if (is_array($node)) $function = $node[type] ."_$name";
  else if (is_object($node)) $function = $node->type ."_$name";
  else if (is_string($node)) $function = $node ."_$name";
  if (function_exists($function)) return ($arg ? $function($node, $arg) : $function($node));
}

function node_view($node, $main = 0) {
  return node_invoke($node, "view", $main);
}

function node_form($node) {
  return node_invoke($node, "form");
}

function node_status($value) {
  $status = array(dumped, expired, queued, posted);
  if (module_exist($value)) {
    return array_intersect($status, node_invoke($value, "status"));
  }
  else if (strlen($value) > 3) {
    $status = array_flip($status);
    return $status[$value];
  }
  else {
    return $status[$value];
  }
}

function node_control($node) {
  global $user, $REQUEST_URI;

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

  if ($user->uid) {
    $choices = array("node.php?id=$node->nid" => t("view node"), "submit.php?mod=$node->type" => t("add node"), "submit.php?mod=$node->type&op=update&id=$node->nid" => t("update node"),  "node.php?op=history&id=$node->nid" => t("view history"));
  }
  else {
    $choices = array("node.php?id=$node->nid" => t("view node"), "node.php?op=history&id=$node->nid" => t("view history"));
  }

  $output .= "<FORM METHOD=\"get\" ACTION=\"\">\n";
  foreach ($choices as $key => $value) $options .= "<OPTION VALUE=\"$key\"". (strstr($REQUEST_URI, "/$key") ? " SELECTED" : "") .">". check_form($value) ."</OPTION>\n";
  $output .= " <SELECT NAME=\"op\" ONCHANGE=\"visit(this.options[this.selectedIndex].value)\">$options</SELECT>\n";
  $output .= "</FORM>\n";

  return $output;
}

function node_preview($node) {
  foreach ($node as $key=>$value) {
    if ($value) $node[$key] = is_array($value) ? node_preview($value) : check_preview($value);
  }
  return $node;
}


function node_attributes_edit($type, $edit) {
  return meta_form($type, $edit);
}

function node_attributes_save($type, $edit) {
  return meta_save($type, $edit);
}

function node_attributes_view($string) {
  foreach (explode(",", $string) as $attribute) {
    if ($attribute = trim($attribute)) {
      $array[] = "<a href=\"index.php?meta=". urlencode($attribute) ."\">$attribute</a>";
    }
  }
  return $array ? $array : array();
}

function node_index($node) {
  return $node->attributes ? implode(" / ", node_attributes_view($node->attributes)) : "&nbsp;";
}

function node_access($node) {
  global $user, $status;
  return ($node->status == $status[posted]) || ($node->status == $status[queued] && $user->uid) || user_access("administer nodes");
}

function node_moderate($moderate) {
  global $user;

  if ($user->uid && $moderate) {
    foreach ($moderate as $nid => $score) {
      if ($score > 0 && $score < 6) {
        if (db_fetch_object(db_query("SELECT * FROM moderate WHERE uid = '". check_query($user->uid) ."' AND nid = '". check_query($nid) ."'"))) {
          db_query("UPDATE moderate SET score = '". check_query($score) ."' WHERE uid = '". check_query($user->uid) ."' AND nid = '". check_query($nid) ."'");
        }
        else {
          db_query("INSERT INTO moderate (uid, nid, score, timestamp) VALUES ('". check_query($user->uid) ."', '". check_query($nid) ."', '". check_query($score) ."', '". time() ."')");
        }
      }
    }
  }
}

function node_moderation($nid) {
  global $user;

  $node = node_get_object(array("nid" => $nid));

  $values = array("--", 1, 2, 3, 4, 5);

  $moderate = db_fetch_object(db_query("SELECT * FROM moderate WHERE nid = '$node->nid' AND uid = '$user->uid'"));

  foreach ($values as $key => $value) {
    $options .= " <option value=\"$key\"". ($moderate->score == $key ? " selected=\"selected\"" : "") .">$value</option>\n";
  }

  $output .= "accumulated rating: ". ($node->score ? $node->score : "--") ." / $node->votes<br />";
  $output .= "<select name=\"moderate[node][$node->nid]\">$options</select>";

  return $output;
}

?>