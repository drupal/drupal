<?php

function _node_get($field, $value) {
  $result = db_query("SELECT lid, type FROM nodes WHERE $field = '$value'");
  if ($node = db_fetch_object($result)) {
    return db_query("SELECT n.*, l.*, u.userid FROM nodes n LEFT JOIN $node->type l ON n.lid = l.id LEFT JOIN users u ON n.author = u.id WHERE n.$field = '$value'");
  }
}

function node_get_object($field, $value) {
  return db_fetch_object(_node_get($field, $value));
}

function node_get_array($field, $value) {
  return db_fetch_array(_node_get($field, $value));
}

function node_del_object($field, $value) {
  if ($node = node_get_object($field, $value)) {
    db_query("DELETE FROM nodes WHERE nid = '$node->nid'");
    db_query("DELETE FROM $node->type WHERE node = '$node->nid'");
    return $node;
  }
}

function node_del_array($field, $value) {
  if ($node = node_get_array($field, $value)) {
    db_query("DELETE FROM nodes WHERE nid = '$node[nid]'");
    db_query("DELETE FROM $node[type] WHERE node = '$node[nid]'");
    return $node;
  }
}

function node_save($node) {
  global $user;

  $rows = array(nid, lid, type, title, score, votes, author, status, timestamp);

  // insert or update node:
  if ($node[nid]) {
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

    db_query("UPDATE nodes SET $u1 WHERE nid = '$node[nid]'");
    db_query("UPDATE $node[type] SET $u2 WHERE node = '$node[nid]'");
  }
  else {
    // setup default values:
    $node = array_merge(array(type => "?", title => "?", score => 0, votes => 0, author => $user->id, status => 1, timestamp => time()), $node);

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

//  if (!_node_get("title = $node[title] AND timestamp < ". time() ." - 60")) {
      db_query("INSERT INTO nodes ($f1) VALUES ($v1)");
      if ($nid = db_insert_id()) {
        $lid = db_query("INSERT INTO $node[type] ($f2, node) VALUES ($v2, $nid)");
        if ($lid = db_insert_id()) {
          db_query("UPDATE nodes SET lid = '$lid' WHERE nid = '$nid'");
        }
        else {
          db_query("DELETE FROM nodes WHERE nid = '$nid'");
        }
      }
//  }
  }
}

function node_view($node, $page = 0) {
  if ($node->type) {
    $function = $node->type ."_view";
    $function($node);
  }
  else {
    print "not found";
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

  $choices = array("/node.php?id=$node->nid" => t("view node"), "/node.php?op=update&id=$node->nid" => t("suggest update"), "/node.php?op=history&id=$node->nid" => t("view history"), "/node.php?op=referer&id=$node->nid" => t("view referers"));

  $output .= "<FORM METHOD=\"get\" ACTION=\"\">\n";
  foreach ($choices as $key => $value) $options .= "<OPTION VALUE=\"$key\"". ($key == $REQUEST_URI ? " SELECTED" : "") .">". check_select($value) ."</OPTION>\n";
  $output .= " <SELECT NAME=\"op\" ONCHANGE=\"visit(this.options[this.selectedIndex].value)\">$options</SELECT>\n";
  $output .= "</FORM>\n";

  return $output;
}

function node_visible($node) {
  global $user;
  return ($node->status == 2) || ($node->status == 1 && $user->id) || user_access($user, "node");
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
