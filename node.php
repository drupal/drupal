<?php

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
          $theme->header();
          node_view($node);
          comment_render($edit[id], $cid);
          $theme->footer();
          break;
        case t("Add comment"):
          $theme->header();
          comment_reply(check_input($cid), check_input($id));
          $theme->footer();
          break;
        case "reply":
          $theme->header();
          comment_reply(check_input($pid), check_input($id));
          $theme->footer();
          break;
        case t("Update settings"):
          comment_settings(check_input($mode), check_input($order), check_input($threshold));
          $theme->header();
          node_view($node);
          comment_render($id, $cid);
          $theme->footer();
          break;
        case t("Moderate comments"):
          comment_moderate($moderate);
          $theme->header();
          node_view($node);
          comment_render($id, $cid);
          $theme->footer();
          break;
        default:
          $theme->header();
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
  $theme->box(t("Not found"), t("The node you are looking for does not exist yet or is no longer accessible.") ."\n");
  $theme->footer();
}

function node_history($node) {
  global $status;
  if ($node->status == $status[expired] || $node->status == $status[posted]) {
    $output .= "<dt><b>". format_date($node->timestamp) ." by ". format_name($node) .":</b></dt><dd>". check_output($node->log, 1) ."<p /></dd>";
  }
  if ($node->pid) {
    $output .= node_history(node_get_object(array("nid" => $node->pid)));
  }
  return $output;
}

$number = ($title ? db_num_row(db_query("SELECT nid FROM node WHERE title = '$title' AND status = $status[posted]")) : 1);

if ($number > 1) {
  $result = db_query("SELECT n.*, u.name, u.uid FROM node n LEFT JOIN user u ON n.author = u.uid WHERE n.title = '$title' AND n.status = $status[posted] ORDER BY timestamp DESC");

  while ($node = db_fetch_object($result)) {
    if (node_access($node)) {
      $output .= "<P><B><A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A></B><BR><SMALL>$node->type - ". format_name($node) ." - ". format_date($node->timestamp, "small") ."</SMALL></P>";
    }
  }

  $theme->header();
  $theme->box(t("Result"), $output);
  $theme->footer();
}
elseif ($number) {
  $node = ($title ? node_get_object(array("title" => $title)) : node_get_object(array("nid" => ($edit[id] ? $edit[id] : $id))));
  if ($node && node_access($node)) {
    switch ($op) {
      case "history":
        $theme->header();
        $theme->box(t("History"), node_control($node) ."<DL>". node_history($node) ."</DL>");
        $theme->footer();
        break;
      default:
        node_render($node);
    }
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