<?php
// $Id$

include_once "includes/common.inc";

page_header();

function node_render($node) {
  global $id, $cid, $op, $moderate, $pid, $edit, $theme, $mode, $order, $threshold, $PHP_SELF;

  if (user_access("access content")) {

    if ($node->comment) {
      switch($op) {
        case t("Preview comment"):
          $theme->header();
          comment_preview($edit);
          $theme->footer();
          break;
        case t("Post comment"):
          comment_post($edit);
          $theme->header(check_output($node->title));
          node_view($node);
          comment_render($edit[id], $cid);
          $theme->footer();
          break;
        case "comment":
          $theme->header();
          comment_reply(check_query($cid), check_query($id));
          $theme->footer();
          break;
        case "reply":
          $theme->header();
          comment_reply(check_query($pid), check_query($id));
          $theme->footer();
          break;
        case t("Update settings"):
          comment_settings(check_query($mode), check_query($order), check_query($threshold));
          $theme->header(check_output($node->title));
          node_view($node);
          comment_render($id, $cid);
          $theme->footer();
          break;
        case t("Update ratings"):
          comment_moderate($moderate["comment"]);
          $theme->header(check_output($node->title));
          node_view($node);
          comment_render($id, $cid);
          $theme->footer();
          break;
        default:
          $theme->header(check_output($node->title));
          node_view($node);
          comment_render($id, $cid);
          $theme->footer();
      }
    }
    else {
      $theme->header();
      node_view($node);
      $theme->footer();
    }
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

$number = ($title ? db_num_rows(db_query("SELECT nid FROM node WHERE title = '$title' AND status = 1")) : 1);

if ($number > 1) {
  $result = db_query("SELECT n.*, u.name, u.uid FROM node n LEFT JOIN users u ON n.uid = u.uid WHERE n.title = '$title' AND n.status = 1 ORDER BY created DESC");

  while ($node = db_fetch_object($result)) {
    if (node_access("view", $node)) {
      $output .= "<p><b><a href=\"node.php?id=$node->nid\">". check_output($node->title) ."</a></b><br /><small>$node->type - ". format_name($node) ." - ". format_date($node->ccreated, "small") ."</small></p>";
    }
  }

  $theme->header();
  $theme->box(t("Result"), $output);
  $theme->footer();
}
elseif ($number) {
  $node = ($title ? node_load(array("title" => $title, "status" => 1)) : node_load(array("nid" => ($edit[id] ? $edit[id] : $id))));

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