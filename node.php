<?php

include "includes/common.inc";

function node_failure() {
  global $theme;
  $theme->header();
  $theme->box(t("Not found"), t("The node you are looking for does not exist yet or is no longer accessible:") ."<UL><LI><A HREF=\"search.php\">". t("search node") ."</A></LI><LI><A HREF=\"submit.php\">". t("add node") ."</A></LI></UL>\n");
  $theme->footer();
}

function node_history($node) {
  global $status;
  if ($node->status == $status[expired] || $node->status == $status[posted]) {
    $output .= "<DT><B>". format_date($node->timestamp) ." by ". format_username($node->userid) .":</B></DT><DD>". check_output($node->log, 1) ."<P></DD>";
  }
  if ($node->pid) {
    $output .= node_history(node_get_object("nid", $node->pid));
  }
  return $output;
}

$number = ($title ? db_result(db_query("SELECT COUNT(nid) FROM node WHERE title = '$title' AND status = $status[posted]")) : 1);

if ($number > 1) {
  $result = db_query("SELECT n.*, u.userid FROM node n LEFT JOIN users u ON n.author = u.id WHERE n.title = '$title'");

  while ($node = db_fetch_object($result)) {
    if (node_visible($node)) {
      $output .= "<P><B><A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A></B><BR><SMALL>$node->type - ". format_username($node->userid) ." - ". format_date($node->timestamp, "small") ."</SMALL></P>";
    }
  }

  $theme->header();
  $theme->box(t("Result"), $output);
  $theme->footer();
}
elseif ($number) {
  $node = ($title ? node_get_object(title, check_input($title)) : node_get_object(nid, check_input($id)));
  if ($node && node_visible($node)) {
    switch ($op) {
      case "history":
        $theme->header();
        $theme->box(t("History"), node_control($node) ."<DL>". node_history($node) ."</DL>");
        $theme->footer();
        break;
      default:
        user_rehash();
        node_view($node, 1);
    }
  }
  else {
    node_failure();
  }
}
else {
  node_failure();
}

?>