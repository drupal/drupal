<?php

include_once "includes/common.inc";

page_header();

function node_render($node) {
  global $id, $cid, $op, $moderate, $pid, $edit, $theme, $mode, $order, $threshold, $PHP_SELF;

  if (user_access("access content")) {

    $theme->header(check_output($node->title));

    node_view($node);

    if (function_exists("comment_render") && $node->comment) {
      comment_render($node, $cid);
    }

    $theme->footer();

  }
  else {
    $theme->header();
    $theme->box(t("Access denied"), message_access());
    $theme->footer();
  }
}

function node_failure() {
  global $theme;
  $theme->header();
  $theme->box(t("Not found"), t("The node you are looking for does no longer exist or is not accessible without the proper access rights.") ."\n");
  $theme->footer();
}

$number = ($title ? db_num_rows(db_query("SELECT nid FROM node WHERE title = '%s' AND status = 1", $title)) : 1);

if ($number > 1) {
  $result = db_query("SELECT n.*, u.name, u.uid FROM node n LEFT JOIN users u ON n.uid = u.uid WHERE n.title = '%s' AND n.status = 1 ORDER BY created DESC", $title);

  while ($node = db_fetch_object($result)) {
    if (node_access("view", $node)) {
      $output .= "<p><b>". l(check_output($node->title), array("id" => $node->nid)) ."</b><br /><small>$node->type - ". format_name($node) ." - ". format_date($node->ccreated, "small") ."</small></p>";
    }
  }

  $theme->header();
  $theme->box(t("Result"), $output);
  $theme->footer();
}
elseif ($number) {
  $node = ($title ? node_load(array("title" => $title, "status" => 1)) : node_load(array("status" => 1, "nid" => ($edit["id"] ? $edit["id"] : $id))));

  if (node_access("view", $node)) {
    if (isset($revision)) {
      $node = $node->revisions[$revision]["node"];
    }

    node_render($node);
  }
  else {
    node_failure();
  }

}
else {
  node_failure();
}

page_footer();

?>